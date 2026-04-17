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

        if (empty($slugNivel) || empty($gradoId) || empty($grupoId)) {
            abort(422, 'Los parámetros slug_nivel, grado_id y grupo_id son obligatorios.');
        }

        // Obtener nivel
        $nivel = Nivel::where('slug', $slugNivel)->first();

        if (!$nivel) {
            abort(404, 'Nivel no encontrado.');
        }

        // Obtener grado
        $grado = Grado::find($gradoId);

        if (!$grado) {
            abort(404, 'Grado no encontrado.');
        }

        // Obtener grupo
        $grupo = Grupo::find($grupoId);

        if (!$grupo) {
            abort(404, 'Grupo no encontrado.');
        }

        // Validar si el nivel es bachillerato
        $esBachillerato = mb_strtolower(trim($nivel->slug ?? ''), 'UTF-8') === 'bachillerato';

        // Obtener semestre solo si es bachillerato
        $semestre = null;

        if ($esBachillerato) {
            if (empty($semestreId)) {
                abort(422, 'El parámetro semestre_id es obligatorio para bachillerato.');
            }

            $semestre = Semestre::find($semestreId);

            if (!$semestre) {
                abort(404, 'Semestre no encontrado.');
            }
        }

        // Obtener días
        $dias = Dia::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('id')
            ->get();

        // Obtener horas del nivel y grado
        $horas = Hora::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get();

        // Consulta base de horarios
        $consultaHorarios = Horario::query()
            ->with([
                'dia',
                'hora',
                'asignacionMateria',
                'asignacionMateria.profesor',
            ])
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id);

        // Si tu tabla horarios tiene semestre_id, se filtra aquí
        if ($esBachillerato) {
            $consultaHorarios->where('semestre_id', $semestre->id);
        }

        $horarios = $consultaHorarios->get();

        // Crear mapa por celda: hora_id-dia_id
        $horarioPorCelda = $horarios->keyBy(function ($item) {
            return $item->hora_id . '-' . $item->dia_id;
        });

        // Obtener profesor titular si existe en la relación del grupo
        $profesorTitular = null;

        if (method_exists($grupo, 'profesor') && $grupo->profesor) {
            $profesorTitular = trim(
                ($grupo->profesor->nombre ?? '') . ' ' .
                ($grupo->profesor->apellido_paterno ?? '') . ' ' .
                ($grupo->profesor->apellido_materno ?? '')
            );
        }

        // Logos
        $logoIzquierdo = public_path('images/logo-centro-universitario-moctezuma.png');
        $logoDerecho = public_path('images/logo-secundario-moctezuma.png');

        // Imágenes por nivel
        $imagenesPorNivel = [
            'preescolar' => public_path('imagenes/personajes_preescolar.png'),
            'primaria' => public_path('imagenes/personajes_primaria.png'),
            'secundaria' => public_path('imagenes/personajes_secundaria.png'),
            'bachillerato' => public_path('imagenes/personajes_bachillerato.png'),
        ];

        $imagenNivel = $imagenesPorNivel[$nivel->slug] ?? null;

        // Validar existencia física de imágenes
        $logoIzquierdo = file_exists($logoIzquierdo) ? $logoIzquierdo : null;
        $logoDerecho = file_exists($logoDerecho) ? $logoDerecho : null;
        $imagenNivel = ($imagenNivel && file_exists($imagenNivel)) ? $imagenNivel : null;

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
            'logo_izquierdo' => $logoIzquierdo,
            'logo_derecho' => $logoDerecho,
            'imagen_nivel' => $imagenNivel,
            'profesor_titular' => $profesorTitular,
        ])->setPaper('letter', 'portrait');

        $nombreArchivo = 'horario-' .
            ($nivel->slug ?? 'nivel') . '-' .
            ($grado->id ?? 'grado') . '-' .
            ($grupo->id ?? 'grupo') .
            ($esBachillerato && $semestre ? '-semestre-' . $semestre->id : '') .
            '.pdf';

        return $pdf->stream($nombreArchivo);
    }




}
