<?php

declare(strict_types=1);

/**
 * Moctezuma Básica
 * Sincronización: Anulación administrativa <-> Continuidad confirmada
 *
 * Corrige el caso:
 *   proyección = confirmada
 *   ciclo destino = anulado
 *   resultado_final = anulacion_administrativa
 *
 * Resultado:
 *   proyección = revertida
 *   tipo_reversion = anulacion_administrativa
 *
 * Además:
 * - deja de mostrar "Cambiar a No continuará";
 * - muestra "Anulada administrativamente";
 * - impide reactivar esa continuidad desde el botón "Cambiar a Continuará";
 * - conserva el ciclo de origen;
 * - conserva auditoría;
 * - repara automáticamente inconsistencias ya existentes.
 *
 * Ejecutar desde la raíz del proyecto:
 *   php aplicar_sincronizacion_continuidad_anulacion.php
 */

$root = getcwd();
$backupSuffix = '.bak-sync-anulacion-continuidad-20260901';

$files = [
    'cierre' => 'app/Services/CierreGeneracionContinuidadService.php',
    'admin' => 'app/Services/AnulacionAdministrativaInscripcionService.php',
    'component' => 'app/Livewire/Accion/Generales/ProyeccionesContinuidad.php',
    'model' => 'app/Models/ProyeccionContinuidad.php',
    'view' => 'resources/views/livewire/accion/generales/proyecciones-continuidad.blade.php',
];

foreach ($files as $relative) {
    $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

    if (!is_file($full)) {
        fwrite(STDERR, "ERROR: No se encontró {$relative}.\n");
        fwrite(STDERR, "No se aplicó la corrección. Ejecuta el script desde la raíz del proyecto.\n");
        exit(1);
    }
}

function backupFile(string $path, string $suffix): void
{
    $backup = $path . $suffix;

    if (!is_file($backup) && !copy($path, $backup)) {
        throw new RuntimeException("No se pudo crear respaldo: {$backup}");
    }
}

/**
 * @return array{0:int,1:int}
 */
function methodRange(string $content, string $signature): array
{
    $start = strpos($content, $signature);

    if ($start === false) {
        throw new RuntimeException("No se encontró el método: {$signature}");
    }

    $open = strpos($content, '{', $start);

    if ($open === false) {
        throw new RuntimeException("No se encontró la llave inicial de: {$signature}");
    }

    $depth = 0;
    $length = strlen($content);
    $quote = null;
    $escaped = false;

    for ($i = $open; $i < $length; $i++) {
        $char = $content[$i];

        if ($quote !== null) {
            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === $quote) {
                $quote = null;
            }

            continue;
        }

        if ($char === "'" || $char === '"') {
            $quote = $char;
            continue;
        }

        if ($char === '{') {
            $depth++;
        } elseif ($char === '}') {
            $depth--;

            if ($depth === 0) {
                return [$start, $i + 1];
            }
        }
    }

    throw new RuntimeException("No se encontró la llave final de: {$signature}");
}

function replaceMethod(string $content, string $signature, string $replacement): string
{
    [$start, $end] = methodRange($content, $signature);

    return substr($content, 0, $start)
        . rtrim($replacement)
        . substr($content, $end);
}

function insertBeforeSignature(
    string $content,
    string $signature,
    string $addition
): string {
    $position = strpos($content, $signature);

    if ($position === false) {
        throw new RuntimeException("No se encontró el punto de inserción: {$signature}");
    }

    return substr($content, 0, $position)
        . rtrim($addition)
        . "\n\n    "
        . substr($content, $position);
}

function replaceExactlyOnce(
    string $content,
    string $needle,
    string $replacement,
    string $label
): string {
    $count = substr_count($content, $needle);

    if ($count !== 1) {
        throw new RuntimeException(
            "No se pudo aplicar {$label}. Se esperaba 1 coincidencia y se encontraron {$count}."
        );
    }

    return str_replace($needle, $replacement, $content);
}

function runCommand(string $command, string $cwd): array
{
    $output = [];
    $code = 0;

    exec(
        'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1',
        $output,
        $code
    );

    return [$code, implode(PHP_EOL, $output)];
}

