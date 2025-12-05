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
        class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-800 shadow">
        <!-- Acabado superior -->
        <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-sky-400 to-indigo-600"></div>

        <!-- Toolbar -->
        <div class="p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col gap-3 lg:gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Buscador -->
                <div class="w-full sm:max-w-xl">
                    <label for="buscar-nivel" class="sr-only">Buscar Nivel</label>
                    <flux:input id="buscar-nivel" type="text" wire:model.live="search"
                        placeholder="Buscar por nombre, cargo, identificador o correo…" icon="magnifying-glass"
                        class="w-full" />
                </div>

                <!-- Resumen -->
                <div class="flex items-center gap-3">
                    <div
                        class="hidden sm:flex items-center gap-2 rounded-lg border border-gray-200 dark:border-neutral-800 px-3 py-1.5 bg-gray-50 dark:bg-neutral-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
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

                <!-- ⬇️ Contenido que se desenfoca mientras se busca/carga -->
                <div class="transition filter duration-200" wire:loading.class="blur-sm" wire:target="search,eliminar">

                    <!-- Tabla (desktop) -->
                    <div
                        class="hidden md:block overflow-hidden rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-800">
                        <div class="overflow-x-auto max-h-[65vh]">
                            <table class="min-w-full text-sm">
                                <thead
                                    class="sticky top-0 z-10 bg-gray-50/95 dark:bg-neutral-900 backdrop-blur text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-neutral-800">
                                    <tr>
                                        <th class="px-4 py-3 text-center font-semibold">#</th>
                                        <th class="px-4 py-3 text-left font-semibold">Logo</th>
                                        <th class="px-4 py-3 text-left font-semibold">Color</th>
                                        <th class="px-4 py-3 text-left font-semibold">Nivel</th>
                                        <th class="px-4 py-3 text-left font-semibold">C.C.T.</th>
                                        <th class="px-4 py-3 text-center font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">
                                    @if ($niveles->isEmpty())
                                        <tr>
                                            <td colspan="9"
                                                class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                                <div class="mx-auto w-full max-w-md">
                                                    <div
                                                        class="rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 p-6">
                                                        <div class="mb-1 text-base font-semibold">
                                                            No hay niveles disponibles
                                                        </div>
                                                        <p class="text-sm">Ajusta tu búsqueda.</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($niveles as $key => $nivel)
                                            <!-- Fila principal -->
                                            <tr
                                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-neutral-800/50">
                                                <!-- # + botón desplegar -->
                                                <td class="px-4 py-3 text-center text-gray-800 dark:text-gray-200">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button type="button"
                                                            class="inline-flex items-center justify-center h-7 w-7 rounded-full
                                                                   bg-gradient-to-r from-indigo-600 to-violet-600
                                                                   hover:from-indigo-700 hover:to-violet-700
                                                                   text-white text-xs shadow-sm
                                                                   focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500
                                                                   dark:focus:ring-offset-neutral-900
                                                                   transition-transform duration-150 hover:scale-105 active:scale-95"
                                                            @click="openRow === {{ $nivel->id }} ? openRow = null : openRow = {{ $nivel->id }}"
                                                            :aria-expanded="openRow === {{ $nivel->id }}"
                                                            aria-label="Ver más datos del nivel">
                                                            <!-- + -->
                                                            <svg x-show="openRow !== {{ $nivel->id }}"
                                                                xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                                                viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd"
                                                                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            <!-- x -->
                                                            <svg x-show="openRow === {{ $nivel->id }}" x-cloak
                                                                xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                                                viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd"
                                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                        <span
                                                            class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                            {{ $key + 1 }}
                                                        </span>
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    @if ($nivel->logo)
                                                        <img src="{{ asset('storage/' . $nivel->logo) }}"
                                                            alt="Logo {{ $nivel->nombre }}"
                                                            class="h-10 w-10 object-contain rounded">
                                                    @else
                                                        <img src="./penacho.jpg" alt="Logo {{ $nivel->nombre }}"
                                                            class="h-10 w-10 object-contain rounded">
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    <div class="h-6 w-12 rounded"
                                                        style="background-color: {{ $nivel->color ?? '#cccccc' }};">
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    {{ $nivel->nombre ?: '---' }}
                                                </td>
                                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                                                    {{ $nivel->cct ?: '---' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <flux:button variant="primary"
                                                            class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                                            @click="$dispatch('abrir-modal-editar');
                                                                Livewire.dispatch('editarModal', { id: {{ $nivel->id }} });
                                                            ">
                                                            <flux:icon.square-pen class="w-3.5 h-3.5" />
                                                            <!-- ícono -->
                                                        </flux:button>
                                                        {{--
                                                        <flux:button variant="danger"
                                                            class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white p-1"
                                                            @click="eliminar({{ $nivel->id }}, '{{ $nivel->nombre }}')">
                                                            <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                                        </flux:button> --}}
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Fila de detalles desplegable con animación -->
                                            <tr x-show="openRow === {{ $nivel->id }}" x-cloak
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 -translate-y-1"
                                                x-transition:enter-end="opacity-100 translate-y-0"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100 translate-y-0"
                                                x-transition:leave-end="opacity-0 -translate-y-1"
                                                class="bg-gray-50/80 dark:bg-neutral-900/80">
                                                <td colspan="10" class="px-6 py-4">
                                                    <div
                                                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs sm:text-sm text-gray-700 dark:text-gray-200">
                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Director
                                                            </p>
                                                            <p class="font-mono text-[11px] sm:text-xs">
                                                                {{ $nivel->director->nombre ?? '---' }}
                                                            </p>
                                                        </div>
                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Supervisor
                                                            </p>
                                                            <p class="font-mono text-[11px] sm:text-xs">
                                                                {{ $nivel->director->nombre ?? '---' }}
                                                            </p>
                                                        </div>


                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tarjetas (mobile) -->
                    <div class="md:hidden space-y-3">
                        @if ($niveles->isEmpty())
                            <div
                                class="rounded-xl border border-dashed border-gray-300 dark:border-neutral-700 p-6 text-center">
                                <div class="mb-1 font-semibold text-gray-700 dark:text-gray-200">
                                    No hay niveles disponibles
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Ajusta tu búsqueda o importa datos.
                                </p>
                            </div>
                        @else
                            @foreach ($niveles as $key => $nivel)
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span>#{{ $key + 1 }}</span>
                                                <span class="font-medium">{{ $nivel->titulo }}</span>
                                                @if ($nivel->status == 'true')
                                                    <span
                                                        class="inline-flex items-center gap-1 rounded-full border border-emerald-300/60 bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-700/50">
                                                        Activo
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center gap-1 rounded-full border border-rose-300/60 bg-rose-50 px-2 py-0.5 text-[10px] font-medium text-rose-700 dark:bg-rose-900/20 dark:text-rose-300 dark:border-rose-700/50">
                                                        Inactivo
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="mt-1 font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $nivel->nombre }} {{ $nivel->apellido_paterno }}
                                                {{ $nivel->apellido_materno }}
                                            </div>
                                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                                <span class="font-medium">Cargo:</span> {{ $nivel->cargo }} ·
                                                <span class="font-medium">ID:</span> {{ $nivel->identificador }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $nivel->telefono }} · {{ $nivel->correo }}
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-center gap-2">

                                            <flux:button variant="primary"
                                                class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                                @click="$dispatch('abrir-modal-editar');
                                                                Livewire.dispatch('editarModal', { id: {{ $nivel->id }} });
                                                            ">
                                                <flux:icon.square-pen class="w-3.5 h-3.5" />
                                                <!-- ícono -->
                                            </flux:button>

                                            <flux:button variant="danger"
                                                class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white p-1"
                                                @click="eliminar({{ $nivel->id }}, '{{ $nivel->nombre }}')">
                                                <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                            </flux:button>

                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
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
