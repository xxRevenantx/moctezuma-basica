<div>
    {{-- ENCABEZADO --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
            Periodos Bachillerato - Crear Nuevo Periodo
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Registra un nuevo periodo bachillerato para la institución.
        </p>
    </div>

    {{-- FORMULARIO --}}
    <form wire:submit.prevent="guardarPeriodoBachillerato" class="space-y-6">
        <div
            class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm p-5 space-y-5">


            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">

                {{-- Ciclo Escolar --}}
                <div>
                    <flux:select wire:model="ciclo_escolar_id" label="Ciclo Escolar">
                        <flux:select.option value="">Selecciona un ciclo escolar</flux:select.option>
                        @foreach ($ciclosEscolares as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">
                                {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Generaciones --}}

                <div>
                    <flux:select wire:model="generacion_id" label="Generación">
                        <flux:select.option value="">Selecciona una generación</flux:select.option>
                        @foreach ($generaciones as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">
                                {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>


                {{-- Semestres --}}
                <div>
                    <flux:select wire:model="semestre_id" label="Semestre">
                        <flux:select.option value="">Selecciona un semestre</flux:select.option>
                        @foreach ($semestres as $semestre)
                            <flux:select.option value="{{ $semestre->id }}">
                                {{ $semestre->numero }}° Semestre
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>


                {{-- Meses --}}
                <div>
                    <flux:select wire:model="mes_id" label="Meses del Periodo">
                        <flux:select.option value="">Selecciona un mes</flux:select.option>
                        @foreach ($meses as $mes)
                            <flux:select.option value="{{ $mes->id }}">{{ $mes->meses }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- INICIO DE SEMESTRE --}}
                <div>
                    <flux:input wire:model="fecha_inicio" type="date" label="Fecha Inicio Semestre" />
                </div>

                {{-- FIN DE SEMESTRE --}}
                <div>
                    <flux:input wire:model="fecha_fin" type="date" label="Fecha Fin Semestre" />
                </div>

            </div>

            {{-- BOTONES --}}
            <div class="flex items-center justify-end gap-3">

                <flux:button type="submit" variant="primary" class="btn-gradient text-xs sm:text-sm"
                    spinner="guardarPeriodoBachillerato">
                    Guardar periodo
                </flux:button>
            </div>

        </div>

</div>


</form>
</div>
