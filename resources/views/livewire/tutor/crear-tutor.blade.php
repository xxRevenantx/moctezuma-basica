<div class="p-4 sm:p-6">

    <div x-data="{ open: false }" class="my-4">
        <!-- Toggle (form-pro) -->
        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="panel-nivel"
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
            <span class="font-medium">{{ __('Nuevo Tutor') }}</span>
            <span class="ml-1 transition-transform duration-200" :class="open ? 'rotate-180' : 'rotate-0'">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15.5l-6-6h12l-6 6z" />
                </svg>
            </span>
        </button>

        <!-- Panel (form-pro) -->
        <div id="panel-nivel" x-show="open" x-cloak x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative mt-4">
            <div class="mx-auto w-full">
                <div
                    class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    {{-- Acento superior --}}
                    <div class="h-1.5 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

                    {{-- Toolbar --}}
                    <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Nuevo tutor
                            </h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                Captura datos generales, domicilio y contacto. La CURP debe ser única.
                            </p>
                        </div>

                        {{-- Botón desplegable (Acciones) --}}
                        <div x-data="{ open: false }" class="relative">
                            <flux:button type="button" variant="outline" @click="open=!open"
                                @keydown.escape.window="open=false" class="rounded-xl">
                                <span class="inline-flex items-center gap-2">
                                    Acciones
                                    <svg class="h-4 w-4 opacity-70" viewBox="0 0 20 20" fill="currentColor"
                                        aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </flux:button>
                        </div>
                    </div>

                    {{-- Form --}}
                    <form wire:submit.prevent="guardar" class="p-5 pt-0">
                        {{-- Loader overlay --}}
                        <div wire:loading.flex wire:target="guardar"
                            class="absolute inset-0 z-10 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-zinc-950/70">
                            <div
                                class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z">
                                    </path>
                                </svg>
                                <span class="text-sm text-zinc-700 dark:text-zinc-200">Guardando…</span>
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

                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <flux:field>
                                    <flux:label badge="Requerido">CURP *</flux:label>
                                    <flux:input wire:model.blur="curp" maxlength="18" class="uppercase tracking-wider"
                                        placeholder="Ej. NUPC950101HGRXXX09 (18 caracteres)" />
                                    <flux:error name="curp" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Parentesco *</flux:label>
                                    <flux:input wire:model.blur="parentesco" maxlength="50" class="uppercase"
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
                                <button type="button"
                                    class="flex w-full items-center justify-between gap-3 p-4 text-left"
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
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <flux:field>
                                            <flux:label badge="Requerido">Nombre *</flux:label>
                                            <flux:input wire:model.blur="nombre" class="uppercase"
                                                placeholder="Ej. CARLOS ALBERTO" />
                                            <flux:error name="nombre" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Apellido paterno *</flux:label>
                                            <flux:input wire:model.blur="apellido_paterno" class="uppercase"
                                                placeholder="Ej. NÚÑEZ" />
                                            <flux:error name="apellido_paterno" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Apellido materno</flux:label>
                                            <flux:input wire:model.blur="apellido_materno" class="uppercase"
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
                                            <flux:input wire:model.blur="ciudad_nacimiento"
                                                placeholder="Ej. CD ALTAMIRANO" />
                                            <flux:error name="ciudad_nacimiento" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Municipio nacimiento</flux:label>
                                            <flux:input wire:model.blur="municipio_nacimiento"
                                                placeholder="Ej. PUNGARABATO" />
                                            <flux:error name="municipio_nacimiento" />
                                        </flux:field>

                                        <flux:field class="sm:col-span-3">
                                            <flux:label badge="Opcional">Estado nacimiento</flux:label>
                                            <flux:input wire:model.blur="estado_nacimiento"
                                                placeholder="Ej. GUERRERO" />
                                            <flux:error name="estado_nacimiento" />
                                        </flux:field>
                                    </div>
                                </div>
                            </div>

                            {{-- DOMICILIO --}}
                            <div
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <button type="button"
                                    class="flex w-full items-center justify-between gap-3 p-4 text-left"
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
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <flux:field class="sm:col-span-2">
                                            <flux:label badge="Requerido">Calle *</flux:label>
                                            <flux:input wire:model.blur="calle"
                                                placeholder="Ej. FRANCISCO I. MADERO ORIENTE" />
                                            <flux:error name="calle" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Número</flux:label>
                                            <flux:input wire:model.blur="numero" placeholder="Ej. 800 / S/N" />
                                            <flux:error name="numero" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Colonia *</flux:label>
                                            <flux:input wire:model.blur="colonia" placeholder="Ej. ESQUIPULA" />
                                            <flux:error name="colonia" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Ciudad *</flux:label>
                                            <flux:input wire:model.blur="ciudad" placeholder="Ej. CD ALTAMIRANO" />
                                            <flux:error name="ciudad" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Municipio *</flux:label>
                                            <flux:input wire:model.blur="municipio" placeholder="Ej. PUNGARABATO" />
                                            <flux:error name="municipio" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Estado *</flux:label>
                                            <flux:input wire:model.blur="estado" placeholder="Ej. GUERRERO" />
                                            <flux:error name="estado" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Código postal *</flux:label>
                                            <flux:input wire:model.blur="codigo_postal" inputmode="numeric"
                                                placeholder="Ej. 40662" />
                                            <flux:error name="codigo_postal" />
                                        </flux:field>
                                    </div>
                                </div>
                            </div>

                            {{-- CONTACTO --}}
                            <div
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <button type="button"
                                    class="flex w-full items-center justify-between gap-3 p-4 text-left"
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
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <flux:field>
                                            <flux:label badge="Opcional">Teléfono casa</flux:label>
                                            <flux:input wire:model.blur="telefono_casa" inputmode="tel"
                                                placeholder="Ej. 767 688 0000" />
                                            <flux:error name="telefono_casa" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Teléfono celular</flux:label>
                                            <flux:input wire:model.blur="telefono_celular" inputmode="tel"
                                                placeholder="Ej. 767 123 4567" />
                                            <flux:error name="telefono_celular" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Correo</flux:label>
                                            <flux:input type="email" wire:model.blur="correo_electronico"
                                                placeholder="correo@dominio.com" />
                                            <flux:error name="correo_electronico" />
                                        </flux:field>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Acciones finales --}}
                        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <flux:button type="button" variant="outline" wire:click="limpiar" class="rounded-xl">
                                Cancelar
                            </flux:button>

                            <flux:button type="submit" variant="primary"
                                class="rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 hover:brightness-110"
                                wire:loading.attr="disabled" wire:target="guardar">
                                <span wire:loading.remove wire:target="guardar">Guardar tutor</span>
                                <span wire:loading wire:target="guardar">Guardando…</span>
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
