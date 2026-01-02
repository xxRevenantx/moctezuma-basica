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
        // Lógica para generar y devolver el PDF de reanudaciones de labores
        $nivel_id = $request->input("nivel_id");
        $tipo_reanudacion = $request->input("tipo_reanudacion");
        $fecha_director = $request->input("fecha_director");
        $fecha_docente = $request->input("fecha_docente");
        $ciclo_escolar = $request->input("ciclo_escolar");

        $copias = $request->input("copias");

        $asignacionesNivel = PersonaNivel::where("nivel_id", $nivel_id)
            ->with("persona.personaRoles.rolePersona")
            ->orderBy('orden', 'asc')
            ->get();

        $delegado = Director::where("identificador", "delegado-servicios-educativos-tierra-caliente")->first();

        //DIRECTORA Preescolar
        $directoraPreescolar = Director::where("identificador", "directora-general")->first();

        // DIRECTORA PRIMARIA Y SECUNDARIA
        $directoraPS = Director::where("identificador", "directora")->first();

        $supervisorPreescolar = Director::where("identificador", "supervisor-preescolar")->first();
        $supervisorPrimaria = Director::where("identificador", "supervisor-primaria")->first();
        $supervisorSecundaria = Director::where("identificador", "supervisor-secundaria")->first();


        $directorAdministracion = Director::where("identificador", "director-general-administracion")->first();
        $directorMagisterio = Director::where("identificador","director-magisterio-estatal")->first();


        $cicloEscolar = cicloEscolar::find($ciclo_escolar);

            // Verificar que exista el nivel_id y que el nivel exista
            if (empty($nivel_id)) {
                abort(422, 'El parámetro nivel_id es obligatorio.');
            }

            $nivel = Nivel::find($nivel_id);
            if (!$nivel) {
                abort(404, 'Nivel no encontrado.');
            }

         $data = [
                "asignacionesNivel" => $asignacionesNivel,
                "fecha_director" => $fecha_director,
                "fecha_docente" => $fecha_docente,
                'nivel' => Nivel::find($nivel_id),
                'escuela' => \App\Models\Escuela::first(),
                'delegado' => $delegado,
                'directoraPreescolar' => $directoraPreescolar,
                'directoraPS' => $directoraPS,
                'cicloEscolar' => $cicloEscolar,
                'supervisorPreescolar' => $supervisorPreescolar,
                'supervisorPrimaria' => $supervisorPrimaria,
                'supervisorSecundaria' => $supervisorSecundaria,
                'copias' => $copias,
                'directorAdministracion' => $directorAdministracion,
                'directorMagisterio' => $directorMagisterio,
            ];



        if($tipo_reanudacion == "1"){
            // RENUDACIÓN DE RECESO DE CLASES

             $pdf = Pdf::loadView('pdf.reanudaciones_receso', $data)->setPaper('letter', 'portrait')
                 ->setOption([
                'fontDir'     => public_path('/fonts'),
                'fontCache'   => public_path('/fonts'),
            ]);
            $nombreArchivo = "OFICIOS_DE_REANUDACIONES_DE_RECESO_DE_CLASES_".mb_strtoupper($nivel->nombre)."_".$cicloEscolar->inicio_anio."-".$cicloEscolar->fin_anio.".pdf";

             return $pdf->stream($nombreArchivo);


        } elseif($tipo_reanudacion == "2"){
            // REANUDACIÓN DE INVERNO
               $pdf = Pdf::loadView('pdf.reanudaciones_invierno', $data)->setPaper('letter', 'portrait')
                 ->setOption([
                'fontDir'     => public_path('/fonts'),
                'fontCache'   => public_path('/fonts'),
            ]);
            $nombreArchivo = "OFICIOS_DE_REANUDACIONES_DE_INVIERNO_".mb_strtoupper($nivel->nombre)."_".$cicloEscolar->inicio_anio."-".$cicloEscolar->fin_anio.".pdf";
             return $pdf->stream($nombreArchivo);

        } elseif($tipo_reanudacion == "3"){
            // REANUDACIÓN DE PRIMAVERA
                 $pdf = Pdf::loadView('pdf.reanudaciones_primavera', $data)->setPaper('letter', 'portrait')
                 ->setOption([
                'fontDir'     => public_path('/fonts'),
                'fontCache'   => public_path('/fonts'),
            ]);
            $nombreArchivo = "OFICIOS_DE_REANUDACIONES_DE_PRIMAVERA_".mb_strtoupper($nivel->nombre)."_".$cicloEscolar->inicio_anio."-".$cicloEscolar->fin_anio.".pdf";
             return $pdf->stream($nombreArchivo);

        }




        $data = [

        ];


    }
}
