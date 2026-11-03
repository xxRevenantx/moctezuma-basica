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

                    <flux:field>
                        <flux:label>Inicio de las 8 semanas de asistencia</flux:label>
                        <flux:input type="date" wire:model.live.debounce.400ms="fecha_inicio_asistencia" />
                        <flux:description>Se ajusta al lunes de la semana elegida. El PDF será oficio horizontal, con 40 días lectivos y resúmenes mensuales.</flux:description>
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
    @if ($this->esAdministrador())
        <section x-data="{ exportacionAbierta: false }" class="overflow-hidden rounded-[1.75rem] border border-indigo-200 bg-white shadow-sm dark:border-indigo-900/50 dark:bg-neutral-900">
            <button type="button" x-on:click="exportacionAbierta = !exportacionAbierta"
                class="flex w-full items-center justify-between gap-4 bg-gradient-to-r from-indigo-50 via-white to-emerald-50 px-5 py-5 text-left transition hover:from-indigo-100 dark:from-indigo-950/30 dark:via-neutral-900 dark:to-emerald-950/20 sm:px-6">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-emerald-600 text-white shadow-lg">
                        <flux:icon.arrow-down-tray class="h-6 w-6" />
                    </span>
                    <span>
                        <span class="block text-base font-black text-slate-900 dark:text-white">Exportación Masiva de Listas Docentes PRO 2.1</span>
                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">Un ZIP con 2 PDF consolidados por profesor: todas sus asistencias y todas sus evaluaciones, incluso de diferentes niveles.</span>
                    </span>
                </div>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-indigo-200 text-indigo-600 dark:border-neutral-700"
                    x-bind:class="exportacionAbierta ? 'rotate-180' : ''"><flux:icon.chevron-down class="h-5 w-5" /></span>
            </button>

            <div x-cloak x-show="exportacionAbierta" x-transition class="space-y-5 border-t border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <flux:field>
                        <flux:label>¿Qué profesores incluir?</flux:label>
                        <flux:select wire:model.live="exportar_alcance">
                            <flux:select.option value="actual">Profesor seleccionado en la lista</flux:select.option>
                            <flux:select.option value="seleccion">Elegir varios profesores</flux:select.option>
                            <flux:select.option value="nivel">Todos los profesores de un nivel</flux:select.option>
                            <flux:select.option value="todos">Todos los profesores del ciclo escolar</flux:select.option>
                        </flux:select>
                    </flux:field>
                    @if ($exportar_alcance === 'nivel')
                        <flux:field>
                            <flux:label>Nivel académico</flux:label>
                            <flux:select wire:model.live="exportar_nivel_id">
                                <flux:select.option value="">Selecciona un nivel</flux:select.option>
                                @foreach ($this->nivelesExportacion as $nivel)
                                    <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    @endif
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/30">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Ciclo y corte</p>
                        <p class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-100">
                            {{ $this->cicloSeleccionado?->inicio_anio }}-{{ $this->cicloSeleccionado?->fin_anio }}
                            · {{ $fecha_corte ?: 'Sin fecha' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Usa el ciclo, fecha de corte e inicio de asistencia configurados arriba.</p>
                    </div>
                </div>

                @if ($exportar_alcance === 'seleccion')
                    <div class="space-y-3 rounded-[1.35rem] border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/30">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm font-black text-slate-800 dark:text-white">Seleccionar docentes <span class="ml-1 rounded-full bg-indigo-100 px-2.5 py-1 text-xs text-indigo-700">{{ count($exportar_profesores_ids) }} seleccionados</span></p>
                            <div class="flex items-center gap-2"><button type="button" wire:click="seleccionarProfesoresVisiblesExportacion" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100">Seleccionar visibles</button>
                            <button type="button" wire:click="limpiarSeleccionExportacion" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-white dark:border-neutral-700 dark:text-slate-300">Limpiar selección</button></div>
                        </div>
                        <flux:input wire:model.live.debounce.350ms="exportar_buscar" icon="magnifying-glass" placeholder="Buscar docentes por nombre o apellidos" />
                        <div class="grid max-h-64 grid-cols-1 gap-2 overflow-y-auto md:grid-cols-2 xl:grid-cols-3">
                            @forelse ($this->profesoresParaExportar as $docente)
                                <label wire:key="zip-docente-{{ $docente->id }}" class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 hover:border-indigo-300 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                    <input type="checkbox" value="{{ $docente->id }}" wire:model.live="exportar_profesores_ids" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span>{{ $this->nombreProfesor($docente) }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-slate-500">No se encontraron docentes con carga académica en este ciclo.</p>
                            @endforelse
                        </div>
                        <p class="text-xs text-slate-500">Se muestran hasta 80 resultados por búsqueda. Los profesores seleccionados permanecen seleccionados al cambiar la búsqueda.</p>
                    </div>
                @elseif ($exportar_alcance === 'actual' && ! $this->profesorSeleccionado)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">Busca y selecciona un profesor en la sección superior, o elige otro alcance de exportación.</div>
                @endif

                <div class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] md:items-center">
                        <flux:field>
                            <flux:label>Organización de los PDF</flux:label>
                            <flux:select wire:model.live="exportar_formato">
                                <flux:select.option value="consolidado">Unificados por profesor (recomendado)</flux:select.option>
                                <flux:select.option value="individual">Separados por materia (anterior)</flux:select.option>
                            </flux:select>
                        </flux:field>
                        <div class="text-xs leading-relaxed text-indigo-950 dark:text-indigo-200">
                            @if ($exportar_formato === 'consolidado')
                                <strong>Consolidación multinivel:</strong> el ZIP tendrá una carpeta por docente y, en ella, un PDF con todas las asistencias y otro con todas las evaluaciones calificables. Una profesora que imparta Primaria, Secundaria y Bachillerato tendrá sus tres niveles reunidos en sus mismos dos documentos, sin alterar el formato de cada página.
                            @else
                                <strong>Formato anterior:</strong> cada lista se conserva como PDF independiente, dentro de carpetas por nivel, grado, grupo y materia.
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-3 rounded-2xl border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
                        <p class="text-sm font-black text-slate-900 dark:text-white">Tipos de documento</p>
                        <flux:checkbox wire:model.live="exportar_asistencia" label="Listas de asistencia (8 semanas / oficio horizontal)" />
                        <flux:checkbox wire:model.live="exportar_evaluacion" label="Listas de evaluación (solo materias calificables)" />
                        @if (! $exportar_asistencia && ! $exportar_evaluacion)
                            <p class="text-xs font-semibold text-rose-600">Selecciona al menos un tipo de documento.</p>
                        @endif
                        @if ($exportar_alcance === 'actual' && $this->profesorSeleccionado)
                            <flux:checkbox wire:model.live="exportar_solo_visibles" label="Respetar filtros y cargas visibles del profesor" />
                        @endif
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                        <p class="text-sm font-black text-emerald-800 dark:text-emerald-200">Estructura de tu ZIP</p>
                        <div class="mt-3 space-y-1 font-mono text-xs leading-6 text-slate-600 dark:text-slate-300">
                            @if ($exportar_formato === 'consolidado')
                                <p>Listas_Docentes_Consolidadas_2026-2027.zip</p>
                                <p class="pl-3">└─ Ciclo_2026-2027/Profesores/</p>
                                <p class="pl-6">└─ Nombre_Del_Profesor/</p>
                                <p class="pl-9 text-blue-700 dark:text-blue-300">Asistencias_Consolidadas.pdf</p>
                                <p class="pl-9 text-emerald-700 dark:text-emerald-300">Evaluaciones_Consolidadas.pdf</p>
                            @else
                                <p>Listas_Docentes_2026-2027.zip</p>
                                <p class="pl-3">└─ Ciclo_2026-2027/Nivel/</p>
                                <p class="pl-6">└─ Nombre_Del_Profesor/Grado/</p>
                                <p class="pl-9">└─ Materia/Asistencia.pdf</p>
                                <p class="pl-9">└─ Materia/Evaluacion.pdf</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50/60 px-4 py-3 text-xs leading-relaxed text-amber-900 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-200">
                    <strong>Validación de periodos:</strong> se respetan los periodos elegidos arriba para el profesor actual. Para varios docentes, el sistema utiliza el periodo que coincide inequívocamente con la fecha de corte. Si faltan fechas o hay varios parciales posibles, no asigna uno al azar y registra la carga omitida en <strong>INCIDENCIAS.txt</strong>. Las listas válidas siguen incluyéndose.
                </div>

                <form method="POST" action="{{ route('profesor.listas.exportar.zip') }}" target="_blank"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                    @csrf
                    <input type="hidden" name="ciclo_escolar_id" value="{{ $ciclo_escolar_id }}" />
                    <input type="hidden" name="fecha_corte" value="{{ $fecha_corte }}" />
                    <input type="hidden" name="fecha_inicio_asistencia" value="{{ $fecha_inicio_asistencia }}" />
                    <input type="hidden" name="alcance" value="{{ $exportar_alcance }}" />
                    <input type="hidden" name="formato" value="{{ $exportar_formato }}" />
                    <input type="hidden" name="profesor_id" value="{{ $profesor_id }}" />
                    <input type="hidden" name="nivel_id" value="{{ $exportar_nivel_id }}" />
                    <input type="hidden" name="incluir_asistencia" value="{{ $exportar_asistencia ? 1 : 0 }}" />
                    <input type="hidden" name="incluir_evaluacion" value="{{ $exportar_evaluacion ? 1 : 0 }}" />
                    <input type="hidden" name="solo_visibles" value="{{ $exportar_solo_visibles ? 1 : 0 }}" />
                    @if ($exportar_alcance === 'seleccion')
                        @foreach ($exportar_profesores_ids as $docenteId)
                            <input type="hidden" name="profesores[]" value="{{ (int) $docenteId }}" />
                        @endforeach
                    @endif
                    @if ($exportar_alcance === 'actual')
                        @foreach ($periodos_por_carga as $clave => $periodoId)
                            @if ($periodoId)
                                <input type="hidden" name="periodos[{{ $clave }}]" value="{{ (int) $periodoId }}" />
                            @endif
                        @endforeach
                        @if ($exportar_solo_visibles)
                            @foreach ($this->cargasProfesor as $carga)
                                <input type="hidden" name="cargas_visibles[]" value="{{ $carga['clave'] }}" />
                            @endforeach
                        @endif
                    @endif
                    <p class="max-w-xl text-xs font-semibold text-slate-500 dark:text-slate-400">@if ($exportar_formato === 'consolidado')
                        Dos PDF como máximo por profesor, con todas sus materias de cualquier nivel, ordenadas por nivel, grado, grupo y carga. No se crea un PDF de evaluación si el docente no tiene materias calificables.
                    @else
                        PDF separados por materia y grupo, conservando la estructura anterior.
                    @endif
                    El ZIP incluye un resumen y las incidencias cuando corresponda.</p>
                    <button type="submit" @disabled(! $this->cicloSeleccionado || ! $exportar_asistencia && ! $exportar_evaluacion || $exportar_alcance === 'actual' && ! $profesor_id || $exportar_alcance === 'seleccion' && count($exportar_profesores_ids) === 0 || $exportar_alcance === 'nivel' && ! $exportar_nivel_id)
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-600 px-5 py-3 text-sm font-black text-white shadow-md shadow-indigo-500/15 transition hover:from-indigo-700 hover:to-emerald-700 disabled:cursor-not-allowed disabled:opacity-40">
                        <flux:icon.arrow-down-tray class="h-5 w-5" /> Descargar ZIP {{ $exportar_formato === 'consolidado' ? 'con PDFs unificados' : 'por materia' }}
                    </button>
                </form>
            </div>
        </section>
    @endif
</div>
