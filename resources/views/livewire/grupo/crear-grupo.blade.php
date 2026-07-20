<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">Crear grupo</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            El ciclo, grado o semestre determinan automáticamente la generación correcta.
        </p>
    </div>

    <form wire:submit.prevent="guardarGrupo" class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1 bg-gradient-to-r from-[#88AC2E] via-[#00a7d6] to-[#006492]"></div>

        <div wire:loading.flex wire:target="ciclo_escolar_id,nivel_id,grado_id,semestre_id,guardarGrupo"
            class="absolute inset-0 z-30 items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-neutral-900/75">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-lg dark:border-neutral-700 dark:bg-neutral-900">
                <svg class="h-5 w-5 animate-spin text-[#006492]" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                </svg>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Validando grupo...</span>
            </div>
        </div>

        <div class="space-y-6 p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar">
                    <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                    @foreach ($ciclosEscolares as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">
                            {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                            @if ($ciclo->es_actual) · Actual @endif
                            @if ($ciclo->cerrado_at) · Cerrado @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <flux:label>Letra o nombre del grupo</flux:label>
                        <flux:button type="button" variant="primary" size="sm"
                            x-on:click="$dispatch('abrir-modal-asignacion-grupo'); Livewire.dispatch('editarModalAsignacionGrupo');">
                            <flux:icon.plus class="h-4 w-4" />
                            Crear o editar
                        </flux:button>
                    </div>
                    <flux:select wire:model="asignacion_grupo_id">
                        <flux:select.option value="">Selecciona un grupo</flux:select.option>
                        @foreach ($asignacionGrupos as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">{{ $grupo->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="asignacion_grupo_id" />
                </div>

                <flux:select wire:model="estado" label="Estado">
                    <flux:select.option value="activo">Activo</flux:select.option>
                    <flux:select.option value="inactivo">Inactivo</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="nivel_id" label="Nivel educativo" :disabled="!$ciclo_escolar_id">
                    <flux:select.option value="">Selecciona un nivel</flux:select.option>
                    @foreach ($niveles as $nivel)
                        <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if (!$esBachillerato)
                    <flux:select wire:model.live="grado_id" label="Grado" :disabled="!$nivel_id">
                        <flux:select.option value="">Selecciona un grado</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}°</flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:select wire:model.live="semestre_id" label="Semestre" :disabled="!$nivel_id">
                        <flux:select.option value="">Selecciona un semestre</flux:select.option>
                        @foreach ($semestres as $semestre)
                            <flux:select.option value="{{ $semestre->id }}">
                                {{ $semestre->numero }}° semestre · {{ $semestre->grado?->nombre }}° grado
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <div>
                    <div class="mb-1 flex items-center gap-2">
                        <flux:label>Generación</flux:label>
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase text-emerald-700">Automática</span>
                    </div>
                    <flux:input value="{{ $generacionCalculada ?: 'Se calculará automáticamente' }}" readonly disabled />
                    @if ($advertenciaAsignacion)
                        <p class="mt-2 text-xs font-semibold text-amber-600">{{ $advertenciaAsignacion }}</p>
                    @endif
                </div>

                <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                    <flux:checkbox
                        wire:model.live="usar_generacion_excepcional"
                        label="Crear grupo con una generación excepcional"
                        description="Úsalo únicamente para no promovidos, reingresos o correcciones autorizadas."
                    />

                    @if ($usar_generacion_excepcional)
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <flux:select wire:model="generacion_excepcional_id" label="Generación excepcional">
                                <flux:select.option value="">Selecciona una generación</flux:select.option>
                                @foreach ($generacionesExcepcionales as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">
                                        {{ $generacion->anio_ingreso }}-{{ $generacion->anio_egreso }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:textarea
                                wire:model="motivo_generacion_excepcional"
                                label="Motivo obligatorio"
                                rows="2"
                                placeholder="Ej. Alumno no promovido que conserva su generación original."
                            />
                        </div>
                    @endif
                </div>
            </div>

            @if ($generacionCalculada)
                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-200">
                    <strong>Vista previa:</strong> ciclo {{ optional($ciclosEscolares->firstWhere('id', (int) $ciclo_escolar_id))->inicio_anio }}-{{ optional($ciclosEscolares->firstWhere('id', (int) $ciclo_escolar_id))->fin_anio }},
                    generación {{ $generacionCalculada }} y cupo ilimitado.
                </div>
            @endif

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" spinner="guardarGrupo">
                    Guardar grupo
                </flux:button>
            </div>
        </div>
    </form>

    <livewire:asignacion-grupo.crear-editar-asignacion-grupo />
</div>
