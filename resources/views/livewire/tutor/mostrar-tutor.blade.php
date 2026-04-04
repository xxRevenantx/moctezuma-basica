<div x-data="{
    openRow: null,
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `Este tutor se eliminará de forma permanente`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminar', id))
    }
}" class="space-y-5">


    {{-- ENCABEZADO --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                Tutores registrados
            </h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Consulta, edita y elimina la información de los tutores registrados.
            </p>
        </div>

        <div
            class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm text-zinc-600 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300">
            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
            <span>Total:
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($tutores->total()) }}
                </span>
            </span>
        </div>
    </div>

    {{-- CONTENEDOR --}}
    <div
        class="relative overflow-hidden rounded-[28px] border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

        {{-- TOOLBAR --}}
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    Listado de tutores
                </h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Busca por CURP, nombre, parentesco, teléfono o correo.
                </p>
            </div>



            <div class="w-full sm:max-w-md">
                <label for="buscarTutor" class="sr-only">Buscar tutor</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-zinc-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10.5 3.75a6.75 6.75 0 1 0 4.163 12.065l4.261 4.261a.75.75 0 1 0 1.06-1.06l-4.261-4.261A6.75 6.75 0 0 0 10.5 3.75Zm-5.25 6.75a5.25 5.25 0 1 1 10.5 0a5.25 5.25 0 0 1-10.5 0Z"
                                clip-rule="evenodd" />
                        </svg>
                    </span>

                    <input id="buscarTutor" type="text" wire:model.live.debounce.300ms="buscar"
                        placeholder="Buscar tutor..."
                        class="w-full rounded-2xl border border-zinc-300 bg-white py-3 pl-11 pr-4 text-sm text-zinc-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-blue-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end p-5">
            <flux:button variant="primary" class="cursor-pointer bg-green-700" wire:click="exportarTutores">
                <div class="flex justify-between gap-2">
                    <flux:icon.download class="w-4 h-4" />
                    Exportar Excel
                </div>
            </flux:button>
        </div>

        {{-- LOADER --}}
        <div wire:loading.flex wire:target="buscar,ordenarPor,actualizar,eliminar"
            class="absolute inset-0 z-20 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-zinc-950/70">
            <div
                class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
                <span class="text-sm text-zinc-700 dark:text-zinc-200">Procesando…</span>
            </div>
        </div>

        {{-- TABLA DESKTOP --}}
        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900/70">
                    <tr class="text-left text-zinc-600 dark:text-zinc-300">
                        <th class="px-6 py-4 font-semibold">
                            <button wire:click="ordenarPor('id')" class="hover:text-blue-600">#</button>
                        </th>
                        <th class="px-6 py-4 font-semibold">
                            <button wire:click="ordenarPor('curp')" class="hover:text-blue-600">CURP</button>
                        </th>
                        <th class="px-6 py-4 font-semibold">
                            <button wire:click="ordenarPor('nombre')" class="hover:text-blue-600">Nombre
                                completo</button>
                        </th>
                        <th class="px-6 py-4 font-semibold">
                            <button wire:click="ordenarPor('parentesco')"
                                class="hover:text-blue-600">Parentesco</button>
                        </th>
                        <th class="px-6 py-4 font-semibold">
                            <button wire:click="ordenarPor('telefono_celular')"
                                class="hover:text-blue-600">Teléfono</button>
                        </th>
                        <th class="px-6 py-4 font-semibold">
                            <button wire:click="ordenarPor('correo_electronico')"
                                class="hover:text-blue-600">Correo</button>
                        </th>
                        <th class="px-6 py-4 font-semibold">Ubicación</th>
                        <th class="px-6 py-4 font-semibold text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($tutores as $index => $tutor)
                        <tr class="transition hover:bg-zinc-50/80 dark:hover:bg-zinc-900/50">
                            <td class="px-6 py-4">
                                {{ $tutores->firstItem() + $index }}
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-medium tracking-wide text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                    {{ $tutor->curp ?: 'Sin CURP' }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ trim($tutor->nombre . ' ' . $tutor->apellido_paterno . ' ' . $tutor->apellido_materno) }}
                                </div>
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    Género: {{ $tutor->genero ?: 'N/D' }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                    {{ $tutor->parentesco ?: 'No definido' }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                {{ $tutor->telefono_celular ?: ($tutor->telefono_casa ?: 'Sin teléfono') }}
                            </td>

                            <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                {{ $tutor->correo_electronico ?: 'Sin correo' }}
                            </td>

                            <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                <div>{{ $tutor->ciudad ?: 'Sin ciudad' }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $tutor->estado ?: 'Sin estado' }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <flux:button variant="primary"
                                        class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                        @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $tutor->id }} });">
                                        <flux:icon.square-pen class="w-3.5 h-3.5" />
                                    </flux:button>

                                    <flux:button variant="danger"
                                        class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white p-1"
                                        @click="eliminar({{ $tutor->id }}, '{{ addslashes($tutor->nombre) }}')">
                                        <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12">
                                <div
                                    class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                        No hay tutores registrados
                                    </h3>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        No se encontraron registros con la búsqueda actual.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- TARJETAS MÓVILES --}}
        <div class="grid gap-4 p-4 lg:hidden">
            @forelse ($tutores as $index => $tutor)
                <div
                    class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ trim($tutor->nombre . ' ' . $tutor->apellido_paterno . ' ' . $tutor->apellido_materno) }}
                            </h3>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Registro #{{ $tutores->firstItem() + $index }}
                            </p>
                        </div>

                        <span
                            class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                            {{ $tutor->parentesco ?: 'N/D' }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 text-sm">
                        <div>
                            <span
                                class="block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">CURP</span>
                            <span class="text-zinc-800 dark:text-zinc-200">{{ $tutor->curp ?: 'Sin CURP' }}</span>
                        </div>

                        <div>
                            <span
                                class="block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Teléfono</span>
                            <span class="text-zinc-800 dark:text-zinc-200">
                                {{ $tutor->telefono_celular ?: ($tutor->telefono_casa ?: 'Sin teléfono') }}
                            </span>
                        </div>

                        <div>
                            <span
                                class="block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Correo</span>
                            <span
                                class="text-zinc-800 dark:text-zinc-200">{{ $tutor->correo_electronico ?: 'Sin correo' }}</span>
                        </div>

                        <div>
                            <span
                                class="block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Ubicación</span>
                            <span class="text-zinc-800 dark:text-zinc-200">
                                {{ $tutor->ciudad ?: 'Sin ciudad' }}, {{ $tutor->estado ?: 'Sin estado' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-2">
                        <flux:button variant="primary"
                            class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                            @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $tutor->id }} });">
                            <flux:icon.square-pen class="w-3.5 h-3.5" />
                        </flux:button>

                        <flux:button variant="danger"
                            class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white p-1"
                            @click="eliminar({{ $tutor->id }}, '{{ addslashes($tutor->nombre) }}')">
                            <flux:icon.trash-2 class="w-3.5 h-3.5" />
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        No hay tutores registrados
                    </h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        No se encontraron resultados.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- PAGINACIÓN --}}
        @if ($tutores->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                {{ $tutores->links() }}
            </div>
        @endif
    </div>


    <livewire:tutor.editar-tutor />



</div>