try {
    /*
    |--------------------------------------------------------------------------
    | 1. CierreGeneracionContinuidadService
    |--------------------------------------------------------------------------
    */
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $files['cierre']);
    $content = (string) file_get_contents($path);
    backupFile($path, $backupSuffix);

    if (!str_contains($content, 'public function sincronizarAnulacionesAdministrativas(')) {
        $method = <<<'PHP'
    /**
     * Corrige proyecciones que permanecieron como "confirmadas" después de que
     * el ciclo destino fue anulado expresamente por error administrativo.
     *
     * Esta sincronización NO altera el resultado académico del ciclo de origen
     * ni vuelve a anular el destino. Únicamente alinea la proyección con el
     * estado real del historial destino.
     */
    public function sincronizarAnulacionesAdministrativas(
        ?int $nivelOrigenId = null,
        ?int $usuarioId = null
    ): int {
        $ids = ProyeccionContinuidad::query()
            ->where('estado', 'confirmada')
            ->whereHas('inscripcionCicloDestino', function ($query): void {
                $query
                    ->where('estado', 'anulado')
                    ->where('resultado_final', 'anulacion_administrativa');
            })
            ->when(
                $nivelOrigenId,
                fn ($query) => $query->whereHas(
                    'inscripcionCicloOrigen',
                    fn ($origen) => $origen->where('nivel_id', $nivelOrigenId)
                )
            )
            ->orderBy('id')
            ->pluck('id');

        $sincronizadas = 0;

        foreach ($ids as $proyeccionId) {
            $actualizada = DB::transaction(function () use (
                $proyeccionId,
                $usuarioId
            ): bool {
                $proyeccion = ProyeccionContinuidad::query()
                    ->with([
                        'inscripcionCicloOrigen',
                        'inscripcionCicloDestino',
                        'detalleCierre',
                    ])
                    ->lockForUpdate()
                    ->find($proyeccionId);

                if (!$proyeccion || $proyeccion->estado !== 'confirmada') {
                    return false;
                }

                $destino = $proyeccion->inscripcionCicloDestino;

                if (
                    !$destino
                    || $destino->estado !== 'anulado'
                    || $destino->resultado_final !== 'anulacion_administrativa'
                ) {
                    return false;
                }

                $antes = $proyeccion->getAttributes();

                $fecha = $destino->fecha_salida
                    ? CarbonImmutable::parse($destino->fecha_salida)->toDateString()
                    : ($destino->cerrado_at
                        ? CarbonImmutable::parse($destino->cerrado_at)->toDateString()
                        : now()->toDateString());

                $actorId = $usuarioId
                    ?: $destino->cerrado_por
                    ?: $proyeccion->confirmada_por;

                $motivo = trim((string) $destino->motivo_cierre);

                if ($motivo === '') {
                    $motivo =
                        'Sincronización automática: el ciclo destino fue anulado por error administrativo.';
                }

                $proyeccion->forceFill([
                    'estado' => 'revertida',
                    'revertida_at' => $destino->cerrado_at ?: now(),
                    'revertida_por' => $actorId,
                    'fecha_reversion' => $fecha,
                    'tipo_reversion' => 'anulacion_administrativa',
                    'motivo_reversion' => $motivo,
                    'snapshot_reversion' => [
                        'tipo' => 'anulacion_administrativa',
                        'sincronizacion_automatica' => true,
                        'proyeccion_antes' => $antes,
                        'destino' => $this->snapshotHistorialParaFirma($destino),
                        'resultado' =>
                            'La proyección fue alineada con un ciclo destino previamente anulado por error administrativo. El ciclo de origen se conserva.',
                    ],
                ])->save();

                if ($proyeccion->detalleCierre) {
                    $proyeccion->detalleCierre->update([
                        'observacion' =>
                            'La continuidad confirmada quedó revertida porque el ciclo destino fue anulado administrativamente. '
                            . 'Se conserva íntegro el resultado académico del ciclo de origen.',
                        'estado_nuevo' => [
                            'proyeccion_estado' => 'revertida',
                            'tipo_reversion' => 'anulacion_administrativa',
                            'inscripcion_ciclo_destino_id' => $destino->id,
                            'destino_estado' => $destino->estado,
                            'destino_resultado_final' => $destino->resultado_final,
                        ],
                    ]);
                }

                if ($actorId) {
                    CambioAcademico::query()->create([
                        'inscripcion_id' => $proyeccion->inscripcion_id,
                        'inscripcion_ciclo_id' => $destino->id,
                        'generacion_id' =>
                            $proyeccion->inscripcionCicloOrigen?->generacion_id
                            ?: $proyeccion->generacion_destino_id,
                        'tipo' => 'sincronizacion_anulacion_administrativa',
                        'motivo' => $motivo,
                        'datos_anteriores' => [
                            'proyeccion' => $antes,
                        ],
                        'datos_nuevos' => [
                            'proyeccion' => $proyeccion->fresh()->getAttributes(),
                            'destino' => $this->snapshotHistorialParaFirma($destino),
                        ],
                        'realizado_por' => $actorId,
                        'realizado_at' => now(),
                    ]);
                }

                return true;
            });

            if ($actualizada) {
                $sincronizadas++;
            }
        }

        return $sincronizadas;
    }
PHP;

        $content = insertBeforeSignature(
            $content,
            'public function diagnosticoRetiroProyeccion(',
            $method
        );
    }

    if (!str_contains($content, 'Esta continuidad fue revertida por una anulación administrativa')) {
        $guardNeedle = <<<'PHP'
            if (! in_array($proyeccion->estado, ['cancelada', 'revertida'], true)) {
PHP;

        $guard = <<<'PHP'
            if (
                $proyeccion->estado === 'revertida'
                && $proyeccion->tipo_reversion === 'anulacion_administrativa'
            ) {
                throw ValidationException::withMessages([
                    'reactivacion_proyeccion' =>
                        'Esta continuidad fue revertida por una anulación administrativa. '
                        . 'No puede reactivarse desde Continuidad; primero debe revertirse la anulación administrativa mediante un proceso auditado específico.',
                ]);
            }

PHP;

        $content = replaceExactlyOnce(
            $content,
            $guardNeedle,
            $guard . $guardNeedle,
            'protección de reactivación administrativa'
        );
    }

    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("No se pudo guardar {$files['cierre']}");
    }

    /*
    |--------------------------------------------------------------------------
    | 2. AnulacionAdministrativaInscripcionService
    |--------------------------------------------------------------------------
    */
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $files['admin']);
    $content = (string) file_get_contents($path);
    backupFile($path, $backupSuffix);

    $lookupMethod = <<<'PHP'
    private function proyeccionConfirmadaDelDestino(
        InscripcionCiclo $historial,
    ): ?ProyeccionContinuidad {
        if (!Schema::hasTable('proyecciones_continuidad')) {
            return null;
        }

        /*
         * Primera opción: vínculo histórico exacto.
         */
        $exacta = ProyeccionContinuidad::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where('inscripcion_ciclo_destino_id', $historial->id)
            ->where('estado', 'confirmada')
            ->orderByDesc('id')
            ->first();

        if ($exacta) {
            return $exacta;
        }

        /*
         * Fallback para datos legacy o proyecciones donde el vínculo al
         * inscripcion_ciclo_destino_id quedó incompleto. Se limita al mismo
         * alumno, ciclo y ubicación proyectada para evitar asociar otra
         * continuidad.
         */
        return ProyeccionContinuidad::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where('estado', 'confirmada')
            ->where('ciclo_destino_id', $historial->ciclo_escolar_id)
            ->where('nivel_destino_id', $historial->nivel_id)
            ->when(
                $historial->grado_id,
                fn ($query) => $query->where('grado_destino_id', $historial->grado_id)
            )
            ->when(
                $historial->semestre_id,
                fn ($query) => $query->where('semestre_destino_id', $historial->semestre_id)
            )
            ->orderByDesc('id')
            ->first();
    }
