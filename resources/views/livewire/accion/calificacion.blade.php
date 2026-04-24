<div x-data="{
    insIds: @js(collect($inscripciones)->pluck('inscripcion_id')->values()->all()),
    asigIds: @js(collect($materias)->pluck('id')->values()->all()),

    move(insId, asigId, direction) {
        const rowIndex = this.insIds.indexOf(insId);
        const colIndex = this.asigIds.indexOf(asigId);

        if (rowIndex === -1 || colIndex === -1) return;

        let nextRowIndex = rowIndex;
        let nextColIndex = colIndex;

        if (direction === 'down') nextRowIndex++;
        if (direction === 'up') nextRowIndex--;
        if (direction === 'right') nextColIndex++;
        if (direction === 'left') nextColIndex--;

        if (nextRowIndex < 0 || nextRowIndex >= this.insIds.length) return;
        if (nextColIndex < 0 || nextColIndex >= this.asigIds.length) return;

        const nextInsId = this.insIds[nextRowIndex];
        const nextAsigId = this.asigIds[nextColIndex];

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
        class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 {{ $this->esBachillerato ? 'xl:grid-cols-6' : 'xl:grid-cols-4' }}">

        <div>
            <flux:select label="Generación" wire:model.live="generacion_id">
                <flux:select.option value="">-- Selecciona una generación --</flux:select.option>
                @foreach ($generaciones as $generacion)
                    <flux:select.option value="{{ $generacion->id }}">
                        {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div>
            <flux:select label="Grado" wire:model.live="grado_id" :disabled="!$generacion_id">
                <flux:select.option value="">-- Selecciona un grado --</flux:select.option>
                @foreach ($grados as $g)
                    <flux:select.option value="{{ $g->id }}">{{ $g->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if ($this->esBachillerato)
            <div>
                <flux:select label="Semestre" wire:model.live="semestre_id" :disabled="!$generacion_id || !$grado_id">
                    <flux:select.option value="">-- Selecciona un semestre --</flux:select.option>
                    @foreach ($semestres as $sem)
                        <flux:select.option value="{{ $sem->id }}">{{ $sem->numero }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select label="Grupo" wire:model.live="grupo_id"
                    :disabled="!$generacion_id || !$grado_id || !$semestre_id">
                    <flux:select.option value="">-- Selecciona un grupo --</flux:select.option>
                    @foreach ($grupos as $gpo)
                        <flux:select.option value="{{ $gpo->id }}">{{ $gpo->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select label="Parcial" wire:model.live="parcial_bachillerato_id"
                    :disabled="!$generacion_id || !$grado_id || !$semestre_id || !$grupo_id">
                    <flux:select.option value="">-- Selecciona un parcial --</flux:select.option>
                    @foreach ($parciales as $parcial)
                        <flux:select.option value="{{ $parcial->id }}">
                            {{ $parcial->descripcion }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @else
            <div>
                <flux:select label="Grupo" wire:model.live="grupo_id" :disabled="!$generacion_id || !$grado_id">
                    <flux:select.option value="">-- Selecciona un grupo --</flux:select.option>
                    @foreach ($grupos as $gpo)
                        <flux:select.option value="{{ $gpo->id }}">{{ $gpo->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif

        <div class="mt-7">
            <button type="button" wire:click="limpiarFiltros"
                class="items-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:opacity-95">
                Limpiar filtros
            </button>
        </div>
    </div>

    @if ($this->periodoSeleccionado)
        <div
            class="mt-6 overflow-hidden rounded-[28px] border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
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

                <div
                    class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 {{ $this->esBachillerato ? 'xl:grid-cols-6' : 'xl:grid-cols-4' }}">
                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
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
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-violet-500"></span>
                            Generación
                        </div>
                        <div class="mt-2 text-1xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            @php
                                $generacionSeleccionada = collect($generaciones)->firstWhere('id', $generacion_id);
                            @endphp
                            {{ $generacionSeleccionada ? $generacionSeleccionada->anio_ingreso . ' - ' . $generacionSeleccionada->anio_egreso : 'Sin generación' }}
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                            Periodo escolar
                        </div>
                        <div class="mt-2 text-1xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            {{ $this->nombrePeriodo }}
                        </div>
                    </div>

                    @if ($this->esBachillerato)
                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                <span class="h-2.5 w-2.5 rounded-full bg-violet-500"></span>
                                Parcial
                            </div>
                            <div class="mt-2 text-1xl font-extrabold text-neutral-900 dark:text-neutral-100">
                                {{ $this->periodoSeleccionado['parcial'] ?? 'Sin parcial' }}
                            </div>
                        </div>
                    @endif

                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
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
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
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
        class="mt-6 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="relative">

            <div wire:loading.flex
                wire:target="nivel_id,generacion_id,grado_id,grupo_id,semestre_id,parcial_bachillerato_id,busqueda,limpiarFiltros,guardarCalificaciones"
                class="absolute inset-0 z-30 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-950/60">
                <div
                    class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white px-5 py-4 shadow-lg dark:border-neutral-800 dark:bg-neutral-950">
                    <div
                        class="h-5 w-5 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-white">
                    </div>
                    <div class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">Cargando…</div>
                </div>
            </div>

            <div class="border-b border-neutral-200 p-4 dark:border-neutral-800">
                <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                            Buscar alumno o matrícula
                        </label>
                        <input type="text" wire:model.live.debounce.300ms="busqueda"
                            placeholder="Escribe nombre o matrícula..."
                            class="mt-1 w-full rounded-2xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-100">
                    </div>

                    <div class="flex justify-start md:justify-end">
                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-950/50">
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
                    <thead class="degradado sticky top-0 z-20 bg-neutral-50 dark:bg-neutral-950/60">
                        <tr class="text-neutral-600 dark:text-neutral-300">
                            <th class="px-4 py-3 text-left font-semibold text-white">#</th>
                            <th
                                class="sticky left-0 z-20 bg-sky-600 px-4 py-3 text-left font-semibold text-white min-w-[140px]">
                                MATRÍCULA
                            </th>
                            <th
                                class="sticky left-[140px] z-20 bg-sky-700 px-4 py-3 text-left font-semibold text-white min-w-[260px]">
                                ALUMNO
                            </th>

                            @foreach ($materias as $m)
                                <th class="min-w-[180px] px-4 py-2 text-center font-semibold text-white">
                                    <div class="text-white">
                                        {{ mb_strtoupper($m['materia']) }}
                                    </div>

                                    <div
                                        class="mt-1 text-[11px] leading-tight font-normal text-neutral-200 dark:text-neutral-300">
                                        {{ $m['profesor'] ?? 'SIN PROFESOR ASIGNADO' }}
                                    </div>
                                </th>
                            @endforeach

                            <th class="min-w-[110px] px-4 py-3 text-center font-semibold text-white">PROMEDIO</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @forelse ($inscripciones as $index => $fila)
                            @php($insId = (int) $fila['inscripcion_id'])

                            <tr class="hover:bg-neutral-50/70 dark:hover:bg-neutral-950/40">
                                <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                                    {{ $index + 1 }}
                                </td>

                                <td
                                    class="sticky left-0 z-10 min-w-[140px] bg-white px-4 py-3 font-medium text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100">
                                    {{ $fila['matricula'] }}
                                </td>

                                <td
                                    class="sticky left-[140px] z-10 min-w-[260px] bg-white px-4 py-3 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
                                    {{ $fila['alumno'] }}
                                </td>

                                @foreach ($materias as $m)
                                    @php($asigId = (int) $m['id'])

                                    <td class="px-4 py-3 text-center">
                                        <div class="mx-auto w-24">
                                            <input id="cal-{{ $insId }}-{{ $asigId }}" type="text"
                                                maxlength="2" inputmode="text"
                                                wire:model.lazy="calificaciones.{{ $insId }}.{{ $asigId }}"
                                                @focus="$event.target.select()"
                                                @keydown.enter.prevent="move({{ $insId }}, {{ $asigId }}, $event.shiftKey ? 'up' : 'down')"
                                                @keydown.tab.prevent="move({{ $insId }}, {{ $asigId }}, $event.shiftKey ? 'left' : 'right')"
                                                @keydown.arrow-down.prevent="move({{ $insId }}, {{ $asigId }}, 'down')"
                                                @keydown.arrow-up.prevent="move({{ $insId }}, {{ $asigId }}, 'up')"
                                                @keydown.arrow-right.prevent="move({{ $insId }}, {{ $asigId }}, 'right')"
                                                @keydown.arrow-left.prevent="move({{ $insId }}, {{ $asigId }}, 'left')"
                                                class="{{ $this->claseInputCalificacion($insId, $asigId) }}"
                                                placeholder="0-10" />

                                            @error('calificaciones.' . $insId . '.' . $asigId)
                                                <div class="mt-1 text-[11px] leading-tight text-red-600 dark:text-red-300">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </td>
                                @endforeach

                                <td class="px-4 py-3 text-center font-semibold text-neutral-900 dark:text-neutral-100">
                                    {{ $promedios[$insId] ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 4 + count($materias) }}" class="px-6 py-10">
                                    <div
                                        class="rounded-2xl border border-dashed border-neutral-200 p-6 text-center dark:border-neutral-800">
                                        <div class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">
                                            No hay datos para mostrar
                                        </div>
                                        <div class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $this->esBachillerato
                                                ? 'Selecciona grado, semestre, grupo y parcial para cargar alumnos y materias.'
                                                : 'Selecciona los filtros para cargar alumnos y materias.' }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-neutral-200 p-5 dark:border-neutral-800">
                @error('calificaciones')
                    <div
                        class="mb-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
                        {{ $message }}
                    </div>
                @enderror

                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="w-full md:w-2/3">
                        <div class="flex items-center justify-between text-xs text-neutral-600 dark:text-neutral-300">
                            <span>
                                Calificaciones introducidas: {{ $this->celdasCapturadas }} de {{ $this->totalCeldas }}
                                ({{ $this->porcentajeCaptura }}%)
                            </span>
                        </div>

                        <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-950">
                            <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-indigo-500"
                                style="width: {{ $this->porcentajeCaptura }}%"></div>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                class="rounded-full px-4 py-2 text-xs font-semibold {{ $this->claseEstadoCambios }}">
                                {{ $this->mensajeCambios }}
                            </span>

                            @if ($hayCambios)
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                    Revisa y guarda los cambios realizados.
                                </span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            @if ($this->mostrarBotonBitacora)
                                <button type="button" wire:click="abrirModalBitacora" wire:loading.attr="disabled"
                                    wire:target="abrirModalBitacora"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-fuchsia-200 bg-white px-5 py-3 text-sm font-semibold text-fuchsia-700 shadow-sm transition hover:bg-fuchsia-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-fuchsia-900/40 dark:bg-neutral-900 dark:text-fuchsia-300 dark:hover:bg-fuchsia-950/20">
                                    <span wire:loading.remove wire:target="abrirModalBitacora"
                                        class="inline-flex items-center gap-2">
                                        <flux:icon.clock class="h-4 w-4" />
                                        Bitácora
                                    </span>

                                    <span wire:loading wire:target="abrirModalBitacora"
                                        class="inline-flex items-center gap-2">
                                        <span
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-fuchsia-300 border-t-fuchsia-700 dark:border-fuchsia-700 dark:border-t-fuchsia-200"></span>
                                        Abriendo...
                                    </span>
                                </button>
                            @endif

                            <button type="button" wire:click="exportarCalificaciones" wire:loading.attr="disabled"
                                wire:target="exportarCalificaciones"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-white px-5 py-3 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-emerald-900/40 dark:bg-neutral-900 dark:text-emerald-300 dark:hover:bg-emerald-950/20">
                                <span wire:loading.remove wire:target="exportarCalificaciones"
                                    class="inline-flex items-center gap-2">
                                    <flux:icon.arrow-down-tray class="h-4 w-4" />
                                    Exportar
                                </span>

                                <span wire:loading wire:target="exportarCalificaciones"
                                    class="inline-flex items-center gap-2">
                                    <span
                                        class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-300 border-t-emerald-700 dark:border-emerald-700 dark:border-t-emerald-200"></span>
                                    Exportando...
                                </span>
                            </button>

                            <button type="button" @if (!$this->puedeExportarPdf) disabled @endif
                                x-on:click="window.open('{{ route(
                                    'misrutas.calificaciones.pdf',
                                    array_filter([
                                        'slug_nivel' => $slug_nivel,
                                        'generacion_id' => $generacion_id,
                                        'grado_id' => $grado_id,
                                        'grupo_id' => $grupo_id,
                                        'busqueda' => $busqueda ?: null,
                                        'semestre_id' => $this->esBachillerato ? $semestre_id : null,
                                        'parcial_bachillerato_id' => $this->esBachillerato ? $parcial_bachillerato_id : null,
                                    ]),
                                ) }}', '_blank')"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-white px-5 py-3 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-rose-900/40 dark:bg-neutral-900 dark:text-rose-300 dark:hover:bg-rose-950/20">
                                <flux:icon.document-arrow-down class="h-4 w-4" />
                                PDF
                            </button>

                            <button type="button" wire:click="guardarCalificaciones"
                                @if (!$this->puedeGuardar) disabled @endif class="{{ $this->claseGuardar }}">
                                Guardar calificaciones
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{ show: @entangle('mostrarModalBitacora').live }" x-cloak>
        <div x-show="show" x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
            @keydown.escape.window="$wire.cerrarModalBitacora()" @click.self="$wire.cerrarModalBitacora()">

            <div x-show="show" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 blur-sm"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100 blur-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100 blur-0"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95 blur-sm"
                class="relative w-full max-w-7xl overflow-hidden rounded-[28px] border border-white/10 bg-white shadow-2xl dark:bg-neutral-900">

                <div class="h-1.5 w-full bg-gradient-to-r from-fuchsia-500 via-sky-500 to-indigo-600"></div>

                <div class="space-y-5 p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-fuchsia-50 text-fuchsia-600 dark:bg-fuchsia-950/30 dark:text-fuchsia-300">
                                <flux:icon.clock class="h-6 w-6" />
                            </div>

                            <div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                                    Bitácora de calificaciones
                                </h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Historial de movimientos del contexto seleccionado.
                                </p>
                            </div>
                        </div>

                        <button type="button" wire:click="cerrarModalBitacora"
                            class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-neutral-800 dark:hover:text-slate-200">
                            <flux:icon.x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="relative min-h-[260px]">
                        {{-- Loader al montar el modal --}}
                        <div wire:loading.flex wire:target="abrirModalBitacora"
                            class="absolute inset-0 z-20 hidden items-center justify-center rounded-3xl border border-white/60 bg-white/75 backdrop-blur-md dark:border-white/10 dark:bg-neutral-900/75">
                            <div
                                class="flex min-w-[260px] flex-col items-center rounded-3xl border border-sky-100 bg-white/90 px-8 py-7 shadow-2xl shadow-sky-500/10 dark:border-sky-900/40 dark:bg-neutral-950/90">
                                <div class="relative mb-4 flex h-16 w-16 items-center justify-center">
                                    <div
                                        class="absolute inset-0 rounded-full border-4 border-sky-200 dark:border-sky-900/40">
                                    </div>
                                    <div
                                        class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-sky-500 border-r-indigo-500">
                                    </div>
                                    <div
                                        class="h-8 w-8 rounded-full bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 shadow-lg shadow-sky-500/30">
                                    </div>
                                </div>

                                <h3 class="text-base font-bold text-slate-800 dark:text-white">
                                    Cargando bitácora
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Preparando historial del contexto actual...
                                </p>
                            </div>
                        </div>

                        <div wire:loading.remove wire:target="abrirModalBitacora">
                            <livewire:accion.bitacora-calificaciones :nivel_id="$nivel_id" :grado_id="$grado_id"
                                :grupo_id="$grupo_id" :semestre_id="$semestre_id" :generacion_id="$generacion_id" :periodo_id="$periodo_id"
                                :esBachillerato="$this->esBachillerato" :key="'bitacora-calificaciones-' .
                                    md5(
                                        json_encode([
                                            'nivel' => $nivel_id,
                                            'grado' => $grado_id,
                                            'grupo' => $grupo_id,
                                            'semestre' => $semestre_id,
                                            'generacion' => $generacion_id,
                                            'periodo' => $periodo_id,
                                            'parcial' => $parcial_bachillerato_id,
                                            'modal' => $mostrarModalBitacora,
                                        ]),
                                    )" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <flux:button type="button" variant="ghost" wire:click="cerrarModalBitacora">
                            Cerrar
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
