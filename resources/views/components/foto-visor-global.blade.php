{{-- Modal único de fotografías de alumnos/personal para las pantallas autenticadas.
     Usar data-foto-visor en una imagen para habilitarlo, sin montar más modales por fila. --}}
<div x-data="fotoVisorGlobal" @abrir-foto-global.window="abrir($event.detail)" @keydown.escape.window="if (abierto) cerrar()">
    <template x-teleport="body">
        <div x-cloak x-show="abierto" x-trap.noscroll="abierto"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto bg-slate-950/85 p-3 backdrop-blur-md sm:p-6"
            role="dialog" aria-modal="true" aria-labelledby="foto-visor-global-titulo"
            aria-describedby="foto-visor-global-descripcion" @click.self="cerrar()">

            <section x-show="abierto" @click.stop
                x-transition:enter="transition duration-200 ease-out"
                x-transition:enter-start="translate-y-3 scale-95 opacity-0"
                x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                x-transition:leave="transition duration-150 ease-in"
                x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                x-transition:leave-end="translate-y-2 scale-95 opacity-0"
                class="relative flex max-h-[95vh] w-full max-w-4xl flex-col overflow-hidden rounded-[28px] border border-white/20 bg-white shadow-2xl shadow-black/50 dark:bg-neutral-900 sm:rounded-[32px]">

                <div class="relative shrink-0 overflow-hidden bg-gradient-to-r from-[#006492] via-[#06729d] to-[#00456c] px-5 py-4 text-white sm:px-7 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-20 h-56 w-56 rounded-full bg-[#88AC2E]/25 blur-3xl"></div>
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-[#88AC2E] via-lime-300 to-transparent"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3 sm:gap-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white/15 ring-1 ring-white/20">
                                <flux:icon.photo class="h-6 w-6" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[.2em] text-sky-100">Centro Universitario Moctezuma · Fotografía</p>
                                <h2 id="foto-visor-global-titulo" class="mt-0.5 truncate text-base font-black sm:text-xl" x-text="nombre"></h2>
                                <p id="foto-visor-global-descripcion" class="mt-1 truncate text-xs font-medium text-sky-100" x-text="contexto"></p>
                            </div>
                        </div>
                        <button x-ref="botonCerrar" type="button" @click="cerrar()" aria-label="Cerrar fotografía ampliada"
                            class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-white/15 text-white ring-1 ring-white/20 transition hover:bg-white/25 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            <flux:icon.x-mark class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto bg-[radial-gradient(ellipse_at_top,_#e0f2fe,_#f1f5f9_58%,_#e2e8f0)] p-4 dark:bg-[radial-gradient(ellipse_at_top,_#082f49,_#0a0a0a_60%,_#171717)] sm:p-7">
                    <div class="mx-auto flex w-full flex-col items-center justify-center gap-4">
                        <div class="relative flex min-h-[240px] w-full items-center justify-center overflow-hidden rounded-[22px] border border-slate-200/70 bg-white/70 p-4 shadow-inner dark:border-neutral-700 dark:bg-neutral-950/70 sm:min-h-[340px] sm:p-8">
                            <div x-show="!cargada && !error" class="flex flex-col items-center gap-3 text-slate-500 dark:text-slate-400" aria-live="polite">
                                <span class="size-8 animate-spin rounded-full border-4 border-slate-200 border-t-[#006492]"></span>
                                <span class="text-xs font-semibold">Cargando fotografía…</span>
                            </div>
                            <div x-show="error" x-cloak class="flex flex-col items-center gap-3 text-center text-rose-700 dark:text-rose-300" role="alert">
                                <flux:icon.exclamation-triangle class="h-9 w-9" />
                                <span class="text-sm font-semibold">No fue posible mostrar la fotografía.</span>
                                <span class="text-xs">Revisa que el archivo original todavía exista.</span>
                            </div>
                            <img x-show="!error" x-cloak :src="url" :alt="'Fotografía ampliada de ' + nombre"
                                x-on:load="cargada = true" x-on:error="error = true" draggable="false"
                                :style="`transform: scale(${zoom}) rotate(${rotacion}deg)`"
                                class="max-h-[48vh] max-w-full rounded-xl object-contain shadow-2xl shadow-slate-950/20 transition-transform duration-200 ease-out sm:max-h-[56vh]"
                                :class="{ 'hidden': !cargada }">
                        </div>
                        <div class="flex flex-wrap items-center justify-center gap-2 text-xs font-bold">
                            <span x-show="matricula" x-cloak class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200 dark:bg-neutral-800 dark:text-neutral-200 dark:ring-neutral-700">
                                Matrícula: <span x-text="matricula"></span>
                            </span>
                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/60">Imagen original · sin modificaciones</span>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white px-4 py-3 dark:border-neutral-800 dark:bg-neutral-900 sm:px-7 sm:py-4">
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="alejar()" :disabled="zoom <= 1 || error" aria-label="Alejar fotografía"
                            class="grid size-10 place-items-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">
                            <flux:icon.minus class="size-4" />
                        </button>
                        <span class="w-14 text-center text-xs font-black tabular-nums text-slate-700 dark:text-slate-200" x-text="Math.round(zoom * 100) + '%'"></span>
                        <button type="button" @click="acercar()" :disabled="zoom >= 3 || error" aria-label="Acercar fotografía"
                            class="grid size-10 place-items-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">
                            <flux:icon.plus class="size-4" />
                        </button>
                        <span class="mx-1 h-6 w-px bg-slate-200 dark:bg-neutral-700"></span>
                        <button type="button" @click="girar(-90)" :disabled="error" aria-label="Girar fotografía a la izquierda"
                            class="grid size-10 place-items-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-100 disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">
                            <flux:icon.arrow-uturn-left class="size-4" />
                        </button>
                        <button type="button" @click="girar(90)" :disabled="error" aria-label="Girar fotografía a la derecha"
                            class="grid size-10 place-items-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-100 disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">
                            <flux:icon.arrow-uturn-right class="size-4" />
                        </button>
                        <button type="button" @click="restablecer()" :disabled="zoom === 1 && rotacion === 0" title="Restablecer vista" aria-label="Restablecer vista"
                            class="grid size-10 place-items-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-100 disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">
                            <flux:icon.arrow-path class="size-4" />
                        </button>
                    </div>
                    <button type="button" @click="cerrar()"
                        class="rounded-xl bg-[#006492] px-5 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-[#004e76] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#88AC2E]">
                        Cerrar
                    </button>
                </div>
            </section>
        </div>
    </template>
</div>
