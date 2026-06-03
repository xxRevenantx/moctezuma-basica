<?php

namespace App\Http\Controllers;

use App\Models\Constancia;
use App\Models\ConstanciaPlantilla;
use App\Models\Inscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class DocumentosPDFController extends Controller
{
    /**
     * Genera una constancia individual guardada en la base de datos.
     */
    public function constanciaPdf(Constancia $constancia)
    {

        // dd($constancia);

        $constancia->load([
            'alumno.nivel',
            'alumno.grado',
            'alumno.generacion',
            'alumno.grupo.asignacionGrupo',
            'alumno.ciclo',
            'plantilla',
        ]);
        // dd($constancia);

        $pdf = Pdf::loadView('pdf.constancia_estudios_pdf', [
            'constancia' => $constancia,
            'alumno' => $constancia->alumno,
            'plantilla' => $constancia->plantilla,
        ])->setPaper('letter', 'portrait');

        $nombreArchivo = Str::slug($constancia->folio, '_') . '.pdf';

        return $pdf->stream($nombreArchivo);
    }

    /**
     * Genera constancias masivas sin guardarlas en la base de datos.
     */
    public function constanciasZip()
    {
        $payload = session()->pull('constancias_zip_payload');

        if (!$payload || empty($payload['alumno_ids'])) {
            abort(404, 'No hay constancias para descargar.');
        }

        $plantilla = ConstanciaPlantilla::query()
            ->find($payload['plantilla_id']);

        if (!$plantilla) {
            abort(404, 'No se encontró la plantilla de constancia.');
        }

        $alumnos = Inscripcion::query()
            ->with([
                'nivel:id,nombre,cct',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'ciclo:id,ciclo',
            ])
            ->whereIn('id', $payload['alumno_ids'])
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        if ($alumnos->isEmpty()) {
            abort(404, 'No se encontraron alumnos para generar constancias.');
        }

        $carpetaBase = storage_path('app/temp');

        if (!File::exists($carpetaBase)) {
            File::makeDirectory($carpetaBase, 0755, true);
        }

        $nombreCarpeta = 'constancias_' . now()->format('Ymd_His');
        $carpetaTemporal = $carpetaBase . '/' . $nombreCarpeta;

        if (!File::exists($carpetaTemporal)) {
            File::makeDirectory($carpetaTemporal, 0755, true);
        }

        foreach ($alumnos as $alumno) {
            $alumnoArray = $this->formatearAlumno($alumno);

            $contenidoGenerado = $this->reemplazarVariablesConAlumno(
                $payload['contenido_html'],
                $alumnoArray,
                $payload
            );

            $folioTemporal = $this->generarFolioTemporal($alumno->id);

            // Objeto temporal. No se guarda en la base de datos.
            $constanciaTemporal = (object) [
                'id' => null,
                'folio' => $folioTemporal,
                'fecha_expedicion' => Carbon::parse($payload['fecha_expedicion']),
                'dirigido_a' => $payload['dirigido_a'] ?? null,
                'modo_descarga' => $payload['modo_descarga'] ?? 'masivo',
                'periodos_calificaciones' => $payload['periodos_calificaciones'] ?? [],
                'contenido_generado_html' => $contenidoGenerado,
                'plantilla' => $plantilla,
            ];

            $pdf = Pdf::loadView('pdf.constancia_relaciones_pdf', [
                'constancia' => $constanciaTemporal,
                'alumno' => $alumno,
                'plantilla' => $plantilla,
            ])->setPaper('letter', 'portrait');

            $nombreAlumno = trim(
                ($alumno->apellido_paterno ?? '') . ' ' .
                ($alumno->apellido_materno ?? '') . ' ' .
                ($alumno->nombre ?? '')
            );

            $nombreArchivo = Str::slug($folioTemporal . '_' . $nombreAlumno, '_') . '.pdf';

            file_put_contents($carpetaTemporal . '/' . $nombreArchivo, $pdf->output());
        }

        $nombreZip = 'CONSTANCIAS_' . Str::upper($payload['modo_descarga'] ?? 'MASIVO') . '_' . now()->format('Ymd_His') . '.zip';
        $rutaZip = $carpetaBase . '/' . $nombreZip;

        $zip = new ZipArchive();

        if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            File::deleteDirectory($carpetaTemporal);

            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        foreach (File::files($carpetaTemporal) as $archivo) {
            $zip->addFile($archivo->getRealPath(), $archivo->getFilename());
        }

        $zip->close();

        File::deleteDirectory($carpetaTemporal);

        return response()
            ->download($rutaZip, $nombreZip)
            ->deleteFileAfterSend(true);
    }

    /**
     * Da formato a los datos del alumno para reemplazar variables.
     */
    private function formatearAlumno(Inscripcion $alumno): array
    {
        $generacion = '';

        if ($alumno->generacion) {
            $generacion = trim(
                ($alumno->generacion->anio_ingreso ?? '') .
                '-' .
                ($alumno->generacion->anio_egreso ?? '')
            );
        }

        return [
            'id' => $alumno->id,
            'nombre_completo' => trim(
                ($alumno->nombre ?? '') . ' ' .
                ($alumno->apellido_paterno ?? '') . ' ' .
                ($alumno->apellido_materno ?? '')
            ),
            'curp' => $alumno->curp,
            'matricula' => $alumno->matricula,
            'genero' => $alumno->genero,
            'nivel' => $alumno->nivel?->nombre,
            'cct' => $alumno->nivel?->cct,
            'grado' => $alumno->grado?->nombre,
            'grupo' => $alumno->grupo?->asignacionGrupo?->nombre,
            'generacion' => $generacion,
            'ciclo' => $alumno->ciclo?->ciclo,
        ];
    }

    /**
     * Reemplaza las variables de la plantilla con datos del alumno.
     */
    private function reemplazarVariablesConAlumno(string $contenido, array $alumno, array $payload): string
    {
        $genero = mb_strtolower(trim((string) ($alumno['genero'] ?? '')));

        $esMujer = in_array($genero, [
            'f',
            'femenino',
            'femenina',
            'mujer',
            'alumna',
        ]);

        $sexo = $esMujer ? 'La alumna' : 'El alumno';

        $descripcion = $esMujer
            ? 'se encuentra inscrita'
            : 'se encuentra inscrito';

        $variables = [
            '@alumno' => $alumno['nombre_completo'] ?? '',
            '@nombre' => $alumno['nombre_completo'] ?? '',
            '@curp' => $alumno['curp'] ?? '',
            '@matricula' => $alumno['matricula'] ?? '',
            '@grado' => $alumno['grado'] ?? '',
            '@nivel' => $alumno['nivel'] ?? '',
            '@grupo' => $alumno['grupo'] ?? '',
            '@generacion' => $alumno['generacion'] ?? '',
            '@ciclo' => $alumno['ciclo'] ?? '',
            '@cct' => $alumno['cct'] ?? '',
            '@sexo' => $sexo,
            '@descripcion' => $descripcion,
            '@fecha' => Carbon::parse($payload['fecha_expedicion'])->translatedFormat('d \d\e F \d\e Y'),
            '@dirigido' => $payload['dirigido_a'] ?: 'A QUIEN CORRESPONDA',
        ];

        return str_replace(array_keys($variables), array_values($variables), $contenido);
    }

    /**
     * Crea un folio temporal para descargas masivas sin guardar en BD.
     */
    private function generarFolioTemporal(int $alumnoId): string
    {
        return 'CONST-' . now()->format('YmdHis') . '-' . Str::padLeft((string) $alumnoId, 5, '0');
    }
}
