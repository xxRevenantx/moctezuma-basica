<div x-data="{
    colapsos: {
        estadistica: false,
        promocion: false,
        cierre_nivel: false,
        cierre_ciclo: false,
        listas: false,
        generaciones_historicas: false,
        horarios: false,
        promedios: false,
        lugares_preescolar: false,
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
                cierre_nivel: guardados.cierre_nivel === true,
                cierre_ciclo: guardados.cierre_ciclo === true,
                listas: guardados.listas === true,
                generaciones_historicas: guardados.generaciones_historicas === true,
                horarios: guardados.horarios === true,
                promedios: guardados.promedios === true,
                credenciales: guardados.credenciales === true,
                lugares_preescolar: guardados.lugares_preescolar === true,
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

    @php
        $esPreescolar = $nivel?->slug === 'preescolar';
    @endphp

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


    {{-- DISTRIBUCIÓN E HISTORIAL ESCOLAR: consulta exclusiva de administración --}}
    @if (auth()->user()?->is_admin)
        <livewire:accion.generales.distribucion-historial
            :slug_nivel="$slug_nivel"
            :key="'distribucion-historial-' . $slug_nivel"
        />
    @endif


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
                        Padrón actual agrupado por generación, grado, semestre y grupo.
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
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">
                            Concentrado por generación
                        </h3>
                        <p class="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                            La información se obtiene directamente de las inscripciones. Las generaciones inactivas
                            siguen disponibles para consulta histórica.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="min-w-[250px]">
                            <flux:select wire:model.live="generacion_id" label="Generación">
                                <flux:select.option value="">Todas las generaciones</flux:select.option>
                                @foreach ($generaciones as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">
                                        {{ $generacion->etiqueta }}{{ $generacion->status ? '' : ' · inactiva' }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <flux:button type="button" wire:click="limpiarFiltroEstadistica" variant="ghost"
                            icon="arrow-path">
                            Limpiar
                        </flux:button>

                        <flux:button type="button" wire:click="exportarEstadisticaExcel" variant="primary"
                            icon="arrow-down-tray" spinner="exportarEstadisticaExcel">
                            Exportar Excel
                        </flux:button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                    @foreach ([
                        'total' => ['Total único', 'text-slate-900 dark:text-white'],
                        'activos' => ['Activos', 'text-emerald-700 dark:text-emerald-300'],
                        'bajas' => ['Bajas', 'text-rose-700 dark:text-rose-300'],
                        'trasladados' => ['Trasladados', 'text-amber-700 dark:text-amber-300'],
                        'egresados' => ['Egresados', 'text-sky-700 dark:text-sky-300'],
                        'suspendidos' => ['Suspendidos', 'text-orange-700 dark:text-orange-300'],
                        'inactivos' => ['Inactivos', 'text-slate-600 dark:text-slate-300'],
                        'reingresos' => ['Reingresos', 'text-violet-700 dark:text-violet-300'],
                        'hombres' => ['Hombres', 'text-blue-700 dark:text-blue-300'],
                        'mujeres' => ['Mujeres', 'text-pink-700 dark:text-pink-300'],
                    ] as $clave => [$etiqueta, $color])
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                {{ $etiqueta }}
                            </p>
                            <p class="mt-2 text-3xl font-black {{ $color }}">
                                {{ $this->resumen[$clave] ?? 0 }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-neutral-800">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/40">
                        <h3 class="font-black text-slate-900 dark:text-white">Distribución actual</h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Conteos por ubicación académica dentro de la generación seleccionada.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-900 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">Grado</th>
                                    @if ($slug_nivel === 'bachillerato')
                                        <th class="px-4 py-3 text-center">Semestre</th>
                                    @endif
                                    <th class="px-4 py-3 text-center">Grupo</th>
                                    <th class="px-4 py-3 text-center">H</th>
                                    <th class="px-4 py-3 text-center">M</th>
                                    <th class="px-4 py-3 text-center">Total</th>
                                    <th class="px-4 py-3 text-center">Activos</th>
                                    <th class="px-4 py-3 text-center">Bajas</th>
                                    <th class="px-4 py-3 text-center">Egresados</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-neutral-800">
                                @forelse ($this->distribucionEscolar as $fila)
                                    <tr class="bg-white dark:bg-neutral-900">
                                        <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">
                                            {{ $fila['grado'] }}
                                        </td>
                                        @if ($slug_nivel === 'bachillerato')
                                            <td class="px-4 py-3 text-center">{{ $fila['semestre'] ?? '—' }}</td>
                                        @endif
                                        <td class="px-4 py-3 text-center">{{ $fila['grupo'] }}</td>
                                        <td class="px-4 py-3 text-center">{{ $fila['hombres'] }}</td>
                                        <td class="px-4 py-3 text-center">{{ $fila['mujeres'] }}</td>
                                        <td class="px-4 py-3 text-center font-black">{{ $fila['total'] }}</td>
                                        <td class="px-4 py-3 text-center">{{ $fila['activos'] }}</td>
                                        <td class="px-4 py-3 text-center">{{ $fila['bajas'] }}</td>
                                        <td class="px-4 py-3 text-center">{{ $fila['egresados'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $slug_nivel === 'bachillerato' ? 9 : 8 }}"
                                            class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                            No hay alumnos para la generación seleccionada.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-200">
                    <p class="font-black">Fuente de información</p>
                    <p class="mt-1">
                        Esta sección ya no usa trayectorias académicas. El nivel, generación, grado, semestre, grupo y
                        estatus se consultan directamente desde la inscripción de cada alumno.
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


    {{-- PANEL DE CIERRE DE CICLO ESCOLAR --}}
    <section class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="alternarCollapse('cierre_ciclo')" class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70 sm:px-6">
            <div class="flex min-w-0 items-center gap-4"><div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] text-white shadow-lg"><flux:icon.check-badge class="h-6 w-6" /></div><div><p class="text-xs font-black uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">Control escolar</p><h2 class="text-lg font-black text-slate-900 dark:text-white">Panel de cierre de ciclo escolar</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Diagnóstico, selección de egresados, cierre de generación y cierre del ciclo en un asistente seguro.</p></div></div>
            <span class="flex h-10 w-10 items-center justify-center rounded-2xl border dark:border-neutral-700" :class="colapsos.cierre_ciclo ? 'rotate-180' : 'rotate-0'"><flux:icon.chevron-down class="h-5 w-5" /></span>
        </button>
        <div x-cloak x-show="colapsos.cierre_ciclo" x-transition.opacity.duration.200ms class="border-t border-slate-200 p-5 dark:border-neutral-800 sm:p-6"><livewire:accion.generales.panel-cierre-ciclo :slug_nivel="$slug_nivel" :key="'panel-cierre-ciclo-' . $slug_nivel" /></div>
    </section>

    {{-- COLLAPSE: CIERRE DE NIVEL Y CONTINUIDAD --}}
    <section class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="alternarCollapse('cierre_nivel')"
            class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 via-sky-600 to-emerald-600 text-white shadow-lg shadow-violet-500/20">
                    <flux:icon.academic-cap class="h-6 w-6" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600 dark:text-violet-300">Fin de etapa</p>
                    <h2 class="truncate text-lg font-black text-slate-900 dark:text-white">Cierre de nivel y continuidad</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Egreso, continuidad interna, traslado, baja y repetición sin eliminar el historial.</p>
                </div>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition duration-300 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                :class="colapsos.cierre_nivel ? 'rotate-180' : 'rotate-0'">
                <flux:icon.chevron-down class="h-5 w-5" />
            </span>
        </button>
        <div x-cloak x-show="colapsos.cierre_nivel" x-transition.opacity.duration.200ms class="border-t border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
            <livewire:accion.generales.cierre-nivel-continuidad :slug_nivel="$slug_nivel" :key="'cierre-nivel-' . $slug_nivel" />
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

    {{-- COLLAPSE: LISTAS HISTÓRICAS DE GENERACIONES --}}
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="alternarCollapse('generaciones_historicas')"
            class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] via-cyan-600 to-[#88AC2E] text-white shadow-lg shadow-sky-600/20">
                    <flux:icon.archive-box class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-[#006492] dark:text-sky-300">
                        Archivo histórico
                    </p>
                    <h2 class="truncate text-lg font-black text-slate-900 dark:text-white">
                        Listas de generaciones egresadas
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Descarga padrones por generación en PDF o Word, con selección múltiple, estatus, grupos y alumnos archivados.
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden rounded-full border px-3 py-1 text-xs font-black sm:inline-flex"
                    :class="colapsos.generaciones_historicas ?
                        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300' :
                        'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300'"
                    x-text="colapsos.generaciones_historicas ? 'Abierto' : 'Cerrado'"></span>

                <span
                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition duration-300 group-hover:border-sky-200 group-hover:text-[#006492] dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                    :class="colapsos.generaciones_historicas ? 'rotate-180' : 'rotate-0'">
                    <flux:icon.chevron-down class="h-5 w-5" />
                </span>
            </div>
        </button>

        <div x-cloak x-show="colapsos.generaciones_historicas" x-transition.opacity.duration.200ms
            class="border-t border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
            <livewire:accion.generales.listas-generaciones-historicas
                :slug_nivel="$slug_nivel"
                :key="'listas-generaciones-historicas-' . $slug_nivel"
            />
        </div>
    </section>

    {{-- COLLAPSE: HORARIOS GENERALES --}}
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="alternarCollapse('horarios')"
            class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-600 to-violet-600 text-white shadow-lg shadow-blue-500/20">
                    <flux:icon.calendar-days class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">
                        Horarios
                    </p>
                    <h2 class="truncate text-lg font-black text-slate-900 dark:text-white">
                        Horarios generales
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Consulta y descarga un horario concentrado con todos los grados y grupos del nivel seleccionado.
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden rounded-full border px-3 py-1 text-xs font-black sm:inline-flex"
                    :class="colapsos.horarios ?
                        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300' :
                        'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300'"
                    x-text="colapsos.horarios ? 'Abierto' : 'Cerrado'"></span>

                <span
                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition duration-300 group-hover:border-blue-200 group-hover:text-blue-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                    :class="colapsos.horarios ? 'rotate-180' : 'rotate-0'">
                    <flux:icon.chevron-down class="h-5 w-5" />
                </span>
            </div>
        </button>

        <div x-cloak x-show="colapsos.horarios" x-transition.opacity.duration.200ms
            class="border-t border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
            <livewire:accion.generales.horarios-generales :slug_nivel="$slug_nivel" :key="'horarios-generales-' . $slug_nivel" />
        </div>
    </section>

    {{-- COLLAPSE: PROMEDIOS GENERALES --}}
    @if ($esPreescolar)
        {{-- COLLAPSE: LUGARES PREESCOLAR --}}
        <section
            class="overflow-hidden rounded-[1.7rem] border border-pink-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <button type="button" x-on:click="alternarCollapse('lugares_preescolar')"
                class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-pink-50 dark:hover:bg-neutral-800/70 sm:px-6">
                <div class="flex min-w-0 items-center gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-500 via-rose-500 to-fuchsia-600 text-white shadow-lg shadow-pink-500/20">
                        <flux:icon.trophy class="h-6 w-6" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-pink-600 dark:text-pink-300">
                            Reconocimientos
                        </p>

                        <h2 class="truncate text-lg font-black text-slate-900 dark:text-white">
                            Lugares Preescolar
                        </h2>

                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Asigna manualmente lugares por periodo o anual. No usa calificaciones ni promedios.
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <span class="hidden rounded-full border px-3 py-1 text-xs font-black sm:inline-flex"
                        :class="colapsos.lugares_preescolar ?
                            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300' :
                            'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300'"
                        x-text="colapsos.lugares_preescolar ? 'Abierto' : 'Cerrado'">
                    </span>

                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition duration-300 group-hover:border-pink-200 group-hover:text-pink-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                        :class="colapsos.lugares_preescolar ? 'rotate-180' : 'rotate-0'">
                        <flux:icon.chevron-down class="h-5 w-5" />
                    </span>
                </div>
            </button>

            <div x-cloak x-show="colapsos.lugares_preescolar" x-transition.opacity.duration.200ms
                class="border-t border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
                <livewire:accion.generales.lugares-preescolar :slug_nivel="$slug_nivel" :key="'lugares-preescolar-' . $slug_nivel" />
            </div>
        </section>
    @else
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
                            Consulta promedios generales y el concentrado por materia para primaria, secundaria y bachillerato, adaptado a periodos o parciales.
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <span class="hidden rounded-full border px-3 py-1 text-xs font-black sm:inline-flex"
                        :class="colapsos.promedios ?
                            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300' :
                            'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300'"
                        x-text="colapsos.promedios ? 'Abierto' : 'Cerrado'">
                    </span>

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

                @if (in_array($slug_nivel, ['primaria', 'secundaria', 'bachillerato'], true))
                    <div class="my-8 border-t border-slate-200 dark:border-neutral-800"></div>

                    <livewire:accion.generales.promedios-materias :slug_nivel="$slug_nivel"
                        :key="'promedios-materias-' . $slug_nivel" />


                    @if ($slug_nivel === 'primaria')
                        <div class="my-8 border-t border-slate-200 dark:border-neutral-800"></div>
                        <livewire:accion.generales.promedios-oficiales-primaria
                            :slug_nivel="$slug_nivel"
                            :key="'promedios-oficiales-primaria-' . $slug_nivel"
                        />
                    @endif
                @endif
            </div>
        </section>
    @endif


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
