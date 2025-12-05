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
                    Cargando datos del nivel...
                </p>
            </div>
        </div>



        <!-- Top accent -->
        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500 shrink-0"></div>

        <!-- Header (fijo) -->
        <div
            class="px-5 sm:px-6 pt-4  flex items-start justify-between gap-3 sticky top-0 bg-white/95 dark:bg-neutral-900/95 backdrop-blur z-10">
            <div class="min-w-0">
                <h2 id="titulo-modal-generacion" class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white">
                    Editar Nivel
                </h2>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    <span class="inline-flex items-center gap-2">
                        <flux:badge color="indigo">Nivel: {{ $nombre }}
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

        <form wire:submit.prevent="actualizarNivel">
            {{-- Grid de inputs --}}
            <flux:field class="p-4 space-y-6">

                {{-- BLOQUE DE LOGO: OCUPA TODO EL ANCHO --}}

                <div class="w-full">
                    <div
                        class="w-full relative rounded-2xl border border-dashed border-neutral-300 dark:border-neutral-700
               bg-gradient-to-b from-slate-50 to-white dark:from-neutral-900 dark:to-neutral-950
               p-4 shadow-sm">

                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div>
                                <h3 class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">
                                    Logo del nivel
                                </h3>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                    Sube o reemplaza el logotipo que identificará este nivel.
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
                                @if ($logo_nuevo)
                                    {{-- Preview cuando se ha seleccionado un nuevo archivo --}}
                                    <div
                                        class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700
                               bg-white dark:bg-neutral-900 p-2">
                                        <img src="{{ $logo_nuevo->temporaryUrl() }}" alt="Vista previa del logo"
                                            class="w-full h-32 md:h-40 object-contain mx-auto">


                                        <button type="button" wire:click="$set('logo_nuevo', null)"
                                            class="absolute top-2 right-2 inline-flex items-center gap-1 rounded-full
                                       bg-neutral-900/80 text-white text-[11px] px-2 py-1 hover:bg-neutral-900">
                                            Quitar
                                        </button>
                                    </div>
                                @elseif(!empty($logo_actual))
                                    {{-- Logo actual guardado en BD --}}
                                    <div
                                        class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700
                               bg-white dark:bg-neutral-900 p-2">
                                        <img src="{{ asset('logos/' . $logo_actual) }}" alt="Logo actual del nivel"
                                            class="w-full h-32 md:h-40 object-contain mx-auto">
                                        <span
                                            class="absolute bottom-2 left-2 rounded-full bg-black/70 text-white text-[11px] px-2 py-0.5">
                                            Logo actual
                                        </span>
                                    </div>
                                @else
                                    {{-- Estado sin logo --}}
                                    <div
                                        class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed
                               border-neutral-300 dark:border-neutral-700 bg-neutral-50/70 dark:bg-neutral-900/40
                               px-4 py-6 text-center">
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                            Aún no hay logo para este nivel.
                                        </p>
                                    </div>
                                @endif
                            </div>

                            {{-- INPUT DE SUBIDA / REEMPLAZO --}}
                            <div class="w-full space-y-2">
                                <label class="block text-xs font-medium text-neutral-700 dark:text-neutral-200">
                                    Subir / reemplazar logo
                                </label>

                                <label
                                    class="w-full flex flex-col items-center justify-center gap-2 rounded-xl border
                                    border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900
                                    px-4 py-3 cursor-pointer hover:border-sky-500 hover:bg-sky-50/40
                                    dark:hover:bg-sky-900/20 transition">

                                    <input type="file" class="hidden" wire:model="logo_nuevo" {{-- sin .live --}}
                                        accept="image/jpeg,image/jpg,image/png,image/webp">

                                    <span
                                        class="inline-flex items-center gap-2 text-xs font-medium
                                             text-neutral-700 dark:text-neutral-100">
                                        Seleccionar imagen
                                    </span>
                                    <span class="text-[11px] text-neutral-500 dark:text-neutral-400">
                                        JPG, PNG o WEBP · Máx. 2 MB
                                    </span>
                                </label>

                                {{-- PROGRESO DE CARGA --}}
                                {{-- loader --}}
                                <div wire:loading wire:target="logo_nuevo"
                                    class="flex items-center gap-2 text-[11px] text-sky-600 dark:text-sky-300">
                                    <span
                                        class="h-3 w-3 rounded-full border-2 border-t-transparent border-sky-500 animate-spin"></span>
                                    Subiendo imagen...
                                </div>


                            </div>
                        </div>
                    </div>
                </div>


                {{-- DATOS DEL NIVEL --}}
                <div class="w-full space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input badge="Requerido" wire:model="nombre" :label="__('Nombre del nivel')" type="text"
                            placeholder="Ej.: Primaria, Secundaria, Bachillerato" autocomplete="off" />

                        {{-- SLUG (solo lectura, generado automáticamente) --}}
                        <flux:input readonly variant="filled" wire:model="slug" :label="__('Slug')"
                            placeholder="primaria, secundaria, bachillerato"
                            helper-text="Se usa en las rutas/URLs. Debe ser único." />

                        <flux:input label="CCT" placeholder="12PES0105U" wire:model.defer="cct" type="text" />



                        <flux:input badge="Requerido" wire:model="color" :label="__('Color del nivel')"
                            type="color" placeholder="Selecciona un color" />

                        <flux:select wire:model.defer="director_id" label="Seleccione un director"
                            placeholder="Seleccione un director">
                            <flux:select.option value="">--Selecciona un director--</flux:select.option>
                            @foreach ($directores as $director)
                                @php
                                    $nombreCompleto =
                                        $director->nombre .
                                        ' ' .
                                        $director->apellido_paterno .
                                        ' ' .
                                        $director->apellido_materno;
                                @endphp
                                <flux:select.option value="{{ $director->id }}">{{ $nombreCompleto }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.defer="supervisor_id" label="Seleccione un supervisor"
                            placeholder="Seleccione un supervisor">
                            <flux:select.option value="">--Selecciona un supervisor--</flux:select.option>
                            @foreach ($directores as $director)
                                @php
                                    $nombreCompleto =
                                        $director->nombre .
                                        ' ' .
                                        $director->apellido_paterno .
                                        ' ' .
                                        $director->apellido_materno;
                                @endphp
                                <flux:select.option value="{{ $director->id }}">{{ $nombreCompleto }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                    </div>


                </div>

                {{-- BOTONES --}}
                <div class="mt-6 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2">
                    <button @click="show = false; $wire.cerrarModal()" type="button"
                        class="inline-flex justify-center rounded-xl px-4 py-2.5 border border-neutral-200 dark:border-neutral-700
                       bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-100
                       hover:bg-neutral-50 dark:hover:bg-neutral-700
                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-300 dark:focus:ring-offset-neutral-900">
                        Cancelar
                    </button>

                    <flux:button variant="primary" type="submit" class="w-full sm:w-auto cursor-pointer"
                        wire:loading.attr="disabled" wire:target="actualizarNivel">
                        {{ __('Guardar') }}
                    </flux:button>
                </div>
            </flux:field>
        </form>


    </div>
</div>
