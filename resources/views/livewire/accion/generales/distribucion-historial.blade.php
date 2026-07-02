<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <flux:select wire:model.live="generacion_id" label="Generación">
            <flux:select.option value="">Todas</flux:select.option>
            @foreach ($generaciones as $g)
                <flux:select.option value="{{ $g->id }}">
                    {{ $g->etiqueta }}{{ $g->status ? '' : ' · inactiva' }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="grado_id" label="Grado">
            <flux:select.option value="">Todos</flux:select.option>
            @foreach ($grados as $g)
                <flux:select.option value="{{ $g->id }}">{{ $g->nombre }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($slug_nivel === 'bachillerato')
            <flux:select wire:model.live="semestre_id" label="Semestre">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($semestres as $s)
                    <flux:select.option value="{{ $s->id }}">{{ $s->numero }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:select wire:model.live="grupo_id" label="Grupo">
            <flux:select.option value="">Todos</flux:select.option>
            @foreach ($grupos as $g)
                <flux:select.option value="{{ $g->id }}">
                    {{ $g->asignacionGrupo?->nombre ?? $g->id }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="estado" label="Estatus">
            <flux:select.option value="todos">Todos</flux:select.option>
            @foreach ($this->categorias as $valor => $texto)
                <flux:select.option value="{{ $valor }}">{{ $texto }}</flux:select.option>
            @endforeach
        </flux:select>

        <label
            class="flex min-h-[42px] cursor-pointer items-center gap-3 self-end rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800">
            <input type="checkbox" wire:model.live="solo_ya_no_estan"
                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            <span>Solo no activos</span>
        </label>
    </div>

    <div class="flex flex-wrap justify-end gap-2">
        <flux:button wire:click="limpiarFiltros" variant="ghost" icon="arrow-path">
            Limpiar
        </flux:button>

        <a target="_blank" href="{{ $this->urlPdf }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-red-200 hover:bg-red-50 hover:text-red-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            PDF
        </a>

        <a href="{{ $this->urlExcel }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            Excel
        </a>

        <a href="{{ $this->urlZip }}"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-500">
            ZIP
        </a>
    </div>

    <div class="space-y-4">
        @forelse ($this->bloques as $bloque)
            <section wire:key="generacion-{{ md5($bloque['ciclo']) }}" x-data="{ abierto: {{ $loop->first ? 'true' : 'false' }} }"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:border-slate-300 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                <button type="button" x-on:click="abierto = ! abierto"
                    class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70"
                    :aria-expanded="abierto">
                    <div class="flex min-w-0 items-center gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate text-base font-black text-slate-900 dark:text-white">
                                    {{ $bloque['ciclo'] }}
                                </h3>

                                <span
                                    class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20">
                                    {{ count($bloque['filas']) }}
                                    {{ count($bloque['filas']) === 1 ? 'grupo' : 'grupos' }}
                                </span>
                            </div>

                            <p class="mt-1 text-sm text-slate-500 dark:text-neutral-400">
                                Distribución e historial escolar de la generación
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <span class="block text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Total histórico
                            </span>
                            <span class="text-xl font-black text-slate-900 dark:text-white">
                                {{ $bloque['totales']['total_historico'] }}
                            </span>
                        </div>

                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition duration-300 group-hover:border-slate-300 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300"
                            :class="abierto ? 'rotate-180' : ''">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                    </div>
                </button>

                <div x-show="abierto" x-collapse x-cloak class="border-t border-slate-100 dark:border-neutral-800">
                    <div
                        class="grid grid-cols-2 gap-3 border-b border-slate-100 bg-slate-50/70 p-4 sm:grid-cols-3 lg:grid-cols-6 dark:border-neutral-800 dark:bg-neutral-950/40">
                        <div
                            class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total</span>
                            <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">
                                {{ $bloque['totales']['total_historico'] }}</p>
                        </div>
                        <div
                            class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-3 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                            <span
                                class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Activos</span>
                            <p class="mt-1 text-xl font-black text-emerald-700 dark:text-emerald-300">
                                {{ $bloque['totales']['activos'] ?? 0 }}</p>
                        </div>
                        <div
                            class="rounded-xl border border-rose-100 bg-rose-50/70 p-3 dark:border-rose-500/20 dark:bg-rose-500/10">
                            <span
                                class="text-xs font-semibold uppercase tracking-wide text-rose-600 dark:text-rose-400">Bajas</span>
                            <p class="mt-1 text-xl font-black text-rose-700 dark:text-rose-300">
                                {{ $bloque['totales']['bajas'] ?? 0 }}</p>
                        </div>
                        <div
                            class="rounded-xl border border-amber-100 bg-amber-50/70 p-3 dark:border-amber-500/20 dark:bg-amber-500/10">
                            <span
                                class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Traslados</span>
                            <p class="mt-1 text-xl font-black text-amber-700 dark:text-amber-300">
                                {{ $bloque['totales']['traslados'] ?? 0 }}</p>
                        </div>
                        <div
                            class="rounded-xl border border-violet-100 bg-violet-50/70 p-3 dark:border-violet-500/20 dark:bg-violet-500/10">
                            <span
                                class="text-xs font-semibold uppercase tracking-wide text-violet-600 dark:text-violet-400">Egresados</span>
                            <p class="mt-1 text-xl font-black text-violet-700 dark:text-violet-300">
                                {{ $bloque['totales']['egresados'] ?? 0 }}</p>
                        </div>
                        <div
                            class="rounded-xl border border-sky-100 bg-sky-50/70 p-3 dark:border-sky-500/20 dark:bg-sky-500/10">
                            <span
                                class="text-xs font-semibold uppercase tracking-wide text-sky-600 dark:text-sky-400">Grupos</span>
                            <p class="mt-1 text-xl font-black text-sky-700 dark:text-sky-300">
                                {{ count($bloque['filas']) }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead
                                class="bg-slate-50/90 text-xs uppercase tracking-wide text-slate-500 dark:bg-neutral-950/60 dark:text-neutral-400">
                                <tr>
                                    <th class="px-5 py-3.5 text-left font-bold">Grado</th>
                                    @if ($slug_nivel === 'bachillerato')
                                        <th class="px-4 py-3.5 text-center font-bold">Sem.</th>
                                    @endif
                                    <th class="px-4 py-3.5 text-center font-bold">Grupo</th>
                                    <th class="px-4 py-3.5 text-center font-bold">H</th>
                                    <th class="px-4 py-3.5 text-center font-bold">M</th>
                                    <th class="px-4 py-3.5 text-center font-bold">Total</th>
                                    <th class="px-4 py-3.5 text-center font-bold">Activos</th>
                                    <th class="px-4 py-3.5 text-center font-bold">Bajas</th>
                                    <th class="px-4 py-3.5 text-center font-bold">Traslados</th>
                                    <th class="px-5 py-3.5 text-center font-bold">Egresados</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                @foreach ($bloque['filas'] as $fila)
                                    <tr class="transition hover:bg-blue-50/40 dark:hover:bg-blue-500/5">
                                        <td class="whitespace-nowrap px-5 py-4">
                                            <span
                                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2.5 font-black text-slate-800 dark:bg-neutral-800 dark:text-white">
                                                {{ $fila['grado'] }}
                                            </span>
                                        </td>

                                        @if ($slug_nivel === 'bachillerato')
                                            <td
                                                class="px-4 py-4 text-center font-semibold text-slate-700 dark:text-neutral-200">
                                                {{ $fila['semestre'] }}
                                            </td>
                                        @endif

                                        <td class="px-4 py-4 text-center">
                                            <span
                                                class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                                {{ $fila['grupo'] }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-4 text-center font-semibold text-slate-600 dark:text-neutral-300">
                                            {{ $fila['hombres'] }}</td>
                                        <td
                                            class="px-4 py-4 text-center font-semibold text-slate-600 dark:text-neutral-300">
                                            {{ $fila['mujeres'] }}</td>
                                        <td class="px-4 py-4 text-center">
                                            <span
                                                class="inline-flex min-w-10 justify-center rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-black text-white dark:bg-white dark:text-slate-900">
                                                {{ $fila['total_historico'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span
                                                class="font-black text-emerald-600 dark:text-emerald-400">{{ $fila['activos'] }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span
                                                class="font-bold text-rose-600 dark:text-rose-400">{{ $fila['bajas'] }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span
                                                class="font-bold text-amber-600 dark:text-amber-400">{{ $fila['traslados'] }}</span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span
                                                class="font-bold text-violet-600 dark:text-violet-400">{{ $fila['egresados'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @empty
            <div
                class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 p-12 text-center dark:border-neutral-700 dark:bg-neutral-900/50">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-neutral-800">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M7 12h10M10 18h4" />
                    </svg>
                </div>
                <p class="mt-4 font-bold text-slate-700 dark:text-neutral-200">Sin información disponible</p>
                <p class="mt-1 text-sm text-slate-500">No hay alumnos para los filtros seleccionados.</p>
            </div>
        @endforelse
    </div>

    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-neutral-800">
            <div>
                <h3 class="font-black text-slate-900 dark:text-white">Listado nominal</h3>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-neutral-400">Detalle individual de alumnos según los
                    filtros aplicados</p>
            </div>
        </div>

        <div class="max-h-[520px] overflow-auto">
            <table class="min-w-full text-sm">
                <thead
                    class="sticky top-0 z-10 bg-slate-50/95 text-xs uppercase tracking-wide text-slate-500 backdrop-blur dark:bg-neutral-950/95 dark:text-neutral-400">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-bold">Alumno</th>
                        <th class="px-4 py-3.5 text-center font-bold">Generación</th>
                        <th class="px-4 py-3.5 text-center font-bold">Ubicación</th>
                        <th class="px-4 py-3.5 text-center font-bold">Estatus</th>
                        <th class="px-5 py-3.5 text-center font-bold">Fecha</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @foreach ($this->listado as $fila)
                        <tr class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/50">
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-900 dark:text-white">{{ $fila['alumno'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $fila['matricula'] }} ·
                                    {{ $fila['curp'] }}</p>
                            </td>
                            <td class="px-4 py-4 text-center font-semibold text-slate-700 dark:text-neutral-200">
                                {{ $fila['generacion'] }}</td>
                            <td class="px-4 py-4 text-center text-slate-600 dark:text-neutral-300">
                                {{ $fila['grado'] }}
                                @if ($slug_nivel === 'bachillerato')
                                    · Sem. {{ $fila['semestre'] }}
                                @endif
                                · {{ $fila['grupo'] }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span
                                    class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-neutral-800 dark:text-neutral-200">
                                    {{ $fila['estado_actual'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center text-slate-600 dark:text-neutral-300">
                                {{ $fila['fecha_baja'] !== '—' ? $fila['fecha_baja'] : $fila['fecha_alta'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
