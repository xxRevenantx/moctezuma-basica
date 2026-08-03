<div x-data="{ open: @entangle('open').live }"
    x-on:abrir-modal-editar.window="open = true"
    x-on:cerrar-modal-editar.window="open = false">
    <div x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div @click.outside="open = false; $wire.cerrarModal()"
            class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white/95 p-5 backdrop-blur dark:border-neutral-800 dark:bg-neutral-900/95">
                <div class="flex items-start gap-3">
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                        <flux:icon.pencil-square class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-950 dark:text-white">Editar asignación del ciclo</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $nombrePersona }} · {{ $nombreNivel }}</p>
                    </div>
                </div>
                <button type="button" @click="open=false; $wire.cerrarModal()" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-neutral-800">
                    <flux:icon.x-mark class="h-5 w-5" />
                </button>
            </div>

            <form wire:submit.prevent="actualizar" class="space-y-6 p-5 sm:p-7">
                <div class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/40 md:grid-cols-3">
                    <div><span class="text-[10px] font-black uppercase text-slate-500">Personal</span><b class="mt-1 block text-sm text-slate-900 dark:text-white">{{ $nombrePersona }}</b></div>
                    <div><span class="text-[10px] font-black uppercase text-slate-500">Nivel</span><b class="mt-1 block text-sm text-slate-900 dark:text-white">{{ $nombreNivel }}</b></div>
                    <div><span class="text-[10px] font-black uppercase text-slate-500">Estado de plantilla</span><b class="mt-1 block text-sm uppercase text-slate-900 dark:text-white">{{ str_replace('_', ' ', $plantillaEstado) }}</b></div>
                </div>

                <flux:select wire:model.live="persona_role_id" label="Función / rol">
                    @foreach ($rolesPersona as $personaRol)
                        <flux:select.option value="{{ $personaRol->id }}">{{ $personaRol->rolePersona?->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="persona_role_id" />

                @if ($rolPermiteGrupo)
                    <section class="rounded-3xl border border-indigo-100 bg-indigo-50/50 p-5 dark:border-indigo-900 dark:bg-indigo-950/20">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div><h4 class="font-black text-slate-900 dark:text-white">Ubicación académica</h4><p class="text-xs text-slate-500">Cambiar grupo cerrará la asignación anterior y creará una nueva.</p></div>
                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-[10px] font-black uppercase text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{{ $rolRequiereGrupo ? 'Obligatoria' : 'Opcional' }}</span>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            @if ($nivel_id && \App\Models\Nivel::query()->whereKey($nivel_id)->value('slug') === 'bachillerato')
                                <flux:select wire:model.live="semestre_id" label="Semestre" placeholder="Selecciona semestre">
                                    @foreach ($semestres as $semestre)
                                        <flux:select.option value="{{ $semestre->id }}">{{ $semestre->orden_global ?: $semestre->numero }}° semestre · {{ $semestre->grado?->nombre }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:select wire:model.live="grado_id" label="Grado" placeholder="Selecciona grado">
                                    @foreach ($grados as $grado)
                                        <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @endif
                            <flux:select wire:model.live="grupo_id" label="Grupo" placeholder="Selecciona grupo">
                                @foreach ($grupos as $grupo)
                                    <flux:select.option value="{{ $grupo->id }}">{{ $grupo->asignacionGrupo?->nombre }} · {{ $grupo->grado?->nombre }} · {{ $grupo->generacion?->etiqueta }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:input label="Generación" value="{{ $generacionTexto }}" readonly placeholder="Automática" />
                        </div>
                        <flux:error name="grado_id" />
                        <flux:error name="semestre_id" />
                        <flux:error name="grupo_id" />
                    </section>
                @else
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-200">
                        Esta función es general y no permite grado o grupo.
                    </div>
                @endif

                @if ($rolPermiteGrupo && $grupo_id)
                    <section class="rounded-3xl border border-violet-100 bg-violet-50/50 p-5 dark:border-violet-900 dark:bg-violet-950/15">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h4 class="font-black text-slate-900 dark:text-white">Responsable titular del grupo</h4>
                                <p class="text-xs text-slate-500">El titular principal se usa en los formatos vacíos de horario.</p>
                            </div>
                            @if ($titular_automatico)
                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-[10px] font-black uppercase text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                    <flux:icon.check-circle class="h-3.5 w-3.5" /> Automático
                                </span>
                            @endif
                        </div>

                        @if ($titular_automatico)
                            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
                                La función y el grupo seleccionados se reconocen automáticamente como titular principal.
                            </div>
                        @endif

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                                <input type="checkbox" wire:model.live="es_titular" @disabled($titular_automatico)
                                    class="rounded border-slate-300 text-blue-700 disabled:opacity-70" />
                                <span><b class="block text-sm">Titular de grupo</b><small class="text-slate-500">Responsable directo del grupo.</small></span>
                            </label>
                            <label class="flex items-center gap-3 rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-900 dark:bg-indigo-950/20">
                                <input type="checkbox" wire:model.live="es_titular_principal" @disabled($titular_automatico)
                                    class="rounded border-indigo-300 text-indigo-700 disabled:opacity-70" />
                                <span><b class="block text-sm">Titular principal</b><small class="text-slate-500">Nombre que aparecerá en el formato.</small></span>
                            </label>
                        </div>
                        <flux:error name="es_titular_principal" />
                    </section>
                @endif

                <section class="rounded-3xl border border-amber-100 bg-amber-50/40 p-5 dark:border-amber-900 dark:bg-amber-950/10">
                    <h4 class="font-black text-slate-900 dark:text-white">Fechas laborales por nivel</h4>
                    <p class="mb-4 text-xs text-slate-500">Se conservan en todos los ciclos de esta persona dentro del nivel.</p>
                    <div class="grid gap-4 md:grid-cols-3">
                        <flux:input type="date" wire:model="ingreso_seg" label="Ingreso SEG" />
                        <flux:input type="date" wire:model="ingreso_sep" label="Ingreso SEP" />
                        <flux:input type="date" wire:model="ingreso_ct" label="Ingreso C.T." />
                    </div>
                </section>

                <div>
                    <flux:textarea wire:model="motivoCambio" label="Motivo del cambio" rows="3"
                        placeholder="Obligatorio si cambia la función, grado o grupo. El historial anterior se conservará." />
                    <flux:error name="motivoCambio" />
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 pt-5 dark:border-neutral-800">
                    <flux:button type="button" variant="ghost" @click="open=false; $wire.cerrarModal()">Cancelar</flux:button>
                    <flux:button type="submit" icon="check" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="actualizar">Guardar conservando historial</span>
                        <span wire:loading wire:target="actualizar">Guardando...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
