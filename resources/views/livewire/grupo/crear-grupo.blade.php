<div>
    {{-- ENCABEZADO --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
            Crear Nuevo grupo
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Registra un nuevo grupo educativo indicando su nivel correspondiente.
        </p>
    </div>

    {{-- FORMULARIO --}}
    <form wire:submit.prevent="guardarGrupo" class="space-y-6">
        <div
            class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm p-5 space-y-5">

            <flux:field>
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-4">

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
                    <flux:select wire:model="grado_id" label="Grado" :disabled="!$nivel_id">
                        @if (!$nivel_id)
                            <flux:select.option value="">
                                Primero selecciona un nivel
                            </flux:select.option>
                        @else
                            <flux:select.option value="">--Selecciona un grado--</flux:select.option>
                            @foreach ($grados as $grado)
                                <flux:select.option value="{{ $grado->id }}">
                                    {{ $grado->nombre }}° GRADO
                                </flux:select.option>
                            @endforeach
                        @endif
                    </flux:select>

                    {{-- Generación (filtrada por nivel) --}}
                    <flux:select wire:model="generacion_id" label="Generación" :disabled="!$nivel_id">
                        @if (!$nivel_id)
                            <flux:select.option value="">
                                Primero selecciona un nivel
                            </flux:select.option>
                        @else
                            <flux:select.option value="">--Selecciona una generación--</flux:select.option>
                            @foreach ($generaciones as $generacion)
                                <flux:select.option value="{{ $generacion->id }}">
                                    {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                </flux:select.option>
                            @endforeach
                        @endif
                    </flux:select>

                    {{-- Semestre (solo para nivel Bachillerato) --}}
                    <flux:select wire:model="semestre_id" label="Semestre" :disabled="!$esBachillerato">
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
            </flux:field>

            {{-- BOTONES --}}
            <div class="flex items-center justify-end gap-3">
                <flux:button type="submit" variant="primary" class="btn-gradient text-xs sm:text-sm"
                    spinner="guardarGrupo">
                    Guardar grupo
                </flux:button>
            </div>
        </div>
    </form>
</div>
