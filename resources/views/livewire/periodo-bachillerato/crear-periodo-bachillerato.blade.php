<div class="space-y-4">
    {{-- FORMULARIO --}}
    <form wire:submit.prevent="guardarPeriodoBachillerato" class="space-y-4">
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm">
            {{-- Acabado superior --}}
            <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500"></div>

            <div class="p-5 space-y-5">
                {{-- Encabezado del formulario --}}
                <div class="flex flex-col gap-1">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                        Crear nuevo periodo
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Completa la información del ciclo escolar, generación, semestre y fechas del periodo.
                    </p>
                </div>

                {{-- Campos --}}
                <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
                    {{-- Ciclo Escolar --}}
                    <div class="sm:col-span-3">
                        <flux:select wire:model="ciclo_escolar_id" label="Ciclo Escolar">
                            <flux:select.option value="">Selecciona un ciclo escolar</flux:select.option>
                            @foreach ($ciclosEscolares as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Generación --}}
                    <div class="sm:col-span-3">
                        <flux:select wire:model="generacion_id" label="Generación">
                            <flux:select.option value="">Selecciona una generación</flux:select.option>
                            @foreach ($generaciones as $generacion)
                                <flux:select.option value="{{ $generacion->id }}">
                                    {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Semestre --}}
                    <div class="sm:col-span-2">
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
                    <div class="sm:col-span-2">
                        <flux:select wire:model="mes_id" label="Mes del periodo">
                            <flux:select.option value="">Selecciona un mes</flux:select.option>
                            @foreach ($meses as $mes)
                                <flux:select.option value="{{ $mes->id }}">{{ $mes->meses }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Inicio de semestre --}}
                    <div class="sm:col-span-1">
                        <flux:input wire:model="fecha_inicio" type="date" label="Fecha inicio" />
                    </div>

                    {{-- Fin de semestre --}}
                    <div class="sm:col-span-1">
                        <flux:input wire:model="fecha_fin" type="date" label="Fecha fin" />
                    </div>
                </div>

                {{-- Botones --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <flux:button type="submit" variant="primary" class="btn-gradient text-xs sm:text-sm px-4 py-2"
                        spinner="guardarPeriodoBachillerato">
                        Guardar periodo
                    </flux:button>
                </div>
            </div>
        </div>
    </form>
</div>
