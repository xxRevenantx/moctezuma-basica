<div>
    <!-- Header -->
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Crear el personal</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Formulario para crear nuevo personal.</p>
    </div>

    <div x-data="{ open: false }" class="my-4">
        <!-- Toggle -->
        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="panel-personal"
            class="group inline-flex items-center gap-2 rounded-2xl px-4 py-2.5
                   bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400
                   dark:focus:ring-offset-neutral-900">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-white/15">
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

        <!-- Loading overlay (para catálogos/guardar) -->
        <div class="pointer-events-none relative" aria-live="polite">
            <div wire:loading
                class="pointer-events-auto absolute inset-0 z-30 rounded-3xl bg-white/60 backdrop-blur-sm
                           dark:bg-neutral-950/50">
                <div class="grid h-full place-items-center p-6">
                    <div
                        class="w-full max-w-sm rounded-3xl border border-neutral-200 bg-white p-5 shadow-xl
                                    dark:border-neutral-800 dark:bg-neutral-900">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 animate-spin rounded-full border-4 border-neutral-200 border-t-neutral-900 dark:border-neutral-700 dark:border-t-white">
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-neutral-900 dark:text-white">Cargando…</div>
                                <div class="text-xs text-neutral-600 dark:text-neutral-400">Procesando información.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel -->
        <div id="panel-personal" x-show="open" x-cloak x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative mt-4">

            <form wire:submit.prevent="crearPersonal" class="group">
                <div
                    class="relative rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-800 shadow-lg overflow-hidden">
                    <!-- Accent top -->
                    <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>

                    <div class="p-5 sm:p-6 lg:p-8">

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

                        <flux:field>

                            {{-- ====== Foto (opcional) dentro de un collapse ====== --}}
                            <div x-data="{ openFoto: false }" class="my-6">
                                {{-- Toggle --}}
                                <button type="button" @click="openFoto = !openFoto" :aria-expanded="openFoto"
                                    aria-controls="panel-foto"
                                    class="group inline-flex w-full items-center justify-between gap-3 rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-left shadow-sm
               hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/25
               dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800/60">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 text-white shadow ring-1 ring-white/15">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7.414a2 2 0 00-.586-1.414l-2.414-2.414A2 2 0 0015.586 3H4zm6 5a3 3 0 100 6 3 3 0 000-6z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </span>

                                        <div class="leading-tight">
                                            <div class="text-sm font-semibold text-neutral-900 dark:text-white">
                                                Fotografía</div>
                                            <div class="text-xs text-neutral-600 dark:text-neutral-400">Sube una foto
                                                (JPG/PNG). Opcional.</div>
                                        </div>
                                    </div>

                                    <span class="flex items-center gap-2">
                                        @if ($foto && method_exists($foto, 'temporaryUrl'))
                                            <span
                                                class="hidden sm:inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-700
                           dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200">
                                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                                Lista
                                            </span>
                                        @else
                                            <span
                                                class="hidden sm:inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-[11px] font-semibold text-neutral-700
                           dark:border-neutral-800 dark:bg-neutral-950/30 dark:text-neutral-200">
                                                <span class="h-2 w-2 rounded-full bg-neutral-400"></span>
                                                Sin foto
                                            </span>
                                        @endif

                                        <span class="transition-transform duration-200"
                                            :class="openFoto ? 'rotate-180' : 'rotate-0'">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-5 w-5 text-neutral-700 dark:text-neutral-200"
                                                viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 15.5l-6-6h12l-6 6z" />
                                            </svg>
                                        </span>
                                    </span>
                                </button>

                                {{-- Panel --}}
                                <div id="panel-foto" x-show="openFoto" x-cloak
                                    x-transition:enter="transition ease-out duration-250"
                                    x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="mt-4">

                                    <flux:field>
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                            {{-- Dropzone / Uploader --}}
                                            <div class="lg:col-span-2">
                                                <div
                                                    class="relative overflow-hidden rounded-3xl border border-dashed border-neutral-300 bg-white p-6 shadow-sm
                               dark:border-neutral-700 dark:bg-neutral-900">

                                                    {{-- Decoración (blobs) --}}
                                                    <div class="absolute inset-0 pointer-events-none">
                                                        <div
                                                            class="absolute -top-20 -right-20 h-56 w-56 rounded-full bg-gradient-to-br from-indigo-500/20 via-violet-500/15 to-fuchsia-500/20 blur-2xl">
                                                        </div>
                                                        <div
                                                            class="absolute -bottom-20 -left-20 h-56 w-56 rounded-full bg-gradient-to-tr from-sky-500/18 via-blue-600/12 to-indigo-600/18 blur-2xl">
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="relative flex flex-col items-center justify-center text-center gap-3">
                                                        <div
                                                            class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 text-white shadow ring-1 ring-white/15">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                                                                viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd"
                                                                    d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7.414a2 2 0 00-.586-1.414l-2.414-2.414A2 2 0 0015.586 3H4zm6 5a3 3 0 100 6 3 3 0 000-6z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        </div>

                                                        <div class="space-y-1">
                                                            <div
                                                                class="text-sm font-semibold text-neutral-900 dark:text-white">
                                                                Arrastra y suelta tu imagen aquí
                                                            </div>
                                                            <div
                                                                class="text-xs text-neutral-600 dark:text-neutral-400">
                                                                o selecciona un archivo desde tu equipo
                                                            </div>
                                                        </div>

                                                        <div class="w-full max-w-sm">
                                                            <input type="file" wire:model="foto"
                                                                accept="image/png,image/jpeg,image/jpg"
                                                                class="block w-full cursor-pointer rounded-2xl border border-neutral-200 bg-white px-4 py-2 text-sm text-neutral-700 shadow-sm
                                           file:mr-4 file:rounded-xl file:border-0 file:bg-neutral-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white
                                           hover:file:bg-neutral-800
                                           dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-200 dark:file:bg-white dark:file:text-neutral-900 dark:hover:file:bg-neutral-200" />

                                                            <div
                                                                class="mt-2 text-[11px] text-neutral-500 dark:text-neutral-400">
                                                                Formatos: JPG/PNG. Recomendado: 600×600+. Tamaño
                                                                sugerido: ≤ 2MB.
                                                            </div>

                                                            <flux:error name="foto" />
                                                        </div>

                                                        {{-- Mini estado de carga --}}
                                                        <div wire:loading wire:target="foto"
                                                            class="mt-2 inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white/80 px-3 py-1 text-xs text-neutral-700 shadow-sm
                                       dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-200">
                                                            <span
                                                                class="h-4 w-4 animate-spin rounded-full border-2 border-neutral-200 border-t-neutral-900 dark:border-neutral-700 dark:border-t-white"></span>
                                                            Subiendo foto…
                                                        </div>
                                                    </div>

                                                    {{-- Overlay de carga (tipo inscripción) --}}
                                                    <div class="pointer-events-none relative" aria-live="polite">
                                                        <div wire:loading wire:target="foto"
                                                            class="pointer-events-auto absolute inset-0 z-30 rounded-3xl bg-white/60 backdrop-blur-sm dark:bg-neutral-950/50">
                                                            <div class="grid h-full place-items-center p-6">
                                                                <div
                                                                    class="w-full max-w-sm rounded-3xl border border-neutral-200 bg-white p-5 shadow-xl
                                               dark:border-neutral-800 dark:bg-neutral-900">
                                                                    <div class="flex items-center gap-3">
                                                                        <div
                                                                            class="h-10 w-10 animate-spin rounded-full border-4 border-neutral-200 border-t-neutral-900 dark:border-neutral-700 dark:border-t-white">
                                                                        </div>
                                                                        <div>
                                                                            <div
                                                                                class="text-sm font-semibold text-neutral-900 dark:text-white">
                                                                                Cargando…
                                                                            </div>
                                                                            <div
                                                                                class="text-xs text-neutral-600 dark:text-neutral-400">
                                                                                Subiendo fotografía.
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                            {{-- Preview --}}
                                            <div class="lg:col-span-1">
                                                <div
                                                    class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                                    <div class="flex items-center justify-between">
                                                        <div
                                                            class="text-sm font-semibold text-neutral-900 dark:text-white">
                                                            Vista previa</div>

                                                        @if ($foto && method_exists($foto, 'temporaryUrl'))
                                                            <span
                                                                class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-700
                                           dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200">
                                                                <span
                                                                    class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                                                Lista
                                                            </span>
                                                        @else
                                                            <span
                                                                class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-[11px] font-semibold text-neutral-700
                                           dark:border-neutral-800 dark:bg-neutral-950/30 dark:text-neutral-200">
                                                                <span
                                                                    class="h-2 w-2 rounded-full bg-neutral-400"></span>
                                                                Sin foto
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="mt-4">
                                                        <div class="mx-auto w-32 sm:w-40">
                                                            <div
                                                                class="relative aspect-square overflow-hidden rounded-3xl bg-neutral-100 ring-1 ring-neutral-200 dark:bg-neutral-800/40 dark:ring-neutral-800">
                                                                @if ($foto && method_exists($foto, 'temporaryUrl'))
                                                                    <img src="{{ $foto->temporaryUrl() }}"
                                                                        alt="Vista previa"
                                                                        class="h-full w-full object-cover" />
                                                                @else
                                                                    <div
                                                                        class="grid h-full place-items-center p-3 text-center">
                                                                        <div class="space-y-2">
                                                                            <div
                                                                                class="mx-auto h-10 w-10 rounded-2xl bg-neutral-900 text-white grid place-items-center dark:bg-white dark:text-neutral-900">
                                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                                    class="h-5 w-5"
                                                                                    viewBox="0 0 20 20"
                                                                                    fill="currentColor">
                                                                                    <path fill-rule="evenodd"
                                                                                        d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7.414a2 2 0 00-.586-1.414l-2.414-2.414A2 2 0 0015.586 3H4zm6 5a3 3 0 100 6 3 3 0 000-6z"
                                                                                        clip-rule="evenodd" />
                                                                                </svg>
                                                                            </div>
                                                                            <div
                                                                                class="text-xs font-semibold text-neutral-900 dark:text-white">
                                                                                Aún no hay imagen
                                                                            </div>
                                                                            <div
                                                                                class="text-[11px] text-neutral-600 dark:text-neutral-400">
                                                                                Cuando subas una foto, aquí verás la
                                                                                vista previa.
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>


                                                </div>
                                            </div>
                                        </div>
                                    </flux:field>
                                </div>
                            </div>



                            {{-- ====== Datos personales ====== --}}
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

                                {{-- CURP --}}
                                <flux:field>
                                    <flux:label badge="Requerido">CURP</flux:label>

                                    <div class="relative">
                                        <flux:input wire:model.live.debounce.600ms="curp" maxlength="18"
                                            class="uppercase" placeholder="18 caracteres" />

                                        <flux:error name="curp" />

                                        @if ($curpError)
                                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $curpError }}</p>
                                        @endif
                                    </div>
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Título</flux:label>
                                    <flux:input wire:model.defer="titulo" placeholder="Ej. Lic, Profr, Dr" />
                                    <flux:error name="titulo" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Nombre(s)</flux:label>
                                    <flux:input wire:model.defer="nombre" placeholder="Ej. Juan" />
                                    <flux:error name="nombre" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Apellido paterno</flux:label>
                                    <flux:input wire:model.defer="apellido_paterno" placeholder="Ej. Pérez" />
                                    <flux:error name="apellido_paterno" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Apellido materno</flux:label>
                                    <flux:input wire:model.defer="apellido_materno"
                                        placeholder="Ej. López (opcional)" />
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
                                        placeholder="RFC (10-13 caracteres)" />
                                    <flux:error name="rfc" />
                                </flux:field>
                            </div>

                            {{-- ====== Contacto ====== --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                                <flux:field>
                                    <flux:label badge="Opcional">Correo</flux:label>
                                    <flux:input type="email" wire:model.defer="correo"
                                        placeholder="correo@dominio.com" />
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
                                        <flux:input wire:model.defer="municipio" placeholder="Ej. Pungarabato" />
                                        <flux:error name="municipio" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label badge="Opcional">Estado</flux:label>
                                        <flux:input wire:model.defer="estado" placeholder="Ej. Guerrero" />
                                        <flux:error name="estado" />
                                    </flux:field>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                                    <flux:field>
                                        <flux:label badge="Opcional">Código postal</flux:label>
                                        <flux:input wire:model.defer="codigo_postal" maxlength="10"
                                            placeholder="Ej. 40662" />
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

                        <!-- Acciones -->
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
                                wire:target="crearPersonal">
                                {{ __('Guardar') }}
                            </flux:button>
                        </div>
                    </div>

                    <!-- Loader overlay Guardando -->
                    <div wire:loading.delay wire:target="crearPersonal"
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
