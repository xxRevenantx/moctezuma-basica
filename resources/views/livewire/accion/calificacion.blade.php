<div x-data="{
    insIds: @js(collect($inscripciones)->pluck('inscripcion_id')->values()->all()),
    asigIds: @js(collect($materias)->pluck('id')->values()->all()),

    focusDown(insId, asigId) {
        const rowIndex = this.insIds.indexOf(insId);
        const colIndex = this.asigIds.indexOf(asigId);

        if (rowIndex === -1 || colIndex === -1) return;

        const nextRowIndex = rowIndex + 1;
        if (nextRowIndex >= this.insIds.length) return;

        const nextInsId = this.insIds[nextRowIndex];
        const nextAsigId = this.asigIds[colIndex];

        const el = document.getElementById(`cal-${nextInsId}-${nextAsigId}`);
        if (el) {
            el.focus();
            if (typeof el.select === 'function') el.select();
        }
    }
}" class="w-full">

    {{-- ITERA NIVELES --}}
    <div class="overflow-hidden">
        <div>
            <div class="-mx-1 overflow-x-auto pb-1">
                <div class="flex min-w-max items-center justify-center gap-2 px-1">
                    @foreach ($niveles as $item)
                        @php
                            $activo = $slug_nivel === $item->slug;
                        @endphp

                        <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'calificaciones']) }}"
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
    </div>

    {{-- Filtros --}}
    <div
        class="mt-6 rounded-2xl border bg-white dark:bg-neutral-900 border-neutral-200 dark:border-neutral-700 shadow-sm p-5">
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

            <div>
                <flux:select label="Nivel" wire:model.live="nivel_id">
                    <flux:select.option value="">-- Selecciona un nivel --</flux:select.option>
                    @foreach ($niveles as $n)
                        <flux:select.option value="{{ $n->id }}">{{ $n->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select label="Grado" wire:model.live="grado_id">
                    <flux:select.option value="">-- Selecciona un grado --</flux:select.option>
                    @foreach ($grados as $g)
                        <flux:select.option value="{{ $g->id }}">{{ $g->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if ($this->esBachillerato)
                <div>
                    <flux:select label="Semestre" wire:model.live="semestre_id">
                        <flux:select.option value="">-- Selecciona un semestre --</flux:select.option>
                        @foreach ($semestres as $s)
                            <flux:select.option value="{{ $s->id }}">{{ $s->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <div>
                <flux:select label="Grupo" wire:model.live="grupo_id">
                    <flux:select.option value="">-- Selecciona un grupo --</flux:select.option>
                    @foreach ($grupos as $gpo)
                        <flux:select.option value="{{ $gpo->id }}">{{ $gpo->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if ($this->esBachillerato)
                <div>
                    <flux:select label="Generación" wire:model.live="generacion_id">
                        <flux:select.option value="">-- Selecciona una generación --</flux:select.option>
                        @foreach ($generaciones as $gen)
                            <flux:select.option value="{{ $gen->id }}">{{ $gen->generacion }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <div>
                <flux:select label="Periodo" wire:model.live="periodo_id">
                    <flux:select.option value="">-- Selecciona un periodo --</flux:select.option>
                    @foreach ($periodos as $p)
                        <flux:select.option value="{{ $p['id'] }}">{{ $p['etiqueta'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="mt-7">
                <button type="button" wire:click="limpiarFiltros"
                    class="items-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white px-4 py-2 text-sm font-semibold shadow hover:opacity-95">
                    Limpiar filtros
                </button>
            </div>
        </div>

        <div
            class="mt-5 rounded-2xl border shadow-sm px-4 py-3 flex items-center justify-between gap-4
            {{ $hayCambios
                ? 'border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/30'
                : 'border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-950/40' }}">
            <div class="flex items-center gap-3">
                <span class="relative flex h-3 w-3">
                    @if ($hayCambios)
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-600"></span>
                    @else
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-600"></span>
                    @endif
                </span>

                <div class="min-w-0">
                    <div class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        {{ $hayCambios ? 'Hay cambios por guardar' : 'No hay cambios por guardar' }}
                    </div>
                    <div class="text-xs text-neutral-600 dark:text-neutral-300">
                        {{ $hayCambios ? 'Guarda para aplicar los cambios.' : 'Todo está al día.' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($this->periodoSeleccionado)
        <div
            class="mt-6 rounded-[28px] border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 shadow-sm overflow-hidden">
            <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500"></div>

            <div class="p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7V3m8 4V3m-9 8h10m-13 9h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v11a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-2xl tracking-tight text-neutral-900 dark:text-neutral-100">
                                {{ $this->esBachillerato ? 'PERIODO SEMESTRAL' : 'PERIODO ESCOLAR' }}
                            </h3>

                            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Información vigente del periodo académico seleccionado.
                            </p>
                        </div>
                    </div>

                    <div>
                        <span
                            class="inline-flex items-center rounded-full px-4 py-1.5 text-sm font-semibold {{ $this->claseEstadoPeriodo }}">
                            {{ $this->estadoPeriodo }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div
                        class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50/70 dark:bg-neutral-950/50 p-5 shadow-sm">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                            Ciclo escolar
                        </div>
                        <div class="mt-2 text-1xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            {{ $this->periodoSeleccionado['ciclo_escolar'] ?? 'Sin ciclo' }}
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50/70 dark:bg-neutral-950/50 p-5 shadow-sm">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                            Periodo escolar
                        </div>
                        <div class="mt-2 text-1xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            {{ $this->nombrePeriodo }}
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50/70 dark:bg-neutral-950/50 p-5 shadow-sm">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            Inicio de periodo
                        </div>
                        <div class="mt-2 text-1xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            {{ \Carbon\Carbon::parse($this->periodoSeleccionado['fecha_inicio'])->format('d/m/Y') }}
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50/70 dark:bg-neutral-950/50 p-5 shadow-sm">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                            Término de periodo
                        </div>
                        <div class="mt-2 text-1xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            {{ \Carbon\Carbon::parse($this->periodoSeleccionado['fecha_fin'])->format('d/m/Y') }}
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <div
                        class="mb-2 flex items-center justify-between text-xs font-medium text-neutral-500 dark:text-neutral-400">
                        <span>{{ \Carbon\Carbon::parse($this->periodoSeleccionado['fecha_inicio'])->format('d/m/Y') }}</span>
                        <span>{{ \Carbon\Carbon::parse($this->periodoSeleccionado['fecha_fin'])->format('d/m/Y') }}</span>
                    </div>

                    <div class="h-3 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500 transition-all duration-500"
                            style="width: {{ $this->porcentajePeriodo }}%">
                        </div>
                    </div>

                    <div class="mt-2 text-right text-sm font-medium text-neutral-600 dark:text-neutral-300">
                        Avance {{ $this->porcentajePeriodo }}%
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabla --}}
    <div
        class="mt-6 rounded-2xl border bg-white dark:bg-neutral-900 border-neutral-200 dark:border-neutral-700 shadow-sm overflow-hidden">
        <div class="relative">

            <div wire:loading.flex
                wire:target="nivel_id,grado_id,grupo_id,periodo_id,generacion_id,semestre_id,busqueda,limpiarFiltros,guardarCalificaciones"
                class="absolute inset-0 z-10 items-center justify-center bg-white/70 dark:bg-neutral-950/60 backdrop-blur-sm">
                <div
                    class="rounded-2xl bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 shadow-lg px-5 py-4 flex items-center gap-3">
                    <div
                        class="h-5 w-5 rounded-full border-2 border-neutral-300 dark:border-neutral-700 border-t-neutral-900 dark:border-t-white animate-spin">
                    </div>
                    <div class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">Cargando…</div>
                </div>
            </div>

            <div class="border-b border-neutral-200 dark:border-neutral-800 p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                            Buscar alumno o matrícula
                        </label>
                        <input type="text" wire:model.live.debounce.300ms="busqueda"
                            placeholder="Escribe nombre o matrícula..."
                            class="mt-1 w-full rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-sky-300">
                    </div>

                    <div class="flex justify-start md:justify-end">
                        <div
                            class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-950/50 px-4 py-3 text-sm">
                            <div class="font-semibold text-neutral-900 dark:text-neutral-100">
                                {{ count($inscripciones) }} alumno(s)
                            </div>
                            <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ count($materias) }} materia(s) calificable(s)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-neutral-50 dark:bg-neutral-950/60 degradado">
                        <tr class="text-neutral-600 dark:text-neutral-300">
                            <th class="px-4 py-3 text-left font-semibold text-white">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-white">MATRÍCULA</th>
                            <th class="px-4 py-3 text-left font-semibold text-white">ALUMNO</th>

                            @foreach ($materias as $m)
                                <th class="px-4 py-2 text-center font-semibold text-white min-w-[180px]">
                                    <div class="text-white">
                                        {{ mb_strtoupper($m['materia']) }}
                                    </div>

                                    <div
                                        class="mt-1 text-[11px] font-normal text-neutral-200 dark:text-neutral-300 leading-tight">
                                        {{ $m['profesor'] ?? 'SIN PROFESOR ASIGNADO' }}
                                    </div>
                                </th>
                            @endforeach

                            <th class="px-4 py-3 text-center font-semibold text-white min-w-[110px]">PROMEDIO</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @forelse ($inscripciones as $index => $fila)
                            @php($insId = (int) $fila['inscripcion_id'])

                            <tr class="hover:bg-neutral-50/70 dark:hover:bg-neutral-950/40">
                                <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                                    {{ $index + 1 }}
                                </td>

                                <td class="px-4 py-3 font-medium text-neutral-900 dark:text-neutral-100">
                                    {{ $fila['matricula'] }}
                                </td>

                                <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                                    {{ $fila['alumno'] }}
                                </td>

                                @foreach ($materias as $m)
                                    @php($asigId = (int) $m['id'])

                                    <td class="px-4 py-3 text-center">
                                        <div class="w-24 mx-auto">
                                            <input id="cal-{{ $insId }}-{{ $asigId }}" type="text"
                                                maxlength="2" inputmode="text"
                                                wire:model.lazy="calificaciones.{{ $insId }}.{{ $asigId }}"
                                                wire:change="marcarCambio"
                                                @keydown.enter.prevent="focusDown({{ $insId }}, {{ $asigId }})"
                                                class="w-24 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 px-3 py-1.5 text-center text-sm uppercase text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-sky-300"
                                                placeholder="0-10" />

                                            @error('calificaciones.' . $insId . '.' . $asigId)
                                                <div class="mt-1 text-[11px] text-red-600 dark:text-red-300 leading-tight">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </td>
                                @endforeach

                                <td class="px-4 py-3 font-semibold text-neutral-900 dark:text-neutral-100 text-center">
                                    {{ $this->promedioFila($insId) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 4 + count($materias) }}" class="px-6 py-10">
                                    <div
                                        class="rounded-2xl border border-dashed border-neutral-200 dark:border-neutral-800 p-6 text-center">
                                        <div class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">
                                            No hay datos para mostrar
                                        </div>
                                        <div class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            Selecciona los filtros para cargar alumnos y materias.
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-neutral-200 dark:border-neutral-800 p-5">
                @error('calificaciones')
                    <div
                        class="mb-3 rounded-2xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                        {{ $message }}
                    </div>
                @enderror

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="w-full md:w-2/3">
                        <div class="flex items-center justify-between text-xs text-neutral-600 dark:text-neutral-300">
                            <span>
                                Calificaciones introducidas: {{ $this->celdasCapturadas }} de {{ $this->totalCeldas }}
                                ({{ $this->porcentajeCaptura }}%)
                            </span>
                        </div>

                        <div class="mt-2 h-3 w-full rounded-full bg-neutral-100 dark:bg-neutral-950 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-indigo-500"
                                style="width: {{ $this->porcentajeCaptura }}%"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button type="button" wire:click="guardarCalificaciones"
                            {{ $this->puedeGuardar ? '' : 'disabled' }} class="{{ $this->claseGuardar }}">
                            Guardar calificaciones
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
