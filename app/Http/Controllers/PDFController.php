<?php

namespace App\Http\Controllers;

use App\Models\cicloEscolar;
use App\Models\Dia;
use App\Models\Director;
use App\Models\Grado;
use App\Models\Grupo;
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
        $slug_nivel = $request->input('slug_nivel');
        $grado_id = $request->input('grado_id');
        $grupo_id = $request->input('grupo_id');
        $semestre_id = $request->input('semestre_id');

        if (empty($slug_nivel) || empty($grado_id) || empty($grupo_id)) {
            abort(422, 'Los parámetros slug_nivel, grado_id y grupo_id son obligatorios.');
        }

        // Buscar el nivel por slug
        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        // Buscar el grado
        $grado = Grado::query()->find($grado_id);
        if (!$grado) {
            abort(404, 'Grado no encontrado.');
        }

        // Buscar el grupo
        $grupo = Grupo::query()->find($grupo_id);
        if (!$grupo) {
            abort(404, 'Grupo no encontrado.');
        }

        // Detectar si el nivel es bachillerato
        $esBachillerato = str($nivel->slug)->lower()->contains('bachillerato');

        // Si es bachillerato, el semestre será obligatorio
        $semestre = null;
        if ($esBachillerato) {
            if (empty($semestre_id)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $semestre = Semestre::query()->find($semestre_id);

            if (!$semestre) {
                abort(404, 'Semestre no encontrado.');
            }
        }

        // Obtener los días del nivel actual y evitar repetidos
        $dias = Dia::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->get()
            ->unique('dia')
            ->values();

        // Obtener el horario con relaciones
        $horarios = Horario::query()
            ->with([
                'hora',
                'dia',
                'asignacionMateria.profesor',
            ])
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id)
            ->when(
                $esBachillerato,
                fn($query) => $query->where('semestre_id', $semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->get();

        // Obtener las horas reales que están relacionadas en el horario
        $horas = $horarios
            ->pluck('hora')
            ->filter()
            ->unique('id')
            ->sortBy([
                ['orden', 'asc'],
                ['hora_inicio', 'asc'],
            ])
            ->values();

        // Crear una matriz para consultar rápido por hora y día
        $horarioPorCelda = $horarios->keyBy(function ($item) {
            return $item->hora_id . '-' . $item->dia_id;
        });

        $pdf = Pdf::loadView('pdf.horarios_pdf', [
            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => $semestre,
            'esBachillerato' => $esBachillerato,
            'dias' => $dias,
            'horas' => $horas,
            'horarioPorCelda' => $horarioPorCelda,
            'fecha_impresion' => now(),
        ])->setPaper('letter', 'landscape');

        $nombreArchivo = 'horario-' .
            str($nivel->nombre)->slug() . '-' .
            str($grado->nombre)->slug() . '-' .
            str($grupo->nombre)->slug();

        if ($semestre) {
            $nombreArchivo .= '-' . str($semestre->semestre ?? $semestre->nombre ?? 'semestre')->slug();
        }

        $nombreArchivo .= '.pdf';

        return $pdf->stream($nombreArchivo);
    }



}
