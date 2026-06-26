<div id="panel-asignacion-materia" class="space-y-6">
    <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] to-[#88AC2E]"></div>
        <div class="space-y-5 p-5 sm:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Cargas académicas por ciclo</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Cada ciclo conserva sus propios profesores, materias y horarios. Los ciclos anteriores no se sobrescriben.
                    </p>
                </div>
                <span class="w-fit rounded-full bg-blue-50 px-3 py-1.5 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                    {{ $nivel?->nombre }}
                </span>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <flux:field>
                    <flux:label>Ciclo de trabajo</flux:label>
                    <flux:select wire:model.live="ciclo_escolar_id">
                        @foreach ($this->ciclosEscolares as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">
                                {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · Actual' : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="ciclo_escolar_id" />
                </flux:field>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                    <p class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Protección histórica</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Archivar o cerrar no elimina horarios, calificaciones ni listas.
                    </p>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                    <p class="text-xs font-black uppercase tracking-wide text-amber-700 dark:text-amber-300">Estado inicial</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Las nuevas cargas quedan como borrador hasta que el administrador las confirme.
                    </p>
                </div>
            </div>
        </div>
    </section>

    @if (auth()->user()?->is_admin)
        <section class="rounded-[1.6rem] border border-indigo-200 bg-gradient-to-br from-indigo-50 to-blue-50 p-5 shadow-sm dark:border-indigo-900/50 dark:from-indigo-950/25 dark:to-blue-950/20 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-2xl">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Preparar este nivel desde otro ciclo</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                        Crea nuevas cargas con IDs nuevos. Puedes copiar solo materias, también docentes o además días y horas.
                    </p>
                </div>

                <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2 xl:max-w-3xl xl:grid-cols-4">
                    <flux:field>
                        <flux:label>Ciclo origen</flux:label>
                        <flux:select wire:model="ciclo_origen_id">
                            <flux:select.option value="">Selecciona</flux:select.option>
                            @foreach ($this->ciclosEscolares as $ciclo)
                                @if ((int) $ciclo->id !== (int) $ciclo_escolar_id)
                                    <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>
                        <flux:error name="ciclo_origen_id" />
                    </flux:field>

                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-white/80 bg-white/70 px-4 py-3 shadow-sm dark:border-neutral-700 dark:bg-neutral-900/60">
                        <input type="checkbox" wire:model="copiar_profesores" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-200">Copiar docentes</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-white/80 bg-white/70 px-4 py-3 shadow-sm dark:border-neutral-700 dark:bg-neutral-900/60">
                        <input type="checkbox" wire:model="copiar_horarios" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-200">Copiar horarios</span>
                    </label>

                    <button type="button" wire:click="copiarDesdeCiclo" wire:loading.attr="disabled"
                        wire:confirm="Se crearán cargas nuevas para este nivel y ciclo. No se modificará el ciclo origen. ¿Continuar?"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700 disabled:opacity-60">
                        <flux:icon.document-duplicate class="h-5 w-5" />
                        <span wire:loading.remove wire:target="copiarDesdeCiclo">Preparar ciclo</span>
                        <span wire:loading wire:target="copiarDesdeCiclo">Copiando…</span>
                    </button>
                </div>
            </div>
        </section>
    @endif

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
        <article class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950 sm:p-6">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ $editandoId ? 'Editar carga' : 'Nueva carga' }}</h3>
                    <p class="mt-1 text-xs font-semibold text-slate-500">El horario puede quedar pendiente.</p>
                </div>
                @if ($editandoId)
                    <button type="button" wire:click="limpiarFormulario" class="rounded-xl border border-slate-200 p-2 text-slate-500 hover:bg-slate-50 dark:border-slate-700">
                        <flux:icon.x-mark class="h-5 w-5" />
                    </button>
                @endif
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Grado, grupo y generación</flux:label>
                    <flux:select wire:model.live="grupo_id">
                        <flux:select.option value="">Selecciona un grupo</flux:select.option>
                        @foreach ($this->grupos as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">
                                {{ $grupo->grado?->nombre ?? 'Sin grado' }} · Grupo {{ $grupo->asignacionGrupo?->nombre ?? '—' }} · {{ $grupo->generacion?->anio_ingreso ?? '—' }}-{{ $grupo->generacion?->anio_egreso ?? '—' }}{{ $grupo->semestre ? ' · ' . $grupo->semestre->numero . '° semestre' : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="grupo_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Materia</flux:label>
                    <flux:select wire:model="materia_id" :disabled="blank($grupo_id)">
                        <flux:select.option value="">Selecciona una materia</flux:select.option>
                        @foreach ($this->materiasDisponibles as $materia)
                            <flux:select.option value="{{ $materia->id }}">
                                {{ $materia->materia }}{{ $materia->clave ? ' · ' . $materia->clave : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="materia_id" />
                </flux:field>

                <div class="relative">
                    <flux:field>
                        <flux:label>Profesor responsable</flux:label>
                        <flux:input wire:model.live.debounce.300ms="buscarProfesor" icon="magnifying-glass"
                            placeholder="Puede quedar pendiente" autocomplete="off" />
                        <flux:error name="profesor_id" />
                    </flux:field>

                    @if ($buscarProfesor !== '' && blank($profesor_id))
                        <div class="absolute z-30 mt-2 max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900">
                            @forelse ($this->profesoresFiltrados as $profesor)
                                <button type="button" wire:click="seleccionarProfesor({{ $profesor['id'] }})"
                                    class="block w-full border-b border-slate-100 px-4 py-3 text-left text-sm font-bold text-slate-700 last:border-0 hover:bg-indigo-50 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-indigo-950/20">
                                    {{ $profesor['nombre'] }}
                                </button>
                            @empty
                                <p class="p-4 text-center text-sm text-slate-500">Sin coincidencias.</p>
                            @endforelse
                        </div>
                    @endif
                </div>

                <button type="button" wire:click="guardarMateria" wire:loading.attr="disabled"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[#006492] px-4 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/20 transition hover:bg-[#005474] disabled:opacity-60">
                    <flux:icon.check class="h-5 w-5" />
                    <span wire:loading.remove wire:target="guardarMateria">{{ $editandoId ? 'Actualizar carga' : 'Guardar como borrador' }}</span>
                    <span wire:loading wire:target="guardarMateria">Guardando…</span>
                </button>
            </div>
        </article>

        <article class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/70 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-black text-slate-900 dark:text-white">Revisión de cargas</h3>
                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $this->asignacionesFiltradas->count() }} registro(s) del ciclo seleccionado.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:input wire:model.live.debounce.350ms="buscar" icon="magnifying-glass" placeholder="Buscar materia, profesor o grupo" />
                    @if (auth()->user()?->is_admin && $this->asignacionesFiltradas->contains('estado', 'borrador'))
                        <button type="button" wire:click="confirmarTodas" wire:confirm="¿Confirmar todas las cargas en borrador de este nivel?"
                            class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white hover:bg-emerald-700">
                            Confirmar borradores
                        </button>
                    @endif
                </div>
            </div>

            @if ($this->asignacionesFiltradas->isEmpty())
                <div class="p-12 text-center">
                    <flux:icon.inbox class="mx-auto h-10 w-10 text-slate-400" />
                    <p class="mt-3 font-black text-slate-800 dark:text-white">No hay cargas en este ciclo.</p>
                    <p class="mt-1 text-sm text-slate-500">Puedes capturarlas manualmente o prepararlas desde otro ciclo.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-100 dark:bg-slate-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Materia</th>
                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Grupo</th>
                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Profesor histórico</th>
                                <th class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500">Horario</th>
                                <th class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->asignacionesFiltradas as $asignacion)
                                <tr wire:key="carga-academica-{{ $asignacion->id }}" class="align-top hover:bg-slate-50 dark:hover:bg-slate-900/60">
                                    <td class="px-4 py-4">
                                        <p class="font-black text-slate-900 dark:text-white">{{ $asignacion->materia?->materia ?? 'Materia' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">{{ $asignacion->materia?->clave ?: 'Sin clave' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700 dark:text-slate-200">
                                        <p class="font-bold">{{ $asignacion->grupo?->grado?->nombre ?? '—' }} · Grupo {{ $asignacion->grupo?->asignacionGrupo?->nombre ?? '—' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Gen. {{ $asignacion->grupo?->generacion?->anio_ingreso ?? '—' }}-{{ $asignacion->grupo?->generacion?->anio_egreso ?? '—' }}</p>
                                        @if ($asignacion->grupo?->semestre)
                                            <p class="mt-1 text-xs font-black text-violet-600">{{ $asignacion->grupo->semestre->numero }}° semestre</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="font-bold text-slate-700 dark:text-slate-200">
                                            {{ $asignacion->profesor ? trim(($asignacion->profesor->titulo ?? '') . ' ' . ($asignacion->profesor->nombre ?? '') . ' ' . ($asignacion->profesor->apellido_paterno ?? '') . ' ' . ($asignacion->profesor->apellido_materno ?? '')) : 'Profesor pendiente' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        @if ($asignacion->horarios->isNotEmpty())
                                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">{{ $asignacion->horarios->count() }} bloque(s)</span>
                                        @else
                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span @class([
                                            'rounded-full px-2.5 py-1 text-[11px] font-black',
                                            'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300' => $asignacion->estado === 'borrador',
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' => $asignacion->estado === 'activa',
                                            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => $asignacion->estado === 'cerrada',
                                            'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300' => $asignacion->estado === 'archivada',
                                        ])>
                                            {{ ucfirst($asignacion->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-wrap justify-end gap-1.5">
                                            <button type="button" wire:click="editar({{ $asignacion->id }})" class="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-[11px] font-black text-blue-700 hover:bg-blue-100">Editar</button>
                                            @if (auth()->user()?->is_admin)
                                                @if ($asignacion->estado === 'borrador')
                                                    <button type="button" wire:click="confirmar({{ $asignacion->id }})" class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[11px] font-black text-white hover:bg-emerald-700">Confirmar</button>
                                                @elseif ($asignacion->estado === 'activa')
                                                    <button type="button" wire:click="cerrar({{ $asignacion->id }})" wire:confirm="¿Cerrar esta carga? Seguirá disponible para consulta histórica."
                                                        class="rounded-lg bg-slate-700 px-2.5 py-1.5 text-[11px] font-black text-white hover:bg-slate-800">Cerrar</button>
                                                @else
                                                    <button type="button" wire:click="reactivar({{ $asignacion->id }})" class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[11px] font-black text-white hover:bg-emerald-700">Reactivar</button>
                                                @endif
                                                @if ($asignacion->estado !== 'archivada')
                                                    <button type="button" wire:click="archivar({{ $asignacion->id }})" wire:confirm="¿Archivar esta carga? No se eliminará ningún dato relacionado."
                                                        class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[11px] font-black text-rose-700 hover:bg-rose-100">Archivar</button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>
</div>
