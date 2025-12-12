<div>
    <!-- Header -->
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Asignación del personal Directivo</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Formulario para asignar un nuevo directivo.</p>
    </div>

    <div x-data="{ open: false }" class="my-4">
        <!-- Toggle (form-pro) -->
        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="panel-directivo"
            class="group inline-flex items-center gap-2 rounded-2xl px-4 py-2.5
                   bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400
                   dark:focus:ring-offset-neutral-900">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-white/15">
                <!-- ícono lápiz -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M5 19h4l10-10-4-4L5 15v4m14.7-11.3a1 1 0 000-1.4l-2-2a1 1 0 00-1.4 0l-1.6 1.6 3.4 3.4 1.6-1.6z" />
                </svg>
            </span>
            <span class="font-medium">{{ __('Nuevo Directivo') }}</span>
            <span class="ml-1 transition-transform duration-200" :class="open ? 'rotate-180' : 'rotate-0'">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15.5l-6-6h12l-6 6z" />
                </svg>
            </span>
        </button>

        <!-- Panel (form-pro) -->
        <div id="panel-directivo" x-show="open" x-cloak x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative mt-4">
            <form wire:submit.prevent="crearDirectivo" class="group">
                <div
                    class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-800 shadow-lg overflow-hidden">
                    <!-- Accent top -->
                    <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>

                    <!-- Content -->
                    <div class="p-5 sm:p-6 lg:p-8">
                        <!-- Título interno -->
                        <div class="mb-5 flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 grid place-items-center">
                                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M12 6v12m6-6H6" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Nuevo directivo</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Completa los campos y guarda los
                                    cambios.</p>
                            </div>
                        </div>

                        <!-- Grid de inputs -->
                        {{-- Grid de inputs --}}
                        <flux:field>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:input badge="Requerido" wire:model="titulo" :label="__('Título')" type="text"
                                    placeholder="Ej.: M.C., Lic., Dr., Mtro., Profr., etc." autocomplete="title" />

                                <flux:input badge="Requerido" wire:model="nombre" :label="__('Nombre')" type="text"
                                    placeholder="Nombre del directivo" autocomplete="given-name" />

                                <flux:input badge="Requerido" wire:model="apellido_paterno"
                                    :label="__('Apellido Paterno')" type="text" placeholder="Apellido paterno"
                                    autocomplete="family-name" />

                                <flux:input badge="Opcional" wire:model="apellido_materno"
                                    :label="__('Apellido Materno')" type="text" placeholder="Apellido materno"
                                    autocomplete="additional-name" />

                                <flux:input badge="Opcional" wire:model="curp" :label="__('CURP')" type="text"
                                    placeholder="CURP del directivo" maxlength="18" autocomplete="off"
                                    class="uppercase" />

                                <flux:input badge="Opcional" wire:model="rfc" :label="__('RFC')" type="text"
                                    placeholder="RFC del directivo" maxlength="13" autocomplete="off"
                                    class="uppercase" />

                                <flux:input badge="Requerido" wire:model="cargo" :label="__('Cargo')" type="text"
                                    placeholder="Cargo" autocomplete="organization-title" />

                                <flux:input badge="Requerido" wire:model="identificador" :label="__('Identificador')"
                                    type="text" placeholder="Ej.: Director, Supervisor, Jefe, Rector…"
                                    autocomplete="off" />

                                <flux:input badge="Opcional" wire:model="zona_escolar" :label="__('Zona escolar')"
                                    type="text" placeholder="Ej.: Zona 021, Zona Altamirano, etc."
                                    autocomplete="off" />

                                <flux:input badge="Opcional" wire:model="sector" :label="__('Sector')" type="text"
                                    placeholder="Ej.: 01, 02.." autocomplete="off" />

                                <flux:input badge="Opcional" wire:model="telefono" :label="__('Teléfono')"
                                    type="text" placeholder="Teléfono" autocomplete="tel" />

                                <flux:input badge="Opcional" wire:model="correo" :label="__('Correo Electrónico')"
                                    type="email" placeholder="correo@dominio.com" autocomplete="email" />
                                <flux:select badge="Requerido" wire:model="genero" :label="__('Género')">
                                    <option value="">-- Selecciona una opción --</option>
                                    <option value="M">Hombre</option>
                                    <option value="F">Mujer</option>
                                </flux:select>
                            </div>

                            {{-- Status --}}
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Define si el directivo estará activo en el sistema.
                                </span>

                                <flux:checkbox wire:model="status" :label="__('Activo')" />
                            </div>
                        </flux:field>


                        <!-- Divider -->
                        <div class="mt-6 border-t border-gray-200 dark:border-neutral-800"></div>

                        <!-- Acciones (abajo de los inputs) -->
                        <div
                            class="mt-6 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2">
                            <button type="button" @click="open = false"
                                class="inline-flex justify-center rounded-xl px-4 py-2.5 border border-neutral-200 dark:border-neutral-700
                                       bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-100
                                       hover:bg-neutral-50 dark:hover:bg-neutral-700
                                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-300 dark:focus:ring-offset-neutral-900">
                                Cancelar
                            </button>

                            <flux:button variant="primary" type="submit"
                                class="w-full sm:w-auto cursor-pointer btn-gradient" wire:loading.attr="disabled"
                                wire:target="crearDirectivo">
                                {{ __('Guardar') }}
                            </flux:button>
                        </div>
                    </div>

                    <!-- Loader overlay -->
                    <div wire:loading.delay wire:target="crearDirectivo"
                        class="pointer-events-none absolute inset-0 grid place-items-center bg-white/60 dark:bg-neutral-900/60">
                        <div
                            class="flex items-center gap-3 rounded-xl bg-white/90 dark:bg-neutral-900/90 px-4 py-3 ring-1 ring-gray-200 dark:ring-neutral-700 shadow">
                            <svg class="h-5 w-5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24"
                                fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Guardando…</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