PHP;

    $content = replaceMethod(
        $content,
        'private function proyeccionConfirmadaDelDestino(',
        $lookupMethod
    );

    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("No se pudo guardar {$files['admin']}");
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Livewire ProyeccionesContinuidad
    |--------------------------------------------------------------------------
    */
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $files['component']);
    $content = (string) file_get_contents($path);
    backupFile($path, $backupSuffix);

    if (!str_contains($content, 'sincronizarAnulacionesAdministrativas($this->nivel->id')) {
        $mountNeedle = <<<'PHP'
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->fecha_confirmacion = now()->toDateString();
PHP;

        $mountReplacement = <<<'PHP'
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();

        app(CierreGeneracionContinuidadService::class)
            ->sincronizarAnulacionesAdministrativas(
                $this->nivel->id,
                (int) auth()->id()
            );

        $this->fecha_confirmacion = now()->toDateString();
PHP;

        $content = replaceExactlyOnce(
            $content,
            $mountNeedle,
            $mountReplacement,
            'sincronización al abrir Continuidad'
        );
    }

    if (!str_contains($content, 'sincronizarAnulacionesAdministrativas(')
        || substr_count($content, 'sincronizarAnulacionesAdministrativas(') < 2) {
        $recargarNeedle = <<<'PHP'
    public function recargar(): void
    {
        $this->cargarCiclosDestino();
PHP;

        $recargarReplacement = <<<'PHP'
    public function recargar(): void
    {
        app(CierreGeneracionContinuidadService::class)
            ->sincronizarAnulacionesAdministrativas(
                $this->nivel->id,
                (int) auth()->id()
            );

        $this->cargarCiclosDestino();
PHP;

        $content = replaceExactlyOnce(
            $content,
            $recargarNeedle,
            $recargarReplacement,
            'sincronización al recargar Continuidad'
        );
    }

    if (!str_contains($content, 'No puede reactivarse desde este módulo porque la inscripción destino fue anulada administrativamente')) {
        [$start, $end] = methodRange(
            $content,
            'public function prepararReactivacion('
        );

        $methodContent = substr($content, $start, $end - $start);
        $needle = "            ->firstOrFail();\n";

        if (substr_count($methodContent, $needle) !== 1) {
            throw new RuntimeException(
                'No se encontró el punto de validación dentro de prepararReactivacion().'
            );
        }

        $guard = <<<'PHP'
            ->firstOrFail();

        if (
            $proyeccion->estado === 'revertida'
            && $proyeccion->tipo_reversion === 'anulacion_administrativa'
        ) {
            $this->addError(
                'reactivacion_proyeccion',
                'No puede reactivarse desde este módulo porque la inscripción destino fue anulada administrativamente.'
            );

            return;
        }
PHP;

        $methodContent = str_replace($needle, $guard . "\n", $methodContent);

        $content = substr($content, 0, $start)
            . $methodContent
            . substr($content, $end);
    }

    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("No se pudo guardar {$files['component']}");
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Modelo ProyeccionContinuidad
    |--------------------------------------------------------------------------
    */
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $files['model']);
    $content = (string) file_get_contents($path);
    backupFile($path, $backupSuffix);

    $labelMethod = <<<'PHP'
    public function getEtiquetaEstadoAttribute(): string
    {
        if (
            $this->estado === 'revertida'
            && $this->tipo_reversion === 'anulacion_administrativa'
        ) {
            return 'Anulada administrativamente';
        }

        return match ($this->estado) {
            'confirmada' => 'Continuará',
            'cancelada' => 'No continuará',
            'revertida' => 'No continuará (retirado)',
            default => 'Pendiente de confirmar',
        };
    }
PHP;

    $content = replaceMethod(
        $content,
        'public function getEtiquetaEstadoAttribute(): string',
        $labelMethod
    );

    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("No se pudo guardar {$files['model']}");
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Vista ProyeccionesContinuidad
    |--------------------------------------------------------------------------
    */
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $files['view']);
    $content = (string) file_get_contents($path);
    backupFile($path, $backupSuffix);

    if (!str_contains($content, 'Anulación administrativa aplicada')) {
        $startMarker = "@elseif (\$proyeccion->estado === 'revertida')";
        $endMarker = "@elseif (\$proyeccion->estado === 'cancelada')";

        $start = strpos($content, $startMarker);
        $end = strpos($content, $endMarker, $start === false ? 0 : $start);

        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException(
                'No se encontró el bloque de acciones para proyecciones revertidas.'
            );
        }

        $replacement = <<<'BLADE'
                                @elseif ($proyeccion->estado === 'revertida')
                                    @if ($proyeccion->tipo_reversion === 'anulacion_administrativa')
                                        <div class="flex min-w-[210px] flex-col items-end gap-2">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">
                                                <flux:icon.shield-exclamation class="h-4 w-4" />
                                                Anulación administrativa aplicada
                                            </span>

                                            <p class="max-w-xs text-right text-xs leading-5 text-slate-500 dark:text-slate-400">
                                                El ciclo destino ya fue anulado por error administrativo.
                                                La promoción o egreso del ciclo de origen se conserva.
                                            </p>

                                            <p class="text-[11px] font-semibold text-slate-400">
                                                Sin acciones de continuidad disponibles
                                            </p>
                                        </div>
                                    @else
                                        <div class="flex min-w-[175px] flex-col items-end gap-2">
                                            <p class="text-xs font-semibold text-violet-700 dark:text-violet-300">
                                                No continuará · retirado
                                                {{ $proyeccion->revertida_at?->format('d/m/Y H:i') }}
                                            </p>

                                            <p class="max-w-xs text-right text-xs text-slate-500">
                                                El destino quedó como no iniciado; el origen se conserva.
                                            </p>

                                            <button type="button"
                                                wire:key="reactivar-revertida-{{ $proyeccion->id }}"
                                                wire:click="prepararReactivacion({{ $proyeccion->id }})"
                                                wire:target="prepararReactivacion({{ $proyeccion->id }})"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60">
                                                <span wire:loading.remove
                                                    wire:target="prepararReactivacion({{ $proyeccion->id }})">
                                                    Cambiar a Continuará
                                                </span>

                                                <span wire:loading
                                                    wire:target="prepararReactivacion({{ $proyeccion->id }})"
                                                    class="inline-flex items-center gap-1.5">
                                                    <flux:icon name="loading" class="size-4" />
                                                    Revisando...
                                                </span>
                                            </button>
                                        </div>
                                    @endif
                                BLADE;

        $content = substr($content, 0, $start)
            . $replacement
            . "\n                                "
            . substr($content, $end);
    }

    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("No se pudo guardar {$files['view']}");
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Validaciones PHP / Blade
    |--------------------------------------------------------------------------
    */
    foreach ([
        $files['cierre'],
        $files['admin'],
        $files['component'],
        $files['model'],
    ] as $relative) {
        [$code, $output] = runCommand(
            'php -l ' . escapeshellarg($relative),
            $root
        );

        if ($code !== 0) {
            throw new RuntimeException(
                "Error de sintaxis en {$relative}:\n{$output}"
            );
        }
    }

    [$clearCode, $clearOutput] = runCommand('php artisan view:clear', $root);

    if ($clearCode !== 0) {
        throw new RuntimeException("No se pudieron limpiar las vistas:\n{$clearOutput}");
    }

    [$cacheCode, $cacheOutput] = runCommand('php artisan view:cache', $root);

    if ($cacheCode !== 0) {
        throw new RuntimeException("Blade no pudo compilar:\n{$cacheOutput}");
    }

    runCommand('php artisan view:clear', $root);

    /*
    |--------------------------------------------------------------------------
    | 7. Reparación inmediata de inconsistencias existentes
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | El script temporal se guarda en storage/app, por lo que __DIR__ apuntaría
    | a storage/app y NO a la raíz. Por eso usamos rutas absolutas del proyecto.
    |--------------------------------------------------------------------------
    */
    $autoloadPath = $root . DIRECTORY_SEPARATOR . 'vendor'
        . DIRECTORY_SEPARATOR . 'autoload.php';

    $bootstrapPath = $root . DIRECTORY_SEPARATOR . 'bootstrap'
        . DIRECTORY_SEPARATOR . 'app.php';

    $bootstrap = <<<'PHP'
