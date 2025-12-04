<div x-data="{ show: false, loading: false }" x-cloak x-trap.noscroll="show" x-show="show"
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
        <!-- Top accent -->
        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500 shrink-0"></div>

        <!-- Header (fijo) -->
        <div
            class="px-5 sm:px-6 pt-4 pb-3 flex items-start justify-between gap-3 sticky top-0 bg-white/95 dark:bg-neutral-900/95 backdrop-blur z-10">
            <div class="min-w-0">
                <h2 id="titulo-modal-generacion" class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white">
                    Editar Directivo
                </h2>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    <span class="inline-flex items-center gap-2">
                        <flux:badge color="indigo">{{ $nombre }} {{ $apellido_paterno }} {{ $apellido_materno }}
                        </flux:badge>
                    </span>
                </p>
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

        {{-- Grid de inputs --}}
        <flux:field class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="titulo" :label="__('Título')" type="text"
                    placeholder="Ej.: M.C., Lic., Dr., Mtro., Profr., etc." autocomplete="title" />

                <flux:input wire:model="nombre" :label="__('Nombre')" type="text" placeholder="Nombre del directivo"
                    autocomplete="given-name" />

                <flux:input wire:model="apellido_paterno" :label="__('Apellido Paterno')" type="text"
                    placeholder="Apellido paterno" autocomplete="family-name" />

                <flux:input wire:model="apellido_materno" :label="__('Apellido Materno')" type="text"
                    placeholder="Apellido materno" autocomplete="additional-name" />

                <flux:input wire:model="curp" :label="__('CURP')" type="text" placeholder="CURP del directivo"
                    maxlength="18" autocomplete="off" class="uppercase" />

                <flux:input wire:model="rfc" :label="__('RFC')" type="text" placeholder="RFC del directivo"
                    maxlength="13" autocomplete="off" class="uppercase" />

                <flux:input wire:model="cargo" :label="__('Cargo')" type="text" placeholder="Cargo"
                    autocomplete="organization-title" />

                <flux:input wire:model="identificador" :label="__('Identificador')" type="text"
                    placeholder="Ej.: Director, Supervisor, Jefe, Rector…" autocomplete="off" />

                <flux:input wire:model="zona_escolar" :label="__('Zona escolar')" type="text"
                    placeholder="Ej.: Zona 021, Zona Altamirano, etc." autocomplete="off" />

                <flux:input wire:model="telefono" :label="__('Teléfono')" type="text" placeholder="Teléfono"
                    autocomplete="tel" />

                <flux:input wire:model="correo" :label="__('Correo Electrónico')" type="email"
                    placeholder="correo@dominio.com" autocomplete="email" />
            </div>

            {{-- Status --}}
            <div class="mt-4 flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Define si el directivo estará activo en el sistema.
                </span>

                <flux:checkbox wire:model="status" :label="__('Activo')" />
            </div>
        </flux:field>





    </div>
</div>
