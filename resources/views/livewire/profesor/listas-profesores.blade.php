<div class="space-y-6" x-data="{ abierto: true }">
    <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="abierto = !abierto"
            class="flex w-full items-center justify-between gap-4 px-5 py-5 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/60 sm:px-6">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-700 text-white shadow-lg shadow-indigo-500/20">
                    <flux:icon.academic-cap class="h-6 w-6" />
                </span>
                <span>
                    <span class="block text-base font-black text-slate-900 dark:text-white">Listas históricas por carga académica</span>
                    <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">
                        Las materias, talleres, profesores y alumnos se consultan según el ciclo y la fecha de corte.
                    </span>
                </span>
            </div>

            <div class="flex items-center gap-3">
                @if ($this->profesorSeleccionado)
                    <span class="hidden rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300 sm:inline-flex">
                        {{ $this->totalMaterias }} carga(s)
                    </span>
                @endif
                <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition dark:border-neutral-700 dark:text-slate-300"
                    x-bind:class="abierto ? 'rotate-180 text-indigo-600' : ''">
                    <flux:icon.chevron-down class="h-5 w-5" />
                </span>
            </div>
        </button>

        <div x-cloak x-show="abierto" x-transition class="border-t border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/30 sm:p-6">
            <div class="space-y-6 rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <flux:field>
                        <flux:label>Ciclo escolar</flux:label>
                        <flux:select wire:model.live="ciclo_escolar_id" :disabled="!$this->esAdministrador()">
                            @foreach ($this->ciclosEscolares as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · Actual' : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        @if (!$this->esAdministrador())
                            <flux:description>Solo administración puede consultar ciclos anteriores.</flux:description>
                        @endif
                    </flux:field>

                    <flux:field>
                        <flux:label>Fecha de corte de la lista</flux:label>
                        <flux:input type="date" wire:model.live.debounce.400ms="fecha_corte" />
                        <flux:description>El alumno aparece únicamente si estaba activo en esta fecha.</flux:description>
                    </flux:field>

                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
                        <p class="text-xs font-black uppercase tracking-wide text-blue-700 dark:text-blue-300">Regla histórica</p>
                        <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Una baja o cambio posterior no modifica las listas de fechas anteriores.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <div class="relative rounded-[1.35rem] border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                        <flux:field>
                            <flux:label>Buscar profesor</flux:label>
                            <flux:input wire:model.live.debounce.350ms="buscar_profesor"
                                icon="magnifying-glass" placeholder="Nombre, CURP, RFC o correo" autocomplete="off" />
                        </flux:field>

                        @if ($buscar_profesor !== '' && !$profesor_id)
                            <div class="mt-3 max-h-80 overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-neutral-700 dark:bg-neutral-900">
                                @forelse ($this->profesores as $profesor)
                                    <button type="button" wire:click="seleccionarProfesor({{ $profesor->id }})"
                                        class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-indigo-50 dark:border-neutral-800 dark:hover:bg-indigo-950/20">
                                        <span>
                                            <span class="block text-sm font-black text-slate-900 dark:text-white">{{ $this->nombreProfesor($profesor) }}</span>
                                            <span class="mt-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $this->rolPrincipal($profesor) }}</span>
                                        </span>
                                        @if ($this->totalCargasProfesor($profesor) > 0)
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                {{ $this->totalCargasProfesor($profesor) }} carga(s)
                                            </span>
                                        @else
                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                                                Sin carga
                                            </span>
                                        @endif
                                    </button>
                                @empty
                                    <p class="p-5 text-center text-sm font-semibold text-slate-500">No se encontraron profesores.</p>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    @if ($this->profesorSeleccionado)
                        <div class="rounded-[1.35rem] border border-indigo-200 bg-gradient-to-br from-indigo-50 to-blue-50 p-5 dark:border-indigo-900/50 dark:from-indigo-950/25 dark:to-blue-950/20">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Profesor histórico seleccionado</p>
                                    <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">{{ $this->nombreProfesor($this->profesorSeleccionado) }}</h3>
                                    <p class="mt-1 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $this->rolPrincipal($this->profesorSeleccionado) }}</p>
                                </div>
                                <button type="button" wire:click="limpiarTodo"
                                    class="rounded-xl border border-rose-200 bg-white p-2 text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/50 dark:bg-neutral-900">
                                    <flux:icon.x-mark class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($this->profesorSeleccionado)
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <flux:field>
                            <flux:label>Materia o taller</flux:label>
                            <flux:input wire:model.live.debounce.350ms="buscar_materia" icon="magnifying-glass" placeholder="Buscar" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Nivel</flux:label>
                            <flux:select wire:model.live="filtro_nivel">
                                <flux:select.option value="">Todos</flux:select.option>
                                @foreach ($this->nivelesFiltro as $nivel)
                                    <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <flux:field>
                            <flux:label>Grado</flux:label>
                            <flux:select wire:model.live="filtro_grado">
                                <flux:select.option value="">Todos</flux:select.option>
                                @foreach ($this->gradosFiltro as $grado)
                                    <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <flux:field>
                            <flux:label>Grupo</flux:label>
                            <flux:select wire:model.live="filtro_grupo">
                                <flux:select.option value="">Todos</flux:select.option>
                                @foreach ($this->gruposFiltro as $grupo)
                                    <flux:select.option value="{{ $grupo->id }}">{{ $grupo->asignacionGrupo?->nombre ?? 'Sin grupo' }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <flux:field>
                            <flux:label>Generación</flux:label>
                            <flux:select wire:model.live="filtro_generacion">
                                <flux:select.option value="">Todas</flux:select.option>
                                @foreach ($this->generacionesFiltro as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">{{ $this->textoGeneracion($generacion) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        @if ($this->semestresFiltro->isNotEmpty())
                            <flux:field>
                                <flux:label>Semestre</flux:label>
                                <flux:select wire:model.live="filtro_semestre">
                                    <flux:select.option value="">Todos</flux:select.option>
                                    @foreach ($this->semestresFiltro as $semestre)
                                        <flux:select.option value="{{ $semestre->id }}">{{ $this->textoSemestre($semestre) }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        @endif

                        <flux:field>
                            <flux:label>Día</flux:label>
                            <flux:select wire:model.live="filtro_dia">
                                <flux:select.option value="">Todos</flux:select.option>
                                @foreach ($this->diasFiltro as $dia)
                                    <flux:select.option value="{{ $dia->id }}">{{ $dia->dia }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <flux:field>
                            <flux:label>Estado de carga</flux:label>
                            <flux:select wire:model.live="filtro_estado">
                                <flux:select.option value="">Todos</flux:select.option>
                                <flux:select.option value="borrador">Borrador</flux:select.option>
                                <flux:select.option value="activa">Activa</flux:select.option>
                                <flux:select.option value="cerrada">Cerrada</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-black text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300">{{ $this->totalMaterias }} cargas</span>
                            <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">{{ $this->totalHoras }} bloques de horario</span>
                        </div>
                        <button type="button" wire:click="limpiarFiltrosMaterias"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                            <flux:icon.funnel class="h-4 w-4" /> Limpiar filtros
                        </button>
                    </div>

                    @if ($this->cargasProfesor->isEmpty())
                        <div class="rounded-[1.35rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center dark:border-neutral-700 dark:bg-neutral-950/40">
                            <flux:icon.inbox class="mx-auto h-9 w-9 text-slate-400" />
                            <p class="mt-3 text-base font-black text-slate-800 dark:text-white">No hay cargas académicas en este ciclo.</p>
                            <p class="mt-1 text-sm text-slate-500">El profesor permanece visible, pero debe recibir una materia o taller para este ciclo.</p>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-[1.35rem] border border-slate-200 dark:border-neutral-800">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                                    <thead class="bg-slate-100 dark:bg-neutral-950">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Carga académica</th>
                                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Grupo</th>
                                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Horario</th>
                                            <th class="min-w-72 px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Periodo / parcial</th>
                                            <th class="px-4 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500">Listas</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                                        @foreach ($this->cargasProfesor as $item)
                                            <tr wire:key="carga-lista-{{ $item['clave'] }}" class="align-top transition hover:bg-slate-50 dark:hover:bg-neutral-800/60">
                                                <td class="px-4 py-4">
                                                    <p class="font-black text-slate-900 dark:text-white">{{ $item['nombre'] }}</p>
                                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">{{ $item['codigo'] ?: 'Sin clave' }}</span>
                                                        <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-black text-violet-700 dark:bg-violet-950/30 dark:text-violet-300">{{ $item['tipo'] === 'taller' ? 'Taller' : 'Materia' }}</span>
                                                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">{{ $this->etiquetaEstado($item['estado']) }}</span>
                                                        @if ($item['calificable'])
                                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">Calificable</span>
                                                        @else
                                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">Solo asistencia</span>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td class="px-4 py-4 text-slate-700 dark:text-slate-200">
                                                    <p class="font-bold">{{ $item['nivel']?->nombre ?? '—' }} · {{ $item['grado']?->nombre ?? '—' }}</p>
                                                    <p class="mt-1 text-xs font-semibold text-slate-500">Grupo {{ $item['grupo']?->asignacionGrupo?->nombre ?? '—' }}</p>
                                                    <p class="mt-1 text-xs font-semibold text-slate-500">Gen. {{ $this->textoGeneracion($item['generacion']) }}</p>
                                                    @if ($item['semestre'])
                                                        <p class="mt-1 text-xs font-black text-violet-600">{{ $this->textoSemestre($item['semestre']) }}</p>
                                                    @endif
                                                </td>

                                                <td class="px-4 py-4">
                                                    @forelse ($item['horarios'] as $horario)
                                                        <p class="mb-1 text-xs font-bold text-slate-600 dark:text-slate-300">
                                                            {{ $horario->dia?->dia ?? 'Día pendiente' }} · {{ $this->textoHora($horario->hora) }}
                                                        </p>
                                                    @empty
                                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700">Horario pendiente</span>
                                                    @endforelse
                                                </td>

                                                <td class="px-4 py-4">
                                                    <flux:select wire:key="periodo-{{ $item['clave'] }}" wire:model.live="periodos_por_carga.{{ $item['clave'] }}">
                                                        <flux:select.option value="">Selecciona periodo o parcial</flux:select.option>
                                                        @foreach ($this->periodosParaCarga($item) as $periodo)
                                                            <flux:select.option value="{{ $periodo->id }}">{{ $this->etiquetaPeriodo($periodo, $item) }}</flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </td>

                                                <td class="px-4 py-4">
                                                    <div class="flex justify-end gap-2">
                                                        @if ($this->puedeDescargarCarga($item, 'asistencia'))
                                                            <a href="{{ $this->urlAsistencia($item['clave']) }}" target="_blank"
                                                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2 text-xs font-black text-white shadow-sm hover:bg-blue-700">
                                                                <flux:icon.clipboard-document-list class="h-4 w-4" /> Asistencia
                                                            </a>
                                                        @else
                                                            <button type="button" disabled class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-xl bg-slate-200 px-3 py-2 text-xs font-black text-slate-500 dark:bg-neutral-800">Asistencia</button>
                                                        @endif

                                                        @if ($item['calificable'] && $this->puedeDescargarCarga($item, 'evaluacion'))
                                                            <a href="{{ $this->urlEvaluacion($item['clave']) }}" target="_blank"
                                                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white shadow-sm hover:bg-emerald-700">
                                                                <flux:icon.document-check class="h-4 w-4" /> Evaluación
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-black text-slate-900 dark:text-white">Descargar todas las cargas visibles</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">Cada carga debe tener su periodo o parcial seleccionado.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($this->puedeDescargarTodas('asistencia'))
                                    <a href="{{ $this->urlAsistencia() }}" target="_blank" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white hover:bg-blue-700">Todas las asistencias</a>
                                @endif
                                @if ($this->puedeDescargarTodas('evaluacion'))
                                    <a href="{{ $this->urlEvaluacion() }}" target="_blank" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white hover:bg-emerald-700">Todas las evaluaciones</a>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </section>
</div>
