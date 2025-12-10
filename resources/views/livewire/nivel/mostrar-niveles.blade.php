<div x-data="{
    openRow: null,
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `El nivel ${nombre} se eliminará de forma permanente`,
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
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Niveles</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Busca, edita o elimina niveles educativos.
        </p>
    </div>

    <!-- Contenedor listado -->
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-lg">
        <!-- Acabado superior -->
        <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-sky-400 to-indigo-600"></div>

        <!-- Toolbar -->
        <div class="p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col gap-3 lg:gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Buscador -->
                <div class="w-full sm:max-w-xl">
                    <label for="buscar-nivel" class="sr-only">Buscar Nivel</label>
                    <flux:input id="buscar-nivel" type="text" wire:model.live="search"
                        placeholder="Buscar por nombre, CCT, director o supervisor…" icon="magnifying-glass"
                        class="w-full" />
                </div>

                <!-- Resumen -->
                <div class="flex items-center gap-3">
                    <div
                        class="hidden sm:flex items-center gap-2 rounded-lg border border-gray-200 dark:border-neutral-800 px-3 py-1.5 bg-gray-50 dark:bg-neutral-800/80">
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.15)]"></span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            Resultados:
                            <strong>{{ method_exists($niveles, 'total') ? $niveles->total() : $niveles->count() }}</strong>
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
                        class="flex items-center gap-3 rounded-xl bg-white dark:bg-neutral-900 px-4 py-3 ring-1 ring-gray-200 dark:ring-neutral-800 shadow-lg">
                        <svg class="h-5 w-5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24"
                            fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-200">Cargando…</span>
                    </div>
                </div>

                <!-- Contenido que se desenfoca mientras se busca/carga -->
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
                        {{-- GRID DE CARDS (desktop + mobile) --}}
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($niveles as $key => $nivel)
                                @php
                                    $colorBase = $nivel->color ?: '#0ea5e9'; // fallback sky-500
                                @endphp

                                <div class="relative group rounded-2xl border border-gray-200/70 dark:border-neutral-800/80 bg-white/95 dark:bg-neutral-950/90 p-4 sm:p-5 shadow-sm flex flex-col gap-4 overflow-hidden backdrop-blur-sm transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-2xl hover:border-gray-300/80 dark:hover:border-neutral-700/80"
                                    style="--nivel-color: {{ $colorBase }};">

                                    {{-- Capa de degradado dinámico --}}
                                    <div class="pointer-events-none absolute inset-0 opacity-80 mix-blend-normal">
                                        <div class="absolute -top-10 -left-10 h-40 w-40 rounded-full blur-3xl"
                                            style="background: radial-gradient(circle at center,
                                                color-mix(in srgb, var(--nivel-color) 80%, #ffffff 20%) 0,
                                                transparent 60%);">
                                        </div>
                                        <div class="absolute -bottom-12 -right-10 h-44 w-44 rounded-full blur-3xl"
                                            style="background: radial-gradient(circle at center,
                                                color-mix(in srgb, var(--nivel-color) 70%, #020617 30%) 0,
                                                transparent 65%);">
                                        </div>
                                        <div class="absolute inset-x-0 top-0 h-1.5"
                                            style="background-image: linear-gradient(to right,
                                                var(--nivel-color),
                                                color-mix(in srgb, var(--nivel-color) 70%, #38bdf8 30%),
                                                color-mix(in srgb, var(--nivel-color) 60%, #6366f1 40%));">
                                        </div>
                                    </div>

                                    {{-- Contenido principal --}}
                                    <div class="relative z-10 flex flex-col gap-4">
                                        {{-- Header: índice + logo + nombre + CCT --}}
                                        <div class="flex items-start justify-between gap-3 pt-1.5">
                                            <div class="flex items-start gap-3 min-w-0">
                                                {{-- Logo --}}
                                                <div class="shrink-0">
                                                    @if ($nivel->logo)
                                                        <div
                                                            class="h-12 w-12 rounded-xl bg-white/80 dark:bg-neutral-900/80 ring-1 ring-white/60 dark:ring-neutral-800/80 shadow-sm flex items-center justify-center overflow-hidden">
                                                            <img src="{{ asset('storage/logos/' . $nivel->logo) }}"
                                                                alt="Logo {{ $nivel->nombre }}"
                                                                class="h-10 w-10 object-contain">
                                                        </div>
                                                    @else
                                                        <div class="h-12 w-12 flex items-center justify-center rounded-xl text-white text-xs font-semibold shadow-md ring-1 ring-white/40"
                                                            style="background-image: linear-gradient(135deg,
                                                                color-mix(in srgb, var(--nivel-color) 92%, #22c55e 8%),
                                                                color-mix(in srgb, var(--nivel-color) 75%, #0f172a 25%));">
                                                            {{ Str::of($nivel->nombre)->substr(0, 2)->upper() }}
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="space-y-1 min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span
                                                            class="inline-flex h-6 px-2 items-center justify-center rounded-full bg-white/80 dark:bg-neutral-900/90 text-[11px] font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-200/80 dark:ring-neutral-700/80 shadow-sm">
                                                            #{{ $key + 1 + ($niveles->currentPage() - 1) * $niveles->perPage() }}
                                                        </span>
                                                        <h2
                                                            class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white truncate">
                                                            {{ $nivel->nombre ?: 'Nivel sin nombre' }}
                                                        </h2>
                                                    </div>

                                                    <p class="text-xs text-gray-700 dark:text-gray-300 truncate">
                                                        <span class="font-medium">C.C.T:</span>
                                                        {{ $nivel->cct ?: '---' }}
                                                    </p>

                                                    @if ($nivel->slug)
                                                        <p
                                                            class="text-[11px] text-gray-500 dark:text-gray-400 font-mono truncate">
                                                            /{{ $nivel->slug }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Color del nivel --}}
                                            <div class="flex flex-col items-end gap-1">
                                                <div
                                                    class="h-6 w-16 rounded-full ring-1 ring-white/70 dark:ring-neutral-800/80 shadow-md overflow-hidden">
                                                    <div class="h-full w-full"
                                                        style="background-image: linear-gradient(to right,
                                                            var(--nivel-color),
                                                            color-mix(in srgb, var(--nivel-color) 70%, #38bdf8 30%));">
                                                    </div>
                                                </div>
                                                <span class="text-[10px] text-gray-400 dark:text-gray-500">
                                                    Color nivel
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Director y Supervisor --}}
                                        <div
                                            class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-[13px] text-gray-900 dark:text-gray-100">
                                            <div class="space-y-1">
                                                <p
                                                    class="font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                                                    <span
                                                        class="inline-block h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.18)]"></span>
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
                                                                class="text-[11px] text-gray-700 dark:text-gray-300 truncate">
                                                                Zona: {{ $nivel->supervisor->zona_escolar ?? '---' }} ·
                                                                Tel: {{ $nivel->director->telefono ?? '---' }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="space-y-1">
                                                <p
                                                    class="font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                                                    <span
                                                        class="inline-block h-2 w-2 rounded-full bg-sky-500 shadow-[0_0_0_4px_rgba(56,189,248,0.2)]"></span>
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
                                                                class="text-[11px] text-gray-700 dark:text-gray-300 truncate">
                                                                Zona: {{ $nivel->supervisor->zona_escolar ?? '---' }} ·
                                                                Tel: {{ $nivel->supervisor->telefono ?? '---' }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Acciones --}}
                                        <div class="flex items-center justify-between pt-1.5">
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                                ID interno: {{ $nivel->id }}
                                            </p>
                                            <div class="flex items-center gap-2">
                                                <flux:button variant="primary"
                                                    class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 text-xs shadow-sm hover:shadow-md"
                                                    @click="$dispatch('abrir-modal-editar');
                                                        Livewire.dispatch('editarModal', { id: {{ $nivel->id }} });
                                                    ">
                                                    <flux:icon.square-pen class="w-3.5 h-3.5" />
                                                </flux:button>

                                                <flux:button variant="danger"
                                                    class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 text-xs shadow-sm hover:shadow-md"
                                                    @click="eliminar({{ $nivel->id }}, '{{ $nivel->nombre }}')">
                                                    <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                                </flux:button>
                                            </div>
                                        </div>
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

        <!-- Modal editar -->
        <livewire:nivel.editar-nivel />
    </div>
</div>
