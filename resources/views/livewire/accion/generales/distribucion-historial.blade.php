<div class="space-y-5" wire:key="distribucion-historial-{{ $slug_nivel }}">
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div
            class="border-b border-slate-200 bg-gradient-to-r from-[#006492] via-sky-700 to-[#88AC2E] px-5 py-5 text-white dark:border-neutral-800 sm:px-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 shadow-lg">
                        <flux:icon.user-group class="h-6 w-6" />
                    </div>

                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-white/75">
                            Generales · {{ $nivel?->nombre }}
                        </p>
                        <h2 class="mt-1 text-xl font-black sm:text-2xl">
                            Distribución e historial escolar
                        </h2>
                        <p class="mt-1 max-w-3xl text-sm leading-6 text-white/80">
                            Consulta alumnos activos, inactivos, bajas, traslados, suspendidos y egresados sin perder su
                            generación, matrícula ni trayectoria histórica.
                        </p>
                    </div>
                </div>

                <div class="inline-flex w-full rounded-2xl bg-white/10 p-1 xl:w-auto">
                    <button type="button" wire:click="$set('modo', 'ciclo')"
                        class="flex-1 rounded-xl px-4 py-2.5 text-sm font-black transition xl:flex-none
                            {{ $modo === 'ciclo' ? 'bg-white text-[#006492] shadow-sm' : 'text-white hover:bg-white/10' }}">
                        Ciclo seleccionado
                    </button>
                    <button type="button" wire:click="$set('modo', 'historico')"
                        class="flex-1 rounded-xl px-4 py-2.5 text-sm font-black transition xl:flex-none
                            {{ $modo === 'historico' ? 'bg-white text-[#006492] shadow-sm' : 'text-white hover:bg-white/10' }}">
                        Historial completo
                    </button>
                </div>
            </div>
        </div>

        <div class="relative space-y-5 p-5 sm:p-6">
            <div wire:loading.flex
                wire:target="modo,ciclo_escolar_id,generacion_id,grado_id,grupo_id,estado,solo_ya_no_estan,limpiarFiltros"
                class="absolute inset-0 z-20 items-start justify-center bg-white/70 pt-24 backdrop-blur-sm dark:bg-neutral-900/70">
                <div
                    class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-3 font-black text-slate-700 shadow-xl dark:border-neutral-700 dark:bg-neutral-800 dark:text-white">
                    <flux:icon.arrow-path class="h-5 w-5 animate-spin" />
                    Actualizando distribución...
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                @if ($modo === 'ciclo')
                    <div>
                        <label
                            class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            Ciclo escolar
                        </label>
                        <flux:select wire:model.live="ciclo_escolar_id">
                            @foreach ($cicloEscolares as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->nombre }}{{ $ciclo->es_actual ? ' · Actual' : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @else
                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                        <p class="text-xs font-black uppercase tracking-wide text-emerald-600 dark:text-emerald-300">
                            Periodo consultado
                        </p>
                        <p class="mt-1 text-sm font-black text-emerald-900 dark:text-emerald-100">
                            Todos los ciclos disponibles
                        </p>
                    </div>
                @endif

                <div>
                    <label
                        class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Generación
                    </label>
                    <flux:select wire:model.live="generacion_id">
                        <flux:select.option value="">Todas las generaciones</flux:select.option>
                        @foreach ($generaciones as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">
                                {{ $generacion->anio_ingreso }}-{{ $generacion->anio_egreso }}
                                {{ !$generacion->status ? ' · Inactiva' : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <label
                        class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Grado
                    </label>
                    <flux:select wire:model.live="grado_id">
                        <flux:select.option value="">Todos los grados</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}°</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <label
                        class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Grupo
                    </label>
                    <flux:select wire:model.live="grupo_id">
                        <flux:select.option value="">Todos los grupos</flux:select.option>
                        @foreach ($this->gruposFiltro as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">
                                {{ $grupo->asignacionGrupo?->nombre ?? 'Grupo' }}
                                · {{ $grupo->grado?->nombre ?? '—' }}°
                                ·
                                {{ $grupo->generacion?->anio_ingreso ?? '—' }}-{{ $grupo->generacion?->anio_egreso ?? '—' }}
                                @if ($grupo->semestre?->numero)
                                    · Sem. {{ $grupo->semestre->numero }}
                                @endif
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <label
                        class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Estado histórico
                    </label>
                    <flux:select wire:model.live="estado">
                        <flux:select.option value="todos">Todos los estados</flux:select.option>
                        @foreach ($this->categorias as $clave => $etiqueta)
                            <flux:select.option value="{{ $clave }}">{{ $etiqueta }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex items-end">
                    <label
                        class="flex min-h-10 w-full cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 transition hover:border-rose-300 hover:bg-rose-50 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:border-rose-900/50 dark:hover:bg-rose-950/20">
                        <input type="checkbox" wire:model.live="solo_ya_no_estan"
                            class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                        <span>
                            <b class="block text-sm text-slate-800 dark:text-white">Ya no están</b>
                            <span class="block text-[11px] text-slate-500">Estado actual no activo</span>
                        </span>
                    </label>
                </div>
            </div>

            <div
                class="flex flex-col gap-3 border-t border-slate-100 pt-5 dark:border-neutral-800 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500 dark:text-slate-400">
                    <span
                        class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                        Activos
                    </span>
                    <span
                        class="rounded-full bg-slate-200 px-3 py-1 text-slate-700 dark:bg-neutral-700 dark:text-slate-200">
                        Inactivos
                    </span>
                    <span
                        class="rounded-full bg-rose-100 px-3 py-1 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200">
                        Bajas
                    </span>
                    <span
                        class="rounded-full bg-amber-100 px-3 py-1 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                        Traslados
                    </span>
                    <span
                        class="rounded-full bg-violet-100 px-3 py-1 text-violet-700 dark:bg-violet-900/30 dark:text-violet-200">
                        Egresados
                    </span>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button type="button" wire:click="limpiarFiltros" variant="ghost" icon="funnel">
                        Limpiar filtros
                    </flux:button>
                    <flux:button href="{{ $this->urlPdf }}" target="_blank" variant="filled" icon="document-text">
                        PDF
                    </flux:button>
                    <flux:button href="{{ $this->urlExcel }}" target="_blank" variant="filled" icon="table-cells">
                        Excel
                    </flux:button>
                    <flux:button href="{{ $this->urlZip }}" target="_blank" variant="primary"
                        icon="archive-box-arrow-down">
                        ZIP por generaciones
                    </flux:button>
                </div>
            </div>
        </div>
    </section>

    @forelse ($this->bloques as $bloque)
        <section wire:key="bloque-distribucion-{{ $bloque['ciclo_escolar_id'] }}"
            class="overflow-hidden rounded-2xl border border-[#88AC2E]/40 bg-white shadow-sm dark:border-[#88AC2E]/30 dark:bg-neutral-900">
            <div
                class="flex flex-col gap-2 bg-[#006492] px-5 py-4 text-white sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-white/70">Distribución escolar</p>
                    <h3 class="mt-1 text-xl font-black">{{ $bloque['ciclo'] }}</h3>
                </div>

                <div class="flex flex-wrap gap-2 text-xs font-black">
                    @if ($bloque['es_actual'])
                        <span class="rounded-full bg-[#88AC2E] px-3 py-1 text-white">Ciclo actual</span>
                    @endif
                    <span class="rounded-full bg-white/15 px-3 py-1">
                        {{ number_format($bloque['totales']['total_historico']) }} alumnos históricos
                    </span>
                    <span class="rounded-full bg-white/15 px-3 py-1">
                        {{ number_format($bloque['totales']['activos']) }} activos
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[2450px] w-full border-separate border-spacing-0 text-xs">
                    <thead>
                        <tr class="bg-[#88AC2E] text-center text-white">
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Regional</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Zona</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">CCT</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Nombre CT</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Nivel</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Turno</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Grado</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Sem.</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Grupo</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">H</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">M</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Histórico</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Activos</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Inactivos</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Bajas</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Traslados</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Suspendidos</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Egresados</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Generación</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Maestro</th>
                            <th class="border-r border-white/40 px-3 py-2.5 font-black">Director</th>
                            <th class="px-3 py-2.5 font-black">Detalle</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($bloque['filas'] as $fila)
                            <tr wire:key="fila-{{ $bloque['ciclo_escolar_id'] }}-{{ $fila['grado_id'] }}-{{ $fila['grupo_id'] }}-{{ $fila['generacion_id'] }}-{{ $fila['semestre_id'] ?? 0 }}"
                                wire:click="abrirDetalle({{ $fila['ciclo_escolar_id'] }}, {{ $fila['grado_id'] ?? 'null' }}, {{ $fila['grupo_id'] ?? 'null' }}, {{ $fila['generacion_id'] ?? 'null' }}, {{ $fila['semestre_id'] ?? 'null' }})"
                                class="cursor-pointer bg-white text-center text-slate-700 transition hover:bg-sky-50 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-sky-950/20">
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-bold dark:border-neutral-700">
                                    {{ $fila['regional'] }}
                                </td>
                                <td class="border-b border-r border-slate-200 px-3 py-3 dark:border-neutral-700">
                                    {{ $fila['zona'] }}
                                </td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black dark:border-neutral-700">
                                    {{ $fila['cct'] }}
                                </td>
                                <td
                                    class="max-w-64 border-b border-r border-slate-200 px-3 py-3 text-left font-bold dark:border-neutral-700">
                                    {{ $fila['nombre_ct'] }}
                                </td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-bold dark:border-neutral-700">
                                    {{ $fila['nivel'] }}
                                </td>
                                <td class="border-b border-r border-slate-200 px-3 py-3 dark:border-neutral-700">
                                    {{ $fila['turno'] }}
                                </td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black dark:border-neutral-700">
                                    {{ $fila['grado'] }}°
                                </td>
                                <td class="border-b border-r border-slate-200 px-3 py-3 dark:border-neutral-700">
                                    {{ $fila['semestre'] }}
                                </td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black dark:border-neutral-700">
                                    {{ $fila['grupo'] }}
                                </td>
                                <td class="border-b border-r border-slate-200 px-3 py-3 dark:border-neutral-700">
                                    {{ $fila['hombres'] }}</td>
                                <td class="border-b border-r border-slate-200 px-3 py-3 dark:border-neutral-700">
                                    {{ $fila['mujeres'] }}</td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black dark:border-neutral-700">
                                    {{ $fila['total_historico'] }}</td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black text-emerald-700 dark:border-neutral-700 dark:text-emerald-300">
                                    {{ $fila['activos'] }}</td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black text-slate-600 dark:border-neutral-700 dark:text-slate-300">
                                    {{ $fila['inactivos'] }}</td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black text-rose-700 dark:border-neutral-700 dark:text-rose-300">
                                    {{ $fila['bajas'] }}</td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black text-amber-700 dark:border-neutral-700 dark:text-amber-300">
                                    {{ $fila['traslados'] }}</td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black text-orange-700 dark:border-neutral-700 dark:text-orange-300">
                                    {{ $fila['suspendidos'] }}</td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black text-violet-700 dark:border-neutral-700 dark:text-violet-300">
                                    {{ $fila['egresados'] }}</td>
                                <td
                                    class="border-b border-r border-slate-200 px-3 py-3 font-black dark:border-neutral-700">
                                    {{ $fila['generacion'] }}</td>
                                <td
                                    class="max-w-56 border-b border-r border-slate-200 px-3 py-3 text-left dark:border-neutral-700">
                                    {{ $fila['maestro'] }}</td>
                                <td
                                    class="max-w-56 border-b border-r border-slate-200 px-3 py-3 text-left dark:border-neutral-700">
                                    {{ $fila['director'] }}</td>
                                <td class="border-b border-slate-200 px-3 py-3 dark:border-neutral-700">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-lg bg-sky-100 px-2.5 py-1.5 font-black text-sky-700 dark:bg-sky-900/30 dark:text-sky-200">
                                        <flux:icon.eye class="h-3.5 w-3.5" /> Alumnos
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr
                            class="bg-slate-100 text-center font-black text-slate-900 dark:bg-neutral-800 dark:text-white">
                            <td colspan="9"
                                class="border-r border-t border-slate-300 px-3 py-3 dark:border-neutral-700">TOTALES
                            </td>
                            <td class="border-r border-t border-slate-300 px-3 py-3 dark:border-neutral-700">
                                {{ $bloque['totales']['hombres'] }}</td>
                            <td class="border-r border-t border-slate-300 px-3 py-3 dark:border-neutral-700">
                                {{ $bloque['totales']['mujeres'] }}</td>
                            <td class="border-r border-t border-slate-300 px-3 py-3 dark:border-neutral-700">
                                {{ $bloque['totales']['total_historico'] }}</td>
                            <td
                                class="border-r border-t border-slate-300 px-3 py-3 text-emerald-700 dark:border-neutral-700 dark:text-emerald-300">
                                {{ $bloque['totales']['activos'] }}</td>
                            <td class="border-r border-t border-slate-300 px-3 py-3 dark:border-neutral-700">
                                {{ $bloque['totales']['inactivos'] }}</td>
                            <td
                                class="border-r border-t border-slate-300 px-3 py-3 text-rose-700 dark:border-neutral-700 dark:text-rose-300">
                                {{ $bloque['totales']['bajas'] }}</td>
                            <td
                                class="border-r border-t border-slate-300 px-3 py-3 text-amber-700 dark:border-neutral-700 dark:text-amber-300">
                                {{ $bloque['totales']['traslados'] }}</td>
                            <td
                                class="border-r border-t border-slate-300 px-3 py-3 text-orange-700 dark:border-neutral-700 dark:text-orange-300">
                                {{ $bloque['totales']['suspendidos'] }}</td>
                            <td
                                class="border-r border-t border-slate-300 px-3 py-3 text-violet-700 dark:border-neutral-700 dark:text-violet-300">
                                {{ $bloque['totales']['egresados'] }}</td>
                            <td colspan="4" class="border-t border-slate-300 px-3 py-3 dark:border-neutral-700">
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    @empty
        <section
            class="rounded-[1.7rem] border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                <flux:icon.user-group class="h-8 w-8" />
            </div>
            <h3 class="mt-4 text-lg font-black text-slate-900 dark:text-white">No hay historial para estos filtros</h3>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                Ejecuta la reconstrucción histórica para ciclos anteriores o cambia los filtros de ciclo, generación,
                grado, grupo y estado.
            </p>
        </section>
    @endforelse

    @if ($modalDetalle)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-3 backdrop-blur-sm sm:p-5"
            wire:key="modal-detalle-distribucion">
            <div
                class="max-h-[94vh] w-full max-w-[1500px] overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
                <div class="flex items-start justify-between gap-4 bg-[#006492] px-5 py-4 text-white sm:px-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-white/70">Listado nominal</p>
                        <h3 class="mt-1 text-lg font-black sm:text-xl">{{ $this->tituloDetalle }}</h3>
                        <p class="mt-1 text-xs text-white/70">
                            {{ $this->detalleAlumnos->count() }} alumno(s) con los filtros del detalle.
                        </p>
                    </div>

                    <button type="button" wire:click="cerrarDetalle"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 transition hover:bg-white/20">
                        <flux:icon.x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="grid gap-4 border-b border-slate-200 p-4 dark:border-neutral-800 sm:grid-cols-[minmax(0,1fr)_250px] sm:p-5">
                    <flux:input type="search" wire:model.live.debounce.350ms="buscar_detalle"
                        icon="magnifying-glass" placeholder="Buscar por matrícula, CURP o nombre..." />

                    <flux:select wire:model.live="estado_detalle">
                        <flux:select.option value="todos">Todos los estados</flux:select.option>
                        @foreach ($this->categorias as $clave => $etiqueta)
                            <flux:select.option value="{{ $clave }}">{{ $etiqueta }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="max-h-[calc(94vh-190px)] overflow-auto">
                    <table class="min-w-[1500px] w-full text-left text-xs">
                        <thead class="sticky top-0 z-10 bg-slate-900 text-white">
                            <tr>
                                <th class="px-4 py-3">Matrícula</th>
                                <th class="px-4 py-3">CURP</th>
                                <th class="px-4 py-3">Alumno</th>
                                <th class="px-4 py-3">Generación</th>
                                <th class="px-4 py-3">Grado / Grupo</th>
                                <th class="px-4 py-3">Estado histórico</th>
                                <th class="px-4 py-3">Estado actual</th>
                                <th class="px-4 py-3">Alta</th>
                                <th class="px-4 py-3">Baja / término</th>
                                <th class="px-4 py-3">Motivo</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                            @forelse ($this->detalleAlumnos as $alumno)
                                <tr wire:key="detalle-alumno-{{ $alumno['trayectoria_id'] }}"
                                    class="align-top hover:bg-sky-50/50 dark:hover:bg-sky-950/10">
                                    <td class="px-4 py-4 font-black text-slate-900 dark:text-white">
                                        {{ $alumno['matricula'] }}</td>
                                    <td class="px-4 py-4 font-mono text-[11px] text-slate-600 dark:text-slate-300">
                                        {{ $alumno['curp'] }}</td>
                                    <td class="px-4 py-4">
                                        <p class="font-black text-slate-900 dark:text-white">{{ $alumno['nombre'] }}
                                        </p>
                                        <p class="mt-1 text-[11px] text-slate-500">
                                            {{ $alumno['genero'] === 'H' ? 'Hombre' : ($alumno['genero'] === 'M' ? 'Mujer' : 'Sin género') }}
                                            @if ($alumno['reconstruido'])
                                                · <span class="font-bold text-amber-600">Dato reconstruido</span>
                                            @endif
                                        </p>
                                    </td>
                                    <td class="px-4 py-4 font-bold">{{ $alumno['generacion'] }}</td>
                                    <td class="px-4 py-4">
                                        <b>{{ $alumno['grado'] }}° {{ $alumno['grupo'] }}</b>
                                        @if ($alumno['semestre'] !== '—')
                                            <br><span class="text-slate-500">Semestre {{ $alumno['semestre'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 font-black {{ $this->claseBadgeEstado($alumno['categoria_historica']) }}">
                                            {{ $alumno['estado_historico'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 font-black {{ $this->claseBadgeEstado($alumno['categoria_actual'], true) }}">
                                            {{ $alumno['estado_actual'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">{{ $alumno['fecha_alta'] }}</td>
                                    <td class="px-4 py-4">{{ $alumno['fecha_baja'] }}</td>
                                    <td class="max-w-56 px-4 py-4 text-slate-600 dark:text-slate-300">
                                        {{ $alumno['motivo'] }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button type="button"
                                                wire:click="abrirTrayectoria({{ $alumno['inscripcion_id'] }})"
                                                class="inline-flex items-center gap-1.5 rounded-xl bg-sky-100 px-3 py-2 font-black text-sky-700 transition hover:bg-sky-200 dark:bg-sky-900/30 dark:text-sky-200">
                                                <flux:icon.clock class="h-4 w-4" /> Trayectoria
                                            </button>
                                            <a href="{{ route('misrutas.expedientes.show', $alumno['inscripcion_id']) }}"
                                                target="_blank"
                                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-100 px-3 py-2 font-black text-emerald-700 transition hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200">
                                                <flux:icon.folder-open class="h-4 w-4" /> Expediente
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-14 text-center text-slate-500">
                                        No se encontraron alumnos en este detalle.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($modalTrayectoria)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/80 p-3 backdrop-blur-sm sm:p-5"
            wire:key="modal-trayectoria-alumno">
            <div
                class="max-h-[94vh] w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
                <div
                    class="flex items-start justify-between gap-4 bg-gradient-to-r from-[#006492] to-[#88AC2E] px-5 py-4 text-white sm:px-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-white/70">Trayectoria individual
                        </p>
                        <h3 class="mt-1 text-xl font-black">{{ $historial['alumno']['nombre'] ?? 'Alumno' }}</h3>
                        <p class="mt-1 text-xs text-white/75">
                            Matrícula {{ $historial['alumno']['matricula'] ?? '—' }} · CURP
                            {{ $historial['alumno']['curp'] ?? '—' }}
                        </p>
                    </div>

                    <button type="button" wire:click="cerrarTrayectoria"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 transition hover:bg-white/20">
                        <flux:icon.x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="max-h-[calc(94vh-92px)] overflow-y-auto p-5 sm:p-6">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
                        <section>
                            <div class="mb-4 flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200">
                                    <flux:icon.academic-cap class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-900 dark:text-white">Historial por ciclo escolar
                                    </h4>
                                    <p class="text-xs text-slate-500">Se conserva la generación original y el estado de
                                        cada ciclo.</p>
                                </div>
                            </div>

                            <div class="relative space-y-4 border-l-2 border-sky-200 pl-6 dark:border-sky-900/50">
                                @forelse ($historial['trayectorias'] ?? [] as $etapa)
                                    <article
                                        class="relative rounded-2xl border p-4 {{ $this->claseTarjetaEtapa($etapa['categoria']) }}">
                                        <span
                                            class="absolute -left-[33px] top-5 h-4 w-4 rounded-full border-4 border-white bg-sky-600 shadow dark:border-neutral-900"></span>
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="text-lg font-black text-slate-900 dark:text-white">
                                                    {{ $etapa['ciclo'] }}</p>
                                                <p class="mt-1 text-sm font-bold text-slate-700 dark:text-slate-200">
                                                    {{ $etapa['grado'] }}° · Grupo {{ $etapa['grupo'] }}
                                                    @if ($etapa['semestre'] !== '—')
                                                        · Semestre {{ $etapa['semestre'] }}
                                                    @endif
                                                </p>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    Generación {{ $etapa['generacion'] }} · {{ $etapa['corte'] }}
                                                </p>
                                            </div>
                                            <span
                                                class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-700 shadow-sm dark:bg-neutral-900 dark:text-white">
                                                {{ $etapa['estado'] }}
                                            </span>
                                        </div>
                                        <div
                                            class="mt-3 grid gap-2 text-xs text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                                            <p><b>Inicio:</b> {{ $etapa['fecha_inicio'] }}</p>
                                            <p><b>Término:</b> {{ $etapa['fecha_fin'] }}</p>
                                            @if ($etapa['motivo'] !== '—')
                                                <p class="sm:col-span-2"><b>Motivo:</b> {{ $etapa['motivo'] }}</p>
                                            @endif
                                            @if ($etapa['observaciones'] !== '—')
                                                <p class="sm:col-span-2"><b>Observaciones:</b>
                                                    {{ $etapa['observaciones'] }}</p>
                                            @endif
                                            @if ($etapa['reconstruido'])
                                                <p class="sm:col-span-2 font-black text-amber-600">Registro
                                                    reconstruido desde datos históricos.</p>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <p
                                        class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-neutral-700">
                                        No hay etapas académicas registradas para este nivel.
                                    </p>
                                @endforelse
                            </div>
                        </section>

                        <section>
                            <div class="mb-4 flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                                    <flux:icon.clipboard-document-list class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-900 dark:text-white">Auditoría de movimientos</h4>
                                    <p class="text-xs text-slate-500">Altas, bajas, reingresos y correcciones
                                        administrativas.</p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @forelse ($historial['movimientos'] ?? [] as $movimiento)
                                    <article
                                        class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-black text-slate-900 dark:text-white">
                                                    {{ $movimiento['tipo'] }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $movimiento['fecha'] }} ·
                                                    {{ $movimiento['ciclo'] }}</p>
                                            </div>
                                            <span
                                                class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600 dark:bg-neutral-700 dark:text-slate-200">
                                                {{ $movimiento['usuario'] }}
                                            </span>
                                        </div>
                                        <p class="mt-3 text-xs leading-5 text-slate-600 dark:text-slate-300">
                                            <b>Motivo:</b> {{ $movimiento['motivo'] }}
                                        </p>
                                        @if ($movimiento['observaciones'] !== '—')
                                            <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">
                                                <b>Observaciones:</b> {{ $movimiento['observaciones'] }}
                                            </p>
                                        @endif
                                    </article>
                                @empty
                                    <p
                                        class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-neutral-700">
                                        No existen movimientos de auditoría para este alumno.
                                    </p>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
