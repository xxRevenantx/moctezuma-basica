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

    public function calificaciones_pdf(Request $request)
    {
        $slugNivel = $request->input('slug_nivel');
        $gradoId = $request->input('grado_id');
        $grupoId = $request->input('grupo_id');
        $periodoId = $request->input('periodo_id');
        $semestreId = $request->input('semestre_id');
        $busqueda = trim((string) $request->input('busqueda', ''));

        if (empty($slugNivel) || empty($gradoId) || empty($grupoId) || empty($periodoId)) {
            abort(422, 'Los parámetros slug_nivel, grado_id, grupo_id y periodo_id son obligatorios.');
        }

        $escuela = \App\Models\Escuela::first();

        $nivel = \App\Models\Nivel::query()
            ->where('slug', $slugNivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

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

        $periodo = \App\Models\Periodos::query()
            ->with('cicloEscolar')
            ->find($periodoId);

        if (!$periodo) {
            abort(404, 'Periodo no encontrado.');
        }

        $esBachillerato = (int) $nivel->id === 4 || mb_strtolower((string) $nivel->slug) === 'bachillerato';

        $semestre = null;
        $generacionId = $grupo->generacion_id ? (int) $grupo->generacion_id : null;

        if ($esBachillerato) {
            if (empty($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $semestre = \App\Models\Semestre::query()->find($semestreId);

            if (!$semestre) {
                abort(404, 'Semestre no encontrado.');
            }

            if ((int) $grupo->semestre_id !== (int) $semestre->id) {
                abort(422, 'El grupo seleccionado no pertenece al semestre indicado.');
            }
        }

        // Materias
        $queryMaterias = \App\Models\AsignacionMateria::query()
            ->where('nivel_id', $nivel->id)
            ->where('grupo_id', $grupo->id)
            ->where('calificable', 1)
            ->orderBy('orden')
            ->orderBy('materia');

        if ($esBachillerato) {
            $queryMaterias->where('semestre', $semestre->id);
        } else {
            $queryMaterias->where('grado_id', $grado->id)
                ->whereNull('semestre');
        }

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

        // Inscripciones
        $queryInscripciones = \App\Models\Inscripcion::query()
            ->with(['grado:id,nombre', 'grupo:id,nombre', 'semestre:id,numero'])
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

        if ($esBachillerato) {
            $queryInscripciones->where('semestre_id', $semestre->id)
                ->where('generacion_id', $generacionId);
        }

        if ($busqueda !== '') {
            $queryInscripciones->where(function ($q) use ($busqueda) {
                $q->where('matricula', 'like', "%{$busqueda}%")
                    ->orWhere(\Illuminate\Support\Facades\DB::raw("TRIM(CONCAT(nombre,' ',IFNULL(apellido_paterno,''),' ',IFNULL(apellido_materno,'')))"), 'like', "%{$busqueda}%");
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
                    'alumno' => trim($item->nombre . ' ' . ($item->apellido_paterno ?? '') . ' ' . ($item->apellido_materno ?? '')) ?: '—',
                    'grado' => $item->grado?->nombre ?? '—',
                    'grupo' => $item->grupo?->nombre ?? '—',
                    'semestre' => $item->semestre?->numero ?? '—',
                ];
            })
            ->values()
            ->toArray();

        // Calificaciones guardadas
        $idsInscripciones = collect($inscripciones)->pluck('inscripcion_id')->values()->all();
        $idsMaterias = collect($materias)->pluck('id')->values()->all();

        $calificaciones = [];

        if (!empty($idsInscripciones) && !empty($idsMaterias)) {
            $queryCalificaciones = \App\Models\Calificacion::query()
                ->whereIn('inscripcion_id', $idsInscripciones)
                ->whereIn('asignacion_materia_id', $idsMaterias)
                ->where('nivel_id', $nivel->id)
                ->where('grado_id', $grado->id)
                ->where('grupo_id', $grupo->id)
                ->where('periodo_id', $periodo->id);

            if ($esBachillerato) {
                $queryCalificaciones->where('semestre_id', $semestre->id)
                    ->where('generacion_id', $generacionId);
            }

            $calificaciones = $queryCalificaciones->get()
                ->mapWithKeys(function ($item) {
                    $clave = $item->inscripcion_id . '-' . $item->asignacion_materia_id;
                    return [$clave => strtoupper(trim((string) $item->calificacion))];
                })
                ->toArray();
        }

        // Número de materias para promediar
        $numeroMateriasPromediar = 0;

        if ($esBachillerato) {
            $registroPromedio = \App\Models\MateriaPromediar::query()
                ->where('nivel_id', $nivel->id)
                ->where('grado_id', $grado->id)
                ->where('grupo_id', $grupo->id)
                ->where('semestre_id', $semestre->id)
                ->first();

            $numeroMateriasPromediar = (int) ($registroPromedio?->numero_materias ?? 0);
        } else {
            $registroPromedio = \App\Models\MateriaPromediar::query()
                ->where('nivel_id', $nivel->id)
                ->where('grado_id', $grado->id)
                ->where('grupo_id', $grupo->id)
                ->whereNull('semestre_id')
                ->first();

            $numeroMateriasPromediar = (int) ($registroPromedio?->numero_materias ?? 0);
        }

        // Promedios
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

                if (is_numeric($valor)) {
                    $numero = (int) $valor;

                    if ($numero >= 0 && $numero <= 10) {
                        $suma += $numero;
                    }
                }
            }

            $promedio = $suma / $numeroMateriasPromediar;
            $promedios[$inscripcionId] = number_format($promedio, 1);
        }

        $slugNivelNormalizado = mb_strtolower((string) $nivel->slug);

        $logoIzquierdo = public_path('imagenes/logo-letra.png');
        $logoDerecho = public_path('imagenes/logo-secundario-moctezuma.png');

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
            'busqueda' => $busqueda,
            'materias' => $materias,
            'inscripciones' => $inscripciones,
            'calificaciones' => $calificaciones,
            'promedios' => $promedios,
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
        /*
        |--------------------------------------------------------------------------
        | Recibo los filtros seleccionados
        |--------------------------------------------------------------------------
        */

        $generacion_id = $request->integer('generacion_id');
        $grado_id = $request->integer('grado_id');
        $grupo_id = $request->integer('grupo_id');
        $semestre_id = $request->integer('semestre_id');

        $tipo_descarga = $request->input('tipo_descarga', 'evaluacion');
        $opcion_descarga = $request->input('opcion_descarga', 'primer_periodo');

        /*
        |--------------------------------------------------------------------------
        | Primero se genera únicamente lista de evaluación
        |--------------------------------------------------------------------------
        */

        if ($tipo_descarga !== 'evaluacion') {
            abort(404, 'Este formato todavía no está disponible.');
        }

        /*
        |--------------------------------------------------------------------------
        | Busco la información principal
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

        /*
        |--------------------------------------------------------------------------
        | Busco los alumnos activos
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Busco las materias calificables
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Busco el docente principal
        |--------------------------------------------------------------------------
        | Se toma el profesor que más se repite en las materias del grupo.
        */

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

        /*
        |--------------------------------------------------------------------------
        | Obtengo escuela
        |--------------------------------------------------------------------------
        */

        $escuela = DB::table('escuela')->first();

        /*
        |--------------------------------------------------------------------------
        | Armo datos del periodo
        |--------------------------------------------------------------------------
        */

        $periodoNumero = $this->numeroPeriodoEvaluacion($opcion_descarga);
        $periodoTexto = $this->textoPeriodoEvaluacion($opcion_descarga);

        $cicloEscolar = $generacion->anio_ingreso . '-' . $generacion->anio_egreso;

        /*
        |--------------------------------------------------------------------------
        | Logos e imagen de marca de agua
        |--------------------------------------------------------------------------
        | Si tus archivos están en otra ruta, solo cambia los nombres.
        */

        $logoIzquierdo = $this->imagenBase64Publica('storage/logos/' . $nivel->logo);
        $logoDerecho = $this->imagenBase64Publica('imagenes/logo-letra.png');
        $marcaAgua = $this->imagenBase64Publica('imagenes/logo-letra.png');

        /*
        |--------------------------------------------------------------------------
        | Datos para la vista
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

            'nombreDocente' => $nombreDocente,
            'periodoNumero' => $periodoNumero,
            'periodoTexto' => $periodoTexto,
            'cicloEscolar' => $cicloEscolar,

            'logoIzquierdo' => $logoIzquierdo,
            'logoDerecho' => $logoDerecho,
            'marcaAgua' => $marcaAgua,

            'turno' => $request->input('turno', 'Matutino'),
            'fechaInicio' => $request->input('fecha_inicio'),
            'fechaFin' => $request->input('fecha_fin'),
        ];

        /*
        |--------------------------------------------------------------------------
        | Genero el PDF
        |--------------------------------------------------------------------------
        */

        $nombreArchivo = 'lista-evaluacion-'
            . $nivel->slug
            . '-grado-' . $grado->id
            . '-grupo-' . $grupo->nombre
            . '.pdf';

        return Pdf::loadView('pdf.lista_evaluacion', $data)
            ->setPaper('letter', 'landscape')
            ->stream($nombreArchivo);
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