require __AUTOLOAD__;
$app = require __BOOTSTRAP__;
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cantidad = app(App\Services\CierreGeneracionContinuidadService::class)
    ->sincronizarAnulacionesAdministrativas();

echo $cantidad;
PHP;

    $bootstrap = str_replace(
        ['__AUTOLOAD__', '__BOOTSTRAP__'],
        [var_export($autoloadPath, true), var_export($bootstrapPath, true)],
        $bootstrap
    );

    $temp = $root . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'app'
        . DIRECTORY_SEPARATOR . 'sync_anulaciones_continuidad.php';

    if (!is_dir(dirname($temp))) {
        mkdir(dirname($temp), 0775, true);
    }

    file_put_contents($temp, "<?php\n" . $bootstrap);

    [$syncCode, $syncOutput] = runCommand(
        'php ' . escapeshellarg($temp),
        $root
    );

    @unlink($temp);

    if ($syncCode !== 0) {
        throw new RuntimeException(
            "El código fue instalado, pero falló la sincronización de datos existentes:\n{$syncOutput}"
        );
    }

    runCommand('php artisan optimize:clear', $root);

    echo "\n============================================================\n";
    echo "SINCRONIZACIÓN COMPLETADA\n";
    echo "============================================================\n";
    echo "Proyecciones reparadas ahora: " . trim($syncOutput) . "\n";
    echo "PHP: OK\n";
    echo "Blade: OK\n";
    echo "Cachés: limpiadas\n";
    echo "No se requiere migración.\n\n";
    echo "Abre nuevamente Cierre y continuidad.\n";
    echo "Las anulaciones administrativas aparecerán como:\n";
    echo "  ANULADA ADMINISTRATIVAMENTE\n";
    echo "y ya no mostrarán Cambiar a No continuará / Cambiar a Continuará.\n";

    exit(0);

} catch (Throwable $e) {
    fwrite(STDERR, "\nERROR: " . $e->getMessage() . "\n");
    fwrite(
        STDERR,
        "Los archivos originales tienen respaldo con sufijo {$backupSuffix}.\n"
    );
    exit(1);
}
