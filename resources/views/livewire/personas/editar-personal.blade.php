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
                    Cargando datos del personal...
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
                    Editar Personal
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
            <form wire:submit.prevent="actualizarPersonal">
                {{-- Grid de inputs --}}
                <flux:field class="p-4">
                    {{-- ====== Foto ====== --}}
                    <div class="w-full">
                        <div
                            class="w-full relative rounded-2xl border border-dashed border-neutral-300 dark:border-neutral-700
               bg-gradient-to-b from-slate-50 to-white dark:from-neutral-900 dark:to-neutral-950
               p-4 shadow-sm">

                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div>
                                    <h3 class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">
                                        Foto de la persona
                                    </h3>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                        Sube o reemplaza la foto que identificará a esta persona.
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full bg-amber-100 text-amber-700
                       dark:bg-amber-900/40 dark:text-amber-200 px-2 py-0.5
                       text-[11px] font-semibold uppercase tracking-wide">
                                    Opcional
                                </span>
                            </div>

                            {{-- CONTENIDO EN DOS COLUMNAS PARA APROVECHAR EL ANCHO --}}
                            <div
                                class="mt-3 grid grid-cols-1 md:grid-cols-[minmax(0,260px)_minmax(0,1fr)] gap-4 items-start">

                                {{-- PREVIEW DEL LOGO --}}
                                <div class="w-full">
                                    @if ($foto_nueva)
                                        {{-- Preview cuando se ha seleccionado un nuevo archivo --}}
                                        <div
                                            class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700
                               bg-white dark:bg-neutral-900 p-2">
                                            <img src="{{ $foto_nueva->temporaryUrl() }}" alt="Vista previa del logo"
                                                class="w-full h-32 md:h-40 object-contain mx-auto">


                                            <button type="button" wire:click="$set('foto_nueva', null)"
                                                class="absolute top-2 right-2 inline-flex items-center gap-1 rounded-full
                                       bg-neutral-900/80 text-white text-[11px] px-2 py-1 hover:bg-neutral-900">
                                                Quitar
                                            </button>
                                        </div>
                                    @elseif(!empty($foto_actual))
                                        {{-- Logo actual guardado en BD --}}

                                        <div
                                            class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700
                               bg-white dark:bg-neutral-900 p-2">
                                            <img src="{{ asset('storage/personal/' . $foto_actual) }}"
                                                alt="Foto actual de la persona"
                                                class="w-full h-32 md:h-40 object-contain mx-auto">
                                            <span
                                                class="absolute bottom-2 left-2 rounded-full bg-black/70 text-white text-[11px] px-2 py-0.5">
                                                Foto actual
                                            </span>
                                        </div>
                                    @else
                                        {{-- Estado sin logo --}}
                                        <div
                                            class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed
                               border-neutral-300 dark:border-neutral-700 bg-neutral-50/70 dark:bg-neutral-900/40
                               px-4 py-6 text-center">
                                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                                Aún no hay foto para esta persona.
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                {{-- INPUT DE SUBIDA / REEMPLAZO --}}
                                <div class="w-full space-y-2">


                                    <label
                                        class="w-full flex flex-col items-center justify-center gap-2 rounded-xl border
                                    border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900
                                    px-4 py-3 cursor-pointer hover:border-sky-500 hover:bg-sky-50/40
                                    dark:hover:bg-sky-900/20 transition">

                                        <input type="file" class="hidden" wire:model="foto_nueva"
                                            {{-- sin .live --}} accept="image/jpeg,image/jpg,image/png,image/webp">

                                        <span
                                            class="inline-flex items-center gap-2 text-xs font-medium
                                             text-neutral-700 dark:text-neutral-100">
                                            Seleccionar una foto
                                        </span>
                                        <span class="text-[11px] text-neutral-500 dark:text-neutral-400">
                                            JPG, PNG o WEBP · Máx. 2 MB
                                        </span>
                                    </label>

                                    {{-- PROGRESO DE CARGA --}}
                                    {{-- loader --}}
                                    <div wire:loading wire:target="foto_nueva"
                                        class="flex items-center gap-2 text-[11px] text-sky-600 dark:text-sky-300">
                                        <span
                                            class="h-3 w-3 rounded-full border-2 border-t-transparent border-sky-500 animate-spin"></span>
                                        Subiendo foto...
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- ====== Datos personales ====== --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label badge="Requerido">CURP</flux:label>
                            <div class="relative">
                                <flux:input wire:model.live.debounce.500ms="curp" maxlength="18" class="uppercase pr-11"
                                    placeholder="18 caracteres" />
                                <flux:error name="curp" />



                                {{-- Overlay sutil tipo “buscando datos” (solo sobre esta zona) --}}
                                <div wire:loading.delay wire:target="consultarCurp"
                                    class="absolute inset-0 rounded-2xl bg-white/65 dark:bg-neutral-900/55 backdrop-blur-[2px]
                   ring-1 ring-black/5 dark:ring-white/10">
                                    <div class="h-full w-full grid place-items-center">
                                        <div
                                            class="flex items-center gap-3 rounded-2xl px-4 py-3
                            bg-white/80 dark:bg-neutral-900/80
                            ring-1 ring-indigo-500/15 dark:ring-indigo-300/15 shadow">
                                            <div class="relative h-5 w-5">
                                                <span
                                                    class="absolute inset-0 animate-ping rounded-full bg-indigo-500/30"></span>
                                                <span class="absolute inset-0 rounded-full bg-indigo-500/60"></span>
                                            </div>
                                            <div class="leading-tight">
                                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                                    Consultando RENAPO…</p>
                                                <p class="text-xs text-zinc-600 dark:text-zinc-300">Esto puede
                                                    tardar unos segundos</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </flux:field>
                        <flux:field>
                            <flux:label badge="Requerido">Título</flux:label>
                            <flux:input wire:model="titulo" placeholder="Ej. Dr. Lic. Profr." />
                            <flux:error name="titulo" />
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
                            <flux:input wire:model.defer="apellido_materno" placeholder="Ej. Doe (opcional)" />
                            <flux:error name="apellido_materno" />
                        </flux:field>


                    </div>


                    {{-- ====== Documentos ====== --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
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
                            <flux:input wire:model.defer="grado_estudios" placeholder="Ej. Licenciatura, Maestría…" />
                            <flux:error name="grado_estudios" />
                        </flux:field>

                        <flux:field>
                            <flux:label badge="Opcional">Especialidad</flux:label>
                            <flux:input wire:model.defer="especialidad" placeholder="Ej. Matemáticas, Español…" />
                            <flux:error name="especialidad" />
                        </flux:field>
                    </div>


                    {{-- ====== Dirección ====== --}}
                    <div class="mt-8">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">
                            Dirección (opcional)
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
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


                    <div
                        class="mt-6 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2">
                        <button @click="show = false; $wire.cerrarModal()" type="button"
                            class="inline-flex justify-center rounded-xl px-4 py-2.5 border border-neutral-200 dark:border-neutral-700
                                   bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-100
                                   hover:bg-neutral-50 dark:hover:bg-neutral-700
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-300 dark:focus:ring-offset-neutral-900">
                            Cancelar
                        </button>

                        <flux:button variant="primary" type="submit" class="w-full sm:w-auto cursor-pointer"
                            wire:loading.attr="disabled" wire:target="actualizarPersonal">
                            {{ __('Guardar') }}
                        </flux:button>
                    </div>
                </flux:field>
            </form>
        </div>
    </div>
</div>
