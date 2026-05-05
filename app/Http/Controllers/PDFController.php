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
        $logoDerecho = public_path('storage/logos/' . $nivel->logo);

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
            ->with('generacion')
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
            $ultimoCiclo = cicloEscolar::query()
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
            ->where('nivel_id', $nivel->id)
            ->where('grupo_id', $grupo->id)
            ->where('calificable', 1);

        if (Schema::hasColumn('asignacion_materias', 'grado_id')) {
            $queryMaterias->where('grado_id', $grado->id);
        }

        $this->aplicarFiltroSemestreAsignacion($queryMaterias, $esBachillerato, $semestre?->id);

        if (Schema::hasColumn('asignacion_materias', 'orden')) {
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
        |--------------------------------------------------------------------------
        | Inscripciones
        |--------------------------------------------------------------------------
        */

        $queryInscripciones = Inscripcion::query()
            ->with(['grado:id,nombre', 'grupo:id,nombre', 'semestre:id,numero'])
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

        if ($esBachillerato) {
            $queryInscripciones->where('semestre_id', $semestre->id);
        } else {
            $queryInscripciones->whereNull('semestre_id');
        }

        if (Schema::hasColumn('inscripciones', 'activo')) {
            $queryInscripciones->where('activo', 1);
        }

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
                    'grupo' => $item->grupo?->nombre ?? '—',
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
            $numeroMateriasPromediar = collect($materias)
                ->filter(fn($materia) => (int) ($materia['extra'] ?? 0) === 0)
                ->count();
        }

        /*
        |--------------------------------------------------------------------------
        | Promedios por alumno
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Promedios por materia
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Promedio general del grupo
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Estadísticas generales
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Periodos por materia
        |--------------------------------------------------------------------------
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

        $inscripcionQuery = Inscripcion::query()
            ->with([
                'grado:id,nombre',
                'grupo:id,nombre',
                'semestre:id,numero',
            ])
            ->where('id', $inscripcionId)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id)
            ->where('activo', 1);

        if ($esBachillerato) {
            $inscripcionQuery->where('semestre_id', $semestre->id);
        } else {
            $inscripcionQuery->whereNull('semestre_id');
        }

        $inscripcion = $inscripcionQuery->first();

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
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id)
            ->where('calificable', 1);

        $this->aplicarFiltroSemestreAsignacion($queryMaterias, $esBachillerato, $semestre?->id);

        if (Schema::hasColumn('asignacion_materias', 'orden')) {
            $queryMaterias->orderBy('orden');
        }

        $materias = $queryMaterias
            ->orderBy('id')
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
        | Promedio
        |--------------------------------------------------------------------------
        */

        $queryMateriaPromediar = MateriaPromediar::query()
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

        if ($numeroMateriasPromediar > 0 && $capturadasNumericas > 0) {
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

        $inscripcionQuery = Inscripcion::query()
            ->with([
                'grado:id,nombre',
                'grupo:id,nombre',
                'semestre:id,numero',
            ])
            ->where('id', $inscripcionId)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

        if (Schema::hasColumn('inscripciones', 'activo')) {
            $inscripcionQuery->where('activo', 1);
        }

        if ($esBachillerato) {
            $inscripcionQuery->where('semestre_id', $semestre->id);
        } else {
            $inscripcionQuery->whereNull('semestre_id');
        }

        $inscripcion = $inscripcionQuery->first();

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
            ->where('nivel_id', $nivel->id)
            ->where('grupo_id', $grupo->id)
            ->where('calificable', 1);

        if (Schema::hasColumn('asignacion_materias', 'grado_id')) {
            $queryMaterias->where('grado_id', $grado->id);
        }

        $this->aplicarFiltroSemestreAsignacion($queryMaterias, $esBachillerato, $semestre?->id);

        if (Schema::hasColumn('asignacion_materias', 'orden')) {
            $queryMaterias->orderBy('orden');
        }

        $materias = $queryMaterias
            ->orderBy('id')
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

        $queryInscripcionesLugar = Inscripcion::query()
            ->with(['grado:id,nombre', 'grupo:id,nombre', 'semestre:id,numero'])
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

        if (Schema::hasColumn('inscripciones', 'activo')) {
            $queryInscripcionesLugar->where('activo', 1);
        }

        if ($esBachillerato) {
            $queryInscripcionesLugar->where('semestre_id', $semestre->id);
        } else {
            $queryInscripcionesLugar->whereNull('semestre_id');
        }

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
                    'grupo' => $item->grupo?->nombre ?? '—',
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

        $logoIzquierdo = $this->imagenBase64Publica('imagenes/logo-letra.png');
        $logoDerecho = $this->imagenBase64Publica('storage/logos/' . $nivel->logo);
        $marcaAgua = $this->imagenBase64Publica('imagenes/logo-letra.png');

        /*
        |--------------------------------------------------------------------------
        | Tipo de diploma
        |--------------------------------------------------------------------------
        */

        $tipoDiploma = $esBachillerato
            ? 'DIPLOMA PARCIAL'
            : 'DIPLOMA DE PERIODO';

        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */


        $data = [
            'titulo' => $tipoDiploma,
            'escuela' => $escuela,

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

        $nombreArchivo = 'DIPLOMA_' .
            str_replace(' ', '_', mb_strtoupper($nombreAlumno ?: 'ALUMNO')) .
            '_' . str_replace(' ', '_', mb_strtoupper($nombrePeriodo)) .
            '.pdf';

        return Pdf::loadView('pdf.diploma_pdf', $data)
            ->setPaper('letter', 'landscape')
            ->stream($nombreArchivo);
    }


    // LISTAS PDF
    public function lista_pdf(Request $request, string $slug_nivel)
    {
        $generacionId = $request->integer('generacion_id');
        $gradoId = $request->integer('grado_id');
        $grupoId = $request->integer('grupo_id');
        $semestreId = $request->integer('semestre_id');

        $tipoDescarga = $request->input('tipo_descarga', 'grupo');
        $opcionDescarga = $request->input('opcion_descarga', 'primer_periodo');

        /*
        |--------------------------------------------------------------------------
        | Nivel
        |--------------------------------------------------------------------------
        */

        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        $esBachillerato = $this->esBachillerato($nivel);
        $esSecundaria = $this->esSecundaria($nivel);
        $esPrimaria = (int) $nivel->id === 2 || $nivel->slug === 'primaria';

        /*
        |--------------------------------------------------------------------------
        | Secundaria y bachillerato no usan lista de evaluación ni asistencia
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Opciones permitidas
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Validación de filtros principales
        |--------------------------------------------------------------------------
        */

        if (blank($generacionId) || blank($gradoId) || blank($grupoId)) {
            abort(422, 'Los parámetros generacion_id, grado_id y grupo_id son obligatorios.');
        }

        if ($esBachillerato && blank($semestreId)) {
            abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
        }

        /*
        |--------------------------------------------------------------------------
        | Datos principales
        |--------------------------------------------------------------------------
        */

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
        | Alumnos activos
        |--------------------------------------------------------------------------
        */

        $alumnosQuery = Inscripcion::query()
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id)
            ->where('activo', 1);

        if ($esBachillerato) {
            $alumnosQuery->where('semestre_id', $semestre->id);
        } else {
            $alumnosQuery->whereNull('semestre_id');
        }

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

        /*
/*
|--------------------------------------------------------------------------
| Materias calificables
|--------------------------------------------------------------------------
| Para primaria en lista de evaluación:
| - Se muestran todas las materias con calificable = 1.
| - Solo Cálculo mental, Caligrafía y Lectura usarán AC / ED / RA.
| - Las demás materias se marcarán como PROMEDIA.
*/

        $materiasQuery = AsignacionMateria::query()
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id)
            ->where('calificable', 1);

        $this->aplicarFiltroSemestreAsignacion($materiasQuery, $esBachillerato, $semestre?->id);

        if (Schema::hasColumn('asignacion_materias', 'orden')) {
            $materiasQuery->orderBy('orden');
        }

        $materias = $materiasQuery
            ->orderBy('id')
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

        $profesorId = $materias
            ->pluck('profesor_id')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        $docente = null;

        if ($profesorId) {
            $docente = Persona::query()
                ->where('id', $profesorId)
                ->first();
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
            $datosPeriodo = $this->obtenerPeriodoPdf(
                nivel: $nivel,
                generacion: $generacion,
                semestre: $semestre,
                request: $request
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
            . '-grado-' . $grado->nombre
            . ($esBachillerato && $semestre ? '-semestre-' . $semestre->numero : '')
            . '-grupo-' . $grupo->nombre
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

        return Pdf::loadView($vistaPdf, $data)
            ->setPaper('letter', $orientacionPdf)
            ->stream($nombreArchivo);
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
        if (Schema::hasColumn('asignacion_materias', 'semestre_id')) {
            if ($esBachillerato) {
                $query->where('semestre_id', $semestreId);
            } else {
                $query->whereNull('semestre_id');
            }

            return;
        }

        if (Schema::hasColumn('asignacion_materias', 'semestre')) {
            if ($esBachillerato) {
                $query->where('semestre', $semestreId);
            } else {
                $query->whereNull('semestre');
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
