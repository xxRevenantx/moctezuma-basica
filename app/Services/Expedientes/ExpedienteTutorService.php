<?php

namespace App\Services\Expedientes;

use App\Models\DocumentoAlumno;
use App\Models\DocumentoTutor;
use App\Models\DocumentoTutorFuente;
use App\Models\DocumentoTutorNoAplica;
use App\Models\DocumentoTutorPendienteVincular;
use App\Models\EventoDocumentoTutor;
use App\Models\OrganizacionDocumentoTutor;
use App\Models\TipoDocumentoTutor;
use App\Models\Tutor;
use App\Support\Pdf\RotatableFpdi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ExpedienteTutorService
{
    public function disk(): string
    {
        return (string) config('filesystems.expedientes_disk', config('expedientes_organizador.disk', 'local'));
    }

    public function tiposActivos(): Collection
    {
        return TipoDocumentoTutor::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    public function resumen(Tutor $tutor): array
    {
        $tutor->loadMissing([
            'documentos.tipoDocumento:id,nombre,slug,es_obligatorio,orden',
            'documentosNoAplican' => fn ($query) => $query->where('activo', true),
            'relacionesActivas:id,tutor_id,parentesco',
        ]);

        $actuales = $tutor->documentos
            ->where('es_actual', true)
            ->where('es_fuente', false);
        $noAplican = $tutor->documentosNoAplican->where('activo', true);
        $parentescosActivos = $tutor->relacionesActivas
            ->pluck('parentesco')
            ->filter()
            ->map(fn ($parentesco): string => Str::upper(trim((string) $parentesco)))
            ->unique()
            ->values();
        $items = $this->tiposActivos()->map(function (TipoDocumentoTutor $tipo) use ($actuales, $noAplican, $parentescosActivos): array {
            $actual = $actuales->where('tipo_documento_tutor_id', $tipo->id)->sortByDesc('version')->first();
            $noAplica = $noAplican->where('tipo_documento_tutor_id', $tipo->id)->sortByDesc('id')->first();
            $disponible = $actual
                && ! in_array($actual->estado, ['pendiente', 'rechazado', 'reemplazado', 'cancelado'], true)
                && $actual->archivo_existe;

            return [
                'tipo_id' => $tipo->id,
                'slug' => $tipo->slug,
                'nombre' => $tipo->nombre,
                'descripcion' => $tipo->descripcion,
                'obligatorio' => (bool) $tipo->es_obligatorio
                    || $parentescosActivos->intersect(collect($tipo->obligatorio_parentescos ?? [])->map(fn ($parentesco): string => Str::upper(trim((string) $parentesco))))->isNotEmpty(),
                'presente' => (bool) $disponible || (bool) $noAplica,
                'estado' => $noAplica ? 'no_aplica' : ($actual?->estado ?? 'pendiente'),
                'no_aplica' => (bool) $noAplica,
                'no_aplica_id' => $noAplica?->id,
                'motivo_no_aplica' => $noAplica?->motivo,
                'documento_id' => $actual?->id,
                'archivo_faltante' => (bool) $actual && ! $actual->archivo_existe,
            ];
        })->values();

        $obligatorios = $items->where('obligatorio', true);
        $base = $obligatorios->isNotEmpty() ? $obligatorios : $items;
        $total = $base->count();
        $completados = $base->where('presente', true)->count();

        return [
            'total' => $total,
            'completados' => $completados,
            'pendientes' => max($total - $completados, 0),
            'porcentaje' => $total > 0 ? (int) floor(($completados / $total) * 100) : 100,
            'completo' => $total === 0 || $completados === $total,
            'items' => $items->all(),
            'archivos_faltantes' => $actuales->reject(fn (DocumentoTutor $documento) => $documento->archivo_existe)->count(),
        ];
    }

    /**
     * Guarda el original y una copia PDF procesable. Cada carga queda como
     * fuente privada y debe confirmarse en el organizador para convertirse en
     * un documento vigente del responsable.
     */
    public function registrarFuenteDesdeUpload(
        UploadedFile $archivo,
        Tutor $tutor,
        int $tipoId,
        string $contenidoArchivo,
        ?int $usuarioId,
        bool $permitirDuplicado = false,
        array $metadatos = []
    ): array {
        if (! in_array($contenidoArchivo, ['un_documento', 'varios_documentos'], true)) {
            throw ValidationException::withMessages([
                'contenido_archivo' => 'Indica si el archivo contiene uno o varios documentos combinados.',
            ]);
        }

        $tipo = TipoDocumentoTutor::query()->where('activo', true)->findOrFail($tipoId);
        $mimeOriginal = $this->validarMime($archivo);
        $tamanoOriginal = (int) ($archivo->getSize() ?: File::size($archivo->getRealPath()));
        $limiteBytes = max((int) config('expedientes_organizador.max_upload_mb', 30), 1) * 1024 * 1024;

        if ($tamanoOriginal > $limiteBytes) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo supera el límite de ' . config('expedientes_organizador.max_upload_mb', 30) . ' MB.',
            ]);
        }

        $hash = hash_file('sha256', $archivo->getRealPath()) ?: null;
        if ($hash && ! $permitirDuplicado && DocumentoTutorFuente::query()
            ->where('tutor_id', $tutor->id)
            ->where('hash_sha256', $hash)
            ->where('estado', 'activo')
            ->exists()) {
            throw ValidationException::withMessages([
                'archivo' => 'Este mismo archivo ya está registrado para el responsable.',
            ]);
        }

        app(PdfCompatibilityService::class)->assertLibrariesAvailable();
        $temporalPdf = $this->crearPdfTemporalDesdeArchivo($archivo, $mimeOriginal);
        $temporalNormalizado = null;

        try {
            $compatibilidad = app(PdfCompatibilityService::class)->prepare($temporalPdf);
            $rutaProcesable = (string) $compatibilidad['path'];
            if ($rutaProcesable !== $temporalPdf) {
                $temporalNormalizado = $rutaProcesable;
            }

            $paginas = $this->validarLimitePaginas((int) $compatibilidad['pages']);
            $uuid = (string) Str::uuid();
            $extensionOriginal = strtolower($archivo->getClientOriginalExtension() ?: $this->extensionDesdeMime($mimeOriginal));
            $directorio = "expedientes-tutores/fuentes/{$tutor->id}/{$uuid}";
            $rutaOriginal = "{$directorio}/original.{$extensionOriginal}";
            $usaNormalizado = $mimeOriginal !== 'application/pdf' || ($compatibilidad['status'] ?? null) === 'normalized';
            $rutaPdf = $usaNormalizado ? "{$directorio}/normalizado.pdf" : $rutaOriginal;
            $contenidoOriginal = File::get($archivo->getRealPath());
            $contenidoPdf = File::get($rutaProcesable);
            $disk = Storage::disk($this->disk());
            $rutasGuardadas = [];

            if (! $disk->put($rutaOriginal, $contenidoOriginal)) {
                throw new RuntimeException('No fue posible guardar el archivo original.');
            }
            $rutasGuardadas[] = $rutaOriginal;

            if ($rutaPdf !== $rutaOriginal) {
                if (! $disk->put($rutaPdf, $contenidoPdf)) {
                    throw new RuntimeException('No fue posible guardar la copia PDF normalizada.');
                }
                $rutasGuardadas[] = $rutaPdf;
            }

            try {
                $resultado = DB::transaction(function () use (
                    $tutor,
                    $tipo,
                    $usuarioId,
                    $archivo,
                    $mimeOriginal,
                    $tamanoOriginal,
                    $hash,
                    $paginas,
                    $compatibilidad,
                    $rutaOriginal,
                    $rutaPdf,
                    $contenidoPdf,
                    $contenidoArchivo,
                    $metadatos
                ): array {
                    $versionFuente = ((int) DocumentoTutor::query()
                        ->where('tutor_id', $tutor->id)
                        ->where('tipo_documento_tutor_id', $tipo->id)
                        ->where('es_fuente', true)
                        ->max('version')) + 1;

                    $documentoFuente = DocumentoTutor::query()->create([
                        'tutor_id' => $tutor->id,
                        'tipo_documento_tutor_id' => $tipo->id,
                        'fecha_documento' => $metadatos['fecha_documento'] ?? now()->toDateString(),
                        'folio' => filled($metadatos['folio'] ?? null) ? trim((string) $metadatos['folio']) : null,
                        'origen' => $metadatos['origen'] ?? 'subido',
                        'disco' => $this->disk(),
                        'ruta' => $rutaPdf,
                        'nombre_original' => $archivo->getClientOriginalName(),
                        'mime_type' => 'application/pdf',
                        'tamano_bytes' => strlen($contenidoPdf),
                        'paginas_total' => $paginas,
                        'hash_sha256' => $hash,
                        'version' => $versionFuente,
                        'es_actual' => true,
                        'es_fuente' => true,
                        'es_organizado' => false,
                        'estado' => 'recibido',
                        'observaciones' => filled($metadatos['observaciones'] ?? null) ? trim((string) $metadatos['observaciones']) : null,
                        'subido_por' => $usuarioId,
                    ]);

                    $fuente = DocumentoTutorFuente::query()->create([
                        'tutor_id' => $tutor->id,
                        'documento_tutor_id' => $documentoFuente->id,
                        'disco' => $this->disk(),
                        'ruta' => $rutaPdf,
                        'ruta_original' => $rutaOriginal,
                        'nombre_original' => $archivo->getClientOriginalName(),
                        'nombre_almacenado' => basename($rutaPdf),
                        'mime_type' => 'application/pdf',
                        'mime_original' => $mimeOriginal,
                        'tamano_bytes' => $tamanoOriginal,
                        'hash_sha256' => $hash,
                        'paginas' => $paginas,
                        'estado' => 'activo',
                        'protegido' => false,
                        'subido_por' => $usuarioId,
                        'metadatos' => [
                            'tipo_documento_tutor_id' => $tipo->id,
                            'contenido_archivo' => $contenidoArchivo,
                            'pdf_estado' => ($compatibilidad['status'] ?? null) === 'normalized' ? 'normalized' : 'compatible',
                            'normalizador' => $compatibilidad['normalizer'] ?? null,
                            'fecha_documento' => $metadatos['fecha_documento'] ?? now()->toDateString(),
                            'folio' => $metadatos['folio'] ?? null,
                            'origen' => $metadatos['origen'] ?? 'subido',
                            'observaciones' => $metadatos['observaciones'] ?? null,
                        ],
                    ]);

                    $borrador = $this->obtenerOCrearBorrador($tutor, $usuarioId);
                    $asignaciones = collect($borrador->asignaciones ?? []);
                    for ($pagina = 1; $pagina <= $paginas; $pagina++) {
                        $clave = $fuente->id . ':' . $pagina;
                        $asignacion = [
                            'fuente_id' => $fuente->id,
                            'pagina' => $pagina,
                            'tipo_documento_tutor_id' => $contenidoArchivo === 'un_documento' ? $tipo->id : null,
                            'tipo_slug' => $contenidoArchivo === 'un_documento' ? $tipo->slug : null,
                            'tipo_nombre' => $contenidoArchivo === 'un_documento' ? $tipo->nombre : null,
                            'orden' => $contenidoArchivo === 'un_documento'
                                ? ((int) $asignaciones->where('tipo_documento_tutor_id', $tipo->id)->max('orden')) + $pagina
                                : 0,
                            'rotacion' => 0,
                            'fecha_documento' => $metadatos['fecha_documento'] ?? now()->toDateString(),
                            'folio' => $metadatos['folio'] ?? null,
                            'origen' => $metadatos['origen'] ?? 'subido',
                            'observaciones' => $metadatos['observaciones'] ?? null,
                        ];
                        $asignaciones = $asignaciones->reject(fn (array $item): bool => ($item['fuente_id'] . ':' . $item['pagina']) === $clave)->push($asignacion);
                    }

                    $borrador->forceFill([
                        'asignaciones' => $this->normalizarAsignaciones($asignaciones->values()->all(), $tutor),
                        'fuentes_ids' => DocumentoTutorFuente::query()
                            ->where('tutor_id', $tutor->id)
                            ->where('estado', 'activo')
                            ->pluck('id')
                            ->values()
                            ->all(),
                        'metadatos' => array_merge($borrador->metadatos ?? [], ['actualizado_por' => $usuarioId]),
                    ])->save();

                    $this->registrarEvento($tutor, 'fuente_subida', 'Se registró un archivo fuente para organizar.', $usuarioId, $documentoFuente, $borrador, null, [
                        'tipo' => $tipo->nombre,
                        'archivo' => $archivo->getClientOriginalName(),
                        'paginas' => $paginas,
                    ]);

                    return ['fuente' => $fuente, 'documento_fuente' => $documentoFuente, 'organizacion' => $borrador];
                });
            } catch (Throwable $e) {
                foreach ($rutasGuardadas as $ruta) {
                    try {
                        $disk->delete($ruta);
                    } catch (Throwable) {
                    }
                }
                throw $e;
            }

            return array_merge($resultado, [
                'paginas' => $paginas,
                'normalizado' => ($compatibilidad['status'] ?? null) === 'normalized',
                'normalizador' => $compatibilidad['normalizer'] ?? null,
            ]);
        } finally {
            File::delete(array_filter([$temporalPdf, $temporalNormalizado]));
        }
    }

    public function datosOrganizador(Tutor $tutor, ?int $usuarioId = null): array
    {
        $borrador = $this->obtenerOCrearBorrador($tutor, $usuarioId);
        $fuentes = DocumentoTutorFuente::query()
            ->where('tutor_id', $tutor->id)
            ->where('estado', 'activo')
            ->where('protegido', false)
            ->with('usuario:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $asignaciones = $this->normalizarAsignaciones($borrador->asignaciones ?? [], $tutor, $fuentes);
        if ($asignaciones !== ($borrador->asignaciones ?? [])) {
            $borrador->forceFill([
                'asignaciones' => $asignaciones,
                'fuentes_ids' => $fuentes->pluck('id')->values()->all(),
            ])->save();
        }

        return [
            'organizacion' => $borrador->fresh(),
            'fuentes' => $fuentes,
            'asignaciones' => $asignaciones,
            'tipos' => $this->tiposActivos(),
            'historial' => OrganizacionDocumentoTutor::query()
                ->where('tutor_id', $tutor->id)
                ->whereIn('estado', ['confirmado', 'error'])
                ->with('usuarioConfirmacion:id,name')
                ->latest('version')
                ->limit(10)
                ->get(),
            'tipos_existentes' => collect((array) data_get($borrador->metadatos, 'baseline_asignaciones', []))
                ->whereNotNull('tipo_documento_tutor_id')
                ->pluck('tipo_documento_tutor_id')
                ->unique()
                ->values()
                ->all(),
        ];
    }

    public function guardarBorrador(
        Tutor $tutor,
        array $asignaciones,
        ?int $usuarioId,
        ?int $organizacionId = null,
        array $retirosConfirmados = []
    ): OrganizacionDocumentoTutor {
        $normalizadas = $this->normalizarAsignaciones($asignaciones, $tutor);

        return DB::transaction(function () use ($tutor, $usuarioId, $organizacionId, $normalizadas, $retirosConfirmados): OrganizacionDocumentoTutor {
            $borrador = $organizacionId
                ? OrganizacionDocumentoTutor::query()
                    ->where('tutor_id', $tutor->id)
                    ->where('estado', 'borrador')
                    ->lockForUpdate()
                    ->find($organizacionId)
                : null;

            $borrador ??= $this->obtenerOCrearBorrador($tutor, $usuarioId);
            $borrador->forceFill([
                'asignaciones' => $normalizadas,
                'fuentes_ids' => DocumentoTutorFuente::query()
                    ->where('tutor_id', $tutor->id)
                    ->where('estado', 'activo')
                    ->pluck('id')
                    ->values()
                    ->all(),
                'retiros_confirmados' => array_values(array_unique(array_map('intval', array_filter($retirosConfirmados)))),
                'error' => null,
                'metadatos' => array_merge($borrador->metadatos ?? [], [
                    'actualizado_por' => $usuarioId,
                    'paginas_sin_clasificar' => collect($normalizadas)->whereNull('tipo_documento_tutor_id')->count(),
                ]),
            ])->save();

            return $borrador->fresh();
        });
    }

    public function confirmarOrganizacion(Tutor $tutor, int $organizacionId, ?int $usuarioId): OrganizacionDocumentoTutor
    {
        $organizacion = OrganizacionDocumentoTutor::query()
            ->where('tutor_id', $tutor->id)
            ->where('estado', 'borrador')
            ->findOrFail($organizacionId);

        $asignaciones = collect($this->normalizarAsignaciones($organizacion->asignaciones ?? [], $tutor));
        $clasificadas = $asignaciones->whereNotNull('tipo_documento_tutor_id');
        if ($clasificadas->isEmpty()) {
            throw ValidationException::withMessages([
                'organizacion' => 'Asigna al menos una página antes de confirmar la organización.',
            ]);
        }

        $tiposActuales = $clasificadas->pluck('tipo_documento_tutor_id')->unique()->map(fn ($id) => (int) $id);
        $tiposBaseline = collect((array) data_get($organizacion->metadatos, 'baseline_asignaciones', []))
            ->whereNotNull('tipo_documento_tutor_id')
            ->pluck('tipo_documento_tutor_id')
            ->unique()
            ->map(fn ($id) => (int) $id);
        $retiros = collect($organizacion->retiros_confirmados ?? [])->map(fn ($id) => (int) $id);
        $faltantes = $tiposBaseline->diff($tiposActuales)->diff($retiros);

        if ($faltantes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'organizacion' => 'Para retirar completamente un documento vigente debes confirmar su retiro.',
            ]);
        }

        try {
            DB::transaction(function () use ($tutor, $organizacion, $usuarioId, $clasificadas, $retiros): void {
                foreach ($clasificadas->groupBy('tipo_documento_tutor_id') as $tipoId => $grupo) {
                    $this->guardarDocumentoOrganizado($tutor, $organizacion, (int) $tipoId, $grupo->sortBy('orden')->values(), $usuarioId);
                }

                foreach ($retiros as $tipoId) {
                    if ($clasificadas->where('tipo_documento_tutor_id', $tipoId)->isNotEmpty()) {
                        continue;
                    }

                    $anteriores = DocumentoTutor::query()
                        ->where('tutor_id', $tutor->id)
                        ->where('tipo_documento_tutor_id', $tipoId)
                        ->where('es_fuente', false)
                        ->where('es_actual', true)
                        ->get();
                    DocumentoTutor::query()
                        ->whereIn('id', $anteriores->pluck('id')->all())
                        ->update(['es_actual' => false, 'estado' => 'reemplazado', 'updated_at' => now()]);

                    $this->registrarEvento($tutor, 'documento_retirado', 'Se retiró el documento vigente del expediente del responsable.', $usuarioId, null, $organizacion, $anteriores->toArray(), ['tipo_documento_tutor_id' => $tipoId]);
                }

                $organizacion->forceFill([
                    'estado' => 'confirmado',
                    'asignaciones' => $clasificadas->values()->all(),
                    'confirmado_por' => $usuarioId,
                    'confirmado_at' => now(),
                    'error' => null,
                    'metadatos' => array_merge($organizacion->metadatos ?? [], [
                        'confirmado_por' => $usuarioId,
                        'paginas_clasificadas' => $clasificadas->count(),
                    ]),
                ])->save();

                $this->registrarEvento($tutor, 'organizacion_confirmada', 'Se confirmó la organización de páginas del expediente.', $usuarioId, null, $organizacion, null, [
                    'version' => $organizacion->version,
                    'paginas' => $clasificadas->count(),
                ]);
            });
        } catch (Throwable $e) {
            $organizacion->forceFill([
                'estado' => 'error',
                'error' => Str::limit($e->getMessage(), 4000, ''),
            ])->save();
            throw $e;
        }

        return $organizacion->fresh();
    }

    public function rutaVistaPagina(DocumentoTutorFuente $fuente, int $pagina, int $rotacion = 0): string
    {
        abort_unless($fuente->estado === 'activo' && ! $fuente->protegido, 404);
        abort_unless($pagina >= 1 && $pagina <= max((int) $fuente->paginas, 1), 404);

        $rotacion = $this->normalizarRotacion($rotacion);
        $directorio = storage_path('app/temp/expedientes-tutores/previews');
        File::ensureDirectoryExists($directorio);
        $clave = hash('sha256', implode('|', [$fuente->id, $fuente->updated_at?->timestamp, $pagina, $rotacion]));
        $destino = $directorio . DIRECTORY_SEPARATOR . $clave . '.pdf';

        if (is_file($destino)) {
            return $destino;
        }

        $temporalFuente = null;
        $rutaFuente = $this->rutaLocalFuente($fuente, $temporalFuente);

        try {
            $pdf = new RotatableFpdi();
            $pdf->setSourceFile($rutaFuente);
            $template = $pdf->importPage($pagina);
            $size = $pdf->getTemplateSize($template);
            $pageWidth = in_array($rotacion, [90, 270], true) ? $size['height'] : $size['width'];
            $pageHeight = in_array($rotacion, [90, 270], true) ? $size['width'] : $size['height'];
            $orientation = $pageWidth > $pageHeight ? 'L' : 'P';
            $pdf->AddPage($orientation, [$pageWidth, $pageHeight]);
            $pdf->placeTemplateRotated($template, $size, $rotacion);
            $pdf->Output('F', $destino);
        } finally {
            if ($temporalFuente) {
                File::delete($temporalFuente);
            }
        }

        return $destino;
    }

    public function vincularDocumentoLegado(DocumentoTutorPendienteVincular $pendiente, Tutor $tutor, ?int $usuarioId): DocumentoTutor
    {
        abort_unless($pendiente->estado === 'pendiente', 422, 'El documento antiguo ya fue atendido.');
        $documentoAlumno = DocumentoAlumno::query()->with('tipoDocumento')->findOrFail($pendiente->documento_alumno_id);
        abort_unless($documentoAlumno->archivo_existe, 422, 'El archivo antiguo ya no existe en el almacenamiento.');
        $tipo = TipoDocumentoTutor::query()->where('slug', $pendiente->tipo_destino_slug)->firstOrFail();

        return DB::transaction(function () use ($pendiente, $tutor, $usuarioId, $documentoAlumno, $tipo): DocumentoTutor {
            $consulta = DocumentoTutor::query()
                ->where('tutor_id', $tutor->id)
                ->where('tipo_documento_tutor_id', $tipo->id)
                ->where('es_fuente', false)
                ->lockForUpdate();
            $version = ((int) (clone $consulta)->max('version')) + 1;
            (clone $consulta)->where('es_actual', true)->update([
                'es_actual' => false,
                'estado' => 'reemplazado',
                'updated_at' => now(),
            ]);

            $documento = DocumentoTutor::query()->create([
                'tutor_id' => $tutor->id,
                'tipo_documento_tutor_id' => $tipo->id,
                'fecha_documento' => $documentoAlumno->fecha_documento ?: $documentoAlumno->created_at?->toDateString(),
                'folio' => $documentoAlumno->folio,
                'origen' => 'migrado_alumno',
                'disco' => $documentoAlumno->disco,
                'ruta' => $documentoAlumno->ruta,
                'nombre_original' => $documentoAlumno->nombre_original,
                'mime_type' => $documentoAlumno->mime_type,
                'tamano_bytes' => $documentoAlumno->tamano_bytes,
                'paginas_total' => $documentoAlumno->paginas_total,
                'hash_sha256' => $documentoAlumno->hash_sha256,
                'version' => $version,
                'es_actual' => true,
                'es_fuente' => false,
                'es_organizado' => $documentoAlumno->es_organizado,
                'estado' => 'recibido',
                'observaciones' => 'Vinculado desde el expediente histórico del alumno. Documento original #' . $documentoAlumno->id . '.',
                'subido_por' => $usuarioId ?: $documentoAlumno->subido_por,
            ]);

            $pendiente->forceFill([
                'tutor_sugerido_id' => $tutor->id,
                'estado' => 'vinculado',
                'resuelto_por' => $usuarioId,
                'resuelto_at' => now(),
            ])->save();

            $this->registrarEvento($tutor, 'documento_legado_vinculado', 'Se vinculó un documento antiguo del alumno al expediente del responsable.', $usuarioId, $documento, null, null, [
                'documento_alumno_id' => $documentoAlumno->id,
                'inscripcion_id' => $pendiente->inscripcion_id,
            ]);

            return $documento;
        });
    }

    public function registrarEvento(
        Tutor $tutor,
        string $accion,
        ?string $descripcion,
        ?int $usuarioId,
        ?DocumentoTutor $documento = null,
        ?OrganizacionDocumentoTutor $organizacion = null,
        mixed $anteriores = null,
        mixed $nuevos = null
    ): EventoDocumentoTutor {
        return EventoDocumentoTutor::query()->create([
            'tutor_id' => $tutor->id,
            'documento_tutor_id' => $documento?->id,
            'organizacion_id' => $organizacion?->id,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'datos_anteriores' => is_array($anteriores) ? $anteriores : null,
            'datos_nuevos' => is_array($nuevos) ? $nuevos : null,
            'usuario_id' => $usuarioId,
            'ip' => request()?->ip(),
            'user_agent' => Str::limit((string) request()?->userAgent(), 500, ''),
        ]);
    }

    protected function obtenerOCrearBorrador(Tutor $tutor, ?int $usuarioId): OrganizacionDocumentoTutor
    {
        $borrador = OrganizacionDocumentoTutor::query()
            ->where('tutor_id', $tutor->id)
            ->where('estado', 'borrador')
            ->latest('version')
            ->first();

        if ($borrador) {
            return $borrador;
        }

        $ultimaConfirmada = OrganizacionDocumentoTutor::query()
            ->where('tutor_id', $tutor->id)
            ->where('estado', 'confirmado')
            ->latest('version')
            ->first();
        $baseline = $ultimaConfirmada?->asignaciones ?? [];
        $version = ((int) OrganizacionDocumentoTutor::query()->where('tutor_id', $tutor->id)->max('version')) + 1;

        return OrganizacionDocumentoTutor::query()->create([
            'tutor_id' => $tutor->id,
            'version' => $version,
            'estado' => 'borrador',
            'asignaciones' => $baseline,
            'fuentes_ids' => DocumentoTutorFuente::query()
                ->where('tutor_id', $tutor->id)
                ->where('estado', 'activo')
                ->pluck('id')
                ->values()
                ->all(),
            'retiros_confirmados' => [],
            'metadatos' => [
                'creado_por' => $usuarioId,
                'baseline_asignaciones' => $baseline,
            ],
        ]);
    }

    protected function normalizarAsignaciones(array $asignaciones, Tutor $tutor, ?Collection $fuentes = null): array
    {
        $fuentes ??= DocumentoTutorFuente::query()
            ->where('tutor_id', $tutor->id)
            ->where('estado', 'activo')
            ->where('protegido', false)
            ->get();
        $fuentesPorId = $fuentes->keyBy('id');
        $tipos = $this->tiposActivos()->keyBy('id');
        $asignacionesPorClave = collect($asignaciones)->keyBy(fn (array $item): string => ((int) ($item['fuente_id'] ?? 0)) . ':' . ((int) ($item['pagina'] ?? 0)));
        $resultado = [];

        foreach ($fuentes as $fuente) {
            for ($pagina = 1; $pagina <= max((int) $fuente->paginas, 1); $pagina++) {
                $clave = $fuente->id . ':' . $pagina;
                $existente = (array) ($asignacionesPorClave->get($clave) ?? []);
                $tipoId = filled($existente['tipo_documento_tutor_id'] ?? null)
                    ? (int) $existente['tipo_documento_tutor_id']
                    : null;
                $tipo = $tipoId ? $tipos->get($tipoId) : null;

                $resultado[] = [
                    'fuente_id' => $fuente->id,
                    'pagina' => $pagina,
                    'tipo_documento_tutor_id' => $tipo?->id,
                    'tipo_slug' => $tipo?->slug,
                    'tipo_nombre' => $tipo?->nombre,
                    'orden' => $tipo ? max((int) ($existente['orden'] ?? 1), 1) : 0,
                    'rotacion' => $this->normalizarRotacion((int) ($existente['rotacion'] ?? 0)),
                    'fecha_documento' => $existente['fecha_documento'] ?? data_get($fuente->metadatos, 'fecha_documento', now()->toDateString()),
                    'folio' => $existente['folio'] ?? data_get($fuente->metadatos, 'folio'),
                    'origen' => $existente['origen'] ?? data_get($fuente->metadatos, 'origen', 'subido'),
                    'observaciones' => $existente['observaciones'] ?? data_get($fuente->metadatos, 'observaciones'),
                ];
            }
        }

        $coleccion = collect($resultado);
        foreach ($coleccion->whereNotNull('tipo_documento_tutor_id')->groupBy('tipo_documento_tutor_id') as $grupo) {
            foreach ($grupo->sortBy('orden')->values() as $indice => $pagina) {
                $clave = $pagina['fuente_id'] . ':' . $pagina['pagina'];
                $posicion = $coleccion->search(fn (array $item): bool => ($item['fuente_id'] . ':' . $item['pagina']) === $clave);
                if ($posicion !== false) {
                    $coleccion[$posicion]['orden'] = $indice + 1;
                }
            }
        }

        return $coleccion->values()->all();
    }

    protected function guardarDocumentoOrganizado(
        Tutor $tutor,
        OrganizacionDocumentoTutor $organizacion,
        int $tipoId,
        Collection $grupo,
        ?int $usuarioId
    ): DocumentoTutor {
        $tipo = TipoDocumentoTutor::query()->findOrFail($tipoId);
        $temporal = $this->generarPdfGrupo($grupo);
        $contenido = File::get($temporal);
        $ruta = 'expedientes-tutores/organizados/' . $tutor->id . '/' . $tipo->slug . '/' . Str::uuid() . '.pdf';
        $disk = Storage::disk($this->disk());

        try {
            if (! $disk->put($ruta, $contenido)) {
                throw new RuntimeException('No fue posible almacenar el documento organizado.');
            }

            $consulta = DocumentoTutor::query()
                ->where('tutor_id', $tutor->id)
                ->where('tipo_documento_tutor_id', $tipo->id)
                ->where('es_fuente', false)
                ->lockForUpdate();
            $anteriores = (clone $consulta)->where('es_actual', true)->get();
            $version = ((int) (clone $consulta)->max('version')) + 1;
            (clone $consulta)->where('es_actual', true)->update([
                'es_actual' => false,
                'estado' => 'reemplazado',
                'updated_at' => now(),
            ]);

            $primera = $grupo->first();
            $documento = DocumentoTutor::query()->create([
                'tutor_id' => $tutor->id,
                'organizacion_id' => $organizacion->id,
                'tipo_documento_tutor_id' => $tipo->id,
                'fecha_documento' => $primera['fecha_documento'] ?? now()->toDateString(),
                'folio' => filled($primera['folio'] ?? null) ? trim((string) $primera['folio']) : null,
                'origen' => 'organizado',
                'disco' => $this->disk(),
                'ruta' => $ruta,
                'nombre_original' => Str::slug($tipo->slug, '_') . '_v' . $version . '.pdf',
                'mime_type' => 'application/pdf',
                'tamano_bytes' => strlen($contenido),
                'paginas_total' => $grupo->count(),
                'hash_sha256' => hash('sha256', $contenido),
                'version' => $version,
                'es_actual' => true,
                'es_fuente' => false,
                'es_organizado' => true,
                'estado' => 'recibido',
                'observaciones' => filled($primera['observaciones'] ?? null) ? trim((string) $primera['observaciones']) : null,
                'subido_por' => $usuarioId,
            ]);

            DocumentoTutorNoAplica::query()
                ->where('tutor_id', $tutor->id)
                ->where('tipo_documento_tutor_id', $tipo->id)
                ->where('activo', true)
                ->update(['activo' => false, 'updated_at' => now()]);

            $this->registrarEvento(
                $tutor,
                $anteriores->isEmpty() ? 'documento_creado' : 'documento_reemplazado',
                $anteriores->isEmpty()
                    ? 'Se creó un documento organizado del responsable.'
                    : 'Se reemplazó el documento vigente conservando su historial.',
                $usuarioId,
                $documento,
                $organizacion,
                $anteriores->toArray(),
                $documento->toArray()
            );

            return $documento;
        } catch (Throwable $e) {
            try {
                $disk->delete($ruta);
            } catch (Throwable) {
            }
            throw $e;
        } finally {
            File::delete($temporal);
        }
    }

    protected function generarPdfGrupo(Collection $grupo): string
    {
        $directorio = storage_path('app/temp/expedientes-tutores/generados');
        File::ensureDirectoryExists($directorio);
        $destino = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.pdf';
        $pdf = new RotatableFpdi();
        $temporales = [];

        try {
            foreach ($grupo as $pagina) {
                $fuente = DocumentoTutorFuente::query()->findOrFail((int) $pagina['fuente_id']);
                $temporal = null;
                $rutaFuente = $this->rutaLocalFuente($fuente, $temporal);
                if ($temporal) {
                    $temporales[] = $temporal;
                }
                $pdf->setSourceFile($rutaFuente);
                $template = $pdf->importPage((int) $pagina['pagina']);
                $size = $pdf->getTemplateSize($template);
                $rotacion = $this->normalizarRotacion((int) ($pagina['rotacion'] ?? 0));
                $pageWidth = in_array($rotacion, [90, 270], true) ? $size['height'] : $size['width'];
                $pageHeight = in_array($rotacion, [90, 270], true) ? $size['width'] : $size['height'];
                $orientation = $pageWidth > $pageHeight ? 'L' : 'P';
                $pdf->AddPage($orientation, [$pageWidth, $pageHeight]);
                $pdf->placeTemplateRotated($template, $size, $rotacion);
            }

            $pdf->Output('F', $destino);
        } finally {
            foreach (array_unique($temporales) as $temporal) {
                File::delete($temporal);
            }
        }

        return $destino;
    }

    protected function crearPdfTemporalDesdeArchivo(UploadedFile $archivo, string $mime): string
    {
        $directorio = storage_path('app/temp/expedientes-tutores/uploads');
        File::ensureDirectoryExists($directorio);
        $destino = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.pdf';

        if ($mime === 'application/pdf') {
            if (! File::copy($archivo->getRealPath(), $destino)) {
                throw new RuntimeException('No fue posible preparar el PDF para su revisión.');
            }

            return $destino;
        }

        $rutaImagen = $archivo->getRealPath();
        $temporalImagen = null;

        if ($mime === 'image/webp') {
            if (! function_exists('imagecreatefromwebp')) {
                throw ValidationException::withMessages(['archivo' => 'El servidor no tiene habilitado el soporte WEBP de GD.']);
            }
            $imagen = @imagecreatefromwebp($rutaImagen);
            if (! $imagen) {
                throw ValidationException::withMessages(['archivo' => 'No fue posible leer la imagen WEBP.']);
            }
            $temporalImagen = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.png';
            imagepng($imagen, $temporalImagen);
            imagedestroy($imagen);
            $rutaImagen = $temporalImagen;
        }

        try {
            $dimensiones = @getimagesize($rutaImagen);
            if (! is_array($dimensiones)) {
                throw ValidationException::withMessages(['archivo' => 'La imagen no es válida o está dañada.']);
            }

            [$anchoPx, $altoPx] = $dimensiones;
            $orientacion = $anchoPx > $altoPx ? 'L' : 'P';
            $anchoPagina = $orientacion === 'L' ? 279.4 : 215.9;
            $altoPagina = $orientacion === 'L' ? 215.9 : 279.4;
            $margen = 8.0;
            $escala = min(($anchoPagina - 2 * $margen) / $anchoPx, ($altoPagina - 2 * $margen) / $altoPx);
            $ancho = $anchoPx * $escala;
            $alto = $altoPx * $escala;
            $x = ($anchoPagina - $ancho) / 2;
            $y = ($altoPagina - $alto) / 2;

            $pdf = new \FPDF($orientacion, 'mm', 'letter');
            $pdf->SetAutoPageBreak(false);
            $pdf->AddPage();
            $pdf->Image($rutaImagen, $x, $y, $ancho, $alto);
            $pdf->Output('F', $destino);
        } finally {
            if ($temporalImagen) {
                File::delete($temporalImagen);
            }
        }

        return $destino;
    }

    protected function validarLimitePaginas(int $paginas): int
    {
        $maximo = max((int) config('expedientes_organizador.max_pages', 50), 1);
        if ($paginas < 1) {
            throw ValidationException::withMessages(['archivo' => 'El PDF no contiene páginas utilizables.']);
        }
        if ($paginas > $maximo) {
            throw ValidationException::withMessages(['archivo' => "El archivo contiene {$paginas} páginas y el límite configurado es {$maximo}."]);
        }

        return $paginas;
    }

    protected function rutaLocalFuente(DocumentoTutorFuente $fuente, ?string &$temporal = null): string
    {
        $disk = Storage::disk($fuente->disco ?: $this->disk());
        if (! $disk->exists($fuente->ruta)) {
            throw new RuntimeException('El archivo fuente ya no existe en el almacenamiento privado.');
        }

        try {
            $ruta = $disk->path($fuente->ruta);
            if (is_file($ruta)) {
                return $ruta;
            }
        } catch (Throwable) {
        }

        $directorio = storage_path('app/temp/expedientes-tutores/remotos');
        File::ensureDirectoryExists($directorio);
        $temporal = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.pdf';
        $origen = $disk->readStream($fuente->ruta);
        $destino = fopen($temporal, 'wb');
        if (! is_resource($origen) || ! is_resource($destino)) {
            if (is_resource($origen)) {
                fclose($origen);
            }
            if (is_resource($destino)) {
                fclose($destino);
            }
            throw new RuntimeException('No fue posible crear una copia temporal del archivo remoto.');
        }
        stream_copy_to_stream($origen, $destino);
        fclose($origen);
        fclose($destino);

        return $temporal;
    }

    protected function validarMime(UploadedFile $archivo): string
    {
        $mime = strtolower((string) $archivo->getMimeType());
        if ($mime === 'application/x-pdf') {
            $mime = 'application/pdf';
        }
        if (! in_array($mime, config('expedientes_organizador.allowed_mimetypes', []), true)) {
            throw ValidationException::withMessages(['archivo' => 'El archivo debe ser PDF, JPG, JPEG, PNG o WEBP.']);
        }

        return $mime;
    }

    protected function extensionDesdeMime(string $mime): string
    {
        return match ($mime) {
            'application/pdf', 'application/x-pdf' => 'pdf',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    protected function normalizarRotacion(int $rotacion): int
    {
        $rotacion = (($rotacion % 360) + 360) % 360;

        return in_array($rotacion, [0, 90, 180, 270], true) ? $rotacion : 0;
    }
}
