<div x-data="{
    openRow: null,
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `La generación ${nombre} se eliminará de forma permanente`,
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
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Generaciones</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Busca, edita o elimina generaciones.
        </p>
    </div>

    <!-- Contenedor listado -->
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white/90 dark:bg-neutral-900/90 shadow-sm">
        <!-- Acabado superior -->
        <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-sky-400 to-indigo-600"></div>

        <!-- Toolbar -->
        <div
            class="p-4 sm:p-5 lg:p-6 border-b border-gray-100/80 dark:border-neutral-800/80 bg-gradient-to-r from-slate-50 via-white to-sky-50 dark:from-neutral-900 dark:via-neutral-950 dark:to-sky-950/20">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Buscador -->
                <div class="w-full sm:max-w-xl">
                    <label for="buscar-generacion" class="sr-only">Buscar Generación</label>
                    <flux:input id="buscar-generacion" type="text" wire:model.live="search" placeholder="Buscar…"
                        icon="magnifying-glass" class="w-full" />
                </div>

                <!-- Resumen -->
                <div class="flex items-center gap-3 justify-between sm:justify-end">
                    <div
                        class="hidden sm:flex items-center gap-2 rounded-lg border border-gray-200 dark:border-neutral-800 px-3 py-1.5 bg-white/70 dark:bg-neutral-900/70 shadow-sm">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            Resultados:
                            <strong>{{ $totalGeneraciones }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de resultados -->
        <div class="px-4 pb-4 pt-3 sm:px-5 sm:pb-6 lg:px-6">
            <div class="relative">

                <!-- Loader -->
                <div wire:loading.delay wire:target="search, eliminar"
                    class="absolute inset-0 z-10 grid place-items-center rounded-2xl bg-white/70 dark:bg-neutral-900/70 backdrop-blur"
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

                    @if ($groupedByNivel->isEmpty())
                        <!-- Estado vacío -->
                        <div
                            class="mt-4 rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 p-8 bg-white/80 dark:bg-neutral-900/80 text-center">
                            <div
                                class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-950">
                                <flux:icon.search class="w-5 h-5 text-indigo-600 dark:text-indigo-300" />
                            </div>
                            <div class="mb-1 text-base font-semibold text-gray-800 dark:text-gray-100">
                                No hay generaciones disponibles
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Ajusta tu búsqueda o registra una nueva generación.
                            </p>
                        </div>
                    @else
                        <!-- Tabla agrupada por nivel -->
                        <div
                            class="mt-2 overflow-hidden rounded-2xl border border-slate-200/80 dark:border-neutral-800/80 bg-white/80 dark:bg-neutral-950/80">
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead
                                        class="bg-slate-50/80 dark:bg-neutral-900/80 border-b border-slate-200/70 dark:border-neutral-800/80">
                                        <tr class="text-left">
                                            <th
                                                class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                                #
                                            </th>
                                            <th
                                                class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                                Generación
                                            </th>
                                            <th
                                                class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                                Ingreso
                                            </th>
                                            <th
                                                class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                                Egreso
                                            </th>
                                            <th
                                                class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                                Estado
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-slate-200/70 dark:divide-neutral-800/80">
                                        @php $row = 0; @endphp

                                        @foreach ($groupedByNivel as $nivelNombre => $items)
                                            <!-- Separador por nivel -->
                                            <tr
                                                class="bg-gradient-to-r from-indigo-50/70 via-white to-sky-50/60 dark:from-indigo-950/30 dark:via-neutral-950 dark:to-sky-950/20">
                                                <td colspan="6" class="px-4 py-3">
                                                    <div
                                                        class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <div
                                                                class="inline-flex items-center gap-2 rounded-full bg-indigo-50 dark:bg-indigo-950/70 px-3 py-1 shadow-sm ring-1 ring-indigo-100 dark:ring-indigo-900/60">
                                                                <span
                                                                    class="h-2 w-2 rounded-full bg-indigo-500 dark:bg-indigo-300"></span>
                                                                <span
                                                                    class="text-xs font-semibold tracking-wide uppercase text-indigo-700 dark:text-indigo-100">
                                                                    {{ $nivelNombre }}
                                                                </span>
                                                            </div>

                                                            <span
                                                                class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                                                {{ $items->count() }} generaciones (en esta página)
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                            @foreach ($items as $generacion)
                                                @php $row++; @endphp
                                                <tr
                                                    class="hover:bg-slate-50/80 dark:hover:bg-neutral-900/40 transition-colors">
                                                    <td class="px-4 py-3">
                                                        <span
                                                            class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950 text-[11px] font-semibold text-indigo-700 dark:text-indigo-200 ring-1 ring-indigo-100 dark:ring-indigo-800">
                                                            {{ $row }}
                                                        </span>
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        <div class="flex flex-col">
                                                            <span class="font-semibold text-slate-900 dark:text-white">
                                                                {{ $generacion->anio_ingreso }} -
                                                                {{ $generacion->anio_egreso }}
                                                            </span>
                                                            <span
                                                                class="text-[11px] text-slate-500 dark:text-slate-400">
                                                                Generación académica
                                                            </span>
                                                        </div>
                                                    </td>

                                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-200">
                                                        {{ $generacion->anio_ingreso }}
                                                    </td>

                                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-200">
                                                        {{ $generacion->anio_egreso }}
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        @if ((int) $generacion->status === 1)
                                                            <span
                                                                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-900/40 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-200 ring-1 ring-emerald-100 dark:ring-emerald-800">
                                                                <span
                                                                    class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                                Activa
                                                            </span>
                                                        @else
                                                            <span
                                                                class="inline-flex items-center gap-1 rounded-full bg-rose-50 dark:bg-rose-900/40 px-2.5 py-1 text-[11px] font-semibold text-rose-700 dark:text-rose-200 ring-1 ring-rose-100 dark:ring-rose-800">
                                                                <span
                                                                    class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                                                Inactiva
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center justify-end gap-2">



                                                            <flux:button variant="danger"
                                                                class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white p-1"
                                                                @click="eliminar({{ $generacion->id }}, '{{ addslashes($generacion->anio_ingreso . ' - ' . $generacion->anio_egreso) }}')">
                                                                <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                                            </flux:button>

                                                            <flux:button variant="primary"
                                                                class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white !px-3 !py-1.5 text-xs"
                                                                @click="$dispatch('abrir-modal-editar');
                                                                        Livewire.dispatch('editarModal', { id: {{ $generacion->id }} });
                                                                    ">
                                                                <flux:icon.square-pen class="w-3.5 h-3.5 mr-1" />
                                                            </flux:button>


                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Paginación -->
                <div class="mt-5">
                    {{ $generaciones->links() }}
                </div>

            </div>
        </div>

        <!-- Modal editar -->
        <livewire:generacion.editar-generacion />
    </div>
</div>
