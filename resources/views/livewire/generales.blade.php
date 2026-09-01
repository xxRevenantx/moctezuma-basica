<div class="space-y-6">
    @php
        $modulos = [
            [
                'titulo' => 'Cierre y continuidad',
                'descripcion' => 'Promoción, continuidad, repetición, traslado y egreso.',
                'ruta' => route('misrutas.cierre-continuidad', ['nivel' => $slug_nivel]),
                'icono' => 'academic-cap',
            ],
            [
                'titulo' => 'Movimientos escolares',
                'descripcion' => 'Bajas, suspensiones, no vigentes y reingresos.',
                'ruta' => route('misrutas.movimientos-escolares', ['nivel' => $slug_nivel]),
                'icono' => 'arrows-right-left',
            ],
            [
                'titulo' => 'Carga académica',
                'descripcion' => 'Materias, grupos y asignación docente.',
                'ruta' => route('misrutas.carga-academica', ['nivel' => $slug_nivel]),
                'icono' => 'book-open',
            ],
            [
                'titulo' => 'Horarios institucionales',
                'descripcion' => 'Horarios por grupo, generales, vacíos y docentes.',
                'ruta' => route('misrutas.horarios-institucionales', ['nivel' => $slug_nivel]),
                'icono' => 'calendar-days',
            ],
            [
                'titulo' => 'Resultados académicos',
                'descripcion' => 'Concentrados, promedios y resultados por materia.',
                'ruta' => route('misrutas.resultados-academicos', ['nivel' => $slug_nivel]),
                'icono' => 'chart-bar',
            ],
            [
                'titulo' => 'Archivo escolar',
                'descripcion' => 'Historial y generaciones egresadas.',
                'ruta' => route('misrutas.archivo-escolar', ['nivel' => $slug_nivel]),
                'icono' => 'archive-box',
            ],
            [
                'titulo' => 'Credenciales',
                'descripcion' => 'Credenciales de alumnos y personal.',
                'ruta' => route('misrutas.credenciales-institucionales', ['nivel' => $slug_nivel]),
                'icono' => 'identification',
            ],
        ];
    @endphp

    {{-- Selector de nivel --}}
    <div class="overflow-hidden">
        <div class="-mx-1 overflow-x-auto pb-1">
            <div class="flex min-w-max items-center justify-center gap-2 px-1">
                @foreach ($niveles as $item)
                    @php($activo = $slug_nivel === $item->slug)
                    <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'generales']) }}"
                        wire:navigate
                        class="group relative inline-flex items-center gap-2 whitespace-nowrap rounded-2xl border px-4 py-3 text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5
                            {{ $activo
                                ? 'border-sky-200 bg-gradient-to-r from-[#006492] to-sky-600 text-white shadow-lg shadow-sky-500/20'
                                : 'border-slate-200 bg-white text-slate-700 shadow-sm hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200' }}">
                        <flux:icon.rectangle-stack class="h-4 w-4" />
                        <span>{{ $item->nombre }}</span>
                        @if ($activo)
                            <span
                                class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide">Activo</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Cabecera de resumen --}}
    <section
        class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>
        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] text-white shadow-lg shadow-sky-900/15">
                        <flux:icon.squares-2x2 class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-[#006492] dark:text-sky-300">
                            Resumen del nivel</p>
                        <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">
                            {{ $nivel->nombre }}</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Vista ejecutiva del padrón. Los procesos administrativos y académicos especializados ahora
                            se encuentran en módulos globales del sidebar.
                        </p>
                    </div>
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
                    <flux:button type="button" wire:click="limpiarFiltroEstadistica" variant="ghost" icon="arrow-path">
                        Limpiar</flux:button>
                    <flux:button type="button" wire:click="exportarEstadisticaExcel" variant="primary"
                        icon="arrow-down-tray" spinner="exportarEstadisticaExcel">Excel</flux:button>
                </div>
            </div>
        </div>
    </section>

    {{-- Indicadores --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @foreach ([
        'total' => ['Total', 'users'],
        'activos' => ['Activos', 'user-group'],
        'bajas' => ['Bajas', 'user-minus'],
        'egresados' => ['Egresados', 'academic-cap'],
        'hombres' => ['Hombres', 'user'],
        'mujeres' => ['Mujeres', 'user'],
    ] as $clave => [$etiqueta, $icono])
            <div
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ $etiqueta }}</p>
                    <flux:icon :name="$icono" class="h-4 w-4 text-slate-400" />
                </div>
                <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $this->resumen[$clave] ?? 0 }}</p>
            </div>
        @endforeach
    </div>

    {{-- Accesos globales --}}
    <section
        class="rounded-[1.8rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
        <div class="mb-4">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-[#88AC2E]">Módulos especializados</p>
            <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Continúa el trabajo en una vista global
            </h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cada módulo conserva {{ $nivel->nombre }} como
                nivel inicial y permite cambiar de nivel sin regresar aquí.</p>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($modulos as $modulo)
                <a href="{{ $modulo['ruta'] }}" wire:navigate
                    class="group flex items-start gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-sky-50 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-950/40 dark:hover:border-sky-900/70 dark:hover:bg-sky-950/15">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-[#006492] shadow-sm ring-1 ring-slate-200 transition group-hover:bg-[#006492] group-hover:text-white dark:bg-neutral-900 dark:ring-neutral-700">
                        <flux:icon :name="$modulo['icono']" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block font-black text-slate-900 dark:text-white">{{ $modulo['titulo'] }}</span>
                        <span
                            class="mt-1 block text-sm leading-5 text-slate-500 dark:text-slate-400">{{ $modulo['descripcion'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Distribución escolar institucional --}}
    <section
        class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_18px_45px_-28px_rgba(15,23,42,0.45)] dark:border-neutral-800 dark:bg-neutral-900">

        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>

        <div
            class="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-lime-50/40 px-5 pb-5 pt-6 dark:border-neutral-800 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-900 sm:px-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-lg shadow-sky-900/15">
                        <flux:icon.table-cells class="h-6 w-6" />
                    </div>

                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#88AC2E]">
                            Concentrado institucional
                        </p>

                        <h2 class="mt-1 text-xl font-black tracking-tight text-slate-950 dark:text-white">
                            Distribución escolar actual
                        </h2>

                        <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Concentrado por grado, semestre y grupo de la generación seleccionada, con separación
                            entre matrícula vigente y registros administrativos no vigentes.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ $this->distribucionPdfUrl }}" target="_blank" rel="noopener"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-[#006492] px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-sky-900/15 transition hover:-translate-y-0.5 hover:bg-[#00557c]">
                        <flux:icon.document-text class="h-4 w-4" />
                        PDF
                    </a>

                    <a href="{{ $this->distribucionWordUrl }}"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-[#88AC2E] px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-lime-900/10 transition hover:-translate-y-0.5 hover:bg-[#759625]">
                        <flux:icon.document-arrow-down class="h-4 w-4" />
                        Word
                    </a>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6">
                <div
                    class="rounded-2xl border border-sky-100 bg-sky-50/80 px-4 py-3 dark:border-sky-900/40 dark:bg-sky-950/20">
                    <p class="text-[9px] font-black uppercase tracking-[0.12em] text-sky-700 dark:text-sky-300">
                        Matrícula vigente
                    </p>
                    <p class="mt-1 text-2xl font-black text-[#006492] dark:text-sky-300">
                        {{ $this->totalesDistribucion['activos'] ?? 0 }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-amber-100 bg-amber-50/80 px-4 py-3 dark:border-amber-900/40 dark:bg-amber-950/20">
                    <p class="text-[9px] font-black uppercase tracking-[0.12em] text-amber-700 dark:text-amber-300">
                        No vigentes
                    </p>
                    <p class="mt-1 text-2xl font-black text-amber-700 dark:text-amber-300">
                        {{ $this->totalesDistribucion['no_vigentes'] ?? 0 }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <p class="text-[9px] font-black uppercase tracking-[0.12em] text-slate-400">
                        Hombres vigentes
                    </p>
                    <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        {{ $this->totalesDistribucion['hombres'] ?? 0 }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <p class="text-[9px] font-black uppercase tracking-[0.12em] text-slate-400">
                        Mujeres vigentes
                    </p>
                    <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        {{ $this->totalesDistribucion['mujeres'] ?? 0 }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-lime-100 bg-lime-50/70 px-4 py-3 dark:border-lime-900/40 dark:bg-lime-950/20">
                    <p class="text-[9px] font-black uppercase tracking-[0.12em] text-[#6D8E19] dark:text-lime-300">
                        Grupos
                    </p>
                    <p class="mt-1 text-2xl font-black text-[#88AC2E]">
                        {{ $this->distribucionEscolar->count() }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <p class="text-[9px] font-black uppercase tracking-[0.12em] text-slate-400">
                        Registros del ciclo
                    </p>
                    <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        {{ $this->totalesDistribucion['total'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] text-sm">
                <thead>
                    <tr class="bg-[#101827] text-white">
                        <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.08em]">Grado</th>

                        @if ($slug_nivel === 'bachillerato')
                            <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">
                                Sem.
                            </th>
                        @endif

                        <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">Grupo
                        </th>
                        <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">H</th>
                        <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">M</th>
                        <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">Vigentes
                        </th>
                        <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">Inactivos
                        </th>
                        <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">Bajas
                        </th>
                        <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">Trasl.
                        </th>
                        <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">Susp.
                        </th>
                        <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">Egres.
                        </th>
                        <th
                            class="bg-[#006492] px-4 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.08em]">
                            Total ciclo
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 dark:divide-neutral-800">
                    @forelse ($this->distribucionEscolar as $fila)
                        <tr
                            class="{{ ($fila['no_vigentes'] ?? 0) > 0 ? 'bg-amber-50/50 dark:bg-amber-950/10' : 'bg-white dark:bg-neutral-900' }} transition hover:bg-sky-50/60 dark:hover:bg-sky-950/10">

                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-xl bg-sky-50 px-2 font-black text-[#006492] ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                                        {{ $fila['grado'] }}
                                    </span>

                                    @if (($fila['no_vigentes'] ?? 0) > 0)
                                        <span
                                            class="rounded-full bg-amber-100 px-2 py-1 text-[9px] font-black uppercase tracking-wide text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                            {{ $fila['no_vigentes'] }}
                                            {{ ($fila['no_vigentes'] ?? 0) === 1 ? 'no vigente' : 'no vigentes' }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            @if ($slug_nivel === 'bachillerato')
                                <td class="px-3 py-3.5 text-center font-bold text-slate-700 dark:text-slate-200">
                                    {{ $fila['semestre'] ?? '—' }}
                                </td>
                            @endif

                            <td class="px-3 py-3.5 text-center">
                                <span
                                    class="inline-flex min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 py-1 font-black text-slate-700 dark:bg-neutral-800 dark:text-slate-200">
                                    {{ $fila['grupo'] }}
                                </span>
                            </td>

                            <td class="px-3 py-3.5 text-center font-semibold text-slate-700 dark:text-slate-200">
                                {{ $fila['hombres'] }}
                            </td>

                            <td class="px-3 py-3.5 text-center font-semibold text-slate-700 dark:text-slate-200">
                                {{ $fila['mujeres'] }}
                            </td>

                            <td class="px-3 py-3.5 text-center">
                                <span
                                    class="inline-flex min-w-9 items-center justify-center rounded-full bg-emerald-50 px-2.5 py-1 font-black text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/40">
                                    {{ $fila['activos'] }}
                                </span>
                            </td>

                            <td
                                class="px-3 py-3.5 text-center font-bold {{ ($fila['inactivos'] ?? 0) > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-300 dark:text-neutral-600' }}">
                                {{ $fila['inactivos'] ?? 0 }}
                            </td>

                            <td
                                class="px-3 py-3.5 text-center font-bold {{ ($fila['bajas'] ?? 0) > 0 ? 'text-rose-700 dark:text-rose-300' : 'text-slate-300 dark:text-neutral-600' }}">
                                {{ $fila['bajas'] ?? 0 }}
                            </td>

                            <td
                                class="px-3 py-3.5 text-center font-bold {{ ($fila['traslados'] ?? 0) > 0 ? 'text-orange-700 dark:text-orange-300' : 'text-slate-300 dark:text-neutral-600' }}">
                                {{ $fila['traslados'] ?? 0 }}
                            </td>

                            <td
                                class="px-3 py-3.5 text-center font-bold {{ ($fila['suspendidos'] ?? 0) > 0 ? 'text-orange-700 dark:text-orange-300' : 'text-slate-300 dark:text-neutral-600' }}">
                                {{ $fila['suspendidos'] ?? 0 }}
                            </td>

                            <td
                                class="px-3 py-3.5 text-center font-bold {{ ($fila['egresados'] ?? 0) > 0 ? 'text-violet-700 dark:text-violet-300' : 'text-slate-300 dark:text-neutral-600' }}">
                                {{ $fila['egresados'] ?? 0 }}
                            </td>

                            <td
                                class="bg-sky-50/70 px-4 py-3.5 text-center text-base font-black text-[#006492] dark:bg-sky-950/20 dark:text-sky-300">
                                {{ $fila['total'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $slug_nivel === 'bachillerato' ? 12 : 11 }}"
                                class="px-4 py-14 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div
                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                                        <flux:icon.user-group class="h-6 w-6" />
                                    </div>

                                    <p class="mt-3 font-black text-slate-700 dark:text-slate-200">
                                        Sin datos para mostrar
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                        No hay alumnos para la generación y filtros seleccionados.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if ($this->distribucionEscolar->isNotEmpty())
                    <tfoot>
                        <tr
                            class="border-t-2 border-[#006492] bg-slate-100 font-black text-slate-950 dark:bg-neutral-800 dark:text-white">
                            <td class="px-4 py-4 text-left text-xs uppercase tracking-[0.08em]">Totales</td>

                            @if ($slug_nivel === 'bachillerato')
                                <td class="px-3 py-4 text-center">—</td>
                            @endif

                            <td class="px-3 py-4 text-center text-xs">
                                {{ $this->distribucionEscolar->count() }} grupos
                            </td>

                            <td class="px-3 py-4 text-center">{{ $this->totalesDistribucion['hombres'] ?? 0 }}</td>
                            <td class="px-3 py-4 text-center">{{ $this->totalesDistribucion['mujeres'] ?? 0 }}</td>

                            <td class="px-3 py-4 text-center text-emerald-700 dark:text-emerald-300">
                                {{ $this->totalesDistribucion['activos'] ?? 0 }}
                            </td>

                            <td class="px-3 py-4 text-center text-amber-700 dark:text-amber-300">
                                {{ $this->totalesDistribucion['inactivos'] ?? 0 }}
                            </td>

                            <td class="px-3 py-4 text-center text-rose-700 dark:text-rose-300">
                                {{ $this->totalesDistribucion['bajas'] ?? 0 }}
                            </td>

                            <td class="px-3 py-4 text-center text-orange-700 dark:text-orange-300">
                                {{ $this->totalesDistribucion['traslados'] ?? 0 }}
                            </td>

                            <td class="px-3 py-4 text-center text-orange-700 dark:text-orange-300">
                                {{ $this->totalesDistribucion['suspendidos'] ?? 0 }}
                            </td>

                            <td class="px-3 py-4 text-center text-violet-700 dark:text-violet-300">
                                {{ $this->totalesDistribucion['egresados'] ?? 0 }}
                            </td>

                            <td class="bg-[#006492] px-4 py-4 text-center text-base text-white">
                                {{ $this->totalesDistribucion['total'] ?? 0 }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div
            class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 text-xs text-slate-500 dark:border-neutral-800 dark:bg-neutral-950/40 dark:text-slate-400 lg:flex-row lg:items-center lg:justify-between sm:px-6">
            <div class="flex items-start gap-2">
                <flux:icon.information-circle class="mt-0.5 h-4 w-4 shrink-0 text-[#006492]" />

                <p class="leading-5">
                    <b class="text-slate-700 dark:text-slate-200">Lectura institucional:</b>
                    H + M corresponde a matrícula vigente.
                    El total del ciclo incluye también registros administrativos no vigentes.
                </p>
            </div>

            <div class="flex items-center gap-2 font-black text-[#006492] dark:text-sky-300">
                <span class="h-2 w-2 rounded-full bg-[#88AC2E]"></span>
                Centro Universitario Moctezuma
            </div>
        </div>
    </section>
</div>
