<div class="space-y-5" x-data="{
    open: new Set(),
    toggle(key) { this.open.has(key) ? this.open.delete(key) : this.open.add(key) },
    isOpen(key) { return this.open.has(key) },
    openAll() { document.querySelectorAll('[data-nivel]').forEach(el => this.open.add(el.dataset.nivel)) },
    closeAll() { this.open = new Set() },
}">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Personal asignado por nivel
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Nivel (collapse) → Grupo (secciones) → Personal (filas)
        </p>
    </div>

    <!-- Toolbar -->
    <div
        class="rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow overflow-hidden">
        <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

        <div class="p-4 sm:p-5 flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-blue-50 dark:bg-blue-900/30 grid place-items-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M16 7a4 4 0 01.88 7.903A5 5 0 1115 7h1z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Listado</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Agrupación por nivel y grupo</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <!-- Search -->
                <div class="w-full sm:w-[380px]">
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                fill="currentColor">
                                <path
                                    d="M10 4a6 6 0 104.472 10.03l4.249 4.249 1.414-1.414-4.249-4.249A6 6 0 0010 4zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" />
                            </svg>
                        </span>

                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Buscar por nombre, especialidad, grado, grupo o nivel…"
                            class="w-full rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900
                                   pl-10 pr-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400" />
                    </div>
                </div>

                <!-- Open/close all -->
                <div class="flex gap-2">
                    <button type="button" @click="openAll()"
                        class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5
                               border border-gray-200 dark:border-neutral-800
                               bg-white dark:bg-neutral-900
                               text-gray-700 dark:text-gray-200
                               hover:bg-gray-50 dark:hover:bg-neutral-800/60
                               focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        Abrir todo
                    </button>

                    <button type="button" @click="closeAll()"
                        class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5
                               border border-gray-200 dark:border-neutral-800
                               bg-white dark:bg-neutral-900
                               text-gray-700 dark:text-gray-200
                               hover:bg-gray-50 dark:hover:bg-neutral-800/60
                               focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        Cerrar todo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Niveles -->
    <div class="space-y-4">
        @forelse($personalNivel as $nivelNombre => $gruposDelNivel)
            @php
                $nivelKey = \Illuminate\Support\Str::slug($nivelNombre) . '-' . crc32($nivelNombre);
                $totalNivel = $gruposDelNivel->flatten(1)->count();
                $numGrupos = $gruposDelNivel->count();
            @endphp

            <div class="rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow overflow-hidden"
                data-nivel="{{ $nivelKey }}">
                <!-- Header nivel -->
                <button type="button" @click="toggle('{{ $nivelKey }}')"
                    :aria-expanded="isOpen('{{ $nivelKey }}')"
                    class="w-full text-left p-4 sm:p-5 flex items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-neutral-800/60 transition">
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="h-11 w-11 rounded-2xl bg-indigo-50 dark:bg-indigo-900/25 grid place-items-center shrink-0">
                            <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M3 7h18M6 7v14h12V7M9 7V4h6v3" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $nivelNombre }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $numGrupos }} grupo(s) · {{ $totalNivel }} asignación(es)
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200">
                            {{ $totalNivel }}
                        </span>

                        <span class="transition-transform duration-200"
                            :class="isOpen('{{ $nivelKey }}') ? 'rotate-180' : 'rotate-0'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 dark:text-gray-300"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 15.5l-6-6h12l-6 6z" />
                            </svg>
                        </span>
                    </div>
                </button>

                <!-- Contenido nivel -->
                <div x-show="isOpen('{{ $nivelKey }}')" x-cloak
                    x-transition:enter="transition ease-out duration-250"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="border-t border-gray-200 dark:border-neutral-800">

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-neutral-800/70">
                                <tr class="text-left text-gray-600 dark:text-gray-200">
                                    <th class="px-4 py-3 font-semibold">#</th>
                                    <th class="px-4 py-3 font-semibold">Orden</th>
                                    <th class="px-4 py-3 font-semibold">Personal</th>
                                    <th class="px-4 py-3 font-semibold">Función</th>
                                    <th class="px-4 py-3 font-semibold">Grado</th>
                                    <th class="px-4 py-3 font-semibold">Grupo</th>
                                    <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">
                                @foreach ($gruposDelNivel as $grupoNombre => $items)
                                    @php $countGrupo = $items->count(); @endphp

                                    <!-- Separador GRUPO -->
                                    <tr class="bg-emerald-50/70 dark:bg-emerald-900/15">
                                        <td colspan="7" class="px-4 py-2">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600 text-white">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            viewBox="0 0 24 24" fill="currentColor">
                                                            <path
                                                                d="M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                                        </svg>
                                                    </span>
                                                    <p class="font-semibold text-gray-900 dark:text-white">
                                                        Grupo: {{ $grupoNombre }}
                                                    </p>
                                                </div>

                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                                                    {{ $countGrupo }} persona(s)
                                                </span>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Filas del PERSONAL -->
                                    @foreach ($items as $idx => $row)
                                        @php
                                            $p = $row->persona;

                                            $nombreCompleto = trim(
                                                ($p->nombre ?? '') .
                                                    ' ' .
                                                    ($p->apellido_paterno ?? '') .
                                                    ' ' .
                                                    ($p->apellido_materno ?? ''),
                                            );

                                            $grado = $row->grado?->nombre ?? '—';
                                            $grupo = $row->grupo?->nombre ?? '—';

                                            // rolesPersona viene del eager load: persona.personaRoles.rolePersona
                                            $roles = $p?->personaRoles ?? collect();
                                        @endphp

                                        <tr class="hover:bg-gray-50 dark:hover:bg-neutral-800/50">
                                            <!-- # -->
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                {{ $idx + 1 }}
                                            </td>

                                            <!-- Orden + controles -->
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    {{-- <span
                                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                                 bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-200">
                                                        {{ $row->orden ?? '—' }}
                                                    </span> --}}

                                                    <div class="flex items-center gap-1">
                                                        <button type="button"
                                                            wire:click="subir({{ $row->id }})"
                                                            class="inline-flex items-center justify-center h-8 w-8 rounded-xl
                                                                   border border-gray-200 dark:border-neutral-700
                                                                   hover:bg-gray-50 dark:hover:bg-neutral-800/60
                                                                   focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                                            ▲
                                                        </button>

                                                        <button type="button"
                                                            wire:click="bajar({{ $row->id }})"
                                                            class="inline-flex items-center justify-center h-8 w-8 rounded-xl
                                                                   border border-gray-200 dark:border-neutral-700
                                                                   hover:bg-gray-50 dark:hover:bg-neutral-800/60
                                                                   focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                                            ▼
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Personal -->
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-gray-900 dark:text-white">
                                                    {{ $nombreCompleto ?: 'Sin nombre' }}
                                                </div>
                                            </td>

                                            <!-- Función -->
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                <div class="flex flex-wrap gap-1.5">
                                                    @forelse($roles as $pr)
                                                        <span
                                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                                     bg-gray-100 text-indigo-700 dark:bg-neutral-800 dark:text-gray-200">
                                                            {{ $pr->rolePersona?->nombre ?? '—' }}
                                                        </span>
                                                    @empty
                                                        <span class="text-xs text-gray-400">—</span>
                                                    @endforelse
                                                </div>
                                            </td>

                                            <!-- Grado -->
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                {{ $grado }}
                                            </td>

                                            <!-- Grupo -->
                                            <td class="px-4 py-3">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                             bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-200">
                                                    {{ $grupo }}
                                                </span>
                                            </td>

                                            <!-- Acciones -->
                                            <td class="px-4 py-3 text-right">
                                                {{-- Solo reordenamiento; no invento otras acciones --}}
                                                <span class="text-xs text-gray-400">—</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach

                                @if ($gruposDelNivel->flatten(1)->isEmpty())
                                    <tr>
                                        <td colspan="7" class="px-6 py-10 text-center">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Sin
                                                asignaciones</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Este nivel no tiene personal asignado.
                                            </p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            @empty
                <div
                    class="rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-8 text-center">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Sin resultados</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        No hay personal asignado o tu búsqueda no coincide.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
