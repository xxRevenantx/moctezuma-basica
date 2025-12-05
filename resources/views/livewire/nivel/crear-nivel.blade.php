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
            Crear nivel educativo
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Registra un nuevo nivel (preescolar, primaria, secundaria, etc.) indicando su CCT y responsables.
        </p>
    </div>

    {{-- FORMULARIO --}}
    <form wire:submit.prevent="guardarNivel" class="space-y-6">
        <div
            class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm p-5 space-y-5">

            {{-- LOGO --}}
            <flux:field label="Logotipo del nivel" class="space-y-3">
                <div class="flex flex-col sm:flex-row items-center gap-4">
                    {{-- PREVIEW --}}
                    <div
                        class="h-20 w-20 rounded-xl border border-dashed border-indigo-400/70 bg-slate-50 dark:bg-slate-800 overflow-hidden flex items-center justify-center">
                        <template x-if="preview">
                            <img x-bind:src="preview" alt="Logo preview" class="h-full w-full object-cover">
                        </template>

                        <template x-if="!preview">
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 text-center px-2">
                                Sin logo
                            </span>
                        </template>
                    </div>

                    {{-- INPUT FILE --}}
                    <label class="flex-1 cursor-pointer">
                        <div
                            class="w-full rounded-2xl border-2 border-dashed border-indigo-400/70 bg-gradient-to-r from-indigo-50 via-violet-50 to-sky-50
                                   dark:from-slate-900 dark:via-slate-900 dark:to-slate-900
                                   px-4 py-4 flex flex-col sm:flex-row items-center gap-3 hover:shadow-md transition-shadow">
                            <div
                                class="inline-flex h-9 items-center justify-center rounded-full bg-gradient-to-r from-indigo-600 to-violet-600
                                       px-4 text-xs font-medium text-white shadow-md">
                                Seleccionar logo
                            </div>
                            <div
                                class="flex-1 text-xs sm:text-sm text-slate-600 dark:text-slate-300 text-center sm:text-left">
                                JPG, PNG o WEBP. Máx. 2MB.
                            </div>
                        </div>

                        <input type="file" class="hidden" accept="image/*" x-on:change="handleLogoChange"
                            wire:model="logo">
                    </label>
                </div>

                @error('logo')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </flux:field>

            {{-- NOMBRE --}}
            <flux:input label="Nombre del nivel" placeholder="Ej. Licenciatura, Secundaria, Bachillerato"
                wire:model.live="nombre" type="text" autocomplete="off" />


            {{-- SLUG y CCT --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <flux:input readonly variant="filled" label="Slug"
                        placeholder="licenciatura, secundaria, bachillerato" wire:model.live="slug" type="text"
                        helper-text="Se usa en las rutas/URLs. Debe ser único." />

                </div>

                <div>
                    <flux:input label="CCT" placeholder="12PES0105U" wire:model.defer="cct" type="text" />

                </div>
            </div>

            {{-- DIRECTOR Y SUPERVISOR --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
                        Director(a)
                    </label>

                    <flux:select wire:model.defer="director_id" placeholder="Seleccione un director">
                        <flux:select.option value="">--Selecciona un director--</flux:select.option>
                        @foreach ($directores as $director)
                            @php
                                $nombreCompleto =
                                    $director->nombre .
                                    ' ' .
                                    $director->apellido_paterno .
                                    ' ' .
                                    $director->apellido_materno;
                            @endphp
                            <flux:select.option value="{{ $director->id }}">{{ $nombreCompleto }}</flux:select.option>
                        @endforeach
                    </flux:select>

                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
                        Supervisor(a)
                    </label>
                    <flux:select wire:model.defer="supervisor_id" placeholder="Seleccione un supervisor">
                        <flux:select.option value="">--Selecciona un supervisor--</flux:select.option>
                        @foreach ($directores as $director)
                            @php
                                $nombreCompleto =
                                    $director->nombre .
                                    ' ' .
                                    $director->apellido_paterno .
                                    ' ' .
                                    $director->apellido_materno;
                            @endphp
                            <flux:select.option value="{{ $director->id }}">{{ $nombreCompleto }}</flux:select.option>
                        @endforeach
                    </flux:select>

                </div>

                <flux:input class="w-25" label="Color" wire:model.defer="color" type="color" value="#4ac841" />

            </div>
            {{-- BOTONES --}}
            <div class="flex items-center justify-end gap-3">



                <flux:button type="submit" variant="primary" class="btn-gradient text-xs sm:text-sm"
                    spinner="guardarNivel">
                    Guardar nivel
                </flux:button>
            </div>
        </div>


    </form>
</div>
