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
                    Cargando datos del grupo...
                </p>
            </div>
        </div>



        <!-- Top accent -->
        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500 shrink-0"></div>

        <!-- Header (fijo) -->
        <div
            class="px-5 sm:px-6 pt-4  flex items-start justify-between gap-3 sticky top-0 bg-white/95 dark:bg-neutral-900/95 backdrop-blur z-10">
            <div class="min-w-0">
                <h2 id="titulo-modal-grupo" class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white">
                    Editar Grupo
                </h2>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    <span class="inline-flex items-center gap-2">
                        <flux:badge color="indigo">
                            {{ $grado_nombre ?? 'Grado no seleccionado' }}°
                            {{ $nivel_nombre ?? 'Nivel no seleccionado' }} | Grupo: {{ $nombre ?? 'No definido' }}
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

        <form wire:submit.prevent="actualizarGrupo">
            {{-- Grid de inputs --}}
            <flux:field class="p-4 space-y-6">


                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- {{ $nivel_id }} --}}

                    {{-- Nombre del grupo --}}
                    <flux:input label="Nombre" placeholder="A, B, C, D..." wire:model="nombre" type="text" />

                    {{-- Nivel --}}
                    <flux:select wire:model.live="nivel_id" label="Nivel educativo">
                        <flux:select.option value="">--Selecciona un nivel--</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">
                                {{ $nivel->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>


                    {{-- Grado (filtrado por nivel) --}}
                    <flux:select wire:model="grado_id" label="Grado" class="uppercase">
                        <flux:select.option value="">--Selecciona un grado--</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">
                                {{ $grado->nombre }}° GRADO DE {{ $grado->nivel->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- Generación (filtrada por nivel) --}}
                    <flux:select wire:model.live="generacion_id" label="Generación" class="uppercase">

                        <flux:select.option value="">--Selecciona una generación--</flux:select.option>
                        @foreach ($generaciones as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">
                                {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }} DE
                                {{ $generacion->nivel->nombre }}
                            </flux:select.option>
                        @endforeach

                    </flux:select>

                    {{-- Semestre (solo para nivel Bachillerato) --}}
                    <flux:select wire:model="semestre_id" label="Semestre">
                        @if (!$esBachillerato)
                            <flux:select.option value="">
                                Solo aplica para nivel Bachillerato
                            </flux:select.option>
                        @else
                            <flux:select.option value="">--Selecciona un semestre--</flux:select.option>
                            @foreach ($semestres as $semestre)
                                <flux:select.option value="{{ $semestre->id }}">
                                    {{ $semestre->numero }}° SEMESTRE
                                </flux:select.option>
                            @endforeach
                        @endif
                    </flux:select>



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
                        wire:loading.attr="disabled" wire:target="actualizarGrupo">
                        {{ __('Guardar') }}
                    </flux:button>
                </div>
            </flux:field>
        </form>


    </div>
</div>
