<div x-data="{
    preview: null,
    handleLogoChange(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = ev => this.preview = ev.target.result;
        reader.readAsDataURL(file);
    }
}"
    x-on:logo-cleared.window="
        preview = null;
        if ($refs.logoInput) {
            $refs.logoInput.value = null;
        }
    ">
    {{-- ENCABEZADO --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
            Crear Nueva Generación
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Registra una nueva generación indicando su nivel correspondiente.
        </p>
    </div>

    {{-- FORMULARIO --}}
    <form wire:submit.prevent="guardarGeneracion" class="space-y-6">
        <div
            class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm p-5 space-y-5">


            <flux:field>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">


                    <flux:input label="Fecha de inicio" placeholder="2020" wire:model.live="anio_ingreso"
                        type="number" />

                    <flux:input label="Fecha de término" placeholder="2024" wire:model.live="anio_egreso"
                        type="number" />

                    <flux:select wire:model="nivel_id" label="Nivel educativo">
                        <flux:select.option value="">--Selecciona un nivel--</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>




                </div>
            </flux:field>


            {{-- BOTONES --}}
            <div class="flex items-center justify-end gap-3">

                <flux:button type="submit" variant="primary" class="btn-gradient text-xs sm:text-sm"
                    spinner="guardarGeneracion">
                    Guardar Generación
                </flux:button>
            </div>
        </div>


    </form>
</div>
