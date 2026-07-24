<?php

namespace App\Services\Expedientes;

use App\Jobs\ConfirmarOrganizacionExpedienteJob;
use App\Models\DocumentoAlumno;
use App\Models\DocumentoAlumnoFuente;
use App\Models\DocumentoAlumnoNoAplica;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\MovimientoAlumno;
use App\Models\OrganizacionDocumentoAlumno;
use App\Models\TipoDocumento;
use App\Support\Pdf\RotatableFpdi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Throwable;

class OrganizadorExpedienteService
{
    public function disk(): string
    {
        return (string) config('filesystems.expedientes_disk', config('expedientes_organizador.disk', 'local'));
    }

    public function tiposOrganizables(): Collection
    {
        return TipoDocumento::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    public function registrarFuenteDesdeUpload(
        UploadedFile $archivo,
        Inscripcion $alumno,
        array $contexto,
        string $modoIntegracion,
        string $contenidoArchivo,
        ?int $usuarioId,
        bool $permitirDuplicado = false
    ): array {
        if (! in_array($modoIntegracion, ['agregar', 'reemplazar'], true)) {
            throw ValidationException::withMessages([
                'modo_integracion' => 'Selecciona si deseas agregar páginas o reemplazar el documento actual.',
            ]);
        }

        if (! in_array($contenidoArchivo, ['un_documento', 'varios_documentos'], true)) {
            throw ValidationException::withMessages([
                'contenido_archivo' => 'Indica si el archivo contiene un documento o varios documentos combinados.',
            ]);
        }

        $tipo = $this->validarContexto($contexto);
        $mimeOriginal = $this->validarMime($archivo);
        $tamano = (int) ($archivo->getSize() ?: File::size($archivo->getRealPath()));
        $limiteBytes = max((int) config('expedientes_organizador.max_upload_mb', 30), 1) * 1024 * 1024;

        if ($tamano > $limiteBytes) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo supera el límite de ' . config('expedientes_organizador.max_upload_mb', 30) . ' MB.',
            ]);
        }

        $hash = hash_file('sha256', $archivo->getRealPath()) ?: null;
        $duplicado = $hash
            ? DocumentoAlumnoFuente::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('hash_sha256', $hash)
                ->where('estado', 'activo')
                ->exists()
            : false;

        if ($duplicado && ! $permitirDuplicado) {
            throw ValidationException::withMessages([
                'archivo' => 'Este mismo archivo ya está registrado para el alumno. Activa “Permitir duplicado” únicamente si realmente deseas conservar otra copia.',
            ]);
        }

        $temporalPdf = $this->crearPdfTemporalDesdeArchivo($archivo, $mimeOriginal);

        try {
            $validacion = $this->validarPdf($temporalPdf);
            $this->sincronizarFuentesExistentes($alumno, $usuarioId);

            $uuid = (string) Str::uuid();
            $extensionOriginal = strtolower($archivo->getClientOriginalExtension() ?: $this->extensionDesdeMime($mimeOriginal));
            $directorio = "expedientes-organizador/fuentes/{$alumno->id}/{$uuid}";
            $rutaOriginal = "{$directorio}/original.{$extensionOriginal}";
            $rutaNormalizada = $mimeOriginal === 'application/pdf'
                ? $rutaOriginal
                : "{$directorio}/normalizado.pdf";
            $disk = Storage::disk($this->disk());
            $contenidoOriginal = File::get($archivo->getRealPath());
            $contenidoPdf = File::get($temporalPdf);
            $rutasGuardadas = [];

            if (! $disk->put($rutaOriginal, $contenidoOriginal)) {
                throw new RuntimeException('No fue posible guardar el archivo original.');
            }
            $rutasGuardadas[] = $rutaOriginal;

            if ($rutaNormalizada !== $rutaOriginal) {
                if (! $disk->put($rutaNormalizada, $contenidoPdf)) {
                    throw new RuntimeException('No fue posible guardar la copia PDF normalizada.');
                }
                $rutasGuardadas[] = $rutaNormalizada;
            }

            try {
                return DB::transaction(function () use (
                    $alumno,
                    $tipo,
                    $contexto,
                    $modoIntegracion,
                    $contenidoArchivo,
                    $usuarioId,
                    $archivo,
                    $mimeOriginal,
                    $tamano,
                    $hash,
                    $validacion,
                    $rutaOriginal,
                    $rutaNormalizada,
                    $rutasGuardadas
                ): array {
                    $documentoFuente = DocumentoAlumno::query()->create([
                        'inscripcion_id' => $alumno->id,
                        'tipo_documento_id' => $tipo->id,
                        'nivel_id' => $contexto['nivel_id'] ?? null,
                        'grado_id' => $contexto['grado_id'] ?? null,
                        'grupo_id' => $contexto['grupo_id'] ?? null,
                        'ciclo_escolar_id' => $contexto['ciclo_escolar_id'] ?? null,
                        'fecha_documento' => $contexto['fecha_documento'] ?? now()->toDateString(),
                        'folio' => filled($contexto['folio'] ?? null) ? trim((string) $contexto['folio']) : null,
                        'origen' => $contexto['origen'] ?? 'subido',
                        'tipo_movimiento' => $contexto['tipo_movimiento'] ?? null,
                        'motivo' => filled($contexto['motivo'] ?? null) ? trim((string) $contexto['motivo']) : null,
                        'disco' => $this->disk(),
                        'ruta' => $rutaNormalizada,
                        'nombre_original' => Str::limit($archivo->getClientOriginalName(), 250, ''),
                        'mime_type' => 'application/pdf',
                        'tamano_bytes' => $tamano,
                        'paginas_total' => (int) $validacion['paginas'],
                        'hash_sha256' => $hash,
                        'version' => 1,
                        'es_actual' => false,
                        'es_fuente' => true,
                        'es_organizado' => false,
                        'estado' => 'pendiente',
                        'observaciones' => filled($contexto['observaciones'] ?? null) ? trim((string) $contexto['observaciones']) : null,
                        'subido_por' => $usuarioId,
                    ]);

                    $fuente = DocumentoAlumnoFuente::query()->create([
                        'inscripcion_id' => $alumno->id,
                        'documento_alumno_id' => $documentoFuente->id,
                        'disco' => $this->disk(),
                        'ruta' => $rutaNormalizada,
                        'ruta_original' => $rutaOriginal,
                        'nombre_original' => Str::limit($archivo->getClientOriginalName(), 255, ''),
                        'nombre_almacenado' => basename($rutaNormalizada),
                        'mime_type' => 'application/pdf',
                        'mime_original' => $mimeOriginal,
                        'tamano_bytes' => $tamano,
                        'hash_sha256' => $hash,
                        'paginas' => (int) $validacion['paginas'],
                        'estado' => 'activo',
                        'protegido' => false,
                        'subido_por' => $usuarioId,
                        'metadatos' => [
                            'convertido_desde_imagen' => str_starts_with($mimeOriginal, 'image/'),
                            'modo_integracion' => $modoIntegracion,
                            'contenido_archivo' => $contenidoArchivo,
                            'contexto' => $this->normalizarContexto($contexto, $tipo),
                            'rutas_guardadas' => $rutasGuardadas,
                        ],
                    ]);

                    $borrador = $this->obtenerOCrearBorrador($alumno, $usuarioId);
                    $asignaciones = collect($borrador->asignaciones ?? []);
                    $contextoNormalizado = $this->normalizarContexto($contexto, $tipo);
                    $claveContexto = $this->claveContexto($contextoNormalizado);

                    if ($modoIntegracion === 'reemplazar') {
                        $asignaciones = $asignaciones->map(function (array $asignacion) use ($claveContexto): array {
                            if (($asignacion['contexto_clave'] ?? null) === $claveContexto) {
                                return $this->asignacionSinClasificar($asignacion);
                            }

                            return $asignacion;
                        });
                    }

                    $orden = ((int) $asignaciones
                        ->where('contexto_clave', $claveContexto)
                        ->max('orden')) + 1;

                    for ($pagina = 1; $pagina <= (int) $validacion['paginas']; $pagina++) {
                        $clasificada = $contenidoArchivo === 'un_documento';
                        $asignaciones->push(array_merge([
                            'fuente_id' => $fuente->id,
                            'pagina' => $pagina,
                            'rotacion' => 0,
                            'orden' => $clasificada ? $orden++ : 0,
                        ], $clasificada
                            ? $this->camposAsignacionContexto($contextoNormalizado)
                            : $this->camposAsignacionVacia()));
                    }

                    $borrador = $this->guardarBorrador(
                        $alumno,
                        $asignaciones->values()->all(),
                        $usuarioId,
                        $borrador->id,
                        $borrador->retiros_confirmados ?? []
                    );

                    return [
                        'fuente' => $fuente->fresh(),
                        'paginas' => (int) $validacion['paginas'],
                        'organizacion_id' => $borrador->id,
                        'requiere_organizacion' => true,
                        'duplicado' => $hash !== null && DocumentoAlumnoFuente::query()
                            ->where('inscripcion_id', $alumno->id)
                            ->where('hash_sha256', $hash)
                            ->whereKeyNot($fuente->id)
                            ->exists(),
                    ];
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
        } finally {
            File::delete($temporalPdf);
        }
    }

    public function datosOrganizador(Inscripcion $alumno, ?int $usuarioId = null): array
    {
        $this->sincronizarFuentesExistentes($alumno, $usuarioId);
        $borrador = $this->obtenerOCrearBorrador($alumno, $usuarioId);
        $fuentes = DocumentoAlumnoFuente::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('estado', 'activo')
            ->where('protegido', false)
            ->with(['documentoAlumno.tipoDocumento:id,nombre,slug'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($fuentes as $fuente) {
            $this->refrescarConteoPaginas($fuente);
        }

        $fuentes = $fuentes->fresh(['documentoAlumno.tipoDocumento:id,nombre,slug']);
        $asignaciones = $this->normalizarAsignaciones(
            $borrador->asignaciones ?? [],
            $fuentes,
            $this->tiposOrganizables()
        );

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
            'tipos' => $this->tiposOrganizables(),
            'historial' => OrganizacionDocumentoAlumno::query()
                ->where('inscripcion_id', $alumno->id)
                ->whereIn('estado', ['confirmado', 'error'])
                ->with('usuarioConfirmacion:id,name')
                ->latest('version')
                ->limit(10)
                ->get(),
            'contextos_existentes' => $this->contextosDesdeAsignaciones(
                (array) data_get($borrador->metadatos, 'baseline_asignaciones', [])
            ),
        ];
    }

    public function guardarBorrador(
        Inscripcion $alumno,
        array $asignaciones,
        ?int $usuarioId,
        ?int $organizacionId = null,
        array $retirosConfirmados = []
    ): OrganizacionDocumentoAlumno {
        $fuentes = DocumentoAlumnoFuente::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('estado', 'activo')
            ->where('protegido', false)
            ->get();
        $normalizadas = $this->normalizarAsignaciones($asignaciones, $fuentes, $this->tiposOrganizables());

        return DB::transaction(function () use ($alumno, $usuarioId, $organizacionId, $normalizadas, $fuentes, $retirosConfirmados): OrganizacionDocumentoAlumno {
            $borrador = $organizacionId
                ? OrganizacionDocumentoAlumno::query()
                    ->where('inscripcion_id', $alumno->id)
                    ->where('estado', 'borrador')
                    ->lockForUpdate()
                    ->find($organizacionId)
                : null;

            $borrador ??= OrganizacionDocumentoAlumno::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('estado', 'borrador')
                ->lockForUpdate()
                ->latest('version')
                ->first();

            if (! $borrador) {
                $version = ((int) OrganizacionDocumentoAlumno::query()
                    ->where('inscripcion_id', $alumno->id)
                    ->lockForUpdate()
                    ->max('version')) + 1;
                $borrador = OrganizacionDocumentoAlumno::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'version' => $version,
                    'estado' => 'borrador',
                    'asignaciones' => [],
                    'fuentes_ids' => [],
                    'retiros_confirmados' => [],
                    'metadatos' => [
                        'creado_por' => $usuarioId,
                        'baseline_asignaciones' => [],
                        'baseline_firmas' => [],
                    ],
                ]);
            }

            $borrador->forceFill([
                'asignaciones' => $normalizadas,
                'fuentes_ids' => $fuentes->pluck('id')->values()->all(),
                'retiros_confirmados' => array_values(array_unique(array_filter($retirosConfirmados))),
                'error' => null,
                'metadatos' => array_merge($borrador->metadatos ?? [], [
                    'actualizado_por' => $usuarioId,
                    'paginas_sin_clasificar' => collect($normalizadas)->whereNull('tipo_documento_id')->count(),
                ]),
            ])->save();

            return $borrador->fresh();
        });
    }

    public function confirmarOrganizacion(
        Inscripcion $alumno,
        int $organizacionId,
        ?int $usuarioId,
        bool $forzarSinCola = false
    ): array {
        $organizacion = OrganizacionDocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('estado', 'borrador')
            ->findOrFail($organizacionId);

        $this->validarOrganizacionConfirmable($organizacion);

        if (! $forzarSinCola && $this->requiereCola($organizacion)) {
            $organizacion->forceFill([
                'estado' => 'procesando',
                'confirmado_por' => $usuarioId,
                'metadatos' => array_merge($organizacion->metadatos ?? [], ['encolado_at' => now()->toIso8601String()]),
            ])->save();

            ConfirmarOrganizacionExpedienteJob::dispatch($organizacion->id, $usuarioId);

            return ['encolado' => true, 'organizacion' => $organizacion->fresh()];
        }

        $this->procesarConfirmacion($organizacion, $usuarioId);

        return ['encolado' => false, 'organizacion' => $organizacion->fresh()];
    }

    public function procesarConfirmacion(OrganizacionDocumentoAlumno $organizacion, ?int $usuarioId): void
    {
        $organizacion->refresh();

        if (! in_array($organizacion->estado, ['borrador', 'procesando'], true)) {
            return;
        }

        try {
            $this->validarOrganizacionConfirmable($organizacion);
            $alumno = Inscripcion::withTrashed()->findOrFail($organizacion->inscripcion_id);
            $asignaciones = collect($organizacion->asignaciones ?? [])->whereNotNull('tipo_documento_id');
            $grupos = $asignaciones->groupBy(fn (array $item): string => (string) $item['contexto_clave']);
            $baselineAsignaciones = collect((array) data_get($organizacion->metadatos, 'baseline_asignaciones', []))
                ->whereNotNull('tipo_documento_id');
            $baseline = $baselineAsignaciones->groupBy(fn (array $item): string => (string) $item['contexto_clave']);
            $retiros = collect($organizacion->retiros_confirmados ?? []);

            $faltanConfirmaciones = $baseline->keys()
                ->diff($grupos->keys())
                ->reject(fn (string $clave): bool => $retiros->contains($clave));

            if ($faltanConfirmaciones->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'organizacion' => 'Para retirar completamente un documento debes confirmar su retiro de forma explícita.',
                ]);
            }

            DB::transaction(function () use ($organizacion, $alumno, $usuarioId, $grupos, $baseline, $retiros): void {
                foreach ($grupos as $clave => $grupo) {
                    $firmaActual = $this->firmaGrupo($grupo);
                    $firmaBase = $baseline->has($clave) ? $this->firmaGrupo($baseline->get($clave)) : null;

                    if ($firmaBase === $firmaActual) {
                        continue;
                    }

                    $this->guardarDocumentoOrganizado($alumno, $organizacion, $grupo, $usuarioId);
                }

                foreach ($retiros as $clave) {
                    if ($grupos->has($clave)) {
                        continue;
                    }

                    $contexto = $this->contextoDesdeClave($clave);
                    $this->marcarContextoReemplazado($alumno->id, $contexto);
                }

                $organizacion->forceFill([
                    'estado' => 'confirmado',
                    'confirmado_por' => $usuarioId,
                    'confirmado_at' => now(),
                    'error' => null,
                    'metadatos' => array_merge($organizacion->metadatos ?? [], [
                        'confirmado_por' => $usuarioId,
                        'paginas_clasificadas' => $grupos->flatten(1)->count(),
                        'paginas_sin_clasificar' => collect($organizacion->asignaciones ?? [])->whereNull('tipo_documento_id')->count(),
                    ]),
                ])->save();
            });
        } catch (Throwable $e) {
            $organizacion->forceFill([
                'estado' => 'error',
                'error' => Str::limit($e->getMessage(), 4000, ''),
                'metadatos' => array_merge($organizacion->metadatos ?? [], ['error_at' => now()->toIso8601String()]),
            ])->save();
            throw $e;
        }
    }

    public function rutaVistaPagina(DocumentoAlumnoFuente $fuente, int $pagina, int $rotacion = 0): string
    {
        abort_unless($fuente->estado === 'activo' && ! $fuente->protegido, 404);
        abort_unless($pagina >= 1 && $pagina <= max((int) $fuente->paginas, 1), 404);
        $rotacion = $this->normalizarRotacion($rotacion);
        $directorio = storage_path('app/temp/expedientes-organizador/previews');
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

    public function limpiarTemporales(): int
    {
        $directorios = [
            storage_path('app/temp/expedientes-organizador'),
        ];
        $limite = now()->subHours(max((int) config('expedientes_organizador.preview_ttl_hours', 24), 1))->timestamp;
        $eliminados = 0;

        foreach ($directorios as $directorio) {
            if (! is_dir($directorio)) {
                continue;
            }

            foreach (File::allFiles($directorio) as $archivo) {
                if ($archivo->getMTime() < $limite && @unlink($archivo->getPathname())) {
                    $eliminados++;
                }
            }
        }

        return $eliminados;
    }

    /**
     * Recalcula el número de páginas y la disponibilidad física de las fuentes
     * sin crear ni modificar un borrador de organización.
     *
     * @return array{fuentes:int, activas:int, inconsistentes:int, paginas:int}
     */
    public function actualizarConteosFuentes(Inscripcion $alumno): array
    {
        $fuentes = DocumentoAlumnoFuente::query()
            ->where('inscripcion_id', $alumno->id)
            ->get();

        foreach ($fuentes as $fuente) {
            $this->refrescarConteoPaginas($fuente);
        }

        $fuentes = DocumentoAlumnoFuente::query()
            ->where('inscripcion_id', $alumno->id)
            ->get(['id', 'estado', 'paginas']);

        return [
            'fuentes' => $fuentes->count(),
            'activas' => $fuentes->where('estado', 'activo')->count(),
            'inconsistentes' => $fuentes->where('estado', 'inconsistente')->count(),
            'paginas' => (int) $fuentes->where('estado', 'activo')->sum('paginas'),
        ];
    }

    public function sincronizarFuentesExistentes(Inscripcion $alumno, ?int $usuarioId = null): void
    {
        $documentos = DocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('es_fuente', false)
            ->where('es_organizado', false)
            ->whereDoesntHave('fuente')
            ->get();

        foreach ($documentos as $documento) {
            DocumentoAlumnoFuente::query()->firstOrCreate(
                ['documento_alumno_id' => $documento->id],
                [
                    'inscripcion_id' => $alumno->id,
                    'disco' => $documento->disco ?: $this->disk(),
                    'ruta' => (string) $documento->ruta,
                    'ruta_original' => (string) $documento->ruta,
                    'nombre_original' => (string) $documento->nombre_original,
                    'nombre_almacenado' => basename((string) $documento->ruta),
                    'mime_type' => (string) ($documento->mime_type ?: 'application/octet-stream'),
                    'mime_original' => (string) ($documento->mime_type ?: 'application/octet-stream'),
                    'tamano_bytes' => (int) $documento->tamano_bytes,
                    'hash_sha256' => $documento->hash_sha256,
                    'paginas' => max((int) ($documento->paginas_total ?? 1), 1),
                    'estado' => $documento->archivo_existe ? 'activo' : 'inconsistente',
                    'protegido' => ! $documento->es_organizable,
                    'subido_por' => $documento->subido_por ?: $usuarioId,
                    'metadatos' => [
                        'migrado' => true,
                        'conteo_paginas_pendiente' => true,
                        'contexto' => $this->contextoDesdeDocumento($documento),
                    ],
                ]
            );
        }
    }

    public function contextosDesdeAsignaciones(array $asignaciones): array
    {
        return collect($asignaciones)
            ->whereNotNull('tipo_documento_id')
            ->groupBy('contexto_clave')
            ->map(function (Collection $grupo, string $clave): array {
                $primera = $grupo->first();

                return [
                    'clave' => $clave,
                    'tipo_documento_id' => $primera['tipo_documento_id'],
                    'tipo_nombre' => $primera['tipo_nombre'] ?? 'Documento',
                    'nivel_id' => $primera['nivel_id'] ?? null,
                    'grado_id' => $primera['grado_id'] ?? null,
                    'grupo_id' => $primera['grupo_id'] ?? null,
                    'ciclo_escolar_id' => $primera['ciclo_escolar_id'] ?? null,
                    'paginas' => $grupo->count(),
                ];
            })
            ->values()
            ->all();
    }

    protected function obtenerOCrearBorrador(Inscripcion $alumno, ?int $usuarioId): OrganizacionDocumentoAlumno
    {
        $borrador = OrganizacionDocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('estado', 'borrador')
            ->latest('version')
            ->first();

        if ($borrador) {
            return $borrador;
        }

        $ultima = OrganizacionDocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('estado', 'confirmado')
            ->latest('version')
            ->first();

        $asignacionesBase = $ultima?->asignaciones ?? $this->asignacionesDesdeDocumentosActuales($alumno);
        $version = ((int) OrganizacionDocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->max('version')) + 1;

        return OrganizacionDocumentoAlumno::query()->create([
            'inscripcion_id' => $alumno->id,
            'version' => $version,
            'estado' => 'borrador',
            'asignaciones' => $asignacionesBase,
            'fuentes_ids' => collect($asignacionesBase)->pluck('fuente_id')->filter()->unique()->values()->all(),
            'retiros_confirmados' => [],
            'metadatos' => [
                'creado_por' => $usuarioId,
                'baseline_asignaciones' => $asignacionesBase,
                'baseline_firmas' => $this->firmasPorContexto(collect($asignacionesBase)),
            ],
        ]);
    }

    protected function asignacionesDesdeDocumentosActuales(Inscripcion $alumno): array
    {
        $documentos = DocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('es_actual', true)
            ->where('es_fuente', false)
            ->whereNotIn('estado', ['rechazado', 'reemplazado', 'cancelada'])
            ->with(['tipoDocumento:id,nombre,slug,requiere_nivel', 'fuente'])
            ->get()
            ->filter(fn (DocumentoAlumno $documento): bool => $documento->es_organizable && $documento->fuente?->estado === 'activo');
        $asignaciones = [];

        foreach ($documentos as $documento) {
            $fuente = $documento->fuente;
            $this->refrescarConteoPaginas($fuente);
            $contexto = $this->contextoDesdeDocumento($documento);
            $campos = $this->camposAsignacionContexto($contexto);

            for ($pagina = 1; $pagina <= max((int) $fuente->paginas, 1); $pagina++) {
                $asignaciones[] = array_merge([
                    'fuente_id' => $fuente->id,
                    'pagina' => $pagina,
                    'orden' => $pagina,
                    'rotacion' => 0,
                ], $campos);
            }
        }

        return $asignaciones;
    }

    protected function guardarDocumentoOrganizado(
        Inscripcion $alumno,
        OrganizacionDocumentoAlumno $organizacion,
        Collection $grupo,
        ?int $usuarioId
    ): DocumentoAlumno {
        $primera = $grupo->first();
        $tipo = TipoDocumento::query()->findOrFail((int) $primera['tipo_documento_id']);
        $contexto = [
            'tipo_documento_id' => $tipo->id,
            'tipo_slug' => $tipo->slug,
            'tipo_nombre' => $tipo->nombre,
            'nivel_id' => $primera['nivel_id'] ?? null,
            'grado_id' => $primera['grado_id'] ?? null,
            'grupo_id' => $primera['grupo_id'] ?? null,
            'ciclo_escolar_id' => $primera['ciclo_escolar_id'] ?? null,
            'fecha_documento' => $primera['fecha_documento'] ?? now()->toDateString(),
            'folio' => $primera['folio'] ?? null,
            'origen' => 'organizado',
            'tipo_movimiento' => $primera['tipo_movimiento'] ?? null,
            'motivo' => $primera['motivo'] ?? null,
            'observaciones' => $primera['observaciones'] ?? null,
        ];
        $temporal = $this->generarPdfGrupo($grupo->sortBy('orden')->values());
        $contenido = File::get($temporal);
        $ruta = 'expedientes-organizados/' . $alumno->id . '/' . $tipo->slug . '/' . Str::uuid() . '.pdf';
        $disk = Storage::disk($this->disk());

        try {
            if (! $disk->put($ruta, $contenido)) {
                throw new RuntimeException('No fue posible almacenar el documento organizado.');
            }

            $consulta = DocumentoAlumno::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('tipo_documento_id', $tipo->id)
                ->where('es_fuente', false)
                ->where(fn ($query) => $this->aplicarContextoQuery($query, $contexto))
                ->lockForUpdate();
            $version = ((int) (clone $consulta)->max('version')) + 1;

            (clone $consulta)->where('es_actual', true)->update([
                'es_actual' => false,
                'estado' => 'reemplazado',
                'updated_at' => now(),
            ]);

            $documento = DocumentoAlumno::query()->create([
                'inscripcion_id' => $alumno->id,
                'organizacion_id' => $organizacion->id,
                'tipo_documento_id' => $tipo->id,
                'nivel_id' => $contexto['nivel_id'],
                'grado_id' => $contexto['grado_id'],
                'grupo_id' => $contexto['grupo_id'],
                'ciclo_escolar_id' => $contexto['ciclo_escolar_id'],
                'fecha_documento' => $contexto['fecha_documento'],
                'folio' => $contexto['folio'],
                'origen' => 'organizado',
                'tipo_movimiento' => $contexto['tipo_movimiento'],
                'motivo' => $contexto['motivo'],
                'disco' => $this->disk(),
                'ruta' => $ruta,
                'nombre_original' => $this->nombreDocumentoOrganizado($tipo, $contexto) . '.pdf',
                'mime_type' => 'application/pdf',
                'tamano_bytes' => strlen($contenido),
                'paginas_total' => $grupo->count(),
                'hash_sha256' => hash('sha256', $contenido),
                'version' => $version,
                'es_actual' => true,
                'es_fuente' => false,
                'es_organizado' => true,
                'estado' => 'recibido',
                'observaciones' => $contexto['observaciones'],
                'subido_por' => $usuarioId,
                'validado_por' => null,
                'validado_at' => null,
            ]);

            $noAplica = DocumentoAlumnoNoAplica::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('tipo_documento_id', $tipo->id)
                ->where('activo', true);
            foreach (['nivel_id', 'grado_id', 'ciclo_escolar_id'] as $campo) {
                $valor = $contexto[$campo] ?? null;
                $valor ? $noAplica->where($campo, $valor) : $noAplica->whereNull($campo);
            }
            $noAplica->update(['activo' => false, 'updated_at' => now()]);

            if ($tipo->slug === 'constancia-baja-traslado' && filled($contexto['tipo_movimiento'])) {
                MovimientoAlumno::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'documento_alumno_id' => $documento->id,
                    'tipo' => $contexto['tipo_movimiento'],
                    'fecha' => $contexto['fecha_documento'],
                    'motivo' => $contexto['motivo'],
                    'observaciones' => $contexto['observaciones'] ?: 'Documento externo organizado sin modificar el estado de la inscripción.',
                    'registrado_por' => $usuarioId,
                ]);
            }

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
        $directorio = storage_path('app/temp/expedientes-organizador/generados');
        File::ensureDirectoryExists($directorio);
        $destino = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.pdf';
        $pdf = new RotatableFpdi();
        $temporales = [];

        try {
            foreach ($grupo as $pagina) {
                $fuente = DocumentoAlumnoFuente::query()->findOrFail((int) $pagina['fuente_id']);
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

    protected function validarOrganizacionConfirmable(OrganizacionDocumentoAlumno $organizacion): void
    {
        $fuentes = DocumentoAlumnoFuente::query()
            ->where('inscripcion_id', $organizacion->inscripcion_id)
            ->where('estado', 'activo')
            ->where('protegido', false)
            ->get();
        $normalizadas = $this->normalizarAsignaciones(
            $organizacion->asignaciones ?? [],
            $fuentes,
            $this->tiposOrganizables()
        );

        foreach ($normalizadas as $asignacion) {
            if ($asignacion['tipo_documento_id'] === null) {
                continue;
            }

            $this->validarContexto($asignacion);
        }

        if (collect($normalizadas)->whereNotNull('tipo_documento_id')->isEmpty()) {
            throw ValidationException::withMessages([
                'organizacion' => 'Asigna al menos una página antes de confirmar la organización.',
            ]);
        }

        $organizacion->forceFill(['asignaciones' => $normalizadas])->save();
    }

    protected function normalizarAsignaciones(array $asignaciones, Collection $fuentes, Collection $tipos): array
    {
        $fuentesPorId = $fuentes->keyBy('id');
        $tiposPorId = $tipos->keyBy('id');
        $vistas = [];
        $normalizadas = [];

        foreach ($asignaciones as $asignacion) {
            $fuenteId = (int) ($asignacion['fuente_id'] ?? 0);
            $pagina = (int) ($asignacion['pagina'] ?? 0);
            $fuente = $fuentesPorId->get($fuenteId);

            if (! $fuente || $pagina < 1 || $pagina > max((int) $fuente->paginas, 1)) {
                continue;
            }

            $clavePagina = $this->clavePagina($fuenteId, $pagina);
            if (isset($vistas[$clavePagina])) {
                continue;
            }
            $vistas[$clavePagina] = true;

            $tipoId = filled($asignacion['tipo_documento_id'] ?? null)
                ? (int) $asignacion['tipo_documento_id']
                : null;
            $tipo = $tipoId ? $tiposPorId->get($tipoId) : null;

            if (! $tipo) {
                $tipoId = null;
            }

            $contexto = $tipo
                ? $this->normalizarContexto($asignacion, $tipo)
                : null;

            $normalizadas[] = array_merge([
                'fuente_id' => $fuenteId,
                'pagina' => $pagina,
                'rotacion' => $this->normalizarRotacion((int) ($asignacion['rotacion'] ?? 0)),
                'orden' => $tipo ? max((int) ($asignacion['orden'] ?? 0), 1) : 0,
            ], $tipo ? $this->camposAsignacionContexto($contexto) : $this->camposAsignacionVacia());
        }

        foreach ($fuentes as $fuente) {
            for ($pagina = 1; $pagina <= max((int) $fuente->paginas, 1); $pagina++) {
                $clave = $this->clavePagina($fuente->id, $pagina);
                if (! isset($vistas[$clave])) {
                    $normalizadas[] = array_merge([
                        'fuente_id' => $fuente->id,
                        'pagina' => $pagina,
                        'rotacion' => 0,
                        'orden' => 0,
                    ], $this->camposAsignacionVacia());
                }
            }
        }

        $coleccion = collect($normalizadas);
        foreach ($coleccion->whereNotNull('tipo_documento_id')->groupBy('contexto_clave') as $clave => $grupo) {
            $orden = 1;
            foreach ($grupo->sortBy(fn (array $item): string => str_pad((string) ($item['orden'] ?: 999999), 8, '0', STR_PAD_LEFT)
                . '-' . str_pad((string) $item['fuente_id'], 10, '0', STR_PAD_LEFT)
                . '-' . str_pad((string) $item['pagina'], 6, '0', STR_PAD_LEFT)) as $item) {
                $indice = $coleccion->search(fn (array $actual): bool => $this->clavePagina((int) $actual['fuente_id'], (int) $actual['pagina']) === $this->clavePagina((int) $item['fuente_id'], (int) $item['pagina']));
                $item['orden'] = $orden++;
                $coleccion->put($indice, $item);
            }
        }

        return $coleccion
            ->sortBy(fn (array $item): string => ($item['contexto_clave'] ?? 'zzzz_sin_clasificar')
                . '-' . str_pad((string) $item['orden'], 8, '0', STR_PAD_LEFT)
                . '-' . str_pad((string) $item['fuente_id'], 10, '0', STR_PAD_LEFT)
                . '-' . str_pad((string) $item['pagina'], 6, '0', STR_PAD_LEFT))
            ->values()
            ->all();
    }

    protected function validarContexto(array $contexto): TipoDocumento
    {
        $tipoId = (int) ($contexto['tipo_documento_id'] ?? 0);
        $tipo = TipoDocumento::query()->where('activo', true)->find($tipoId);

        if (! $tipo) {
            throw ValidationException::withMessages(['organizacion' => 'Selecciona un tipo documental válido.']);
        }

        $nivelId = filled($contexto['nivel_id'] ?? null) ? (int) $contexto['nivel_id'] : null;
        $gradoId = filled($contexto['grado_id'] ?? null) ? (int) $contexto['grado_id'] : null;
        $grupoId = filled($contexto['grupo_id'] ?? null) ? (int) $contexto['grupo_id'] : null;
        $cicloId = filled($contexto['ciclo_escolar_id'] ?? null) ? (int) $contexto['ciclo_escolar_id'] : null;

        if ($tipo->requiere_nivel && ! $nivelId) {
            throw ValidationException::withMessages(['organizacion' => "Selecciona el nivel para {$tipo->nombre}."]);
        }

        $requiereGradoCiclo = in_array($tipo->slug, [
            'boleta-final-grado',
            'constancia-estudios',
            'constancia-baja-traslado',
            'constancia-traslado-calificaciones',
        ], true);

        if ($requiereGradoCiclo && (! $gradoId || ! $cicloId)) {
            throw ValidationException::withMessages(['organizacion' => "Selecciona grado y ciclo escolar para {$tipo->nombre}."]);
        }

        if ($gradoId && ! Grado::query()->whereKey($gradoId)->when($nivelId, fn ($q) => $q->where('nivel_id', $nivelId))->exists()) {
            throw ValidationException::withMessages(['organizacion' => 'El grado no pertenece al nivel seleccionado.']);
        }

        if ($grupoId && ! Grupo::query()->whereKey($grupoId)
            ->when($nivelId, fn ($q) => $q->where('nivel_id', $nivelId))
            ->when($gradoId, fn ($q) => $q->where('grado_id', $gradoId))
            ->exists()) {
            throw ValidationException::withMessages(['organizacion' => 'El grupo no pertenece al nivel y grado seleccionados.']);
        }

        return $tipo;
    }

    protected function normalizarContexto(array $contexto, TipoDocumento $tipo): array
    {
        $resultado = [
            'tipo_documento_id' => $tipo->id,
            'tipo_slug' => $tipo->slug,
            'tipo_nombre' => $tipo->nombre,
            'requiere_nivel' => (bool) $tipo->requiere_nivel,
            'nivel_id' => filled($contexto['nivel_id'] ?? null) ? (int) $contexto['nivel_id'] : null,
            'grado_id' => filled($contexto['grado_id'] ?? null) ? (int) $contexto['grado_id'] : null,
            'grupo_id' => filled($contexto['grupo_id'] ?? null) ? (int) $contexto['grupo_id'] : null,
            'ciclo_escolar_id' => filled($contexto['ciclo_escolar_id'] ?? null) ? (int) $contexto['ciclo_escolar_id'] : null,
            'fecha_documento' => filled($contexto['fecha_documento'] ?? null) ? (string) $contexto['fecha_documento'] : now()->toDateString(),
            'folio' => filled($contexto['folio'] ?? null) ? trim((string) $contexto['folio']) : null,
            'origen' => filled($contexto['origen'] ?? null) ? (string) $contexto['origen'] : 'subido',
            'tipo_movimiento' => filled($contexto['tipo_movimiento'] ?? null) ? (string) $contexto['tipo_movimiento'] : null,
            'motivo' => filled($contexto['motivo'] ?? null) ? trim((string) $contexto['motivo']) : null,
            'observaciones' => filled($contexto['observaciones'] ?? null) ? trim((string) $contexto['observaciones']) : null,
        ];
        $resultado['contexto_clave'] = $this->claveContexto($resultado);

        return $resultado;
    }

    protected function contextoDesdeDocumento(DocumentoAlumno $documento): array
    {
        $tipo = $documento->tipoDocumento ?: TipoDocumento::query()->findOrFail($documento->tipo_documento_id);

        return $this->normalizarContexto([
            'tipo_documento_id' => $tipo->id,
            'nivel_id' => $documento->nivel_id,
            'grado_id' => $documento->grado_id,
            'grupo_id' => $documento->grupo_id,
            'ciclo_escolar_id' => $documento->ciclo_escolar_id,
            'fecha_documento' => $documento->fecha_documento?->toDateString(),
            'folio' => $documento->folio,
            'origen' => $documento->origen,
            'tipo_movimiento' => $documento->tipo_movimiento,
            'motivo' => $documento->motivo,
            'observaciones' => $documento->observaciones,
        ], $tipo);
    }

    protected function camposAsignacionContexto(array $contexto): array
    {
        return [
            'tipo_documento_id' => $contexto['tipo_documento_id'],
            'tipo_slug' => $contexto['tipo_slug'],
            'tipo_nombre' => $contexto['tipo_nombre'],
            'contexto_clave' => $contexto['contexto_clave'],
            'nivel_id' => $contexto['nivel_id'],
            'grado_id' => $contexto['grado_id'],
            'grupo_id' => $contexto['grupo_id'],
            'ciclo_escolar_id' => $contexto['ciclo_escolar_id'],
            'fecha_documento' => $contexto['fecha_documento'],
            'folio' => $contexto['folio'],
            'origen' => $contexto['origen'],
            'tipo_movimiento' => $contexto['tipo_movimiento'],
            'motivo' => $contexto['motivo'],
            'observaciones' => $contexto['observaciones'],
        ];
    }

    protected function camposAsignacionVacia(): array
    {
        return [
            'tipo_documento_id' => null,
            'tipo_slug' => null,
            'tipo_nombre' => null,
            'contexto_clave' => null,
            'nivel_id' => null,
            'grado_id' => null,
            'grupo_id' => null,
            'ciclo_escolar_id' => null,
            'fecha_documento' => null,
            'folio' => null,
            'origen' => null,
            'tipo_movimiento' => null,
            'motivo' => null,
            'observaciones' => null,
        ];
    }

    protected function asignacionSinClasificar(array $asignacion): array
    {
        return array_merge($asignacion, ['orden' => 0], $this->camposAsignacionVacia());
    }

    protected function claveContexto(array $contexto): string
    {
        return implode('|', [
            (int) ($contexto['tipo_documento_id'] ?? 0),
            (int) ($contexto['nivel_id'] ?? 0),
            (int) ($contexto['grado_id'] ?? 0),
            (int) ($contexto['grupo_id'] ?? 0),
            (int) ($contexto['ciclo_escolar_id'] ?? 0),
        ]);
    }

    protected function contextoDesdeClave(string $clave): array
    {
        $partes = array_pad(array_map('intval', explode('|', $clave)), 5, 0);

        return [
            'tipo_documento_id' => $partes[0] ?: null,
            'nivel_id' => $partes[1] ?: null,
            'grado_id' => $partes[2] ?: null,
            'grupo_id' => $partes[3] ?: null,
            'ciclo_escolar_id' => $partes[4] ?: null,
        ];
    }

    protected function aplicarContextoQuery($query, array $contexto): void
    {
        foreach (['nivel_id', 'grado_id', 'grupo_id', 'ciclo_escolar_id'] as $campo) {
            $valor = $contexto[$campo] ?? null;
            $valor ? $query->where($campo, $valor) : $query->whereNull($campo);
        }
    }

    protected function marcarContextoReemplazado(int $alumnoId, array $contexto): void
    {
        $query = DocumentoAlumno::query()
            ->where('inscripcion_id', $alumnoId)
            ->where('tipo_documento_id', $contexto['tipo_documento_id'])
            ->where('es_fuente', false)
            ->where('es_actual', true);
        $this->aplicarContextoQuery($query, $contexto);
        $query->update(['es_actual' => false, 'estado' => 'reemplazado', 'updated_at' => now()]);
    }

    protected function firmaGrupo(Collection $grupo): string
    {
        return hash('sha256', json_encode($grupo
            ->sortBy('orden')
            ->values()
            ->map(fn (array $item): array => [
                'fuente_id' => (int) $item['fuente_id'],
                'pagina' => (int) $item['pagina'],
                'rotacion' => (int) $item['rotacion'],
                'orden' => (int) $item['orden'],
                'contexto_clave' => $item['contexto_clave'],
            ])->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function firmasPorContexto(Collection $asignaciones): array
    {
        return $asignaciones->whereNotNull('tipo_documento_id')
            ->groupBy('contexto_clave')
            ->map(fn (Collection $grupo): string => $this->firmaGrupo($grupo))
            ->all();
    }

    protected function requiereCola(OrganizacionDocumentoAlumno $organizacion): bool
    {
        $asignaciones = collect($organizacion->asignaciones ?? [])->whereNotNull('tipo_documento_id');
        $fuentesIds = $asignaciones->pluck('fuente_id')->unique();
        $fuentes = DocumentoAlumnoFuente::query()->whereKey($fuentesIds->all())->get();
        $bytes = $fuentes->sum('tamano_bytes');
        $paginas = $asignaciones->count();

        return $bytes > max((int) config('expedientes_organizador.queue_threshold_mb', 20), 1) * 1024 * 1024
            || $paginas > max((int) config('expedientes_organizador.queue_threshold_pages', 30), 1);
    }

    protected function crearPdfTemporalDesdeArchivo(UploadedFile $archivo, string $mime): string
    {
        $directorio = storage_path('app/temp/expedientes-organizador/uploads');
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
                throw ValidationException::withMessages([
                    'archivo' => 'El servidor no tiene habilitado el soporte WEBP de GD.',
                ]);
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

    protected function validarPdf(string $ruta): array
    {
        try {
            $fpdi = new Fpdi();
            $paginas = $fpdi->setSourceFile($ruta);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'archivo' => 'El PDF está protegido con contraseña, dañado o usa una estructura incompatible. Guarda una copia PDF estándar sin contraseña e inténtalo nuevamente.',
            ]);
        }

        $maximo = max((int) config('expedientes_organizador.max_pages', 50), 1);
        if ($paginas < 1) {
            throw ValidationException::withMessages(['archivo' => 'El PDF no contiene páginas utilizables.']);
        }
        if ($paginas > $maximo) {
            throw ValidationException::withMessages(['archivo' => "El archivo contiene {$paginas} páginas y el límite configurado es {$maximo}."]);
        }

        return ['paginas' => $paginas];
    }

    protected function refrescarConteoPaginas(DocumentoAlumnoFuente $fuente): void
    {
        $pendiente = (bool) data_get($fuente->metadatos, 'conteo_paginas_pendiente', false);
        if (! $pendiente || $fuente->estado !== 'activo' || $fuente->protegido) {
            return;
        }

        $temporal = null;

        try {
            $ruta = $this->rutaLocalFuente($fuente, $temporal);
            $paginas = (int) $this->validarPdf($ruta)['paginas'];
            $metadatos = $fuente->metadatos ?? [];
            data_forget($metadatos, 'conteo_paginas_pendiente');
            $fuente->forceFill(['paginas' => $paginas, 'metadatos' => $metadatos])->save();
            if ($fuente->documentoAlumno) {
                $fuente->documentoAlumno->forceFill(['paginas_total' => $paginas])->saveQuietly();
            }
        } catch (Throwable $e) {
            $fuente->forceFill([
                'estado' => 'inconsistente',
                'metadatos' => array_merge($fuente->metadatos ?? [], ['error_conteo' => $e->getMessage()]),
            ])->save();
        } finally {
            if ($temporal) {
                File::delete($temporal);
            }
        }
    }

    protected function rutaLocalFuente(DocumentoAlumnoFuente $fuente, ?string &$temporal = null): string
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

        $directorio = storage_path('app/temp/expedientes-organizador/remotos');
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
            throw ValidationException::withMessages([
                'archivo' => 'El archivo debe ser PDF, JPG, JPEG, PNG o WEBP.',
            ]);
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

    protected function clavePagina(int $fuenteId, int $pagina): string
    {
        return $fuenteId . ':' . $pagina;
    }

    protected function nombreDocumentoOrganizado(TipoDocumento $tipo, array $contexto): string
    {
        $segmentos = [$tipo->slug];
        foreach (['nivel_id' => 'nivel', 'grado_id' => 'grado', 'ciclo_escolar_id' => 'ciclo'] as $campo => $prefijo) {
            if ($contexto[$campo] ?? null) {
                $segmentos[] = $prefijo . '-' . $contexto[$campo];
            }
        }

        return Str::slug(implode('-', $segmentos), '_');
    }
}
