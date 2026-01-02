<div class="space-y-6">
    <!-- Header simple -->
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-black text-neutral-900 dark:text-white">Selecciona un grado</h1>
        <p class="text-sm text-neutral-600 dark:text-neutral-300">
            Elige un grado del nivel seleccionado.
        </p>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-md">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path d="M21 21l-4.3-4.3"></path>
                    <circle cx="11" cy="11" r="7"></circle>
                </svg>
            </span>

            <input type="text" wire:model.live.debounce.250ms="search" placeholder="Buscar grado…"
                class="w-full rounded-2xl border border-neutral-200 bg-white pl-11 pr-4 py-2.5 text-sm text-neutral-900 shadow-sm outline-none
                       focus:ring-2 focus:ring-sky-500/40 focus:border-sky-400
                       dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-sky-400/25 dark:focus:border-sky-500" />
        </div>

        <div class="flex items-center gap-2">
            @if ($this->nivel)
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-3 py-1 text-xs font-semibold text-neutral-800 shadow-sm
                             dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-100">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    {{ $this->nivel->nombre }}
                </span>
            @endif
        </div>
    </div>

    @if (!$this->nivel)
        <div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 dark:border-rose-900/40 dark:bg-rose-950/25">
            <h3 class="text-base font-extrabold text-rose-800 dark:text-rose-200">Nivel no encontrado</h3>
            <p class="mt-1 text-sm text-rose-700/90 dark:text-rose-200/80">
                Revisa el slug enviado en la ruta.
            </p>
        </div>
    @else
        @php($grados = $this->grados)

        @if ($grados->isEmpty())
            <div
                class="rounded-3xl border border-dashed border-neutral-300 bg-white p-10 text-center dark:border-neutral-800 dark:bg-neutral-900">
                <h3 class="text-lg font-black text-neutral-900 dark:text-white">Sin grados</h3>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                    No hay grados disponibles
                    @if ($search)
                        con el filtro “{{ $search }}”
                    @endif.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4 xl:grid-cols-4">
                @foreach ($grados as $g)
                    <div
                        class="group relative overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-sm
                               dark:border-neutral-800 dark:bg-neutral-900">

                        {{-- ✅ Loader overlay SOLO para el card presionado --}}
                        <div wire:loading.flex wire:target="seleccionar({{ $g->id }})"
                            class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-sm
                                   dark:bg-neutral-900/70">
                            <div
                                class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow ring-1 ring-black/5
                                       dark:bg-neutral-950 dark:ring-white/10">
                                <svg class="h-5 w-5 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span
                                    class="text-sm font-semibold text-neutral-700 dark:text-neutral-200">Abriendo…</span>
                            </div>
                        </div>

                        <!-- Banner superior -->
                        <div class="relative h-20 bg-gradient-to-r from-slate-700 via-slate-600 to-slate-700">
                            <div class="absolute -left-10 -top-8 h-28 w-28 rotate-45 bg-sky-500/90"></div>
                            <div class="absolute left-10 -top-10 h-28 w-28 rotate-45 bg-cyan-400/60"></div>
                            <div class="absolute left-24 -top-10 h-28 w-28 rotate-45 bg-emerald-400/50"></div>
                            <div class="absolute right-6 bottom-3 h-1.5 w-16 rounded-full bg-sky-200/70"></div>
                        </div>

                        <!-- “Logo” circular -->
                        <div class="relative -mt-10 flex justify-center">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow ring-1 ring-black/5
                                       dark:bg-neutral-950 dark:ring-white/10">
                                <span class="text-xl">📚</span>
                            </div>
                        </div>

                        <div class="px-6 pb-6 pt-3">
                            <h3 class="text-center text-lg font-black tracking-wide text-neutral-900 dark:text-white">
                                {{ mb_strtoupper($g->nombre) }}° GRADO
                            </h3>

                            <!-- Badges -->
                            <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                                <span
                                    class="inline-flex items-center gap-2 rounded-lg bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800
                                           dark:bg-amber-500/15 dark:text-amber-200">
                                    Hombres
                                    <span
                                        class="rounded-md bg-white/60 px-2 py-0.5 text-[11px] font-black dark:bg-white/10">
                                        {{ $g->hombres }}
                                    </span>
                                </span>

                                <span
                                    class="inline-flex items-center gap-2 rounded-lg bg-neutral-100 px-3 py-1 text-xs font-semibold text-neutral-800
                                           dark:bg-white/10 dark:text-neutral-100">
                                    Total
                                    <span
                                        class="rounded-md bg-white/70 px-2 py-0.5 text-[11px] font-black dark:bg-white/10">
                                        {{ $g->total }}
                                    </span>
                                </span>

                                <span
                                    class="inline-flex items-center gap-2 rounded-lg bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-800
                                           dark:bg-violet-500/15 dark:text-violet-200">
                                    Mujeres
                                    <span
                                        class="rounded-md bg-white/60 px-2 py-0.5 text-[11px] font-black dark:bg-white/10">
                                        {{ $g->mujeres }}
                                    </span>
                                </span>
                            </div>

                            <!-- Barras -->
                            <div class="mt-5 space-y-3">
                                <div
                                    class="flex items-center justify-between text-xs font-semibold text-neutral-600 dark:text-neutral-300">
                                    <span>Hombres</span>
                                    <span>{{ $g->pct_h }}%</span>
                                </div>
                                <div class="h-2.5 w-full rounded-full bg-neutral-200 dark:bg-neutral-800">
                                    <div class="h-2.5 rounded-full bg-sky-600" style="width: {{ $g->pct_h }}%">
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-between text-xs font-semibold text-neutral-600 dark:text-neutral-300">
                                    <span>Mujeres</span>
                                    <span>{{ $g->pct_m }}%</span>
                                </div>
                                <div class="h-2.5 w-full rounded-full bg-neutral-200 dark:bg-neutral-800">
                                    <div class="h-2.5 rounded-full bg-violet-600" style="width: {{ $g->pct_m }}%">
                                    </div>
                                </div>
                            </div>

                            <!-- Botón -->
                            <button type="button" wire:click="seleccionar({{ $g->id }})"
                                wire:loading.attr="disabled" wire:target="seleccionar({{ $g->id }})"
                                class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl
                                       bg-gradient-to-r from-sky-600 to-indigo-600 px-4 py-3
                                       text-sm font-black text-white shadow hover:brightness-110
                                       focus:outline-none focus:ring-2 focus:ring-sky-500/30
                                       disabled:cursor-not-allowed disabled:opacity-70">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-95" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 6h16"></path>
                                    <path d="M4 12h16"></path>
                                    <path d="M4 18h16"></path>
                                </svg>
                                GRADO: {{ mb_strtoupper($g->nombre) }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="pt-2 text-center text-xs text-neutral-500 dark:text-neutral-400">
                Consejo: después puedes filtrar por grupo dentro del grado si lo necesitas.
            </p>
        @endif
    @endif
</div>
