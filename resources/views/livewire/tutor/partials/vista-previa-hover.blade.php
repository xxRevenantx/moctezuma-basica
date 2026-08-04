@php
    $url = (string) ($url ?? '');
    $downloadUrl = (string) ($downloadUrl ?? $url);
    $nombre = (string) ($nombre ?? 'Documento');
    $mime = strtolower((string) ($mime ?? 'application/pdf'));
    $tamano = (string) ($tamano ?? '');
    $paginas = max((int) ($paginas ?? 0), 0);
    $estado = (string) ($estado ?? 'Disponible');
    $label = (string) ($label ?? 'Ver');
    $buttonClass = (string) ($buttonClass ?? 'inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-sky-500/15 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800');
    $mostrarIcono = (bool) ($mostrarIcono ?? true);
    $esImagen = str_starts_with($mime, 'image/');
    $esPdf = in_array($mime, ['application/pdf', 'application/x-pdf'], true)
        || str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?: ''), '.pdf');
@endphp

<div
    x-data="{
        abierto: false,
        cargando: true,
        temporizadorAbrir: null,
        temporizadorCerrar: null,
        x: 16,
        y: 16,
        ancho: 420,
        alto: 520,
        permiteHover() {
            return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        },
        programarApertura(retraso = 350) {
            if (!this.permiteHover()) return;
            clearTimeout(this.temporizadorCerrar);
            clearTimeout(this.temporizadorAbrir);
            this.temporizadorAbrir = setTimeout(() => this.abrirVista(), retraso);
        },
        abrirVista() {
            if (!this.permiteHover()) return;
            this.cargando = true;
            this.abierto = true;
            this.$nextTick(() => {
                this.reposicionar();
                requestAnimationFrame(() => this.reposicionar());
            });
        },
        programarCierre() {
            clearTimeout(this.temporizadorAbrir);
            clearTimeout(this.temporizadorCerrar);
            this.temporizadorCerrar = setTimeout(() => {
                this.abierto = false;
                this.cargando = true;
            }, 150);
        },
        cancelarCierre() {
            clearTimeout(this.temporizadorCerrar);
        },
        reposicionar() {
            const disparador = this.$refs.disparador;
            if (!disparador) return;

            const margen = 12;
            const separacion = 12;
            const rect = disparador.getBoundingClientRect();
            this.ancho = Math.min(420, Math.max(300, window.innerWidth - (margen * 2)));
            this.alto = Math.min(520, Math.max(360, window.innerHeight - (margen * 2)));

            let izquierda = rect.right + separacion;
            if (izquierda + this.ancho > window.innerWidth - margen) {
                izquierda = rect.left - this.ancho - separacion;
            }
            if (izquierda < margen) {
                izquierda = Math.max(margen, (window.innerWidth - this.ancho) / 2);
            }

            let arriba = rect.top - 28;
            if (arriba + this.alto > window.innerHeight - margen) {
                arriba = window.innerHeight - this.alto - margen;
            }
            if (arriba < margen) arriba = margen;

            this.x = Math.round(izquierda);
            this.y = Math.round(arriba);
        },
    }"
    x-on:keydown.escape.window="abierto = false"
    x-on:resize.window="if (abierto) reposicionar()"
    x-on:scroll.window.throttle="if (abierto) reposicionar()"
    class="relative inline-flex"
>
    <a
        x-ref="disparador"
        href="{{ $url }}"
        target="_blank"
        rel="noopener"
        class="{{ $buttonClass }}"
        x-on:mouseenter="programarApertura()"
        x-on:mouseleave="programarCierre()"
        x-on:focus="programarApertura(250)"
        x-on:blur="programarCierre()"
        aria-label="Abrir {{ $nombre }}"
    >
        @if ($mostrarIcono)
            <flux:icon name="eye" class="size-4" />
        @endif
        {{ $label }}
    </a>

    <template x-teleport="body">
        <div
            x-cloak
            x-show="abierto"
            x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.975]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.985]"
            :style="`position:fixed;left:${x}px;top:${y}px;width:${ancho}px;height:${alto}px;z-index:99999;`"
            class="flex overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-950/10 dark:border-neutral-700 dark:bg-neutral-950"
            x-on:mouseenter="cancelarCierre()"
            x-on:mouseleave="programarCierre()"
        >
            <div class="flex min-w-0 flex-1 flex-col">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 bg-gradient-to-r from-sky-50 to-lime-50 px-4 py-3 dark:border-neutral-800 dark:from-sky-950/40 dark:to-lime-950/20">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-black text-slate-900 dark:text-white" title="{{ $nombre }}">{{ $nombre }}</p>
                        <div class="mt-1 flex flex-wrap gap-x-2 gap-y-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                            @if ($tamano !== '')
                                <span>{{ $tamano }}</span>
                            @endif
                            @if ($paginas > 0)
                                <span>{{ $paginas }} página(s)</span>
                            @endif
                            @if ($estado !== '')
                                <span class="capitalize">{{ $estado }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-white/80 px-2 py-1 text-[9px] font-black uppercase tracking-wide text-sky-700 ring-1 ring-sky-200 dark:bg-neutral-900 dark:text-sky-300 dark:ring-sky-900">
                        Vista rápida
                    </span>
                </div>

                <div class="relative min-h-0 flex-1 overflow-hidden bg-slate-100 dark:bg-neutral-900">
                    <div x-show="cargando" class="absolute inset-0 z-10 flex items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-neutral-950/80">
                        <div class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-600 shadow ring-1 ring-slate-200 dark:bg-neutral-900 dark:text-slate-300 dark:ring-neutral-700">
                            <svg class="size-4 animate-spin text-[#006492]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                            </svg>
                            Cargando vista previa…
                        </div>
                    </div>

                    <template x-if="abierto">
                        <div class="h-full w-full p-3">
                            @if ($esImagen)
                                <img
                                    src="{{ $url }}"
                                    alt="Vista previa de {{ $nombre }}"
                                    class="h-full w-full rounded-xl bg-white object-contain shadow-sm dark:bg-neutral-950"
                                    x-on:load="cargando = false"
                                    x-on:error="cargando = false"
                                >
                            @elseif ($esPdf)
                                <iframe
                                    src="{{ $url }}#page=1&toolbar=0&navpanes=0&scrollbar=0&view=FitH"
                                    title="Primera página de {{ $nombre }}"
                                    class="pointer-events-none h-full w-full rounded-xl bg-white shadow-sm"
                                    loading="lazy"
                                    x-on:load="cargando = false"
                                ></iframe>
                            @else
                                <div x-init="cargando = false" class="flex h-full items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center dark:border-neutral-700 dark:bg-neutral-950">
                                    <div>
                                        <flux:icon name="document" class="mx-auto size-10 text-slate-400" />
                                        <p class="mt-3 text-sm font-black text-slate-800 dark:text-white">Vista previa no disponible</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Abre el archivo para consultarlo con la aplicación compatible.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-white px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950">
                    <a href="{{ $url }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#006492] px-3 py-2 text-xs font-black text-white transition hover:bg-[#00547b]">
                        <flux:icon name="arrow-top-right-on-square" class="size-4" /> Abrir
                    </a>
                    @if ($downloadUrl !== '')
                        <a href="{{ $downloadUrl }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">
                            <flux:icon name="arrow-down-tray" class="size-4" /> Descargar
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
