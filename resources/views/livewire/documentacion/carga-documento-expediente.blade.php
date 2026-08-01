<div
    x-data="{
        progreso: 0,
        subiendo: false,
        confirmarRetiroNoAplica: false
    }"
    x-on:livewire-upload-start="subiendo = true; progreso = 0"
    x-on:livewire-upload-progress="progreso = Number($event.detail.progress || 0)"
    x-on:livewire-upload-finish="subiendo = false; progreso = 100"
    x-on:livewire-upload-error="subiendo = false; progreso = 0"
    class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
>
    <div class="h-1 bg-gradient-to-r from-[#006492] to-[#88AC2E]"></div>

    <div class="p-5">
        <div class="flex items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#006492]/10 text-[#006492] dark:bg-[#006492]/20 dark:text-sky-300">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5V5.625A3.375 3.375 0 0011.25 2.25h-4.5A2.25 2.25 0 004.5 4.5v15a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25v-5.25z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.25 2.25V6a2.25 2.25 0 002.25 2.25H19.5" />
                    </svg>
                </span>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="font-semibold uppercase tracking-wide text-slate-900 dark:text-white">{{ $etiqueta }}</h4>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $obligatorio ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-neutral-300' }}">
                            {{ $obligatorio ? 'Obligatorio' : 'Opcional' }}
                        </span>
                        @if ($organizacionPendiente)
                            <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                Organización pendiente
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-neutral-400">
                        PDF, JPG, JPEG, PNG o WEBP · máximo {{ $maxMb }} MB. Puede contener una o varias páginas, incluso documentos combinados.
                    </p>
                </div>
            </div>

            @if ($noAplica)
                <span class="shrink-0 rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                    No aplica
                </span>
            @elseif ($inconsistente)
                <span class="shrink-0 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                    Archivo faltante
                </span>
            @elseif ($guardado)
                <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                    Entregado
                </span>
            @else
                <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                    Pendiente
                </span>
            @endif
        </div>

        @if ($guardado)
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/70 p-3 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                <p class="truncate text-sm font-medium text-emerald-900 dark:text-emerald-100" title="{{ $nombreArchivo }}">
                    {{ $nombreArchivo }}
                </p>
                <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                    {{ $tamanoArchivo }}
                    @if ($paginasDocumento > 0)
                        · {{ $paginasDocumento }} página(s)
                    @endif
                    · {{ ucfirst($estadoDocumento) }}
                </p>
            </div>
        @elseif ($inconsistente)
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-200">
                La base de datos conserva el registro, pero el archivo físico ya no está disponible. Sube una nueva copia para reparar el expediente.
            </div>
        @elseif ($noAplica)
            <div class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-3 text-sm text-violet-800 dark:border-violet-900/60 dark:bg-violet-950/20 dark:text-violet-200">
                <p class="font-semibold">Este documento fue marcado como no aplicable.</p>
                @if ($motivoNoAplica !== '')
                    <p class="mt-1 text-xs">Motivo: {{ $motivoNoAplica }}</p>
                @endif
            </div>
        @endif

        @if ($organizacionPendiente)
            <div class="mt-3 rounded-xl border border-sky-200 bg-sky-50 p-3 text-xs text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/20 dark:text-sky-200">
                Hay páginas guardadas como borrador. El documento actual seguirá vigente hasta confirmar la organización.
            </div>
        @endif

        <div class="mt-4 flex flex-wrap gap-2">
            @if ($guardado && $archivoGuardadoUrl)
                <a
                    href="{{ $archivoGuardadoUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Ver documento
                </a>

                <a
                    href="{{ $archivoDescargaUrl }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 10.5L12 15m0 0l4.5-4.5M12 15V3" />
                    </svg>
                    Descargar
                </a>
            @endif

            @if ($tieneFuentes && ! $soloLectura)
                <button
                    type="button"
                    wire:click="abrirOrganizador"
                    class="inline-flex items-center gap-2 rounded-xl border border-[#006492]/30 bg-[#006492]/5 px-3 py-2 text-sm font-semibold text-[#006492] transition hover:bg-[#006492]/10 dark:border-sky-800 dark:text-sky-300"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 6.75h15M4.5 12h15m-15 5.25h15" />
                    </svg>
                    Organizar páginas
                </button>
            @endif

            @if (! $soloLectura)
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[#006492] px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-[#00547b]">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16.5V3.75m0 0L7.5 8.25M12 3.75l4.5 4.5M3.75 15v3.75A2.25 2.25 0 006 21h12a2.25 2.25 0 002.25-2.25V15" />
                    </svg>
                    {{ $guardado ? 'Agregar o reemplazar' : 'Subir archivo' }}
                    <input
                        wire:model="archivo"
                        type="file"
                        accept="application/pdf,image/jpeg,image/png,image/webp,.pdf,.jpg,.jpeg,.png,.webp"
                        class="sr-only"
                    >
                </label>

                @if ($noAplica)
                    <button
                        type="button"
                        wire:click="quitarNoAplica"
                        wire:confirm="¿Retirar la marca No aplica? El documento volverá a mostrarse como pendiente."
                        class="inline-flex items-center gap-2 rounded-xl border border-violet-200 px-3 py-2 text-sm font-medium text-violet-700 transition hover:bg-violet-50 dark:border-violet-900/60 dark:text-violet-300 dark:hover:bg-violet-950/20"
                    >
                        Quitar No aplica
                    </button>
                @elseif (! $guardado)
                    <button
                        type="button"
                        wire:click="solicitarNoAplica"
                        class="inline-flex items-center gap-2 rounded-xl border border-violet-200 px-3 py-2 text-sm font-medium text-violet-700 transition hover:bg-violet-50 dark:border-violet-900/60 dark:text-violet-300 dark:hover:bg-violet-950/20"
                    >
                        No aplica
                    </button>
                @endif
            @endif
        </div>

        <div x-show="subiendo" x-cloak class="mt-4">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-neutral-400">
                <span>Subiendo y validando…</span>
                <span x-text="progreso + '%'">0%</span>
            </div>
            <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-neutral-700">
                <div class="h-full rounded-full bg-[#006492] transition-all" :style="`width:${progreso}%`"></div>
            </div>
        </div>

        @if ($requiereConfirmacion)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">¿Cómo deseas integrar el nuevo archivo?</p>
                <p class="mt-1 text-xs text-amber-800 dark:text-amber-200">
                    El archivo contiene {{ $archivoPaginas }} página(s). Puedes reemplazar las páginas actuales de {{ $etiqueta }} o agregarlas al final. Los archivos anteriores se conservan.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="guardarArchivo('reemplazar')"
                        wire:loading.attr="disabled"
                        wire:target="guardarArchivo"
                        class="rounded-xl bg-amber-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-60"
                    >
                        Reemplazar documento
                    </button>
                    <button
                        type="button"
                        wire:click="guardarArchivo('agregar')"
                        wire:loading.attr="disabled"
                        wire:target="guardarArchivo"
                        class="rounded-xl bg-[#006492] px-3.5 py-2 text-sm font-semibold text-white hover:bg-[#00547b] disabled:opacity-60"
                    >
                        Agregar páginas
                    </button>
                    <button
                        type="button"
                        wire:click="cancelarReemplazo"
                        class="rounded-xl border border-amber-300 px-3.5 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:text-amber-200 dark:hover:bg-amber-950/40"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        @endif

        @if ($pdfRequiereDecision)
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/60 dark:bg-rose-950/20">
                <p class="text-sm font-semibold text-rose-900 dark:text-rose-100">El PDF puede conservarse, pero no organizarse.</p>
                <p class="mt-1 text-xs text-rose-800 dark:text-rose-200">{{ $pdfDiagnostico }}</p>
                @if ($pdfPuedeGuardarseOriginal)
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            type="button"
                            wire:click="guardarOriginalSinOrganizar"
                            wire:loading.attr="disabled"
                            wire:target="guardarOriginalSinOrganizar"
                            class="rounded-xl bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60"
                        >
                            Guardar original sin organizar
                        </button>
                        <button
                            type="button"
                            wire:click="cancelarReemplazo"
                            class="rounded-xl border border-rose-300 px-3.5 py-2 text-sm font-medium text-rose-800 hover:bg-rose-100 dark:border-rose-800 dark:text-rose-200 dark:hover:bg-rose-950/40"
                        >
                            Elegir otro archivo
                        </button>
                    </div>
                @endif
            </div>
        @endif

        @error('archivo')
            <p class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">{{ $message }}</p>
        @enderror

        @if ($mensaje !== '')
            <p class="mt-3 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">{{ $mensaje }}</p>
        @endif

        @if ($historial !== [])
            <details class="mt-4 rounded-xl border border-slate-200 dark:border-neutral-700">
                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-slate-700 dark:text-neutral-200">
                    Historial de versiones ({{ count($historial) }})
                </summary>
                <div class="space-y-2 border-t border-slate-200 p-3 dark:border-neutral-700">
                    @foreach ($historial as $version)
                        <div class="flex flex-col gap-2 rounded-lg bg-slate-50 p-3 text-xs dark:bg-neutral-800/60 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-800 dark:text-neutral-100">
                                    Versión {{ $version['version'] }} · {{ ucfirst($version['estado']) }}
                                    @if ($version['actual'])
                                        · Actual
                                    @endif
                                </p>
                                <p class="mt-0.5 text-slate-500 dark:text-neutral-400">
                                    {{ $version['fecha'] }} · {{ $version['tamano'] }}
                                    @if ($version['paginas'] > 0)
                                        · {{ $version['paginas'] }} pág.
                                    @endif
                                </p>
                            </div>
                            @if ($version['url'])
                                <a href="{{ $version['url'] }}" target="_blank" rel="noopener" class="shrink-0 font-semibold text-[#006492] hover:underline dark:text-sky-300">
                                    Consultar
                                </a>
                            @else
                                <span class="shrink-0 font-medium text-rose-600 dark:text-rose-300">Archivo no disponible</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </div>

    <div
        wire:loading.flex
        wire:target="archivo,guardarArchivo,guardarOriginalSinOrganizar,abrirOrganizador,quitarNoAplica"
        class="absolute inset-0 z-20 items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-neutral-900/80"
    >
        <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-lg ring-1 ring-slate-200 dark:bg-neutral-900 dark:text-neutral-200 dark:ring-neutral-700">
            <svg class="size-5 animate-spin text-[#006492]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            Procesando documento…
        </div>
    </div>
</div>
