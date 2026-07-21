<div x-data="{ abierto: true, buscador: false }" class="space-y-4">
    <button type="button" @click="abierto = !abierto"
        class="group inline-flex items-center gap-3 rounded-2xl bg-gradient-to-r from-violet-700 to-fuchsia-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-violet-500/20 transition hover:-translate-y-0.5">
        <span class="grid h-8 w-8 place-items-center rounded-xl bg-white/15">
            <flux:icon.user-plus class="h-4 w-4" />
        </span>
        Nueva asignación de personal
        <flux:icon.chevron-down class="h-4 w-4 transition" ::class="abierto ? 'rotate-180' : ''" />
    </button>

    <section x-show="abierto" x-collapse
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/40 dark:border-neutral-800 dark:bg-neutral-900 dark:shadow-none">
        <div class="h-1.5 bg-gradient-to-r from-violet-600 via-blue-600 to-cyan-500"></div>

        <form wire:submit.prevent="asignarPersonalNivel" class="space-y-6 p-5 sm:p-7">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                <div class="flex items-start gap-3">
                    <div
                        class="grid h-12 w-12 place-items-center rounded-2xl bg-violet-50 text-violet-700 ring-1 ring-violet-100 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900">
                        <flux:icon.academic-cap class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-950 dark:text-white">Asignación por ciclo escolar</h2>
                        <p class="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                            La función queda ligada al ciclo seleccionado. Las fechas SEG, SEP y C.T. se conservan por
                            persona y nivel educativo.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span @class([
                        'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black uppercase tracking-wide ring-1',
                        'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900' =>
                            $plantillaEstado === 'borrador',
                        'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900' =>
                            $plantillaEstado === 'en_revision',
                        'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900' =>
                            $plantillaEstado === 'publicada',
                        'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700' =>
                            $plantillaEstado === 'cerrada',
                    ])>
                        <span class="h-2 w-2 rounded-full bg-current"></span>
                        {{ str_replace('_', ' ', $plantillaEstado) }}
                    </span>
                    @if ($plantillaEstado === 'cerrada')
                        <span class="text-xs font-bold text-rose-600">Reabre la plantilla para editar.</span>
                    @endif
                </div>
            </div>

            <div
                class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/40 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar" disabled>
                        @foreach ($ciclos as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">
                                {{ $ciclo->nombre }}{{ $ciclo->es_actual ? ' · actual' : '' }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <p class="mt-1 text-[11px] text-slate-500">Se controla desde el selector general de Plantilla.</p>
                </div>
                <div class="lg:col-span-4">
                    <flux:select wire:model.live.change="nivel_id" label="Nivel educativo"
                        placeholder="Selecciona un nivel">
                        <flux:select.option value="0">--Selecciona el nivel--</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="nivel_id" />
                </div>
                <div class="lg:col-span-4">
                    <div
                        class="rounded-2xl border border-blue-100 bg-blue-50 p-3 text-xs text-blue-800 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-200">
                        <p class="font-black">Regla de seguridad</p>
                        <p class="mt-1">Solo se mostrarán grupos activos del ciclo, nivel y grado/semestre
                            seleccionados.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-12">
                <div class="xl:col-span-5">
                    <flux:field>
                        <flux:label>Personal</flux:label>
                        <div class="relative" @click.outside="buscador = false">
                            <flux:input wire:model.live.debounce.300ms="buscar_persona" @focus="buscador = true"
                                @click="buscador = true" icon="magnifying-glass"
                                placeholder="Buscar por nombre o apellidos..." autocomplete="off" />

                            @if ($persona_id)
                                <button type="button" wire:click="limpiarPersona"
                                    class="absolute right-10 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-rose-600 dark:hover:bg-neutral-800">
                                    <flux:icon.x-mark class="h-4 w-4" />
                                </button>
                            @endif

                            <div x-show="buscador" x-cloak
                                class="absolute z-50 mt-2 max-h-72 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">
                                @forelse ($this->personalFiltrado as $persona)
                                    <button type="button" wire:click="seleccionarPersona({{ $persona->id }})"
                                        @click="buscador = false"
                                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-blue-50 dark:hover:bg-blue-950/30">
                                        <span
                                            class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-100 font-black text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                            {{ mb_substr($persona->nombre ?? 'P', 0, 1) }}
                                        </span>
                                        <span>
                                            <b
                                                class="block text-sm text-slate-900 dark:text-white">{{ trim(($persona->titulo ? $persona->titulo . ' ' : '') . $persona->nombre . ' ' . $persona->apellido_paterno . ' ' . $persona->apellido_materno) }}</b>
                                            <small class="text-slate-500">Seleccionar personal</small>
                                        </span>
                                    </button>
                                @empty
                                    <p class="p-4 text-center text-sm text-slate-500">No se encontraron coincidencias.
                                    </p>
                                @endforelse
                            </div>
                        </div>
                        <flux:error name="persona_id" />
                    </flux:field>
                </div>

                <div class="xl:col-span-7">
                    <flux:field>
                        <flux:label>Función / rol</flux:label>
                        @if ($persona_id && $rolesPersona->isNotEmpty())
                            <div
                                class="flex min-h-11 flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2 dark:border-neutral-700 dark:bg-neutral-950">
                                @foreach ($rolesPersona as $personaRol)
                                    @php($rol = $personaRol->rolePersona)
                                    <button type="button" wire:click="seleccionarRol({{ $personaRol->id }})"
                                        @class([
                                            'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition',
                                            'border-violet-500 bg-violet-600 text-white shadow' =>
                                                (int) $persona_role_id === (int) $personaRol->id,
                                            'border-slate-200 bg-slate-50 text-slate-700 hover:border-violet-300 hover:bg-violet-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200' =>
                                                (int) $persona_role_id !== (int) $personaRol->id,
                                        ])>
                                        <span class="h-2 w-2 rounded-full bg-current opacity-70"></span>
                                        {{ $rol?->nombre ?? 'Función' }}
                                        @if ($rol?->requiere_grupo)
                                            <span
                                                class="rounded-full bg-white/20 px-1.5 py-0.5 text-[9px] uppercase">Grupo
                                                obligatorio</span>
                                        @elseif ($rol?->permite_grupo)
                                            <span
                                                class="rounded-full bg-white/20 px-1.5 py-0.5 text-[9px] uppercase">Grupo
                                                opcional</span>
                                        @else
                                            <span
                                                class="rounded-full bg-white/20 px-1.5 py-0.5 text-[9px] uppercase">General</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @elseif ($persona_id)
                            <div
                                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                                La persona no tiene funciones asignadas en el catálogo de roles.
                            </div>
                        @else
                            <div
                                class="rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-neutral-700">
                                Selecciona primero una persona.</div>
                        @endif
                        <flux:error name="persona_role_id" />
                    </flux:field>
                </div>
            </div>

            @if ($rolPermiteGrupo)
                <section
                    class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50/70 to-white p-5 dark:border-indigo-900 dark:from-indigo-950/20 dark:to-neutral-900">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">Ubicación académica</h3>
                            <p class="text-xs text-slate-500">La generación se obtiene automáticamente del grupo y nunca
                                se captura manualmente.</p>
                        </div>
                        <span
                            class="rounded-full bg-indigo-100 px-3 py-1 text-[10px] font-black uppercase text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                            {{ $rolRequiereGrupo ? 'Obligatoria' : 'Opcional' }}
                        </span>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-4">
                        @if ($nivel_id && $niveles->firstWhere('id', (int) $nivel_id)?->slug === 'bachillerato')
                            <flux:select wire:key="semestre-{{ $ciclo_escolar_id }}-{{ $nivel_id }}"
                                wire:model.live.change="semestre_id" label="Semestre" placeholder="Selecciona semestre">
                                <flux:select.option value="0">--Selecciona el semestre--</flux:select.option>
                                @foreach ($this->semestresDisponibles as $semestre)
                                    <flux:select.option value="{{ $semestre->id }}">
                                        {{ $semestre->orden_global ?: $semestre->numero }}° semestre ·
                                        {{ $semestre->grado?->nombre }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:select wire:key="grado-{{ $ciclo_escolar_id }}-{{ $nivel_id }}"
                                wire:model.live.change="grado_id" label="Grado" placeholder="Selecciona grado">
                                <flux:select.option value="0">--Selecciona el grado--</flux:select.option>
                                @foreach ($this->gradosDisponibles as $grado)
                                    <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif

                        <flux:select
                            wire:key="grupo-{{ $ciclo_escolar_id }}-{{ $nivel_id }}-{{ $grado_id }}-{{ $semestre_id }}"
                            wire:model.live.change="grupo_id" label="Grupo compatible" placeholder="Selecciona grupo">
                            <flux:select.option value="0">--Selecciona el grupo--</flux:select.option>
                            @foreach ($this->gruposDisponibles as $grupo)
                                <flux:select.option value="{{ $grupo->id }}">
                                    {{ $grupo->asignacionGrupo?->nombre }} · {{ $grupo->grado?->nombre }} ·
                                    {{ $grupo->generacion?->etiqueta }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:input label="Generación" value="{{ $generacionTexto }}" readonly
                            placeholder="Se calcula con el grupo" />

                        <div class="flex items-end">
                            <div
                                class="w-full rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
                                <b class="block">{{ $this->gruposDisponibles->count() }} grupo(s) disponible(s)</b>
                                <span>Cupo ilimitado; solo se valida compatibilidad.</span>
                            </div>
                        </div>
                    </div>
                    <flux:error name="grado_id" />
                    <flux:error name="semestre_id" />
                    <flux:error name="grupo_id" />
                </section>
            @elseif ($persona_role_id)
                <div
                    class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-200">
                    <b>Función general:</b> grado y grupo quedan bloqueados para evitar asignaciones inconsistentes.
                </div>
            @endif

            <section
                class="rounded-3xl border border-amber-100 bg-amber-50/40 p-5 dark:border-amber-900 dark:bg-amber-950/10">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-black text-slate-900 dark:text-white">Datos laborales por nivel</h3>
                        <p class="text-xs text-slate-500">Estas fechas no se duplican por ciclo; permanecen asociadas a
                            la persona en este nivel.</p>
                    </div>
                    @if ($fechasExistentes)
                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-[10px] font-black uppercase text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                            <flux:icon.check-circle class="h-3.5 w-3.5" /> Datos existentes recuperados
                        </span>
                    @endif
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input type="date" wire:model="ingreso_seg" label="Fecha de ingreso SEG" />
                    <flux:input type="date" wire:model="ingreso_sep" label="Fecha de ingreso SEP" />
                    <flux:input type="date" wire:model="ingreso_ct" label="Fecha de ingreso C.T." />
                </div>
            </section>

            <div
                class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end dark:border-neutral-800">
                <button type="button" @click="abierto = false"
                    class="rounded-2xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" @disabled($plantillaEstado === 'cerrada')
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-700 to-blue-700 px-6 py-2.5 text-sm font-black text-white shadow-lg disabled:cursor-not-allowed disabled:opacity-50">
                    <flux:icon.check class="h-4 w-4" />
                    <span wire:loading.remove wire:target="asignarPersonalNivel">Guardar en la plantilla</span>
                    <span wire:loading wire:target="asignarPersonalNivel">Validando y guardando...</span>
                </button>
            </div>
        </form>
    </section>
</div>
