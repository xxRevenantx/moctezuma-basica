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
    <div class="relative w-[92vw] sm:w-[88vw] md:w-[90vw] max-w-2xl mx-4 sm:mx-6
                bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl ring-1 ring-black/5 dark:ring-white/10
                overflow-hidden flex flex-col max-h-[85vh]"
        role="dialog" aria-modal="true" aria-labelledby="titulo-modal-generacion" x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2" wire:ignore.self>

        <!-- Overlay de carga -->
        <div x-show="loading" x-transition.opacity
            class="absolute inset-0 z-20 flex items-center justify-center
                   bg-white/80 dark:bg-neutral-900/80 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-2">
                <svg class="w-6 h-6 animate-spin text-indigo-600 dark:text-indigo-400"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                    Cargando datos del tutor...
                </p>
            </div>
        </div>

        <!-- Top accent -->
        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500 shrink-0"></div>

        <!-- Header (fijo) -->
        <div
            class="px-5 sm:px-6 pt-4 pb-3 flex items-start justify-between gap-3
                   sticky top-0 bg-white/95 dark:bg-neutral-900/95 backdrop-blur z-10">
            <div class="min-w-0">
                <h2 id="titulo-modal-generacion" class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white">
                    Editar Tutor
                </h2>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    <span class="inline-flex items-center gap-2">
                        <flux:badge color="indigo">
                            {{ $nombre }} {{ $apellido_paterno }} {{ $apellido_materno }}
                        </flux:badge>
                    </span>
                </p>
            </div>

            <button @click="show = false; $wire.cerrarModal()" type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full
                       text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100
                       dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-neutral-800
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                aria-label="Cerrar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Contenido con scroll -->
        <div class="flex-1 overflow-y-auto">

            {{-- Form --}}
            <form wire:submit.prevent="actualizarTutor" class="p-5 pt-0">
                {{-- Loader overlay --}}
                <div wire:loading.flex wire:target="actualizarTutor"
                    class="absolute inset-0 z-10 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-zinc-950/70">
                    <div
                        class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                        <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z">
                            </path>
                        </svg>
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">Actualizando…</span>
                    </div>
                </div>

                {{-- Identidad --}}
                <div
                    class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Identidad</h3>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">La CURP es obligatoria y
                                única.</p>
                        </div>

                        <span
                            class="inline-flex items-center rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                            Campos clave
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label badge="Requerido">CURP *</flux:label>
                            <flux:input wire:model="curp" maxlength="18" class="uppercase tracking-wider"
                                placeholder="Ej. NUPC950101HGRXXX09 (18 caracteres)" />
                            <flux:error name="curp" />
                        </flux:field>

                        <flux:field>
                            <flux:label badge="Requerido">Parentesco *</flux:label>
                            <flux:input wire:model="parentesco" maxlength="50" class="uppercase"
                                placeholder="Ej. PADRE, MADRE, TUTOR..." />
                            <flux:error name="parentesco" />
                        </flux:field>

                        <flux:field>
                            <flux:label badge="Requerido">Género *</flux:label>
                            <flux:select wire:model="genero">
                                <option value="">Selecciona…</option>
                                <option value="M">M - Masculino</option>
                                <option value="F">F - Femenino</option>
                                <option value="O">O - Otro</option>
                            </flux:select>
                            <flux:error name="genero" />
                        </flux:field>
                    </div>
                </div>

                {{-- Secciones colapsables --}}
                <div class="mt-4 space-y-4" x-data="{ gen: true, dom: false, con: false }">
                    {{-- DATOS GENERALES --}}
                    <div
                        class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                        <button type="button" class="flex w-full items-center justify-between gap-3 p-4 text-left"
                            @click="gen=!gen">
                            <div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Datos
                                    generales
                                </h3>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Nombre completo y
                                    datos de
                                    nacimiento.</p>
                            </div>
                            <svg class="h-5 w-5 text-zinc-500 transition" :class="gen ? 'rotate-180' : ''"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-show="gen" x-transition.opacity.duration.200ms class="p-4 pt-0"
                            style="display:none;">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label badge="Requerido">Nombre *</flux:label>
                                    <flux:input wire:model="nombre" class="uppercase"
                                        placeholder="Ej. CARLOS ALBERTO" />
                                    <flux:error name="nombre" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Apellido paterno *</flux:label>
                                    <flux:input wire:model="apellido_paterno" class="uppercase"
                                        placeholder="Ej. NÚÑEZ" />
                                    <flux:error name="apellido_paterno" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Apellido materno</flux:label>
                                    <flux:input wire:model="apellido_materno" class="uppercase"
                                        placeholder="Ej. PÉREZ (opcional)" />
                                    <flux:error name="apellido_materno" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Fecha de nacimiento</flux:label>
                                    <flux:input type="date" wire:model="fecha_nacimiento"
                                        placeholder="Selecciona una fecha" />
                                    <flux:error name="fecha_nacimiento" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Ciudad nacimiento</flux:label>
                                    <flux:input wire:model="ciudad_nacimiento" placeholder="Ej. CD ALTAMIRANO" />
                                    <flux:error name="ciudad_nacimiento" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Municipio nacimiento</flux:label>
                                    <flux:input wire:model="municipio_nacimiento" placeholder="Ej. PUNGARABATO" />
                                    <flux:error name="municipio_nacimiento" />
                                </flux:field>

                                <flux:field class="sm:col-span-3">
                                    <flux:label badge="Opcional">Estado nacimiento</flux:label>
                                    <flux:input wire:model="estado_nacimiento" placeholder="Ej. GUERRERO" />
                                    <flux:error name="estado_nacimiento" />
                                </flux:field>
                            </div>
                        </div>
                    </div>

                    {{-- DOMICILIO --}}
                    <div
                        class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                        <button type="button" class="flex w-full items-center justify-between gap-3 p-4 text-left"
                            @click="dom=!dom">
                            <div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Domicilio
                                </h3>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Calle, colonia,
                                    municipio y
                                    CP.</p>
                            </div>
                            <svg class="h-5 w-5 text-zinc-500 transition" :class="dom ? 'rotate-180' : ''"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-show="dom" x-transition.opacity.duration.200ms class="p-4 pt-0"
                            style="display:none;">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <flux:field class="sm:col-span-2">
                                    <flux:label badge="Requerido">Calle *</flux:label>
                                    <flux:input wire:model="calle" placeholder="Ej. FRANCISCO I. MADERO ORIENTE" />
                                    <flux:error name="calle" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Número</flux:label>
                                    <flux:input wire:model="numero" placeholder="Ej. 800 / S/N" />
                                    <flux:error name="numero" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Colonia *</flux:label>
                                    <flux:input wire:model="colonia" placeholder="Ej. ESQUIPULA" />
                                    <flux:error name="colonia" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Ciudad *</flux:label>
                                    <flux:input wire:model="ciudad" placeholder="Ej. CD ALTAMIRANO" />
                                    <flux:error name="ciudad" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Municipio *</flux:label>
                                    <flux:input wire:model="municipio" placeholder="Ej. PUNGARABATO" />
                                    <flux:error name="municipio" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Estado *</flux:label>
                                    <flux:input wire:model="estado" placeholder="Ej. GUERRERO" />
                                    <flux:error name="estado" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Código postal *</flux:label>
                                    <flux:input wire:model="codigo_postal" inputmode="numeric"
                                        placeholder="Ej. 40662" />
                                    <flux:error name="codigo_postal" />
                                </flux:field>
                            </div>
                        </div>
                    </div>

                    {{-- CONTACTO --}}
                    <div
                        class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                        <button type="button" class="flex w-full items-center justify-between gap-3 p-4 text-left"
                            @click="con=!con">
                            <div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Contacto
                                </h3>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Teléfonos y correo
                                    electrónico.</p>
                            </div>
                            <svg class="h-5 w-5 text-zinc-500 transition" :class="con ? 'rotate-180' : ''"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-show="con" x-transition.opacity.duration.200ms class="p-4 pt-0"
                            style="display:none;">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label badge="Opcional">Teléfono casa</flux:label>
                                    <flux:input wire:model="telefono_casa" inputmode="tel"
                                        placeholder="Ej. 767 688 0000" />
                                    <flux:error name="telefono_casa" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Teléfono celular</flux:label>
                                    <flux:input wire:model="telefono_celular" inputmode="tel"
                                        placeholder="Ej. 767 123 4567" />
                                    <flux:error name="telefono_celular" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Correo</flux:label>
                                    <flux:input type="email" wire:model="correo_electronico"
                                        placeholder="correo@dominio.com" />
                                    <flux:error name="correo_electronico" />
                                </flux:field>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Acciones finales --}}
                <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button @click="show = false; $wire.cerrarModal()" type="button"
                        class="inline-flex justify-center rounded-xl px-4 py-2.5 border border-neutral-200 dark:border-neutral-700
                                   bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-100
                                   hover:bg-neutral-50 dark:hover:bg-neutral-700
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-300 dark:focus:ring-offset-neutral-900">
                        Cancelar
                    </button>

                    <flux:button variant="primary" type="submit" class="w-full sm:w-auto cursor-pointer"
                        wire:loading.attr="disabled" wire:target="actualizarTutor">
                        {{ __('Actualizar') }}
                    </flux:button>


                </div>
            </form>
        </div>
    </div>
</div>
