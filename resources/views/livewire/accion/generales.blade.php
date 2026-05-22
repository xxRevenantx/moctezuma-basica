<div x-data="{
    colapsos: {
        estadistica: false,
        promocion: false,
        listas: false,
        promedios: false,
        credenciales: false,
    },

    init() {
        this.cargarEstadoCollapses();

        document.addEventListener('livewire:navigated', () => {
            this.cargarEstadoCollapses();
        });
    },

    llaveCollapses() {
        return 'generales_collapses_' + @js($slug_nivel);
    },

    cargarEstadoCollapses() {
        const raw = localStorage.getItem(this.llaveCollapses());

        if (!raw) {
            this.guardarEstadoCollapses();
            return;
        }

        try {
            const guardados = JSON.parse(raw);

            this.colapsos = {
                estadistica: guardados.estadistica === true,
                promocion: guardados.promocion === true,
                listas: guardados.listas === true,
                promedios: guardados.promedios === true,
                credenciales: guardados.credenciales === true,
            };
        } catch (error) {
            localStorage.removeItem(this.llaveCollapses());
            this.guardarEstadoCollapses();
        }
    },

    guardarEstadoCollapses() {
        localStorage.setItem(this.llaveCollapses(), JSON.stringify(this.colapsos));
    },

    alternarCollapse(seccion) {
        this.colapsos[seccion] = !this.colapsos[seccion];
        this.guardarEstadoCollapses();
    },
}" class="space-y-6">
    @once
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    @endonce

    {{-- ITERA NIVELES --}}
    <div class="overflow-hidden">
        <div class="-mx-1 overflow-x-auto pb-1">
            <div class="flex min-w-max items-center justify-center gap-2 px-1">
                @foreach ($niveles as $item)
                    @php
                        $activo = $slug_nivel === $item->slug;
                    @endphp

                    <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'generales']) }}"
                        wire:navigate aria-current="{{ $activo ? 'page' : 'false' }}"
                        class="group relative inline-flex items-center gap-2 whitespace-nowrap rounded-2xl border px-4 py-3 text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5
                            {{ $activo
                                ? 'border-sky-200 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20 dark:border-sky-700/50'
                                : 'border-slate-200 bg-white text-slate-700 shadow-sm hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:border-sky-800 dark:hover:bg-neutral-800 dark:hover:text-sky-300' }}">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-xl
                                {{ $activo
                                    ? 'bg-white/15 text-white'
                                    : 'bg-slate-100 text-slate-500 group-hover:bg-sky-100 group-hover:text-sky-700 dark:bg-neutral-700 dark:text-slate-300 dark:group-hover:bg-sky-950/40 dark:group-hover:text-sky-300' }}">
                            <flux:icon.rectangle-stack class="h-4 w-4" />
                        </span>

                        <span>{{ $item->nombre }}</span>

                        @if ($activo)
                            <span class="rounded-full bg-white/15 px-2 py-0.5 text-[11px] font-bold text-white">
                                Activo
                            </span>
                            <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-white/80"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- COLLAPSE: ESTADÍSTICA GENERAL --}}
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="alternarCollapse('estadistica')"
            class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                    <flux:icon.chart-bar-square class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-sky-600 dark:text-sky-300">
                        Estadística
                    </p>

                    <h2 class="truncate text-lg font-black text-slate-900 dark:text-white">
                        Estadística general de {{ $nivel->nombre }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Inicio de curso, medio curso y fin de curso. El estado abierto o cerrado se guarda en este
                        navegador.
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden rounded-full border px-3 py-1 text-xs font-black sm:inline-flex"
                    :class="colapsos.estadistica ?
                        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300' :
                        'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300'"
                    x-text="colapsos.estadistica ? 'Abierto' : 'Cerrado'">
                </span>

                <span
                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition duration-300 group-hover:border-sky-200 group-hover:text-sky-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                    :class="colapsos.estadistica ? 'rotate-180' : 'rotate-0'">
                    <flux:icon.chevron-down class="h-5 w-5" />
                </span>
            </div>
        </button>

        <div x-cloak x-show="colapsos.estadistica" x-transition.opacity.duration.200ms
            class="border-t border-slate-200 dark:border-neutral-800">
            <div class="space-y-6 p-5 sm:p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                            <flux:icon.chart-bar-square class="h-6 w-6" />
                        </div>

                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">
                                Concentrado estadístico de {{ $nivel->nombre }}
                            </h2>

                            <p class="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                                La información se calcula desde trayectorias académicas y se separa en tres cortes:
                                inicio, medio y fin de curso.
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                                    Nivel: {{ $nivel->nombre }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900/50">
                                    Generación:
                                    {{ $this->textoGeneracion($generacion_id ? (int) $generacion_id : null) }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-xs font-black text-violet-700 ring-1 ring-violet-100 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/50">
                                    Ciclo escolar:
                                    {{ $this->textoCicloEscolar($ciclo_escolar_id ? (int) $ciclo_escolar_id : null) }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50">
                                    Existencia fin: {{ $this->totalesFinCurso['existencia']['t'] ?? 0 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 xl:w-[620px]">
                        <flux:field>
                            <flux:label>Ciclo escolar</flux:label>

                            <flux:select wire:model.live="ciclo_escolar_id">
                                <flux:select.option value="">
                                    Selecciona un ciclo escolar
                                </flux:select.option>

                                @foreach ($cicloEscolares as $cicloEscolar)
                                    <flux:select.option value="{{ $cicloEscolar->id }}">
                                        {{ $cicloEscolar->inicio_anio }} - {{ $cicloEscolar->fin_anio }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <flux:field>
                            <flux:label>Generación</flux:label>

                            <flux:select wire:model.live="generacion_id">
                                <flux:select.option value="">
                                    Todas las generaciones
                                </flux:select.option>

                                @foreach ($generaciones as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">
                                        {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <div class="flex flex-col gap-3 sm:col-span-2 sm:flex-row sm:items-end">
                            <button type="button" wire:click="limpiarFiltroEstadistica"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                <flux:icon.arrow-path class="h-4 w-4" />
                                Limpiar filtros
                            </button>

                            <button type="button" wire:click="exportarEstadisticaExcel" wire:loading.attr="disabled"
                                wire:target="exportarEstadisticaExcel"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 via-green-600 to-lime-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-emerald-500/20 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60">
                                <span wire:loading.remove wire:target="exportarEstadisticaExcel"
                                    class="inline-flex items-center gap-2">
                                    <flux:icon.download class="h-4 w-4" />
                                    Exportar Excel
                                </span>

                                <span wire:loading wire:target="exportarEstadisticaExcel">
                                    Generando Excel...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Inicio
                            de curso</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                            {{ $this->totalesInicioCurso['existencia']['t'] ?? 0 }}</p>
                        <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">Existencia</p>
                    </div>

                    <div
                        class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                        <p class="text-xs font-black uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Medio
                            curso</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                            {{ $this->totalesMedioCurso['existencia']['t'] ?? 0 }}</p>
                        <p class="mt-1 text-xs font-bold text-indigo-700 dark:text-indigo-300">Existencia</p>
                    </div>

                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                        <p class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Fin
                            de curso</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                            {{ $this->totalesFinCurso['existencia']['t'] ?? 0 }}</p>
                        <p class="mt-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">Existencia</p>
                    </div>

                    <div
                        class="rounded-2xl border border-lime-200 bg-lime-50 p-4 dark:border-lime-900/50 dark:bg-lime-950/20">
                        <p class="text-xs font-black uppercase tracking-wide text-lime-700 dark:text-lime-300">
                            Promovidos</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                            {{ $this->totalesFinCurso['promovidos']['t'] ?? 0 }}</p>
                        <p class="mt-1 text-xs font-bold text-lime-700 dark:text-lime-300">Al cierre del curso</p>
                    </div>
                </div>

                @include('partials.tabla-estadistica', [
                    'titulo' => 'Inicio de curso',
                    'descripcion' =>
                        'Muestra la inscripción inicial, bajas y existencia del primer corte del ciclo escolar.',
                    'filas' => $this->estadisticaInicioCurso,
                    'totales' => $this->totalesInicioCurso,
                    'acento' => 'from-slate-600 via-slate-800 to-slate-950',
                    'bloques' => [
                        'inicial' => 'Inscripción inicial',
                        'altas' => 'Altas',
                        'inscripcion_total' => 'Inscripción total',
                        'bajas' => 'Bajas',
                        'existencia' => 'Existencia',
                    ],
                ])

                @include('partials.tabla-estadistica', [
                    'titulo' => 'Medio curso',
                    'descripcion' => 'Integra la inscripción inicial más las altas registradas como medio ciclo.',
                    'filas' => $this->estadisticaMedioCurso,
                    'totales' => $this->totalesMedioCurso,
                    'acento' => 'from-sky-500 via-blue-600 to-indigo-600',
                    'bloques' => [
                        'inicial' => 'Inscripción inicial',
                        'altas' => 'Altas',
                        'inscripcion_total' => 'Inscripción total',
                        'bajas' => 'Bajas',
                        'existencia' => 'Existencia',
                    ],
                ])

                @include('partials.tabla-estadistica', [
                    'titulo' => 'Fin de curso',
                    'descripcion' =>
                        'Cierra el ciclo con altas finales, inscripción total, bajas, existencia, promovidos y no promovidos.',
                    'filas' => $this->estadisticaFinCurso,
                    'totales' => $this->totalesFinCurso,
                    'acento' => 'from-emerald-500 via-teal-600 to-lime-600',
                    'bloques' => [
                        'altas' => 'Altas',
                        'inscripcion_total' => 'Inscripción total',
                        'bajas' => 'Bajas',
                        'existencia' => 'Existencia',
                        'promovidos' => 'Promovidos',
                        'no_promovidos' => 'No promovidos',
                    ],
                ])

                <div
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                    <p class="font-black">Nota de cálculo</p>

                    <p class="mt-1">
                        H = hombres, M = mujeres, T = total. La estadística se calcula desde
                        <b>trayectorias académicas</b> y se filtra por <b>ciclo escolar</b>. Inicio usa alumnos con
                        <b>Inicio de ciclo</b>. Medio suma alumnos de <b>Inicio de ciclo</b> más altas de
                        <b>Medio ciclo</b>. Fin suma inicio, medio y altas de <b>Fin de ciclo</b>. Promovidos se toma
                        del campo <b>promovido</b> de la trayectoria académica.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- COLLAPSE: PROMOCIÓN MASIVA DE ALUMNOS --}}
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="alternarCollapse('promocion')"
            class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-600 to-sky-600 text-white shadow-lg shadow-emerald-500/20">
                    <flux:icon.arrow-path-rounded-square class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-300">
                        Promoción</p>
                    <h2 class="truncate text-lg font-black text-slate-900 dark:text-white">Promoción masiva de alumnos
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Promueve alumnos a otro ciclo escolar
                        solo cuando abras esta sección.</p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden rounded-full border px-3 py-1 text-xs font-black sm:inline-flex"
                    :class="colapsos.promocion ?
                        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300' :
                        'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300'"
                    x-text="colapsos.promocion ? 'Abierto' : 'Cerrado'"></span>

                <span
                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition duration-300 group-hover:border-emerald-200 group-hover:text-emerald-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                    :class="colapsos.promocion ? 'rotate-180' : 'rotate-0'">
                    <flux:icon.chevron-down class="h-5 w-5" />
                </span>
            </div>
        </button>

        <div x-cloak x-show="colapsos.promocion" x-transition.opacity.duration.200ms
            class="border-t border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
            <livewire:accion.generales.promocion-alumnos :slug_nivel="$slug_nivel" :key="'promocion-alumnos-' . $slug_nivel" />
        </div>
    </section>

    {{-- COLLAPSE: LISTAS GENERALES --}}
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="alternarCollapse('listas')"
            class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-rose-600 text-white shadow-lg shadow-amber-500/20">
                    <flux:icon.clipboard-document-list class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600 dark:text-amber-300">Listas
                    </p>
                    <h2 class="truncate text-lg font-black text-slate-900 dark:text-white">Listas generales</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Consulta y genera listas del nivel
                        seleccionado.</p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden rounded-full border px-3 py-1 text-xs font-black sm:inline-flex"
                    :class="colapsos.listas ?
                        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300' :
                        'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300'"
                    x-text="colapsos.listas ? 'Abierto' : 'Cerrado'"></span>

                <span
                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition duration-300 group-hover:border-amber-200 group-hover:text-amber-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                    :class="colapsos.listas ? 'rotate-180' : 'rotate-0'">
                    <flux:icon.chevron-down class="h-5 w-5" />
                </span>
            </div>
        </button>

        <div x-cloak x-show="colapsos.listas" x-transition.opacity.duration.200ms
            class="border-t border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
            <livewire:accion.generales.listas :slug_nivel="$slug_nivel" :key="'listas-generales-' . $slug_nivel" />
        </div>
    </section>
    {{-- COLLAPSE: PROMEDIOS GENERALES --}}
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="alternarCollapse('promedios')"
            class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 via-sky-600 to-blue-700 text-white shadow-lg shadow-cyan-500/20">
                    <flux:icon.chart-bar-square class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-600 dark:text-cyan-300">
                        Promedios
                    </p>
                    <h2 class="truncate text-lg font-black text-slate-900 dark:text-white">
                        Promedios generales
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Consulta el concentrado de promedios por periodos en básica y parciales por semestre en
                        bachillerato.
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden rounded-full border px-3 py-1 text-xs font-black sm:inline-flex"
                    :class="colapsos.promedios ?
                        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300' :
                        'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300'"
                    x-text="colapsos.promedios ? 'Abierto' : 'Cerrado'"></span>

                <span
                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition duration-300 group-hover:border-cyan-200 group-hover:text-cyan-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                    :class="colapsos.promedios ? 'rotate-180' : 'rotate-0'">
                    <flux:icon.chevron-down class="h-5 w-5" />
                </span>
            </div>
        </button>

        <div x-cloak x-show="colapsos.promedios" x-transition.opacity.duration.200ms
            class="border-t border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
            <livewire:accion.generales.promedios-generales :slug_nivel="$slug_nivel" :key="'promedios-generales-' . $slug_nivel" />
        </div>
    </section>


    {{-- COLLAPSE: CREDENCIALES --}}
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="alternarCollapse('credenciales')"
            class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-600 text-white shadow-lg shadow-violet-500/20">
                    <flux:icon.identification class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600 dark:text-violet-300">
                        Credenciales</p>
                    <h2 class="truncate text-lg font-black text-slate-900 dark:text-white">Credenciales</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Genera credenciales del nivel
                        seleccionado.</p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden rounded-full border px-3 py-1 text-xs font-black sm:inline-flex"
                    :class="colapsos.credenciales ?
                        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300' :
                        'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300'"
                    x-text="colapsos.credenciales ? 'Abierto' : 'Cerrado'"></span>

                <span
                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition duration-300 group-hover:border-violet-200 group-hover:text-violet-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                    :class="colapsos.credenciales ? 'rotate-180' : 'rotate-0'">
                    <flux:icon.chevron-down class="h-5 w-5" />
                </span>
            </div>
        </button>

        <div x-cloak x-show="colapsos.credenciales" x-transition.opacity.duration.200ms
            class="border-t border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
            <livewire:generales.credenciales :slug_nivel="$slug_nivel" :key="'credenciales-generales-' . $slug_nivel" />
        </div>
    </section>
</div>
