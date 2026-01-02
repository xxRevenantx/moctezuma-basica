<div class="w-full space-y-6">

    <!-- HERO / HEADER -->
    <div
        class="w-full relative overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="absolute inset-0">
            <div
                class="absolute -top-32 -right-32 h-72 w-72 rounded-full bg-gradient-to-br from-sky-500/25 via-blue-600/20 to-indigo-600/25 blur-3xl">
            </div>
            <div
                class="absolute -bottom-32 -left-32 h-72 w-72 rounded-full bg-gradient-to-tr from-violet-500/20 via-fuchsia-500/15 to-rose-500/20 blur-3xl">
            </div>
        </div>

        <div class="relative p-6 sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900 dark:text-white">
                        Matrícula — {{ $nivel->nombre ?? 'Nivel' }}
                    </h1>
                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        Selecciona una generación para ver alumnos. Después puedes filtrar por grupo y buscar.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    <span
                        class="inline-flex items-center gap-2 rounded-2xl border border-neutral-200 bg-white/70 px-3 py-2 text-sm text-neutral-700 shadow-sm backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/40 dark:text-neutral-200">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        Total: <b>{{ $total }}</b>
                    </span>

                    <span
                        class="inline-flex items-center gap-2 rounded-2xl border border-neutral-200 bg-white/70 px-3 py-2 text-sm text-neutral-700 shadow-sm backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/40 dark:text-neutral-200">
                        H: <b>{{ $hombres }}</b> • M: <b>{{ $mujeres }}</b>
                    </span>

                    @if (($this->selectedCount ?? 0) > 0)
                        <span
                            class="inline-flex items-center gap-2 rounded-2xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm text-indigo-700 shadow-sm dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200">
                            Seleccionados: <b>{{ $this->selectedCount }}</b>
                        </span>
                    @endif
                </div>
            </div>

            <!-- TOOLBAR -->
            <div class="mt-6 grid w-full grid-cols-1 gap-3 lg:grid-cols-12">

                <!-- Search -->
                <div class="lg:col-span-6">
                    <label class="sr-only" for="search">Buscar</label>

                    <div class="relative w-full">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M12.9 14.32a8 8 0 1 1 1.414-1.414l3.387 3.387a1 1 0 0 1-1.414 1.414l-3.387-3.387ZM14 8a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>

                        <input id="search" type="text" wire:model.live.debounce.350ms="search"
                            :disabled="@js(!$generacion_id)" placeholder="Buscar por matrícula, CURP o nombre…"
                            class="w-full rounded-2xl border border-neutral-200 bg-white pl-10 pr-3 py-3 text-sm shadow-sm outline-none
                                   focus:ring-2 focus:ring-sky-500/30 dark:border-neutral-800 dark:bg-neutral-950 dark:text-white
                                   disabled:opacity-60 disabled:cursor-not-allowed" />
                    </div>

                    @if (!$generacion_id)
                        <p class="mt-1 text-[11px] text-neutral-500 dark:text-neutral-400">
                            Selecciona una generación para habilitar búsqueda.
                        </p>
                    @endif
                </div>

                <!-- Generación -->
                <div class="lg:col-span-3">
                    <label class="sr-only" for="generacion_id">Generación</label>
                    <select id="generacion_id" wire:model.live="generacion_id"
                        class="w-full rounded-2xl border border-neutral-200 bg-white px-3 py-3 text-sm shadow-sm outline-none
                               focus:ring-2 focus:ring-indigo-500/25 dark:border-neutral-800 dark:bg-neutral-950 dark:text-white">
                        <option value="">— Selecciona una generación —</option>

                        @foreach ($generaciones as $gen)
                            <option value="{{ $gen->id }}">
                                {{ $gen->label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Grupo -->
                <div class="lg:col-span-2">
                    <label class="sr-only" for="grupo_id">Grupo</label>
                    <select id="grupo_id" wire:model.live="grupo_id" :disabled="@js(!$generacion_id)"
                        class="w-full rounded-2xl border border-neutral-200 bg-white px-3 py-3 text-sm shadow-sm outline-none
                               focus:ring-2 focus:ring-indigo-500/25 dark:border-neutral-800 dark:bg-neutral-950 dark:text-white
                               disabled:opacity-60 disabled:cursor-not-allowed">
                        <option value="">— Grupo —</option>
                        @foreach ($grupos as $gr)
                            <option value="{{ $gr->id }}">{{ $gr->nombre }}</option>
                        @endforeach
                    </select>

                    <p class="mt-1 text-[11px] text-neutral-500 dark:text-neutral-400">
                        @if (!$generacion_id)
                            Selecciona una generación para habilitar grupo.
                        @else
                            @if ($grupos->count() === 0)
                                No hay grupos para la generación seleccionada.
                            @endif
                        @endif
                    </p>
                </div>

                <!-- Acciones -->
                <div class="lg:col-span-1 flex gap-2 lg:justify-end">
                    <flux:button variant="primary" wire:click="clearFilters">Limpiar</flux:button>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD GRADO(S) GENERACIÓN --}}
    @if ($generacion_id)
        @php
            $genSel = $generaciones->firstWhere('id', (int) $generacion_id);
            $genText = $genSel ? $genSel->anio_ingreso . ' - ' . $genSel->anio_egreso : '—';
        @endphp

        <div
            class="w-full relative overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="absolute inset-0">
                <div
                    class="absolute -top-28 -right-28 h-64 w-64 rounded-full bg-gradient-to-br from-indigo-500/18 via-sky-500/12 to-violet-500/18 blur-3xl">
                </div>
                <div
                    class="absolute -bottom-28 -left-28 h-64 w-64 rounded-full bg-gradient-to-tr from-emerald-500/10 via-teal-500/10 to-sky-500/10 blur-3xl">
                </div>
            </div>

            <div class="relative p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div
                            class="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            Generación seleccionada
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-2xl border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-sm font-semibold text-neutral-800 dark:border-neutral-700 dark:bg-neutral-950/40 dark:text-neutral-200">
                                {{ $genText }}
                            </span>

                            <span
                                class="inline-flex items-center gap-2 rounded-2xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200">
                                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                Grado:
                                <b>{{ $this->gradoGeneracionLabel ?: '—' }}</b>
                            </span>
                        </div>

                        @if (!$this->gradoGeneracionLabel)
                            <div class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                                No se encontró grado asociado (revisa que existan grupos para esta generación con
                                grado_id).
                            </div>
                        @endif
                    </div>

                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                        Se calcula desde <b>grupos</b> (generación → grupos → grado).
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- TABLA -->
    <div
        class="w-full relative overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

        <!-- Loader overlay -->
        <div wire:loading.flex
            wire:target="search, generacion_id, grupo_id, clearFilters, gotoPage, previousPage, nextPage, selectPage, selected, resetSelection, aplicarCambiarGrado"
            class="absolute inset-0 z-10 items-center justify-center bg-white/60 backdrop-blur-sm dark:bg-neutral-950/50">
            <div
                class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white px-4 py-3 shadow dark:border-neutral-800 dark:bg-neutral-900">
                <svg class="h-5 w-5 animate-spin text-neutral-600 dark:text-neutral-200"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
                <span class="text-sm text-neutral-700 dark:text-neutral-200">Cargando…</span>
            </div>
        </div>

        <div class="w-full p-5 sm:p-6">

            @if (!$generacion_id)
                <div
                    class="w-full rounded-2xl border border-dashed border-neutral-300 p-10 text-center text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-300">
                    <div class="mx-auto max-w-md space-y-2">
                        <div class="text-base font-semibold text-neutral-900 dark:text-white">
                            Selecciona una generación para ver alumnos
                        </div>
                        <div class="text-sm text-neutral-600 dark:text-neutral-400">
                            Después podrás filtrar por grupo, buscar y seleccionar filas.
                        </div>
                    </div>
                </div>
            @else
                <!-- Desktop -->
                <div class="hidden md:block w-full overflow-x-auto">
                    <table class="w-full min-w-[980px] text-sm">
                        <thead class="text-left">
                            <tr class="border-b border-neutral-200 dark:border-neutral-800">
                                <th class="py-3 pr-4 w-10">
                                    <input type="checkbox" wire:model.live="selectPage"
                                        class="h-4 w-4 rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500/30 dark:border-neutral-700 dark:bg-neutral-950">
                                </th>
                                <th class="py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-200">#</th>
                                <th class="py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-200">Matrícula
                                </th>
                                <th class="py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-200">Alumno</th>
                                <th class="py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-200">CURP</th>
                                <th class="py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-200">Género</th>
                                <th class="py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-200">Grado</th>
                                <th class="py-3 pr-4 font-semibold text-neutral-700 dark:text-neutral-200">Grupo</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                            @forelse ($rows as $r)
                                <tr class="hover:bg-neutral-50/70 dark:hover:bg-neutral-950/40">
                                    <td class="py-3 pr-4">
                                        <input type="checkbox" value="{{ $r->id }}"
                                            wire:model.live="selected"
                                            class="h-4 w-4 rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500/30 dark:border-neutral-700 dark:bg-neutral-950">
                                    </td>

                                    <td class="py-3 pr-4 text-neutral-500 dark:text-neutral-400">
                                        {{ $loop->iteration + $rows->perPage() * ($rows->currentPage() - 1) }}
                                    </td>

                                    <td class="py-3 pr-4">
                                        <span
                                            class="inline-flex items-center rounded-xl border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200">
                                            {{ $r->matricula }}
                                        </span>
                                    </td>

                                    <td class="py-3 pr-4">
                                        <div class="font-semibold text-neutral-900 dark:text-white">
                                            {{ trim($r->nombre . ' ' . $r->apellido_paterno . ' ' . $r->apellido_materno) }}
                                        </div>
                                        <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                            Folio: {{ $r->folio ?? '—' }}
                                        </div>
                                    </td>

                                    <td class="py-3 pr-4 font-mono text-xs text-neutral-700 dark:text-neutral-200">
                                        {{ $r->curp }}
                                    </td>

                                    <td class="py-3 pr-4">
                                        @php $g = $r->genero; @endphp
                                        <span
                                            class="inline-flex items-center rounded-xl px-2.5 py-1 text-xs font-semibold border
                                            {{ $g === 'H'
                                                ? 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:border-sky-500/30'
                                                : ($g === 'M'
                                                    ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:border-rose-500/30'
                                                    : 'bg-neutral-50 text-neutral-700 border-neutral-200 dark:bg-neutral-500/10 dark:text-neutral-200 dark:border-neutral-500/30') }}">
                                            {{ $g === 'H' ? 'Hombre' : ($g === 'M' ? 'Mujer' : '—') }}
                                        </span>
                                    </td>

                                    <td class="py-3 pr-4">
                                        <span
                                            class="inline-flex items-center rounded-xl border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold text-neutral-700 dark:border-neutral-700 dark:bg-neutral-950/40 dark:text-neutral-200">
                                            {{ $r->grado->nombre ?? '—' }}
                                        </span>
                                    </td>

                                    <td class="py-3 pr-4">
                                        <span
                                            class="inline-flex items-center rounded-xl border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold text-neutral-700 dark:border-neutral-700 dark:bg-neutral-950/40 dark:text-neutral-200">
                                            {{ $r->grupo->nombre ?? '—' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-10">
                                        <div
                                            class="rounded-2xl border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-300">
                                            No hay alumnos con los filtros actuales.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Mobile -->
                <div class="md:hidden space-y-3">
                    @forelse ($rows as $r)
                        <div
                            class="w-full rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <input type="checkbox" value="{{ $r->id }}" wire:model.live="selected"
                                        class="mt-1 h-4 w-4 rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500/30 dark:border-neutral-700 dark:bg-neutral-950">

                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-bold text-neutral-900 dark:text-white">
                                            {{ trim($r->nombre . ' ' . $r->apellido_paterno . ' ' . $r->apellido_materno) }}
                                        </div>
                                        <div
                                            class="mt-1 text-xs text-neutral-500 dark:text-neutral-400 font-mono break-all">
                                            {{ $r->curp }}
                                        </div>
                                    </div>
                                </div>

                                <span
                                    class="shrink-0 inline-flex items-center rounded-xl border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200">
                                    {{ $r->matricula }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div
                                    class="rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 dark:border-neutral-800 dark:bg-neutral-900">
                                    <div class="text-neutral-500 dark:text-neutral-400">Género</div>
                                    <div class="font-semibold text-neutral-800 dark:text-neutral-200">
                                        {{ $r->genero === 'H' ? 'Hombre' : ($r->genero === 'M' ? 'Mujer' : '—') }}
                                    </div>
                                </div>

                                <div
                                    class="rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 dark:border-neutral-800 dark:bg-neutral-900">
                                    <div class="text-neutral-500 dark:text-neutral-400">Folio</div>
                                    <div class="font-semibold text-neutral-800 dark:text-neutral-200">
                                        {{ $r->folio ?? '—' }}
                                    </div>
                                </div>

                                <div
                                    class="rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 dark:border-neutral-800 dark:bg-neutral-900">
                                    <div class="text-neutral-500 dark:text-neutral-400">Grado</div>
                                    <div class="font-semibold text-neutral-800 dark:text-neutral-200">
                                        {{ $r->grado->nombre ?? '—' }}
                                    </div>
                                </div>

                                <div
                                    class="rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 dark:border-neutral-800 dark:bg-neutral-900">
                                    <div class="text-neutral-500 dark:text-neutral-400">Grupo</div>
                                    <div class="font-semibold text-neutral-800 dark:text-neutral-200">
                                        {{ $r->grupo->nombre ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div
                            class="w-full rounded-2xl border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-300">
                            No hay alumnos con los filtros actuales.
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                <div class="mt-5 w-full">
                    {{ $rows->links() }}
                </div>

                @if (($this->selectedCount ?? 0) > 0)
                    <div class="mt-5 w-full">
                        <div
                            class="relative overflow-hidden rounded-3xl border border-indigo-200 bg-indigo-50/60 p-4 shadow-sm
                   dark:border-indigo-500/25 dark:bg-indigo-500/10">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-indigo-900 dark:text-indigo-100">
                                        Cambiar grado a alumnos seleccionados
                                    </div>
                                    <div class="text-xs text-indigo-700/90 dark:text-indigo-200/90">
                                        Seleccionados: <b>{{ $this->selectedCount }}</b> — (se limpiará el grupo al
                                        cambiar de grado)
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="rounded-2xl border border-indigo-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700
                               hover:bg-indigo-50 dark:border-indigo-500/25 dark:bg-neutral-950 dark:text-indigo-200 dark:hover:bg-neutral-900"
                                        wire:click="resetSelection">
                                        Limpiar selección
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-12">
                                <div class="sm:col-span-8">
                                    <label class="text-sm font-semibold text-indigo-900 dark:text-indigo-100">
                                        Nuevo grado ({{ $nivel->nombre ?? 'Nivel' }})
                                    </label>

                                    <select wire:model.live="nuevo_grado_id"
                                        class="mt-2 w-full rounded-2xl border border-indigo-200 bg-white px-3 py-3 text-sm shadow-sm outline-none
                               focus:ring-2 focus:ring-indigo-500/25
                               dark:border-indigo-500/25 dark:bg-neutral-950 dark:text-white">
                                        <option value="">— Selecciona —</option>
                                        @foreach ($grados as $g)
                                            <option value="{{ $g->id }}">{{ $g->nombre }}</option>
                                        @endforeach
                                    </select>

                                    @error('nuevo_grado_id')
                                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="sm:col-span-4 flex items-end">
                                    <button type="button"
                                        class="w-full rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow
                               hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed"
                                        wire:click="aplicarCambiarGrado" :disabled="@js(($this->selectedCount ?? 0) === 0)">
                                        Aplicar cambio
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            @endif

        </div>
    </div>
</div>
