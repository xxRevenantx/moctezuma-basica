<?php

namespace App\Http\Controllers;

use App\Models\cicloEscolar;
use App\Models\Dia;
use App\Models\Director;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\PersonaNivel;
use App\Models\AsignacionMateria;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Periodos;
use App\Models\Persona;
use App\Models\Semestre;
use App\Services\ListaAcademicaService;
use App\Services\CalificacionOficialPrimariaService;
use App\Services\PromedioBachilleratoService;
use App\Services\PromedioSecundariaService;
use App\Support\CalificacionBachillerato;
use App\Support\PromedioExcel;
use App\Support\ReglasMateriaBachillerato;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Calificacion;
use App\Models\MateriaPromediar;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;


class PDFController extends Controller
{

    // BOLET ANUAL DE LOS PERIODOS Y PARCIALES Y DIPLOMA ANUAL DE LOS PERIODOS Y PARCIALES
    public function boletareconocimientoPromedioPdf(Request $request, string $slug_nivel, string $tipo)
    {
        $request->validate([
            'fecha' => ['nullable', 'date'],
        ]);

        $slugNivel = $slug_nivel;

        $generacionId = $request->integer('generacion_id');
        $gradoId = $request->integer('grado_id');
        $grupoId = $request->integer('grupo_id');
        $semestreId = $request->integer('semestre_id');
        $inscripcionId = $request->integer('inscripcion_id');
        $cicloEscolarId = $request->integer('ciclo_escolar_id');

        if (
            blank($slugNivel) ||
            blank($generacionId) ||
            blank($gradoId) ||
            blank($grupoId) ||
            blank($inscripcionId)
        ) {
            abort(422, 'Los parámetros slug_nivel, generacion_id, grado_id, grupo_id e inscripcion_id son obligatorios.');
        }

        $escuela = \App\Models\Escuela::query()->first();

        if (!$escuela) {
            abort(404, 'No se encontró la escuela.');
        }

        $nivel = Nivel::query()
            ->where('slug', $slugNivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $esBachillerato = $this->esBachillerato($nivel);
        $esSecundaria = $this->esSecundaria($nivel);

        abort_if($esBachillerato && !in_array($tipo, ['semestral', 'reconocimiento', 'diploma'], true), 404);
        abort_if(!$esBachillerato && !in_array($tipo, ['boleta', 'reconocimiento', 'diploma'], true), 404);

        $generacion = Generacion::query()
            ->where('id', $generacionId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$generacion) {
            abort(404, 'Generación no encontrada o no pertenece al nivel seleccionado.');
        }

        $grado = Grado::query()
            ->where('id', $gradoId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$grado) {
            abort(404, 'Grado no encontrado o no pertenece al nivel seleccionado.');
        }

        $grupoQuery = Grupo::query()
            ->with('asignacionGrupo')
            ->where('id', $grupoId)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id);

        if ($esBachillerato) {
            if (blank($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $grupoQuery->where('semestre_id', $semestreId);
        } else {
            $grupoQuery->whereNull('semestre_id');
        }

        $grupo = $grupoQuery->first();

        if (!$grupo) {
            abort(404, 'Grupo no encontrado para el contexto seleccionado.');
        }

        $semestre = null;

        if ($esBachillerato) {
            $semestre = Semestre::query()
                ->where('id', $semestreId)
                ->where('grado_id', $grado->id)
                ->first();

            if (!$semestre) {
                abort(404, 'Semestre no encontrado o no pertenece al grado seleccionado.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Ciclo escolar
        |--------------------------------------------------------------------------
        */

        $cicloEscolar = null;

        if (!blank($cicloEscolarId)) {
            $cicloEscolar = cicloEscolar::query()
                ->where('id', $cicloEscolarId)
                ->first();
        }

        if (!$cicloEscolar) {
            $cicloEscolar = cicloEscolar::query()
                ->orderByDesc('id')
                ->first();
        }

        if (!$cicloEscolar) {
            abort(404, 'No se encontró el ciclo escolar.');
        }

        $cicloEscolarTexto = $cicloEscolar->inicio_anio . '-' . $cicloEscolar->fin_anio;

        /*
        |--------------------------------------------------------------------------
        | Inscripción del alumno
        |--------------------------------------------------------------------------
        */

        $inscripcion = $this->buscarInscripcionAlumnoPdf(
            inscripcionId: $inscripcionId,
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            grupo: $grupo,
            esBachillerato: $esBachillerato
        );

        /*
         * En bachillerato el alumno puede estar actualmente en el semestre
         * siguiente. Se permite recuperar su inscripción histórica y el
         * servicio semestral valida más adelante que sí pertenezca al ciclo,
         * generación, grado, grupo y semestre solicitados.
         */
        if (! $inscripcion && $esBachillerato) {
            $inscripcion = Inscripcion::withTrashed()
                ->whereKey($inscripcionId)
                ->where('nivel_id', $nivel->id)
                ->first();
        }

        if (!$inscripcion) {
            abort(404, 'Inscripción no encontrada para el contexto seleccionado.');
        }

        $nombreAlumno = trim(
            ($inscripcion->apellido_paterno ?? '') . ' ' .
                ($inscripcion->apellido_materno ?? '') . ' ' .
                ($inscripcion->nombre ?? '')
        );

        if ($nombreAlumno === '') {
            $nombreAlumno = 'ALUMNO';
        }

        /*
        |--------------------------------------------------------------------------
        | Periodos/parciales que se van a unir
        |--------------------------------------------------------------------------
        */

        $numerosPeriodos = $esBachillerato ? [1, 2] : [1, 2, 3];

        $periodosQuery = Periodos::query()
            ->with([
                'cicloEscolar',
                'periodoBasica',
                'parcialBachillerato',
                'mesesBasica',
                'mesesBachillerato',
            ])
            ->where('nivel_id', $nivel->id);

        if (Schema::hasColumn('periodos', 'ciclo_escolar_id')) {
            $periodosQuery->where('ciclo_escolar_id', $cicloEscolar->id);
        }

        if ($esBachillerato) {
            $periodosQuery
                ->where('generacion_id', $generacion->id)
                ->where('semestre_id', $semestre->id)
                ->whereIn('parcial_bachillerato_id', $numerosPeriodos)
                ->whereNull('periodo_basica_id');
        } else {
            $periodosQuery
                ->whereIn('periodo_basica_id', $numerosPeriodos)
                ->whereNull('parcial_bachillerato_id');
        }

        $periodos = $periodosQuery
            ->get()
            ->mapWithKeys(function ($periodo) use ($esBachillerato) {
                $numero = $esBachillerato
                    ? (int) $periodo->parcial_bachillerato_id
                    : (int) $periodo->periodo_basica_id;

                return [$numero => $periodo];
            });

        if ($periodos->isEmpty()) {
            abort(404, 'No se encontraron periodos para generar la boleta de promedio.');
        }

        $periodosResumen = [];

        foreach ($numerosPeriodos as $numeroPeriodo) {
            $periodo = $periodos->get($numeroPeriodo);

            $periodosResumen[$numeroPeriodo] = [
                'numero' => $numeroPeriodo,
                'periodo_id' => $periodo?->id,
                'nombre' => $esBachillerato
                    ? mb_strtoupper($periodo?->parcialBachillerato?->parcial ?? $periodo?->parcialBachillerato?->descripcion ?? 'PARCIAL ' . $numeroPeriodo)
                    : mb_strtoupper($periodo?->periodoBasica?->periodo ?? $periodo?->periodoBasica?->descripcion ?? 'PERIODO ' . $numeroPeriodo),
                'fecha_inicio' => $periodo?->fecha_inicio,
                'fecha_fin' => $periodo?->fecha_fin,
            ];
        }

        $idsPeriodos = $periodos
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Materias calificables
        |--------------------------------------------------------------------------
        */

        $queryMaterias = AsignacionMateria::query()
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->select([
                'asignacion_materias.*',
                'materias.materia as materia',
                'materias.clave as clave',
                'materias.slug as slug',
                'materias.calificable as calificable',
                'materias.participa_en_calificacion_oficial as participa_en_calificacion_oficial',
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('asignacion_materias.ciclo_escolar_id', $cicloEscolar->id)
            ->where('asignacion_materias.estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA);

        /*
         * Bachillerato: las materias extra se consultan para mostrarlas en una
         * tabla informativa independiente. Nunca participan en parciales,
         * promedio semestral, promedio final, lugares ni reconocimientos.
         */
        if ($esBachillerato) {
            ReglasMateriaBachillerato::aplicarCapturables($queryMaterias);
        } else {
            $queryMaterias->where('materias.calificable', true);
        }

        $this->aplicarFiltroSemestreAsignacion($queryMaterias, $esBachillerato, $semestre?->id);

        if ($esSecundaria) {
            $queryMaterias
                ->where('materias.extra', false)
                ->where('materias.receso', false)
                ->where('materias.participa_en_calificacion_oficial', true)
                ->whereIn('materias.campo_formativo_id', function ($query): void {
                    $query->select('id')
                        ->from('campos_formativos')
                        ->where('activo', true)
                        ->where('slug', '!=', 'sin-campo-formativo');
                });
        }

        $materias = $queryMaterias
            ->orderByRaw('CASE WHEN asignacion_materias.orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('asignacion_materias.orden')
            ->orderBy('asignacion_materias.id')
            ->get();

        $idsMaterias = $materias->pluck('id')->values()->all();

        /*
        |--------------------------------------------------------------------------
        | Calificaciones del alumno en todos los periodos
        |--------------------------------------------------------------------------
        */

        $queryCalificaciones = Calificacion::query()
            ->where('inscripcion_id', $inscripcion->id);

        if (!empty($idsPeriodos)) {
            $queryCalificaciones->whereIn('periodo_id', $idsPeriodos);
        }

        if (!empty($idsMaterias)) {
            $queryCalificaciones->whereIn('asignacion_materia_id', $idsMaterias);
        }

        if (Schema::hasColumn('calificaciones', 'nivel_id')) {
            $queryCalificaciones->where('nivel_id', $nivel->id);
        }

        if (Schema::hasColumn('calificaciones', 'generacion_id')) {
            $queryCalificaciones->where('generacion_id', $generacion->id);
        }

        if (Schema::hasColumn('calificaciones', 'grado_id')) {
            $queryCalificaciones->where('grado_id', $grado->id);
        }

        if (Schema::hasColumn('calificaciones', 'grupo_id')) {
            $queryCalificaciones->where('grupo_id', $grupo->id);
        }

        if (Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
            $queryCalificaciones->where('ciclo_escolar_id', $cicloEscolar->id);
        }

        if (Schema::hasColumn('calificaciones', 'semestre_id')) {
            if ($esBachillerato) {
                $queryCalificaciones->where('semestre_id', $semestre->id);
            } else {
                $queryCalificaciones->whereNull('semestre_id');
            }
        }

        $calificacionesMap = $queryCalificaciones
            ->get()
            ->groupBy('asignacion_materia_id')
            ->map(function ($registrosMateria) {
                return $registrosMateria->keyBy('periodo_id');
            });

        /*
        |--------------------------------------------------------------------------
        | Número de materias para promediar
        |--------------------------------------------------------------------------
        */

        $queryMateriaPromediar = MateriaPromediar::query()
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id);

        if (Schema::hasColumn('materia_promediar', 'grupo_id')) {
            $queryMateriaPromediar->where('grupo_id', $grupo->id);
        }

        if ($esBachillerato) {
            $queryMateriaPromediar->where('semestre_id', $semestre->id);
        } else {
            $queryMateriaPromediar->whereNull('semestre_id');
        }

        $registroPromedio = $queryMateriaPromediar->first();

        $numeroMateriasPromediar = (int) ($registroPromedio?->numero_materias ?? 0);

        if ($numeroMateriasPromediar <= 0) {
            $numeroMateriasPromediar = $materias
                ->filter(function ($materia) use ($esBachillerato) {
                    return (int) ($materia->calificable ?? 0) === 1
                        && (int) ($materia->extra ?? 0) === 0
                        && (int) ($materia->receso ?? 0) === 0
                        && ($esBachillerato || (int) ($materia->participa_en_calificacion_oficial ?? 1) === 1);
                })
                ->count();
        }

        /*
         * promedio-numerico-pro:
         * Se toma solo el primer decimal sin redondear.
         * Se agrega un ajuste mínimo para evitar errores internos de precisión.
         * Ejemplo: 8.777777777777778 se muestra como 8.7.
         */
        $truncarPromedio = function (float $valor): float {
            return PromedioExcel::truncar($valor) ?? 0.0;
        };

        $formatearPromedio = function (?float $valor) use ($truncarPromedio): string {
            if ($valor === null) {
                return '—';
            }

            return number_format($truncarPromedio($valor), 1, '.', '');
        };

        /*
        |--------------------------------------------------------------------------
        | Filas de materias y promedios por periodo
        |--------------------------------------------------------------------------
        */

        $filasMaterias = [];

        $sumasPorPeriodo = [];
        $capturadasPorPeriodo = [];

        foreach ($numerosPeriodos as $numeroPeriodo) {
            $sumasPorPeriodo[$numeroPeriodo] = 0;
            $capturadasPorPeriodo[$numeroPeriodo] = 0;
        }

        $especiales = 0;
        $reprobadas = 0;
        $aprobadas = 0;
        $pendientes = 0;
        $totalCeldas = 0;

        foreach ($materias as $materia) {
            $calificacionesPeriodo = [];
            $sumaMateria = 0;
            $capturadasMateria = 0;

            foreach ($numerosPeriodos as $numeroPeriodo) {
                $periodo = $periodos->get($numeroPeriodo);
                $registro = $periodo
                    ? $calificacionesMap->get($materia->id)?->get($periodo->id)
                    : null;

                $valor = $registro
                    ? strtoupper(trim((string) $registro->calificacion))
                    : '';

                $totalCeldas++;

                if ($valor === '') {
                    $pendientes++;

                    $calificacionesPeriodo[$numeroPeriodo] = [
                        'calificacion' => '—',
                        'estado' => 'Sin captura',
                        'porcentaje' => 0,
                    ];

                    continue;
                }

                if (is_numeric($valor)) {
                    $numero = (float) $valor;

                    if ($numero >= 0 && $numero <= 10) {
                        $capturadasMateria++;
                        $sumaMateria += $numero;

                        if (
                            (int) ($materia->calificable ?? 0) === 1
                            && (int) ($materia->extra ?? 0) === 0
                            && (int) ($materia->receso ?? 0) === 0
                        ) {
                            $sumasPorPeriodo[$numeroPeriodo] += $numero;
                            $capturadasPorPeriodo[$numeroPeriodo]++;
                        }

                        $estado = match (true) {
                            $numero < 6 => 'En riesgo',
                            $numero < 8 => 'Regular',
                            default => 'Aprobado',
                        };

                        $calificacionesPeriodo[$numeroPeriodo] = [
                            'calificacion' => $valor,
                            'estado' => $estado,
                            'porcentaje' => min(100, $numero * 10),
                        ];

                        continue;
                    }
                }

                $especiales++;

                $calificacionesPeriodo[$numeroPeriodo] = [
                    'calificacion' => $valor,
                    'estado' => 'Especial',
                    'porcentaje' => 0,
                ];
            }

            $promedioMateriaNumero = null;
            $promedioMateria = '—';
            $estadoMateria = 'Sin datos';

            if ($capturadasMateria > 0) {
                $promedioMateriaNumero = (float) ($sumaMateria / $capturadasMateria);
                $promedioMateria = $formatearPromedio($promedioMateriaNumero);

                if ($promedioMateriaNumero < 6) {
                    $estadoMateria = 'En riesgo';
                    $reprobadas++;
                } elseif ($promedioMateriaNumero < 8) {
                    $estadoMateria = 'Regular';
                    $aprobadas++;
                } else {
                    $estadoMateria = 'Aprobado';
                    $aprobadas++;
                }
            }

            $filasMaterias[] = [
                'materia' => $materia->materia ?: 'Materia',
                'clave' => $materia->clave ?: '—',
                'extra' => (int) ($materia->extra ?? 0),
                'receso' => (int) ($materia->receso ?? 0),
                'calificaciones' => $calificacionesPeriodo,
                'promedio' => $promedioMateria,
                'promedio_numero' => $promedioMateriaNumero,
                'estado' => $estadoMateria,
            ];
        }

        /*
         * Se conserva el detalle de materias extra antes de que bachillerato
         * sustituya las materias oficiales con PromedioBachilleratoService.
         */
        $filasMateriasExtras = $esBachillerato
            ? collect($filasMaterias)
            ->filter(fn(array $fila) => (int) ($fila['extra'] ?? 0) === 1)
            ->values()
            ->all()
            : [];

        /*
        |--------------------------------------------------------------------------
        | Promedios finales
        |--------------------------------------------------------------------------
        */

        $promediosPeriodos = [];
        $promediosPeriodosPrecisos = [];

        foreach ($numerosPeriodos as $numeroPeriodo) {
            $promedioPeriodoPreciso = null;

            if ($capturadasPorPeriodo[$numeroPeriodo] > 0) {
                /*
                 * Regla PROMEDIO de Excel: solo participan valores numéricos;
                 * textos, claves especiales y vacíos no suman ni cuentan como divisor.
                 * No se trunca este resultado intermedio.
                 */
                $promedioPeriodoPreciso = (float) (
                    $sumasPorPeriodo[$numeroPeriodo] / $capturadasPorPeriodo[$numeroPeriodo]
                );
            }

            $promediosPeriodosPrecisos[$numeroPeriodo] = $promedioPeriodoPreciso;
            $promediosPeriodos[$numeroPeriodo] = $formatearPromedio($promedioPeriodoPreciso);
        }

        $promedio = '—';
        $promedioNumero = PromedioExcel::calcular($promediosPeriodosPrecisos);
        $porcentajePromedio = 0;
        $estadoPromedio = 'Sin datos';

        if ($promedioNumero !== null) {
            // El truncamiento se aplica únicamente al mostrar el promedio final.
            $promedio = PromedioExcel::formatear($promedioNumero);
            $porcentajePromedio = min(100, $promedioNumero * 10);

            if ($promedioNumero < 6) {
                $estadoPromedio = 'Reprobado';
            } elseif ($promedioNumero < 8) {
                $estadoPromedio = 'Regular';
            } else {
                $estadoPromedio = 'Aprobado';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Lugar por grupo para el reconocimiento
        |--------------------------------------------------------------------------
        |
        | Se calcula igual que en Promedios Generales:
        | - Cada grupo tiene sus propios lugares.
        | - Los empates comparten el mismo lugar.
        | - En básica se divide entre los 3 periodos aunque falte captura.
        | - Solo se usan calificaciones numéricas de materias normales.
        */

        $lugarAlumno = null;
        $textoLugarAlumno = 'Pendiente';

        $queryInscripcionesGrupo = \App\Models\Inscripcion::query()
            ->select(['id', 'matricula', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

        if (Schema::hasColumn('inscripciones', 'semestre_id')) {
            if ($esBachillerato && $semestre) {
                $queryInscripcionesGrupo->where('semestre_id', $semestre->id);
            } else {
                $queryInscripcionesGrupo->whereNull('semestre_id');
            }
        }

        $inscripcionesGrupo = $queryInscripcionesGrupo->get();
        $idsInscripcionesGrupo = $inscripcionesGrupo->pluck('id')->filter()->values()->all();

        $calificacionesGrupoPorAlumno = collect();

        if (!empty($idsInscripcionesGrupo)) {
            $queryCalificacionesGrupo = Calificacion::query()
                ->whereIn('inscripcion_id', $idsInscripcionesGrupo);

            if (!empty($idsPeriodos)) {
                $queryCalificacionesGrupo->whereIn('periodo_id', $idsPeriodos);
            }

            if (!empty($idsMaterias)) {
                $queryCalificacionesGrupo->whereIn('asignacion_materia_id', $idsMaterias);
            }

            if (Schema::hasColumn('calificaciones', 'nivel_id')) {
                $queryCalificacionesGrupo->where('nivel_id', $nivel->id);
            }

            if (Schema::hasColumn('calificaciones', 'generacion_id')) {
                $queryCalificacionesGrupo->where('generacion_id', $generacion->id);
            }

            if (Schema::hasColumn('calificaciones', 'grado_id')) {
                $queryCalificacionesGrupo->where('grado_id', $grado->id);
            }

            if (Schema::hasColumn('calificaciones', 'grupo_id')) {
                $queryCalificacionesGrupo->where('grupo_id', $grupo->id);
            }

            if (Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
                $queryCalificacionesGrupo->where('ciclo_escolar_id', $cicloEscolar->id);
            }

            if (Schema::hasColumn('calificaciones', 'semestre_id')) {
                if ($esBachillerato && $semestre) {
                    $queryCalificacionesGrupo->where('semestre_id', $semestre->id);
                } else {
                    $queryCalificacionesGrupo->whereNull('semestre_id');
                }
            }

            $calificacionesGrupoPorAlumno = $queryCalificacionesGrupo
                ->get()
                ->groupBy('inscripcion_id')
                ->map(function ($registrosAlumno) {
                    return $registrosAlumno
                        ->groupBy('asignacion_materia_id')
                        ->map(function ($registrosMateria) {
                            return $registrosMateria->keyBy('periodo_id');
                        });
                });
        }

        $calcularPromedioFinalAlumno = function (int $inscripcionAlumnoId) use ($calificacionesGrupoPorAlumno, $materias, $periodos, $numerosPeriodos): ?float {
            $mapaAlumno = $calificacionesGrupoPorAlumno->get($inscripcionAlumnoId, collect());
            $promediosPeriodoAlumno = [];

            foreach ($numerosPeriodos as $numeroPeriodo) {
                $periodo = $periodos->get($numeroPeriodo);

                if (!$periodo) {
                    continue;
                }

                $valoresPeriodo = [];

                foreach ($materias as $materia) {
                    if ((int) ($materia->extra ?? 0) === 1 || (int) ($materia->receso ?? 0) === 1) {
                        continue;
                    }

                    $registro = $mapaAlumno->get($materia->id)?->get($periodo->id);
                    $valor = $registro ? trim((string) $registro->calificacion) : null;
                    $numero = PromedioExcel::valoresNumericos([$valor])->first();

                    if ($numero !== null) {
                        $valoresPeriodo[] = $numero;
                    }
                }

                $promedioPeriodo = PromedioExcel::calcular($valoresPeriodo);

                if ($promedioPeriodo !== null) {
                    $promediosPeriodoAlumno[] = $promedioPeriodo;
                }
            }

            // Precisión completa para orden, lugares y estadísticas.
            return PromedioExcel::calcular($promediosPeriodoAlumno);
        };

        $promediosAlumnosGrupo = $inscripcionesGrupo
            ->map(function ($inscripcionGrupo) use ($calcularPromedioFinalAlumno) {
                return [
                    'inscripcion_id' => (int) $inscripcionGrupo->id,
                    'promedio_final' => $calcularPromedioFinalAlumno((int) $inscripcionGrupo->id),
                ];
            });

        $promediosUnicosDesc = $promediosAlumnosGrupo
            ->filter(fn(array $alumnoGrupo) => ($alumnoGrupo['promedio_final'] ?? null) !== null)
            ->pluck('promedio_final')
            ->sortDesc()
            ->map(fn($valor) => PromedioExcel::claveComparacion($valor))
            ->filter()
            ->unique()
            ->values();

        $promedioClaveAlumno = PromedioExcel::claveComparacion($promedioNumero);

        $indiceLugarAlumno = $promedioClaveAlumno !== null
            ? $promediosUnicosDesc->search($promedioClaveAlumno)
            : false;

        if ($indiceLugarAlumno !== false) {
            $lugarAlumno = ((int) $indiceLugarAlumno) + 1;
            $textoLugarAlumno = $lugarAlumno . '° lugar';
        }

        /*
        |--------------------------------------------------------------------------
        | Fuente académica oficial por nivel
        |--------------------------------------------------------------------------
        |
        | El bloque anterior construye la estructura institucional del PDF.
        | Después se reemplazan promedios, lugares y detalle por la misma fuente
        | utilizada en Promedios Generales:
        |
        | - Primaria: promedio de los cuatro campos formativos.
        | - Secundaria: promedio de los promedios anuales precisos de materias.
        | - Bachillerato: promedio semestral de los promedios finales por materia.
        | - Sin truncamientos intermedios; se trunca únicamente al presentar.
        */
        $reporteAcademico = null;
        $filaAcademica = null;

        if ($nivel->slug === 'primaria') {
            $reporteAcademico = app(CalificacionOficialPrimariaService::class)->reporteAnual(
                nivelId: (int) $nivel->id,
                cicloEscolarId: (int) $cicloEscolar->id,
                generacionId: (int) $generacion->id,
                gradoId: (int) $grado->id,
                grupoId: (int) $grupo->id,
            );
        } elseif ($nivel->slug === 'secundaria') {
            $reporteAcademico = app(PromedioSecundariaService::class)->reporteAnual(
                nivelId: (int) $nivel->id,
                cicloEscolarId: (int) $cicloEscolar->id,
                generacionId: (int) $generacion->id,
                gradoId: (int) $grado->id,
                grupoId: (int) $grupo->id,
            );
        } elseif ($esBachillerato) {
            $reporteAcademico = app(PromedioBachilleratoService::class)->reporteSemestral(
                nivelId: (int) $nivel->id,
                cicloEscolarId: (int) $cicloEscolar->id,
                generacionId: (int) $generacion->id,
                gradoId: (int) $grado->id,
                grupoId: (int) $grupo->id,
                semestreId: (int) $semestre->id,
            );
        }

        if (is_array($reporteAcademico)) {
            $filasAcademicas = collect($reporteAcademico['alumnos'] ?? []);
            $filaAcademica = $filasAcademicas->firstWhere('inscripcion_id', (int) $inscripcion->id);

            if ($filaAcademica) {
                $promediosPeriodosPrecisos = $filaAcademica['promedios_periodo_precisos'] ?? [];
                $promediosPeriodos = collect($numerosPeriodos)
                    ->mapWithKeys(fn(int $numero) => [
                        $numero => PromedioExcel::formatear(
                            $promediosPeriodosPrecisos[$numero] ?? null,
                            1,
                            '—'
                        ),
                    ])
                    ->all();

                $promedioNumero = $nivel->slug === 'secundaria'
                    ? ($filaAcademica['promedio_general_preciso'] ?? null)
                    : ($filaAcademica['promedio_general_preciso']
                        ?? $filaAcademica['promedio_provisional_preciso']
                        ?? null);
                $promedio = PromedioExcel::formatear($promedioNumero, 1, '—');
                $porcentajePromedio = $promedioNumero !== null
                    ? min(100, ((float) $promedioNumero) * 10)
                    : 0;

                /*
                 * En secundaria, la boleta, el reconocimiento, el diploma y los
                 * ZIP deben usar exactamente el mismo detalle por materia que la
                 * sección Promedios Generales. El promedio anual solo existe si
                 * están capturados P1, P2 y P3; se conserva preciso y se trunca
                 * únicamente al presentarlo.
                 */
                if ($nivel->slug === 'secundaria') {
                    $filasMaterias = collect($filaAcademica['materias'] ?? [])
                        ->map(function (array $materia) use ($numerosPeriodos): array {
                            $calificacionesPeriodo = [];

                            foreach ($numerosPeriodos as $numeroPeriodo) {
                                $valor = $materia['evaluaciones'][$numeroPeriodo] ?? null;
                                $especial = $materia['especiales'][$numeroPeriodo] ?? null;

                                if (is_numeric($valor)) {
                                    $numero = (float) $valor;
                                    $estadoPeriodo = match (true) {
                                        $numero < 6.0 => 'En riesgo',
                                        $numero < 8.0 => 'Regular',
                                        default => 'Aprobado',
                                    };

                                    $calificacionesPeriodo[$numeroPeriodo] = [
                                        'calificacion' => PromedioExcel::formatear($numero, 1, '—'),
                                        'estado' => $estadoPeriodo,
                                        'porcentaje' => min(100, $numero * 10),
                                    ];

                                    continue;
                                }

                                if (filled($especial)) {
                                    $calificacionesPeriodo[$numeroPeriodo] = [
                                        'calificacion' => mb_strtoupper(trim((string) $especial)),
                                        'estado' => 'Especial',
                                        'porcentaje' => 0,
                                    ];

                                    continue;
                                }

                                $calificacionesPeriodo[$numeroPeriodo] = [
                                    'calificacion' => '—',
                                    'estado' => 'Sin captura',
                                    'porcentaje' => 0,
                                ];
                            }

                            $completa = (bool) ($materia['completo'] ?? false);
                            $promedioMateriaPreciso = $completa
                                ? ($materia['promedio_final_preciso'] ?? null)
                                : null;

                            $estadoMateria = match (true) {
                                !$completa || !is_numeric($promedioMateriaPreciso) => 'Provisional',
                                (float) $promedioMateriaPreciso < 6.0 => 'En riesgo',
                                (float) $promedioMateriaPreciso < 8.0 => 'Regular',
                                default => 'Aprobado',
                            };

                            return [
                                'materia' => $materia['materia'] ?? 'Materia',
                                'clave' => $materia['clave'] ?? '—',
                                'extra' => 0,
                                'receso' => 0,
                                'calificaciones' => $calificacionesPeriodo,
                                'promedio' => PromedioExcel::formatear($promedioMateriaPreciso, 1, '—'),
                                'promedio_numero' => $promedioMateriaPreciso,
                                'promedio_numero_preciso' => $promedioMateriaPreciso,
                                'promedio_truncado' => PromedioExcel::truncar($promedioMateriaPreciso, 1),
                                'estado' => $estadoMateria,
                                'completo' => $completa,
                            ];
                        })
                        ->values()
                        ->all();

                    $totalCeldas = count($filasMaterias) * count($numerosPeriodos);
                    $pendientes = collect($filasMaterias)
                        ->flatMap(fn(array $materia) => $materia['calificaciones'] ?? [])
                        ->where('estado', 'Sin captura')
                        ->count();
                    $especiales = collect($filasMaterias)
                        ->flatMap(fn(array $materia) => $materia['calificaciones'] ?? [])
                        ->where('estado', 'Especial')
                        ->count();
                    $reprobadas = collect($filasMaterias)->where('estado', 'En riesgo')->count();
                    $aprobadas = collect($filasMaterias)
                        ->filter(fn(array $materia) => in_array($materia['estado'], ['Regular', 'Aprobado'], true))
                        ->count();
                    $numeroMateriasPromediar = count($filasMaterias);
                } elseif ($esBachillerato) {
                    $filasMaterias = collect($filaAcademica['materias'] ?? [])
                        ->map(function (array $materia) use ($numerosPeriodos): array {
                            $calificacionesPeriodo = [];

                            foreach ($numerosPeriodos as $numeroPeriodo) {
                                $valor = $materia['evaluaciones'][$numeroPeriodo] ?? null;
                                $especial = $materia['especiales'][$numeroPeriodo] ?? null;

                                if (is_numeric($valor)) {
                                    $numero = (float) $valor;
                                    $estadoPeriodo = match (true) {
                                        $numero < 6.0 => 'En riesgo',
                                        $numero < 8.0 => 'Regular',
                                        default => 'Aprobado',
                                    };

                                    $calificacionesPeriodo[$numeroPeriodo] = [
                                        'calificacion' => CalificacionBachillerato::formatearEntero($numero),
                                        'estado' => $estadoPeriodo,
                                        'porcentaje' => min(100, $numero * 10),
                                    ];

                                    continue;
                                }

                                if (filled($especial)) {
                                    $calificacionesPeriodo[$numeroPeriodo] = [
                                        'calificacion' => mb_strtoupper(trim((string) $especial)),
                                        'estado' => 'Especial',
                                        'porcentaje' => 0,
                                    ];

                                    continue;
                                }

                                $calificacionesPeriodo[$numeroPeriodo] = [
                                    'calificacion' => '—',
                                    'estado' => 'Sin captura',
                                    'porcentaje' => 0,
                                ];
                            }

                            $completa = (bool) ($materia['completo'] ?? false);
                            $promedioMateriaPreciso = $completa
                                ? ($materia['promedio_final_preciso'] ?? null)
                                : null;

                            $estadoMateria = match (true) {
                                ! $completa || ! is_numeric($promedioMateriaPreciso) => 'Provisional',
                                (float) $promedioMateriaPreciso < 6.0 => 'En riesgo',
                                (float) $promedioMateriaPreciso < 8.0 => 'Regular',
                                default => 'Aprobado',
                            };

                            return [
                                'materia' => $materia['materia'] ?? 'Materia',
                                'clave' => $materia['clave'] ?? '—',
                                'extra' => 0,
                                'receso' => 0,
                                'calificaciones' => $calificacionesPeriodo,
                                'promedio' => CalificacionBachillerato::formatearEntero($promedioMateriaPreciso),
                                'promedio_numero' => $promedioMateriaPreciso,
                                'promedio_numero_preciso' => $promedioMateriaPreciso,
                                'promedio_truncado' => CalificacionBachillerato::truncarParcial($promedioMateriaPreciso),
                                'estado' => $estadoMateria,
                                'completo' => $completa,
                            ];
                        })
                        ->values()
                        ->all();

                    $totalCeldas = count($filasMaterias) * count($numerosPeriodos);
                    $pendientes = collect($filasMaterias)
                        ->flatMap(fn(array $materia) => $materia['calificaciones'] ?? [])
                        ->where('estado', 'Sin captura')
                        ->count();
                    $especiales = collect($filasMaterias)
                        ->flatMap(fn(array $materia) => $materia['calificaciones'] ?? [])
                        ->where('estado', 'Especial')
                        ->count();
                    $reprobadas = collect($filasMaterias)->where('estado', 'En riesgo')->count();
                    $aprobadas = collect($filasMaterias)
                        ->filter(fn(array $materia) => in_array($materia['estado'], ['Regular', 'Aprobado'], true))
                        ->count();
                    $numeroMateriasPromediar = count($filasMaterias);
                }

                if (!($filaAcademica['completo'] ?? false)) {
                    $tieneAvance = is_numeric($filaAcademica['promedio_provisional_preciso'] ?? null);
                    $estadoPromedio = $tieneAvance ? 'Incompleto' : 'Sin datos';
                } elseif ($nivel->slug === 'primaria') {
                    $estadoPromedio = empty($filaAcademica['campos_reprobados'] ?? [])
                        ? (((float) $promedioNumero >= 8) ? 'Aprobado' : 'Regular')
                        : 'Reprobado';
                } elseif ($esBachillerato) {
                    $estadoPromedio = match (true) {
                        ! is_numeric($promedioNumero) => 'Sin datos',
                        (float) $promedioNumero < 6.0 => 'Reprobado',
                        (float) $promedioNumero < 8.0 => 'Regular',
                        default => 'Aprobado',
                    };
                } else {
                    $estadoPromedio = empty($filaAcademica['materias_reprobadas'] ?? [])
                        ? (((float) $promedioNumero >= 8) ? 'Aprobado' : 'Regular')
                        : 'Reprobado';
                }

                $lugarAlumno = null;
                $textoLugarAlumno = 'Pendiente';

                if ($esBachillerato) {
                    $lugarAlumno = $filaAcademica['lugar'] ?? null;
                    $textoLugarAlumno = $filaAcademica['texto_lugar'] ?? 'Pendiente';
                } elseif (($filaAcademica['completo'] ?? false) && $promedioNumero !== null) {
                    $promediosUnicosAcademicos = $filasAcademicas
                        ->filter(function (array $fila) use ($nivel): bool {
                            if (
                                !($fila['completo'] ?? false)
                                || !is_numeric($fila['promedio_general_preciso'] ?? null)
                            ) {
                                return false;
                            }

                            if ($nivel->slug === 'primaria') {
                                return ($fila['promocion_sugerida'] ?? false) === true;
                            }

                            if ($nivel->slug === 'secundaria') {
                                return empty($fila['materias_reprobadas'] ?? []);
                            }

                            return true;
                        })
                        ->pluck('promedio_general_preciso')
                        ->sortDesc()
                        ->map(fn($valor) => PromedioExcel::claveComparacion($valor))
                        ->filter()
                        ->unique()
                        ->values();

                    $claveAlumno = PromedioExcel::claveComparacion($promedioNumero);
                    $indice = $claveAlumno !== null
                        ? $promediosUnicosAcademicos->search($claveAlumno)
                        : false;

                    if ($indice !== false) {
                        $lugarAlumno = ((int) $indice) + 1;
                        $textoLugarAlumno = $lugarAlumno . '° lugar';
                    }
                }
            }
        }

        if ($esBachillerato && ! is_array($filaAcademica)) {
            abort(
                404,
                'No se encontró información académica completa para el alumno, grupo y semestre seleccionados.'
            );
        }

        if ($tipo === 'reconocimiento') {
            abort_unless(
                in_array($nivel->slug, ['primaria', 'secundaria'], true) || $esBachillerato,
                403,
                'El reconocimiento no está habilitado para este nivel.'
            );

            abort_unless(
                is_array($filaAcademica)
                    && ($filaAcademica['completo'] ?? false) === true
                    && $lugarAlumno !== null,
                422,
                $esBachillerato
                    ? 'El alumno aún no tiene un reconocimiento semestral habilitado.'
                    : 'El alumno aún no tiene un reconocimiento anual habilitado.'
            );

            if ($nivel->slug === 'primaria') {
                abort_unless(
                    ($filaAcademica['promocion_sugerida'] ?? false) === true,
                    403,
                    'El alumno no cumple las condiciones académicas para el reconocimiento anual.'
                );
            }

            if ($nivel->slug === 'secundaria') {
                abort_unless(
                    empty($filaAcademica['materias_reprobadas'] ?? []),
                    403,
                    'El alumno tiene materias no acreditadas y no puede generar reconocimiento anual.'
                );
            }

            if ($esBachillerato) {
                abort_unless(
                    ($filaAcademica['reconocimiento_disponible'] ?? false) === true
                        && (int) ($filaAcademica['lugar'] ?? 0) >= 1
                        && (int) ($filaAcademica['lugar'] ?? 0) <= 3,
                    403,
                    'El reconocimiento semestral solo está disponible para los tres primeros lugares con promedio aprobatorio.'
                );
            }
        }

        if ($tipo === 'diploma') {
            $this->validarDiplomaAnual(
                nivel: $nivel,
                grado: $grado,
                semestre: $semestre,
                filaAcademica: $filaAcademica,
                promedioNumero: $promedioNumero,
                promediosPeriodosPrecisos: $promediosPeriodosPrecisos,
                numerosPeriodos: $numerosPeriodos,
            );
        }

        $filasResumenAcademico = $esBachillerato
            ? collect($filasMaterias)
            ->filter(fn(array $fila) => (int) ($fila['extra'] ?? 0) === 0)
            ->values()
            : collect($filasMaterias);

        $totalMaterias = $filasResumenAcademico->count();

        if ($esBachillerato) {
            $totalCeldas = $totalMaterias * count($numerosPeriodos);
            $pendientes = $filasResumenAcademico
                ->flatMap(fn(array $materia) => $materia['calificaciones'] ?? [])
                ->where('estado', 'Sin captura')
                ->count();
            $especiales = $filasResumenAcademico
                ->flatMap(fn(array $materia) => $materia['calificaciones'] ?? [])
                ->where('estado', 'Especial')
                ->count();
            $reprobadas = $filasResumenAcademico->where('estado', 'En riesgo')->count();
            $aprobadas = $filasResumenAcademico
                ->filter(fn(array $materia) => in_array($materia['estado'], ['Regular', 'Aprobado'], true))
                ->count();
        }

        $porcentajeCaptura = $totalCeldas > 0
            ? round((($totalCeldas - $pendientes) / $totalCeldas) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Docente y director
        |--------------------------------------------------------------------------
        */

        $mostrarSoloDirector = ($tipo === 'diploma' && ($esSecundaria || $esBachillerato))
            || ($esBachillerato && $tipo === 'reconocimiento');

        /*
         * En primaria, tanto el reconocimiento como el diploma deben mostrar
         * al docente titular. Primero se consulta la asignación explícita del
         * personal al grado y grupo; después se usan fuentes de respaldo.
         */
        $mostrarDocenteTitular = $nivel->slug === 'primaria'
            && in_array($tipo, ['reconocimiento', 'diploma'], true);

        $docente = null;

        if ($mostrarDocenteTitular) {
            $asignacionTitular = PersonaNivel::query()
                ->with([
                    'persona:id,titulo,nombre,apellido_paterno,apellido_materno,genero,status',
                ])
                ->where('nivel_id', $nivel->id)
                ->whereHas('persona', function ($query) {
                    $query->where(function ($query) {
                        $query->whereNull('status')
                            ->orWhere('status', true);
                    });
                })
                ->whereHas('detalles', function ($query) use ($grado, $grupo) {
                    $query
                        ->where('grado_id', $grado->id)
                        ->where('grupo_id', $grupo->id)
                        ->whereHas('personaRole.rolePersona', function ($query) {
                            $query->whereIn('slug', [
                                'docente_titular',
                                'maestro_frente_a_grupo',
                                'docente_grupo',
                                'docente',
                                'director_con_grupo',
                            ]);
                        });
                })
                ->orderBy('orden')
                ->orderBy('id')
                ->first();

            $docente = $asignacionTitular?->persona;

            /*
             * Respaldo para instalaciones que utilizan la tabla docente_grupo.
             */
            if (!$docente && Schema::hasTable('docente_grupo')) {
                $docente = Persona::query()
                    ->whereHas('docenteGrupos', function ($query) use ($grupo, $cicloEscolar) {
                        $query
                            ->where('grupo_id', $grupo->id)
                            ->where('ciclo_escolar_id', $cicloEscolar->id)
                            ->where('es_tutor', true);
                    })
                    ->where(function ($query) {
                        $query->whereNull('status')
                            ->orWhere('status', true);
                    })
                    ->first();
            }

            /*
             * Último respaldo: profesor con más materias asignadas al grupo.
             */
            if (!$docente) {
                $profesorId = $materias
                    ->pluck('profesor_id')
                    ->filter()
                    ->countBy()
                    ->sortDesc()
                    ->keys()
                    ->first();

                if ($profesorId) {
                    $docente = Persona::query()->find($profesorId);
                }
            }
        }

        $director = Nivel::query()
            ->where('id', $nivel->id)
            ->with(['director', 'supervisor'])
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Logos
        |--------------------------------------------------------------------------
        */

        $logoIzquierdo = $this->imagenBase64Publica('imagenes/logo-letra.png');
        $logoDerecho = $this->imagenBase64Publica('imagenes/seg.png');
        $watermark = $this->imagenBase64Publica('storage/logos/' . $nivel->logo);



        $titulo = match ($tipo) {
            'semestral' => 'BOLETA SEMESTRAL',
            'boleta' => 'BOLETA ',
            'reconocimiento' => 'RECONOCIMIENTO',
            'diploma' => 'DIPLOMA',
            default => 'BOLETA DE PROMEDIO',
        };

        $alumno = [
            'inscripcion_id' => $inscripcion->id,
            'matricula' => $inscripcion->matricula,
            'nombre_completo' => $nombreAlumno,
            'grado' => $grado->nombre ?? '—',
            'grupo' => $this->nombreGrupo($grupo),
            'semestre' => $semestre?->numero,
            'periodos' => $promediosPeriodos,
            'suma_periodos' => collect($promediosPeriodosPrecisos)
                ->filter(fn($valor) => $valor !== null)
                ->sum(),
            'promedio_final' => $promedioNumero,
            'periodos_capturados' => collect($promediosPeriodosPrecisos)
                ->filter(fn($valor) => $valor !== null)
                ->count(),
            'periodos_faltantes' => count($numerosPeriodos) - collect($promediosPeriodosPrecisos)
                ->filter(fn($valor) => $valor !== null)
                ->count(),

            'materias_capturadas' => $totalMaterias - $pendientes,
            'lugar' => $lugarAlumno,
            'texto_lugar' => $textoLugarAlumno,
            'estatus' => match ($estadoPromedio) {
                'Aprobado' => ($promedioNumero >= 9 ? 'Destacado' : 'Aprobado'),
                'Regular' => 'Aprobado',
                'Reprobado' => 'En riesgo',
                default => 'Incompleto',
            },
        ];

        $encabezadosPeriodos = collect($periodosResumen)
            ->mapWithKeys(fn($periodo, $numero) => [
                $numero => $periodo['nombre'],
            ])
            ->toArray();

        $data = [
            'titulo' => $titulo,
            'tipo' => $tipo,
            'encabezadosPeriodos' => $encabezadosPeriodos,

            'watermark' => $watermark,

            'escuela' => $escuela,
            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'generacion' => $generacion,

            'esBachillerato' => $esBachillerato,
            'esSecundaria' => $esSecundaria,
            'mostrarSoloDirector' => $mostrarSoloDirector,
            'mostrarDocenteTitular' => $mostrarDocenteTitular,

            'director' => $director,
            'docente' => $docente,

            'cicloEscolar' => $cicloEscolar,
            'cicloEscolarTexto' => $cicloEscolarTexto,

            'inscripcion' => $inscripcion,
            'nombreAlumno' => $nombreAlumno,

            'alumno' => $alumno,
            'reporteAcademico' => $reporteAcademico,
            'filaAcademica' => $filaAcademica,

            'periodosResumen' => $periodosResumen,
            'promediosPeriodos' => $promediosPeriodos,

            'filasMaterias' => $filasMaterias,
            'filasMateriasExtras' => $filasMateriasExtras,
            'promedio' => $promedio,
            'promedioNumero' => $promedioNumero,
            'lugarAlumno' => $lugarAlumno,
            'textoLugarAlumno' => $textoLugarAlumno,
            'porcentajePromedio' => $porcentajePromedio,
            'estadoPromedio' => $estadoPromedio,

            'totalMaterias' => $totalMaterias,
            'pendientes' => $pendientes,
            'especiales' => $especiales,
            'aprobadas' => $aprobadas,
            'reprobadas' => $reprobadas,
            'porcentajeCaptura' => $porcentajeCaptura,
            'numeroMateriasPromediar' => $numeroMateriasPromediar,

            'fecha_impresion' => now(),
            'fechaPdf' => $this->fechaPdf($request->query('fecha')),
            'logo_izquierdo' => $logoIzquierdo,
            'logo_derecho' => $logoDerecho,
        ];

        $nombreArchivo = match ($tipo) {
            'semestral' => 'BOLETA_SEMESTRAL_' . str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) . '.pdf',
            'boleta' => 'BOLETA_ANUAL_' . str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) . '.pdf',
            'reconocimiento' => 'RECONOCIMIENTO_' . str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) . '.pdf',
            'diploma' => 'DIPLOMA_' . str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) . '.pdf',
            default => 'BOLETA_PROMEDIO_' . str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) . '.pdf',
        };

        $vista = match ($tipo) {
            'reconocimiento' => 'pdf.reconocimiento_promedio_pdf',
            'diploma' => 'pdf.diploma_anual_pdf',
            default => 'pdf.boleta_promedio_pdf',
        };

        return Pdf::loadView($vista, $data)
            ->setPaper('letter', in_array($tipo, ['reconocimiento', 'diploma'], true) ? 'landscape' : 'portrait')
            ->stream($nombreArchivo);
    }

    private function fechaPdf(?string $fecha): string
    {
        try {
            $fecha = $fecha ?: now()->format('Y-m-d');

            return Carbon::parse($fecha)
                ->locale('es')
                ->translatedFormat('d \\d\\e F \\d\\e Y');
        } catch (\Throwable $exception) {
            return now()
                ->locale('es')
                ->translatedFormat('d \\d\\e F \\d\\e Y');
        }
    }

    private function estatusPromedioPdf(?float $promedio, int $capturados, int $esperados): string
    {
        if ($capturados === 0) {
            return 'Sin captura';
        }

        if ($capturados < $esperados) {
            return 'Incompleto';
        }

        if (($promedio ?? 0) < 6) {
            return 'En riesgo';
        }

        if (($promedio ?? 0) >= 9) {
            return 'Destacado';
        }

        return 'Aprobado';
    }
    public function credencial_profesor_pdf(Request $request)
    {
        $request->validate([
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],
            'modo_descarga' => ['required', 'string', 'in:nivel,todos,individual,seleccionados'],
            'persona_individual_id' => ['nullable', 'integer', 'exists:personas,id'],
            'personas' => ['nullable', 'string'],
            'vigencia' => ['nullable', 'string', 'max:120'],
            'cargo' => ['nullable', 'string', 'max:80'],
        ]);

        $nivel = Nivel::query()
            ->with('director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo,status')
            ->select('id', 'nombre', 'slug', 'cct', 'logo', 'color', 'director_id')
            ->findOrFail((int) $request->query('nivel_id'));

        $modoDescarga = $request->query('modo_descarga', 'seleccionados');

        $personas = $this->obtenerPersonasCredencialProfesor($request, $modoDescarga, $nivel->id);

        if ($personas->isEmpty()) {
            abort(404, 'No se encontró personal asignado al nivel para generar credenciales.');
        }

        $pdf = Pdf::loadView('pdf.credenciales_profesores_pdf', [
            'nivel' => $nivel,
            'personas' => $personas,
            'vigencia' => $request->query(
                'vigencia',
                'Ciclo escolar ' . now()->year . ' - ' . now()->addYear()->year
            ),
            'cargo' => $request->query('cargo', 'PERSONAL'),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('credenciales-personal-' . $nivel->slug . '.pdf');
    }

    private function obtenerPersonasCredencialProfesor(Request $request, string $modoDescarga, int $nivelId)
    {
        $consulta = Persona::query()
            ->select('personas.*')
            ->with([
                'personaRoles.rolePersona:id,nombre,slug,status',
                'personaNiveles' => function ($consulta) use ($nivelId) {
                    $consulta->where('nivel_id', $nivelId)
                        ->with([
                            'nivel:id,nombre,slug,cct,logo,color,director_id',
                            'detalles.personaRole.rolePersona:id,nombre,slug,status',
                        ]);
                },
            ])
            ->where('personas.status', 1)

            /*
             * Se toma todo el personal asignado al nivel.
             * No se filtra por docente/profesor/maestro porque plantilla cuenta todo el personal del nivel.
             */
            ->whereHas('personaNiveles', function ($consulta) use ($nivelId) {
                $consulta->where('nivel_id', $nivelId);
            });

        if ($modoDescarga === 'individual') {
            $personaIndividualId = (int) $request->query('persona_individual_id');

            $consulta->where('personas.id', $personaIndividualId);
        }

        if ($modoDescarga === 'seleccionados') {
            $personasSeleccionadas = collect(explode(',', (string) $request->query('personas')))
                ->map(fn($id) => (int) trim($id))
                ->filter()
                ->unique()
                ->values();

            if ($personasSeleccionadas->isEmpty()) {
                return collect();
            }

            $consulta->whereIn('personas.id', $personasSeleccionadas->all());
        }

        return $consulta
            ->orderBy('personas.apellido_paterno')
            ->orderBy('personas.apellido_materno')
            ->orderBy('personas.nombre')
            ->get();
    }

    private function obtenerPersonas(Request $request, string $modoDescarga, int $nivelId)
    {
        $query = Persona::query()
            ->with([
                'personaRoles.rolePersona:id,nombre,slug,status',
                'personaNiveles' => function ($consulta) use ($nivelId) {
                    $consulta->where('nivel_id', $nivelId)
                        ->orderBy('orden');
                },
                'personaNiveles.nivel:id,nombre,slug,cct,logo,color,director_id',
            ])
            ->where('status', 1)
            ->whereHas('personaNiveles', function ($consulta) use ($nivelId) {
                $consulta->where('nivel_id', $nivelId);
            })
            ->whereHas('personaRoles.rolePersona', function ($consulta) {
                $consulta->where(function ($rol) {
                    $rol->where('slug', 'like', '%docente%')
                        ->orWhere('slug', 'like', '%maestro%')
                        ->orWhere('slug', 'like', '%maestroa%')
                        ->orWhere('slug', 'like', '%profesor%')
                        ->orWhere('slug', 'like', '%tutor%')
                        ->orWhere('slug', 'director_con_grupo')
                        ->orWhere('nombre', 'like', '%Docente%')
                        ->orWhere('nombre', 'like', '%Maestro%')
                        ->orWhere('nombre', 'like', '%Maestra%')
                        ->orWhere('nombre', 'like', '%Profesor%')
                        ->orWhere('nombre', 'like', '%Tutora%')
                        ->orWhere('nombre', 'like', '%Tutor%');
                });
            });

        if ($modoDescarga === 'individual') {
            return $query
                ->where('id', (int) $request->query('persona_id'))
                ->get();
        }

        if ($modoDescarga === 'seleccionados') {
            $ids = collect(explode(',', (string) $request->query('personas')))
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return collect();
            }

            $personas = $query
                ->whereIn('id', $ids->all())
                ->get();

            return $personas
                ->sortBy(fn($persona) => $ids->search((int) $persona->id))
                ->values();
        }

        $busqueda = trim((string) $request->query('buscar'));

        if ($busqueda !== '') {
            $query->where(function ($consulta) use ($busqueda) {
                $consulta
                    ->where('nombre', 'like', '%' . $busqueda . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $busqueda . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $busqueda . '%')
                    ->orWhere('curp', 'like', '%' . $busqueda . '%')
                    ->orWhere('rfc', 'like', '%' . $busqueda . '%')
                    ->orWhere('correo', 'like', '%' . $busqueda . '%')
                    ->orWhereRaw(
                        "CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?",
                        ['%' . $busqueda . '%']
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                        ['%' . $busqueda . '%']
                    );
            });
        }

        return $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    // Generar oficios de reanudaciones de labores
    public function reanudaciones(Request $request)
    {
        $nivel_id = $request->input("nivel_id");
        $tipo_reanudacion = $request->input("tipo_reanudacion");
        $fecha_director = $request->input("fecha_director");
        $fecha_docente = $request->input("fecha_docente");
        $ciclo_escolar = $request->input("ciclo_escolar");
        $copias = $request->input("copias");

        if (empty($nivel_id)) {
            abort(422, 'El parámetro nivel_id es obligatorio.');
        }

        $nivel = Nivel::where("id", $nivel_id)->with(['director', 'supervisor'])->first();
        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $delegado = Director::where("identificador", "delegado-servicios-educativos-tierra-caliente")->first();
        $directorAdministracion = Director::where("identificador", "director-general-administracion")->first();
        $directorMagisterio = Director::where("identificador", "director-magisterio-estatal")->first();

        $cicloEscolar = cicloEscolar::find($ciclo_escolar);

        // ✅ IMPORTANTE: ordenar por persona_nivel.orden
        $asignacionesNivel = PersonaNivel::query()
            ->where("nivel_id", $nivel_id)
            ->with([
                'persona.personaRoles.rolePersona',
                // ✅ si en el PDF usas $personal->detalles, conviene ordenarlos
                'detalles' => function ($q) {
                    $q->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('orden')
                        ->orderBy('id');
                },
                'detalles.PersonaRole.rolePersona',
                'detalles.grado',
                'detalles.grupo.asignacionGrupo',
            ])
            ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('orden', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $data = [
            "asignacionesNivel" => $asignacionesNivel,
            "fecha_director" => $fecha_director,
            "fecha_docente" => $fecha_docente,
            'nivel' => $nivel,
            'escuela' => \App\Models\Escuela::first(),
            'delegado' => $delegado,
            'cicloEscolar' => $cicloEscolar,
            'copias' => $copias,
            'directorAdministracion' => $directorAdministracion,
            'directorMagisterio' => $directorMagisterio,
        ];

        if ($tipo_reanudacion == "1") {
            $pdf = Pdf::loadView('pdf.reanudaciones_receso', $data)
                ->setPaper('letter', 'portrait')
                ->setOption([
                    'fontDir' => public_path('/fonts'),
                    'fontCache' => public_path('/fonts'),
                ]);

            $nombreArchivo = "OFICIOS_DE_REANUDACIONES_DE_RECESO_DE_CLASES_" .
                mb_strtoupper($nivel->nombre) . "_" .
                $cicloEscolar->inicio_anio . "-" . $cicloEscolar->fin_anio . ".pdf";

            return $pdf->stream($nombreArchivo);
        }

        if ($tipo_reanudacion == "2") {
            $pdf = Pdf::loadView('pdf.reanudaciones_invierno', $data)
                ->setPaper('letter', 'portrait')
                ->setOption([
                    'fontDir' => public_path('/fonts'),
                    'fontCache' => public_path('/fonts'),
                ]);

            $nombreArchivo = "OFICIOS_DE_REANUDACIONES_DE_INVIERNO_" .
                mb_strtoupper($nivel->nombre) . "_" .
                $cicloEscolar->inicio_anio . "-" . $cicloEscolar->fin_anio . ".pdf";

            return $pdf->stream($nombreArchivo);
        }

        if ($tipo_reanudacion == "3") {
            $pdf = Pdf::loadView('pdf.reanudaciones_primavera', $data)
                ->setPaper('letter', 'portrait')
                ->setOption([
                    'fontDir' => public_path('/fonts'),
                    'fontCache' => public_path('/fonts'),
                ]);

            $nombreArchivo = "OFICIOS_DE_REANUDACIONES_DE_PRIMAVERA_" .
                mb_strtoupper($nivel->nombre) . "_" .
                $cicloEscolar->inicio_anio . "-" . $cicloEscolar->fin_anio . ".pdf";

            return $pdf->stream($nombreArchivo);
        }

        abort(422, 'Tipo de reanudación inválido.');
    }

    // HORARIO PDF
    public function horario_pdf(Request $request)
    {
        $slugNivel = trim((string) $request->input('slug_nivel'));
        $generacionId = $request->integer('generacion_id');
        $gradoId = $request->integer('grado_id');
        $grupoId = $request->integer('grupo_id');
        $semestreId = $request->integer('semestre_id');
        $cicloEscolarId = $request->integer('ciclo_escolar_id');

        if (
            $slugNivel === '' ||
            !$generacionId ||
            !$gradoId ||
            !$grupoId
        ) {
            abort(
                422,
                'Los parámetros slug_nivel, generacion_id, grado_id y grupo_id son obligatorios.'
            );
        }

        $escuela = \App\Models\Escuela::query()->first();

        if (!$escuela) {
            abort(404, 'No se encontró la escuela.');
        }

        $nivel = Nivel::query()
            ->where('slug', $slugNivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $esBachillerato = $this->esBachillerato($nivel);
        $esSecundaria = $this->esSecundaria($nivel);
        $esPreescolar = (int) $nivel->id === 1 || $nivel->slug === 'preescolar';
        $esPrimaria = (int) $nivel->id === 2 || $nivel->slug === 'primaria';

        $generacion = Generacion::query()
            ->whereKey($generacionId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$generacion) {
            abort(404, 'Generación no encontrada o no pertenece al nivel seleccionado.');
        }

        $grado = Grado::query()
            ->whereKey($gradoId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$grado) {
            abort(404, 'Grado no encontrado o no pertenece al nivel seleccionado.');
        }

        $grupoQuery = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grado:id,nombre,orden',
                'semestre:id,numero',
            ])
            ->whereKey($grupoId)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id);

        if ($esBachillerato) {
            if (!$semestreId) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $grupoQuery->where('semestre_id', $semestreId);
        } elseif (Schema::hasColumn('grupos', 'semestre_id')) {
            $grupoQuery->whereNull('semestre_id');
        }

        $grupo = $grupoQuery->first();

        if (!$grupo) {
            abort(404, 'Grupo no encontrado para el contexto seleccionado.');
        }

        $semestre = null;

        if ($esBachillerato) {
            $semestre = Semestre::query()
                ->whereKey($semestreId)
                ->where('grado_id', $grado->id)
                ->first();

            if (!$semestre) {
                abort(404, 'Semestre no encontrado o no pertenece al grado seleccionado.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Ciclo escolar
        |--------------------------------------------------------------------------
        */

        $cicloEscolar = $cicloEscolarId
            ? cicloEscolar::query()->find($cicloEscolarId)
            : null;

        if (!$cicloEscolar) {
            $cicloEscolar = cicloEscolar::query()
                ->orderByDesc('id')
                ->first();
        }

        if (!$cicloEscolar) {
            abort(404, 'No se encontró el ciclo escolar.');
        }

        /*
        |--------------------------------------------------------------------------
        | Horarios del grupo
        |--------------------------------------------------------------------------
        |
        | Se recuperan materias normales y proyecciones de talleres conjuntos.
        | Cada taller tiene una fila en horarios por cada grupo participante.
        */

        $horariosQuery = Horario::query()
            ->with([
                'dia:id,nivel_id,dia,orden',
                'hora:id,nivel_id,hora_inicio,hora_fin,orden',

                'asignacionMateria.materia',
                'asignacionMateria.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',

                'tallerSesion:id,taller_id,profesor_id,ciclo_escolar_id,dia_id,hora_id,ubicacion,conflicto_forzado,motivo_conflicto',
                'tallerSesion.taller:id,nivel_id,nombre,clave',
                'tallerSesion.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tallerSesion.grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'tallerSesion.grupos.grado:id,nombre,orden',
                'tallerSesion.grupos.asignacionGrupo:id,nombre',
            ]);

        if (Schema::hasColumn('horarios', 'nivel_id')) {
            $horariosQuery->where('nivel_id', $nivel->id);
        }

        if (Schema::hasColumn('horarios', 'generacion_id')) {
            $horariosQuery->where('generacion_id', $generacion->id);
        }

        if (Schema::hasColumn('horarios', 'grado_id')) {
            $horariosQuery->where('grado_id', $grado->id);
        }

        if (Schema::hasColumn('horarios', 'grupo_id')) {
            $horariosQuery->where('grupo_id', $grupo->id);
        }

        if (Schema::hasColumn('horarios', 'ciclo_escolar_id')) {
            $horariosQuery->where('ciclo_escolar_id', $cicloEscolar->id);
        }

        if (Schema::hasColumn('horarios', 'semestre_id')) {
            if ($esBachillerato && $semestre) {
                $horariosQuery->where('semestre_id', $semestre->id);
            } else {
                $horariosQuery->whereNull('semestre_id');
            }
        }

        /*
         * Se excluyen filas incompletas. Para los talleres se comprueba que la
         * sesión todavía incluya al grupo mediante la tabla pivote.
         */
        $horariosQuery->where(function ($actividadQuery) use ($grupo) {
            $actividadQuery
                ->where(function ($materiaQuery) {
                    $materiaQuery
                        ->whereNull('taller_sesion_id')
                        ->whereNotNull('asignacion_materia_id')
                        ->whereHas('asignacionMateria');
                })
                ->orWhere(function ($tallerQuery) use ($grupo) {
                    $tallerQuery
                        ->whereNotNull('taller_sesion_id')
                        ->whereHas('tallerSesion.grupos', function ($gruposQuery) use ($grupo) {
                            $gruposQuery->where('grupos.id', $grupo->id);
                        });
                });
        });

        $horarios = $horariosQuery
            ->orderBy('hora_id')
            ->orderBy('dia_id')
            ->orderBy('id')
            ->get();

        if ($horarios->isEmpty()) {
            abort(404, 'No se encontraron registros de horario para los filtros seleccionados.');
        }

        /*
        |--------------------------------------------------------------------------
        | Días y horas del nivel
        |--------------------------------------------------------------------------
        |
        | Se toma la configuración completa del nivel para que el PDF conserve
        | sus cinco días y todos los bloques, incluso cuando alguna celda esté
        | vacía. Las relaciones usadas por los registros se agregan como respaldo.
        */

        $normalizarDia = static function ($dia): string {
            return Str::lower(
                Str::ascii(
                    trim((string) ($dia->dia ?? $dia->nombre ?? ''))
                )
            );
        };

        $dias = Dia::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->concat($horarios->pluck('dia')->filter())
            ->unique('id')
            ->unique($normalizarDia)
            ->sortBy(function ($dia) use ($normalizarDia) {
                $nombre = $normalizarDia($dia);

                $ordenNombre = match (true) {
                    str_contains($nombre, 'lunes') => 1,
                    str_contains($nombre, 'martes') => 2,
                    str_contains($nombre, 'miercoles') => 3,
                    str_contains($nombre, 'jueves') => 4,
                    str_contains($nombre, 'viernes') => 5,
                    default => 99,
                };

                return sprintf(
                    '%02d-%06d-%06d',
                    $ordenNombre,
                    (int) ($dia->orden ?? 999999),
                    (int) ($dia->id ?? 999999)
                );
            })
            ->values();

        $horas = Hora::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->orderBy('id')
            ->get()
            ->concat($horarios->pluck('hora')->filter())
            ->unique('id')
            ->unique(function ($hora) {
                return trim((string) $hora->hora_inicio)
                    . '|'
                    . trim((string) $hora->hora_fin);
            })
            ->sortBy(function ($hora) {
                return sprintf(
                    '%s-%s-%06d',
                    (string) ($hora->hora_inicio ?? '99:99:99'),
                    (string) ($hora->hora_fin ?? '99:99:99'),
                    (int) ($hora->id ?? 999999)
                );
            })
            ->values();

        if ($dias->isEmpty()) {
            abort(404, 'No se encontraron días configurados para el nivel.');
        }

        if ($horas->isEmpty()) {
            abort(404, 'No se encontraron horas configuradas para el nivel.');
        }

        /*
        |--------------------------------------------------------------------------
        | Mapas de celdas
        |--------------------------------------------------------------------------
        |
        | Una materia normal ocupa una celda. Los talleres se mantienen como
        | colección porque una autorización administrativa puede permitir más
        | de uno en el mismo bloque.
        */

        $horarioPorCelda = $horarios
            ->whereNull('taller_sesion_id')
            ->groupBy(fn($horario) => $horario->hora_id . '-' . $horario->dia_id)
            ->map(fn($registros) => $registros->first());

        $talleresPorCelda = $horarios
            ->whereNotNull('taller_sesion_id')
            ->groupBy(fn($horario) => $horario->hora_id . '-' . $horario->dia_id)
            ->map(function ($registros) {
                return $registros
                    ->unique('taller_sesion_id')
                    ->sortBy(function ($horario) {
                        return Str::lower(
                            Str::ascii(
                                trim((string) ($horario->tallerSesion?->taller?->nombre ?? ''))
                            )
                        );
                    })
                    ->values();
            });

        /*
        |--------------------------------------------------------------------------
        | Profesor titular de preescolar y primaria
        |--------------------------------------------------------------------------
        */

        $profesorTitular = null;
        $profesorTitularId = null;

        if ($esPreescolar || $esPrimaria) {
            $personalAsignado = PersonaNivel::query()
                ->with([
                    'persona:id,titulo,nombre,apellido_paterno,apellido_materno,genero',
                    'detalles' => function ($query) {
                        $query->with([
                            'grado:id,nombre',
                            'grupo' => function ($query) {
                                $query
                                    ->select('id', 'asignacion_grupo_id')
                                    ->with('asignacionGrupo:id,nombre');
                            },
                        ]);
                    },
                ])
                ->where('nivel_id', $nivel->id)
                ->whereHas('detalles', function ($query) use ($grado, $grupo) {
                    $query
                        ->where('grado_id', $grado->id)
                        ->where('grupo_id', $grupo->id);
                })
                ->first();

            $profesorTitularId = $personalAsignado?->persona?->id
                ? (int) $personalAsignado->persona->id
                : null;

            $profesorTitular = $this->nombrePersona($personalAsignado?->persona);

            if ($profesorTitular === '') {
                $profesorTitular = null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Tabla de docentes
        |--------------------------------------------------------------------------
        |
        | Preescolar conserva la exclusión del profesor titular.
        | Primaria conserva solo materias extra calificables.
        | Secundaria y bachillerato muestran materias calificables.
        | Secundaria agrega además los talleres conjuntos no calificables.
        */

        $docentesPreescolar = collect();
        $docentesHorario = collect();

        if ($esPreescolar) {
            $docentesPreescolar = $horarios
                ->whereNull('taller_sesion_id')
                ->map(function ($horario) use ($profesorTitularId) {
                    $asignacion = $horario->asignacionMateria;
                    $materia = $asignacion?->materia;

                    if (!$asignacion || !$materia) {
                        return null;
                    }

                    if ((int) ($materia->receso ?? 0) === 1) {
                        return null;
                    }

                    $profesor = $asignacion->profesor;
                    $profesorId = $profesor?->id ? (int) $profesor->id : null;

                    if (
                        $profesorTitularId !== null &&
                        $profesorId !== null &&
                        $profesorId === $profesorTitularId
                    ) {
                        return null;
                    }

                    return [
                        'profesor_id' => $profesorId,
                        'docente' => $profesor
                            ? ($this->nombrePersona($profesor) ?: 'Sin docente')
                            : 'Sin docente',
                        'materia' => trim((string) ($materia->materia ?? '')) ?: 'Sin materia',
                        'orden' => (int) ($materia->orden ?? 999999),
                        'sin_docente' => $profesorId === null,
                    ];
                })
                ->filter()
                ->groupBy(fn(array $item) => $item['profesor_id'] !== null
                    ? 'profesor-' . $item['profesor_id']
                    : 'sin-docente')
                ->map(function ($items) {
                    $primero = $items->first();

                    $materias = $items
                        ->sortBy([
                            ['orden', 'asc'],
                            ['materia', 'asc'],
                        ])
                        ->pluck('materia')
                        ->filter()
                        ->unique(fn($materia) => mb_strtoupper(trim((string) $materia), 'UTF-8'))
                        ->values();

                    return [
                        'profesor_id' => $primero['profesor_id'],
                        'docente' => $primero['docente'],
                        'materias' => $materias->all(),
                        'materias_texto' => $materias->implode(', '),
                        'sin_docente' => (bool) $primero['sin_docente'],
                    ];
                })
                ->sortBy(function (array $item) {
                    return sprintf(
                        '%d-%s',
                        $item['sin_docente'] ? 1 : 0,
                        mb_strtoupper(trim((string) $item['docente']), 'UTF-8')
                    );
                })
                ->values();
        } else {
            $slugsExcluidosPrimaria = [
                'calculo-mental',
                'caligrafia',
                'lectura',
            ];

            foreach ($horarios->whereNull('taller_sesion_id') as $horario) {
                $asignacion = $horario->asignacionMateria;
                $materia = $asignacion?->materia;

                if (!$asignacion || !$materia) {
                    continue;
                }

                $calificable = (int) ($materia->calificable ?? 0);
                $extra = (int) ($materia->extra ?? 0);
                $receso = (int) ($materia->receso ?? 0);
                $slugMateria = Str::lower(trim((string) ($materia->slug ?? '')));

                if ($receso === 1) {
                    continue;
                }

                if ($esSecundaria || $esBachillerato) {
                    if ($calificable !== 1) {
                        continue;
                    }
                } else {
                    if ($extra !== 1 || $calificable !== 1) {
                        continue;
                    }

                    if (in_array($slugMateria, $slugsExcluidosPrimaria, true)) {
                        continue;
                    }
                }

                $profesor = $asignacion->profesor;
                $profesorId = $profesor?->id ? (int) $profesor->id : null;

                $docentesHorario->push([
                    'profesor_id' => $profesorId,
                    'docente' => $profesor
                        ? ($this->nombrePersona($profesor) ?: 'Sin docente')
                        : 'Sin docente',
                    'materia' => trim((string) ($materia->materia ?? '')) ?: 'Sin materia',
                    'orden' => (int) ($materia->orden ?? 999999),
                    'sin_docente' => $profesorId === null,
                ]);
            }

            if ($esSecundaria) {
                foreach (
                    $horarios
                        ->whereNotNull('taller_sesion_id')
                        ->unique('taller_sesion_id')
                    as $horarioTaller
                ) {
                    $sesion = $horarioTaller->tallerSesion;

                    if (!$sesion) {
                        continue;
                    }

                    $profesor = $sesion->profesor;
                    $profesorId = $profesor?->id ? (int) $profesor->id : null;
                    $nombreTaller = trim((string) ($sesion->taller?->nombre ?? '')) ?: 'Taller';

                    $docentesHorario->push([
                        'profesor_id' => $profesorId,
                        'docente' => $profesor
                            ? ($this->nombrePersona($profesor) ?: 'Sin docente')
                            : 'Sin docente',
                        'materia' => $nombreTaller,
                        'orden' => 999998,
                        'sin_docente' => $profesorId === null,
                    ]);
                }
            }

            $docentesHorario = $docentesHorario
                ->groupBy(fn(array $item) => $item['profesor_id'] !== null
                    ? 'profesor-' . $item['profesor_id']
                    : 'sin-docente')
                ->map(function ($items) {
                    $primero = $items->first();

                    $materias = $items
                        ->sortBy([
                            ['orden', 'asc'],
                            ['materia', 'asc'],
                        ])
                        ->pluck('materia')
                        ->filter()
                        ->unique(fn($materia) => mb_strtoupper(trim((string) $materia), 'UTF-8'))
                        ->values();

                    return [
                        'profesor_id' => $primero['profesor_id'],
                        'docente' => $primero['docente'],
                        'materias' => $materias->all(),
                        'materias_texto' => $materias->implode(', '),
                        'sin_docente' => (bool) $primero['sin_docente'],
                    ];
                })
                ->sortBy(function (array $item) {
                    return sprintf(
                        '%d-%s',
                        $item['sin_docente'] ? 1 : 0,
                        mb_strtoupper(trim((string) $item['docente']), 'UTF-8')
                    );
                })
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Imágenes y nombre del archivo
        |--------------------------------------------------------------------------
        */

        $logoIzquierdo = $this->imagenBase64Publica('imagenes/logo-letra.png');

        $logoDerecho = $this->imagenBase64Publica(
            !empty($nivel->logo)
                ? 'storage/logos/' . $nivel->logo
                : 'imagenes/logo-letra.png'
        );

        $imagenesPorNivel = [
            'preescolar' => 'imagenes/personajes_preescolar.png',
            'primaria' => 'imagenes/personajes_primaria.png',
            'secundaria' => 'imagenes/personajes_secundaria.png',
            'bachillerato' => 'imagenes/personajes_bachillerato.png',
        ];

        $imagenNivel = $this->imagenBase64Publica(
            $imagenesPorNivel[$nivel->slug] ?? null
        );

        $nombreArchivo = 'HORARIO_' .
            mb_strtoupper((string) $nivel->slug, 'UTF-8') .
            '_GRADO_' . Str::slug((string) ($grado->nombre ?? 'grado'), '_') .
            '_GRUPO_' . Str::slug($this->nombreGrupo($grupo), '_') .
            ($esBachillerato && $semestre
                ? '_SEMESTRE_' . $semestre->numero
                : '') .
            '.pdf';

        return Pdf::loadView('pdf.horarios_pdf', [
            'escuela' => $escuela,
            'nivel' => $nivel,
            'generacion' => $generacion,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'ciclo_escolar' => $cicloEscolar,

            'dias' => $dias,
            'horas' => $horas,
            'horarios' => $horarios,
            'horarioPorCelda' => $horarioPorCelda,
            'talleresPorCelda' => $talleresPorCelda,

            'profesor_titular' => $profesorTitular,
            'profesor_titular_id' => $profesorTitularId,
            'docentes_preescolar' => $docentesPreescolar,
            'docentes_horario' => $docentesHorario,

            'esBachillerato' => $esBachillerato,
            'esSecundaria' => $esSecundaria,
            'esPreescolar' => $esPreescolar,
            'esPrimaria' => $esPrimaria,

            'logo_izquierdo' => $logoIzquierdo,
            'logo_derecho' => $logoDerecho,
            'imagen_nivel' => $imagenNivel,
        ])
            ->setPaper('letter', 'portrait')
            ->stream($nombreArchivo);
    }



    public function reconocimiento_calificaciones_pdf(Request $request)
    {
        $slugNivel = $request->input('slug_nivel');
        $generacionId = $request->integer('generacion_id');
        $gradoId = $request->integer('grado_id');
        $grupoId = $request->integer('grupo_id');
        $semestreId = $request->integer('semestre_id');
        $inscripcionId = $request->integer('inscripcion_id');

        if (
            blank($slugNivel) ||
            blank($generacionId) ||
            blank($gradoId) ||
            blank($grupoId) ||
            blank($inscripcionId)
        ) {
            abort(422, 'Los parámetros slug_nivel, generacion_id, grado_id, grupo_id e inscripcion_id son obligatorios.');
        }

        /*
        |--------------------------------------------------------------------------
        | Escuela
        |--------------------------------------------------------------------------
        */

        $escuela = \App\Models\Escuela::query()->first();

        if (!$escuela) {
            abort(404, 'No se encontró la escuela.');
        }

        /*
        |--------------------------------------------------------------------------
        | Nivel
        |--------------------------------------------------------------------------
        */

        $nivel = Nivel::query()
            ->where('slug', $slugNivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $esBachillerato = $this->esBachillerato($nivel);
        $esSecundaria = $this->esSecundaria($nivel);

        /*
        |--------------------------------------------------------------------------
        | Generación
        |--------------------------------------------------------------------------
        */

        $generacion = Generacion::query()
            ->where('id', $generacionId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$generacion) {
            abort(404, 'Generación no encontrada o no pertenece al nivel seleccionado.');
        }

        /*
        |--------------------------------------------------------------------------
        | Grado
        |--------------------------------------------------------------------------
        */

        $grado = Grado::query()
            ->where('id', $gradoId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$grado) {
            abort(404, 'Grado no encontrado o no pertenece al nivel seleccionado.');
        }

        /*
        |--------------------------------------------------------------------------
        | Grupo
        |--------------------------------------------------------------------------
        */

        $grupoQuery = Grupo::query()
            ->with('asignacionGrupo')
            ->where('id', $grupoId)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id);

        if ($esBachillerato) {
            if (blank($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $grupoQuery->where('semestre_id', $semestreId);
        } else {
            $grupoQuery->whereNull('semestre_id');
        }

        $grupo = $grupoQuery->first();

        if (!$grupo) {
            abort(404, 'Grupo no encontrado para el contexto seleccionado.');
        }

        /*
        |--------------------------------------------------------------------------
        | Semestre
        |--------------------------------------------------------------------------
        */

        $semestre = null;

        if ($esBachillerato) {
            $semestre = Semestre::query()
                ->where('id', $semestreId)
                ->where('grado_id', $grado->id)
                ->first();

            if (!$semestre) {
                abort(404, 'Semestre no encontrado o no pertenece al grado seleccionado.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Periodo correcto según nivel
        |--------------------------------------------------------------------------
        */

        $datosPeriodo = $this->obtenerPeriodoPdf(
            nivel: $nivel,
            generacion: $generacion,
            semestre: $semestre,
            request: $request
        );

        $periodo = $datosPeriodo['periodo'];
        $periodoId = $datosPeriodo['periodo_id'];

        $periodoBasicaId = $datosPeriodo['periodo_basica_id'];
        $parcialBachilleratoId = $datosPeriodo['parcial_bachillerato_id'];

        $periodoNumero = $datosPeriodo['periodoNumero'];
        $periodoTexto = $datosPeriodo['periodoTexto'];
        $nombrePeriodo = $datosPeriodo['nombrePeriodo'];

        $mesBasicaId = $datosPeriodo['mesBasicaId'];
        $mesBachilleratoId = $datosPeriodo['mesBachilleratoId'];

        $tipoPeriodo = $datosPeriodo['tipoPeriodo'];

        $cicloEscolarTexto = $periodo->cicloEscolar
            ? ($periodo->cicloEscolar->inicio_anio . '-' . $periodo->cicloEscolar->fin_anio)
            : '—';

        /*
        |--------------------------------------------------------------------------
        | Inscripción del alumno
        |--------------------------------------------------------------------------
        */

        $inscripcion = $this->buscarInscripcionAlumnoPdf(
            inscripcionId: $inscripcionId,
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            grupo: $grupo,
            esBachillerato: $esBachillerato
        );

        if (!$inscripcion) {
            abort(404, 'Inscripción no encontrada para el contexto seleccionado.');
        }

        $nombreAlumno = trim(
            ($inscripcion->apellido_paterno ?? '') . ' ' .
                ($inscripcion->apellido_materno ?? '') . ' ' .
                ($inscripcion->nombre ?? '')
        );

        if ($nombreAlumno === '') {
            $nombreAlumno = 'ALUMNO';
        }

        /*
        |--------------------------------------------------------------------------
        | Materias calificables
        |--------------------------------------------------------------------------
        */

        $queryMaterias = AsignacionMateria::query()
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->select([
                'asignacion_materias.*',
                'materias.materia as materia',
                'materias.clave as clave',
                'materias.slug as slug',
                'materias.calificable as calificable',
                'materias.participa_en_calificacion_oficial as participa_en_calificacion_oficial',
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('asignacion_materias.ciclo_escolar_id', $periodo->ciclo_escolar_id)
            ->where('asignacion_materias.estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA);

        if ($esBachillerato) {
            // Reconocimientos y diplomas solo consideran materias oficiales.
            ReglasMateriaBachillerato::aplicarPromediables($queryMaterias);
        } else {
            $queryMaterias->where('materias.calificable', 1);
        }

        $this->aplicarFiltroSemestreAsignacion($queryMaterias, $esBachillerato, $semestre?->id);

        $materias = $queryMaterias
            // Se respeta el orden de la asignación de materias.
            ->orderByRaw('CASE WHEN asignacion_materias.orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('asignacion_materias.orden')
            ->orderBy('asignacion_materias.id')
            ->get();

        $idsMaterias = $materias->pluck('id')->values()->all();

        /*
        |--------------------------------------------------------------------------
        | Calificaciones del alumno
        |--------------------------------------------------------------------------
        */

        $queryCalificaciones = Calificacion::query()
            ->where('inscripcion_id', $inscripcion->id)
            ->where('periodo_id', $periodoId);

        if (!empty($idsMaterias)) {
            $queryCalificaciones->whereIn('asignacion_materia_id', $idsMaterias);
        }

        if (Schema::hasColumn('calificaciones', 'nivel_id')) {
            $queryCalificaciones->where('nivel_id', $nivel->id);
        }

        if (Schema::hasColumn('calificaciones', 'generacion_id')) {
            $queryCalificaciones->where('generacion_id', $generacion->id);
        }

        if (Schema::hasColumn('calificaciones', 'grado_id')) {
            $queryCalificaciones->where('grado_id', $grado->id);
        }

        if (Schema::hasColumn('calificaciones', 'grupo_id')) {
            $queryCalificaciones->where('grupo_id', $grupo->id);
        }

        if (!blank($periodo->ciclo_escolar_id) && Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
            $queryCalificaciones->where('ciclo_escolar_id', $periodo->ciclo_escolar_id);
        }

        if (Schema::hasColumn('calificaciones', 'semestre_id')) {
            if ($esBachillerato) {
                $queryCalificaciones->where('semestre_id', $semestre->id);
            } else {
                $queryCalificaciones->whereNull('semestre_id');
            }
        }

        $calificacionesMap = $queryCalificaciones
            ->get()
            ->keyBy('asignacion_materia_id');

        /*
        |--------------------------------------------------------------------------
        | Número de materias para promediar
        |--------------------------------------------------------------------------
        */

        $queryMateriaPromediar = MateriaPromediar::query()
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id);

        if (Schema::hasColumn('materia_promediar', 'grupo_id')) {
            $queryMateriaPromediar->where('grupo_id', $grupo->id);
        }

        if ($esBachillerato) {
            $queryMateriaPromediar->where('semestre_id', $semestre->id);
        } else {
            $queryMateriaPromediar->whereNull('semestre_id');
        }

        $registroPromedio = $queryMateriaPromediar->first();

        $materiasElegibles = $materias
            ->filter(function ($materia) use ($esBachillerato) {
                if ((int) ($materia->calificable ?? 0) !== 1) {
                    return false;
                }

                if ($esBachillerato) {
                    return (int) ($materia->extra ?? 0) === 0
                        && (int) ($materia->receso ?? 0) === 0;
                }

                return (int) ($materia->extra ?? 0) === 0
                    && (int) ($materia->receso ?? 0) === 0
                    && (int) ($materia->participa_en_calificacion_oficial ?? 1) === 1;
            })
            ->sortBy([
                fn($materia) => $materia->orden === null ? 1 : 0,
                fn($materia) => $materia->orden ?? 999,
                fn($materia) => $materia->id ?? 999,
            ])
            ->values();

        /*
         * Prioridad del divisor:
         * 1. materia_promediar.numero_materias, cuando existe y es mayor a 0.
         * 2. En bachillerato, total de materias con calificable = 1.
         * 3. En los demás niveles se conserva la configuración obligatoria.
         */
        $numeroConfigurado = (int) ($registroPromedio?->numero_materias ?? 0);

        $numeroMateriasPromediar = $numeroConfigurado > 0
            ? $numeroConfigurado
            : ($esBachillerato ? $materiasElegibles->count() : 0);

        $materiasPromediables = $numeroMateriasPromediar > 0
            ? $materiasElegibles
            : collect();

        $idsMateriasPromediables = $materiasPromediables
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();

        $hayMateriasPromediables = $numeroMateriasPromediar > 0 && !empty($idsMateriasPromediables);

        $obtenerNumeroValido = function ($valor) use ($esBachillerato): ?float {
            $valor = strtoupper(trim((string) $valor));

            if ($valor === '' || !is_numeric($valor)) {
                return null;
            }

            if ($esBachillerato) {
                $entero = CalificacionBachillerato::truncarParcial($valor);

                return $entero === null ? null : (float) $entero;
            }

            $numero = (float) $valor;

            if ($numero < 0 || $numero > 10) {
                return null;
            }

            return $numero;
        };

        $truncarPromedio = function (float $valor): float {
            /*
             * promedio-numerico-pro:
             * Se toma solo el primer decimal sin redondear.
             * Ejemplo: 8.777777777777778 se muestra como 8.7.
             */
            return PromedioExcel::truncar($valor) ?? 0.0;
        };

        /*
        |--------------------------------------------------------------------------
        | Filas de materias y promedio del alumno
        |--------------------------------------------------------------------------
        */

        $filasMaterias = [];

        $suma = 0;
        $capturadasNumericas = 0;
        $capturadasNumericasPromedio = 0;
        $especiales = 0;
        $reprobadas = 0;
        $aprobadas = 0;

        foreach ($materias as $materia) {
            $registro = $calificacionesMap->get($materia->id);

            $valor = $registro
                ? strtoupper(trim((string) $registro->calificacion))
                : '';

            $observacion = $registro?->observacion;

            $estado = 'Sin captura';
            $porcentaje = 0;
            $numero = $obtenerNumeroValido($valor);

            if ($esBachillerato && $numero !== null) {
                $valor = (string) (int) $numero;
            }

            if ($numero !== null) {
                $porcentaje = min(100, $numero * 10);
                $capturadasNumericas++;

                if ($hayMateriasPromediables && in_array((int) $materia->id, $idsMateriasPromediables, true)) {
                    $suma += $numero;
                    $capturadasNumericasPromedio++;
                }

                if ($numero < 6) {
                    $estado = 'En riesgo';
                    $reprobadas++;
                } elseif ($numero < 8) {
                    $estado = 'Regular';
                    $aprobadas++;
                } else {
                    $estado = 'Aprobado';
                    $aprobadas++;
                }
            } elseif ($valor !== '') {
                $estado = 'Especial';
                $especiales++;
            }

            $filasMaterias[] = [
                'materia' => $materia->materia ?: 'Materia',
                'clave' => $materia->clave ?: '—',
                'calificacion' => $valor !== '' ? $valor : '—',
                'estado' => $estado,
                'porcentaje' => $porcentaje,
                'observacion' => $observacion ?: '—',
                'extra' => (int) ($materia->extra ?? 0),
                'receso' => (int) ($materia->receso ?? 0),
                'promediable' => $hayMateriasPromediables && in_array((int) $materia->id, $idsMateriasPromediables, true),
            ];
        }

        $promedio = '0.0';
        $promedioNumero = 0.0;
        $promedioPreciso = null;
        $porcentajePromedio = 0;
        $estadoPromedio = 'Pendiente';

        if ($hayMateriasPromediables && $capturadasNumericasPromedio > 0) {
            /*
             * Se conserva el promedio con precisión completa. El truncamiento
             * se utiliza únicamente para presentar el valor final.
             */
            $promedioPreciso = (float) ($suma / $numeroMateriasPromediar);
            $promedioNumero = PromedioExcel::truncar($promedioPreciso) ?? 0.0;
            $promedio = PromedioExcel::formatear($promedioPreciso, 1, '0.0');
            $porcentajePromedio = min(100, $promedioPreciso * 10);

            if ($promedioPreciso < 6) {
                $estadoPromedio = 'Reprobado';
            } elseif ($promedioPreciso < 8) {
                $estadoPromedio = 'Regular';
            } else {
                $estadoPromedio = 'Aprobado';
            }
        }

        $totalMaterias = count($filasMaterias);

        $pendientes = collect($filasMaterias)
            ->filter(fn($fila) => $fila['calificacion'] === '—')
            ->count();

        $porcentajeCaptura = $totalMaterias > 0
            ? round((($totalMaterias - $pendientes) / $totalMaterias) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Lugar del alumno por promedio
        |--------------------------------------------------------------------------
        | Esta parte replica la lógica de calificaciones_pdf:
        | - Se toman todos los alumnos del mismo contexto.
        | - Se consultan sus calificaciones del mismo periodo.
        | - Se promedian solo materias extra = 0.
        | - Se divide entre el número configurado o, si falta, entre las materias calificables.
        | - Se toma solo el primer decimal sin redondear.
        | - Se ordena de mayor a menor.
        | - Los empates comparten lugar.
        | - Solo se consideran los primeros 3 lugares.
        */

        $queryInscripcionesLugar = $this->queryInscripcionesPorContextoPdf(
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            grupo: $grupo,
            esBachillerato: $esBachillerato
        );

        $inscripcionesLugar = $queryInscripcionesLugar
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get()
            ->map(function ($item) {
                return [
                    'inscripcion_id' => (int) $item->id,
                    'matricula' => $item->matricula ?: '—',
                    'alumno' => trim(
                        ($item->nombre ?? '') . ' ' .
                            ($item->apellido_paterno ?? '') . ' ' .
                            ($item->apellido_materno ?? '')
                    ) ?: '—',
                    'grado' => $item->grado?->nombre ?? '—',
                    'grupo' => $this->nombreGrupo($item->grupo),
                    'semestre' => $item->semestre?->numero ?? '—',
                ];
            })
            ->values()
            ->toArray();

        $idsInscripcionesLugar = collect($inscripcionesLugar)
            ->pluck('inscripcion_id')
            ->values()
            ->all();

        $idsMateriasLugar = $materiasPromediables
            ->pluck('id')
            ->values()
            ->all();

        $calificacionesLugar = [];

        if (!empty($idsInscripcionesLugar) && !empty($idsMateriasLugar)) {
            $queryCalificacionesLugar = Calificacion::query()
                ->whereIn('inscripcion_id', $idsInscripcionesLugar)
                ->whereIn('asignacion_materia_id', $idsMateriasLugar)
                ->where('periodo_id', $periodoId);

            if (Schema::hasColumn('calificaciones', 'nivel_id')) {
                $queryCalificacionesLugar->where('nivel_id', $nivel->id);
            }

            if (Schema::hasColumn('calificaciones', 'generacion_id')) {
                $queryCalificacionesLugar->where('generacion_id', $generacion->id);
            }

            if (Schema::hasColumn('calificaciones', 'grado_id')) {
                $queryCalificacionesLugar->where('grado_id', $grado->id);
            }

            if (Schema::hasColumn('calificaciones', 'grupo_id')) {
                $queryCalificacionesLugar->where('grupo_id', $grupo->id);
            }

            if (!blank($periodo->ciclo_escolar_id) && Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
                $queryCalificacionesLugar->where('ciclo_escolar_id', $periodo->ciclo_escolar_id);
            }

            if (Schema::hasColumn('calificaciones', 'semestre_id')) {
                if ($esBachillerato) {
                    $queryCalificacionesLugar->where('semestre_id', $semestre->id);
                } else {
                    $queryCalificacionesLugar->whereNull('semestre_id');
                }
            }

            $calificacionesLugar = $queryCalificacionesLugar
                ->get()
                ->mapWithKeys(function ($item) {
                    $clave = $item->inscripcion_id . '-' . $item->asignacion_materia_id;

                    return [
                        $clave => strtoupper(trim((string) $item->calificacion)),
                    ];
                })
                ->toArray();
        }

        $promediosLugar = [];
        $promediosLugarPrecisos = [];

        foreach ($inscripcionesLugar as $filaLugar) {
            $inscripcionLugarId = (int) $filaLugar['inscripcion_id'];

            if (!$hayMateriasPromediables) {
                $promediosLugar[$inscripcionLugarId] = 'Pendiente';
                $promediosLugarPrecisos[$inscripcionLugarId] = null;
                continue;
            }

            $sumaLugar = 0;
            $totalNumericasLugar = 0;

            foreach ($materiasPromediables as $materia) {
                $claveLugar = $inscripcionLugarId . '-' . $materia->id;
                $numeroLugar = $obtenerNumeroValido($calificacionesLugar[$claveLugar] ?? null);

                if ($numeroLugar === null) {
                    continue;
                }

                $sumaLugar += $numeroLugar;
                $totalNumericasLugar++;
            }

            if ($totalNumericasLugar === 0) {
                $promediosLugar[$inscripcionLugarId] = 'Pendiente';
                $promediosLugarPrecisos[$inscripcionLugarId] = null;
                continue;
            }

            $promedioLugarPreciso = (float) ($sumaLugar / $numeroMateriasPromediar);
            $promediosLugarPrecisos[$inscripcionLugarId] = $promedioLugarPreciso;
            $promediosLugar[$inscripcionLugarId] = PromedioExcel::formatear($promedioLugarPreciso, 1, 'Pendiente');
        }

        $inscripcionesOrdenadasLugar = collect($inscripcionesLugar)
            ->sortByDesc(function ($filaLugar) use ($promediosLugarPrecisos) {
                $promedioAlumnoLugar = $promediosLugarPrecisos[$filaLugar['inscripcion_id']] ?? null;

                return is_numeric($promedioAlumnoLugar) ? (float) $promedioAlumnoLugar : -1;
            })
            ->values();

        $promediosUnicosLugar = $hayMateriasPromediables
            ? $inscripcionesOrdenadasLugar
            ->map(function ($filaLugar) use ($promediosLugarPrecisos) {
                $promedioAlumnoLugar = $promediosLugarPrecisos[$filaLugar['inscripcion_id']] ?? null;

                if (!is_numeric($promedioAlumnoLugar)) {
                    return null;
                }

                return PromedioExcel::claveComparacion((float) $promedioAlumnoLugar);
            })
            ->filter()
            ->unique()
            ->values()
            ->take(3)
            : collect();

        $lugaresPorPromedio = [];

        foreach ($promediosUnicosLugar as $index => $promedioUnicoLugar) {
            $lugaresPorPromedio[$promedioUnicoLugar] = $index + 1;
        }

        $promedioClaveAlumno = $hayMateriasPromediables && is_numeric($promedioPreciso)
            ? PromedioExcel::claveComparacion($promedioPreciso)
            : null;

        $lugarAlumno = $promedioClaveAlumno && isset($lugaresPorPromedio[$promedioClaveAlumno])
            ? $lugaresPorPromedio[$promedioClaveAlumno]
            : null;

        $textoLugarAlumno = $lugarAlumno
            ? $lugarAlumno . '° LUGAR'
            : 'PENDIENTE';

        /*
        |--------------------------------------------------------------------------
        | Docente y director
        |--------------------------------------------------------------------------
        */

        $profesorId = $materias
            ->pluck('profesor_id')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        $docente = null;

        if ($profesorId) {
            $docente = Persona::query()->find($profesorId);
        }

        $director = Nivel::query()
            ->where('id', $nivel->id)
            ->with(['director'])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Logos
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Logos y datos institucionales del reconocimiento
        |--------------------------------------------------------------------------
        | Se usan imágenes en base64 porque Dompdf suele fallar con rutas relativas.
        | Las imágenes deben estar dentro de public/.
        */

        $logoIzquierdo = $this->imagenBase64Publica('imagenes/logo-letra.png');

        $logoDerecho = $this->imagenBase64Publica('imagenes/logo-edu.png');

        $marcaAgua = $this->imagenBase64Publica('imagenes/logo-letra.png');

        /*
            |
            | Datos según el nivel
            |--------------------------------------------------------------------------
            | Se arma el encabezado de acuerdo al nivel seleccionado.
            */

        $nombreNivel = mb_strtolower($nivel->nombre ?? '');

        $secretariaTexto = 'SECRETARÍA DE EDUCACIÓN GUERRERO';

        $nombreEscuelaReconocimiento = match (true) {
            str_contains($nombreNivel, 'preescolar') => 'JARDÍN DE NIÑOS PART. CENTRO UNIVERSITARIO MOCTEZUMA',
            str_contains($nombreNivel, 'primaria') => 'ESC.PRIM.PART. CENTRO UNIVERSITARIO MOCTEZUMA',
            str_contains($nombreNivel, 'secundaria') => 'ESC.SEC.PART. CENTRO UNIVERSITARIO MOCTEZUMA',
            str_contains($nombreNivel, 'bachillerato') => 'BACHILLERATO GENERAL CENTRO UNIVERSITARIO MOCTEZUMA',
            default => mb_strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA'),
        };

        $cctReconocimiento = match (true) {
            str_contains($nombreNivel, 'preescolar') => data_get($nivel, 'cct') ?: 'C.C.T. 12PJN0226W',
            str_contains($nombreNivel, 'primaria') => data_get($nivel, 'cct') ?: 'C.C.T. 12PPR0070B',
            str_contains($nombreNivel, 'secundaria') => data_get($nivel, 'cct') ?: 'C.C.T. 12PES0105U',
            str_contains($nombreNivel, 'bachillerato') => data_get($nivel, 'cct') ?: 'C.C.T. 12PBH0071R',
            default => data_get($nivel, 'cct') ?: data_get($escuela, 'cct') ?: 'C.C.T. NO EXISTE',
        };

        /*
        |--------------------------------------------------------------------------
        | Tipo de reconocimiento
        |--------------------------------------------------------------------------
        */

        $tipoReconocimiento = $esBachillerato
            ? 'RECONOCIMIENTO PARCIAL'
            : 'RECONOCIMIENTO DE PERIODO';

        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */


        $data = [
            'titulo' => $tipoReconocimiento,
            'escuela' => $escuela,

            'secretariaTexto' => $secretariaTexto,
            'nombreEscuelaReconocimiento' => $nombreEscuelaReconocimiento,
            'cctReconocimiento' => $cctReconocimiento,

            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'generacion' => $generacion,

            'esBachillerato' => $esBachillerato,
            'esSecundaria' => $esSecundaria,
            'logoIzquierdo' => $logoIzquierdo,
            'logoDerecho' => $logoDerecho,

            'director' => $director,
            'docente' => $docente,

            'periodo' => $periodo,
            'periodoNumero' => $periodoNumero,
            'periodoTexto' => $periodoTexto,
            'nombrePeriodo' => $nombrePeriodo,
            'periodoBasicaId' => $periodoBasicaId,
            'parcialBachilleratoId' => $parcialBachilleratoId,
            'mesBasicaId' => $mesBasicaId,
            'mesBachilleratoId' => $mesBachilleratoId,
            'tipoPeriodo' => $tipoPeriodo,

            'cicloEscolarTexto' => $cicloEscolarTexto,

            'inscripcion' => $inscripcion,
            'nombreAlumno' => $nombreAlumno,
            'alumnoNombre' => mb_strtoupper($nombreAlumno),

            'filasMaterias' => $filasMaterias,
            'materias' => $filasMaterias,

            'promedio' => $promedio,
            'promedioNumero' => $promedioNumero,
            'porcentajePromedio' => $porcentajePromedio,
            'estadoPromedio' => $estadoPromedio,

            'totalMaterias' => $totalMaterias,
            'pendientes' => $pendientes,
            'especiales' => $especiales,
            'aprobadas' => $aprobadas,
            'reprobadas' => $reprobadas,
            'porcentajeCaptura' => $porcentajeCaptura,

            'numeroMateriasPromediar' => $numeroMateriasPromediar,
            'hayMateriasPromediables' => $hayMateriasPromediables,

            'lugarAlumno' => $lugarAlumno,
            'textoLugarAlumno' => $textoLugarAlumno,
            'promediosLugar' => $promediosLugar,

            'fecha_impresion' => now(),

            'marcaAgua' => $marcaAgua,
        ];




        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        $nombreArchivo = 'RECONOCIMIENTO_' .
            str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) .
            '_' . str_replace(' ', '_', mb_strtoupper($nombrePeriodo)) .
            '.pdf';

        return Pdf::loadView('pdf.reconocimiento_pdf', $data)
            ->setPaper('letter', 'landscape')
            ->stream($nombreArchivo);
    }

    // CALIFICACIONES PDF

    public function calificaciones_pdf(\Illuminate\Http\Request $request)
    {
        $slugNivel = $request->input('slug_nivel');
        $generacionId = $request->input('generacion_id');
        $gradoId = $request->input('grado_id');
        $grupoId = $request->input('grupo_id');

        $periodoId = $request->input('periodo_id');
        $periodoBasicaId = $request->input('periodo_basica_id');
        $parcialBachilleratoId = $request->input('parcial_bachillerato_id');
        $semestreId = $request->input('semestre_id');

        $busqueda = trim((string) $request->input('busqueda', ''));

        if (empty($slugNivel) || empty($generacionId) || empty($gradoId) || empty($grupoId)) {
            abort(422, 'Los parámetros slug_nivel, generacion_id, grado_id y grupo_id son obligatorios.');
        }

        $escuela = \App\Models\Escuela::first();

        if (!$escuela) {
            abort(404, 'No se encontró la escuela.');
        }

        $nivel = Nivel::query()
            ->where('slug', $slugNivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $slugNivelNormalizado = mb_strtolower((string) $nivel->slug);
        $esBachillerato = $this->esBachillerato($nivel);

        $generacion = Generacion::query()
            ->where('id', $generacionId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$generacion) {
            abort(404, 'Generación no encontrada o no pertenece al nivel seleccionado.');
        }

        $grado = Grado::query()
            ->where('id', $gradoId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$grado) {
            abort(404, 'Grado no encontrado o no pertenece al nivel seleccionado.');
        }

        $grupoQuery = Grupo::query()
            ->with(['asignacionGrupo', 'generacion'])
            ->where('id', $grupoId)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id);

        if ($esBachillerato) {
            $grupoQuery->where('semestre_id', $semestreId);
        } else {
            $grupoQuery->whereNull('semestre_id');
        }

        $grupo = $grupoQuery->first();

        if (!$grupo) {
            abort(404, 'Grupo no encontrado para el contexto seleccionado.');
        }

        $semestre = null;

        if ($esBachillerato) {
            if (empty($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            if (
                empty($periodoId) &&
                empty($parcialBachilleratoId) &&
                blank($request->input('opcion_descarga'))
            ) {
                abort(422, 'El parcial es obligatorio para bachillerato.');
            }

            $semestre = Semestre::query()
                ->where('id', $semestreId)
                ->where('grado_id', $grado->id)
                ->first();

            if (!$semestre) {
                abort(404, 'Semestre no encontrado o no pertenece al grado seleccionado.');
            }

            if (!empty($grupo->semestre_id) && (int) $grupo->semestre_id !== (int) $semestre->id) {
                abort(422, 'El grupo seleccionado no pertenece al semestre indicado.');
            }
        } else {
            if (
                empty($periodoId) &&
                empty($periodoBasicaId) &&
                blank($request->input('opcion_descarga'))
            ) {
                abort(422, 'El periodo de básica es obligatorio.');
            }
        }

        /*
            |--------------------------------------------------------------------------
            | Periodo correcto según nivel
            |--------------------------------------------------------------------------
            */

        $datosPeriodo = $this->obtenerPeriodoPdf(
            nivel: $nivel,
            generacion: $generacion,
            semestre: $semestre,
            request: $request
        );

        $periodo = $datosPeriodo['periodo'];
        $periodoId = $datosPeriodo['periodo_id'];

        $periodoBasicaId = $datosPeriodo['periodo_basica_id'];
        $parcialBachilleratoId = $datosPeriodo['parcial_bachillerato_id'];

        $periodoNumero = $datosPeriodo['periodoNumero'];
        $periodoTexto = $datosPeriodo['periodoTexto'];
        $nombrePeriodo = $datosPeriodo['nombrePeriodo'];

        $mesBasicaId = $datosPeriodo['mesBasicaId'];
        $mesBachilleratoId = $datosPeriodo['mesBachilleratoId'];

        $tipoPeriodo = $datosPeriodo['tipoPeriodo'];

        $cicloEscolarId = $periodo->ciclo_escolar_id;

        if (blank($cicloEscolarId)) {
            $ultimoCiclo = CicloEscolar::query()
                ->latest('id')
                ->first();

            $cicloEscolarId = $ultimoCiclo?->id;
        }

        /*
            |--------------------------------------------------------------------------
            | Materias calificables
            |--------------------------------------------------------------------------
            */

        $queryMaterias = AsignacionMateria::query()
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->select([
                'asignacion_materias.*',
                'materias.materia as materia',
                'materias.clave as clave',
                'materias.slug as slug',
                'materias.calificable as calificable',
                'materias.participa_en_calificacion_oficial as participa_en_calificacion_oficial',
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('asignacion_materias.ciclo_escolar_id', $periodo->ciclo_escolar_id)
            ->where('asignacion_materias.estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA);

        if ($esBachillerato) {
            /*
             * Se recuperan las materias oficiales y las materias extra para
             * mostrarlas en tablas independientes. Las extra nunca participan
             * en promedios, lugares, aprobación ni estadísticas académicas.
             */
            ReglasMateriaBachillerato::aplicarCapturables($queryMaterias);
        } else {
            $queryMaterias->where('materias.calificable', true);
        }

        $this->aplicarFiltroSemestreAsignacion($queryMaterias, $esBachillerato, $semestre?->id);

        $queryMaterias
            // Se respeta el orden de asignación de materias.
            ->orderByRaw('CASE WHEN asignacion_materias.orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('asignacion_materias.orden')
            ->orderBy('asignacion_materias.id');

        $materias = $queryMaterias->get()
            ->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'materia' => $item->materia ?: 'MATERIA',
                    'clave' => $item->clave ?: '—',
                    'extra' => (int) ($item->extra ?? 0),
                    'calificable' => (int) ($item->calificable ?? 0),
                    'receso' => (int) ($item->receso ?? 0),
                    'participa_en_calificacion_oficial' => (int) ($item->participa_en_calificacion_oficial ?? 1),
                    'orden' => $item->orden,
                ];
            })
            ->values()
            ->toArray();

        $materiasOficiales = $esBachillerato
            ? collect($materias)
            ->filter(function (array $materia): bool {
                return (int) ($materia['calificable'] ?? 0) === 1
                    && (int) ($materia['extra'] ?? 0) === 0
                    && (int) ($materia['receso'] ?? 0) === 0;
            })
            ->values()
            : collect($materias)->values();

        $materiasExtra = $esBachillerato
            ? collect($materias)
            ->filter(function (array $materia): bool {
                return (int) ($materia['extra'] ?? 0) === 1
                    && (int) ($materia['receso'] ?? 0) === 0;
            })
            ->values()
            : collect();

        /*
            |--------------------------------------------------------------------------
            | Inscripciones
            |--------------------------------------------------------------------------
            */

        $queryInscripciones = $this->queryInscripcionesPorContextoPdf(
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            grupo: $grupo,
            esBachillerato: $esBachillerato
        );

        if ($busqueda !== '') {
            $queryInscripciones->where(function ($query) use ($busqueda) {
                $query->where('matricula', 'like', "%{$busqueda}%")
                    ->orWhere('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_paterno', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_materno', 'like', "%{$busqueda}%")
                    ->orWhere(
                        DB::raw("TRIM(CONCAT(nombre,' ',IFNULL(apellido_paterno,''),' ',IFNULL(apellido_materno,'')))"),
                        'like',
                        "%{$busqueda}%"
                    );
            });
        }

        $inscripciones = $queryInscripciones
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get()
            ->map(function ($item) {
                return [
                    'inscripcion_id' => (int) $item->id,
                    'matricula' => $item->matricula ?: '—',
                    'alumno' => trim(
                        ($item->nombre ?? '') . ' ' .
                            ($item->apellido_paterno ?? '') . ' ' .
                            ($item->apellido_materno ?? '')
                    ) ?: '—',
                    'grado' => $item->grado?->nombre ?? '—',
                    'grupo' => $this->nombreGrupo($item->grupo),
                    'semestre' => $item->semestre?->numero ?? '—',
                ];
            })
            ->values()
            ->toArray();

        $idsInscripciones = collect($inscripciones)->pluck('inscripcion_id')->values()->all();
        $idsMaterias = collect($materias)->pluck('id')->values()->all();

        /*
            |--------------------------------------------------------------------------
            | Calificaciones guardadas
            |--------------------------------------------------------------------------
            */

        $calificaciones = [];

        if (!empty($idsInscripciones) && !empty($idsMaterias)) {
            $queryCalificaciones = Calificacion::query()
                ->whereIn('inscripcion_id', $idsInscripciones)
                ->whereIn('asignacion_materia_id', $idsMaterias)
                ->where('periodo_id', $periodoId);

            if (Schema::hasColumn('calificaciones', 'nivel_id')) {
                $queryCalificaciones->where('nivel_id', $nivel->id);
            }

            if (Schema::hasColumn('calificaciones', 'generacion_id')) {
                $queryCalificaciones->where('generacion_id', $generacion->id);
            }

            if (Schema::hasColumn('calificaciones', 'grado_id')) {
                $queryCalificaciones->where('grado_id', $grado->id);
            }

            if (Schema::hasColumn('calificaciones', 'grupo_id')) {
                $queryCalificaciones->where('grupo_id', $grupo->id);
            }

            if (!blank($cicloEscolarId) && Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
                $queryCalificaciones->where('ciclo_escolar_id', $cicloEscolarId);
            }

            if (Schema::hasColumn('calificaciones', 'semestre_id')) {
                if ($esBachillerato) {
                    $queryCalificaciones->where('semestre_id', $semestre->id);
                } else {
                    $queryCalificaciones->whereNull('semestre_id');
                }
            }

            $calificaciones = $queryCalificaciones->get()
                ->mapWithKeys(function ($item) use ($esBachillerato) {
                    $clave = $item->inscripcion_id . '-' . $item->asignacion_materia_id;
                    $valor = strtoupper(trim((string) $item->calificacion));

                    if ($esBachillerato && is_numeric($valor)) {
                        $valor = CalificacionBachillerato::formatearEntero($valor, '');
                    }

                    return [$clave => $valor];
                })
                ->toArray();
        }

        /*
        |--------------------------------------------------------------------------
        | Número de materias para promediar
        |--------------------------------------------------------------------------
        */

        $queryMateriaPromediar = MateriaPromediar::query()
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id);

        if (Schema::hasColumn('materia_promediar', 'grupo_id')) {
            $queryMateriaPromediar->where('grupo_id', $grupo->id);
        }

        if ($esBachillerato) {
            $queryMateriaPromediar->where('semestre_id', $semestre->id);
        } else {
            $queryMateriaPromediar->whereNull('semestre_id');
        }

        $registroPromedio = $queryMateriaPromediar->first();

        $numeroConfigurado = (int) ($registroPromedio?->numero_materias ?? 0);

        $numeroMateriasPromediar = $numeroConfigurado > 0
            ? $numeroConfigurado
            : ($esBachillerato
                ? $materiasOficiales->count()
                : 0);

        /*
            |--------------------------------------------------------------------------
            | promedio-numerico-pro
            |--------------------------------------------------------------------------
            | Solo entran materias normales, no materias extra.
            | No se usa take(numero_materias), porque puede dejar fuera materias
            | numéricas si antes aparecen AC, NP, SD, ED, RA, textos o vacíos.
            | El promedio se divide solo entre calificaciones numéricas encontradas.
            */
        $materiasPromediables = $numeroMateriasPromediar > 0
            ? $materiasOficiales
            ->filter(fn($materia) => (int) ($materia['extra'] ?? 0) === 0
                && (int) ($materia['receso'] ?? 0) === 0
                && (!$esBachillerato || (int) ($materia['calificable'] ?? 0) === 1)
                && ($esBachillerato || (int) ($materia['participa_en_calificacion_oficial'] ?? 1) === 1))
            ->sortBy([
                fn($materia) => ($materia['orden'] ?? null) === null ? 1 : 0,
                fn($materia) => $materia['orden'] ?? 999,
                fn($materia) => $materia['id'] ?? 999,
            ])
            ->values()
            : collect();

        $idsMateriasPromediables = $materiasPromediables
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();

        /*
            |--------------------------------------------------------------------------
            | Helpers internos para calificaciones
            |--------------------------------------------------------------------------
            */

        $obtenerNumeroValido = function ($valor) use ($esBachillerato): ?float {
            $valor = strtoupper(trim((string) $valor));

            if ($valor === '' || !is_numeric($valor)) {
                return null;
            }

            if ($esBachillerato) {
                $entero = CalificacionBachillerato::truncarParcial($valor);

                return $entero === null ? null : (float) $entero;
            }

            $numero = (float) $valor;

            if ($numero < 0 || $numero > 10) {
                return null;
            }

            return $numero;
        };

        $truncarPromedio = function (float $valor): float {
            /*
             * promedio-numerico-pro:
             * Se toma solo el primer decimal sin redondear.
             * Ejemplo: 8.777777777777778 se muestra como 8.7.
             */
            return PromedioExcel::truncar($valor) ?? 0.0;
        };

        /*
        |--------------------------------------------------------------------------
        | Promedios por alumno
        |--------------------------------------------------------------------------
        */

        $promedios = [];
        $promediosPrecisos = [];
        $totalNumericasPorAlumno = [];

        foreach ($inscripciones as $fila) {
            $inscripcionId = (int) $fila['inscripcion_id'];

            if ($numeroMateriasPromediar <= 0 || empty($idsMateriasPromediables)) {
                $promedios[$inscripcionId] = 'Pendiente';
                $promediosPrecisos[$inscripcionId] = null;
                $totalNumericasPorAlumno[$inscripcionId] = 0;
                continue;
            }

            $suma = 0;
            $totalNumericas = 0;

            foreach ($materiasPromediables as $materia) {
                $clave = $inscripcionId . '-' . $materia['id'];
                $numero = $obtenerNumeroValido($calificaciones[$clave] ?? null);

                if ($numero === null) {
                    continue;
                }

                $suma += $numero;
                $totalNumericas++;
            }

            $totalNumericasPorAlumno[$inscripcionId] = $totalNumericas;

            /*
             * Si el alumno no tiene calificaciones numéricas,
             * no se marca como reprobado; queda pendiente.
             */
            if ($totalNumericas === 0) {
                $promedios[$inscripcionId] = 'Pendiente';
                $promediosPrecisos[$inscripcionId] = null;
                continue;
            }

            /*
             * Se conserva la precisión completa para cálculos posteriores.
             * Solo el valor mostrado se trunca a un decimal.
             */
            $divisorPromedio = $esBachillerato
                ? $numeroMateriasPromediar
                : $totalNumericas;

            $promedioPreciso = (float) ($suma / $divisorPromedio);
            $promediosPrecisos[$inscripcionId] = $promedioPreciso;
            $promedios[$inscripcionId] = PromedioExcel::formatear($promedioPreciso, 1, 'Pendiente');
        }

        /*
            |--------------------------------------------------------------------------
            | Promedio por materia
            |--------------------------------------------------------------------------
            | Solo se toman calificaciones numéricas.
            | AC, ED, RA, NP, SD, vacíos o textos no se suman ni se cuentan.
            */

        $promediosPorMateria = [];

        foreach ($materiasPromediables as $materia) {
            $asignacionMateriaId = (int) ($materia['id'] ?? 0);

            if ($asignacionMateriaId <= 0) {
                continue;
            }

            $sumaMateria = 0;
            $totalNumericasMateria = 0;
            $totalPendientesMateria = 0;
            $totalEspecialesMateria = 0;

            foreach ($inscripciones as $fila) {
                $inscripcionId = (int) ($fila['inscripcion_id'] ?? 0);
                $clave = $inscripcionId . '-' . $asignacionMateriaId;

                $valor = strtoupper(trim((string) ($calificaciones[$clave] ?? '')));

                if ($valor === '') {
                    $totalPendientesMateria++;
                    continue;
                }

                if (in_array($valor, ['AC', 'ED', 'RA', 'NP', 'SD'], true)) {
                    $totalEspecialesMateria++;
                    continue;
                }

                if (!is_numeric($valor)) {
                    $totalPendientesMateria++;
                    continue;
                }

                $numero = (float) $valor;

                if ($numero < 0 || $numero > 10) {
                    $totalPendientesMateria++;
                    continue;
                }

                $sumaMateria += $numero;
                $totalNumericasMateria++;
            }

            if ($totalNumericasMateria > 0) {
                $promedioMateria = (float) ($sumaMateria / $totalNumericasMateria);

                $promedioTexto = PromedioExcel::formatear($promedioMateria);
                $porcentaje = min(100, max(0, $promedioMateria * 10));

                if ($promedioMateria < 6) {
                    $estado = 'En riesgo';
                } elseif ($promedioMateria < 8) {
                    $estado = 'Regular';
                } else {
                    $estado = 'Aprobatorio';
                }
            } else {
                $promedioTexto = 'Pendiente';
                $porcentaje = 0;
                $estado = 'Sin datos';
            }

            $promediosPorMateria[] = [
                'id' => $asignacionMateriaId,
                'asignacion_materia_id' => $asignacionMateriaId,
                'materia' => $materia['materia'] ?? 'Materia',
                'promedio' => $promedioTexto,
                'porcentaje' => $porcentaje,
                'total_capturadas' => $totalNumericasMateria,
                'total_pendientes' => $totalPendientesMateria,
                'total_especiales' => $totalEspecialesMateria,
                'estado' => $estado,
            ];
        }

        /*
    |--------------------------------------------------------------------------
    | Promedio general del grupo
    |--------------------------------------------------------------------------
    */

        $promediosNumericosGrupo = collect($promediosPrecisos)
            ->filter(fn($valor) => is_numeric($valor))
            ->map(fn($valor) => (float) $valor)
            ->values();

        $promedioGeneralGrupo = PromedioExcel::calcular($promediosNumericosGrupo) ?? 0.0;

        $promedioGeneralGrupoTexto = PromedioExcel::formatear($promedioGeneralGrupo, 1, '0.0');

        $porcentajePromedioGeneral = min(100, $promedioGeneralGrupo * 10);

        /*
    |--------------------------------------------------------------------------
    | Estadísticas generales
    |--------------------------------------------------------------------------
    */

        $totalAlumnos = count($inscripciones);

        $totalConPromedio = $promediosNumericosGrupo->count();

        $totalAprobados = collect($promediosPrecisos)
            ->filter(fn($valor) => is_numeric($valor) && (float) $valor >= 6)
            ->count();

        $totalReprobados = collect($promediosPrecisos)
            ->filter(fn($valor) => is_numeric($valor) && (float) $valor < 6)
            ->count();

        $totalSinPromedio = max(0, $totalAlumnos - $totalConPromedio);

        /*
         * La aprobación se calcula solo con alumnos que ya tienen promedio numérico.
         * Los pendientes no cuentan como reprobados.
         */
        $porcentajeAprobacion = $totalConPromedio > 0
            ? round(($totalAprobados / $totalConPromedio) * 100)
            : 0;

        /*
    |--------------------------------------------------------------------------
    | Periodos por materia
    |--------------------------------------------------------------------------
    */

        $periodosPorMateria = collect($materiasPromediables)
            ->map(function ($materia) use ($periodo, $nombrePeriodo, $esBachillerato) {
                return [
                    'materia' => $materia['materia'],
                    'periodo' => $nombrePeriodo,
                    'tipo' => $esBachillerato ? 'Parcial bachillerato' : 'Periodo básica',
                    'fecha_inicio' => $periodo?->fecha_inicio
                        ? \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y')
                        : '—',
                    'fecha_fin' => $periodo?->fecha_fin
                        ? \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y')
                        : '—',
                ];
            })
            ->values()
            ->toArray();

        /*
    |--------------------------------------------------------------------------
    | Logos e imagen por nivel
    |--------------------------------------------------------------------------
    */

        $logoIzquierdo = public_path('storage/logos/' . $nivel->logo);
        $logoDerecho = public_path('imagenes/logo-letra.png');

        $imagenesPorNivel = [
            'preescolar' => public_path('imagenes/personajes_preescolar.png'),
            'primaria' => public_path('imagenes/personajes_primaria.png'),
            'secundaria' => public_path('imagenes/personajes_secundaria.png'),
            'bachillerato' => public_path('imagenes/personajes_bachillerato.png'),
        ];

        $imagenNivel = $imagenesPorNivel[$slugNivelNormalizado] ?? null;

        $logoIzquierdo = file_exists($logoIzquierdo) ? $logoIzquierdo : null;
        $logoDerecho = file_exists($logoDerecho) ? $logoDerecho : null;
        $imagenNivel = ($imagenNivel && file_exists($imagenNivel)) ? $imagenNivel : null;

        $pdf = Pdf::loadView('pdf.calificaciones_pdf', [
            'titulo' => 'REPORTE DE CALIFICACIONES',

            'escuela' => $escuela,
            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'esBachillerato' => $esBachillerato,

            'periodo' => $periodo,
            'periodoNumero' => $periodoNumero,
            'periodoTexto' => $periodoTexto,
            'nombrePeriodo' => $nombrePeriodo,
            'periodoBasicaId' => $periodoBasicaId,
            'parcialBachilleratoId' => $parcialBachilleratoId,
            'mesBasicaId' => $mesBasicaId,
            'mesBachilleratoId' => $mesBachilleratoId,
            'tipoPeriodo' => $tipoPeriodo,
            'busqueda' => $busqueda,

            'materias' => $materiasOficiales->values()->toArray(),
            'materiasExtra' => $materiasExtra->values()->toArray(),
            'inscripciones' => $inscripciones,
            'calificaciones' => $calificaciones,
            'promedios' => $promedios,

            'promediosPorMateria' => $promediosPorMateria,

            'promedioGeneralGrupo' => $promedioGeneralGrupoTexto,
            'porcentajePromedioGeneral' => $porcentajePromedioGeneral,
            'porcentajeAprobacion' => $porcentajeAprobacion,
            'totalAlumnos' => $totalAlumnos,
            'totalAprobados' => $totalAprobados,
            'totalReprobados' => $totalReprobados,
            'totalSinPromedio' => $totalSinPromedio,
            'totalConPromedio' => $totalConPromedio,
            'totalNumericasPorAlumno' => $totalNumericasPorAlumno,

            'periodosPorMateria' => $periodosPorMateria ?? [],

            'logo_izquierdo' => $logoIzquierdo,
            'logo_derecho' => $logoDerecho,
            'imagen_nivel' => $imagenNivel,
        ])->setPaper('letter', 'landscape');

        $nombreArchivo = 'CALIFICACIONES_' .
            mb_strtoupper($nivel->nombre ?? 'NIVEL') . '_' .
            'GRADO_' . ($grado->nombre ?? 'GRADO') . '_' .
            'GRUPO_' . $this->nombreGrupo($grupo) .
            ($esBachillerato && $semestre ? '_SEMESTRE_' . $semestre->numero : '') .
            '_' . ($esBachillerato ? 'PARCIAL_' : 'PERIODO_') . $periodoNumero .
            '.pdf';

        return $pdf->stream($nombreArchivo);
    }



    public function boleta_calificaciones_pdf(\Illuminate\Http\Request $request)
    {
        $slugNivel = $request->input('slug_nivel');
        $generacionId = $request->integer('generacion_id');
        $gradoId = $request->integer('grado_id');
        $grupoId = $request->integer('grupo_id');
        $semestreId = $request->integer('semestre_id');
        $inscripcionId = $request->integer('inscripcion_id');

        if (
            blank($slugNivel) ||
            blank($generacionId) ||
            blank($gradoId) ||
            blank($grupoId) ||
            blank($inscripcionId)
        ) {
            abort(422, 'Los parámetros slug_nivel, generacion_id, grado_id, grupo_id e inscripcion_id son obligatorios.');
        }

        $escuela = \App\Models\Escuela::query()->first();

        if (!$escuela) {
            abort(404, 'No se encontró la escuela.');
        }

        $nivel = Nivel::query()
            ->where('slug', $slugNivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $esBachillerato = $this->esBachillerato($nivel);
        $esSecundaria = $this->esSecundaria($nivel);

        /*
         * Primaria conserva su comportamiento actual. En bachillerato las
         * materias extra también se muestran en una tabla independiente y son
         * completamente informativas: no afectan parciales, semestre, final,
         * estados académicos, lugares ni reconocimientos.
         */
        $esPrimaria = \Illuminate\Support\Str::contains(
            mb_strtolower((string) ($nivel->slug ?? $nivel->nombre ?? '')),
            'primaria'
        );

        $separarMateriasExtras = $esPrimaria || $esBachillerato;

        $generacion = Generacion::query()
            ->where('id', $generacionId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$generacion) {
            abort(404, 'Generación no encontrada o no pertenece al nivel seleccionado.');
        }

        $grado = Grado::query()
            ->where('id', $gradoId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$grado) {
            abort(404, 'Grado no encontrado o no pertenece al nivel seleccionado.');
        }

        $grupoQuery = Grupo::query()
            ->with('asignacionGrupo')
            ->where('id', $grupoId)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id);

        if ($esBachillerato) {
            if (blank($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $grupoQuery->where('semestre_id', $semestreId);
        } else {
            $grupoQuery->whereNull('semestre_id');
        }

        $grupo = $grupoQuery->first();

        if (!$grupo) {
            abort(404, 'Grupo no encontrado para el contexto seleccionado.');
        }

        $semestre = null;

        if ($esBachillerato) {
            $semestre = Semestre::query()
                ->where('id', $semestreId)
                ->where('grado_id', $grado->id)
                ->first();

            if (!$semestre) {
                abort(404, 'Semestre no encontrado o no pertenece al grado seleccionado.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Periodo correcto según nivel
        |--------------------------------------------------------------------------
        */

        $datosPeriodo = $this->obtenerPeriodoPdf(
            nivel: $nivel,
            generacion: $generacion,
            semestre: $semestre,
            request: $request
        );

        $periodo = $datosPeriodo['periodo'];
        $periodoId = $datosPeriodo['periodo_id'];

        $periodoBasicaId = $datosPeriodo['periodo_basica_id'];
        $parcialBachilleratoId = $datosPeriodo['parcial_bachillerato_id'];

        $periodoNumero = $datosPeriodo['periodoNumero'];
        $periodoTexto = $datosPeriodo['periodoTexto'];
        $nombrePeriodo = $datosPeriodo['nombrePeriodo'];

        $mesBasicaId = $datosPeriodo['mesBasicaId'];
        $mesBachilleratoId = $datosPeriodo['mesBachilleratoId'];

        $tipoPeriodo = $datosPeriodo['tipoPeriodo'];

        $cicloEscolarTexto = $periodo->cicloEscolar
            ? ($periodo->cicloEscolar->inicio_anio . '-' . $periodo->cicloEscolar->fin_anio)
            : '—';

        /*
        |--------------------------------------------------------------------------
        | Inscripción
        |--------------------------------------------------------------------------
        */

        $inscripcion = $this->buscarInscripcionAlumnoPdf(
            inscripcionId: $inscripcionId,
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            grupo: $grupo,
            esBachillerato: $esBachillerato
        );

        if (!$inscripcion) {
            abort(404, 'Inscripción no encontrada para el contexto seleccionado.');
        }

        $nombreAlumno = trim(
            ($inscripcion->apellido_paterno ?? '') . ' ' .
                ($inscripcion->apellido_materno ?? '') . ' ' .
                ($inscripcion->nombre ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | Materias calificables
        |--------------------------------------------------------------------------
        */

        $queryMaterias = AsignacionMateria::query()
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->select([
                'asignacion_materias.*',
                'materias.materia as materia',
                'materias.clave as clave',
                'materias.slug as slug',
                'materias.calificable as calificable',
                'materias.participa_en_calificacion_oficial as participa_en_calificacion_oficial',
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('asignacion_materias.ciclo_escolar_id', $periodo->ciclo_escolar_id)
            ->where('asignacion_materias.estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA);

        if ($esBachillerato) {
            ReglasMateriaBachillerato::aplicarCapturables($queryMaterias);
        } else {
            $queryMaterias->where('materias.calificable', true);
        }

        $this->aplicarFiltroSemestreAsignacion($queryMaterias, $esBachillerato, $semestre?->id);

        $materias = $queryMaterias
            ->orderByRaw('CASE WHEN asignacion_materias.orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('asignacion_materias.orden')
            ->orderBy('asignacion_materias.id')
            ->get();

        $idsMaterias = $materias->pluck('id')->values()->all();

        /*
        |--------------------------------------------------------------------------
        | Calificaciones
        |--------------------------------------------------------------------------
        */

        $queryCalificaciones = Calificacion::query()
            ->where('inscripcion_id', $inscripcion->id)
            ->where('periodo_id', $periodoId);

        if (!empty($idsMaterias)) {
            $queryCalificaciones->whereIn('asignacion_materia_id', $idsMaterias);
        }

        if (Schema::hasColumn('calificaciones', 'nivel_id')) {
            $queryCalificaciones->where('nivel_id', $nivel->id);
        }

        if (Schema::hasColumn('calificaciones', 'generacion_id')) {
            $queryCalificaciones->where('generacion_id', $generacion->id);
        }

        if (Schema::hasColumn('calificaciones', 'grado_id')) {
            $queryCalificaciones->where('grado_id', $grado->id);
        }

        if (Schema::hasColumn('calificaciones', 'grupo_id')) {
            $queryCalificaciones->where('grupo_id', $grupo->id);
        }

        if (!blank($periodo->ciclo_escolar_id) && Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
            $queryCalificaciones->where('ciclo_escolar_id', $periodo->ciclo_escolar_id);
        }

        if (Schema::hasColumn('calificaciones', 'semestre_id')) {
            if ($esBachillerato) {
                $queryCalificaciones->where('semestre_id', $semestre->id);
            } else {
                $queryCalificaciones->whereNull('semestre_id');
            }
        }

        $calificacionesMap = $queryCalificaciones
            ->get()
            ->keyBy('asignacion_materia_id');

        /*
        |--------------------------------------------------------------------------
        | Configuración de materias a promediar
        |--------------------------------------------------------------------------
        */

        $queryMateriaPromediar = MateriaPromediar::query()
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id);

        if (Schema::hasColumn('materia_promediar', 'grupo_id')) {
            $queryMateriaPromediar->where('grupo_id', $grupo->id);
        }

        if ($esBachillerato) {
            $queryMateriaPromediar->where('semestre_id', $semestre->id);
        } else {
            $queryMateriaPromediar->whereNull('semestre_id');
        }

        $registroPromedio = $queryMateriaPromediar->first();

        $numeroConfigurado = (int) ($registroPromedio?->numero_materias ?? 0);

        $numeroMateriasPromediar = $numeroConfigurado > 0
            ? $numeroConfigurado
            : ($esBachillerato
                ? $materias->filter(function ($materia): bool {
                    return (int) ($materia->calificable ?? 0) === 1
                        && (int) ($materia->extra ?? 0) === 0
                        && (int) ($materia->receso ?? 0) === 0;
                })->count()
                : 0);

        /*
         * promedio-numerico-pro:
         * Se toman todas las materias normales ordenadas por asignación.
         * No se usa take(), porque los textos como AC, NP, SD, ED o RA
         * no deben dejar fuera otras materias numéricas.
         */
        $materiasPromediables = $numeroMateriasPromediar > 0
            ? $materias
            ->filter(function ($materia) use ($esBachillerato) {
                return (int) ($materia->calificable ?? 0) === 1
                    && (int) ($materia->extra ?? 0) === 0
                    && (int) ($materia->receso ?? 0) === 0
                    && ($esBachillerato || (int) ($materia->participa_en_calificacion_oficial ?? 1) === 1);
            })
            ->sortBy([
                fn($materia) => $materia->orden === null ? 1 : 0,
                fn($materia) => $materia->orden ?? 999,
                fn($materia) => $materia->id ?? 999,
            ])
            ->values()
            : collect();

        $idsMateriasPromediables = $materiasPromediables
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();

        $hayMateriasPromediables = $numeroMateriasPromediar > 0 && !empty($idsMateriasPromediables);

        $obtenerNumeroValido = function ($valor) use ($esBachillerato): ?float {
            $valor = strtoupper(trim((string) $valor));

            if ($valor === '' || !is_numeric($valor)) {
                return null;
            }

            if ($esBachillerato) {
                $entero = CalificacionBachillerato::truncarParcial($valor);

                return $entero === null ? null : (float) $entero;
            }

            $numero = (float) $valor;

            if ($numero < 0 || $numero > 10) {
                return null;
            }

            return $numero;
        };

        $truncarPromedio = function (float $valor): float {
            /*
             * promedio-numerico-pro:
             * Se toma solo el primer decimal sin truncar.
             * Ejemplo: 8.777777777777778 se muestra como 8.7.
             */
            return PromedioExcel::truncar($valor) ?? 0.0;
        };

        /*
        |--------------------------------------------------------------------------
        | Filas de materias y promedio del alumno
        |--------------------------------------------------------------------------
        */

        $filasMaterias = [];
        $sumaPromedio = 0;
        $capturadasNumericasPromedio = 0;
        $capturadasNumericas = 0;
        $especiales = 0;
        $reprobadas = 0;
        $aprobadas = 0;

        foreach ($materias as $materia) {
            $registro = $calificacionesMap->get($materia->id);

            $valor = $registro
                ? strtoupper(trim((string) $registro->calificacion))
                : '';

            $observacion = $registro?->observacion;

            $estado = 'Sin captura';
            $porcentaje = 0;

            $numero = $obtenerNumeroValido($valor);

            if ($esBachillerato && $numero !== null) {
                $valor = (string) (int) $numero;
            }

            if ($numero !== null) {
                $porcentaje = min(100, $numero * 10);
                $capturadasNumericas++;

                if ($hayMateriasPromediables && in_array((int) $materia->id, $idsMateriasPromediables, true)) {
                    $sumaPromedio += $numero;
                    $capturadasNumericasPromedio++;
                }

                if ($numero < 6) {
                    $estado = 'En riesgo';
                    $reprobadas++;
                } elseif ($numero < 8) {
                    $estado = 'Regular';
                    $aprobadas++;
                } else {
                    $estado = 'Aprobado';
                    $aprobadas++;
                }
            } elseif ($valor !== '') {
                $estado = 'Especial';
                $especiales++;
            }

            $filasMaterias[] = [
                'materia' => $materia->materia ?: 'Materia',
                'clave' => $materia->clave ?: '—',
                'calificacion' => $valor !== '' ? $valor : '—',
                'estado' => $estado,
                'porcentaje' => $porcentaje,
                'observacion' => $observacion ?: '—',
                'extra' => (int) ($materia->extra ?? 0),
                'receso' => (int) ($materia->receso ?? 0),
                'promediable' => $hayMateriasPromediables && in_array((int) $materia->id, $idsMateriasPromediables, true),
            ];
        }

        $promedio = '0.0';
        $promedioNumero = 0.0;
        $promedioPreciso = null;
        $porcentajePromedio = 0;
        $estadoPromedio = 'Pendiente';

        if ($hayMateriasPromediables && $capturadasNumericasPromedio > 0) {
            /*
             * En bachillerato se respeta materia_promediar.numero_materias; si
             * no existe, se usa el total de materias calificables no extra.
             * En los demás niveles se conserva el divisor anterior.
             */
            $divisorPromedio = $esBachillerato
                ? $numeroMateriasPromediar
                : $capturadasNumericasPromedio;

            $promedioPreciso = (float) ($sumaPromedio / $divisorPromedio);
            $promedioNumero = PromedioExcel::truncar($promedioPreciso) ?? 0.0;
            $promedio = PromedioExcel::formatear($promedioPreciso, 1, '0.0');
            $porcentajePromedio = min(100, $promedioPreciso * 10);

            if ($promedioPreciso < 6) {
                $estadoPromedio = 'Reprobado';
            } elseif ($promedioPreciso < 8) {
                $estadoPromedio = 'Regular';
            } else {
                $estadoPromedio = 'Aprobado';
            }
        }

        /*
         * En primaria y bachillerato se separan las materias normales y las
         * materias extra. En bachillerato la segunda tabla es solo informativa.
         */
        $filasMateriasRegulares = collect($filasMaterias)
            ->filter(fn($fila) => (int) ($fila['extra'] ?? 0) === 0)
            ->values()
            ->toArray();

        $filasMateriasExtras = collect($filasMaterias)
            ->filter(fn($fila) => (int) ($fila['extra'] ?? 0) === 1)
            ->values()
            ->toArray();

        if (!$separarMateriasExtras) {
            $filasMateriasRegulares = $filasMaterias;
            $filasMateriasExtras = [];
        }

        /*
         * El resumen principal se recalcula con materias regulares para que las
         * extras no alteren aprobadas, reprobadas, especiales ni captura.
         */
        if ($esBachillerato) {
            $especiales = collect($filasMateriasRegulares)
                ->where('estado', 'Especial')
                ->count();

            $reprobadas = collect($filasMateriasRegulares)
                ->where('estado', 'En riesgo')
                ->count();

            $aprobadas = collect($filasMateriasRegulares)
                ->filter(fn(array $fila) => in_array($fila['estado'], ['Regular', 'Aprobado'], true))
                ->count();
        }

        $totalMaterias = count($filasMateriasRegulares);

        $pendientes = collect($filasMateriasRegulares)
            ->filter(fn($fila) => $fila['calificacion'] === '—')
            ->count();

        $porcentajeCaptura = $totalMaterias > 0
            ? round((($totalMaterias - $pendientes) / $totalMaterias) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Lugar del alumno por promedio
        |--------------------------------------------------------------------------
        */

        $queryInscripcionesLugar = $this->queryInscripcionesPorContextoPdf(
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            grupo: $grupo,
            esBachillerato: $esBachillerato
        );

        $inscripcionesLugar = $queryInscripcionesLugar
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get()
            ->map(function ($item) {
                return [
                    'inscripcion_id' => (int) $item->id,
                    'matricula' => $item->matricula ?: '—',
                    'alumno' => trim(
                        ($item->nombre ?? '') . ' ' .
                            ($item->apellido_paterno ?? '') . ' ' .
                            ($item->apellido_materno ?? '')
                    ) ?: '—',
                    'grado' => $item->grado?->nombre ?? '—',
                    'grupo' => $this->nombreGrupo($item->grupo),
                    'semestre' => $item->semestre?->numero ?? '—',
                ];
            })
            ->values()
            ->toArray();

        $idsInscripcionesLugar = collect($inscripcionesLugar)
            ->pluck('inscripcion_id')
            ->values()
            ->all();

        $idsMateriasLugar = $materiasPromediables
            ->pluck('id')
            ->values()
            ->all();

        $calificacionesLugar = [];

        if (!empty($idsInscripcionesLugar) && !empty($idsMateriasLugar)) {
            $queryCalificacionesLugar = Calificacion::query()
                ->whereIn('inscripcion_id', $idsInscripcionesLugar)
                ->whereIn('asignacion_materia_id', $idsMateriasLugar)
                ->where('periodo_id', $periodoId);

            if (Schema::hasColumn('calificaciones', 'nivel_id')) {
                $queryCalificacionesLugar->where('nivel_id', $nivel->id);
            }

            if (Schema::hasColumn('calificaciones', 'generacion_id')) {
                $queryCalificacionesLugar->where('generacion_id', $generacion->id);
            }

            if (Schema::hasColumn('calificaciones', 'grado_id')) {
                $queryCalificacionesLugar->where('grado_id', $grado->id);
            }

            if (Schema::hasColumn('calificaciones', 'grupo_id')) {
                $queryCalificacionesLugar->where('grupo_id', $grupo->id);
            }

            if (!blank($periodo->ciclo_escolar_id) && Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
                $queryCalificacionesLugar->where('ciclo_escolar_id', $periodo->ciclo_escolar_id);
            }

            if (Schema::hasColumn('calificaciones', 'semestre_id')) {
                if ($esBachillerato) {
                    $queryCalificacionesLugar->where('semestre_id', $semestre->id);
                } else {
                    $queryCalificacionesLugar->whereNull('semestre_id');
                }
            }

            $calificacionesLugar = $queryCalificacionesLugar
                ->get()
                ->mapWithKeys(function ($item) {
                    $clave = $item->inscripcion_id . '-' . $item->asignacion_materia_id;

                    return [
                        $clave => strtoupper(trim((string) $item->calificacion)),
                    ];
                })
                ->toArray();
        }

        $promediosLugar = [];
        $promediosLugarPrecisos = [];

        foreach ($inscripcionesLugar as $filaLugar) {
            $inscripcionLugarId = (int) $filaLugar['inscripcion_id'];

            if (!$hayMateriasPromediables) {
                $promediosLugar[$inscripcionLugarId] = 'Pendiente';
                $promediosLugarPrecisos[$inscripcionLugarId] = null;
                continue;
            }

            $sumaLugar = 0;
            $totalNumericasLugar = 0;

            foreach ($materiasPromediables as $materia) {
                $claveLugar = $inscripcionLugarId . '-' . $materia->id;
                $numeroLugar = $obtenerNumeroValido($calificacionesLugar[$claveLugar] ?? null);

                if ($numeroLugar === null) {
                    continue;
                }

                $sumaLugar += $numeroLugar;
                $totalNumericasLugar++;
            }

            if ($totalNumericasLugar === 0) {
                $promediosLugar[$inscripcionLugarId] = 'Pendiente';
                $promediosLugarPrecisos[$inscripcionLugarId] = null;
                continue;
            }

            $divisorLugar = $esBachillerato
                ? $numeroMateriasPromediar
                : $totalNumericasLugar;

            $promedioLugarPreciso = (float) ($sumaLugar / $divisorLugar);
            $promediosLugarPrecisos[$inscripcionLugarId] = $promedioLugarPreciso;
            $promediosLugar[$inscripcionLugarId] = PromedioExcel::formatear($promedioLugarPreciso, 1, 'Pendiente');
        }

        $inscripcionesOrdenadasLugar = collect($inscripcionesLugar)
            ->sortByDesc(function ($filaLugar) use ($promediosLugarPrecisos) {
                $promedioAlumnoLugar = $promediosLugarPrecisos[$filaLugar['inscripcion_id']] ?? null;

                return is_numeric($promedioAlumnoLugar) ? (float) $promedioAlumnoLugar : -1;
            })
            ->values();

        $promediosUnicosLugar = $inscripcionesOrdenadasLugar
            ->map(function ($filaLugar) use ($promediosLugarPrecisos) {
                $promedioAlumnoLugar = $promediosLugarPrecisos[$filaLugar['inscripcion_id']] ?? null;

                if (!is_numeric($promedioAlumnoLugar)) {
                    return null;
                }

                return PromedioExcel::claveComparacion((float) $promedioAlumnoLugar);
            })
            ->filter()
            ->unique()
            ->values()
            ->take(3);

        $lugaresPorPromedio = [];

        foreach ($promediosUnicosLugar as $index => $promedioUnicoLugar) {
            $lugaresPorPromedio[$promedioUnicoLugar] = $index + 1;
        }

        $promedioClaveAlumno = is_numeric($promedioPreciso)
            ? PromedioExcel::claveComparacion($promedioPreciso)
            : null;

        $lugarAlumno = $promedioClaveAlumno && isset($lugaresPorPromedio[$promedioClaveAlumno])
            ? $lugaresPorPromedio[$promedioClaveAlumno]
            : null;

        $textoLugarAlumno = $lugarAlumno
            ? $lugarAlumno . '° LUGAR'
            : 'SIN LUGAR';

        /*
        |--------------------------------------------------------------------------
        | Docente y director
        |--------------------------------------------------------------------------
        */

        $profesorId = $materias
            ->pluck('profesor_id')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        $docente = null;

        if ($profesorId) {
            $docente = Persona::query()->find($profesorId);
        }

        $director = Nivel::query()
            ->where('id', $nivel->id)
            ->with(['director'])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Logos
        |--------------------------------------------------------------------------
        */

        $logoIzquierdo = $this->imagenBase64Publica('imagenes/logo-letra.png');
        $logoDerecho = $this->imagenBase64Publica('storage/logos/' . $nivel->logo);

        $tipoBoleta = $esBachillerato ? 'BOLETA PARCIAL' : 'BOLETA DE PERIODO';

        $pdf = Pdf::loadView('pdf.boleta_calificaciones_pdf', [
            'titulo' => $tipoBoleta,
            'escuela' => $escuela,
            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'esBachillerato' => $esBachillerato,
            'esSecundaria' => $esSecundaria,
            'esPrimaria' => $esPrimaria,
            'director' => $director,
            'docente' => $docente,

            'periodo' => $periodo,
            'periodoNumero' => $periodoNumero,
            'periodoTexto' => $periodoTexto,
            'nombrePeriodo' => $nombrePeriodo,
            'periodoBasicaId' => $periodoBasicaId,
            'parcialBachilleratoId' => $parcialBachilleratoId,
            'mesBasicaId' => $mesBasicaId,
            'mesBachilleratoId' => $mesBachilleratoId,
            'tipoPeriodo' => $tipoPeriodo,

            'cicloEscolarTexto' => $cicloEscolarTexto,
            'inscripcion' => $inscripcion,
            'nombreAlumno' => $nombreAlumno,

            'filasMaterias' => $filasMaterias,
            'filasMateriasRegulares' => $filasMateriasRegulares,
            'filasMateriasExtras' => $filasMateriasExtras,

            'promedio' => $promedio,
            'promedioNumero' => $promedioNumero,
            'numeroMateriasPromediar' => $numeroMateriasPromediar,
            'hayMateriasPromediables' => $hayMateriasPromediables,
            'capturadasNumericasPromedio' => $capturadasNumericasPromedio,
            'porcentajePromedio' => $porcentajePromedio,
            'estadoPromedio' => $estadoPromedio,

            'totalMaterias' => $totalMaterias,
            'pendientes' => $pendientes,
            'especiales' => $especiales,
            'aprobadas' => $aprobadas,
            'reprobadas' => $reprobadas,
            'porcentajeCaptura' => $porcentajeCaptura,

            'lugarAlumno' => $lugarAlumno,
            'textoLugarAlumno' => $textoLugarAlumno,
            'promediosLugar' => $promediosLugar,

            'fecha_impresion' => now(),
            'logo_izquierdo' => $logoIzquierdo,
            'logo_derecho' => $logoDerecho,
        ])->setPaper('letter', 'portrait');

        $nombreArchivo = 'BOLETA_' .
            str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) .
            '_' . str_replace(' ', '_', mb_strtoupper($nombrePeriodo)) .
            '.pdf';

        return $pdf->stream($nombreArchivo);
    }


    public function diploma_calificaciones_pdf(Request $request)
    {

        $slugNivel = $request->input('slug_nivel');
        $generacionId = $request->integer('generacion_id');
        $gradoId = $request->integer('grado_id');
        $grupoId = $request->integer('grupo_id');
        $semestreId = $request->integer('semestre_id');
        $inscripcionId = $request->integer('inscripcion_id');

        if (
            blank($slugNivel) ||
            blank($generacionId) ||
            blank($gradoId) ||
            blank($grupoId) ||
            blank($inscripcionId)
        ) {
            abort(422, 'Los parámetros slug_nivel, generacion_id, grado_id, grupo_id e inscripcion_id son obligatorios.');
        }


        /*
        |--------------------------------------------------------------------------
        | Escuela
        |--------------------------------------------------------------------------
        */

        $escuela = \App\Models\Escuela::query()->first();

        if (!$escuela) {
            abort(404, 'No se encontró la escuela.');
        }

        /*
        |--------------------------------------------------------------------------
        | Nivel
        |--------------------------------------------------------------------------
        */

        $nivel = Nivel::query()
            ->where('slug', $slugNivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $esBachillerato = $this->esBachillerato($nivel);
        $esSecundaria = $this->esSecundaria($nivel);

        /*
        |--------------------------------------------------------------------------
        | Generación
        |--------------------------------------------------------------------------
        */

        $generacion = Generacion::query()
            ->where('id', $generacionId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$generacion) {
            abort(404, 'Generación no encontrada o no pertenece al nivel seleccionado.');
        }

        /*
        |--------------------------------------------------------------------------
        | Grado
        |--------------------------------------------------------------------------
        */

        $grado = Grado::query()
            ->where('id', $gradoId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$grado) {
            abort(404, 'Grado no encontrado o no pertenece al nivel seleccionado.');
        }

        /*
        |--------------------------------------------------------------------------
        | Grupo
        |--------------------------------------------------------------------------
        */

        $grupoQuery = Grupo::query()
            ->with('asignacionGrupo')
            ->where('id', $grupoId)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id);

        if ($esBachillerato) {
            if (blank($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $grupoQuery->where('semestre_id', $semestreId);
        } else {
            $grupoQuery->whereNull('semestre_id');
        }

        $grupo = $grupoQuery->first();

        if (!$grupo) {
            abort(404, 'Grupo no encontrado para el contexto seleccionado.');
        }

        /*
        |--------------------------------------------------------------------------
        | Semestre
        |--------------------------------------------------------------------------
        */

        $semestre = null;

        if ($esBachillerato) {
            $semestre = Semestre::query()
                ->where('id', $semestreId)
                ->where('grado_id', $grado->id)
                ->first();

            if (!$semestre) {
                abort(404, 'Semestre no encontrado o no pertenece al grado seleccionado.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Periodo correcto según nivel
        |--------------------------------------------------------------------------
        */

        $datosPeriodo = $this->obtenerPeriodoPdf(
            nivel: $nivel,
            generacion: $generacion,
            semestre: $semestre,
            request: $request
        );

        $periodo = $datosPeriodo['periodo'];
        $periodoId = $datosPeriodo['periodo_id'];

        $periodoBasicaId = $datosPeriodo['periodo_basica_id'];
        $parcialBachilleratoId = $datosPeriodo['parcial_bachillerato_id'];

        $periodoNumero = $datosPeriodo['periodoNumero'];
        $periodoTexto = $datosPeriodo['periodoTexto'];
        $nombrePeriodo = $datosPeriodo['nombrePeriodo'];

        $mesBasicaId = $datosPeriodo['mesBasicaId'];
        $mesBachilleratoId = $datosPeriodo['mesBachilleratoId'];

        $tipoPeriodo = $datosPeriodo['tipoPeriodo'];

        $cicloEscolarTexto = $periodo->cicloEscolar
            ? ($periodo->cicloEscolar->inicio_anio . '-' . $periodo->cicloEscolar->fin_anio)
            : '—';

        /*
        |--------------------------------------------------------------------------
        | Inscripción del alumno
        |--------------------------------------------------------------------------
        */

        $inscripcion = $this->buscarInscripcionAlumnoPdf(
            inscripcionId: $inscripcionId,
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            grupo: $grupo,
            esBachillerato: $esBachillerato
        );

        if (!$inscripcion) {
            abort(404, 'Inscripción no encontrada para el contexto seleccionado.');
        }

        $nombreAlumno = trim(
            ($inscripcion->apellido_paterno ?? '') . ' ' .
                ($inscripcion->apellido_materno ?? '') . ' ' .
                ($inscripcion->nombre ?? '')
        );

        if ($nombreAlumno === '') {
            $nombreAlumno = 'ALUMNO';
        }

        /*
        |--------------------------------------------------------------------------
        | Materias calificables
        |--------------------------------------------------------------------------
        */

        $queryMaterias = AsignacionMateria::query()
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->select([
                'asignacion_materias.*',
                'materias.materia as materia',
                'materias.clave as clave',
                'materias.slug as slug',
                'materias.calificable as calificable',
                'materias.participa_en_calificacion_oficial as participa_en_calificacion_oficial',
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('asignacion_materias.ciclo_escolar_id', $periodo->ciclo_escolar_id)
            ->where('asignacion_materias.estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA);

        if ($esBachillerato) {
            // Reconocimientos y diplomas solo consideran materias oficiales.
            ReglasMateriaBachillerato::aplicarPromediables($queryMaterias);
        } else {
            $queryMaterias->where('materias.calificable', 1);
        }

        $this->aplicarFiltroSemestreAsignacion($queryMaterias, $esBachillerato, $semestre?->id);

        $materias = $queryMaterias
            ->orderBy('asignacion_materias.orden')
            ->orderBy('materias.orden')
            ->orderBy('asignacion_materias.id')
            ->get();

        $idsMaterias = $materias->pluck('id')->values()->all();

        /*
        |--------------------------------------------------------------------------
        | Calificaciones del alumno
        |--------------------------------------------------------------------------
        */

        $queryCalificaciones = Calificacion::query()
            ->where('inscripcion_id', $inscripcion->id)
            ->where('periodo_id', $periodoId);

        if (!empty($idsMaterias)) {
            $queryCalificaciones->whereIn('asignacion_materia_id', $idsMaterias);
        }

        if (Schema::hasColumn('calificaciones', 'nivel_id')) {
            $queryCalificaciones->where('nivel_id', $nivel->id);
        }

        if (Schema::hasColumn('calificaciones', 'generacion_id')) {
            $queryCalificaciones->where('generacion_id', $generacion->id);
        }

        if (Schema::hasColumn('calificaciones', 'grado_id')) {
            $queryCalificaciones->where('grado_id', $grado->id);
        }

        if (Schema::hasColumn('calificaciones', 'grupo_id')) {
            $queryCalificaciones->where('grupo_id', $grupo->id);
        }

        if (!blank($periodo->ciclo_escolar_id) && Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
            $queryCalificaciones->where('ciclo_escolar_id', $periodo->ciclo_escolar_id);
        }

        if (Schema::hasColumn('calificaciones', 'semestre_id')) {
            if ($esBachillerato) {
                $queryCalificaciones->where('semestre_id', $semestre->id);
            } else {
                $queryCalificaciones->whereNull('semestre_id');
            }
        }

        $calificacionesMap = $queryCalificaciones
            ->get()
            ->keyBy('asignacion_materia_id');

        /*
        |--------------------------------------------------------------------------
        | Número de materias para promediar
        |--------------------------------------------------------------------------
        */

        $queryMateriaPromediar = MateriaPromediar::query()
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id);

        if (Schema::hasColumn('materia_promediar', 'grupo_id')) {
            $queryMateriaPromediar->where('grupo_id', $grupo->id);
        }

        if ($esBachillerato) {
            $queryMateriaPromediar->where('semestre_id', $semestre->id);
        } else {
            $queryMateriaPromediar->whereNull('semestre_id');
        }

        $registroPromedio = $queryMateriaPromediar->first();

        $numeroMateriasPromediar = (int) ($registroPromedio?->numero_materias ?? 0);

        if ($numeroMateriasPromediar <= 0) {
            $numeroMateriasPromediar = $materias
                ->filter(fn($materia) => (int) ($materia->calificable ?? 0) === 1
                    && (int) ($materia->extra ?? 0) === 0
                    && (int) ($materia->receso ?? 0) === 0
                    && ($esBachillerato || (int) ($materia->participa_en_calificacion_oficial ?? 1) === 1))
                ->count();
        }

        /*
        |--------------------------------------------------------------------------
        | Filas de materias y promedio del alumno
        |--------------------------------------------------------------------------
        */

        $filasMaterias = [];

        $valoresPromediables = [];
        $capturadasNumericas = 0;
        $especiales = 0;
        $reprobadas = 0;
        $aprobadas = 0;

        foreach ($materias as $materia) {
            $registro = $calificacionesMap->get($materia->id);

            $valor = $registro
                ? strtoupper(trim((string) $registro->calificacion))
                : '';

            $observacion = $registro?->observacion;

            $estado = 'Sin captura';
            $porcentaje = 0;
            $numero = $esBachillerato
                ? CalificacionBachillerato::truncarParcial($valor)
                : PromedioExcel::valoresNumericos([$valor])->first();

            if ($esBachillerato && $numero !== null) {
                $valor = (string) $numero;
            }

            if ($numero !== null) {
                $porcentaje = min(100, $numero * 10);
                $capturadasNumericas++;

                if (
                    (int) ($materia->calificable ?? 0) === 1
                    && (int) ($materia->extra ?? 0) === 0
                    && (int) ($materia->receso ?? 0) === 0
                    && ($esBachillerato || (int) ($materia->participa_en_calificacion_oficial ?? 1) === 1)
                ) {
                    // Igual que PROMEDIO de Excel: solo cuentan celdas numéricas
                    // de disciplinas que participan en la calificación oficial.
                    $valoresPromediables[] = $numero;
                }

                if ($numero < 6) {
                    $estado = 'En riesgo';
                    $reprobadas++;
                } elseif ($numero < 8) {
                    $estado = 'Regular';
                    $aprobadas++;
                } else {
                    $estado = 'Aprobado';
                    $aprobadas++;
                }
            } elseif ($valor !== '') {
                $estado = 'Especial';
                $especiales++;
            }

            $filasMaterias[] = [
                'materia' => $materia->materia ?: 'Materia',
                'clave' => $materia->clave ?: '—',
                'calificacion' => $valor !== '' ? $valor : '—',
                'estado' => $estado,
                'porcentaje' => $porcentaje,
                'observacion' => $observacion ?: '—',
                'extra' => (int) ($materia->extra ?? 0),
            ];
        }

        $promedio = '—';
        $promedioNumero = null;
        $promedioPreciso = !empty($valoresPromediables)
            ? ($esBachillerato
                ? (float) (array_sum($valoresPromediables) / $numeroMateriasPromediar)
                : PromedioExcel::calcular($valoresPromediables))
            : null;
        $porcentajePromedio = 0;
        $estadoPromedio = 'Sin datos';

        if ($promedioPreciso !== null) {
            // En bachillerato los componentes ya son enteros; el promedio agregado conserva decimales.
            $promedioNumero = PromedioExcel::truncar($promedioPreciso);
            $promedio = PromedioExcel::formatear($promedioPreciso);
            $porcentajePromedio = min(100, $promedioPreciso * 10);

            if ($promedioPreciso < 6) {
                $estadoPromedio = 'Reprobado';
            } elseif ($promedioPreciso < 8) {
                $estadoPromedio = 'Regular';
            } else {
                $estadoPromedio = 'Aprobado';
            }
        }

        $totalMaterias = count($filasMaterias);

        $pendientes = collect($filasMaterias)
            ->filter(fn($fila) => $fila['calificacion'] === '—')
            ->count();

        $porcentajeCaptura = $totalMaterias > 0
            ? round((($totalMaterias - $pendientes) / $totalMaterias) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Lugar del alumno por promedio
        |--------------------------------------------------------------------------
        | Esta parte replica la lógica de calificaciones_pdf:
        | - Se toman todos los alumnos del mismo contexto.
        | - Se consultan sus calificaciones del mismo periodo.
        | - Se promedian solo materias extra = 0.
        | - Se divide entre $numeroMateriasPromediar.
        | - Se trunca a 1 decimal.
        | - Se ordena de mayor a menor.
        | - Los empates comparten lugar.
        | - Solo se consideran los primeros 3 lugares.
        */

        $queryInscripcionesLugar = $this->queryInscripcionesPorContextoPdf(
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            grupo: $grupo,
            esBachillerato: $esBachillerato
        );

        $inscripcionesLugar = $queryInscripcionesLugar
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get()
            ->map(function ($item) {
                return [
                    'inscripcion_id' => (int) $item->id,
                    'matricula' => $item->matricula ?: '—',
                    'alumno' => trim(
                        ($item->nombre ?? '') . ' ' .
                            ($item->apellido_paterno ?? '') . ' ' .
                            ($item->apellido_materno ?? '')
                    ) ?: '—',
                    'grado' => $item->grado?->nombre ?? '—',
                    'grupo' => $this->nombreGrupo($item->grupo),
                    'semestre' => $item->semestre?->numero ?? '—',
                ];
            })
            ->values()
            ->toArray();

        $idsInscripcionesLugar = collect($inscripcionesLugar)
            ->pluck('inscripcion_id')
            ->values()
            ->all();

        $idsMateriasLugar = $materias
            ->pluck('id')
            ->values()
            ->all();

        $calificacionesLugar = [];

        if (!empty($idsInscripcionesLugar) && !empty($idsMateriasLugar)) {
            $queryCalificacionesLugar = Calificacion::query()
                ->whereIn('inscripcion_id', $idsInscripcionesLugar)
                ->whereIn('asignacion_materia_id', $idsMateriasLugar)
                ->where('periodo_id', $periodoId);

            if (Schema::hasColumn('calificaciones', 'nivel_id')) {
                $queryCalificacionesLugar->where('nivel_id', $nivel->id);
            }

            if (Schema::hasColumn('calificaciones', 'generacion_id')) {
                $queryCalificacionesLugar->where('generacion_id', $generacion->id);
            }

            if (Schema::hasColumn('calificaciones', 'grado_id')) {
                $queryCalificacionesLugar->where('grado_id', $grado->id);
            }

            if (Schema::hasColumn('calificaciones', 'grupo_id')) {
                $queryCalificacionesLugar->where('grupo_id', $grupo->id);
            }

            if (!blank($periodo->ciclo_escolar_id) && Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
                $queryCalificacionesLugar->where('ciclo_escolar_id', $periodo->ciclo_escolar_id);
            }

            if (Schema::hasColumn('calificaciones', 'semestre_id')) {
                if ($esBachillerato) {
                    $queryCalificacionesLugar->where('semestre_id', $semestre->id);
                } else {
                    $queryCalificacionesLugar->whereNull('semestre_id');
                }
            }

            $calificacionesLugar = $queryCalificacionesLugar
                ->get()
                ->mapWithKeys(function ($item) {
                    $clave = $item->inscripcion_id . '-' . $item->asignacion_materia_id;

                    return [
                        $clave => strtoupper(trim((string) $item->calificacion)),
                    ];
                })
                ->toArray();
        }

        $promediosLugar = [];
        $promediosLugarPrecisos = [];

        foreach ($inscripcionesLugar as $filaLugar) {
            $inscripcionLugarId = (int) $filaLugar['inscripcion_id'];
            $valoresLugar = [];

            foreach ($materias as $materia) {
                if (
                    (int) ($materia->calificable ?? 0) !== 1
                    || (int) ($materia->extra ?? 0) !== 0
                    || (int) ($materia->receso ?? 0) !== 0
                ) {
                    continue;
                }

                $claveLugar = $inscripcionLugarId . '-' . $materia->id;
                $valorLugar = $calificacionesLugar[$claveLugar] ?? null;
                $numeroLugar = PromedioExcel::valoresNumericos([$valorLugar])->first();

                if ($numeroLugar !== null) {
                    $valoresLugar[] = $numeroLugar;
                }
            }

            $promedioLugarPreciso = !empty($valoresLugar)
                ? ($esBachillerato
                    ? (float) (array_sum($valoresLugar) / $numeroMateriasPromediar)
                    : PromedioExcel::calcular($valoresLugar))
                : null;
            $promediosLugarPrecisos[$inscripcionLugarId] = $promedioLugarPreciso;
            $promediosLugar[$inscripcionLugarId] = PromedioExcel::formatear($promedioLugarPreciso);
        }

        $inscripcionesOrdenadasLugar = collect($inscripcionesLugar)
            ->sortByDesc(function ($filaLugar) use ($promediosLugarPrecisos) {
                return $promediosLugarPrecisos[$filaLugar['inscripcion_id']] ?? -1;
            })
            ->values();

        $promediosUnicosLugar = $inscripcionesOrdenadasLugar
            ->map(function ($filaLugar) use ($promediosLugarPrecisos) {
                return PromedioExcel::claveComparacion(
                    $promediosLugarPrecisos[$filaLugar['inscripcion_id']] ?? null
                );
            })
            ->filter()
            ->unique()
            ->values()
            ->take(3);

        $lugaresPorPromedio = [];

        foreach ($promediosUnicosLugar as $index => $promedioUnicoLugar) {
            $lugaresPorPromedio[$promedioUnicoLugar] = $index + 1;
        }

        $promedioClaveAlumno = PromedioExcel::claveComparacion($promedioPreciso);

        $lugarAlumno = $promedioClaveAlumno && isset($lugaresPorPromedio[$promedioClaveAlumno])
            ? $lugaresPorPromedio[$promedioClaveAlumno]
            : null;

        $textoLugarAlumno = $lugarAlumno
            ? $lugarAlumno . '° LUGAR'
            : 'SIN LUGAR';

        /*
        |--------------------------------------------------------------------------
        | Docente y director
        |--------------------------------------------------------------------------
        */

        $profesorId = $materias
            ->pluck('profesor_id')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        $docente = null;

        if ($profesorId) {
            $docente = Persona::query()->find($profesorId);
        }

        $director = Nivel::query()
            ->where('id', $nivel->id)
            ->with(['director'])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Logos
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Logos y datos institucionales del diploma
        |--------------------------------------------------------------------------
        | Se usan imágenes en base64 porque Dompdf suele fallar con rutas relativas.
        | Las imágenes deben estar dentro de public/.
        */

        $logoIzquierdo = $this->imagenBase64Publica('imagenes/logo-letra.png');

        $logoDerecho = $this->imagenBase64Publica('imagenes/logo-edu.png');

        $marcaAgua = $this->imagenBase64Publica('imagenes/logo-letra.png');

        /*
            |
            | Datos según el nivel
            |--------------------------------------------------------------------------
            | Se arma el encabezado de acuerdo al nivel seleccionado.
            */

        $nombreNivel = mb_strtolower($nivel->nombre ?? '');

        $secretariaTexto = 'SECRETARÍA DE EDUCACIÓN GUERRERO';

        $nombreEscuelaDiploma = match (true) {
            str_contains($nombreNivel, 'preescolar') => 'JARDÍN DE NIÑOS PART. CENTRO UNIVERSITARIO MOCTEZUMA',
            str_contains($nombreNivel, 'primaria') => 'ESC.PRIM.PART. CENTRO UNIVERSITARIO MOCTEZUMA',
            str_contains($nombreNivel, 'secundaria') => 'ESC.SEC.PART. CENTRO UNIVERSITARIO MOCTEZUMA',
            str_contains($nombreNivel, 'bachillerato') => 'BACHILLERATO GENERAL CENTRO UNIVERSITARIO MOCTEZUMA',
            default => mb_strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA'),
        };

        $cctDiploma = match (true) {
            str_contains($nombreNivel, 'preescolar') => data_get($nivel, 'cct') ?: 'C.C.T. 12PJN0226W',
            str_contains($nombreNivel, 'primaria') => data_get($nivel, 'cct') ?: 'C.C.T. 12PPR0070B',
            str_contains($nombreNivel, 'secundaria') => data_get($nivel, 'cct') ?: 'C.C.T. 12PES0105U',
            str_contains($nombreNivel, 'bachillerato') => data_get($nivel, 'cct') ?: 'C.C.T. 12PBH0071R',
            default => data_get($nivel, 'cct') ?: data_get($escuela, 'cct') ?: 'C.C.T. NO EXISTE',
        };

        /*
        |--------------------------------------------------------------------------
        | Tipo de diploma
        |--------------------------------------------------------------------------
        */

        $tipoDiploma = $esBachillerato
            ? 'RECONOCMIENTO PARCIAL'
            : 'DIPLOMA DE PERIODO';

        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */


        $data = [
            'titulo' => $tipoDiploma,
            'escuela' => $escuela,

            'secretariaTexto' => $secretariaTexto,
            'nombreEscuelaDiploma' => $nombreEscuelaDiploma,
            'cctDiploma' => $cctDiploma,

            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'generacion' => $generacion,

            'esBachillerato' => $esBachillerato,
            'esSecundaria' => $esSecundaria,
            'logoIzquierdo' => $logoIzquierdo,
            'logoDerecho' => $logoDerecho,

            'director' => $director,
            'docente' => $docente,

            'periodo' => $periodo,
            'periodoNumero' => $periodoNumero,
            'periodoTexto' => $periodoTexto,
            'nombrePeriodo' => $nombrePeriodo,
            'periodoBasicaId' => $periodoBasicaId,
            'parcialBachilleratoId' => $parcialBachilleratoId,
            'mesBasicaId' => $mesBasicaId,
            'mesBachilleratoId' => $mesBachilleratoId,
            'tipoPeriodo' => $tipoPeriodo,

            'cicloEscolarTexto' => $cicloEscolarTexto,

            'inscripcion' => $inscripcion,
            'nombreAlumno' => $nombreAlumno,
            'alumnoNombre' => mb_strtoupper($nombreAlumno),

            'filasMaterias' => $filasMaterias,
            'materias' => $filasMaterias,

            'promedio' => $promedio,
            'promedioNumero' => $promedioNumero,
            'porcentajePromedio' => $porcentajePromedio,
            'estadoPromedio' => $estadoPromedio,

            'totalMaterias' => $totalMaterias,
            'pendientes' => $pendientes,
            'especiales' => $especiales,
            'aprobadas' => $aprobadas,
            'reprobadas' => $reprobadas,
            'porcentajeCaptura' => $porcentajeCaptura,

            'numeroMateriasPromediar' => $numeroMateriasPromediar,

            'lugarAlumno' => $lugarAlumno,
            'textoLugarAlumno' => $textoLugarAlumno,
            'promediosLugar' => $promediosLugar,

            'fecha_impresion' => now(),

            'marcaAgua' => $marcaAgua,
        ];



        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        $nombreArchivo = 'RECONOCIMIENTO_' .
            str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) .
            '_' . str_replace(' ', '_', mb_strtoupper($nombrePeriodo)) .
            '.pdf';

        return Pdf::loadView('pdf.reconocimiento_pdf', $data)
            ->setPaper('letter', 'landscape')
            ->stream($nombreArchivo);
    }


    public function credenciales_pdf(Request $request)
    {
        $nivel = Nivel::query()
            ->where('slug', $request->slug_nivel)
            ->firstOrFail();

        $modoDescarga = $request->get('modo_descarga', 'grupo');

        $query = Inscripcion::query()
            ->with([
                'nivel',
                'grado',
                'generacion',
                'grupo.asignacionGrupo',
                'semestre',
            ])
            ->where('nivel_id', $nivel->id);

        if ($modoDescarga === 'nivel') {
            /*
             * No se agrega ningún filtro extra.
             * Se descargan todos los alumnos del nivel seleccionado.
             */
        }

        if ($modoDescarga === 'generacion') {
            $query->where('generacion_id', $request->generacion_id);
        }

        if ($modoDescarga === 'grado') {
            $query->where('generacion_id', $request->generacion_id)
                ->where('grado_id', $request->grado_id);
        }

        if ($modoDescarga === 'semestre') {
            $query->where('generacion_id', $request->generacion_id)
                ->where('grado_id', $request->grado_id);

            if (Schema::hasColumn('inscripciones', 'semestre_id')) {
                $query->where('semestre_id', $request->semestre_id);
            }
        }

        if ($modoDescarga === 'grupo') {
            $query->where('generacion_id', $request->generacion_id)
                ->where('grado_id', $request->grado_id)
                ->where('grupo_id', $request->grupo_id);

            if (
                ((int) $nivel->id === 4 || $nivel->slug === 'bachillerato')
                && Schema::hasColumn('inscripciones', 'semestre_id')
                && $request->filled('semestre_id')
            ) {
                $query->where('semestre_id', $request->semestre_id);
            }
        }

        if ($modoDescarga === 'individual') {
            $query->where('id', $request->alumno_id);
        }

        if ($modoDescarga === 'seleccionados') {
            $ids = collect(explode(',', (string) $request->alumnos))
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $query->whereIn('id', $ids);
        }

        $alumnos = $query
            ->orderBy('grado_id')
            ->orderBy('grupo_id')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        if ($alumnos->isEmpty()) {
            abort(404, 'No se encontraron alumnos para generar credenciales.');
        }

        $cicloEscolar = CicloEscolar::query()
            ->orderBy('id', 'desc')
            ->first();

        $nombreArchivo = 'credenciales_' . $nivel->slug . '_' . $modoDescarga . '.pdf';

        return Pdf::loadView('pdf.credenciales_pdf', [
            'alumnos' => $alumnos,
            'nivel' => $nivel,
            'cicloEscolar' => $cicloEscolar,
            'modoDescarga' => $modoDescarga,
        ])
            ->setPaper('letter', 'portrait')
            ->stream($nombreArchivo);
    }


    // LISTAS PDF
    public function lista_pdf(Request $request, string $slug_nivel)
    {
        $cicloEscolar = cicloEscolar::query()
            ->when($request->integer('ciclo_escolar_id'), fn($q) => $q->whereKey($request->integer('ciclo_escolar_id')))
            ->when(!$request->integer('ciclo_escolar_id'), fn($q) => $q->where('es_actual', true))
            ->first()
            ?? cicloEscolar::query()->orderByDesc('inicio_anio')->orderByDesc('fin_anio')->firstOrFail();

        if (!$cicloEscolar->es_actual) {
            abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede consultar listas históricas.');
        }

        $request->merge(['ciclo_escolar_id' => $cicloEscolar->id]);

        $modoDescarga = $request->input('modo_descarga', 'grupo');

        $generacionId = $request->integer('generacion_id');
        $gradoId = $request->integer('grado_id');
        $grupoId = $request->integer('grupo_id');
        $semestreId = $request->integer('semestre_id');

        $tipoDescarga = $request->input('tipo_descarga', 'grupo');
        $opcionDescarga = $request->input('opcion_descarga', 'primer_periodo');



        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $esBachillerato = $this->esBachillerato($nivel);
        $esSecundaria = $this->esSecundaria($nivel);



        $tiposPermitidos = ($esBachillerato || $esSecundaria)
            ? [
                'grupo',
                'boletas',
                'formatos',
            ]
            : [
                'evaluacion',
                'asistencia',
                'grupo',
                'boletas',
                'formatos',
            ];

        if (!in_array($tipoDescarga, $tiposPermitidos, true)) {
            abort(404, 'El tipo de descarga no es válido para este nivel.');
        }



        $parcialSeleccionado = null;
        $parcialId = null;

        if ($esBachillerato && $tipoDescarga !== 'formatos') {
            $parcialId = $this->parcialIdDesdeOpcion($opcionDescarga);

            if (!$parcialId) {
                abort(404, 'El parcial seleccionado no es válido.');
            }

            $parcialSeleccionado = Parcial::query()
                ->where('id', $parcialId)
                ->first();

            if (!$parcialSeleccionado) {
                abort(404, 'Parcial no encontrado.');
            }

            $opcionesPermitidas = Parcial::query()
                ->pluck('id')
                ->map(fn($id) => 'parcial_' . $id)
                ->toArray();
        } else {
            $opcionesPermitidas = match ($tipoDescarga) {
                'evaluacion' => [
                    'primer_periodo',
                    'segundo_periodo',
                    'tercer_periodo',
                ],

                'asistencia' => [
                    'primer_periodo',
                    'segundo_periodo',
                    'tercer_periodo',
                ],

                'grupo' => [
                    'primer_periodo',
                    'segundo_periodo',
                    'tercer_periodo',
                ],

                'boletas' => [
                    'primer_periodo',
                    'segundo_periodo',
                    'tercer_periodo',
                ],

                'formatos' => [
                    'sece',
                    'sece_interna',
                    'personalizadores',
                    'etiquetas',
                ],

                default => [],
            };
        }

        if (!in_array($opcionDescarga, $opcionesPermitidas, true)) {
            abort(404, 'La opción de descarga no es válida.');
        }




        if (!in_array($modoDescarga, ['grupo', 'nivel'], true)) {
            abort(422, 'El modo de descarga no es válido.');
        }



        if ($modoDescarga === 'grupo') {
            if (blank($generacionId) || blank($gradoId) || blank($grupoId)) {
                abort(422, 'Los parámetros generacion_id, grado_id y grupo_id son obligatorios.');
            }

            if ($esBachillerato && blank($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $grupo = $this->obtenerGrupoListaPdf(
                nivel: $nivel,
                generacionId: $generacionId,
                gradoId: $gradoId,
                grupoId: $grupoId,
                semestreId: $semestreId,
                esBachillerato: $esBachillerato
            );

            $contexto = $this->construirContextoListaPdf(
                request: $request,
                nivel: $nivel,
                grupo: $grupo,
                tipoDescarga: $tipoDescarga,
                opcionDescarga: $opcionDescarga,
                parcialSeleccionado: $parcialSeleccionado,
                parcialId: $parcialId
            );

            return Pdf::loadView($contexto['vistaPdf'], $contexto['data'])
                ->setPaper('letter', $contexto['orientacionPdf'])
                ->stream($contexto['nombreArchivo']);
        }



        $grupos = $this->obtenerGruposListaNivelPdf(
            nivel: $nivel,
            generacionId: $generacionId,
            esBachillerato: $esBachillerato,
            cicloEscolarId: (int) $cicloEscolar->id,
        );

        if ($grupos->isEmpty()) {
            abort(404, 'No se encontraron grupos para generar las listas del nivel.');
        }

        $contextos = [];

        foreach ($grupos as $grupo) {
            $contextos[] = $this->construirContextoListaPdf(
                request: $request,
                nivel: $nivel,
                grupo: $grupo,
                tipoDescarga: $tipoDescarga,
                opcionDescarga: $opcionDescarga,
                parcialSeleccionado: $parcialSeleccionado,
                parcialId: $parcialId
            );
        }

        $primerContexto = collect($contextos)->first();

        if (!$primerContexto) {
            abort(404, 'No se pudo preparar la información de las listas.');
        }

        $nombreArchivo = 'listas-' . $nivel->slug . '-todas-las-generaciones'
            . '-' . Str::slug($tipoDescarga, '-')
            . '-' . Str::slug($opcionDescarga, '-')
            . '.pdf';

        $nombreArchivo .= '-' . Str::slug($tipoDescarga, '-') . '-' . Str::slug($opcionDescarga, '-') . '.pdf';

        return Pdf::loadView('pdf.listas_nivel_pdf', [
            'nivel' => $nivel,
            'contextos' => $contextos,
            'tipo_descarga' => $tipoDescarga,
            'opcion_descarga' => $opcionDescarga,
            'modoDescarga' => $modoDescarga,
        ])
            ->setPaper('letter', $primerContexto['orientacionPdf'])
            ->stream($nombreArchivo);
    }


    private function obtenerGrupoListaPdf(
        Nivel $nivel,
        int $generacionId,
        int $gradoId,
        int $grupoId,
        ?int $semestreId,
        bool $esBachillerato
    ): Grupo {
        $generacion = Generacion::query()
            ->where('id', $generacionId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$generacion) {
            abort(404, 'Generación no encontrada o no pertenece al nivel seleccionado.');
        }

        $grado = Grado::query()
            ->where('id', $gradoId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$grado) {
            abort(404, 'Grado no encontrado o no pertenece al nivel seleccionado.');
        }

        $grupoQuery = Grupo::query()
            ->with([
                'asignacionGrupo',
                'generacion',
                'grado',
                'semestre',
            ])
            ->where('id', $grupoId)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id);

        if ($esBachillerato) {
            $grupoQuery->where('semestre_id', $semestreId);
        } else {
            $grupoQuery->whereNull('semestre_id');
        }

        $grupo = $grupoQuery->first();

        if (!$grupo) {
            abort(404, 'Grupo no encontrado para el contexto seleccionado.');
        }

        return $grupo;
    }

    private function obtenerGruposListaNivelPdf(
        Nivel $nivel,
        ?int $generacionId,
        bool $esBachillerato,
        ?int $cicloEscolarId = null,
    ) {
        $consulta = Grupo::query()
            ->with([
                'asignacionGrupo',
                'generacion',
                'grado',
                'semestre',
            ])
            ->where('nivel_id', $nivel->id);

        /*
         * En modo nivel NO se filtra por generación.
         * Se deben traer todos los grupos del nivel seleccionado,
         * independientemente de la generación.
         */

        if (!$esBachillerato && Schema::hasColumn('grupos', 'semestre_id')) {
            $consulta->whereNull('semestre_id');
        }

        $grupoIds = \App\Models\Inscripcion::query()
            ->where('nivel_id', $nivel->id)
            ->whereNotNull('grupo_id')
            ->pluck('grupo_id')
            ->filter()
            ->unique();

        if ($grupoIds->isNotEmpty()) {
            $consulta->whereIn('id', $grupoIds);
        }

        return $consulta
            ->orderBy('generacion_id')
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->orderBy('asignacion_grupo_id')
            ->get();
    }

    private function construirContextoListaPdf(
        Request $request,
        Nivel $nivel,
        Grupo $grupo,
        string $tipoDescarga,
        string $opcionDescarga,
        $parcialSeleccionado = null,
        ?int $parcialId = null
    ): array {
        $esBachillerato = $this->esBachillerato($nivel);
        $esSecundaria = $this->esSecundaria($nivel);
        $esPreescolar = (int) $nivel->id === 1 || $nivel->slug === 'preescolar';
        $esPrimaria = (int) $nivel->id === 2 || $nivel->slug === 'primaria';
        $cicloEscolarId = $request->integer('ciclo_escolar_id');

        /*
    |--------------------------------------------------------------------------
    | Datos principales
    |--------------------------------------------------------------------------
    */

        $generacion = $grupo->generacion;

        if (!$generacion) {
            $generacion = Generacion::query()
                ->where('id', $grupo->generacion_id)
                ->where('nivel_id', $nivel->id)
                ->first();
        }

        if (!$generacion) {
            abort(404, 'Generación no encontrada para el grupo seleccionado.');
        }

        $grado = $grupo->grado;

        if (!$grado) {
            $grado = Grado::query()
                ->where('id', $grupo->grado_id)
                ->where('nivel_id', $nivel->id)
                ->first();
        }

        if (!$grado) {
            abort(404, 'Grado no encontrado para el grupo seleccionado.');
        }

        $semestre = null;

        if ($esBachillerato) {
            $semestre = $grupo->semestre;

            if (!$semestre) {
                $semestre = Semestre::query()
                    ->where('id', $grupo->semestre_id)
                    ->where('grado_id', $grado->id)
                    ->first();
            }

            if (!$semestre) {
                abort(404, 'Semestre no encontrado para el grupo seleccionado.');
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Alumnos activos
    |--------------------------------------------------------------------------
    */

        // La lista se resuelve después de conocer el periodo y su fecha de corte.
        $alumnos = collect();

        /*
    |--------------------------------------------------------------------------
    | Materias calificables
    |--------------------------------------------------------------------------
    */

        $materiasQuery = AsignacionMateria::query()
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->select([
                'asignacion_materias.*',
                'materias.materia as materia',
                'materias.clave as clave',
                'materias.slug as slug',
                'materias.calificable as calificable',
                'materias.participa_en_calificacion_oficial as participa_en_calificacion_oficial',
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('asignacion_materias.ciclo_escolar_id', $cicloEscolarId)
            ->where('asignacion_materias.estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
            ->where('materias.calificable', 1);

        $this->aplicarFiltroSemestreAsignacion($materiasQuery, $esBachillerato, $semestre?->id);

        $materias = $materiasQuery
            ->orderBy('asignacion_materias.orden')
            ->orderBy('materias.orden')
            ->orderBy('asignacion_materias.id')
            ->get();

        $materiasPromediables = collect();
        $materiasCualitativas = collect();

        if ($tipoDescarga === 'evaluacion' && $esPrimaria) {
            /*
             * Solo estas materias especiales usarán AC / ED / RA.
             */
            $slugsMateriasCualitativas = [
                'calculo-mental',
                'caligrafia',
                'lectura',
            ];

            $materiasCualitativas = $materias
                ->filter(function ($materia) use ($slugsMateriasCualitativas) {
                    return in_array($materia->slug, $slugsMateriasCualitativas, true)
                        && (int) ($materia->calificable ?? 0) === 1
                        && (int) ($materia->extra ?? 0) === 1;
                })
                ->values();

            $materiasPromediables = $materias
                ->filter(function ($materia) use ($slugsMateriasCualitativas) {
                    return !in_array($materia->slug, $slugsMateriasCualitativas, true)
                        && (int) ($materia->calificable ?? 0) === 1
                        && (int) ($materia->participa_en_calificacion_oficial ?? 1) === 1;
                })
                ->values();
        }

        /*
    |--------------------------------------------------------------------------
    | Docente principal
    |--------------------------------------------------------------------------
    */

        $docente = null;

        if ($esPreescolar || $esPrimaria) {
            $personalAsignado = PersonaNivel::query()
                ->with([
                    'persona:id,titulo,nombre,apellido_paterno,apellido_materno,genero',
                    'nivel:id,nombre',
                    'detalles' => function ($query) {
                        $query->with([
                            'grado:id,nombre',
                            'grupo' => function ($query) {
                                $query->select('id', 'asignacion_grupo_id')
                                    ->with('asignacionGrupo:id,nombre');
                            },
                        ]);
                    },
                ])
                ->where('nivel_id', $nivel->id)
                ->whereHas('detalles', function ($query) use ($grado, $grupo) {
                    $query->where('grado_id', $grado->id)
                        ->where('grupo_id', $grupo->id);
                })
                ->first();

            $docente = $personalAsignado?->persona;
        } else {
            $profesorId = $materias
                ->pluck('profesor_id')
                ->filter()
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first();

            if ($profesorId) {
                $docente = Persona::query()
                    ->where('id', $profesorId)
                    ->first();
            }
        }

        $nombreDocente = $this->nombrePersona($docente);

        if ($nombreDocente === '') {
            $nombreDocente = 'DOCENTE';
        }

        /*
    |--------------------------------------------------------------------------
    | Escuela, ciclo y director
    |--------------------------------------------------------------------------
    */

        $escuela = DB::table('escuela')->first();

        if (!$escuela) {
            abort(404, 'No se encontró la escuela.');
        }

        $cicloEscolar = cicloEscolar::query()
            ->whereKey($cicloEscolarId)
            ->first();

        if (!$cicloEscolar) {
            abort(404, 'No se encontró el ciclo escolar.');
        }

        $director = Nivel::query()
            ->where('id', $nivel->id)
            ->with(['director'])
            ->first();

        /*
    |--------------------------------------------------------------------------
    | Periodo / parcial
    |--------------------------------------------------------------------------
    */

        $periodo = null;
        $periodoNumero = null;
        $periodoTexto = null;
        $nombrePeriodo = null;

        $periodoBasicaId = null;
        $parcialBachilleratoId = null;

        $mesBasicaId = null;
        $mesBachilleratoId = null;
        $mesAsistencia = null;

        $tipoPeriodo = null;

        if ($tipoDescarga !== 'formatos') {
            $requestPeriodo = clone $request;

            /*
             * En modo nivel, cada grupo puede tener generación o semestre diferente.
             * Por eso se prepara el request con los datos reales del grupo actual.
             */
            $requestPeriodo->merge([
                'generacion_id' => $generacion->id,
                'grado_id' => $grado->id,
                'grupo_id' => $grupo->id,
                'semestre_id' => $semestre?->id,
                'opcion_descarga' => $opcionDescarga,
            ]);

            $datosPeriodo = $this->obtenerPeriodoPdf(
                nivel: $nivel,
                generacion: $generacion,
                semestre: $semestre,
                request: $requestPeriodo
            );

            $periodo = $datosPeriodo['periodo'];
            $periodoNumero = $datosPeriodo['periodoNumero'];
            $periodoTexto = $datosPeriodo['periodoTexto'];
            $nombrePeriodo = $datosPeriodo['nombrePeriodo'];

            $periodoBasicaId = $datosPeriodo['periodo_basica_id'];
            $parcialBachilleratoId = $datosPeriodo['parcial_bachillerato_id'];

            $mesBasicaId = $datosPeriodo['mesBasicaId'];
            $mesBachilleratoId = $datosPeriodo['mesBachilleratoId'];
            $mesAsistencia = $datosPeriodo['mesAsistencia'];

            $tipoPeriodo = $datosPeriodo['tipoPeriodo'];
        }

        $fechaCorte = $periodo?->fecha_fin
            ?? $periodo?->fecha_inicio
            ?? $request->input('fecha_fin')
            ?? ($cicloEscolar->es_actual
                ? now()->toDateString()
                : sprintf('%04d-07-31', (int) $cicloEscolar->fin_anio));

        $alumnos = app(ListaAcademicaService::class)->alumnosPorContexto(
            cicloEscolarId: (int) $cicloEscolarId,
            grupoIds: [(int) $grupo->id],
            fechaCorte: $fechaCorte,
            nivelId: (int) $nivel->id,
            gradoId: (int) $grado->id,
            generacionId: (int) $generacion->id,
            semestreId: $esBachillerato ? (int) $semestre?->id : null,
        );

        /*
    |--------------------------------------------------------------------------
    | Motivo para lista de grupo
    |--------------------------------------------------------------------------
    */

        $mostrarMotivo = $tipoDescarga === 'grupo' && $request->boolean('mostrar_motivo');

        /*
    |--------------------------------------------------------------------------
    | Imágenes
    |--------------------------------------------------------------------------
    */

        $logoIzquierdo = $this->imagenBase64Publica(!empty($nivel->logo) ? 'storage/logos/' . $nivel->logo : 'imagenes/logo-letra.png');
        $logoDerecho = $this->imagenBase64Publica('imagenes/logo-letra.png');
        $marcaAgua = $this->imagenBase64Publica('imagenes/logo-letra.png');
        $imagenPersonalizador = $this->imagenBase64Publica('imagenes/personalizador.jpg');

        /*
    |--------------------------------------------------------------------------
    | Datos enviados a vistas
    |--------------------------------------------------------------------------
    */

        $data = [
            'escuela' => $escuela,
            'nivel' => $nivel,
            'generacion' => $generacion,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,

            'alumnos' => $alumnos,
            'materias' => $materias,

            'docente' => $docente,
            'nombreDocente' => $nombreDocente,
            'director' => $director,

            'periodo' => $periodo,
            'periodoNumero' => $periodoNumero,
            'periodoTexto' => $periodoTexto,
            'nombrePeriodo' => $nombrePeriodo,

            'periodoBasicaId' => $periodoBasicaId,
            'parcialBachilleratoId' => $parcialBachilleratoId,

            'mesBasicaId' => $mesBasicaId,
            'mesBachilleratoId' => $mesBachilleratoId,
            'mesAsistencia' => $mesAsistencia,

            'tipoPeriodo' => $tipoPeriodo,
            'cicloEscolar' => $cicloEscolar,

            'parcialSeleccionado' => $parcialSeleccionado,
            'parcialId' => $parcialId,

            'esBachillerato' => $esBachillerato,
            'esSecundaria' => $esSecundaria,
            'esPrimaria' => $esPrimaria,

            'materiasPromediables' => $materiasPromediables,
            'materiasCualitativas' => $materiasCualitativas,

            'logoIzquierdo' => $logoIzquierdo,
            'logoDerecho' => $logoDerecho,
            'marcaAgua' => $marcaAgua,
            'imagenPersonalizador' => $imagenPersonalizador,

            'turno' => $request->input('turno', 'Matutino'),
            'fechaInicio' => $request->input('fecha_inicio'),
            'fechaFin' => $request->input('fecha_fin'),

            'tipo_descarga' => $tipoDescarga,
            'opcion_descarga' => $opcionDescarga,

            'mostrarMotivo' => $mostrarMotivo,
        ];

        /*
    |--------------------------------------------------------------------------
    | Vista PDF
    |--------------------------------------------------------------------------
    */

        $vistaPdf = match ($tipoDescarga) {
            'evaluacion' => 'pdf.lista_evaluacion',
            'asistencia' => 'pdf.lista_asistencia',
            'grupo' => 'pdf.lista_grupo',
            'boletas' => 'pdf.lista_boletas',

            'formatos' => match ($opcionDescarga) {
                'sece' => 'pdf.lista.sece',
                'sece_interna' => 'pdf.sece_interna',
                'personalizadores' => 'pdf.personalizadores',
                'etiquetas' => 'pdf.etiquetas_pdf',
                default => abort(404, 'El formato seleccionado no existe.'),
            },

            default => abort(404, 'El tipo de descarga seleccionado no existe.'),
        };

        /*
    |--------------------------------------------------------------------------
    | Nombre del archivo
    |--------------------------------------------------------------------------
    */

        $nombreTipo = match ($tipoDescarga) {
            'evaluacion' => 'lista-evaluacion',
            'asistencia' => 'lista-asistencia',
            'grupo' => 'lista-grupo',
            'boletas' => $esBachillerato ? 'lista-boletas-parcial' : 'lista-boletas-periodo',

            'formatos' => match ($opcionDescarga) {
                'sece' => 'formato-sece',
                'sece_interna' => 'formato-sece-interna',
                'personalizadores' => 'personalizadores',
                'etiquetas' => 'etiquetas',
                default => 'formato',
            },

            default => 'lista',
        };

        $nombreArchivo = $nombreTipo
            . '-' . $nivel->slug
            . '-grado-' . Str::slug((string) ($grado->nombre ?? 'grado'), '-')
            . ($esBachillerato && $semestre ? '-semestre-' . $semestre->numero : '')
            . '-grupo-' . Str::slug($this->nombreGrupo($grupo), '-')
            . ($periodoNumero ? '-' . ($esBachillerato ? 'parcial-' : 'periodo-') . $periodoNumero : '')
            . '.pdf';

        /*
    |--------------------------------------------------------------------------
    | Orientación
    |--------------------------------------------------------------------------
    */

        $orientacionPdf = match (true) {
            $tipoDescarga === 'grupo' => 'portrait',
            $tipoDescarga === 'evaluacion' => 'portrait',
            $tipoDescarga === 'asistencia' => 'landscape',
            $tipoDescarga === 'boletas' => 'portrait',

            $tipoDescarga === 'formatos' && $opcionDescarga === 'sece' => 'portrait',
            $tipoDescarga === 'formatos' && $opcionDescarga === 'sece_interna' => 'portrait',
            $tipoDescarga === 'formatos' && $opcionDescarga === 'personalizadores' => 'portrait',
            $tipoDescarga === 'formatos' && $opcionDescarga === 'etiquetas' => 'portrait',

            default => 'portrait',
        };

        return [
            'data' => $data,
            'vistaPdf' => $vistaPdf,
            'nombreArchivo' => $nombreArchivo,
            'orientacionPdf' => $orientacionPdf,
        ];
    }



    private function obtenerGrupoIdsEquivalentesPdf(Grupo $grupo, Nivel $nivel, Generacion $generacion, Grado $grado, bool $esBachillerato): array
    {
        if (!$esBachillerato) {
            return [(int) $grupo->id];
        }

        /*
         * En bachillerato, el mismo grupo puede existir en varios semestres
         * con ids diferentes. Para alumnos se toma el grupo lógico.
         */
        if (blank($grupo->asignacion_grupo_id)) {
            return [(int) $grupo->id];
        }

        return Grupo::query()
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('asignacion_grupo_id', $grupo->asignacion_grupo_id)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();
    }

    private function aplicarFiltroActivoInscripcionPdf($query): void
    {
        if (!Schema::hasColumn('inscripciones', 'activo')) {
            return;
        }

        $query->where(function ($q) {
            $q->where('activo', 1)
                ->orWhere('activo', true)
                ->orWhere('activo', '1')
                ->orWhere('activo', 'true');
        });
    }

    private function queryInscripcionesPorContextoPdf(
        Nivel $nivel,
        Generacion $generacion,
        Grado $grado,
        Grupo $grupo,
        bool $esBachillerato
    ) {
        $grupoIds = $this->obtenerGrupoIdsEquivalentesPdf(
            grupo: $grupo,
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            esBachillerato: $esBachillerato
        );

        $query = Inscripcion::query()
            ->with([
                'grado:id,nombre',
                'grupo' => function ($query) {
                    $query->select('id', 'asignacion_grupo_id')
                        ->with('asignacionGrupo:id,nombre');
                },
                'semestre:id,numero',
            ])
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id);

        /*
         * No se filtra por semestre_id en alumnos.
         * El semestre seleccionado solo se usa para materias, parciales y calificaciones.
         */
        if (!empty($grupoIds)) {
            $query->whereIn('grupo_id', $grupoIds);
        }

        $this->aplicarFiltroActivoInscripcionPdf($query);

        return $query;
    }

    private function buscarInscripcionAlumnoPdf(
        int $inscripcionId,
        Nivel $nivel,
        Generacion $generacion,
        Grado $grado,
        Grupo $grupo,
        bool $esBachillerato
    ): ?Inscripcion {
        return $this->queryInscripcionesPorContextoPdf(
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            grupo: $grupo,
            esBachillerato: $esBachillerato
        )
            ->where('id', $inscripcionId)
            ->first();
    }

    private function textoParcialBachillerato(?Parcial $parcial): string
    {
        if (!$parcial) {
            return 'PARCIAL';
        }

        if (!empty($parcial->parcial)) {
            return mb_strtoupper($parcial->parcial);
        }

        if (!empty($parcial->descripcion)) {
            return mb_strtoupper($parcial->descripcion);
        }

        return 'PARCIAL ' . $parcial->id;
    }



    private function esBachillerato($nivel): bool
    {
        return (int) $nivel->id === 4 || $nivel->slug === 'bachillerato';
    }
    private function esSecundaria($nivel): bool
    {
        return (int) $nivel->id === 3 || $nivel->slug === 'secundaria';
    }

    private function validarDiplomaAnual(
        Nivel $nivel,
        Grado $grado,
        ?Semestre $semestre,
        ?array $filaAcademica,
        ?float $promedioNumero,
        array $promediosPeriodosPrecisos,
        array $numerosPeriodos,
    ): void {
        $gradoTerminalId = Grado::query()
            ->where('nivel_id', $nivel->id)
            ->orderByDesc('orden')
            ->orderByDesc('id')
            ->value('id');

        abort_unless(
            $gradoTerminalId !== null && (int) $grado->id === (int) $gradoTerminalId,
            403,
            'El diploma anual solo está disponible para el último grado del nivel.'
        );

        if ($this->esBachillerato($nivel)) {
            abort_unless(
                $semestre !== null && (int) $semestre->numero === 6,
                403,
                'El diploma de bachillerato solo está disponible para sexto semestre.'
            );

            abort_unless(
                is_array($filaAcademica) && ($filaAcademica['completo'] ?? false) === true,
                422,
                'El alumno aún tiene materias sin ambos parciales numéricos.'
            );

            abort_unless(
                ($filaAcademica['todas_materias_acreditadas'] ?? false) === true
                    && empty($filaAcademica['materias_reprobadas'] ?? []),
                403,
                'El alumno tiene materias no acreditadas y no puede generar el diploma.'
            );

            abort_unless(
                ($filaAcademica['diploma_disponible'] ?? false) === true
                    && $promedioNumero !== null
                    && $promedioNumero >= 6.0,
                403,
                'El alumno no cumple las condiciones académicas para generar el diploma.'
            );

            return;
        }

        abort_unless(
            in_array($nivel->slug, ['primaria', 'secundaria'], true),
            403,
            'El diploma anual no está habilitado para este nivel desde este módulo.'
        );

        abort_unless(
            is_array($filaAcademica) && ($filaAcademica['completo'] ?? false) === true,
            422,
            'El alumno aún tiene evaluaciones pendientes.'
        );

        abort_unless(
            ($filaAcademica['promocion_confirmada'] ?? null) === true,
            403,
            'Debes confirmar la promoción del alumno antes de generar el diploma.'
        );
    }

    private function numeroPeriodoEvaluacion(string $opcion): int
    {
        return match ($opcion) {
            'primer_periodo' => 1,
            'segundo_periodo' => 2,
            'tercer_periodo' => 3,
            default => 1,
        };
    }

    private function textoPeriodoEvaluacion(string $opcion): string
    {
        return match ($opcion) {
            'primer_periodo' => 'PRIMER PERIODO',
            'segundo_periodo' => 'SEGUNDO PERIODO',
            'tercer_periodo' => 'TERCER PERIODO',
            default => 'PRIMER PERIODO',
        };
    }

    private function numeroPeriodoAsistencia(string $opcion): int
    {

        return match ($opcion) {
            'primer_periodo' => 1,
            'segundo_periodo' => 2,
            'tercer_periodo' => 3,
            default => 1,
        };
    }

    private function textoPeriodoAsistencia(string $opcion): string
    {
        return match ($opcion) {
            'primer_periodo' => 'PRIMER PERIODO',
            'segundo_periodo' => 'SEGUNDO PERIODO',
            'tercer_periodo' => 'TERCER PERIODO',
            default => 'PRIMER PERIODO',
        };
    }

    private function mesPeriodoAsistencia(string $opcion): string
    {
        return match ($opcion) {
            'primer_periodo' => 'Agosto',
            'segundo_periodo' => 'Noviembre',
            'tercer_periodo' => 'Febrero',
            default => 'Agosto',
        };
    }

    private function aplicarFiltroSemestreAsignacion($query, bool $esBachillerato, ?int $semestreId): void
    {
        if (Schema::hasColumn('materias', 'semestre_id')) {
            if ($esBachillerato) {
                $query->where('materias.semestre_id', $semestreId);
            } else {
                $query->whereNull('materias.semestre_id');
            }

            return;
        }

        if (Schema::hasColumn('asignacion_materias', 'semestre_id')) {
            if ($esBachillerato) {
                $query->where('asignacion_materias.semestre_id', $semestreId);
            } else {
                $query->whereNull('asignacion_materias.semestre_id');
            }

            return;
        }

        if (Schema::hasColumn('asignacion_materias', 'semestre')) {
            if ($esBachillerato) {
                $query->where('asignacion_materias.semestre', $semestreId);
            } else {
                $query->whereNull('asignacion_materias.semestre');
            }
        }
    }



    private function nombrePersona($persona): string
    {
        if (!$persona) {
            return '';
        }

        return trim(
            ($persona->titulo ?? '') . ' ' .
                ($persona->nombre ?? '') . ' ' .
                ($persona->apellido_paterno ?? '') . ' ' .
                ($persona->apellido_materno ?? '')
        );
    }

    private function obtenerPeriodoPdf(
        Nivel $nivel,
        ?Generacion $generacion,
        ?Semestre $semestre,
        Request $request
    ): array {
        $esBachillerato = $this->esBachillerato($nivel);

        $periodoId = $request->integer('periodo_id') ?: null;
        $periodoBasicaId = $request->integer('periodo_basica_id') ?: null;
        $parcialBachilleratoId = $request->integer('parcial_bachillerato_id') ?: null;
        $opcionDescarga = $request->input('opcion_descarga');
        $cicloEscolarId = $request->integer('ciclo_escolar_id') ?: null;

        /*
         * Si llega periodo_id directo, se respeta.
         * Pero se valida que sea del tipo correcto.
         */
        if ($periodoId) {
            $periodo = Periodos::query()
                ->with([
                    'cicloEscolar',
                    'periodoBasica',
                    'parcialBachillerato',
                    'mesesBasica',
                    'mesesBachillerato',
                ])
                ->where('id', $periodoId)
                ->where('nivel_id', $nivel->id)
                ->when($cicloEscolarId, fn($q) => $q->where('ciclo_escolar_id', $cicloEscolarId))
                ->first();

            if (!$periodo) {
                abort(404, 'Periodo no encontrado para el nivel seleccionado.');
            }

            if ($esBachillerato && blank($periodo->parcial_bachillerato_id)) {
                abort(422, 'El periodo seleccionado no corresponde a bachillerato.');
            }

            if (!$esBachillerato && blank($periodo->periodo_basica_id)) {
                abort(422, 'El periodo seleccionado no corresponde a básica.');
            }

            return $this->formatearPeriodoPdf($periodo, $esBachillerato);
        }

        /*
         * Bachillerato usa parcial_bachillerato_id.
         */
        if ($esBachillerato) {
            if (!$generacion) {
                abort(422, 'La generación es obligatoria para bachillerato.');
            }

            if (!$semestre) {
                abort(422, 'El semestre es obligatorio para bachillerato.');
            }

            if (!$parcialBachilleratoId && $opcionDescarga) {
                $parcialBachilleratoId = $this->parcialIdDesdeOpcion($opcionDescarga);
            }

            if (!$parcialBachilleratoId) {
                abort(422, 'El parcial es obligatorio para bachillerato.');
            }

            $periodo = Periodos::query()
                ->with([
                    'cicloEscolar',
                    'parcialBachillerato',
                    'mesesBachillerato',
                ])
                ->where('nivel_id', $nivel->id)
                ->when($cicloEscolarId, fn($q) => $q->where('ciclo_escolar_id', $cicloEscolarId))
                ->where('generacion_id', $generacion->id)
                ->where('semestre_id', $semestre->id)
                ->where('parcial_bachillerato_id', $parcialBachilleratoId)
                ->whereNull('periodo_basica_id')
                ->first();

            if (!$periodo) {
                abort(404, 'No se encontró el parcial de bachillerato seleccionado.');
            }

            return $this->formatearPeriodoPdf($periodo, true);
        }

        /*
         * Preescolar, primaria y secundaria usan periodo_basica_id.
         */
        if (!$periodoBasicaId && $opcionDescarga) {
            $periodoBasicaId = $this->periodoBasicaIdDesdeOpcion($opcionDescarga);
        }

        if (!$periodoBasicaId) {
            abort(422, 'El periodo de básica es obligatorio.');
        }

        $periodo = Periodos::query()
            ->with([
                'cicloEscolar',
                'periodoBasica',
                'mesesBasica',
            ])
            ->where('nivel_id', $nivel->id)
            ->when($cicloEscolarId, fn($q) => $q->where('ciclo_escolar_id', $cicloEscolarId))
            ->where('periodo_basica_id', $periodoBasicaId)
            ->whereNull('parcial_bachillerato_id')
            ->first();

        if (!$periodo) {
            abort(404, 'No se encontró el periodo de básica seleccionado.');
        }

        return $this->formatearPeriodoPdf($periodo, false);
    }

    private function formatearPeriodoPdf(Periodos $periodo, bool $esBachillerato): array
    {
        if ($esBachillerato) {
            $numero = (int) $periodo->parcial_bachillerato_id;

            $texto = $periodo?->parcialBachillerato?->parcial
                ?? $periodo?->parcialBachillerato?->descripcion
                ?? 'PARCIAL ' . $numero;

            return [
                'periodo' => $periodo,
                'periodo_id' => $periodo->id,

                'periodo_basica_id' => null,
                'parcial_bachillerato_id' => $numero,

                'periodoNumero' => $numero,
                'periodoTexto' => mb_strtoupper($texto),
                'nombrePeriodo' => mb_strtoupper($texto),

                'mesBasicaId' => null,
                'mesBachilleratoId' => $periodo->mes_bachillerato_id,

                'tipoPeriodo' => 'parcial_bachillerato',
                'mesAsistencia' => null,
            ];
        }

        $numero = (int) $periodo->periodo_basica_id;

        $texto = $periodo?->periodoBasica?->periodo
            ?? $periodo?->periodoBasica?->descripcion
            ?? 'PERIODO ' . $numero;

        return [
            'periodo' => $periodo,
            'periodo_id' => $periodo->id,

            'periodo_basica_id' => $numero,
            'parcial_bachillerato_id' => null,

            'periodoNumero' => $numero,
            'periodoTexto' => mb_strtoupper($texto),
            'nombrePeriodo' => mb_strtoupper($texto),

            'mesBasicaId' => $periodo->mes_basica_id,
            'mesBachilleratoId' => null,

            'tipoPeriodo' => 'periodo_basica',
            'mesAsistencia' => $this->mesPeriodoAsistenciaPorNumero($numero),
        ];
    }

    private function periodoBasicaIdDesdeOpcion(?string $opcion): ?int
    {
        return match ($opcion) {
            'primer_periodo' => 1,
            'segundo_periodo' => 2,
            'tercer_periodo' => 3,
            default => null,
        };
    }

    private function parcialIdDesdeOpcion(?string $opcion): ?int
    {
        if (!$opcion) {
            return null;
        }

        if (str_starts_with($opcion, 'parcial_')) {
            $id = (int) str_replace('parcial_', '', $opcion);

            return $id > 0 ? $id : null;
        }

        return match ($opcion) {
            'primer_periodo' => 1,
            'segundo_periodo' => 2,
            'tercer_periodo' => 3,
            default => null,
        };
    }

    private function mesPeriodoAsistenciaPorNumero(?int $numeroPeriodo): ?string
    {
        return match ((int) $numeroPeriodo) {
            1 => 'Agosto',
            2 => 'Noviembre',
            3 => 'Febrero',
            default => null,
        };
    }

    private function nombrePeriodoParaPdf($periodo, bool $esBachillerato): string
    {
        if ($esBachillerato) {
            return mb_strtoupper(
                $periodo?->parcialBachillerato?->parcial
                    ?? $periodo?->parcialBachillerato?->descripcion
                    ?? 'PARCIAL'
            );
        }

        return mb_strtoupper(
            $periodo?->periodoBasica?->periodo
                ?? $periodo?->periodoBasica?->descripcion
                ?? 'PERIODO'
        );
    }
    private function nombreGrupo($grupo): string
    {
        if (!$grupo) {
            return 'GRUPO';
        }

        return $grupo->asignacionGrupo?->nombre ?? 'GRUPO';
    }

    private function nombreGrupoArchivo($grupo): string
    {
        return Str::slug($this->nombreGrupo($grupo), '_');
    }

    private function imagenBase64Publica(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        $path = public_path($ruta);

        if (!file_exists($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }
}
