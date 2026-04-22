<div class="space-y-5">
    {{-- Resumen --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 dark:border-sky-900/40 dark:bg-sky-950/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">
                Total
            </p>
            <p class="mt-1 text-2xl font-bold text-sky-900 dark:text-sky-100">
                {{ $this->totalMovimientos }}
            </p>
        </div>

        <div
            class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 dark:border-emerald-900/40 dark:bg-emerald-950/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                Altas
            </p>
            <p class="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                {{ $this->totalCreaciones }}
            </p>
        </div>

        <div
            class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 dark:border-amber-900/40 dark:bg-amber-950/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                Ediciones
            </p>
            <p class="mt-1 text-2xl font-bold text-amber-900 dark:text-amber-100">
                {{ $this->totalEdiciones }}
            </p>
        </div>

        <div
            class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 dark:border-rose-900/40 dark:bg-rose-950/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">
                Eliminaciones
            </p>
            <p class="mt-1 text-2xl font-bold text-rose-900 dark:text-rose-100">
                {{ $this->totalEliminaciones }}
            </p>
        </div>
    </div>

    {{-- Contexto --}}
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-neutral-950/50">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Grado</p>
            <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ optional(collect($grados)->firstWhere('id', $grado_id))->nombre ?? 'No seleccionado' }}
            </p>
        </div>

        @if ($esBachillerato)
            <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-neutral-950/50">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Semestre</p>
                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    {{ optional(collect($semestres)->firstWhere('id', $semestre_id))->numero ?? 'No seleccionado' }}
                </p>
            </div>
        @endif

        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-neutral-950/50">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Grupo</p>
            <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ optional(collect($grupos)->firstWhere('id', $grupo_id))->nombre ?? 'No seleccionado' }}
            </p>
        </div>

        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-neutral-950/50">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Periodo</p>
            <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ optional(collect($periodos)->firstWhere('id', $periodo_id))['etiqueta'] ?? 'No seleccionado' }}
            </p>
        </div>
    </div>

    {{-- Filtros --}}
    <div
        class="rounded-3xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <flux:label>Alumno</flux:label>
                <x-input wire:model.live.debounce.400ms="buscar_alumno" placeholder="Matrícula o nombre..." />
            </div>

            <div>
                <flux:label>Materia</flux:label>
                <x-input wire:model.live.debounce.400ms="buscar_materia" placeholder="Nombre de la materia..." />
            </div>

            <div>
                <flux:label>Usuario</flux:label>
                <x-input wire:model.live.debounce.400ms="buscar_usuario" placeholder="Nombre o correo..." />
            </div>

            <div>
                <flux:label>Acción</flux:label>
                <flux:select wire:model.live="accion">
                    <flux:select.option value="">Todas</flux:select.option>
                    <flux:select.option value="crear">Crear</flux:select.option>
                    <flux:select.option value="editar">Editar</flux:select.option>
                    <flux:select.option value="eliminar">Eliminar</flux:select.option>
                </flux:select>
            </div>

            <div>
                <flux:label>Búsqueda general</flux:label>
                <x-input wire:model.live.debounce.400ms="buscar_general" placeholder="Texto libre..." />
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="button" wire:click="limpiarFiltros"
                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:bg-neutral-700">
                Limpiar filtros
            </button>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="relative">
        <div wire:loading.flex
            wire:target="buscar_alumno,buscar_materia,buscar_usuario,buscar_general,accion,limpiarFiltros"
            class="absolute inset-0 z-20 hidden items-center justify-center rounded-3xl border border-white/60 bg-white/75 backdrop-blur-md dark:border-white/10 dark:bg-neutral-900/75">
            <div
                class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white px-5 py-4 shadow-lg dark:border-neutral-800 dark:bg-neutral-950">
                <div
                    class="h-5 w-5 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-white">
                </div>
                <div class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">Cargando bitácora…</div>
            </div>
        </div>

        <div class="hidden overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-800 xl:block">
            <div class="max-h-[55vh] overflow-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                    <thead class="sticky top-0 bg-slate-50/95 dark:bg-neutral-800/95">
                        <tr>
                            <th
                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Fecha</th>
                            <th
                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Usuario</th>
                            <th
                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Alumno</th>
                            <th
                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Materia</th>
                            <th
                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Acción</th>
                            <th
                                class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Antes</th>
                            <th
                                class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Ahora</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                        @forelse ($rows as $row)
                            @php
                                $ins = $row->inscripcion;
                                $alumno = trim(
                                    ($ins?->nombre ?? '') .
                                        ' ' .
                                        ($ins?->apellido_paterno ?? '') .
                                        ' ' .
                                        ($ins?->apellido_materno ?? ''),
                                );
                            @endphp

                            <tr class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/60">
                                <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                    {{ optional($row->created_at)->format('d/m/Y h:i A') }}
                                </td>

                                <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                    <div class="font-semibold text-slate-800 dark:text-slate-100">
                                        {{ $row->usuario?->name ?? 'Sin usuario' }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $row->usuario?->email ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                    <div class="font-semibold text-slate-800 dark:text-slate-100">
                                        {{ $alumno !== '' ? $alumno : 'Sin alumno' }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        Matrícula: {{ $ins?->matricula ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                    {{ $row->asignacionMateria?->materia ?? 'Sin materia' }}
                                </td>

                                <td class="px-4 py-4 align-top">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $this->claseAccion($row->accion) }}">
                                        {{ mb_strtoupper($row->accion) }}
                                    </span>
                                </td>

                                <td
                                    class="px-4 py-4 align-top text-center text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    {{ $row->calificacion_anterior !== null && $row->calificacion_anterior !== '' ? $row->calificacion_anterior : '—' }}
                                </td>

                                <td
                                    class="px-4 py-4 align-top text-center text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    {{ $row->calificacion_nueva !== null && $row->calificacion_nueva !== '' ? $row->calificacion_nueva : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    No se encontraron movimientos con los filtros actuales.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Móvil --}}
        <div class="space-y-4 xl:hidden">
            @forelse ($rows as $row)
                @php
                    $ins = $row->inscripcion;
                    $alumno = trim(
                        ($ins?->nombre ?? '') .
                            ' ' .
                            ($ins?->apellido_paterno ?? '') .
                            ' ' .
                            ($ins?->apellido_materno ?? ''),
                    );
                @endphp

                <div
                    class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-base font-bold text-slate-800 dark:text-white">
                                {{ $alumno !== '' ? $alumno : 'Sin alumno' }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{ $row->asignacionMateria?->materia ?? 'Sin materia' }}
                            </p>
                        </div>

                        <span
                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $this->claseAccion($row->accion) }}">
                            {{ mb_strtoupper($row->accion) }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                        <div><span class="font-semibold">Fecha:</span>
                            {{ optional($row->created_at)->format('d/m/Y h:i A') }}</div>
                        <div><span class="font-semibold">Usuario:</span> {{ $row->usuario?->name ?? 'Sin usuario' }}
                        </div>
                        <div><span class="font-semibold">Matrícula:</span> {{ $ins?->matricula ?? '—' }}</div>
                        <div><span class="font-semibold">Antes:</span>
                            {{ $row->calificacion_anterior !== null && $row->calificacion_anterior !== '' ? $row->calificacion_anterior : '—' }}
                        </div>
                        <div><span class="font-semibold">Ahora:</span>
                            {{ $row->calificacion_nueva !== null && $row->calificacion_nueva !== '' ? $row->calificacion_nueva : '—' }}
                        </div>
                    </div>
                </div>
            @empty
                <div
                    class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-400">
                    No se encontraron movimientos con los filtros actuales.
                </div>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $rows->links() }}
        </div>
    </div>
</div>
