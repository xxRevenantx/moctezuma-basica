<div>
    {{-- ENCABEZADO --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
            Crear Nuevo Semestre
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Registra un nuevo semestre indicando su nivel correspondiente.
        </p>
    </div>

    {{-- FORMULARIO --}}
    <form wire:submit.prevent="guardarSemestre" class="space-y-6">
        <div
            class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm p-5 space-y-5">


            <flux:field>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">


                    <flux:input label="Semestre" placeholder="Primero, segundo, tercero" wire:model.live="numero"
                        type="number" />

                    <flux:select wire:model="grado_id" label="Grado educativo">
                        <flux:select.option value="">--Selecciona un grado--</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }} ° GRADO
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="mes_id" label="Meses de Bachillerato">
                        <flux:select.option value="">--Selecciona los meses--</flux:select.option>
                        @foreach ($mesesBachilleratos as $meses)
                            <flux:select.option value="{{ $meses->id }}">{{ $meses->meses }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>




                </div>
            </flux:field>


            {{-- BOTONES --}}
            <div class="flex items-center justify-end gap-3">

                <flux:button type="submit" variant="primary" class="btn-gradient text-xs sm:text-sm"
                    spinner="guardarGrado">
                    Guardar Semestre
                </flux:button>
            </div>
        </div>


    </form>
</div>
