<div x-data="{ show:false, loading:false, abrir(){this.show=true;this.loading=true}, cerrar(){if(this.loading)return;this.show=false;$wire.cerrarModal()} }"
    x-cloak x-trap.noscroll="show" x-show="show" x-on:abrir-modal-editar.window="abrir()"
    x-on:editar-cargado.window="loading=false" x-on:cerrar-modal-editar.window="show=false;loading=false"
    x-on:keydown.escape.window="cerrar()" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" x-on:click.self="cerrar()"></div>

    <div class="relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] via-[#00a7d6] to-[#88AC2E]"></div>

        <div x-show="loading" class="absolute inset-0 z-40 flex items-center justify-center bg-white/90 dark:bg-neutral-900/90">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 px-5 py-3 shadow dark:border-neutral-700">
                <svg class="h-5 w-5 animate-spin text-[#006492]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path></svg>
                <span class="font-bold">Cargando grupo...</span>
            </div>
        </div>

        <form wire:submit.prevent="actualizarGrupo" class="flex min-h-0 flex-1 flex-col">
            <header class="flex items-start justify-between border-b border-slate-100 px-6 py-5 dark:border-neutral-800">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Editar grupo</h2>
                    <p class="mt-1 text-sm text-slate-500">La generación se recalcula con el ciclo y el grado o semestre.</p>
                </div>
                <button type="button" x-on:click="cerrar()" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-neutral-800">✕</button>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto p-6">
                @if ($tieneInscripciones || $esGeneracionExcepcional)
                    <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
                        {{ $esGeneracionExcepcional ? 'Este grupo utiliza una generación excepcional y conserva su estructura documentada.' : 'Este grupo ya tiene alumnos. Puedes cambiar su nombre o estado, pero no su ciclo, nivel, grado, generación o semestre.' }}
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar" :disabled="$tieneInscripciones || $esGeneracionExcepcional">
                        <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                        @foreach ($ciclosEscolares as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <flux:label>Letra o nombre</flux:label>
                            <flux:button type="button" variant="primary" size="sm" x-on:click="$dispatch('abrir-modal-asignacion-grupo'); Livewire.dispatch('editarModalAsignacionGrupo');">Administrar</flux:button>
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

                    <flux:select wire:model.live="nivel_id" label="Nivel educativo" :disabled="$tieneInscripciones || $esGeneracionExcepcional">
                        <flux:select.option value="">Selecciona un nivel</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    @if (!$esBachillerato)
                        <flux:select wire:model.live="grado_id" label="Grado" :disabled="!$nivel_id || $tieneInscripciones || $esGeneracionExcepcional">
                            <flux:select.option value="">Selecciona un grado</flux:select.option>
                            @foreach ($grados as $grado)
                                <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}°</flux:select.option>
                            @endforeach
                        </flux:select>
                    @else
                        <flux:select wire:model.live="semestre_id" label="Semestre" :disabled="!$nivel_id || $tieneInscripciones || $esGeneracionExcepcional">
                            <flux:select.option value="">Selecciona un semestre</flux:select.option>
                            @foreach ($semestres as $semestre)
                                <flux:select.option value="{{ $semestre->id }}">{{ $semestre->numero }}° semestre · {{ $semestre->grado?->nombre }}° grado</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif

                    <div>
                        <div class="mb-1 flex items-center gap-2"><flux:label>Generación</flux:label><span class="rounded-full border px-2 py-0.5 text-[10px] font-black uppercase {{ $esGeneracionExcepcional ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">{{ $esGeneracionExcepcional ? 'Excepcional' : 'Automática' }}</span></div>
                        <flux:input value="{{ $generacionCalculada ?: 'Se calculará automáticamente' }}" readonly disabled />
                        @if ($advertenciaAsignacion)<p class="mt-2 text-xs font-semibold text-amber-600">{{ $advertenciaAsignacion }}</p>@endif
                        @if ($esGeneracionExcepcional && $motivoGeneracionExcepcional)
                            <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-300">{{ $motivoGeneracionExcepcional }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-200">
                    Cupo ilimitado. Las inscripciones solo podrán usar este grupo cuando coincidan ciclo, nivel, grado, generación y semestre.
                </div>
            </div>

            <footer class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                <flux:button type="button" x-on:click="cerrar()">Cancelar</flux:button>
                <flux:button type="submit" variant="primary" spinner="actualizarGrupo">Guardar cambios</flux:button>
            </footer>
        </form>
    </div>

    <livewire:asignacion-grupo.crear-editar-asignacion-grupo />
</div>
