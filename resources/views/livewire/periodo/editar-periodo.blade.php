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
        role="dialog" aria-modal="true" aria-labelledby="titulo-modal-periodo" x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2" wire:ignore.self>

        {{-- Overlay de carga --}}
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
                    Cargando datos del periodo...
                </p>
            </div>
        </div>

        {{-- Top accent --}}
        <div class="h-1.5 w-full shrink-0 bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>

        {{-- Header --}}
        <div
            class="sticky top-0 z-10 flex items-start justify-between gap-3 bg-white/95 px-5 pt-4 backdrop-blur dark:bg-neutral-900/95 sm:px-6">
            <div class="min-w-0">
                <h2 id="titulo-modal-periodo" class="text-xl font-bold text-neutral-900 dark:text-white sm:text-2xl">
                    Editar periodo
                </h2>

                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    <span class="inline-flex items-center gap-2">
                        <flux:badge color="indigo">
                            Periodo: {{ $periodo_nombre ?? '—' }}
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

        <form wire:submit.prevent="actualizarPeriodoBachillerato">
            <flux:field class="space-y-6 p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Nivel --}}
                    <div class="sm:col-span-2">
                        <flux:select wire:model.live="nivel_id" label="Nivel">
                            <flux:select.option value="">Selecciona un nivel</flux:select.option>

                            @foreach ($niveles as $nivel)
                                <flux:select.option value="{{ $nivel->id }}">
                                    {{ $nivel->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:error name="nivel_id" />
                    </div>

                    {{-- Ciclo escolar --}}
                    <div>
                        <flux:select wire:model="ciclo_escolar_id" label="Ciclo escolar">
                            <flux:select.option value="">Selecciona un ciclo escolar</flux:select.option>

                            @foreach ($ciclosEscolares as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:error name="ciclo_escolar_id" />
                    </div>

                    {{-- Generación --}}
                    <div class="{{ (int) $nivel_id !== 4 ? 'opacity-60' : '' }}">
                        <flux:select wire:model="generacion_id" label="Generación" :disabled="(int) $nivel_id !== 4">
                            <flux:select.option value="">Selecciona una generación</flux:select.option>

                            @foreach ($generaciones as $generacion)
                                <flux:select.option value="{{ $generacion->id }}">
                                    {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:error name="generacion_id" />

                        @if ((int) $nivel_id !== 4)
                            <p class="mt-1 text-xs text-slate-400">
                                Este campo solo aplica para bachillerato.
                            </p>
                        @endif
                    </div>

                    {{-- Semestre --}}
                    <div class="{{ (int) $nivel_id !== 4 ? 'opacity-60' : '' }}">
                        <flux:select wire:model="semestre_id" label="Semestre" :disabled="(int) $nivel_id !== 4">
                            <flux:select.option value="">Selecciona un semestre</flux:select.option>

                            @foreach ($semestres as $semestre)
                                <flux:select.option value="{{ $semestre->id }}">
                                    {{ $semestre->numero }}° Semestre
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:error name="semestre_id" />

                        @if ((int) $nivel_id !== 4)
                            <p class="mt-1 text-xs text-slate-400">
                                Este campo solo aplica para bachillerato.
                            </p>
                        @endif
                    </div>

                    {{-- Mes --}}
                    <div class="{{ (int) $nivel_id !== 4 ? 'opacity-60' : '' }}">
                        <flux:select wire:model="mes_bachillerato_id" label="Mes del periodo"
                            :disabled="(int) $nivel_id !== 4">
                            <flux:select.option value="">Selecciona un mes</flux:select.option>

                            @foreach ($meses as $mes)
                                <flux:select.option value="{{ $mes->id }}">
                                    {{ $mes->meses }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:error name="mes_bachillerato_id" />

                        @if ((int) $nivel_id !== 4)
                            <p class="mt-1 text-xs text-slate-400">
                                Este campo solo aplica para bachillerato.
                            </p>
                        @endif
                    </div>

                    {{-- Parcial --}}
                    <div class="{{ (int) $nivel_id !== 4 ? 'opacity-60' : '' }}">
                        <flux:select wire:model="parcial_bachillerato_id" label="Parcial"
                            :disabled="(int) $nivel_id !== 4">
                            <flux:select.option value="">Selecciona un parcial</flux:select.option>

                            @foreach ($parciales as $parcial)
                                <flux:select.option value="{{ $parcial->id }}">
                                    {{ $parcial->descripcion }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:error name="parcial_id" />

                        @if ((int) $nivel_id !== 4)
                            <p class="mt-1 text-xs text-slate-400">
                                Este campo solo aplica para bachillerato.
                            </p>
                        @endif
                    </div>

                    {{-- Fecha inicio --}}
                    <div>
                        <flux:input wire:model="fecha_inicio" type="date" label="Fecha inicio" />
                        <flux:error name="fecha_inicio" />
                    </div>

                    {{-- Fecha fin --}}
                    <div>
                        <flux:input wire:model="fecha_fin" type="date" label="Fecha fin" />
                        <flux:error name="fecha_fin" />
                    </div>
                </div>

                {{-- Botones --}}
                <div class="mt-6 flex flex-col-reverse items-stretch justify-end gap-2 sm:flex-row sm:items-center">
                    <button @click="show = false; $wire.cerrarModal()" type="button"
                        class="inline-flex justify-center rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-neutral-700 hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-300 focus:ring-offset-2 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700 dark:focus:ring-offset-neutral-900">
                        Cancelar
                    </button>

                    <flux:button variant="primary" type="submit" class="w-full cursor-pointer sm:w-auto"
                        wire:loading.attr="disabled" wire:target="actualizarPeriodoBachillerato">
                        Guardar
                    </flux:button>
                </div>
            </flux:field>
        </form>
    </div>
</div>
