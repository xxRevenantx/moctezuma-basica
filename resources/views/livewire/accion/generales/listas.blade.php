<div x-data="{
    abierto: 'listas',

    cambiar(seccion) {
        this.abierto = this.abierto === seccion ? null : seccion
    }
}" class="space-y-6">


    {{-- COLLAPSE DE DESCARGA --}}
    <section class="space-y-4">
        <article
            class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm transition-all duration-300 dark:border-neutral-800 dark:bg-neutral-900">
            <button type="button" x-on:click="cambiar('listas')"
                class="group flex w-full items-center justify-between gap-4 px-5 py-5 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/60 sm:px-6">
                <div class="flex items-center gap-4">
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white shadow-lg shadow-sky-500/20 transition group-hover:scale-105">
                        <flux:icon.document-arrow-down class="h-5 w-5" />
                    </span>

                    <span>
                        <span class="block text-base font-black text-slate-900 dark:text-white">
                            Descargar listas en PDF
                        </span>

                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">
                            Filtra por generación, grado, grupo y descarga la lista del nivel seleccionado.
                        </span>
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        class="hidden rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/60 sm:inline-flex">
                        PDF
                    </span>

                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                        x-bind:class="abierto === 'listas'
                            ?
                            'rotate-180 border-sky-200 text-sky-600 dark:border-sky-900 dark:text-sky-300' :
                            ''">
                        <flux:icon.chevron-down class="h-5 w-5" />
                    </span>
                </div>
            </button>

            <div x-cloak x-show="abierto === 'listas'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                class="border-t border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/30 sm:p-6">
                <div
                    class="relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    {{-- LOADER --}}
                    <div wire:loading.flex
                        class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                </path>
                            </svg>
                            Cargando filtros...
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                    Filtros de la lista
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Completa los campos para habilitar la descarga del PDF.
                                </p>
                            </div>

                            <button type="button" wire:click="limpiarFiltros"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                <flux:icon.arrow-path class="h-4 w-4" />
                                Limpiar
                            </button>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            {{-- GENERACIÓN --}}
                            <flux:field>
                                <flux:label>Generación</flux:label>

                                <flux:select wire:model.live="generacion_id" placeholder="Selecciona generación">
                                    @foreach ($generaciones as $generacion)
                                        <flux:select.option value="{{ $generacion->id }}">
                                            {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="generacion_id" />
                            </flux:field>

                            {{-- GRADO --}}
                            @if (!$this->esBachillerato())
                                <flux:field>
                                    <flux:label>Grado</flux:label>

                                    <flux:select wire:model.live="grado_id" placeholder="Selecciona grado">
                                        @foreach ($grados as $grado)
                                            <flux:select.option value="{{ $grado->id }}">
                                                {{ $grado->nombre }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="grado_id" />
                                </flux:field>
                            @endif

                            {{-- SEMESTRE PARA BACHILLERATO --}}
                            @if ($this->esBachillerato())
                                <flux:field>
                                    <flux:label>Semestre</flux:label>

                                    <flux:select wire:model.live="semestre_id" placeholder="Selecciona semestre">
                                        @foreach ($semestres as $semestre)
                                            <flux:select.option value="{{ $semestre->id }}">
                                                {{ $semestre->semestre }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="semestre_id" />
                                </flux:field>
                            @endif

                            {{-- GRUPO --}}
                            <flux:field>
                                <flux:label>Grupo</flux:label>

                                <flux:select wire:model.live="grupo_id" placeholder="Selecciona grupo"
                                    :disabled="$grupos->isEmpty()">
                                    @foreach ($grupos as $grupo)
                                        <flux:select.option value="{{ $grupo->id }}">
                                            {{ $grupo->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="grupo_id" />
                            </flux:field>
                        </div>

                        {{-- RESUMEN --}}
                        <div
                            class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">
                                            Estado de la descarga
                                        </p>

                                        @if ($this->puedeDescargar)
                                            <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">
                                                Los filtros están completos. Ya puedes descargar la lista en PDF.
                                            </p>
                                        @else
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                Selecciona los filtros requeridos para habilitar el botón de descarga.
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if ($this->puedeDescargar)
                                    <a href="{{ $this->urlPdf }}" target="_blank"
                                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-500 via-rose-600 to-pink-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-rose-500/20 transition hover:-translate-y-0.5 hover:shadow-xl">
                                        <flux:icon.document-arrow-down class="h-5 w-5" />
                                        Descargar PDF
                                    </a>
                                @else
                                    <button type="button" :disabled="true"
                                        class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-200 px-5 py-3 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-neutral-500">
                                        <flux:icon.lock-closed class="h-5 w-5" />
                                        Descargar PDF
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </section>
</div>
