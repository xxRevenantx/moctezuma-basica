<div class="space-y-6">
    <section class="relative overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 w-full bg-gradient-to-r from-[#88AC2E] via-[#006492] to-sky-500"></div>

        <div wire:loading.delay.flex
            wire:target="ciclo_escolar_id,alcance,generacion_id,grado_id,semestre_id,hora_inicio_id,hora_fin_id,estilo_celda,seleccionarTodosLosGrupos,limpiarSeleccionGrupos,grupos_seleccionados"
            class="absolute inset-0 z-30 hidden items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-neutral-900/75">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                Preparando formato de horario...
            </div>
        </div>

        <div class="space-y-6 p-5 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#88AC2E] via-[#006492] to-sky-500 text-white shadow-lg shadow-blue-500/20">
                        <flux:icon.printer class="h-6 w-6" />
                    </div>

                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">
                            Formatos de horario vacíos
                        </h3>

                        <p class="mt-1 max-w-4xl text-sm text-slate-500 dark:text-slate-400">
                            Genera formatos listos para imprimir por nivel, grado o selección de grupos, usando los días y horas configurados para este nivel.
                            Funciona aunque el grupo todavía no tenga horario capturado.
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-black text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <span class="h-2 w-2 rounded-full bg-current"></span>
                                Una página por grupo
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-black text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                <span class="h-2 w-2 rounded-full bg-current"></span>
                                Carta horizontal
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-[11px] font-black text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                <span class="h-2 w-2 rounded-full bg-current"></span>
                                Vista previa en PDF
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:min-w-[280px]">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Grupos visibles</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $this->gruposDisponibles->count() }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Seleccionados</p>
                        <p class="mt-2 text-3xl font-black text-[#006492] dark:text-sky-300">{{ $this->cantidadSeleccionada }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/30 sm:p-5">
                <div class="mb-4">
                    <p class="text-sm font-black text-slate-900 dark:text-white">Configuración del formato</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Define el alcance, filtra los grupos y elige el rango de horas que se mostrará en el formato.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <flux:field>
                        <flux:label>Nivel</flux:label>
                        <flux:input readonly disabled variant="filled" value="{{ $nivel?->nombre ?? '—' }}" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Ciclo escolar</flux:label>
                        <flux:select wire:model.change="ciclo_escolar_id">
                            <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                            @foreach ($ciclosEscolares as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Alcance</flux:label>
                        <flux:select wire:model.change="alcance">
                            <flux:select.option value="nivel">Nivel completo</flux:select.option>
                            <flux:select.option value="grado">Todos los grupos de un grado</flux:select.option>
                            <flux:select.option value="grupos">Selección manual de grupos</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Estilo de celdas</flux:label>
                        <flux:select wire:model.change="estilo_celda">
                            <flux:select.option value="lineas">Con líneas para escribir</flux:select.option>
                            <flux:select.option value="vacia">Completamente vacías</flux:select.option>
                            <flux:select.option value="campos">Con campos Materia / Profesor</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Generación</flux:label>
                        <flux:select wire:model.change="generacion_id" :disabled="$alcance === 'nivel'">
                            <flux:select.option value="">Selecciona una generación</flux:select.option>
                            @foreach ($generaciones as $generacion)
                                <flux:select.option value="{{ $generacion->id }}">
                                    {{ $generacion->etiqueta }}{{ $generacion->status ? '' : ' · inactiva' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Grado</flux:label>
                        <flux:select wire:model.change="grado_id" :disabled="$alcance === 'nivel' || !$generacion_id">
                            <flux:select.option value="">Selecciona un grado</flux:select.option>
                            @foreach ($grados as $grado)
                                <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    @if ($esBachillerato)
                        <flux:field>
                            <flux:label>Semestre</flux:label>
                            <flux:select wire:model.change="semestre_id" :disabled="$alcance === 'nivel' || !$grado_id">
                                <flux:select.option value="">Selecciona un semestre</flux:select.option>
                                @foreach ($semestres as $semestre)
                                    <flux:select.option value="{{ $semestre->id }}">Semestre {{ $semestre->numero }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:label>Hora inicial</flux:label>
                        <flux:select wire:model.change="hora_inicio_id">
                            <flux:select.option value="">Selecciona una hora</flux:select.option>
                            @foreach ($horas as $hora)
                                <flux:select.option value="{{ $hora->id }}">{{ $hora->hora_inicio }} - {{ $hora->hora_fin }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Hora final</flux:label>
                        <flux:select wire:model.change="hora_fin_id">
                            <flux:select.option value="">Selecciona una hora</flux:select.option>
                            @foreach ($horas as $hora)
                                <flux:select.option value="{{ $hora->id }}">{{ $hora->hora_inicio }} - {{ $hora->hora_fin }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            </div>

            @if ($alcance === 'grupos')
                <div class="rounded-3xl border border-[#88AC2E]/30 bg-[#88AC2E]/5 p-4 dark:border-[#88AC2E]/30 dark:bg-[#88AC2E]/10 sm:p-5">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white">Selección manual de grupos</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Puedes marcar uno o varios grupos. El PDF generará una página por cada grupo seleccionado.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="seleccionarTodosLosGrupos"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-white px-4 py-2 text-xs font-black text-emerald-700 shadow-sm transition hover:bg-emerald-50 dark:border-emerald-900/40 dark:bg-neutral-900 dark:text-emerald-300 dark:hover:bg-neutral-800">
                                <flux:icon.check-circle class="h-4 w-4" />
                                Seleccionar todos
                            </button>

                            <button type="button" wire:click="limpiarSeleccionGrupos"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                <flux:icon.x-circle class="h-4 w-4" />
                                Limpiar
                            </button>
                        </div>
                    </div>

                    @if ($this->gruposDisponibles->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-400">
                            No hay grupos disponibles con los filtros actuales.
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($this->gruposDisponibles as $grupo)
                                <label class="group flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-[#006492]/40 hover:shadow-sm dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-sky-700/50">
                                    <input type="checkbox" value="{{ $grupo->id }}" wire:model.live="grupos_seleccionados"
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-[#006492] focus:ring-[#006492]" />

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-black text-slate-900 dark:text-white">
                                            {{ $this->etiquetaGrupo($grupo) }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            {{ $grupo->generacion?->etiqueta ?? 'Sin generación' }}
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div class="rounded-3xl border border-blue-200 bg-blue-50/40 p-4 dark:border-blue-900/40 dark:bg-blue-950/10 sm:p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <p class="text-sm font-black text-slate-900 dark:text-white">Salida</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            El archivo se abre en una nueva pestaña como vista previa lista para imprimir.
                        </p>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Horas incluidas: <span class="font-bold text-slate-700 dark:text-slate-200">
                                {{ $this->horasRango->first()?->hora_inicio ?? '—' }} - {{ $this->horasRango->last()?->hora_fin ?? '—' }}
                            </span>
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ $this->puedeGenerar ? $this->urlVistaPrevia : '#' }}" target="_blank"
                            @class([
                                'inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-3 text-sm font-black text-white shadow-lg transition',
                                'bg-gradient-to-r from-[#88AC2E] via-[#006492] to-sky-500 hover:-translate-y-0.5 hover:shadow-xl' => $this->puedeGenerar,
                                'pointer-events-none cursor-not-allowed bg-slate-300 text-slate-500 shadow-none dark:bg-neutral-800 dark:text-slate-500' => !$this->puedeGenerar,
                            ])>
                            <flux:icon.document-text class="h-5 w-5" />
                            Vista previa PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
