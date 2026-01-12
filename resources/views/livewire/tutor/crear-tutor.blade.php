<div class="p-4 sm:p-6">
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
                    <flux:button type="button" variant="outline" @click="open=!open" @keydown.escape.window="open=false"
                        class="rounded-xl">
                        <span class="inline-flex items-center gap-2">
                            Acciones
                            <svg class="h-4 w-4 opacity-70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                    </flux:button>

                    <div x-show="open" x-transition.opacity.duration.150ms @click.outside="open=false"
                        class="absolute right-0 z-20 mt-2 w-56 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-950"
                        style="display:none;" role="menu">
                        <button type="button" wire:click="limpiar"
                            class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-900"
                            role="menuitem">
                            <span class="h-2 w-2 rounded-full bg-zinc-400"></span>
                            Limpiar formulario
                        </button>

                        <button type="button" wire:click="autocompletarDemo"
                            class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-900"
                            role="menuitem">
                            <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                            Autocompletar demo
                        </button>

                        <div class="h-px bg-zinc-200 dark:bg-zinc-800"></div>

                        <button type="button" onclick="window.history.back()"
                            class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-900"
                            role="menuitem">
                            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                            Volver
                        </button>
                    </div>
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
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z">
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
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">La CURP es obligatoria y única.</p>
                        </div>

                        <span
                            class="inline-flex items-center rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                            Campos clave
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <flux:field>
                            <flux:label>CURP *</flux:label>
                            <flux:input wire:model.blur="curp" maxlength="18" class="uppercase tracking-wider"
                                placeholder="18 caracteres" />
                            <flux:error name="curp" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Parentesco *</flux:label>
                            <flux:input wire:model.blur="parentesco" maxlength="50" class="uppercase"
                                placeholder="PADRE, MADRE, TUTOR..." />
                            <flux:error name="parentesco" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Género</flux:label>
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
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Datos generales
                                </h3>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Nombre completo y datos de
                                    nacimiento.</p>
                            </div>
                            <svg class="h-5 w-5 text-zinc-500 transition" :class="gen ? 'rotate-180' : ''"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-show="gen" x-transition.opacity.duration.200ms class="p-4 pt-0" style="display:none;">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <flux:field>
                                    <flux:label>Nombre *</flux:label>
                                    <flux:input wire:model.blur="nombre" class="uppercase" placeholder="Nombre(s)" />
                                    <flux:error name="nombre" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Apellido paterno *</flux:label>
                                    <flux:input wire:model.blur="apellido_paterno" class="uppercase"
                                        placeholder="Apellido paterno" />
                                    <flux:error name="apellido_paterno" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Apellido materno</flux:label>
                                    <flux:input wire:model.blur="apellido_materno" class="uppercase"
                                        placeholder="(Opcional)" />
                                    <flux:error name="apellido_materno" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Fecha de nacimiento</flux:label>
                                    <flux:input type="date" wire:model="fecha_nacimiento" />
                                    <flux:error name="fecha_nacimiento" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Ciudad nacimiento</flux:label>
                                    <flux:input wire:model.blur="ciudad_nacimiento" />
                                    <flux:error name="ciudad_nacimiento" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Municipio nacimiento</flux:label>
                                    <flux:input wire:model.blur="municipio_nacimiento" />
                                    <flux:error name="municipio_nacimiento" />
                                </flux:field>

                                <flux:field class="sm:col-span-3">
                                    <flux:label>Estado nacimiento</flux:label>
                                    <flux:input wire:model.blur="estado_nacimiento" />
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
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Domicilio</h3>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Calle, colonia, municipio y
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
                                    <flux:label>Calle</flux:label>
                                    <flux:input wire:model.blur="calle" />
                                    <flux:error name="calle" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Número</flux:label>
                                    <flux:input wire:model.blur="numero" />
                                    <flux:error name="numero" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Colonia</flux:label>
                                    <flux:input wire:model.blur="colonia" />
                                    <flux:error name="colonia" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Ciudad</flux:label>
                                    <flux:input wire:model.blur="ciudad" />
                                    <flux:error name="ciudad" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Municipio</flux:label>
                                    <flux:input wire:model.blur="municipio" />
                                    <flux:error name="municipio" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Estado</flux:label>
                                    <flux:input wire:model.blur="estado" />
                                    <flux:error name="estado" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Código postal</flux:label>
                                    <flux:input wire:model.blur="codigo_postal" />
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
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Contacto</h3>
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
                                    <flux:label>Teléfono casa</flux:label>
                                    <flux:input wire:model.blur="telefono_casa" />
                                    <flux:error name="telefono_casa" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Teléfono celular</flux:label>
                                    <flux:input wire:model.blur="telefono_celular" />
                                    <flux:error name="telefono_celular" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Correo</flux:label>
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
