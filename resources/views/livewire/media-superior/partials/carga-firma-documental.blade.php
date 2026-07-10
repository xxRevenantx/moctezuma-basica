@php
    $tiposArchivo = [
        'firma' => [
            'titulo' => 'Firma manuscrita',
            'descripcion' => 'Imagen recortada de la firma. Se recomienda PNG transparente.',
            'modelo' => 'firmaUploads.' . $rol,
            'upload' => $this->firmaUploads[$rol] ?? null,
            'path' => $firmantes[$rol]['firma_path'] ?? null,
            'eliminado' => (bool) ($this->eliminarFirmas[$rol] ?? false),
            'aspecto' => 'firma',
        ],
        'sello' => [
            'titulo' => 'Sello oficial',
            'descripcion' => 'Sello separado de la firma para controlar su tamaño y posición.',
            'modelo' => 'selloUploads.' . $rol,
            'upload' => $this->selloUploads[$rol] ?? null,
            'path' => $firmantes[$rol]['sello_path'] ?? null,
            'eliminado' => (bool) ($this->eliminarSellos[$rol] ?? false),
            'aspecto' => 'sello',
        ],
    ];
    $registroId = $firmantes[$rol]['registro_id'] ?? null;
    $version = $firmantes[$rol]['archivos_version'] ?? now()->timestamp;
    $firmanteSeleccionado = filled($firmantes[$rol]['id'] ?? null);
@endphp

