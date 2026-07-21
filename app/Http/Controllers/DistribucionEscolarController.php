<?php

namespace App\Http\Controllers;

use App\Exports\Distribucion\DistribucionEscolarExport;
use App\Models\Nivel;
use App\Services\DistribucionEscolarService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormato;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DistribucionEscolarController extends Controller
{
    public function pdf(
        Request $request,
        string $slug_nivel,
        DistribucionEscolarService $service
    ) {
        abort_unless(auth()->user()?->is_admin, 403);

        [$nivel, $filtros] = $this->resolver(
            $request,
            $slug_nivel,
            $service
        );

        $bloques = $service->bloques($nivel, $filtros);
        $listado = $service->listadoCompleto($nivel, $filtros);

        abort_if(
            $bloques->isEmpty(),
            404,
            'No se encontraron datos para generar la distribución escolar.'
        );

        $nombreArchivo = $this->nombreBase($nivel, $filtros) . '.pdf';

        return $this->crearPdf(
            $nivel,
            $bloques,
            $listado,
            $filtros
        )->stream($nombreArchivo);
    }
    public function excel(
        Request $request,
        string $slug_nivel,
        DistribucionEscolarService $service
    ): BinaryFileResponse {
        abort_unless(auth()->user()?->is_admin, 403);

        [$nivel, $filtros] = $this->resolver($request, $slug_nivel, $service);
        $bloques = $service->bloques($nivel, $filtros);
        $listado = $service->listadoCompleto($nivel, $filtros);

        abort_if($bloques->isEmpty(), 404, 'No se encontraron datos para generar el archivo Excel.');

        return Excel::download(
            new DistribucionEscolarExport($bloques, $listado),
            $this->nombreBase($nivel, $filtros) . '.xlsx'
        );
    }

    public function zip(
        Request $request,
        string $slug_nivel,
        DistribucionEscolarService $service
    ): BinaryFileResponse {
        abort_unless(auth()->user()?->is_admin, 403);
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');

        [$nivel, $filtros] = $this->resolver($request, $slug_nivel, $service);
        $bloques = $service->bloques($nivel, $filtros);
        $listado = $service->listadoCompleto($nivel, $filtros);

        abort_if($bloques->isEmpty(), 404, 'No se encontraron datos para generar el archivo ZIP.');

        $directorio = storage_path('app/temp/distribucion_' . Str::uuid());
        File::ensureDirectoryExists($directorio . '/Generaciones');

        $nombreBase = $this->nombreBase($nivel, $filtros);
        $pdfGeneral = $directorio . '/' . $nombreBase . '.pdf';
        $excelGeneral = $directorio . '/' . $nombreBase . '.xlsx';

        File::put(
            $pdfGeneral,
            $this->crearPdf($nivel, $bloques, $listado, $filtros)->output()
        );

        File::put(
            $excelGeneral,
            Excel::raw(
                new DistribucionEscolarExport($bloques, $listado),
                ExcelFormato::XLSX
            )
        );

        $generaciones = $bloques
            ->flatMap(fn(array $bloque) => collect($bloque['filas']))
            ->filter(fn(array $fila) => !empty($fila['generacion_id']))
            ->unique('generacion_id')
            ->sortBy('generacion_ingreso')
            ->values();

        foreach ($generaciones as $filaGeneracion) {
            $filtrosGeneracion = array_merge($filtros, [
                'generacion_id' => (int) $filaGeneracion['generacion_id'],
            ]);

            $bloquesGeneracion = $service->bloques($nivel, $filtrosGeneracion);
            $listadoGeneracion = $service->listadoCompleto($nivel, $filtrosGeneracion);

            if ($bloquesGeneracion->isEmpty()) {
                continue;
            }

            $slugGeneracion = Str::slug((string) $filaGeneracion['generacion'], '_');
            $carpetaGeneracion = $directorio . '/Generaciones/Generacion_' . $slugGeneracion;
            File::ensureDirectoryExists($carpetaGeneracion);

            File::put(
                $carpetaGeneracion . '/Distribucion_' . $slugGeneracion . '.pdf',
                $this->crearPdf(
                    $nivel,
                    $bloquesGeneracion,
                    $listadoGeneracion,
                    $filtrosGeneracion,
                    'Generación ' . $filaGeneracion['generacion']
                )->output()
            );

            File::put(
                $carpetaGeneracion . '/Listado_' . $slugGeneracion . '.xlsx',
                Excel::raw(
                    new DistribucionEscolarExport($bloquesGeneracion, $listadoGeneracion),
                    ExcelFormato::XLSX
                )
            );
        }

        $zipPath = storage_path('app/temp/' . $nombreBase . '_' . Str::random(8) . '.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive();
        $resultado = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        abort_unless($resultado === true, 500, 'No fue posible crear el archivo ZIP.');

        foreach (File::allFiles($directorio) as $archivo) {
            $rutaRelativa = Str::after($archivo->getPathname(), $directorio . DIRECTORY_SEPARATOR);
            $zip->addFile($archivo->getPathname(), str_replace(DIRECTORY_SEPARATOR, '/', $rutaRelativa));
        }

        $zip->close();
        File::deleteDirectory($directorio);

        return response()
            ->download($zipPath, $nombreBase . '.zip')
            ->deleteFileAfterSend(true);
    }

    private function resolver(
        Request $request,
        string $slugNivel,
        DistribucionEscolarService $service
    ): array {
        $categorias = implode(',', array_keys($service->categorias()));

        $datos = $request->validate([
            'ciclo_escolar_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'estado' => ['nullable', 'in:todos,' . $categorias],
            'solo_ya_no_estan' => ['nullable', 'boolean'],
        ]);

        $nivel = Nivel::query()->with('director')->where('slug', $slugNivel)->firstOrFail();

        return [$nivel, [
            'ciclo_escolar_id' => $datos['ciclo_escolar_id'] ?? null,
            'generacion_id' => $datos['generacion_id'] ?? null,
            'grado_id' => $datos['grado_id'] ?? null,
            'grupo_id' => $datos['grupo_id'] ?? null,
            'semestre_id' => $datos['semestre_id'] ?? null,
            'estado' => $datos['estado'] ?? 'todos',
            'solo_ya_no_estan' => (bool) ($datos['solo_ya_no_estan'] ?? false),
        ]];
    }

    private function crearPdf(
        Nivel $nivel,
        Collection $bloques,
        Collection $listado,
        array $filtros,
        ?string $subtitulo = null
    ) {
        return Pdf::loadView('pdf.distribucion-escolar-historica', [
            'nivel' => $nivel,
            'bloques' => $bloques,
            'listado' => $listado,
            'filtros' => $filtros,
            'subtitulo' => $subtitulo,
            'logo' => $this->imagenBase64(public_path('imagenes/logo-letra.png')),
            'generadoPor' => auth()->user()?->name ?: 'Administración',
            'generadoEn' => now(),
        ])->setPaper('letter', 'landscape');
    }

    private function nombreBase(Nivel $nivel, array $filtros): string
    {
        $alcance = filled($filtros['generacion_id'] ?? null)
            ? 'generacion_' . $filtros['generacion_id']
            : 'todas_las_generaciones';

        return Str::slug(
            'distribucion_escolar_' . $nivel->slug . '_' . $alcance . '_' . now()->format('Ymd_His'),
            '_'
        );
    }

    private function imagenBase64(string $ruta): ?string
    {
        if (!is_file($ruta)) {
            return null;
        }

        $mime = mime_content_type($ruta) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($ruta));
    }
}
