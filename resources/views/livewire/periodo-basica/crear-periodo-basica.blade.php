<div>
    {{-- ENCABEZADO --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
            Crear periodo básico
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Registra un nuevo periodo básico para la institución.
        </p>
    </div>

    {{-- FORMULARIO --}}
    <form wire:submit.prevent="guardarPeriodoBasico" class="space-y-6">
        <div
            class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm p-5 space-y-5">


            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Ciclo Escolar --}}
                <div>
                    <flux:select wire:model="ciclo_escolar_id" label="Ciclo Escolar"
                        placeholder="Selecciona un ciclo escolar">
                        @foreach ($ciclosEscolares as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">
                                {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Período --}}
                <div>
                    <flux:select wire:model="periodo_id" label="Período" placeholder="Selecciona un período">
                        @foreach ($periodos as $periodo)
                            <flux:select.option value="{{ $periodo->id }}">{{ $periodo->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Parcial Inicio --}}
                <div>
                    <flux:input wire:model="parcial_inicio" type="date" label="Fecha Inicio Parcial" />
                </div>

                {{-- Parcial Fin --}}
                <div>
                    <flux:input wire:model="parcial_fin" type="date" label="Fecha Fin Parcial" />
                </div>

            </div>



        </div>
        {{-- BOTONES --}}
        <div class="flex items-center justify-end gap-3">

            <flux:button type="submit" variant="primary" class="btn-gradient text-xs sm:text-sm"
                spinner="guardarPeriodoBasico">
                Guardar periodo básico
            </flux:button>
        </div>
</div>


</form>
</div>
