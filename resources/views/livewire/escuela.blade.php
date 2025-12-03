<div class="w-full mx-auto py-4  bg-neutral-50/60 dark:bg-neutral-900/60">

    <!-- ENCABEZADO -->
    <div class="flex flex-col gap-4">
        <div
            class="relative overflow-hidden rounded-2xl border border-neutral-200/80 dark:border-neutral-800 bg-gradient-to-b from-white to-neutral-50 dark:from-neutral-950 dark:to-neutral-900 shadow-[0_18px_45px_-25px_rgba(15,23,42,0.55)]">

            <!-- Acento superior -->
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 via-blue-500 to-indigo-500"></div>

            <!-- Fondo decorativo sutil -->
            <div class="pointer-events-none absolute -left-10 -bottom-10 h-32 w-32 rounded-full bg-sky-400/10 blur-3xl">
            </div>
            <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-indigo-500/10 blur-3xl">
            </div>

            <!-- Barra de título -->
            <div
                class="relative z-10 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-950/95 px-4 py-3 sm:px-6 sm:py-4 text-neutral-900 dark:text-neutral-50">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-200 shadow-sm ring-1 ring-violet-200/70 dark:ring-violet-700/60">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7h18M7 3v4m10-4v4M5 21h14a2 2 0 002-2V7H3v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <div class="space-y-0.5">
                        <h1 class="text-lg sm:text-xl font-semibold tracking-tight">
                            Datos de la escuela
                        </h1>
                        <p class="hidden sm:block text-[11px] uppercase tracking-[0.18em] text-neutral-400">
                            Ficha institucional · Información general
                        </p>
                    </div>
                </div>
                <span
                    class="hidden sm:inline-flex items-center gap-1 rounded-full bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-200 px-3 py-1 text-[11px] font-medium tracking-wide">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Actualiza la ficha institucional
                </span>
            </div>

            <!-- OVERLAY LOADER al guardar -->
            <div wire:loading.flex wire:target="guardarEscuela"
                class="absolute inset-0 z-30 items-center justify-center bg-white/70 dark:bg-black/60 backdrop-blur-sm">
                <div class="flex flex-col items-center gap-3 text-neutral-700 dark:text-neutral-100">
                    <!-- Spinner -->
                    <svg class="animate-spin h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <div class="text-sm font-medium">Guardando cambios…</div>
                    <div class="h-1 w-40 rounded-full bg-neutral-200 dark:bg-neutral-800 overflow-hidden">
                        <div
                            class="h-full w-1/2 animate-[loader_1.2s_ease-in-out_infinite] bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600">
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORMULARIO -->
            <div class="relative z-10 p-4 sm:p-6">
                <form wire:submit.prevent="guardarEscuela" class="space-y-6" role="form"
                    aria-label="Formulario de datos de la escuela">
                    <flux:field>

                        <!-- Nota / ayuda -->
                        <div
                            class="flex items-start gap-3 rounded-2xl bg-neutral-50 dark:bg-neutral-900/80 border border-dashed border-neutral-200 dark:border-neutral-700 px-4 py-3">
                            <div
                                class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 text-sky-600 dark:bg-sky-900/40 dark:text-sky-200 text-xs font-semibold">
                                i
                            </div>
                            <p class="text-xs leading-relaxed text-neutral-600 dark:text-neutral-400">
                                Completa la información institucional. Los campos clave que
                                son importantes para notificaciones y reportes.
                            </p>
                        </div>

                        <!-- Grid responsive: 1 / 2 / 3 / 4 columnas -->
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-4 bg-white/70 dark:bg-neutral-950/40 rounded-2xl p-4 ring-1 ring-neutral-100 dark:ring-neutral-800">

                            <!-- Nombre (más ancho) -->
                            <flux:input wire:model="nombre" :label="__('Nombre')" type="text"
                                placeholder="Nombre de la escuela" autofocus autocomplete="organization"
                                class="sm:col-span-2 lg:col-span-2" />


                            <!-- Calle (más ancho) -->
                            <flux:input wire:model="calle" :label="__('Calle')" type="text" placeholder="Calle"
                                autocomplete="address-line1" class="sm:col-span-2 lg:col-span-2" />

                            <flux:input wire:model="no_exterior" :label="__('No. Exterior')" type="text"
                                placeholder="Número Exterior" autocomplete="address-line2" />
                            <flux:input wire:model="no_interior" :label="__('No. Interior')" type="text"
                                placeholder="Número Interior" autocomplete="address-line2" />

                            <!-- Colonia (más ancho) -->
                            <flux:input wire:model="colonia" :label="__('Colonia')" type="text" placeholder="Colonia"
                                autocomplete="address-level4" class="sm:col-span-2" />

                            <flux:input wire:model="codigo_postal" :label="__('Código Postal')" type="text"
                                inputmode="numeric" placeholder="Código Postal" autocomplete="postal-code" />

                            <flux:input wire:model="ciudad" :label="__('Ciudad')" type="text" placeholder="Ciudad"
                                autocomplete="address-level2" />
                            <flux:input wire:model="municipio" :label="__('Municipio')" type="text"
                                placeholder="Municipio" autocomplete="address-level2" />
                            <flux:input wire:model="estado" :label="__('Estado')" type="text" placeholder="Estado"
                                autocomplete="address-level1" />

                            <flux:input wire:model="telefono" :label="__('Teléfono')" type="tel"
                                placeholder="Teléfono" autocomplete="tel" />
                            <flux:input wire:model="correo" :label="__('Correo')" type="email"
                                placeholder="Correo electrónico" autocomplete="email" />

                            <!-- Página Web (más ancho) -->
                            <flux:input wire:model="pagina_web" :label="__('Página Web')" type="url"
                                placeholder="https://ejemplo.edu.mx" autocomplete="url"
                                class="sm:col-span-2 lg:col-span-2" />
                        </div>

                        <!-- Botonera -->
                        <div
                            class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-4 border-t border-neutral-200/80 dark:border-neutral-800 mt-4">


                            <flux:button variant="primary" type="submit"
                                class="w-full sm:w-auto min-w-[150px] cursor-pointer
                       bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600
                       hover:from-sky-600 hover:via-blue-700 hover:to-indigo-700
                       text-sm font-semibold tracking-wide
                       shadow-lg hover:shadow-xl focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-sky-500"
                                wire:loading.attr="disabled" wire:target="guardarEscuela">
                                <span wire:loading.remove wire:target="guardarEscuela">{{ __('Guardar') }}</span>
                                <span wire:loading wire:target="guardarEscuela"
                                    class="inline-flex items-center gap-2">
                                    <span
                                        class="w-4 h-4 rounded-full border-2 border-white/70 border-t-transparent animate-spin"></span>
                                    {{ __('Guardando…') }}
                                </span>
                            </flux:button>
                        </div>
                    </flux:field>
                </form>
            </div>
        </div>
    </div>

    <!-- Animación barrita del loader -->
    <style>
        @keyframes loader {
            0% {
                transform: translateX(-50%);
            }

            50% {
                transform: translateX(60%);
            }

            100% {
                transform: translateX(160%);
            }
        }

        .animate-\[loader_1\.2s_ease-in-out_infinite] {
            animation: loader 1.2s ease-in-out infinite;
        }
    </style>

</div>
