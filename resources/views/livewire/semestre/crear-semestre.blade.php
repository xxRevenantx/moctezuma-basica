<div>
    {{-- ENCABEZADO --}}
    <div class="mb-3">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
            Crear Nuevo Semestre
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Registra un nuevo semestre indicando su nivel correspondiente.
        </p>
    </div>

    <!-- Nota SOLO bachillerato -->
    <div
        class="mb-3 w-full inline-flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/30 dark:text-amber-100 text-xs sm:text-sm">
        <span
            class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-[11px] font-semibold text-amber-800 dark:bg-amber-800/70 dark:text-amber-50">
            i
        </span>
        <span>
            <span class="font-semibold">Nota:</span>
            Este módulo de semestres aplica <span class="font-semibold uppercase">únicamente</span> para el
            nivel <span class="font-semibold">Bachillerato</span>.
        </span>
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
