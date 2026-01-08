<div x-data="{
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `El ciclo escolar ${nombre} se eliminará de forma permanente`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminar', id))
    }
}" class="w-full mx-auto p-4 sm:p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                Ciclos escolares
            </h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                Consulta y busca ciclos por año o por rango (ej. 2025-2026).
            </p>
        </div>

        {{-- Toolbar --}}
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <div class="relative w-full sm:w-80">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-zinc-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <path d="M10.8 19a8.2 8.2 0 1 1 0-16.4 8.2 8.2 0 0 1 0 16.4Z" stroke="currentColor"
                            stroke-width="2" />
                    </svg>
                </span>

                <flux:input class="pl-9" placeholder="Buscar (ej. 2025, 2025-2026)"
                    wire:model.live.debounce.350ms="search" />
            </div>

            <div class="w-full sm:w-auto">
                <flux:select wire:model.live="perPage">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </flux:select>
            </div>
        </div>
    </div>

    {{-- Card container --}}
    <div
        class="relative overflow-hidden rounded-2xl border border-zinc-200/70 dark:border-zinc-800 bg-white dark:bg-zinc-950 shadow-sm">
        <div class="h-1.5 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

        {{-- Loader overlay --}}
        <div wire:loading.flex wire:target="search,perPage"
            class="absolute inset-0 z-10 items-center justify-center bg-white/60 dark:bg-zinc-950/60 backdrop-blur-sm">
            <div
                class="flex items-center gap-3 rounded-2xl border border-zinc-200/70 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/60 px-4 py-3 shadow-sm">
                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="text-sm text-zinc-700 dark:text-zinc-200">Cargando…</span>
            </div>
        </div>

        {{-- Desktop table --}}
        <div class="hidden md:block">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/40">
                        <tr class="text-left">
                            <th class="px-5 py-3 text-xs font-semibold tracking-wide text-zinc-600 dark:text-zinc-300">
                                Ciclo
                            </th>
                            <th class="px-5 py-3 text-xs font-semibold tracking-wide text-zinc-600 dark:text-zinc-300">
                                Inicio
                            </th>
                            <th class="px-5 py-3 text-xs font-semibold tracking-wide text-zinc-600 dark:text-zinc-300">
                                Fin
                            </th>
                            <th class="px-5 py-3 text-xs font-semibold tracking-wide text-zinc-600 dark:text-zinc-300">
                                Creado
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-zinc-600 dark:text-zinc-300 text-right">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($ciclos as $ciclo)
                            @php
                                $diff = (int) $ciclo->fin_anio - (int) $ciclo->inicio_anio;
                                $badge = $diff === 1 ? 'Correcto' : ($diff === 0 ? 'Mismo año' : 'Atípico');
                            @endphp

                            <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-900/30 transition">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-2xl border border-zinc-200/70 dark:border-zinc-800 bg-gradient-to-br from-sky-500/10 via-blue-600/10 to-indigo-600/10 flex items-center justify-center">
                                            <svg class="h-5 w-5 text-zinc-800 dark:text-zinc-100" viewBox="0 0 24 24"
                                                fill="none">
                                                <path d="M7 3v3M17 3v3" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" />
                                                <path d="M4 8h16" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" />
                                                <path
                                                    d="M6 6h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2Z"
                                                    stroke="currentColor" stroke-width="2" />
                                            </svg>
                                        </div>

                                        <div>
                                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ $ciclo->inicio_anio }} — {{ $ciclo->fin_anio }}
                                            </div>

                                            <div class="mt-1 inline-flex items-center gap-2">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs
                                                    border border-zinc-200/70 dark:border-zinc-800
                                                    bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200">
                                                    {{ $badge }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm text-zinc-700 dark:text-zinc-200">
                                    {{ $ciclo->inicio_anio }}
                                </td>

                                <td class="px-5 py-4 text-sm text-zinc-700 dark:text-zinc-200">
                                    {{ $ciclo->fin_anio }}
                                </td>

                                <td class="px-5 py-4 text-sm text-zinc-700 dark:text-zinc-200">
                                    <span class="text-zinc-500 dark:text-zinc-400">
                                        {{ $ciclo->created_at?->format('d/m/Y') }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center gap-2">
                                        <flux:button variant="primary"
                                            class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 text-xs shadow-sm hover:shadow-md"
                                            @click="$dispatch('abrir-modal-editar');
                                                        Livewire.dispatch('editarModal', { id: {{ $ciclo->id }} });
                                                    ">
                                            <flux:icon.square-pen class="w-3.5 h-3.5" />
                                        </flux:button>

                                        <flux:button variant="danger"
                                            class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 text-xs shadow-sm hover:shadow-md"
                                            @click="eliminar({{ $ciclo->id }}, '{{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}')">
                                            <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10">
                                    <div
                                        class="rounded-2xl border border-dashed border-zinc-300/70 dark:border-zinc-700 p-8 text-center">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            No hay ciclos escolares
                                        </div>
                                        <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                            Intenta ajustar la búsqueda o crea un nuevo ciclo.
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden p-4 space-y-3">
            @forelse ($ciclos as $ciclo)
                @php
                    $diff = (int) $ciclo->fin_anio - (int) $ciclo->inicio_anio;
                    $badge = $diff === 1 ? 'Correcto' : ($diff === 0 ? 'Mismo año' : 'Atípico');
                @endphp

                <div
                    class="rounded-2xl border border-zinc-200/70 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $ciclo->inicio_anio }} — {{ $ciclo->fin_anio }}
                            </div>
                            <div
                                class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs
                                border border-zinc-200/70 dark:border-zinc-800
                                bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200">
                                {{ $badge }}
                            </div>
                        </div>

                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $ciclo->created_at?->format('d/m/Y') }}
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div
                            class="rounded-xl border border-zinc-200/70 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/30 p-3">
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">Inicio</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $ciclo->inicio_anio }}</div>
                        </div>
                        <div
                            class="rounded-xl border border-zinc-200/70 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/30 p-3">
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">Fin</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $ciclo->fin_anio }}</div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-xl px-3 py-1.5 text-xs font-medium
                                   border border-zinc-200/70 dark:border-zinc-800
                                   bg-white dark:bg-zinc-950 hover:bg-zinc-50 dark:hover:bg-zinc-900 transition">
                            Editar
                        </button>

                        <button type="button"
                            class="inline-flex items-center justify-center rounded-xl px-3 py-1.5 text-xs font-medium
                                   border border-rose-200/70 dark:border-rose-900/40
                                   bg-rose-50/60 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300
                                   hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                            Eliminar
                        </button>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300/70 dark:border-zinc-700 p-8 text-center">
                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                        No hay ciclos escolares
                    </div>
                    <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                        Crea un ciclo escolar para comenzar.
                    </div>
                </div>
            @endforelse
        </div>

        <livewire:ciclo-escolar.editar-ciclo-escolar />

        {{-- Pagination --}}
        <div class="px-4 sm:px-6 py-4 border-t border-zinc-100 dark:border-zinc-800">
            {{ $ciclos->links() }}
        </div>
    </div>
</div>
