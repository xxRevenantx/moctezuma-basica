<div class="space-y-4">
    <form wire:submit.prevent="guardarPeriodo" class="space-y-4">
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">

            <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500"></div>

            <div wire:loading.flex wire:target="guardarPeriodo"
                class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-slate-950/70">
                <div
                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                    <svg class="h-5 w-5 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">
                        Guardando periodo...
                    </span>
                </div>
            </div>

            <div class="space-y-5 p-5">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                        Crear nuevo periodo
                    </h2>

                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        La generación, semestre y mes del periodo solo aplican para bachillerato.
                    </p>

                    @if ((int) $nivel_id === 4)
                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-800/60 dark:bg-emerald-900/20 dark:text-emerald-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Bachillerato seleccionado: generación, semestre y mes habilitados
                        </div>
                    @elseif (!empty($nivel_id))
                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                            Nivel distinto de bachillerato: generación, semestre y mes deshabilitados
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    {{-- Nivel --}}
                    <div>
                        <flux:select wire:model.live="nivel_id" label="Nivel">
                            <flux:select.option value="">Selecciona un nivel</flux:select.option>
                            @foreach ($niveles as $nivel)
                                <flux:select.option value="{{ $nivel->id }}">
                                    {{ $nivel->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Ciclo escolar --}}
                    <div>
                        <flux:select wire:model="ciclo_escolar_id" label="Ciclo escolar">
                            <flux:select.option value="">Selecciona un ciclo escolar</flux:select.option>
                            @foreach ($ciclosEscolares as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
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

                        @if ((int) $nivel_id !== 4)
                            <p class="mt-1 text-xs text-slate-400">
                                Este campo solo aplica para bachillerato.
                            </p>
                        @endif
                    </div>

                    {{-- Mes del periodo --}}
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

                        @if ((int) $nivel_id !== 4)
                            <p class="mt-1 text-xs text-slate-400">
                                Este campo solo aplica para bachillerato.
                            </p>
                        @endif
                    </div>

                    {{-- Fecha inicio --}}
                    <div>
                        <flux:input wire:model="fecha_inicio" type="date" label="Fecha inicio" />
                    </div>

                    {{-- Fecha fin --}}
                    <div>
                        <flux:input wire:model="fecha_fin" type="date" label="Fecha fin" />
                    </div>
                </div>

                <div class="flex items-center justify-end pt-2">
                    <flux:button type="submit" variant="primary" class="px-4 py-2 text-xs sm:text-sm"
                        spinner="guardarPeriodo">
                        Guardar periodo
                    </flux:button>
                </div>
            </div>
        </div>
    </form>
</div>
