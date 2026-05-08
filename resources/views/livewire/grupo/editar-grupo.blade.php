<div x-data="{ show: false, loading: false }" x-cloak x-trap.noscroll="show" x-show="show"
    @abrir-modal-editar.window="show = true; loading = true" @editar-cargado.window="loading = false"
    @cerrar-modal-editar.window="
        show = false;
        loading = false;
        $wire.cerrarModal()
    "
    @keydown.escape.window="show = false; $wire.cerrarModal()" class="fixed inset-0 z-50 flex items-center justify-center"
    aria-live="polite">
    {{-- Overlay --}}
    <div class="absolute inset-0 bg-neutral-900/70 backdrop-blur-sm" x-show="show" x-transition.opacity
        @click.self="show = false; $wire.cerrarModal()"></div>

    {{-- Modal --}}
    <div class="relative mx-4 flex max-h-[85vh] w-[92vw] max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-neutral-900 dark:ring-white/10 sm:mx-6 sm:w-[88vw] md:w-[90vw]"
        role="dialog" aria-modal="true" aria-labelledby="titulo-modal-grupo" x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2" wire:ignore.self>
        {{-- Overlay de carga inicial --}}
        <div x-show="loading" x-transition.opacity
            class="absolute inset-0 z-20 flex items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-neutral-900/80">
            <div class="flex flex-col items-center gap-2">
                <svg class="h-6 w-6 animate-spin text-indigo-600 dark:text-indigo-400"
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

        {{-- Top accent --}}
        <div class="h-1.5 w-full shrink-0 bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>

        {{-- Header --}}
        <div
            class="sticky top-0 z-10 flex items-start justify-between gap-3 bg-white/95 px-5 pt-4 backdrop-blur dark:bg-neutral-900/95 sm:px-6">
            <div class="min-w-0">
                <h2 id="titulo-modal-grupo" class="text-xl font-bold text-neutral-900 dark:text-white sm:text-2xl">
                    Editar Grupo
                </h2>

                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    <span class="inline-flex items-center gap-2">
                        <flux:badge color="indigo">
                            {{ $grado_nombre ?: 'Grado no seleccionado' }}°
                            {{ $nivel_nombre ?: 'Nivel no seleccionado' }}
                            |
                            Grupo: {{ $grupo_nombre ?: 'No definido' }}
                        </flux:badge>
                    </span>
                </p>
            </div>

            <button @click="show = false; $wire.cerrarModal()" type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-zinc-400 dark:hover:bg-neutral-800 dark:hover:text-zinc-200"
                aria-label="Cerrar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form wire:submit.prevent="actualizarGrupo" class="relative">
            <flux:field class="space-y-6 p-4">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    {{-- Grupo --}}
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <flux:label>
                                Grupo
                            </flux:label>

                            <flux:button type="button" variant="primary" size="sm"
                                @click="$dispatch('abrir-modal-asignacion-grupo');
                                        Livewire.dispatch('editarModalAsignacionGrupo');">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="plus" class="h-4 w-4" />
                                    Crear o editar
                                </div>
                            </flux:button>
                        </div>

                        <flux:select wire:model="asignacion_grupo_id" placeholder="Selecciona un grupo">
                            <flux:select.option value="">
                                -- Selecciona un grupo --
                            </flux:select.option>

                            @foreach ($asignacionGrupos as $grupo)
                                <flux:select.option value="{{ $grupo->id }}">
                                    {{ $grupo->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:error name="asignacion_grupo_id" />
                    </div>

                    {{-- Nivel --}}
                    <flux:select wire:model.live="nivel_id" label="Nivel educativo">
                        <flux:select.option value="">
                            -- Selecciona un nivel --
                        </flux:select.option>

                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">
                                {{ $nivel->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- Grado --}}
                    <flux:select wire:model="grado_id" label="Grado" class="uppercase" :disabled="!$nivel_id">
                        @if (!$nivel_id)
                            <flux:select.option value="">
                                Primero selecciona un nivel
                            </flux:select.option>
                        @else
                            <flux:select.option value="">
                                -- Selecciona un grado --
                            </flux:select.option>

                            @foreach ($grados as $grado)
                                <flux:select.option value="{{ $grado->id }}">
                                    {{ $grado->nombre }}° GRADO DE {{ $grado->nivel?->nombre }}
                                </flux:select.option>
                            @endforeach
                        @endif
                    </flux:select>

                    {{-- Generación --}}
                    <flux:select wire:model.live="generacion_id" label="Generación" class="uppercase"
                        :disabled="!$nivel_id">
                        @if (!$nivel_id)
                            <flux:select.option value="">
                                Primero selecciona un nivel
                            </flux:select.option>
                        @else
                            <flux:select.option value="">
                                -- Selecciona una generación --
                            </flux:select.option>

                            @foreach ($generaciones as $generacion)
                                <flux:select.option value="{{ $generacion->id }}">
                                    {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                    DE {{ $generacion->nivel?->nombre }}
                                </flux:select.option>
                            @endforeach
                        @endif
                    </flux:select>

                    {{-- Semestre --}}
                    <flux:select wire:model="semestre_id" label="Semestre" :disabled="!$esBachillerato">
                        @if (!$esBachillerato)
                            <flux:select.option value="">
                                Solo aplica para nivel Bachillerato
                            </flux:select.option>
                        @else
                            <flux:select.option value="">
                                -- Selecciona un semestre --
                            </flux:select.option>

                            @foreach ($semestres as $semestre)
                                <flux:select.option value="{{ $semestre->id }}">
                                    {{ $semestre->numero }}° SEMESTRE
                                </flux:select.option>
                            @endforeach
                        @endif
                    </flux:select>

                </div>

                {{-- Botones --}}
                <div class="mt-6 flex flex-col-reverse items-stretch justify-end gap-2 sm:flex-row sm:items-center">
                    <button @click="show = false; $wire.cerrarModal()" type="button"
                        class="inline-flex justify-center rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-neutral-700 hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-300 focus:ring-offset-2 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700 dark:focus:ring-offset-neutral-900">
                        Cancelar
                    </button>

                    <flux:button variant="primary" type="submit" class="w-full cursor-pointer sm:w-auto"
                        wire:loading.attr="disabled" wire:target="actualizarGrupo">
                        Guardar
                    </flux:button>
                </div>
            </flux:field>

            {{-- Loader al guardar --}}
            <div wire:loading.flex wire:target="actualizarGrupo"
                class="absolute inset-0 z-20 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                <div
                    class="flex items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-xl ring-1 ring-slate-200 dark:bg-neutral-900 dark:ring-neutral-700">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>

                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Guardando cambios...
                    </span>
                </div>
            </div>
        </form>
    </div>

    <livewire:asignacion-grupo.crear-editar-asignacion-grupo />
</div>
