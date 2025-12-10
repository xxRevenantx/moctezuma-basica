<div x-data="{
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `El ${nombre} grado se eliminará de forma permanente`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminar', id))
    }
}" class="space-y-5">
    <!-- Encabezado -->
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Grados</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Busca, edita o elimina grados educativos.
        </p>
    </div>

    <!-- Contenedor listado -->
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-800 shadow">
        <!-- Acabado superior -->
        <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-sky-400 to-indigo-600"></div>

        <!-- Toolbar -->
        <div class="p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col gap-3 lg:gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Buscador -->
                <div class="w-full sm:max-w-xl">
                    <label for="buscar-grado" class="sr-only">Buscar Grado</label>
                    <flux:input id="buscar-grado" type="text" wire:model.live="search"
                        placeholder="Buscar por nombre, CCT, director o supervisor…" icon="magnifying-glass"
                        class="w-full" />
                </div>

                <!-- Resumen -->
                <div class="flex items-center gap-3">
                    <div
                        class="hidden sm:flex items-center gap-2 rounded-lg border border-gray-200 dark:border-neutral-800 px-3 py-1.5 bg-gray-50 dark:bg-neutral-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            Resultados:
                            <strong>{{ method_exists($grados, 'total') ? $grados->total() : $grados->count() }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de resultados -->
        <div class="px-4 pb-4 sm:px-5 sm:pb-6 lg:px-6">
            <div class="relative">

                <!-- Loader -->
                <div wire:loading.delay wire:target="search, eliminar"
                    class="absolute inset-0 z-10 grid place-items-center rounded-xl bg-white/70 dark:bg-neutral-900/70 backdrop-blur"
                    aria-live="polite" aria-busy="true">
                    <div
                        class="flex items-center gap-3 rounded-xl bg-white dark:bg-neutral-900 px-4 py-3 ring-1 ring-gray-200 dark:ring-neutral-800 shadow">
                        <svg class="h-5 w-5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24"
                            fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-200">Cargando…</span>
                    </div>
                </div>

                <!-- Contenido -->
                <div class="transition filter duration-200" wire:loading.class="blur-sm" wire:target="search,eliminar">

                    @if ($niveles->isEmpty())
                        <div class="py-10">
                            <div class="mx-auto w-full max-w-md">
                                <div
                                    class="rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 p-6 bg-white/70 dark:bg-neutral-900/70 text-center">
                                    <div class="mb-1 text-base font-semibold text-gray-800 dark:text-gray-100">
                                        No hay niveles disponibles
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Ajusta tu búsqueda o registra un nuevo nivel.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- GRID DE CARDS POR NIVEL --}}
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($niveles as $key => $nivel)
                                <div
                                    class="relative rounded-2xl border border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-900/90 p-4 sm:p-5 shadow-sm flex flex-col gap-4">
                                    {{-- Header: índice + logo + nombre + CCT --}}
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-3 min-w-0">
                                            {{-- Logo --}}
                                            <div class="shrink-0">
                                                @if ($nivel->logo)
                                                    <img src="{{ asset('storage/logos/' . $nivel->logo) }}"
                                                        alt="Logo {{ $nivel->nombre }}"
                                                        class="h-12 w-12 object-contain rounded-lg ring-1 ring-gray-200/70 dark:ring-neutral-700/70 bg-white dark:bg-neutral-900">
                                                @else
                                                    <div
                                                        class="h-12 w-12 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 text-white text-xs font-semibold">
                                                        {{ Str::of($nivel->nombre)->substr(0, 2)->upper() }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="space-y-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span
                                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-900/30 text-[11px] font-semibold text-blue-700 dark:text-blue-300 ring-1 ring-blue-100 dark:ring-blue-800/70">
                                                        {{ $key + 1 + ($niveles->currentPage() - 1) * $niveles->perPage() }}
                                                    </span>
                                                    <h2
                                                        class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white truncate">
                                                        {{ $nivel->nombre ?: 'Nivel sin nombre' }}
                                                    </h2>
                                                </div>

                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    <span class="font-medium">C.C.T:</span>
                                                    {{ $nivel->cct ?: '---' }}
                                                </p>

                                                @if ($nivel->slug)
                                                    <p
                                                        class="text-[11px] text-gray-400 dark:text-gray-500 font-mono truncate">
                                                        /{{ $nivel->slug }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Color del nivel --}}
                                        <div class="flex flex-col items-end gap-1">
                                            <div class="h-6 w-12 rounded-md border border-white/40 shadow-sm"
                                                style="background-color: {{ $nivel->color ?? '#e5e7eb' }};">
                                            </div>
                                            <span class="text-[10px] text-gray-400 dark:text-gray-500">
                                                Color
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Director y Supervisor --}}
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-[13px] text-gray-700 dark:text-gray-200">
                                        <div class="space-y-1">
                                            <p
                                                class="font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                                                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                                Director
                                            </p>
                                            <div class="flex items-start gap-1.5">
                                                <span class="text-[11px] text-gray-400 dark:text-gray-500 mt-[2px]">
                                                    ■
                                                </span>
                                                <div class="space-y-0.5">
                                                    <p class="font-medium truncate">
                                                        @if ($nivel->director)
                                                            {{ $nivel->director->nombre ?? '' }}
                                                            {{ $nivel->director->apellido_paterno ?? '' }}
                                                            {{ $nivel->director->apellido_materno ?? '' }}
                                                        @else
                                                            ---
                                                        @endif
                                                    </p>
                                                    @if ($nivel->director)
                                                        <p
                                                            class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                                                            Zona: {{ $nivel->director->zona_escolar ?? '---' }} ·
                                                            Tel: {{ $nivel->director->telefono ?? '---' }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-1">
                                            <p
                                                class="font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                                                <span class="inline-block h-2 w-2 rounded-full bg-sky-500"></span>
                                                Supervisor
                                            </p>
                                            <div class="flex items-start gap-1.5">
                                                <span class="text-[11px] text-gray-400 dark:text-gray-500 mt-[2px]">
                                                    ■
                                                </span>
                                                <div class="space-y-0.5">
                                                    <p class="font-medium truncate">
                                                        @if ($nivel->supervisor)
                                                            {{ $nivel->supervisor->nombre ?? '' }}
                                                            {{ $nivel->supervisor->apellido_paterno ?? '' }}
                                                            {{ $nivel->supervisor->apellido_materno ?? '' }}
                                                        @else
                                                            ---
                                                        @endif
                                                    </p>
                                                    @if ($nivel->supervisor)
                                                        <p
                                                            class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                                                            Zona: {{ $nivel->supervisor->zona_escolar ?? '---' }} ·
                                                            Tel: {{ $nivel->supervisor->telefono ?? '---' }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 🎓 Grados asignados a este nivel (EDITABLES) --}}
                                    <div class="pt-2 border-t border-dashed border-gray-200 dark:border-neutral-700">
                                        <p
                                            class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">
                                            Grados asignados (clic para editar)
                                        </p>

                                        @if ($nivel->grados && $nivel->grados->count())
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ($nivel->grados as $grado)
                                                    <button type="button"
                                                        class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 dark:bg-indigo-900/40 px-2.5 py-0.5 text-[11px] font-medium text-indigo-700 dark:text-indigo-200 ring-1 ring-indigo-100 dark:ring-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-800/60 hover:shadow-sm transition"
                                                        @click="$dispatch('abrir-modal-editar');
                                                    Livewire.dispatch('editarModal', { id: {{ $grado->id }} });">
                                                        <span
                                                            class="h-1.5 w-1.5 rounded-full bg-indigo-500 dark:bg-indigo-300"></span>
                                                        {{ $grado->nombre ? $grado->nombre . '°' : 'Grado' }}
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-3 w-3 opacity-80" viewBox="0 0 20 20"
                                                            fill="currentColor" aria-hidden="true">
                                                            <path
                                                                d="M13.586 3.586a2 2 0 112.828 2.828l-7.5 7.5a2 2 0 01-.878.514l-3 0.75a1 1 0 01-1.213-1.213l.75-3a2 2 0 01.514-.878l7.5-7.5z" />
                                                        </svg>
                                                    </button>

                                                    <button type="button"
                                                        class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-300 ring-1 ring-red-100 dark:ring-red-800 hover:bg-red-100 dark:hover:bg-red-800/60 hover:shadow-sm transition"
                                                        @click="eliminar({{ $grado->id }}, '{{ $grado->nombre }}°')"
                                                        title="Eliminar grado">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                                            viewBox="0 0 20 20" fill="currentColor"
                                                            aria-hidden="true">
                                                            <path fill-rule="evenodd"
                                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-[11px] text-gray-400 dark:text-gray-500">
                                                Sin grados asignados a este nivel.
                                            </p>
                                        @endif
                                    </div>


                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Paginación -->
                <div class="mt-5">
                    {{ $niveles->links() }}
                </div>
            </div>
        </div>

        <!-- Modal editar GRADO -->
        <livewire:grado.editar-grados />
    </div>
</div>
