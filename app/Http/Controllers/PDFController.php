<?php

namespace App\Http\Controllers;

use App\Models\cicloEscolar;
use App\Models\Dia;
use App\Models\Director;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Nivel;
use App\Models\PersonaNivel;
use App\Models\Semestre;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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
        $slugNivel = $request->input('slug_nivel');
        $gradoId = $request->input('grado_id');
        $grupoId = $request->input('grupo_id');
        $semestreId = $request->input('semestre_id');

        $escuela = \App\Models\Escuela::first();

        if (empty($slugNivel) || empty($gradoId) || empty($grupoId)) {
            abort(422, 'Los parámetros slug_nivel, grado_id y grupo_id son obligatorios.');
        }

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

        $generacionId = $grupo->generacion_id;

        if (empty($generacionId)) {
            abort(422, 'El grupo seleccionado no tiene una generación asignada.');
        }

        $slugNivelNormalizado = mb_strtolower(trim($nivel->slug ?? ''), 'UTF-8');
        $esBachillerato = $slugNivelNormalizado === 'bachillerato';

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

        $cicloEscolar = \App\Models\CicloEscolar::query()
            ->orderByDesc('id')
            ->first();

        $dias = \App\Models\Dia::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->unique('dia')
            ->values();

        $horas = \App\Models\Hora::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get();

        $consultaHorarios = \App\Models\Horario::query()
            ->with([
                'dia',
                'hora',
                'generacion',
                'asignacionMateria',
                'asignacionMateria.profesor',
            ])
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('generacion_id', $generacionId)
            ->where('grupo_id', $grupo->id);

        if ($esBachillerato) {
            $consultaHorarios->where('semestre_id', $semestre->id);
        } else {
            $consultaHorarios->whereNull('semestre_id');
        }

        $horarios = $consultaHorarios->get();

        $horarioPorCelda = $horarios->keyBy(function ($item) {
            return $item->hora_id . '-' . $item->dia_id;
        });


        $profesorTitular = null;

        $mostrarProfesorTitular = in_array(
            $slugNivelNormalizado,
            ['preescolar', 'primaria'],
            true
        );

        if ($mostrarProfesorTitular) {
            $personalGrupo = \App\Models\PersonaNivel::query()
                ->select([
                    'id',
                    'persona_id',
                    'nivel_id',
                    'ingreso_seg',
                    'ingreso_sep',
                    'ingreso_ct',
                    'orden',
                ])
                ->with([
                    'persona:id,titulo,nombre,apellido_paterno,apellido_materno,genero',
                    'detalles' => function ($q) use ($grupo) {
                        $q->select([
                            'id',
                            'persona_nivel_id',
                            'persona_role_id',
                            'grado_id',
                            'grupo_id',
                            'orden',
                        ])
                            ->where('grupo_id', $grupo->id)
                            ->with([
                                'grado:id,nombre,nivel_id',
                                'grupo:id,nombre,nivel_id,grado_id,generacion_id',
                            ])
                            ->orderBy('orden')
                            ->orderBy('id');
                    },
                ])
                ->where('nivel_id', $nivel->id)
                ->whereHas('detalles', function ($q) use ($grupo, $generacionId) {
                    $q->where('grupo_id', $grupo->id)
                        ->whereHas('grupo', function ($qq) use ($generacionId) {
                            $qq->where('generacion_id', $generacionId);
                        });
                })
                ->orderBy('orden')
                ->orderBy('id')
                ->get();

            $personalTitular = $personalGrupo->first();

            if ($personalTitular && $personalTitular->persona) {
                $persona = $personalTitular->persona;

                $profesorTitular = trim(
                    ($persona->titulo ? $persona->titulo . ' ' : '') .
                        ($persona->nombre ?? '') . ' ' .
                        ($persona->apellido_paterno ?? '') . ' ' .
                        ($persona->apellido_materno ?? '')
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

        $imagenNivel = $imagenesPorNivel[$slugNivelNormalizado] ?? null;

        $logoIzquierdo = file_exists($logoIzquierdo) ? $logoIzquierdo : null;
        $logoDerecho = file_exists($logoDerecho) ? $logoDerecho : 'storage/logos/' . $nivel->logo;
        $imagenNivel = ($imagenNivel && file_exists($imagenNivel)) ? $imagenNivel : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.horarios_pdf', [
            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'generacion_id' => $generacionId,
            'semestre' => $semestre,
            'esBachillerato' => $esBachillerato,
            'dias' => $dias,
            'horas' => $horas,
            'horarioPorCelda' => $horarioPorCelda,
            'fecha_impresion' => now(),
            'logo_izquierdo' => $logoIzquierdo,
            'logo_derecho' => $logoDerecho,
            'imagen_nivel' => $imagenNivel,
            'profesor_titular' => $profesorTitular,
            'ciclo_escolar' => $cicloEscolar,
            'escuela' => $escuela,
        ])->setPaper('letter', 'portrait');



        $nombreArchivo = 'Horario_de_' .
            ($grado->nombre ?? 'grado') . '°_grado_' .
            ($nivel->nombre ?? 'nivel') . '_' .
            ($grupo->nombre ?? 'grupo') . '_' .
            'Generacion_' . ($grupo->generacion->anio_ingreso ?? 'generacion') . '_' . ($grupo->generacion->anio_egreso ?? 'egreso') .
            ($esBachillerato && $semestre ? '_semestre_' . $semestre->id : '') .
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
}
