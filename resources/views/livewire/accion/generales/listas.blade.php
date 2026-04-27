<div x-data="{
    abierto: 'listas',

    cambiar(seccion) {
        this.abierto = this.abierto === seccion ? null : seccion
    }
}" class="space-y-6">

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
                            Descargar listas y formatos en PDF
                        </span>

                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">
                            Filtra por generación, grado, {{ $this->esBachillerato() ? 'semestre,' : '' }} grupo y tipo
                            de documento.
                        </span>
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        class="hidden rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-100 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/60 sm:inline-flex">
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

                    <div wire:loading.flex
                        wire:target="generacion_id,grado_id,semestre_id,grupo_id,tipo_descarga,opcion_descarga,limpiarFiltros"
                        class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                </path>
                            </svg>
                            Actualizando filtros...
                        </div>
                    </div>

                    <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                    Filtros de descarga
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Selecciona la información escolar y el documento que deseas generar.
                                </p>
                            </div>

                            <button type="button" wire:click="limpiarFiltros"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                <flux:icon.arrow-path class="h-4 w-4" />
                                Limpiar
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

                            <flux:field>
                                <flux:label>Nivel</flux:label>
                                <flux:input readonly variant="filled" value="{{ $nivel?->nombre ?? '—' }}" disabled />
                            </flux:field>

                            <flux:field>
                                <flux:label>Generación</flux:label>

                                <flux:select id="generacion_id" wire:model.live="generacion_id">
                                    <flux:select.option value="">
                                        Selecciona una generación
                                    </flux:select.option>

                                    @foreach ($generaciones as $generacion)
                                        <flux:select.option value="{{ $generacion->id }}">
                                            {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="generacion_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Grado</flux:label>

                                <flux:select id="grado_id" wire:model.live="grado_id" :disabled="!$generacion_id">
                                    <flux:select.option value="">
                                        Selecciona un grado
                                    </flux:select.option>

                                    @foreach ($grados as $grado)
                                        <flux:select.option value="{{ $grado->id }}">
                                            {{ $grado->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="grado_id" />
                            </flux:field>

                            @if ($this->esBachillerato())
                                <flux:field>
                                    <flux:label>Semestre</flux:label>

                                    <flux:select id="semestre_id" wire:model.live="semestre_id"
                                        :disabled="!$generacion_id || !$grado_id || $semestres->isEmpty()">
                                        <flux:select.option value="">
                                            Selecciona un semestre
                                        </flux:select.option>

                                        @foreach ($semestres as $semestre)
                                            <flux:select.option value="{{ $semestre->id }}">
                                                {{ $this->textoSemestre($semestre) }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="semestre_id" />
                                </flux:field>
                            @endif

                            <flux:field>
                                <flux:label>Grupo</flux:label>

                                <flux:select id="grupo_id" wire:model.live="grupo_id"
                                    wire:key="lista-grupo-select-{{ $slug_nivel }}-{{ $generacion_id ?? 'null' }}-{{ $grado_id ?? 'null' }}-{{ $semestre_id ?? 'null' }}-{{ $grupos->count() }}"
                                    :disabled="$this->esBachillerato()
                                                                            ? (!$generacion_id || !$grado_id || !$semestre_id || $grupos->isEmpty())
                                                                            : (!$generacion_id || !$grado_id || $grupos->isEmpty())">

                                    <flux:select.option value="">
                                        Selecciona un grupo
                                    </flux:select.option>

                                    @foreach ($grupos as $grupo)
                                        <flux:select.option value="{{ $grupo->id }}">
                                            {{ $grupo->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="grupo_id" />

                                @if (!$this->esBachillerato() && $generacion_id && $grado_id && $grupos->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        No hay grupos registrados para la generación y grado seleccionados.
                                    </p>
                                @endif

                                @if ($this->esBachillerato() && $generacion_id && $grado_id && $semestre_id && $grupos->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        No hay grupos registrados para la generación, grado y semestre seleccionados.
                                    </p>
                                @endif
                            </flux:field>

                            <flux:field>
                                <flux:label>Tipo de documento</flux:label>

                                <flux:select id="tipo_descarga" wire:model.live="tipo_descarga">
                                    @foreach ($this->tiposDescarga() as $valor => $texto)
                                        <flux:select.option value="{{ $valor }}">
                                            {{ $texto }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="tipo_descarga" />
                            </flux:field>

                            <flux:field>
                                <flux:label>
                                    {{ $tipo_descarga === 'formatos' ? 'Formato' : 'Periodo' }}
                                </flux:label>

                                <flux:select id="opcion_descarga" wire:model.live="opcion_descarga"
                                    wire:key="opcion-descarga-{{ $tipo_descarga }}">
                                    @foreach ($this->opcionesDescarga() as $valor => $texto)
                                        <flux:select.option value="{{ $valor }}">
                                            {{ $texto }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="opcion_descarga" />
                            </flux:field>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            @if ($this->generacionSeleccionada)
                                <span
                                    class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300">
                                    Generación:
                                    {{ $this->generacionSeleccionada->anio_ingreso }} -
                                    {{ $this->generacionSeleccionada->anio_egreso }}
                                </span>
                            @endif

                            @if ($this->gradoSeleccionado)
                                <span
                                    class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                    Grado: {{ $this->gradoSeleccionado->nombre }}
                                </span>
                            @endif

                            @if ($this->esBachillerato() && $this->semestreSeleccionado)
                                <span
                                    class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                    {{ $this->textoSemestre($this->semestreSeleccionado) }}
                                </span>
                            @endif

                            @if ($this->grupoSeleccionado)
                                <span
                                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    Grupo: {{ $this->grupoSeleccionado->nombre }}
                                </span>
                            @endif

                            <span
                                class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                {{ $this->textoTipoDescarga }}: {{ $this->textoOpcionDescarga }}
                            </span>

                            @if ($this->puedeDescargar)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    <flux:icon.check-circle class="h-4 w-4" />
                                    Listo para descargar
                                </span>
                            @endif
                        </div>

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
                                                Ya puedes descargar:
                                                <span class="font-bold">
                                                    {{ $this->textoTipoDescarga }} - {{ $this->textoOpcionDescarga }}
                                                </span>
                                            </p>
                                        @else
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                @if ($this->esBachillerato())
                                                    Selecciona generación, grado, semestre, grupo y documento.
                                                @else
                                                    Selecciona generación, grado, grupo y documento.
                                                @endif
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
