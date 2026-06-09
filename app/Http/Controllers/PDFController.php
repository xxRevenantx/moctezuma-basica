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
use Barryvdh\DomPDF\Facade\Pdf;
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

        abort_if($esBachillerato && $tipo !== 'semestral', 404);
        abort_if(!$esBachillerato && !in_array($tipo, ['boleta', 'reconocimiento'], true), 404);

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
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('materias.calificable', 1);

        $this->aplicarFiltroSemestreAsignacion($queryMaterias, $esBachillerato, $semestre?->id);

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
                ->filter(function ($materia) {
                    return (int) ($materia->extra ?? 0) === 0
                        && (int) ($materia->receso ?? 0) === 0;
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
            return floor(($valor + 0.000000001) * 10) / 10;
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

                        if ((int) ($materia->extra ?? 0) === 0 && (int) ($materia->receso ?? 0) === 0) {
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
                $promedioMateriaNumero = $truncarPromedio($sumaMateria / $capturadasMateria);
                $promedioMateria = number_format($promedioMateriaNumero, 1, '.', '');

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
        |--------------------------------------------------------------------------
        | Promedios finales
        |--------------------------------------------------------------------------
        */

        $promediosPeriodos = [];
        $sumaPromediosPeriodos = 0;
        $periodosCapturados = 0;

        foreach ($numerosPeriodos as $numeroPeriodo) {
            $promedioPeriodo = null;

            if ($capturadasPorPeriodo[$numeroPeriodo] > 0) {
                /*
                 * promedio-numerico-pro:
                 * Se divide únicamente entre las calificaciones numéricas encontradas.
                 * AC, NP, SD, ED, RA, textos y vacíos no suman ni dividen.
                 */
                $promedioPeriodo = $truncarPromedio(
                    $sumasPorPeriodo[$numeroPeriodo] / $capturadasPorPeriodo[$numeroPeriodo]
                );
            }

            $promediosPeriodos[$numeroPeriodo] = $formatearPromedio($promedioPeriodo);

            if ($promedioPeriodo !== null) {
                $sumaPromediosPeriodos += $promedioPeriodo;
                $periodosCapturados++;
            }
        }

        $promedio = '—';
        $promedioNumero = null;
        $porcentajePromedio = 0;
        $estadoPromedio = 'Sin datos';

        if ($periodosCapturados > 0) {
            /*
             * En básica el promedio final se divide siempre entre los 3 periodos.
             * Si falta un periodo, no se cambia el divisor porque así se conserva la lógica de Promedios Generales.
             * En bachillerato se divide entre los parciales numéricos encontrados.
             */
            $divisorPromedioFinal = $esBachillerato ? $periodosCapturados : count($numerosPeriodos);

            $promedioTruncado = $truncarPromedio($sumaPromediosPeriodos / max(1, $divisorPromedioFinal));

            $promedioNumero = $promedioTruncado;
            $promedio = number_format($promedioTruncado, 1, '.', '');
            $porcentajePromedio = min(100, $promedioTruncado * 10);

            if ($promedioTruncado < 6) {
                $estadoPromedio = 'Reprobado';
            } elseif ($promedioTruncado < 8) {
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

        $calcularPromedioFinalAlumno = function (int $inscripcionAlumnoId) use ($calificacionesGrupoPorAlumno, $materias, $periodos, $numerosPeriodos, $esBachillerato, $truncarPromedio): ?float {
            $mapaAlumno = $calificacionesGrupoPorAlumno->get($inscripcionAlumnoId, collect());

            $sumasPeriodoAlumno = [];
            $capturadasPeriodoAlumno = [];

            foreach ($numerosPeriodos as $numeroPeriodo) {
                $sumasPeriodoAlumno[$numeroPeriodo] = 0;
                $capturadasPeriodoAlumno[$numeroPeriodo] = 0;
            }

            foreach ($materias as $materia) {
                if ((int) ($materia->extra ?? 0) === 1 || (int) ($materia->receso ?? 0) === 1) {
                    continue;
                }

                foreach ($numerosPeriodos as $numeroPeriodo) {
                    $periodo = $periodos->get($numeroPeriodo);

                    if (!$periodo) {
                        continue;
                    }

                    $registro = $mapaAlumno->get($materia->id)?->get($periodo->id);
                    $valor = $registro ? trim((string) $registro->calificacion) : '';

                    if (!is_numeric($valor)) {
                        continue;
                    }

                    $numero = (float) $valor;

                    if ($numero < 0 || $numero > 10) {
                        continue;
                    }

                    $sumasPeriodoAlumno[$numeroPeriodo] += $numero;
                    $capturadasPeriodoAlumno[$numeroPeriodo]++;
                }
            }

            $sumaPromediosAlumno = 0;
            $periodosCapturadosAlumno = 0;

            foreach ($numerosPeriodos as $numeroPeriodo) {
                if ($capturadasPeriodoAlumno[$numeroPeriodo] <= 0) {
                    continue;
                }

                $promedioPeriodoAlumno = $truncarPromedio(
                    $sumasPeriodoAlumno[$numeroPeriodo] / $capturadasPeriodoAlumno[$numeroPeriodo]
                );

                $sumaPromediosAlumno += $promedioPeriodoAlumno;
                $periodosCapturadosAlumno++;
            }

            if ($periodosCapturadosAlumno <= 0) {
                return null;
            }

            $divisorAlumno = $esBachillerato ? $periodosCapturadosAlumno : count($numerosPeriodos);

            return $truncarPromedio($sumaPromediosAlumno / max(1, $divisorAlumno));
        };

        $promediosAlumnosGrupo = $inscripcionesGrupo
            ->map(function ($inscripcionGrupo) use ($calcularPromedioFinalAlumno) {
                return [
                    'inscripcion_id' => (int) $inscripcionGrupo->id,
                    'promedio_final' => $calcularPromedioFinalAlumno((int) $inscripcionGrupo->id),
                ];
            });

        $promediosUnicosDesc = $promediosAlumnosGrupo
            ->filter(fn(array $alumnoGrupo) => ($alumnoGrupo['promedio_final'] ?? null) !== null && (float) $alumnoGrupo['promedio_final'] > 0)
            ->pluck('promedio_final')
            ->map(fn($valor) => number_format((float) $valor, 1, '.', ''))
            ->unique()
            ->sortByDesc(fn($valor) => (float) $valor)
            ->values();

        $promedioClaveAlumno = $promedioNumero !== null
            ? number_format((float) $promedioNumero, 1, '.', '')
            : null;

        $indiceLugarAlumno = $promedioClaveAlumno !== null
            ? $promediosUnicosDesc->search($promedioClaveAlumno)
            : false;

        if ($indiceLugarAlumno !== false) {
            $lugarAlumno = ((int) $indiceLugarAlumno) + 1;
            $textoLugarAlumno = $lugarAlumno . '° lugar';
        }

        $totalMaterias = count($filasMaterias);

        $porcentajeCaptura = $totalCeldas > 0
            ? round((($totalCeldas - $pendientes) / $totalCeldas) * 100)
            : 0;

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
            ->with(['director', 'supervisor'])
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Logos
        |--------------------------------------------------------------------------
        */

        $logoIzquierdo = $this->imagenBase64Publica('imagenes/logo-letra.png');
        $logoDerecho = $this->imagenBase64Publica('storage/logos/' . $nivel->logo);

        $titulo = match ($tipo) {
            'semestral' => 'BOLETA SEMESTRAL',
            'boleta' => 'BOLETA ',
            'reconocimiento' => 'RECONOCIMIENTO',
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
            'suma_periodos' => collect($promediosPeriodos)
                ->filter(fn($valor) => is_numeric($valor))
                ->map(fn($valor) => (float) $valor)
                ->sum(),
            'promedio_final' => $promedioNumero,
            'periodos_capturados' => collect($promediosPeriodos)
                ->filter(fn($valor) => is_numeric($valor))
                ->count(),
            'periodos_faltantes' => count($numerosPeriodos) - collect($promediosPeriodos)
                ->filter(fn($valor) => is_numeric($valor))
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

            'escuela' => $escuela,
            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'generacion' => $generacion,

            'esBachillerato' => $esBachillerato,
            'esSecundaria' => $esSecundaria,

            'director' => $director,
            'docente' => $docente,

            'cicloEscolar' => $cicloEscolar,
            'cicloEscolarTexto' => $cicloEscolarTexto,

            'inscripcion' => $inscripcion,
            'nombreAlumno' => $nombreAlumno,

            'alumno' => $alumno,

            'periodosResumen' => $periodosResumen,
            'promediosPeriodos' => $promediosPeriodos,

            'filasMaterias' => $filasMaterias,
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
            'logo_izquierdo' => $logoIzquierdo,
            'logo_derecho' => $logoDerecho,
        ];

        $nombreArchivo = match ($tipo) {
            'semestral' => 'BOLETA_SEMESTRAL_' . str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) . '.pdf',
            'boleta' => 'BOLETA_ANUAL_' . str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) . '.pdf',
            'reconocimiento' => 'RECONOCIMIENTO_' . str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) . '.pdf',
            default => 'BOLETA_PROMEDIO_' . str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) . '.pdf',
        };

        $vista = $tipo === 'reconocimiento'
            ? 'pdf.reconocimiento_promedio_pdf'
            : 'pdf.boleta_promedio_pdf';

        return Pdf::loadView($vista, $data)
            ->setPaper('letter', $tipo === 'reconocimiento' ? 'landscape' : 'portrait')
            ->stream($nombreArchivo);
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
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('materias.calificable', 1);

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

        /*
         * Si no existe configuración o el número es 0,
         * no se promedia ninguna materia.
         */
        $numeroMateriasPromediar = $registroPromedio
            ? (int) $registroPromedio->numero_materias
            : 0;

        /*
         * promedio-numerico-pro:
         * Se toman todas las materias normales, no extra y no receso.
         * No se usa take(numero_materias), porque puede dejar fuera materias
         * numéricas cuando antes aparecen AC, NP, SD, ED, RA, textos o vacíos.
         */
        $materiasPromediables = $numeroMateriasPromediar > 0
            ? $materias
            ->filter(function ($materia) {
                return (int) ($materia->extra ?? 0) === 0
                    && (int) ($materia->receso ?? 0) === 0;
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

        $obtenerNumeroValido = function ($valor): ?float {
            $valor = strtoupper(trim((string) $valor));

            if ($valor === '' || !is_numeric($valor)) {
                return null;
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
            return floor(($valor + 0.000000001) * 10) / 10;
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
        $porcentajePromedio = 0;
        $estadoPromedio = 'Pendiente';

        if ($hayMateriasPromediables && $capturadasNumericasPromedio > 0) {
            /*
             * promedio-numerico-pro:
             * Se divide únicamente entre calificaciones numéricas encontradas.
             */
            $promedioCalculado = $suma / $capturadasNumericasPromedio;
            $promedioTruncado = $truncarPromedio($promedioCalculado);

            $promedioNumero = $promedioTruncado;
            $promedio = number_format($promedioTruncado, 1, '.', '');
            $porcentajePromedio = min(100, $promedioTruncado * 10);

            if ($promedioTruncado < 6) {
                $estadoPromedio = 'Reprobado';
            } elseif ($promedioTruncado < 8) {
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
        | - Se divide solo entre calificaciones numéricas encontradas.
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

        foreach ($inscripcionesLugar as $filaLugar) {
            $inscripcionLugarId = (int) $filaLugar['inscripcion_id'];

            if (!$hayMateriasPromediables) {
                $promediosLugar[$inscripcionLugarId] = 'Pendiente';
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
                continue;
            }

            $promedioLugarCalculado = $sumaLugar / $totalNumericasLugar;
            $promedioLugarTruncado = $truncarPromedio($promedioLugarCalculado);

            $promediosLugar[$inscripcionLugarId] = number_format($promedioLugarTruncado, 1, '.', '');
        }

        $inscripcionesOrdenadasLugar = collect($inscripcionesLugar)
            ->sortByDesc(function ($filaLugar) use ($promediosLugar) {
                $promedioAlumnoLugar = $promediosLugar[$filaLugar['inscripcion_id']] ?? null;

                return is_numeric($promedioAlumnoLugar) ? (float) $promedioAlumnoLugar : -1;
            })
            ->values();

        $promediosUnicosLugar = $hayMateriasPromediables
            ? $inscripcionesOrdenadasLugar
            ->map(function ($filaLugar) use ($promediosLugar, $truncarPromedio) {
                $promedioAlumnoLugar = $promediosLugar[$filaLugar['inscripcion_id']] ?? null;

                if (!is_numeric($promedioAlumnoLugar) || (float) $promedioAlumnoLugar <= 0) {
                    return null;
                }

                return number_format($truncarPromedio((float) $promedioAlumnoLugar), 1, '.', '');
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

        $promedioClaveAlumno = $hayMateriasPromediables && is_numeric($promedio) && (float) $promedio > 0
            ? number_format($truncarPromedio((float) $promedio), 1, '.', '')
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
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('materias.calificable', 1);

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
                    'extra' => (int) ($item->extra ?? 0),
                    'orden' => $item->orden,
                ];
            })
            ->values()
            ->toArray();

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
                ->mapWithKeys(function ($item) {
                    $clave = $item->inscripcion_id . '-' . $item->asignacion_materia_id;

                    return [
                        $clave => strtoupper(trim((string) $item->calificacion)),
                    ];
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

        $numeroMateriasPromediar = $registroPromedio
            ? (int) $registroPromedio->numero_materias
            : 0;

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
            ? collect($materias)
            ->filter(fn($materia) => (int) ($materia['extra'] ?? 0) === 0)
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

        $obtenerNumeroValido = function ($valor): ?float {
            $valor = strtoupper(trim((string) $valor));

            if ($valor === '' || !is_numeric($valor)) {
                return null;
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
            return floor(($valor + 0.000000001) * 10) / 10;
        };

        /*
        |--------------------------------------------------------------------------
        | Promedios por alumno
        |--------------------------------------------------------------------------
        */

        $promedios = [];
        $totalNumericasPorAlumno = [];

        foreach ($inscripciones as $fila) {
            $inscripcionId = (int) $fila['inscripcion_id'];

            if ($numeroMateriasPromediar <= 0 || empty($idsMateriasPromediables)) {
                $promedios[$inscripcionId] = 'Pendiente';
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
                continue;
            }

            /*
             * Se divide solo entre calificaciones numéricas capturadas.
             */
            $promedio = $truncarPromedio($suma / $totalNumericas);

            $promedios[$inscripcionId] = number_format($promedio, 1, '.', '');
        }

        /*
            |--------------------------------------------------------------------------
            | Promedio por materia
            |--------------------------------------------------------------------------
            | Solo se toman calificaciones numéricas.
            | AC, ED, RA, NP, SD, vacíos o textos no se suman ni se cuentan.
            */

        $promediosPorMateria = [];

        foreach ($materias as $materia) {
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
                $promedioMateria = $truncarPromedio($sumaMateria / $totalNumericasMateria);

                $promedioTexto = number_format($promedioMateria, 1, '.', '');
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

        $promediosNumericosGrupo = collect($promedios)
            ->filter(fn($valor) => is_numeric($valor))
            ->map(fn($valor) => (float) $valor)
            ->values();

        $promedioGeneralGrupo = $promediosNumericosGrupo->isNotEmpty()
            ? $truncarPromedio($promediosNumericosGrupo->avg())
            : 0;

        $promedioGeneralGrupoTexto = number_format($promedioGeneralGrupo, 1, '.', '');

        $porcentajePromedioGeneral = min(100, $promedioGeneralGrupo * 10);

        /*
    |--------------------------------------------------------------------------
    | Estadísticas generales
    |--------------------------------------------------------------------------
    */

        $totalAlumnos = count($inscripciones);

        $totalConPromedio = $promediosNumericosGrupo->count();

        $totalAprobados = collect($promedios)
            ->filter(fn($valor) => is_numeric($valor) && (float) $valor >= 6)
            ->count();

        $totalReprobados = collect($promedios)
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

            'materias' => $materias,
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
         * Se detecta primaria para separar sus materias extra en el PDF.
         * Las materias extra no se toman en cuenta para el promedio.
         */
        $esPrimaria = \Illuminate\Support\Str::contains(
            mb_strtolower((string) ($nivel->slug ?? $nivel->nombre ?? '')),
            'primaria'
        );

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
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('materias.calificable', 1);

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

        /*
         * Si no existe configuración o el número es 0,
         * el promedio queda pendiente.
         */
        $numeroMateriasPromediar = $registroPromedio
            ? max(0, (int) $registroPromedio->numero_materias)
            : 0;

        /*
         * promedio-numerico-pro:
         * Se toman todas las materias normales ordenadas por asignación.
         * No se usa take(), porque los textos como AC, NP, SD, ED o RA
         * no deben dejar fuera otras materias numéricas.
         */
        $materiasPromediables = $numeroMateriasPromediar > 0
            ? $materias
            ->filter(function ($materia) {
                return (int) ($materia->extra ?? 0) === 0
                    && (int) ($materia->receso ?? 0) === 0;
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

        $obtenerNumeroValido = function ($valor): ?float {
            $valor = strtoupper(trim((string) $valor));

            if ($valor === '' || !is_numeric($valor)) {
                return null;
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
            return floor(($valor + 0.000000001) * 10) / 10;
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
        $porcentajePromedio = 0;
        $estadoPromedio = 'Pendiente';

        if ($hayMateriasPromediables && $capturadasNumericasPromedio > 0) {
            /*
             * Se divide únicamente entre las calificaciones numéricas encontradas.
             * AC, NP, SD, ED, RA, textos y vacíos no suman ni dividen.
             */
            $promedioNumero = $truncarPromedio($sumaPromedio / $capturadasNumericasPromedio);
            $promedio = number_format($promedioNumero, 1, '.', '');
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
         * En primaria se separan las materias normales y las materias extra.
         * Las materias extra se muestran en una tabla aparte y no afectan el promedio.
         */
        $filasMateriasRegulares = collect($filasMaterias)
            ->filter(fn($fila) => (int) ($fila['extra'] ?? 0) === 0)
            ->values()
            ->toArray();

        $filasMateriasExtras = collect($filasMaterias)
            ->filter(fn($fila) => (int) ($fila['extra'] ?? 0) === 1)
            ->values()
            ->toArray();

        if (!$esPrimaria) {
            $filasMateriasRegulares = $filasMaterias;
            $filasMateriasExtras = [];
        }

        /*
         * Para primaria, el avance de captura se calcula solo con materias normales.
         * Así las materias extra no afectan el resumen principal.
         */
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

        foreach ($inscripcionesLugar as $filaLugar) {
            $inscripcionLugarId = (int) $filaLugar['inscripcion_id'];

            if (!$hayMateriasPromediables) {
                $promediosLugar[$inscripcionLugarId] = 'Pendiente';
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
                continue;
            }

            $promedioLugar = $truncarPromedio($sumaLugar / $totalNumericasLugar);

            $promediosLugar[$inscripcionLugarId] = number_format($promedioLugar, 1, '.', '');
        }

        $inscripcionesOrdenadasLugar = collect($inscripcionesLugar)
            ->sortByDesc(function ($filaLugar) use ($promediosLugar) {
                $promedioAlumnoLugar = $promediosLugar[$filaLugar['inscripcion_id']] ?? null;

                return is_numeric($promedioAlumnoLugar) ? (float) $promedioAlumnoLugar : -1;
            })
            ->values();

        $promediosUnicosLugar = $inscripcionesOrdenadasLugar
            ->map(function ($filaLugar) use ($promediosLugar) {
                $promedioAlumnoLugar = $promediosLugar[$filaLugar['inscripcion_id']] ?? null;

                if (!is_numeric($promedioAlumnoLugar) || (float) $promedioAlumnoLugar <= 0) {
                    return null;
                }

                return number_format((float) $promedioAlumnoLugar, 1, '.', '');
            })
            ->filter()
            ->unique()
            ->values()
            ->take(3);

        $lugaresPorPromedio = [];

        foreach ($promediosUnicosLugar as $index => $promedioUnicoLugar) {
            $lugaresPorPromedio[$promedioUnicoLugar] = $index + 1;
        }

        $promedioClaveAlumno = is_numeric($promedio)
            ? number_format((float) $promedio, 1, '.', '')
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
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
            ->where('materias.calificable', 1);

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
                ->filter(fn($materia) => (int) ($materia->extra ?? 0) === 0)
                ->count();
        }

        /*
        |--------------------------------------------------------------------------
        | Filas de materias y promedio del alumno
        |--------------------------------------------------------------------------
        */

        $filasMaterias = [];

        $suma = 0;
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

            if (is_numeric($valor)) {
                $numero = (float) $valor;

                if ($numero >= 0 && $numero <= 10) {
                    $porcentaje = min(100, $numero * 10);
                    $capturadasNumericas++;

                    if ((int) ($materia->extra ?? 0) === 0) {
                        $suma += $numero;
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
        $porcentajePromedio = 0;
        $estadoPromedio = 'Sin datos';

        if ($numeroMateriasPromediar > 0) {
            $promedioCalculado = $suma / $numeroMateriasPromediar;
            $promedioTruncado = floor($promedioCalculado * 10) / 10;

            $promedioNumero = $promedioTruncado;
            $promedio = number_format($promedioTruncado, 1);
            $porcentajePromedio = min(100, $promedioTruncado * 10);

            if ($promedioTruncado < 6) {
                $estadoPromedio = 'Reprobado';
            } elseif ($promedioTruncado < 8) {
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

        foreach ($inscripcionesLugar as $filaLugar) {
            $inscripcionLugarId = (int) $filaLugar['inscripcion_id'];

            if ($numeroMateriasPromediar <= 0) {
                $promediosLugar[$inscripcionLugarId] = '—';
                continue;
            }

            $sumaLugar = 0;

            foreach ($materias as $materia) {
                if ((int) ($materia->extra ?? 0) !== 0) {
                    continue;
                }

                $claveLugar = $inscripcionLugarId . '-' . $materia->id;
                $valorLugar = $calificacionesLugar[$claveLugar] ?? null;

                if ($valorLugar === null || $valorLugar === '') {
                    continue;
                }

                $valorLugar = strtoupper(trim((string) $valorLugar));

                if (!is_numeric($valorLugar)) {
                    continue;
                }

                $numeroLugar = (float) $valorLugar;

                if ($numeroLugar >= 0 && $numeroLugar <= 10) {
                    $sumaLugar += $numeroLugar;
                }
            }

            $promedioLugarCalculado = $sumaLugar / $numeroMateriasPromediar;
            $promedioLugarTruncado = floor($promedioLugarCalculado * 10) / 10;

            $promediosLugar[$inscripcionLugarId] = number_format($promedioLugarTruncado, 1);
        }

        $inscripcionesOrdenadasLugar = collect($inscripcionesLugar)
            ->sortByDesc(function ($filaLugar) use ($promediosLugar) {
                $promedioAlumnoLugar = $promediosLugar[$filaLugar['inscripcion_id']] ?? null;

                return is_numeric($promedioAlumnoLugar) ? (float) $promedioAlumnoLugar : -1;
            })
            ->values();

        $promediosUnicosLugar = $inscripcionesOrdenadasLugar
            ->map(function ($filaLugar) use ($promediosLugar) {
                $promedioAlumnoLugar = $promediosLugar[$filaLugar['inscripcion_id']] ?? null;

                return is_numeric($promedioAlumnoLugar)
                    ? number_format((float) $promedioAlumnoLugar, 1, '.', '')
                    : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->take(3);

        $lugaresPorPromedio = [];

        foreach ($promediosUnicosLugar as $index => $promedioUnicoLugar) {
            $lugaresPorPromedio[$promedioUnicoLugar] = $index + 1;
        }

        $promedioClaveAlumno = is_numeric($promedio)
            ? number_format((float) $promedio, 1, '.', '')
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
            esBachillerato: $esBachillerato
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
        bool $esBachillerato
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

        $alumnosQuery = $this->queryInscripcionesPorContextoPdf(
            nivel: $nivel,
            generacion: $generacion,
            grado: $grado,
            grupo: $grupo,
            esBachillerato: $esBachillerato
        );

        $alumnos = $alumnosQuery
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

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
                'materias.extra as extra',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $nivel->id)
            ->where('materias.grado_id', $grado->id)
            ->where('asignacion_materias.grupo_id', $grupo->id)
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
                        && (int) ($materia->calificable ?? 0) === 1;
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
            ->orderByDesc('id')
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
