<div x-data="{
    openRow: null,
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `El grado ${nombre} se eliminará de forma permanente`,
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
                    <flux:input id="buscar-grado" type="text" wire:model.live="search" placeholder="Buscar…"
                        icon="magnifying-glass" class="w-full" />
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
                                        <th class="px-4 py-3 text-left font-semibold">Grado</th>
                                        <th class="px-4 py-3 text-left font-semibold">Nivel</th>
                                        <th class="px-4 py-3 text-center font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">
                                    @if ($grados->isEmpty())
                                        <tr>
                                            <td colspan="9"
                                                class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                                <div class="mx-auto w-full max-w-md">
                                                    <div
                                                        class="rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 p-6">
                                                        <div class="mb-1 text-base font-semibold">
                                                            No hay grados disponibles
                                                        </div>
                                                        <p class="text-sm">Ajusta tu búsqueda.</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($grados as $key => $grado)
                                            <!-- Fila principal -->
                                            <tr
                                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-neutral-800/50">
                                                <!-- # + botón desplegar -->
                                                <td class="px-4 py-3 text-center text-gray-800 dark:text-gray-200">
                                                    <div class="flex items-center justify-center gap-2">

                                                        <span
                                                            class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                            {{ $key + 1 }}
                                                        </span>
                                                    </div>
                                                </td>



                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    {{ $grado->nombre ? $grado->nombre . '° GRADO' : '---' }}
                                                </td>
                                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                                                    {{ $grado->nivel->nombre ?: '---' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <flux:button variant="primary"
                                                            class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                                            @click="$dispatch('abrir-modal-editar');
                                                                Livewire.dispatch('editarModal', { id: {{ $grado->id }} });
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
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tarjetas (mobile) -->
                    <div class="md:hidden space-y-3">
                        @if ($grados->isEmpty())
                            <div
                                class="rounded-xl border border-dashed border-gray-300 dark:border-neutral-700 p-6 text-center">
                                <div class="mb-1 font-semibold text-gray-700 dark:text-gray-200">
                                    No hay grados disponibles
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Ajusta tu búsqueda o importa datos.
                                </p>
                            </div>
                        @else
                            @foreach ($grados as $key => $grado)
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="space-y-2">
                                            <div
                                                class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 px-3 py-1 text-white text-xs font-medium shadow-sm">
                                                <span>#{{ $key + 1 }}</span>
                                            </div>
                                            <div>
                                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                    {{ $grado->nombre ?: '---' }}
                                                </h2>
                                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                                    Nivel: {{ $grado->nivel->nombre ?: '---' }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-center gap-2">

                                            <flux:button variant="primary"
                                                class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                                @click="$dispatch('abrir-modal-editar');
                                                                Livewire.dispatch('editarModal', { id: {{ $grado->id }} });
                                                            ">
                                                <flux:icon.square-pen class="w-3.5 h-3.5" />
                                                <!-- ícono -->
                                            </flux:button>

                                            <flux:button variant="danger"
                                                class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white p-1"
                                                @click="eliminar({{ $grado->id }}, '{{ $grado->nombre }}')">
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
                    {{ $grados->links() }}
                </div>
            </div>
        </div>

        <!-- Modal editar -->
        {{-- <livewire:grado.editar-grado /> --}}
    </div>
</div>
