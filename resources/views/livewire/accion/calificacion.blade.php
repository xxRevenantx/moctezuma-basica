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

    {{-- Encabezado --}}
    <div class="sticky top-0 z-10">
        <div
            class="rounded-2xl border border-neutral-200/60 dark:border-neutral-700/60 bg-gradient-to-r from-[#E4F6FF] to-[#F2EFFF] dark:from-[#0b1220] dark:to-[#121a2a] shadow-lg p-5">
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Captura de Calificaciones</h1>
            <p class="text-sm text-neutral-600 dark:text-neutral-300">
                Captura calificaciones por nivel, grado, grupo, y periodo.
            </p>
        </div>
    </div>

    {{-- Filtros --}}
    <div
        class="mt-6 rounded-2xl border bg-white dark:bg-neutral-900 border-neutral-200 dark:border-neutral-700 shadow-sm p-5">
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

            {{-- Nivel --}}
            <div>
                <label class="text-xs font-medium text-neutral-600 dark:text-neutral-300">Nivel</label>
                <select wire:model.live="nivel_id"
                    class="mt-1 w-full rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-sky-300">
                    <option value="">-- Selecciona un nivel --</option>
                    @foreach ($niveles as $n)
                        <option value="{{ $n->id }}">{{ $n->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Grado --}}
            <div>
                <label class="text-xs font-medium text-neutral-600 dark:text-neutral-300">Grado</label>
                <select wire:model.live="grado_id" @disabled(!$nivel_id)
                    class="mt-1 w-full rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-sky-300 disabled:opacity-60">
                    <option value="">-- Selecciona un grado --</option>
                    @foreach ($grados as $g)
                        <option value="{{ $g->id }}">{{ $g->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Semestre --}}
            @if ($this->esBachillerato)
                <div>
                    <label class="text-xs font-medium text-neutral-600 dark:text-neutral-300">Semestre</label>
                    <select wire:model.live="semestre_id" @disabled(!$grado_id)
                        class="mt-1 w-full rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-sky-300 disabled:opacity-60">
                        <option value="">-- Selecciona un semestre --</option>
                        @foreach ($semestres as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Grupo --}}
            <div>
                <label class="text-xs font-medium text-neutral-600 dark:text-neutral-300">Grupo</label>
                <select wire:model.live="grupo_id" @disabled(!$grado_id)
                    class="mt-1 w-full rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-sky-300 disabled:opacity-60">
                    <option value="">-- Selecciona un grupo --</option>
                    @foreach ($grupos as $gpo)
                        <option value="{{ $gpo->id }}">{{ $gpo->nombre }}</option>
                    @endforeach
                </select>
            </div>


            {{-- Generación --}}
            @if ($this->esBachillerato)
                <div>
                    <label class="text-xs font-medium text-neutral-600 dark:text-neutral-300">Generación</label>
                    <select wire:model.live="generacion_id"
                        class="mt-1 w-full rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-sky-300">
                        <option value="">-- Selecciona una generación --</option>
                        @foreach ($generaciones as $gen)
                            <option value="{{ $gen->id }}">{{ $gen->generacion }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Periodo --}}
            <div>
                <label class="text-xs font-medium text-neutral-600 dark:text-neutral-300">Periodo</label>
                <select wire:model.live="periodo_id"
                    class="mt-1 w-full rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-sky-300 disabled:opacity-60">
                    <option value="">-- Selecciona un periodo --</option>
                    @foreach ($periodos as $p)
                        <option value="{{ $p->id }}">{{ $p->etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Limpiar --}}
            <div class="mt-7">
                <button type="button" wire:click="limpiarFiltros"
                    class="items-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white px-4 py-2 text-sm font-semibold shadow hover:opacity-95">
                    Limpiar filtros
                </button>
            </div>
        </div>

        {{-- Estado cambios --}}
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

    {{-- Tabla --}}
    <div
        class="mt-6 rounded-2xl border bg-white dark:bg-neutral-900 border-neutral-200 dark:border-neutral-700 shadow-sm overflow-hidden">
        <div class="relative">

            {{-- Overlay de carga --}}
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

            {{-- Barra superior --}}
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
                    <thead class="bg-neutral-50 dark:bg-neutral-950/60">
                        <tr class="text-neutral-600 dark:text-neutral-300">
                            <th class="px-4 py-3 text-left font-semibold text-white">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-white">MATRÍCULA</th>
                            <th class="px-4 py-3 text-left font-semibold text-white">ALUMNO</th>

                            @foreach ($materias as $m)
                                <th class="px-4 py-2 text-center font-semibold text-white min-w-[160px]">
                                    <div class="text-white">
                                        {{ mb_strtoupper($m['materia']) }}
                                    </div>
                                    <div class="text-[11px] font-normal text-neutral-300 dark:text-neutral-400 mt-1">
                                        {{ $m['clave'] }}
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
                                            <input id="cal-{{ $insId }}-{{ $asigId }}" type="number"
                                                min="0" max="10" step="1"
                                                wire:model.lazy="calificaciones.{{ $insId }}.{{ $asigId }}"
                                                wire:change="marcarCambio"
                                                @keydown.enter.prevent="focusDown({{ $insId }}, {{ $asigId }})"
                                                class="w-24 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 px-3 py-1.5 text-center text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-sky-300"
                                                placeholder="0" />

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

            {{-- Acciones inferiores --}}
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