<div class="border-t border-slate-200 bg-slate-50/60 p-5 dark:border-slate-800 dark:bg-slate-900/30">
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="font-black text-slate-950 dark:text-white">Firma digital para Historial académico</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">
                Los archivos se guardan de forma privada y se asocian a esta persona y vigencia. PDF, impresión y Word pueden incluirlos; Excel no.
            </p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
            <flux:icon.lock-closed class="h-3.5 w-3.5" /> Privado
        </span>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($tiposArchivo as $tipoArchivo => $archivo)
            @php
                $tieneTemporal = $archivo['upload'] && method_exists($archivo['upload'], 'temporaryUrl');
                $tieneGuardado = filled($archivo['path']) && $registroId && ! $archivo['eliminado'];
                $preview = $tieneTemporal
                    ? $archivo['upload']->temporaryUrl()
                    : ($tieneGuardado
                        ? route('media-superior.documentos.firmante-archivo', [
                            'firmante' => $registroId,
                            'tipo' => $tipoArchivo,
                            'v' => $version,
                        ])
                        : null);
            @endphp

            <div
                wire:key="archivo-{{ $rol }}-{{ $tipoArchivo }}"
                x-data="firmaDocumentalEditor('{{ $archivo['modelo'] }}', '{{ $archivo['aspecto'] }}')"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-950"
            >
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                    <div>
                        <p class="text-sm font-black text-slate-950 dark:text-white">{{ $archivo['titulo'] }}</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $archivo['descripcion'] }}</p>
                    </div>
                    @if ($tieneTemporal)
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">Sin guardar</span>
                    @elseif ($tieneGuardado)
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">Configurado</span>
                    @elseif ($archivo['eliminado'])
                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">Se eliminará</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-500 dark:bg-slate-800">Pendiente</span>
                    @endif
                </div>

                <div class="p-4">
                    <div
                        @class([
                            'flex items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-100/70 dark:border-slate-700 dark:bg-slate-900',
                            'h-32' => $tipoArchivo === 'firma',
                            'h-44' => $tipoArchivo === 'sello',
                        ])
                        style="background-image: linear-gradient(45deg, rgba(148,163,184,.10) 25%, transparent 25%), linear-gradient(-45deg, rgba(148,163,184,.10) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, rgba(148,163,184,.10) 75%), linear-gradient(-45deg, transparent 75%, rgba(148,163,184,.10) 75%); background-size: 18px 18px; background-position: 0 0, 0 9px, 9px -9px, -9px 0;"
                    >
                        @if ($preview)
                            <img src="{{ $preview }}" alt="{{ $archivo['titulo'] }}" class="max-h-full max-w-full object-contain p-3">
                        @else
                            <div class="px-4 text-center text-slate-400">
                                <flux:icon.photo class="mx-auto h-8 w-8" />
                                <p class="mt-2 text-xs font-bold">Sin archivo cargado</p>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($firmanteSeleccionado)
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[#006492] px-3.5 py-2.5 text-xs font-black text-white transition hover:bg-[#005474]">
                                <flux:icon.pencil-square class="h-4 w-4" />
                                {{ $preview ? 'Cambiar y editar' : 'Subir y editar' }}
                                <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" x-on:change="seleccionar($event)">
                            </label>
                        @else
                            <button type="button" disabled class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-slate-200 px-3.5 py-2.5 text-xs font-black text-slate-500 dark:bg-slate-800">
                                <flux:icon.user-plus class="h-4 w-4" /> Selecciona firmante
                            </button>
                        @endif

                        @if ($preview)
                            <button type="button" wire:click="quitarArchivo('{{ $rol }}', '{{ $tipoArchivo }}')"
                                class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs font-black text-rose-700 hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300">
                                <flux:icon.trash class="h-4 w-4" /> Eliminar
                            </button>
                        @elseif ($archivo['eliminado'] && filled($archivo['path']))
                            <button type="button" wire:click="restaurarArchivo('{{ $rol }}', '{{ $tipoArchivo }}')"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200">
                                <flux:icon.arrow-path class="h-4 w-4" /> Restaurar
                            </button>
                        @endif
                    </div>

                    <p class="mt-3 text-[11px] leading-5 text-slate-500">PNG, JPG, JPEG o WebP · máximo 2 MB. El editor exporta una copia PNG sin modificar el original.</p>
                    <flux:error name="{{ $archivo['modelo'] }}" />
                </div>

                <template x-teleport="body">
                    <div x-show="abierto" x-cloak x-on:keydown.escape.window="cerrar()" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                        <div x-show="abierto" x-transition.opacity class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" x-on:click="cerrar()"></div>
                        <div x-show="abierto" x-transition class="relative z-10 w-full max-w-3xl overflow-hidden rounded-[1.75rem] bg-white shadow-2xl dark:bg-slate-950">
                            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                                <div>
                                    <p class="font-black text-slate-950 dark:text-white">Recortar y girar {{ mb_strtolower($archivo['titulo']) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Arrastra la imagen, ajusta el zoom y aplica la orientación correcta.</p>
                                </div>
                                <button type="button" x-on:click="cerrar()" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-900"><flux:icon.x-mark class="h-5 w-5" /></button>
                            </div>

                            <div class="grid gap-5 p-5 lg:grid-cols-[1fr_15rem]">
                                <div>
                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-900"
                                        style="background-image: linear-gradient(45deg, rgba(148,163,184,.16) 25%, transparent 25%), linear-gradient(-45deg, rgba(148,163,184,.16) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, rgba(148,163,184,.16) 75%), linear-gradient(-45deg, transparent 75%, rgba(148,163,184,.16) 75%); background-size: 20px 20px; background-position: 0 0, 0 10px, 10px -10px, -10px 0;">
                                        <canvas x-ref="canvas" class="block h-auto w-full cursor-move touch-none" x-on:pointerdown="iniciarArrastre($event)" x-on:pointermove="arrastrar($event)" x-on:pointerup="terminarArrastre($event)" x-on:pointercancel="terminarArrastre($event)"></canvas>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <div class="flex items-center justify-between text-xs font-black text-slate-700 dark:text-slate-200"><span>Zoom</span><span x-text="zoom.toFixed(2) + '×'"></span></div>
                                        <input type="range" min="0.7" max="3" step="0.05" x-model.number="zoom" x-on:input="dibujar()" class="mt-2 w-full">
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between text-xs font-black text-slate-700 dark:text-slate-200"><span>Horizontal</span><span x-text="Math.round(offsetX)"></span></div>
                                        <input type="range" min="-250" max="250" step="1" x-model.number="offsetX" x-on:input="dibujar()" class="mt-2 w-full">
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between text-xs font-black text-slate-700 dark:text-slate-200"><span>Vertical</span><span x-text="Math.round(offsetY)"></span></div>
                                        <input type="range" min="-250" max="250" step="1" x-model.number="offsetY" x-on:input="dibujar()" class="mt-2 w-full">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button" x-on:click="girar(-90)" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-black dark:border-slate-700"><flux:icon.arrow-uturn-left class="h-4 w-4" /> Izquierda</button>
                                        <button type="button" x-on:click="girar(90)" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-black dark:border-slate-700"><flux:icon.arrow-uturn-right class="h-4 w-4" /> Derecha</button>
                                    </div>
                                    <button type="button" x-on:click="reiniciar()" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-black text-slate-600 dark:border-slate-700 dark:text-slate-300">Restablecer encuadre</button>
                                    <div x-show="subiendo" class="rounded-xl bg-blue-50 p-3 text-xs font-bold text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                                        Procesando y cargando… <span x-text="progreso + '%'" class="float-right"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:flex-row sm:justify-end">
                                <button type="button" x-on:click="cerrar()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancelar</button>
                                <button type="button" x-on:click="aplicar()" x-bind:disabled="subiendo" class="rounded-xl bg-[#006492] px-5 py-2.5 text-sm font-black text-white disabled:opacity-60">Aplicar imagen</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        @endforeach
    </div>
</div>
