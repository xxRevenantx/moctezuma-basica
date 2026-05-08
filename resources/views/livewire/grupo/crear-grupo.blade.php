<div>
    {{-- ENCABEZADO --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
            Crear Nuevo grupo
        </h1>

        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Registra un nuevo grupo educativo indicando su nivel correspondiente.
        </p>
    </div>

    {{-- FORMULARIO --}}
    <form wire:submit.prevent="guardarGrupo" class="space-y-6">
        <div
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

                {{-- Grupo --}}
                <div>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <flux:label>
                            Grupo
                        </flux:label>

                        <flux:button type="button" variant="primary" size="sm"
                            @click="$dispatch('abrir-modal-asignacion-grupo');
                                    Livewire.dispatch('editarModalAsignacionGrupo');">
                            <div class="flex items-center gap-2">
                                <flux:icon name="plus" class="h-4 w-4" />
                                Crear o editar
                            </div>
                        </flux:button>
                    </div>

                    <flux:select wire:model="asignacion_grupo_id" placeholder="Selecciona un grupo">
                        <flux:select.option value="">
                            -- Selecciona un grupo --
                        </flux:select.option>

                        @foreach ($asignacionGrupos as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">
                                {{ $grupo->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:error name="asignacion_grupo_id" />
                </div>

                {{-- Nivel --}}
                <flux:select wire:model.live="nivel_id" label="Nivel educativo">
                    <flux:select.option value="">
                        -- Selecciona un nivel --
                    </flux:select.option>

                    @foreach ($niveles as $nivel)
                        <flux:select.option value="{{ $nivel->id }}">
                            {{ $nivel->nombre }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Grado --}}
                <flux:select wire:model="grado_id" label="Grado" :disabled="!$nivel_id">
                    @if (!$nivel_id)
                        <flux:select.option value="">
                            Primero selecciona un nivel
                        </flux:select.option>
                    @else
                        <flux:select.option value="">
                            -- Selecciona un grado --
                        </flux:select.option>

                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">
                                {{ $grado->nombre }}° GRADO
                            </flux:select.option>
                        @endforeach
                    @endif
                </flux:select>

                {{-- Generación --}}
                <flux:select wire:model="generacion_id" label="Generación" :disabled="!$nivel_id">
                    @if (!$nivel_id)
                        <flux:select.option value="">
                            Primero selecciona un nivel
                        </flux:select.option>
                    @else
                        <flux:select.option value="">
                            -- Selecciona una generación --
                        </flux:select.option>

                        @foreach ($generaciones as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">
                                {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                            </flux:select.option>
                        @endforeach
                    @endif
                </flux:select>

                {{-- Semestre --}}
                <flux:select wire:model="semestre_id" label="Semestre" :disabled="!$esBachillerato">
                    @if (!$esBachillerato)
                        <flux:select.option value="">
                            Solo aplica para nivel Bachillerato
                        </flux:select.option>
                    @else
                        <flux:select.option value="">
                            -- Selecciona un semestre --
                        </flux:select.option>

                        @foreach ($semestres as $semestre)
                            <flux:select.option value="{{ $semestre->id }}">
                                {{ $semestre->numero }}° SEMESTRE
                            </flux:select.option>
                        @endforeach
                    @endif
                </flux:select>

            </div>

            {{-- BOTONES --}}
            <div class="mt-6 flex items-center justify-end gap-3">
                <flux:button type="submit" variant="primary" class="btn-gradient text-xs sm:text-sm"
                    spinner="guardarGrupo">
                    Guardar grupo
                </flux:button>
            </div>
        </div>
    </form>

    <livewire:asignacion-grupo.crear-editar-asignacion-grupo />
</div>
