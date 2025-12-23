<div x-data="{
    show: false,
    loading: false,

    // ===== Eliminar =====
    eliminar(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `El rol  se eliminará de forma permanente`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminarRol', id))
    }
}" x-cloak x-trap.noscroll="show" x-show="show"
    @abrir-modal-editar.window="show = true; loading = true" @editar-cargado.window="loading = false"
    @cerrar-modal-editar.window="
      show = false;
      loading = false;
      $wire.cerrarModal()
    "
    @keydown.escape.window="show = false; $wire.cerrarModal()" class="fixed inset-0 z-50 flex items-center justify-center"
    aria-live="polite">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-neutral-900/70 backdrop-blur-sm" x-show="show" x-transition.opacity
        @click.self="show = false; $wire.cerrarModal()"></div>

    <!-- Modal -->
    <div class="relative w-[92vw] sm:w-[88vw] md:w-[90vw] max-w-2xl mx-4 sm:mx-6 bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl ring-1 ring-black/5 dark:ring-white/10 overflow-hidden
               flex flex-col max-h-[85vh]"
        role="dialog" aria-modal="true" aria-labelledby="titulo-modal-generacion" x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2" wire:ignore.self>
        <!-- Overlay de carga inicial -->
        <div x-show="loading" x-transition.opacity
            class="absolute inset-0 z-20 flex items-center justify-center bg-white/80 dark:bg-neutral-900/80 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-2">
                <svg class="w-6 h-6 animate-spin text-indigo-600 dark:text-indigo-400"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                    Cargando datos del rol...
                </p>
            </div>
        </div>

        <!-- Loader Livewire: Guardar -->
        <div wire:loading.delay wire:target="save"
            class="absolute inset-0 z-30 flex items-center justify-center bg-white/80 dark:bg-neutral-900/80 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-2">
                <svg class="w-6 h-6 animate-spin text-indigo-600 dark:text-indigo-400"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                    Guardando rol...
                </p>
            </div>
        </div>

        <!-- Loader Livewire: Eliminar -->
        <div wire:loading.delay wire:target="eliminarRol"
            class="absolute inset-0 z-30 flex items-center justify-center bg-white/80 dark:bg-neutral-900/80 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-2">
                <svg class="w-6 h-6 animate-spin text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                    Eliminando rol...
                </p>
            </div>
        </div>

        <!-- Top accent -->
        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500 shrink-0"></div>

        <!-- Header (fijo) -->
        <div
            class="px-5 sm:px-6 pt-4 flex items-start justify-between gap-3 sticky top-0 bg-white/95 dark:bg-neutral-900/95 backdrop-blur z-10">
            <div class="min-w-0">
                <h2 id="titulo-modal-rol" class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white">
                    Crear o editar Roles
                </h2>
            </div>

            <button @click="show = false; $wire.cerrarModal()" type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                aria-label="Cerrar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form wire:submit.prevent="save">
            <flux:field class="p-4 space-y-6">

                <div class="space-y-3">
                    <flux:select label="Rol existente (opcional)" wire:model.live="roleId">
                        <flux:select.option value="">➕ Crear nuevo rol</flux:select.option>

                        @foreach ($roles as $role)
                            <flux:select.option value="{{ $role->id }}">
                                {{ $role->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="flex items-center gap-2">
                        @if ($roleId)
                            <span
                                class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold
                                bg-violet-50 text-violet-700 dark:bg-violet-900/25 dark:text-violet-200 ring-1 ring-violet-600/15">
                                <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                                Editando rol #{{ $roleId }}
                            </span>

                            <button type="button" wire:click="setCrear"
                                class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400">
                                Cambiar a “Crear nuevo”
                            </button>
                        @else
                            <span
                                class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold
                                bg-emerald-50 text-emerald-700 dark:bg-emerald-900/25 dark:text-emerald-200 ring-1 ring-emerald-600/15">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Creando nuevo rol
                            </span>
                        @endif
                    </div>

                    {{-- Error si intentan eliminar sin poder, o validación --}}
                    <flux:error name="roleId" />
                </div>

                {{-- Inputs --}}
                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 rounded-2xl border border-gray-200 dark:border-neutral-800 bg-gray-50/60 dark:bg-neutral-800/40 p-4">
                    <flux:field>
                        <flux:label>Nombre</flux:label>
                        <flux:input wire:model.live="nombre" placeholder="Ej. Maestro(a) frente a grupo" />
                        <flux:error name="nombre" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input variant="filled" readonly wire:model.defer="slug"
                            placeholder="Ej. maestro_frente_a_grupo" />
                        <flux:error name="slug" />
                    </flux:field>


                </div>

                {{-- Botones --}}
                <div class="mt-6 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2">

                    {{-- Eliminar: solo si hay rol seleccionado --}}


                    <flux:button variant="danger" class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white p-1"
                        @click="eliminar({{ $roleId }})" wire:loading.attr="disabled"
                        wire:target="eliminarRol">
                        Eliminar
                    </flux:button>



                    <button type="button" @click="show = false; $wire.cerrarModal()"
                        class="inline-flex justify-center rounded-xl px-4 py-2.5 border border-neutral-200 dark:border-neutral-700
                               bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-100
                               hover:bg-neutral-50 dark:hover:bg-neutral-700 transition
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-300 dark:focus:ring-offset-neutral-900">
                        Cancelar
                    </button>

                    <flux:button variant="primary" type="submit" class="w-full sm:w-auto cursor-pointer"
                        wire:loading.attr="disabled" wire:target="save">
                        Guardar
                    </flux:button>
                </div>

            </flux:field>
        </form>
    </div>
</div>
