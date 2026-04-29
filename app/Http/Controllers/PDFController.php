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
use App\Models\Periodos;
use App\Models\Persona;
use App\Models\Semestre;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class PDFController extends Controller
{

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
                'detalles.grupo',
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
        $slugNivel = (string) $request->input('slug_nivel');
        $generacionId = $request->input('generacion_id');
        $gradoId = $request->input('grado_id');
        $grupoId = $request->input('grupo_id');
        $semestreId = $request->input('semestre_id');

        if (blank($slugNivel) || blank($generacionId) || blank($gradoId) || blank($grupoId)) {
            abort(422, 'Los parámetros slug_nivel, generacion_id, grado_id y grupo_id son obligatorios.');
        }

        $escuela = \App\Models\Escuela::query()->first();

        if (!$escuela) {
            abort(404, 'No se encontró la escuela.');
        }

        $nivel = \App\Models\Nivel::query()
            ->where('slug', $slugNivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $generacion = \App\Models\Generacion::query()
            ->where('id', $generacionId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$generacion) {
            abort(404, 'Generación no encontrada o no pertenece al nivel seleccionado.');
        }

        $grado = \App\Models\Grado::query()
            ->where('id', $gradoId)
            ->where('nivel_id', $nivel->id)
            ->first();

        if (!$grado) {
            abort(404, 'Grado no encontrado o no pertenece al nivel seleccionado.');
        }

        $grupo = \App\Models\Grupo::query()
            ->with(['generacion', 'grado', 'semestre'])
            ->where('id', $grupoId)
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('generacion_id', $generacion->id)
            ->first();

        if (!$grupo) {
            abort(404, 'Grupo no encontrado o no pertenece a la generación y grado seleccionados.');
        }

        $esBachillerato = (int) $nivel->id === 4;

        $semestre = null;

        if ($esBachillerato) {
            if (blank($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $semestre = \App\Models\Semestre::query()
                ->where('id', $semestreId)
                ->where('grado_id', $grado->id)
                ->first();

            if (!$semestre) {
                abort(404, 'Semestre no encontrado o no pertenece al grado seleccionado.');
            }

            if ((int) $grupo->semestre_id !== (int) $semestre->id) {
                abort(422, 'El grupo seleccionado no pertenece al semestre indicado.');
            }
        }

        $cicloEscolar = \App\Models\CicloEscolar::query()
            ->latest('id')
            ->first();

        if (!$cicloEscolar) {
            abort(404, 'No se encontró el ciclo escolar.');
        }

        $horas = \App\Models\Hora::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get();

        $dias = \App\Models\Dia::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->get()
            ->unique('dia')
            ->values();

        $queryHorarios = \App\Models\Horario::query()
            ->with([
                'dia',
                'hora',
                'asignacionMateria.profesor',
            ])
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

        if ($esBachillerato) {
            $queryHorarios->where('semestre_id', $semestre->id);
        } else {
            $queryHorarios->whereNull('semestre_id');
        }

        $horarios = $queryHorarios->get();

        $horarioPorCelda = $horarios->keyBy(function ($item) {
            return $item->hora_id . '-' . $item->dia_id;
        });

        $profesorTitular = null;

        if (!$esBachillerato) {
            $primeraMateriaConProfesor = \App\Models\AsignacionMateria::query()
                ->with('profesor')
                ->where('nivel_id', $nivel->id)
                ->where('grado_id', $grado->id)
                ->where('grupo_id', $grupo->id)
                ->whereNull('semestre')
                ->whereNotNull('profesor_id')
                ->orderBy('orden')
                ->first();

            if ($primeraMateriaConProfesor?->profesor) {
                $profesor = $primeraMateriaConProfesor->profesor;

                $profesorTitular = trim(
                    ($profesor->nombre ?? '') . ' ' .
                        ($profesor->apellido_paterno ?? '') . ' ' .
                        ($profesor->apellido_materno ?? '')
                );
            }
        }

        $logoIzquierdo = public_path('imagenes/logo-letra.png');
        $logoDerecho = public_path('imagenes/logo-secundario-moctezuma.png');

        $imagenesPorNivel = [
            'preescolar' => public_path('imagenes/personajes_preescolar.png'),
            'primaria' => public_path('imagenes/personajes_primaria.png'),
            'secundaria' => public_path('imagenes/personajes_secundaria.png'),
            'bachillerato' => public_path('imagenes/personajes_bachillerato.png'),
        ];

        $slugNivelNormalizado = mb_strtolower((string) $nivel->slug, 'UTF-8');
        $imagenNivel = $imagenesPorNivel[$slugNivelNormalizado] ?? null;

        $logoIzquierdo = file_exists($logoIzquierdo) ? $logoIzquierdo : null;
        $logoDerecho = file_exists($logoDerecho) ? $logoDerecho : null;
        $imagenNivel = ($imagenNivel && file_exists($imagenNivel)) ? $imagenNivel : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.horarios_pdf', [
            'escuela' => $escuela,
            'nivel' => $nivel,
            'generacion' => $generacion,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'ciclo_escolar' => $cicloEscolar,
            'horas' => $horas,
            'dias' => $dias,
            'horarioPorCelda' => $horarioPorCelda,
            'profesor_titular' => $profesorTitular,
            'logo_izquierdo' => $logoIzquierdo,
            'logo_derecho' => $logoDerecho,
            'imagen_nivel' => $imagenNivel,
        ])->setPaper('letter', 'landscape');

        $nombreArchivo = 'HORARIO_' .
            mb_strtoupper($nivel->nombre ?? 'NIVEL', 'UTF-8') . '_' .
            'GEN_' . ($generacion->anio_ingreso ?? 'GEN') . '-' . ($generacion->anio_egreso ?? 'GEN') . '_' .
            'GRADO_' . ($grado->nombre ?? 'GRADO') . '_' .
            'GRUPO_' . ($grupo->nombre ?? 'GRUPO') .
            ($esBachillerato && $semestre ? '_SEMESTRE_' . ($semestre->numero ?? '') : '') .
            '.pdf';

        return $pdf->stream($nombreArchivo);
    }

    // CALIFICACIONES PDF

    public function calificaciones_pdf(\Illuminate\Http\Request $request)
    {
        $slugNivel = $request->input('slug_nivel');
        $generacionId = $request->input('generacion_id');
        $gradoId = $request->input('grado_id');
        $grupoId = $request->input('grupo_id');

        /*
         * El PDF puede recibir periodo_id directo.
         * Para básica también puede llegar periodo_basica_id.
         */
        $periodoId = $request->input('periodo_id');
        $periodoBasicaId = $request->input('periodo_basica_id');
        $parcialBachilleratoId = $request->input('parcial_bachillerato_id');
        $semestreId = $request->input('semestre_id');

        $busqueda = trim((string) $request->input('busqueda', ''));

        if (empty($slugNivel) || empty($generacionId) || empty($gradoId) || empty($grupoId)) {
            abort(422, 'Los parámetros slug_nivel, generacion_id, grado_id y grupo_id son obligatorios.');
        }

        $escuela = \App\Models\Escuela::first();

        $nivel = \App\Models\Nivel::query()
            ->where('slug', $slugNivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $slugNivelNormalizado = mb_strtolower((string) $nivel->slug);
        $esBachillerato = $slugNivelNormalizado === 'bachillerato';

        $grado = \App\Models\Grado::query()->find($gradoId);

        if (!$grado) {
            abort(404, 'Grado no encontrado.');
        }

        $grupo = \App\Models\Grupo::query()
            ->with('generacion')
            ->find($grupoId);

        if (!$grupo) {
            abort(404, 'Grupo no encontrado.');
        }

        if ((int) $grupo->grado_id !== (int) $grado->id) {
            abort(422, 'El grupo seleccionado no pertenece al grado indicado.');
        }

        $semestre = null;

        if ($esBachillerato) {
            if (empty($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            if (empty($periodoId) && empty($parcialBachilleratoId)) {
                abort(422, 'El parámetro periodo_id o parcial_bachillerato_id es obligatorio para bachillerato.');
            }

            $semestre = \App\Models\Semestre::query()->find($semestreId);

            if (!$semestre) {
                abort(404, 'Semestre no encontrado.');
            }

            if (!empty($grupo->semestre_id) && (int) $grupo->semestre_id !== (int) $semestre->id) {
                abort(422, 'El grupo seleccionado no pertenece al semestre indicado.');
            }
        } else {
            if (empty($periodoId) && empty($periodoBasicaId)) {
                abort(422, 'El parámetro periodo_id o periodo_basica_id es obligatorio para básica.');
            }
        }

        /*
         * Obtiene el periodo.
         */
        $queryPeriodo = \App\Models\Periodos::query()
            ->with([
                'cicloEscolar',
                'periodoBasica',
                'parcialBachillerato',
                'mesesBasica',
                'mesesBachillerato',
            ])
            ->where('nivel_id', $nivel->id);

        if (!empty($periodoId)) {
            $queryPeriodo->where('id', $periodoId);
        } else {
            if ($esBachillerato) {
                $queryPeriodo
                    ->where('generacion_id', $generacionId)
                    ->where('semestre_id', $semestreId)
                    ->where('parcial_bachillerato_id', $parcialBachilleratoId);
            } else {
                $queryPeriodo->where('periodo_basica_id', $periodoBasicaId);
            }
        }

        $periodo = $queryPeriodo
            ->latest('id')
            ->first();

        if (!$periodo) {
            abort(404, 'Periodo no encontrado.');
        }

        $periodoId = $periodo->id;
        $cicloEscolarId = $periodo->ciclo_escolar_id;

        if (blank($cicloEscolarId)) {
            $ultimoCiclo = \App\Models\CicloEscolar::query()
                ->latest('id')
                ->first();

            $cicloEscolarId = $ultimoCiclo?->id;
        }

        /*
         * Nombre visible del periodo seleccionado.
         */
        $nombrePeriodo = 'Periodo seleccionado';

        if (!$esBachillerato && $periodo?->periodoBasica?->descripcion) {
            $nombrePeriodo = $periodo->periodoBasica->descripcion;
        }

        if ($esBachillerato && $periodo?->parcialBachillerato?->descripcion) {
            $nombrePeriodo = $periodo->parcialBachillerato->descripcion;
        }

        /*
         * Materias calificables.
         */
        $queryMaterias = \App\Models\AsignacionMateria::query()
            ->where('nivel_id', $nivel->id)
            ->where('grupo_id', $grupo->id)
            ->where('calificable', 1);

        if (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'grado_id')) {
            $queryMaterias->where('grado_id', $grado->id);
        }

        if ($esBachillerato) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'semestre_id')) {
                $queryMaterias->where('semestre_id', $semestre->id);
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'semestre')) {
                $queryMaterias->where('semestre', $semestre->id);
            }
        } else {
            if (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'semestre_id')) {
                $queryMaterias->whereNull('semestre_id');
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'semestre')) {
                $queryMaterias->whereNull('semestre');
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'orden')) {
            $queryMaterias->orderBy('orden');
        }

        $queryMaterias->orderBy('materia');

        $materias = $queryMaterias->get()
            ->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'materia' => $item->materia ?: 'MATERIA',
                    'extra' => (int) ($item->extra ?? 0),
                ];
            })
            ->values()
            ->toArray();

        /*
         * Inscripciones.
         */
        $queryInscripciones = \App\Models\Inscripcion::query()
            ->with(['grado:id,nombre', 'grupo:id,nombre', 'semestre:id,numero'])
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacionId)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

        if ($esBachillerato) {
            $queryInscripciones->where('semestre_id', $semestre->id);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('inscripciones', 'activo')) {
            $queryInscripciones->where('activo', 1);
        }

        if ($busqueda !== '') {
            $queryInscripciones->where(function ($query) use ($busqueda) {
                $query->where('matricula', 'like', "%{$busqueda}%")
                    ->orWhere('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_paterno', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_materno', 'like', "%{$busqueda}%")
                    ->orWhere(
                        \Illuminate\Support\Facades\DB::raw(
                            "TRIM(CONCAT(nombre,' ',IFNULL(apellido_paterno,''),' ',IFNULL(apellido_materno,'')))"
                        ),
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
                    'grupo' => $item->grupo?->nombre ?? '—',
                    'semestre' => $item->semestre?->numero ?? '—',
                ];
            })
            ->values()
            ->toArray();

        $idsInscripciones = collect($inscripciones)->pluck('inscripcion_id')->values()->all();
        $idsMaterias = collect($materias)->pluck('id')->values()->all();

        /*
         * Calificaciones guardadas.
         */
        $calificaciones = [];

        if (!empty($idsInscripciones) && !empty($idsMaterias)) {
            $queryCalificaciones = \App\Models\Calificacion::query()
                ->whereIn('inscripcion_id', $idsInscripciones)
                ->whereIn('asignacion_materia_id', $idsMaterias)
                ->where('periodo_id', $periodoId);

            if (\Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'nivel_id')) {
                $queryCalificaciones->where('nivel_id', $nivel->id);
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'generacion_id')) {
                $queryCalificaciones->where('generacion_id', $generacionId);
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'grado_id')) {
                $queryCalificaciones->where('grado_id', $grado->id);
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'grupo_id')) {
                $queryCalificaciones->where('grupo_id', $grupo->id);
            }

            if (!blank($cicloEscolarId) && \Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
                $queryCalificaciones->where('ciclo_escolar_id', $cicloEscolarId);
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'semestre_id')) {
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
         * Número de materias para promediar.
         */
        $queryMateriaPromediar = \App\Models\MateriaPromediar::query()
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

        if ($esBachillerato) {
            $queryMateriaPromediar->where('semestre_id', $semestre->id);
        } else {
            $queryMateriaPromediar->whereNull('semestre_id');
        }

        $registroPromedio = $queryMateriaPromediar->first();

        $numeroMateriasPromediar = (int) ($registroPromedio?->numero_materias ?? 0);

        /*
         * Si no existe configuración, usa las materias normales que no son extra.
         */
        if ($numeroMateriasPromediar <= 0) {
            $numeroMateriasPromediar = collect($materias)
                ->filter(fn($materia) => (int) ($materia['extra'] ?? 0) === 0)
                ->count();
        }

        /*
         * Promedios por alumno.
         * Solo toma calificaciones numéricas de 0 a 10.
         * Trunca a un decimal, no redondea.
         */
        $promedios = [];

        foreach ($inscripciones as $fila) {
            $inscripcionId = (int) $fila['inscripcion_id'];

            if ($numeroMateriasPromediar <= 0) {
                $promedios[$inscripcionId] = '—';
                continue;
            }

            $suma = 0;

            foreach ($materias as $materia) {
                if ((int) ($materia['extra'] ?? 0) !== 0) {
                    continue;
                }

                $clave = $inscripcionId . '-' . $materia['id'];
                $valor = $calificaciones[$clave] ?? null;

                if ($valor === null || $valor === '') {
                    continue;
                }

                $valor = strtoupper(trim((string) $valor));

                if (!is_numeric($valor)) {
                    continue;
                }

                $numero = (float) $valor;

                if ($numero >= 0 && $numero <= 10) {
                    $suma += $numero;
                }
            }

            $promedio = $suma / $numeroMateriasPromediar;
            $promedioTruncado = floor($promedio * 10) / 10;

            $promedios[$inscripcionId] = number_format($promedioTruncado, 1);
        }

        /*
         * Promedios por materia.
         */
        $promediosPorMateria = [];

        foreach ($materias as $materia) {
            $sumaMateria = 0;
            $totalMateria = 0;

            foreach ($inscripciones as $fila) {
                $clave = $fila['inscripcion_id'] . '-' . $materia['id'];
                $valor = $calificaciones[$clave] ?? null;

                if ($valor === null || $valor === '') {
                    continue;
                }

                $valor = strtoupper(trim((string) $valor));

                if (!is_numeric($valor)) {
                    continue;
                }

                $numero = (float) $valor;

                if ($numero < 0 || $numero > 10) {
                    continue;
                }

                $sumaMateria += $numero;
                $totalMateria++;
            }

            $promedioMateria = $totalMateria > 0
                ? floor(($sumaMateria / $totalMateria) * 10) / 10
                : null;

            $promediosPorMateria[] = [
                'id' => $materia['id'],
                'materia' => $materia['materia'],
                'promedio' => $promedioMateria !== null ? number_format($promedioMateria, 1) : '—',
                'porcentaje' => $promedioMateria !== null ? min(100, $promedioMateria * 10) : 0,
                'total_capturadas' => $totalMateria,
            ];
        }

        /*
         * Promedio general del grupo.
         */
        $promediosNumericosGrupo = collect($promedios)
            ->filter(fn($valor) => is_numeric($valor))
            ->map(fn($valor) => (float) $valor)
            ->values();

        $promedioGeneralGrupo = $promediosNumericosGrupo->isNotEmpty()
            ? floor($promediosNumericosGrupo->avg() * 10) / 10
            : null;

        $promedioGeneralGrupoTexto = $promedioGeneralGrupo !== null
            ? number_format($promedioGeneralGrupo, 1)
            : '—';

        $porcentajePromedioGeneral = $promedioGeneralGrupo !== null
            ? min(100, $promedioGeneralGrupo * 10)
            : 0;

        /*
         * Estadísticas generales.
         */
        $totalAlumnos = count($inscripciones);

        $totalAprobados = collect($promedios)
            ->filter(fn($valor) => is_numeric($valor) && (float) $valor >= 6)
            ->count();

        $totalReprobados = collect($promedios)
            ->filter(fn($valor) => is_numeric($valor) && (float) $valor < 6)
            ->count();

        $totalSinPromedio = collect($promedios)
            ->filter(fn($valor) => !is_numeric($valor))
            ->count();

        $porcentajeAprobacion = $totalAlumnos > 0
            ? round(($totalAprobados / $totalAlumnos) * 100)
            : 0;

        /*
         * Periodos por materia.
         * Como este PDF corresponde a un periodo seleccionado,
         * se muestra ese periodo aplicado a cada materia calificable.
         */
        $periodosPorMateria = collect($materias)
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
         * Logos e imagen por nivel.
         */
        $logoIzquierdo = public_path('storage/logos' . $nivel->logo);
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.calificaciones_pdf', [
            'titulo' => 'REPORTE DE CALIFICACIONES',
            'escuela' => $escuela,
            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'esBachillerato' => $esBachillerato,
            'periodo' => $periodo,
            'nombrePeriodo' => $nombrePeriodo,
            'busqueda' => $busqueda,

            'materias' => $materias,
            'inscripciones' => $inscripciones,
            'calificaciones' => $calificaciones,
            'promedios' => $promedios,

            'promediosPorMateria' => $promediosPorMateria,
            'promedioGeneralGrupo' => $promedioGeneralGrupoTexto,
            'porcentajePromedioGeneral' => $porcentajePromedioGeneral,
            'totalAlumnos' => $totalAlumnos,
            'totalAprobados' => $totalAprobados,
            'totalReprobados' => $totalReprobados,
            'totalSinPromedio' => $totalSinPromedio,
            'porcentajeAprobacion' => $porcentajeAprobacion,
            'periodosPorMateria' => $periodosPorMateria,

            'fecha_impresion' => now(),
            'logo_izquierdo' => $logoIzquierdo,
            'logo_derecho' => $logoDerecho,
            'imagen_nivel' => $imagenNivel,
        ])->setPaper('letter', 'landscape');

        $nombreArchivo = 'CALIFICACIONES_' .
            mb_strtoupper($nivel->nombre ?? 'NIVEL') . '_' .
            'GRADO_' . ($grado->nombre ?? 'GRADO') . '_' .
            'GRUPO_' . ($grupo->nombre ?? 'GRUPO') .
            ($esBachillerato && $semestre ? '_SEMESTRE_' . $semestre->numero : '') .
            '_PERIODO_' . $periodo->id .
            '.pdf';

        return $pdf->stream($nombreArchivo);
    }


    // LISTAS PDF
    public function lista_pdf(Request $request, string $slug_nivel)
    {

        $generacion_id = $request->integer('generacion_id');
        $grado_id = $request->integer('grado_id');
        $grupo_id = $request->integer('grupo_id');
        $semestre_id = $request->integer('semestre_id');

        $tipo_descarga = $request->input('tipo_descarga', 'evaluacion');
        $opcion_descarga = $request->input('opcion_descarga', 'primer_periodo');

        $mostrarMotivo = $tipo_descarga === 'grupo' && $request->boolean('mostrar_motivo');

        /*
        |--------------------------------------------------------------------------
        | Valido tipo y opción de descarga
        |--------------------------------------------------------------------------
        */

        $tiposPermitidos = [
            'evaluacion',
            'asistencia',
            'grupo',
            'formatos',
        ];

        if (!in_array($tipo_descarga, $tiposPermitidos)) {
            abort(404, 'El tipo de descarga no es válido.');
        }

        $opcionesPermitidas = match ($tipo_descarga) {
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

            'formatos' => [
                'sece',
                'sece_interna',
                'lista_boletas',
                'personalizadores',
                'etiquetas',
            ],

            default => [],
        };

        if (!in_array($opcion_descarga, $opcionesPermitidas)) {
            abort(404, 'La opción de descarga no es válida.');
        }

        /*
        |--------------------------------------------------------------------------
        | Consulto información principal
        |--------------------------------------------------------------------------
        */

        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $esBachillerato = $this->esBachillerato($nivel);

        $generacion = Generacion::query()
            ->where('id', $generacion_id)
            ->where('nivel_id', $nivel->id)
            ->firstOrFail();

        $grado = Grado::query()
            ->where('id', $grado_id)
            ->where('nivel_id', $nivel->id)
            ->firstOrFail();

        $grupoQuery = Grupo::query()
            ->where('id', $grupo_id)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id);

        if ($esBachillerato) {
            $grupoQuery->where('semestre_id', $semestre_id);
        } else {
            $grupoQuery->whereNull('semestre_id');
        }

        $grupo = $grupoQuery->firstOrFail();

        $semestre = null;

        if ($esBachillerato && $semestre_id) {
            $semestre = Semestre::query()
                ->where('id', $semestre_id)
                ->where('grado_id', $grado->id)
                ->firstOrFail();
        }

        $alumnosQuery = Inscripcion::query()
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id)
            ->where('activo', 1);

        if ($esBachillerato) {
            $alumnosQuery->where('semestre_id', $semestre_id);
        } else {
            $alumnosQuery->whereNull('semestre_id');
        }

        $alumnos = $alumnosQuery
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        $materiasQuery = AsignacionMateria::query()
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id)
            ->where('calificable', 1);

        if ($esBachillerato) {
            $materiasQuery->where('semestre', $semestre_id);
        } else {
            $materiasQuery->whereNull('semestre');
        }

        $materias = $materiasQuery
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $profesor_id = $materias
            ->pluck('profesor_id')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        $docente = null;

        if ($profesor_id) {
            $docente = Persona::query()
                ->where('id', $profesor_id)
                ->first();
        }

        $nombreDocente = $docente
            ? $this->nombrePersona($docente)
            : '____________________________';

        $escuela = DB::table('escuela')->first();

        $cicloEscolar = CicloEscolar::orderBy('id', 'desc')->first();


        $periodo = Periodos::with(['periodoBasica', 'parcialBachillerato'])
            ->where('nivel_id', $nivel->id)
            ->where('periodo_basica_id', $this->numeroPeriodoAsistencia($opcion_descarga))
            ->first();


        if ($tipo_descarga === 'asistencia') {
            $periodoNumero = $this->numeroPeriodoAsistencia($opcion_descarga);
            $periodoTexto = $this->textoPeriodoAsistencia($opcion_descarga);
            $mesAsistencia = $this->mesPeriodoAsistencia($opcion_descarga);
        } else {
            $periodoNumero = $periodo;
            $periodoTexto = $this->textoPeriodoEvaluacion($opcion_descarga);
            $mesAsistencia = null;
        }


        $logoIzquierdo = $this->imagenBase64Publica('storage/logos/' . $nivel->logo);
        $logoDerecho = $this->imagenBase64Publica('imagenes/logo-letra.png');
        $marcaAgua = $this->imagenBase64Publica('imagenes/logo-letra.png');

        $data = [
            'escuela' => $escuela,
            'nivel' => $nivel,
            'generacion' => $generacion,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,

            'alumnos' => $alumnos,
            'materias' => $materias,

            'nombreDocente' => $nombreDocente,
            'periodoNumero' => $periodoNumero,
            'periodoTexto' => $periodoTexto,
            'mesAsistencia' => $mesAsistencia,
            'cicloEscolar' => $cicloEscolar,

            'logoIzquierdo' => $logoIzquierdo,
            'logoDerecho' => $logoDerecho,
            'marcaAgua' => $marcaAgua,

            'turno' => $request->input('turno', 'Matutino'),
            'fechaInicio' => $request->input('fecha_inicio'),
            'fechaFin' => $request->input('fecha_fin'),

            'tipo_descarga' => $tipo_descarga,
            'opcion_descarga' => $opcion_descarga,

            'mostrarMotivo' => $mostrarMotivo,
        ];

        /*
        |--------------------------------------------------------------------------
        | Selecciono la vista PDF según el tipo de descarga
        |--------------------------------------------------------------------------
        */

        $vistaPdf = match ($tipo_descarga) {
            'evaluacion' => 'pdf.lista_evaluacion',
            'asistencia' => 'pdf.lista_asistencia',
            'grupo' => 'pdf.lista_grupo',

            'formatos' => match ($opcion_descarga) {
                'sece' => 'pdf.listas.formatos.sece',
                'sece_interna' => 'pdf.listas.formatos.sece-interna',
                'lista_boletas' => 'pdf.listas.formatos.lista-boletas',
                'personalizadores' => 'pdf.listas.formatos.personalizadores',
                'etiquetas' => 'pdf.listas.formatos.etiquetas',
                default => abort(404, 'El formato seleccionado no existe.'),
            },

            default => abort(404, 'El tipo de descarga seleccionado no existe.'),
        };


        $nombreTipo = match ($tipo_descarga) {
            'evaluacion' => 'lista-evaluacion',
            'asistencia' => 'lista-asistencia',
            'grupo' => 'lista-grupo',

            'formatos' => match ($opcion_descarga) {
                'sece' => 'formato-sece',
                'sece_interna' => 'formato-sece-interna',
                'lista_boletas' => 'lista-boletas',
                'personalizadores' => 'personalizadores',
                'etiquetas' => 'etiquetas',
                default => 'formato',
            },

            default => 'lista',
        };

        $nombreGrado = Grado::query()->where('id', $grado_id)->value('nombre') ?? 'grado';

        $nombreArchivo = $nombreTipo
            . '-' . $nivel->slug
            . '-grado-' . $grado->nombre
            . '-grupo-' . $grupo->nombre
            . '.pdf';

        /*
        |--------------------------------------------------------------------------
        | Genero PDF
        |--------------------------------------------------------------------------
        */

        // Defino la orientación del PDF según el tipo y opción seleccionada.
        $orientacionPdf = match (true) {
            // Lista de grupo como la imagen que mandaste.
            $tipo_descarga === 'grupo' => 'portrait',

            // Evaluación por periodo.
            $tipo_descarga === 'evaluacion' => 'landscape',

            // Asistencia por periodo.
            $tipo_descarga === 'asistencia' => 'landscape',

            // Formatos específicos.
            $tipo_descarga === 'formatos' && $opcion_descarga === 'sece' => 'landscape',
            $tipo_descarga === 'formatos' && $opcion_descarga === 'sece_interna' => 'landscape',
            $tipo_descarga === 'formatos' && $opcion_descarga === 'lista_boletas' => 'portrait',
            $tipo_descarga === 'formatos' && $opcion_descarga === 'personalizadores' => 'portrait',
            $tipo_descarga === 'formatos' && $opcion_descarga === 'etiquetas' => 'portrait',

            default => 'portrait',
        };

        return Pdf::loadView($vistaPdf, $data)
            ->setPaper('letter', $orientacionPdf)
            ->stream($nombreArchivo);
    }


    public function boleta_calificaciones_pdf(\Illuminate\Http\Request $request)
    {
        $slugNivel = $request->input('slug_nivel');
        $generacionId = $request->input('generacion_id');
        $gradoId = $request->input('grado_id');
        $grupoId = $request->input('grupo_id');
        $periodoId = $request->input('periodo_id');
        $inscripcionId = $request->input('inscripcion_id');

        $periodoBasicaId = $request->input('periodo_basica_id');
        $parcialBachilleratoId = $request->input('parcial_bachillerato_id');
        $semestreId = $request->input('semestre_id');

        if (
            empty($slugNivel) ||
            empty($generacionId) ||
            empty($gradoId) ||
            empty($grupoId) ||
            empty($periodoId) ||
            empty($inscripcionId)
        ) {
            abort(422, 'Los parámetros slug_nivel, generacion_id, grado_id, grupo_id, periodo_id e inscripcion_id son obligatorios.');
        }

        $escuela = \App\Models\Escuela::first();

        $nivel = \App\Models\Nivel::query()
            ->where('slug', $slugNivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $slugNivelNormalizado = mb_strtolower((string) $nivel->slug);
        $esBachillerato = $slugNivelNormalizado === 'bachillerato';

        $grado = \App\Models\Grado::query()->find($gradoId);

        if (!$grado) {
            abort(404, 'Grado no encontrado.');
        }

        $grupo = \App\Models\Grupo::query()->find($grupoId);

        if (!$grupo) {
            abort(404, 'Grupo no encontrado.');
        }

        $semestre = null;

        if ($esBachillerato) {
            if (empty($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $semestre = \App\Models\Semestre::query()->find($semestreId);

            if (!$semestre) {
                abort(404, 'Semestre no encontrado.');
            }
        }

        $periodo = \App\Models\Periodos::query()
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
            abort(404, 'Periodo no encontrado.');
        }

        $nombrePeriodo = 'Periodo seleccionado';

        if (!$esBachillerato && $periodo?->periodoBasica?->descripcion) {
            $nombrePeriodo = $periodo->periodoBasica->descripcion;
        }

        if ($esBachillerato && $periodo?->parcialBachillerato?->descripcion) {
            $nombrePeriodo = $periodo->parcialBachillerato->descripcion;
        }

        $cicloEscolarTexto = $periodo->cicloEscolar
            ? ($periodo->cicloEscolar->inicio_anio . '-' . $periodo->cicloEscolar->fin_anio)
            : '—';

        $inscripcionQuery = \App\Models\Inscripcion::query()
            ->with([
                'grado:id,nombre',
                'grupo:id,nombre',
                'semestre:id,numero',
            ])
            ->where('id', $inscripcionId)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacionId)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

        if ($esBachillerato) {
            $inscripcionQuery->where('semestre_id', $semestre->id);
        }

        $inscripcion = $inscripcionQuery->first();

        if (!$inscripcion) {
            abort(404, 'Inscripción no encontrada para el contexto seleccionado.');
        }

        $nombreAlumno = trim(
            ($inscripcion->nombre ?? '') . ' ' .
                ($inscripcion->apellido_paterno ?? '') . ' ' .
                ($inscripcion->apellido_materno ?? '')
        );

        /*
         * Materias calificables del grupo.
         */
        $queryMaterias = \App\Models\AsignacionMateria::query()
            ->where('nivel_id', $nivel->id)
            ->where('grupo_id', $grupo->id)
            ->where('calificable', 1);

        if (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'grado_id')) {
            $queryMaterias->where('grado_id', $grado->id);
        }

        if ($esBachillerato) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'semestre_id')) {
                $queryMaterias->where('semestre_id', $semestre->id);
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'semestre')) {
                $queryMaterias->where('semestre', $semestre->id);
            }
        } else {
            if (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'semestre_id')) {
                $queryMaterias->whereNull('semestre_id');
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'semestre')) {
                $queryMaterias->whereNull('semestre');
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('asignacion_materias', 'orden')) {
            $queryMaterias->orderBy('orden');
        }

        $queryMaterias->orderBy('materia');

        $materias = $queryMaterias->get();

        $idsMaterias = $materias->pluck('id')->values()->all();

        /*
         * Calificaciones del alumno en el periodo/parcial seleccionado.
         */
        $queryCalificaciones = \App\Models\Calificacion::query()
            ->where('inscripcion_id', $inscripcion->id)
            ->whereIn('asignacion_materia_id', $idsMaterias)
            ->where('periodo_id', $periodo->id);

        if (\Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'nivel_id')) {
            $queryCalificaciones->where('nivel_id', $nivel->id);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'generacion_id')) {
            $queryCalificaciones->where('generacion_id', $generacionId);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'grado_id')) {
            $queryCalificaciones->where('grado_id', $grado->id);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'grupo_id')) {
            $queryCalificaciones->where('grupo_id', $grupo->id);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('calificaciones', 'semestre_id')) {
            if ($esBachillerato) {
                $queryCalificaciones->where('semestre_id', $semestre->id);
            } else {
                $queryCalificaciones->whereNull('semestre_id');
            }
        }

        $calificacionesMap = $queryCalificaciones->get()
            ->keyBy('asignacion_materia_id');

        /*
         * Número de materias para promediar.
         */
        $queryMateriaPromediar = \App\Models\MateriaPromediar::query()
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

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

            $esNumerica = is_numeric($valor);
            $valorNumerico = null;
            $estado = 'Sin captura';
            $porcentaje = 0;

            if ($esNumerica) {
                $numero = (float) $valor;

                if ($numero >= 0 && $numero <= 10) {
                    $valorNumerico = $numero;
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

        $tipoBoleta = $esBachillerato ? 'BOLETA PARCIAL' : 'BOLETA DE PERIODO';

        /*
         * Logos.
         */
        $logoIzquierdo = public_path('imagenes/logo-letra.png');
        $logoDerecho = public_path('imagenes/logo-secundario-moctezuma.png');

        $logoIzquierdo = file_exists($logoIzquierdo) ? $logoIzquierdo : null;
        $logoDerecho = file_exists($logoDerecho) ? $logoDerecho : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.boleta_calificaciones_pdf', [
            'titulo' => $tipoBoleta,
            'escuela' => $escuela,
            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'esBachillerato' => $esBachillerato,
            'periodo' => $periodo,
            'nombrePeriodo' => $nombrePeriodo,
            'cicloEscolarTexto' => $cicloEscolarTexto,
            'inscripcion' => $inscripcion,
            'nombreAlumno' => $nombreAlumno,
            'filasMaterias' => $filasMaterias,
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






    private function esBachillerato($nivel): bool
    {
        return (int) $nivel->id === 4 || $nivel->slug === 'bachillerato';
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

    private function nombrePersona($persona): string
    {
        return trim(
            ($persona->nombre ?? '') . ' ' .
                ($persona->apellido_paterno ?? '') . ' ' .
                ($persona->apellido_materno ?? '')
        );
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
