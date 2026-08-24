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
                            <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide">Activo</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Cabecera de resumen --}}
    <section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>
        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] text-white shadow-lg shadow-sky-900/15">
                        <flux:icon.squares-2x2 class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-[#006492] dark:text-sky-300">Resumen del nivel</p>
                        <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ $nivel->nombre }}</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Vista ejecutiva del padrón. Los procesos administrativos y académicos especializados ahora se encuentran en módulos globales del sidebar.
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
                    <flux:button type="button" wire:click="limpiarFiltroEstadistica" variant="ghost" icon="arrow-path">Limpiar</flux:button>
                    <flux:button type="button" wire:click="exportarEstadisticaExcel" variant="primary" icon="arrow-down-tray" spinner="exportarEstadisticaExcel">Excel</flux:button>
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
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $etiqueta }}</p>
                    <flux:icon :name="$icono" class="h-4 w-4 text-slate-400" />
                </div>
                <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $this->resumen[$clave] ?? 0 }}</p>
            </div>
        @endforeach
    </div>

    {{-- Accesos globales --}}
    <section class="rounded-[1.8rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
        <div class="mb-4">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-[#88AC2E]">Módulos especializados</p>
            <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Continúa el trabajo en una vista global</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cada módulo conserva {{ $nivel->nombre }} como nivel inicial y permite cambiar de nivel sin regresar aquí.</p>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($modulos as $modulo)
                <a href="{{ $modulo['ruta'] }}" wire:navigate
                    class="group flex items-start gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-sky-50 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-950/40 dark:hover:border-sky-900/70 dark:hover:bg-sky-950/15">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-[#006492] shadow-sm ring-1 ring-slate-200 transition group-hover:bg-[#006492] group-hover:text-white dark:bg-neutral-900 dark:ring-neutral-700">
                        <flux:icon :name="$modulo['icono']" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block font-black text-slate-900 dark:text-white">{{ $modulo['titulo'] }}</span>
                        <span class="mt-1 block text-sm leading-5 text-slate-500 dark:text-slate-400">{{ $modulo['descripcion'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Distribución actual --}}
    <section class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950/40">
            <h2 class="font-black text-slate-950 dark:text-white">Distribución actual</h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Conteos por grado, semestre y grupo de la generación seleccionada.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left">Grado</th>
                        @if ($slug_nivel === 'bachillerato')<th class="px-4 py-3 text-center">Semestre</th>@endif
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
                        <tr>
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">{{ $fila['grado'] }}</td>
                            @if ($slug_nivel === 'bachillerato')<td class="px-4 py-3 text-center">{{ $fila['semestre'] ?? '—' }}</td>@endif
                            <td class="px-4 py-3 text-center">{{ $fila['grupo'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $fila['hombres'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $fila['mujeres'] }}</td>
                            <td class="px-4 py-3 text-center font-black">{{ $fila['total'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $fila['activos'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $fila['bajas'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $fila['egresados'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $slug_nivel === 'bachillerato' ? 9 : 8 }}" class="px-4 py-10 text-center text-slate-500">No hay alumnos para la generación seleccionada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
