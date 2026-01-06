<?php

namespace App\Http\Controllers;

use App\Models\cicloEscolar;
use App\Models\Director;
use App\Models\Nivel;
use App\Models\PersonaNivel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PDFController extends Controller
{

    // Generar oficios de reanudaciones de labores
public function reanudaciones(Request $request)
{
    $nivel_id         = $request->input("nivel_id");
    $tipo_reanudacion = $request->input("tipo_reanudacion");
    $fecha_director   = $request->input("fecha_director");
    $fecha_docente    = $request->input("fecha_docente");
    $ciclo_escolar    = $request->input("ciclo_escolar");
    $copias           = $request->input("copias");

    if (empty($nivel_id)) {
        abort(422, 'El parámetro nivel_id es obligatorio.');
    }

    $nivel = Nivel::where("id", $nivel_id)->with(['director', 'supervisor'])->first();
    if (! $nivel) {
        abort(404, 'Nivel no encontrado.');
    }

    $delegado = Director::where("identificador", "delegado-servicios-educativos-tierra-caliente")->first();
    $directorAdministracion = Director::where("identificador", "director-general-administracion")->first();
    $directorMagisterio     = Director::where("identificador", "director-magisterio-estatal")->first();

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
        "asignacionesNivel"      => $asignacionesNivel,
        "fecha_director"         => $fecha_director,
        "fecha_docente"          => $fecha_docente,
        'nivel'                  => $nivel,
        'escuela'                => \App\Models\Escuela::first(),
        'delegado'               => $delegado,
        'cicloEscolar'           => $cicloEscolar,
        'copias'                 => $copias,
        'directorAdministracion' => $directorAdministracion,
        'directorMagisterio'     => $directorMagisterio,
    ];

    if ($tipo_reanudacion == "1") {
        $pdf = Pdf::loadView('pdf.reanudaciones_receso', $data)
            ->setPaper('letter', 'portrait')
            ->setOption([
                'fontDir'   => public_path('/fonts'),
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
                'fontDir'   => public_path('/fonts'),
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
                'fontDir'   => public_path('/fonts'),
                'fontCache' => public_path('/fonts'),
            ]);

        $nombreArchivo = "OFICIOS_DE_REANUDACIONES_DE_PRIMAVERA_" .
            mb_strtoupper($nivel->nombre) . "_" .
            $cicloEscolar->inicio_anio . "-" . $cicloEscolar->fin_anio . ".pdf";

        return $pdf->stream($nombreArchivo);
    }

    abort(422, 'Tipo de reanudación inválido.');
}



}
