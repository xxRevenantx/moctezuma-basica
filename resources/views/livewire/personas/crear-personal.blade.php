<div>
    <!-- Header -->
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Asignación del personal</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Formulario para asignar nuevo personal.</p>
    </div>

    <div x-data="{ open: false }" class="my-4">
        <!-- Toggle (form-pro) -->
        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="panel-personal"
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
            <span class="font-medium">{{ __('Nuevo Personal') }}</span>
            <span class="ml-1 transition-transform duration-200" :class="open ? 'rotate-180' : 'rotate-0'">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15.5l-6-6h12l-6 6z" />
                </svg>
            </span>
        </button>

        <!-- Panel (form-pro) -->
        <div id="panel-personal" x-show="open" x-cloak x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative mt-4">

            <form wire:submit.prevent="crearPersonal" class="group">
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
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Nuevo Personal</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Completa los campos y guarda los
                                    cambios.</p>
                            </div>
                        </div>


                        {{-- Grid de inputs --}}
                        <flux:field>

                            {{-- ====== Foto ====== --}}
                            <div class="mt-6">
                                <flux:field>
                                    <flux:label>Foto (opcional)</flux:label>

                                    <div
                                        class="mt-2 rounded-2xl border border-dashed border-zinc-300/70 dark:border-zinc-700 bg-white/60 dark:bg-zinc-900/40 p-4 sm:p-5 shadow-sm">
                                        <div class="flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between">
                                            {{-- Texto + icono --}}
                                            <div class="flex items-start gap-3">
                                                <div
                                                    class="grid place-items-center h-11 w-11 rounded-xl bg-zinc-100 dark:bg-zinc-800 ring-1 ring-black/5 dark:ring-white/10">
                                                    {{-- icon --}}
                                                    <svg class="h-5 w-5 text-zinc-600 dark:text-zinc-300" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M3 16l4-4a3 3 0 014 0l1 1a3 3 0 004 0l4-4m-2 10a8 8 0 11-16 0 8 8 0 0116 0z" />
                                                    </svg>
                                                </div>

                                                <div>
                                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                                        Sube una foto del personal
                                                    </p>
                                                    <p class="text-xs text-zinc-600 dark:text-zinc-300 mt-0.5">
                                                        JPG o PNG. Recomendado: 600×600 o superior.
                                                    </p>

                                                    <div class="mt-2">
                                                        <flux:input type="file" wire:model="foto"
                                                            accept="image/png,image/jpeg,image/jpg" />
                                                    </div>

                                                    <flux:error name="foto" />
                                                </div>
                                            </div>

                                            {{-- Loader al subir --}}
                                            <div class="shrink-0">
                                                <div wire:loading wire:target="foto"
                                                    class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium
                               bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 ring-1 ring-black/5 dark:ring-white/10">
                                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24"
                                                        fill="none">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                                    </svg>
                                                    Subiendo…
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Preview --}}
                                        @if ($foto && method_exists($foto, 'temporaryUrl'))
                                            <div class="mt-4">
                                                <div class="flex items-center gap-4">
                                                    <div class="relative">
                                                        <img src="{{ $foto->temporaryUrl() }}"
                                                            class="h-24 w-24 sm:h-28 sm:w-28 rounded-2xl object-cover ring-1 ring-black/10 dark:ring-white/10 shadow-sm" />

                                                        <div
                                                            class="absolute -bottom-2 -right-2 rounded-full bg-white dark:bg-zinc-900 ring-1 ring-black/10 dark:ring-white/10 px-2 py-1 text-[11px] font-semibold text-zinc-700 dark:text-zinc-200">
                                                            Preview
                                                        </div>
                                                    </div>

                                                    <div class="min-w-0 flex-1">
                                                        <p
                                                            class="text-sm font-semibold text-zinc-900 dark:text-white truncate">
                                                            Imagen seleccionada
                                                        </p>
                                                        <p class="text-xs text-zinc-600 dark:text-zinc-300 mt-0.5">
                                                            Si no te gusta, puedes cambiarla o quitarla.
                                                        </p>

                                                        <div class="mt-3 flex flex-wrap gap-2">
                                                            <button type="button"
                                                                class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold
                                           bg-zinc-900 text-white dark:bg-white dark:text-zinc-900
                                           hover:opacity-90 transition"
                                                                onclick="document.querySelector('input[type=file][wire\\:model=&quot;foto&quot;], input[type=file][wire\\:model=&quot;foto&quot;]').click()">
                                                                Cambiar
                                                            </button>

                                                            <button type="button" wire:click="$set('foto', null)"
                                                                class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold
                                           bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white
                                           ring-1 ring-black/10 dark:ring-white/10 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                                                                Quitar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            {{-- Estado vacío --}}
                                            <div
                                                class="mt-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 ring-1 ring-black/5 dark:ring-white/10 p-3">
                                                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                                                    Aún no has seleccionado una imagen.
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </flux:field>
                            </div>





                            {{-- ====== Datos personales ====== --}}
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <flux:field>
                                    <flux:label badge="Requerido">CURP</flux:label>
                                    <flux:input wire:model.defer="curp" maxlength="18" class="uppercase"
                                        placeholder="18 caracteres (opcional)" />
                                    <flux:error name="curp" />
                                </flux:field>
                                <flux:field>
                                    <flux:label badge="Requerido">Nombre(s)</flux:label>
                                    <flux:input wire:model.defer="nombre" placeholder="Ej. John" />
                                    <flux:error name="nombre" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Apellido paterno</flux:label>
                                    <flux:input wire:model.defer="apellido_paterno" placeholder="Ej. Doe" />
                                    <flux:error name="apellido_paterno" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Apellido materno</flux:label>
                                    <flux:input wire:model.defer="apellido_materno"
                                        placeholder="Ej. Doe (opcional)" />
                                    <flux:error name="apellido_materno" />
                                </flux:field>


                            </div>


                            {{-- ====== Documentos ====== --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                                <flux:field>
                                    <flux:label badge="Requerido">Fecha de nacimiento</flux:label>
                                    <flux:input type="date" wire:model.defer="fecha_nacimiento" />
                                    <flux:error name="fecha_nacimiento" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Género</flux:label>
                                    <flux:select wire:model.defer="genero">
                                        <option value="">Selecciona…</option>
                                        <option value="H">Hombre (H)</option>
                                        <option value="M">Mujer (M)</option>
                                    </flux:select>
                                    <flux:error name="genero" />
                                </flux:field>





                                <flux:field>
                                    <flux:label badge="Opcional">RFC</flux:label>
                                    <flux:input wire:model.defer="rfc" maxlength="13" class="uppercase"
                                        placeholder="13 caracteres (opcional)" />
                                    <flux:error name="rfc" />
                                </flux:field>
                            </div>

                            {{-- ====== Contacto ====== --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                                <flux:field>
                                    <flux:label badge="Opcional">Correo</flux:label>
                                    <flux:input type="email" wire:model.defer="correo"
                                        placeholder="correo@dominio.com (opcional)" />
                                    <flux:error name="correo" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Teléfono móvil</flux:label>
                                    <flux:input wire:model.defer="telefono_movil" maxlength="10" inputmode="numeric"
                                        placeholder="10 dígitos" />
                                    <flux:error name="telefono_movil" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Teléfono fijo</flux:label>
                                    <flux:input wire:model.defer="telefono_fijo" maxlength="10" inputmode="numeric"
                                        placeholder="10 dígitos" />
                                    <flux:error name="telefono_fijo" />
                                </flux:field>
                            </div>

                            {{-- ====== Datos laborales ====== --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                                <flux:field>
                                    <flux:label badge="Opcional">Grado de estudios</flux:label>
                                    <flux:input wire:model.defer="grado_estudios"
                                        placeholder="Ej. Licenciatura, Maestría…" />
                                    <flux:error name="grado_estudios" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Especialidad</flux:label>
                                    <flux:input wire:model.defer="especialidad"
                                        placeholder="Ej. Matemáticas, Español…" />
                                    <flux:error name="especialidad" />
                                </flux:field>
                            </div>


                            {{-- ====== Dirección ====== --}}
                            <div class="mt-8">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">
                                    Dirección (opcional)
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <flux:field class="md:col-span-2">
                                        <flux:label badge="Opcional">Calle</flux:label>
                                        <flux:input wire:model.defer="calle" placeholder="Ej. Av. Insurgentes" />
                                        <flux:error name="calle" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label badge="Opcional">No. exterior</flux:label>
                                        <flux:input wire:model.defer="numero_exterior" placeholder="Ej. 123" />
                                        <flux:error name="numero_exterior" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label badge="Opcional">No. interior</flux:label>
                                        <flux:input wire:model.defer="numero_interior" placeholder="Ej. 4B" />
                                        <flux:error name="numero_interior" />
                                    </flux:field>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                                    <flux:field class="md:col-span-2">
                                        <flux:label badge="Opcional">Colonia</flux:label>
                                        <flux:input wire:model.defer="colonia" placeholder="Ej. Centro" />
                                        <flux:error name="colonia" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label badge="Opcional">Municipio</flux:label>
                                        <flux:input wire:model.defer="municipio" placeholder="Ej. Guadalajara" />
                                        <flux:error name="municipio" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label badge="Opcional">Estado</flux:label>
                                        <flux:input wire:model.defer="estado" placeholder="Ej. Jalisco" />
                                        <flux:error name="estado" />
                                    </flux:field>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                                    <flux:field>
                                        <flux:label badge="Opcional">Código postal</flux:label>
                                        <flux:input wire:model.defer="codigo_postal" maxlength="10"
                                            placeholder="Ej. 44100" />
                                        <flux:error name="codigo_postal" />
                                    </flux:field>
                                </div>
                            </div>


                            {{-- Status --}}
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Define si el personal estará activo en el sistema.
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
