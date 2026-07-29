<div
    class="mt-6"
    x-data="{ abierto: false, dragId: null }"
>
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button
            type="button"
            x-on:click="abierto = !abierto"
            class="flex w-full items-center justify-between gap-4 px-5 py-5 text-left sm:px-7"
        >
            <div class="flex min-w-0 items-center gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] via-sky-500 to-[#88AC2E] text-white shadow-lg shadow-sky-500/20">
                    <flux:icon.sparkles class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-[#006492]">Horarios inteligentes</p>
                        <flux:badge color="green" size="sm">Versionado</flux:badge>
                        <flux:badge color="blue" size="sm">Multigrado permitido</flux:badge>
                    </div>
                    <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Planificador, editor visual y publicación</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-neutral-400">
                        Conserva el flujo manual actual y añade disponibilidad docente, propuestas automáticas, conflictos, versiones y publicación controlada.
                    </p>
                </div>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-500 dark:border-neutral-700">
                <flux:icon.chevron-down class="h-5 w-5 transition" x-bind:class="abierto ? 'rotate-180' : ''" />
            </span>
        </button>

        <div x-cloak x-show="abierto" x-collapse class="border-t border-slate-100 dark:border-neutral-800">
            <div class="space-y-6 p-5 sm:p-7">
                <div class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/40 lg:grid-cols-[minmax(0,1fr)_auto]">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <flux:select wire:model.live="cicloEscolarId" label="Ciclo escolar">
                            @foreach ($ciclos as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · Actual' : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <div class="sm:col-span-1 lg:col-span-3">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-400">Regla institucional de simultaneidad</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-neutral-200">
                                Un docente puede atender dos o más grupos o grados en el mismo bloque hasta su máximo configurado. Debe clasificarse como sesión compartida o traslape excepcional justificado.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <flux:button wire:click="verificarMotor" variant="ghost" icon="command-line">Comprobar OR-Tools</flux:button>
                    </div>
                    @if ($mensajeMotor !== '')
                        <p class="text-xs font-semibold text-slate-500 lg:col-span-2">{{ $mensajeMotor }}</p>
                    @endif
                </div>

                <nav class="flex flex-wrap gap-2 rounded-2xl bg-slate-100 p-2 dark:bg-neutral-950/70">
                    @foreach ([
                        'disponibilidad' => ['Disponibilidad docente', 'calendar-days'],
                        'reglas' => ['Reglas y cargas', 'adjustments-horizontal'],
                        'propuestas' => ['Generar propuestas', 'sparkles'],
                        'editor' => ['Editor visual', 'view-columns'],
                        'versiones' => ['Versiones y publicación', 'queue-list'],
                    ] as $clave => [$etiqueta, $icono])
                        <button
                            type="button"
                            wire:click="cambiarSeccion('{{ $clave }}')"
                            @class([
                                'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition',
                                'bg-white text-[#006492] shadow-sm dark:bg-neutral-800 dark:text-sky-300' => $seccion === $clave,
                                'text-slate-500 hover:bg-white/60 hover:text-slate-800 dark:text-neutral-400 dark:hover:bg-neutral-800/60' => $seccion !== $clave,
                            ])
                        >
                            <flux:icon :name="$icono" class="h-4 w-4" />
                            {{ $etiqueta }}
                        </button>
                    @endforeach
                </nav>

                @if ($seccion === 'disponibilidad')
                    <div class="space-y-6">
                        <section class="grid gap-5 xl:grid-cols-[340px_minmax(0,1fr)]">
                            <aside class="space-y-4 rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                <flux:heading size="lg">Configuración del docente</flux:heading>
                                <flux:select wire:model.live="profesorId" label="Docente">
                                    <flux:select.option value="">Selecciona</flux:select.option>
                                    @foreach ($profesores as $profesor)
                                        <flux:select.option value="{{ $profesor->id }}">
                                            {{ trim($profesor->apellido_paterno.' '.$profesor->apellido_materno.' '.$profesor->nombre) }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <div class="grid grid-cols-2 gap-3">
                                    <flux:input type="number" min="1" max="6" wire:model="maxGruposSimultaneos" label="Grupos simultáneos" />
                                    <flux:input type="number" min="1" max="20" wire:model="maxHorasDiarias" label="Horas diarias" />
                                    <flux:input type="number" min="1" max="12" wire:model="maxHorasConsecutivas" label="Consecutivas" />
                                    <flux:input type="number" min="0" max="12" wire:model="maxHuecosDiarios" label="Huecos máximos" />
                                    <flux:input type="number" min="0" max="6" wire:model="minDescansoBloques" label="Descanso mínimo" />
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <flux:select wire:model="primeraHoraId" label="Primera hora">
                                        <flux:select.option value="">Sin límite</flux:select.option>
                                        @foreach ($horas as $hora)
                                            <flux:select.option value="{{ $hora->id }}">{{ substr($hora->hora_inicio, 0, 5) }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:select wire:model="ultimaHoraId" label="Última hora">
                                        <flux:select.option value="">Sin límite</flux:select.option>
                                        @foreach ($horas as $hora)
                                            <flux:select.option value="{{ $hora->id }}">{{ substr($hora->hora_fin, 0, 5) }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>

                                <div class="space-y-3 rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60">
                                    <flux:checkbox wire:model="permitirMultigrado" label="Puede atender varios grupos o grados" />
                                    <flux:checkbox wire:model="permitirMateriasDistintas" label="Puede impartir contenidos distintos simultáneamente" />
                                    <flux:checkbox wire:model="requiereMotivoTraslape" label="Exigir motivo para traslape excepcional" />
                                </div>

                                @if ($puedeEditar)
                                    <flux:button wire:click="guardarDisponibilidad" variant="primary" icon="check" class="w-full">Guardar disponibilidad</flux:button>
                                @endif
                            </aside>

                            <section class="overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-800">
                                <div class="border-b border-slate-200 p-5 dark:border-neutral-800">
                                    <flux:heading size="lg">Matriz semanal</flux:heading>
                                    <flux:text variant="subtle">Presiona cada bloque para recorrer: disponible → preferido → autorización → no disponible.</flux:text>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold">
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Disponible</span>
                                        <span class="rounded-full bg-sky-100 px-3 py-1 text-sky-700">Preferido</span>
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700">Con autorización</span>
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-rose-700">No disponible</span>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-950/60">
                                            <tr>
                                                <th class="px-3 py-3 text-left">Hora</th>
                                                @foreach ($dias as $dia)
                                                    <th class="px-3 py-3 text-center">{{ $dia->dia }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                            @foreach ($horas as $hora)
                                                <tr>
                                                    <td class="whitespace-nowrap px-3 py-3 font-black text-slate-600 dark:text-neutral-300">
                                                        {{ substr($hora->hora_inicio, 0, 5) }}–{{ substr($hora->hora_fin, 0, 5) }}
                                                    </td>
                                                    @foreach ($dias as $dia)
                                                        @php($estadoDisp = $disponibilidad[$dia->id.'-'.$hora->id] ?? 'disponible')
                                                        <td class="p-2 text-center">
                                                            <button
                                                                type="button"
                                                                wire:click="ciclarDisponibilidad({{ $dia->id }}, {{ $hora->id }})"
                                                                @disabled(!$puedeEditar || !$profesorId)
                                                                @class([
                                                                    'min-h-11 w-full rounded-xl border px-2 py-2 text-[11px] font-black uppercase transition disabled:cursor-not-allowed disabled:opacity-50',
                                                                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $estadoDisp === 'disponible',
                                                                    'border-sky-200 bg-sky-50 text-sky-700' => $estadoDisp === 'preferido',
                                                                    'border-amber-200 bg-amber-50 text-amber-700' => $estadoDisp === 'autorizacion',
                                                                    'border-rose-200 bg-rose-50 text-rose-700' => $estadoDisp === 'no_disponible',
                                                                ])
                                                            >
                                                                {{ str_replace('_', ' ', $estadoDisp) }}
                                                            </button>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </section>

                        <section class="grid gap-5 lg:grid-cols-2">
                            <div class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                <flux:heading>Excepción por fecha</flux:heading>
                                <flux:text variant="subtle">Permisos, cursos, ausencias o disponibilidad especial.</flux:text>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <flux:input type="date" wire:model="excepcionFecha" label="Fecha" />
                                    <flux:select wire:model="excepcionHoraId" label="Bloque">
                                        <flux:select.option value="">Todo el día</flux:select.option>
                                        @foreach ($horas as $hora)
                                            <flux:select.option value="{{ $hora->id }}">{{ substr($hora->hora_inicio, 0, 5) }}–{{ substr($hora->hora_fin, 0, 5) }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:select wire:model="excepcionEstado" label="Estado">
                                        <flux:select.option value="no_disponible">No disponible</flux:select.option>
                                        <flux:select.option value="autorizacion">Solo con autorización</flux:select.option>
                                        <flux:select.option value="preferido">Preferido</flux:select.option>
                                        <flux:select.option value="disponible">Disponible</flux:select.option>
                                    </flux:select>
                                    <flux:input wire:model="excepcionMotivo" label="Motivo" placeholder="Describe la excepción" />
                                </div>
                                @if ($puedeEditar)
                                    <flux:button wire:click="crearExcepcion" variant="primary" icon="plus" class="mt-4">Agregar excepción</flux:button>
                                @endif
                            </div>
                            <div class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                <div class="flex items-center justify-between"><flux:heading>Excepciones registradas</flux:heading><flux:badge color="blue">{{ $excepciones->count() }}</flux:badge></div>
                                <div class="mt-4 space-y-2">
                                    @forelse ($excepciones as $excepcion)
                                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-neutral-950/60">
                                            <div>
                                                <p class="font-black text-slate-800 dark:text-white">{{ $excepcion->fecha->format('d/m/Y') }} · {{ $excepcion->hora ? substr($excepcion->hora->hora_inicio, 0, 5) : 'Todo el día' }}</p>
                                                <p class="text-xs text-slate-500">{{ str_replace('_', ' ', ucfirst($excepcion->estado)) }} · {{ $excepcion->motivo }}</p>
                                            </div>
                                            @if ($puedeEditar)
                                                <flux:button wire:click="eliminarExcepcion({{ $excepcion->id }})" size="sm" variant="ghost" icon="trash"></flux:button>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">No hay excepciones para este docente.</p>
                                    @endforelse
                                </div>
                            </div>
                        </section>
                    </div>
                @endif

                @if ($seccion === 'reglas')
                    <div class="space-y-6">
                        <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div><flux:heading size="lg">Reglas globales</flux:heading><flux:text variant="subtle">Las obligatorias bloquean; las preferencias asignan puntaje a las propuestas.</flux:text></div>
                                @if ($puedeEditar)<flux:button wire:click="guardarReglasGlobales" variant="primary" icon="check">Guardar reglas</flux:button>@endif
                            </div>
                            <div class="mt-5 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-950/60"><tr><th class="px-4 py-3 text-left">Regla</th><th class="px-4 py-3 text-center">Tipo</th><th class="px-4 py-3 text-center">Activa</th><th class="px-4 py-3 text-center">Peso</th></tr></thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                        @foreach ($reglas as $regla)
                                            <tr>
                                                <td class="px-4 py-4"><p class="font-black text-slate-800 dark:text-white">{{ $regla->nombre }}</p><p class="text-xs text-slate-400">{{ $regla->codigo }}</p></td>
                                                <td class="px-4 py-4 text-center"><flux:badge :color="$regla->categoria === 'obligatoria' ? 'red' : 'blue'">{{ ucfirst($regla->categoria) }}</flux:badge></td>
                                                <td class="px-4 py-4 text-center"><flux:checkbox wire:model="reglasActivas.{{ $regla->id }}" :disabled="!$puedeEditar" /></td>
                                                <td class="px-4 py-4"><flux:input type="number" min="0" max="100" wire:model="reglasPesos.{{ $regla->id }}" :disabled="!$puedeEditar || $regla->categoria === 'obligatoria'" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-800">
                            <div class="flex flex-col gap-3 border-b border-slate-200 p-5 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
                                <div><flux:heading size="lg">Carga semanal por materia y grupo</flux:heading><flux:text variant="subtle">Valor base por asignación, editable sin cambiar el catálogo de materias.</flux:text></div>
                                @if ($puedeEditar)<flux:button wire:click="guardarCargas" variant="primary" icon="check">Guardar cargas</flux:button>@endif
                            </div>
                            <div class="max-h-[620px] overflow-auto">
                                <table class="min-w-[1100px] text-sm">
                                    <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-950"><tr><th class="px-4 py-3 text-left">Materia / grupo</th><th class="px-3 py-3">Sesiones</th><th class="px-3 py-3">Máx. día</th><th class="px-3 py-3">Días mínimos</th><th class="px-3 py-3">Consecutivos</th><th class="px-3 py-3">Máx. consecutivos</th><th class="px-3 py-3">Preferencia</th><th class="px-3 py-3">Multigrado</th><th class="px-3 py-3">Fijar</th></tr></thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                        @foreach ($asignaciones as $asignacion)
                                            <tr wire:key="carga-horario-{{ $asignacion->id }}">
                                                <td class="px-4 py-4"><p class="font-black text-slate-800 dark:text-white">{{ $asignacion->materia?->materia ?? 'Sin materia' }}</p><p class="text-xs text-slate-500">{{ $asignacion->grupo?->grado?->nombre }} · Grupo {{ $asignacion->grupo?->asignacionGrupo?->nombre ?? '—' }} · {{ trim(($asignacion->profesor?->nombre ?? '').' '.($asignacion->profesor?->apellido_paterno ?? '')) ?: 'Sin docente' }}</p></td>
                                                <td class="px-3 py-3"><flux:input type="number" min="1" max="20" wire:model="cargas.{{ $asignacion->id }}.sesiones" /></td>
                                                <td class="px-3 py-3"><flux:input type="number" min="1" max="10" wire:model="cargas.{{ $asignacion->id }}.max_dia" /></td>
                                                <td class="px-3 py-3"><flux:input type="number" min="1" max="6" wire:model="cargas.{{ $asignacion->id }}.dias_minimos" /></td>
                                                <td class="px-3 py-3 text-center"><flux:checkbox wire:model="cargas.{{ $asignacion->id }}.consecutivos" /></td>
                                                <td class="px-3 py-3"><flux:input type="number" min="1" max="6" wire:model="cargas.{{ $asignacion->id }}.max_consecutivos" /></td>
                                                <td class="px-3 py-3"><flux:select wire:model="cargas.{{ $asignacion->id }}.preferencia"><flux:select.option value="cualquiera">Cualquiera</flux:select.option><flux:select.option value="primeras">Primeras</flux:select.option><flux:select.option value="ultimas">Últimas</flux:select.option></flux:select></td>
                                                <td class="px-3 py-3 text-center"><flux:checkbox wire:model="cargas.{{ $asignacion->id }}.multigrado" /></td>
                                                <td class="px-3 py-3 text-center"><flux:checkbox wire:model="cargas.{{ $asignacion->id }}.bloqueada" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                @endif

                @if ($seccion === 'propuestas')
                    <div class="space-y-6">
                        <section class="relative overflow-hidden rounded-3xl border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-lime-50 p-6 dark:border-sky-900 dark:from-sky-950/30 dark:via-neutral-900 dark:to-lime-950/20">
                            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                <div><flux:badge color="blue">CP-SAT + respaldo PHP</flux:badge><flux:heading size="xl" class="mt-3">Generar cuatro propuestas comparables</flux:heading><flux:text variant="subtle" class="mt-2">Menos huecos docentes, mejor distribución para alumnos, mayor respeto a preferencias y equilibrio general.</flux:text></div>
                                <div class="space-y-3 rounded-2xl bg-white/80 p-4 dark:bg-neutral-900/70">
                                    <flux:checkbox wire:model="conservarHorarioActual" label="Conservar el horario actual como bloques fijados" />
                                    @if ($puedeEditar)
                                        <flux:button wire:click="generarPropuestas" wire:loading.attr="disabled" wire:target="generarPropuestas" variant="primary" icon="sparkles" class="w-full">
                                            <span wire:loading.remove wire:target="generarPropuestas">Generar propuestas</span><span wire:loading wire:target="generarPropuestas">Optimizando...</span>
                                        </flux:button>
                                    @endif
                                    <flux:button wire:click="crearBorradorActual" variant="ghost" icon="document-duplicate" class="w-full">Borrador desde horario actual</flux:button>
                                </div>
                            </div>
                        </section>

                        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            @forelse ($versiones->where('estado', 'propuesta')->take(8) as $version)
                                @php($conf = $version->conflictos ?? [])
                                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                    <div class="flex items-start justify-between gap-3"><flux:badge color="violet">V{{ $version->numero }}</flux:badge><span class="text-3xl font-black text-[#006492]">{{ number_format((float) $version->puntaje, 0) }}</span></div>
                                    <h4 class="mt-4 font-black text-slate-900 dark:text-white">{{ $version->nombre }}</h4>
                                    <p class="mt-1 text-xs text-slate-500">{{ $version->detalles_count }} bloques · {{ $version->created_at?->diffForHumans() }}</p>
                                    <div class="mt-4 grid grid-cols-2 gap-2 text-center text-xs font-black"><div class="rounded-xl bg-rose-50 p-2 text-rose-700">{{ count($conf['criticos'] ?? []) }} críticos</div><div class="rounded-xl bg-amber-50 p-2 text-amber-700">{{ count($conf['advertencias'] ?? []) }} avisos</div></div>
                                    <div class="mt-4 flex gap-2"><flux:button wire:click="abrirVersionEditor({{ $version->id }})" size="sm" variant="primary" icon="eye" class="flex-1">Revisar</flux:button></div>
                                </article>
                            @empty
                                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 p-10 text-center dark:border-neutral-700"><flux:icon.sparkles class="mx-auto h-10 w-10 text-slate-300" /><p class="mt-3 font-black text-slate-700 dark:text-neutral-200">Aún no hay propuestas</p><p class="text-sm text-slate-500">Configura cargas y disponibilidad, después ejecuta el optimizador.</p></div>
                            @endforelse
                        </section>
                    </div>
                @endif

                @if ($seccion === 'editor')
                    <div class="space-y-6">
                        <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                            <div class="grid gap-4 lg:grid-cols-12 lg:items-end">
                                <div class="lg:col-span-5"><flux:select wire:model.live="versionSeleccionadaId" label="Versión para editar"><flux:select.option value="">Selecciona</flux:select.option>@foreach($versiones as $version)<flux:select.option value="{{ $version->id }}">V{{ $version->numero }} · {{ $version->nombre }} · {{ $version->etiqueta_estado }}</flux:select.option>@endforeach</flux:select></div>
                                <div class="lg:col-span-3"><flux:select wire:model.live="editorGrupoId" label="Grupo visible"><flux:select.option value="">Todos</flux:select.option>@foreach($gruposEditor as $grupo)<flux:select.option value="{{ $grupo->id }}">{{ $grupo->grado?->nombre }} · Grupo {{ $grupo->asignacionGrupo?->nombre ?? '—' }}</flux:select.option>@endforeach</flux:select></div>
                                <div class="flex flex-wrap gap-2 lg:col-span-4 lg:justify-end">
                                    @if ($versionSeleccionada && $versionSeleccionada->estado === 'propuesta' && $puedeEditar)<flux:button wire:click="convertirEnBorrador" variant="primary" icon="pencil-square">Editar propuesta</flux:button>@endif
                                    @if ($versionSeleccionada && in_array($versionSeleccionada->estado, ['propuesta','borrador']) && $puedeEditar)
                                        <flux:button wire:click="regenerarNoBloqueados" icon="sparkles">Regenerar libres</flux:button>
                                        <flux:button wire:click="recalcularDiagnostico" icon="arrow-path">Revisar conflictos</flux:button>
                                        <flux:button wire:click="enviarRevision" icon="paper-airplane">Enviar a revisión</flux:button>
                                    @endif
                                </div>
                            </div>

                            @if ($versionSeleccionada)
                                @php($conflictosVersion = $versionSeleccionada->conflictos ?? [])
                                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                                    <div class="rounded-2xl bg-blue-50 p-4"><p class="text-xs font-black uppercase text-blue-600">Estado</p><p class="mt-1 font-black text-slate-900">{{ $versionSeleccionada->etiqueta_estado }}</p></div>
                                    <div class="rounded-2xl bg-emerald-50 p-4"><p class="text-xs font-black uppercase text-emerald-600">Puntaje</p><p class="mt-1 text-2xl font-black text-slate-900">{{ number_format((float)$versionSeleccionada->puntaje, 0) }}/100</p></div>
                                    <div class="rounded-2xl bg-rose-50 p-4"><p class="text-xs font-black uppercase text-rose-600">Críticos</p><p class="mt-1 text-2xl font-black text-slate-900">{{ count($conflictosVersion['criticos'] ?? []) }}</p></div>
                                    <div class="rounded-2xl bg-amber-50 p-4"><p class="text-xs font-black uppercase text-amber-600">Advertencias</p><p class="mt-1 text-2xl font-black text-slate-900">{{ count($conflictosVersion['advertencias'] ?? []) }}</p></div>
                                    <div class="rounded-2xl bg-violet-50 p-4"><p class="text-xs font-black uppercase text-violet-600">Intercambio</p><p class="mt-1 font-black text-slate-900">{{ $detalleIntercambioId ? 'Seleccionado' : 'Inactivo' }}</p></div>
                                </div>
                            @endif
                        </section>

                        @if ($versionSeleccionada)
                            @php($editableVersion = in_array($versionSeleccionada->estado, ['propuesta','borrador']) && $puedeEditar)
                            <section class="overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-800">
                                <div class="border-b border-slate-200 p-4 dark:border-neutral-800"><p class="text-sm font-semibold text-slate-600 dark:text-neutral-300">Arrastra una tarjeta a otra celda. Usa el candado para fijarla o “Intercambiar” para permutar dos clases.</p></div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-[1100px] text-sm">
                                        <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-950">
                                            <tr>
                                                <th class="sticky left-0 z-10 bg-slate-50 px-3 py-3 text-left dark:bg-neutral-950">Hora</th>
                                                @foreach($dias as $dia)
                                                    @php($diaBloqueado = $versionSeleccionada->detalles->where('dia_id', $dia->id)->isNotEmpty() && $versionSeleccionada->detalles->where('dia_id', $dia->id)->every(fn($d) => $d->bloqueado))
                                                    <th class="px-3 py-3 text-center">
                                                        <div class="flex items-center justify-center gap-2">
                                                            <span>{{ $dia->dia }}</span>
                                                            @if ($editableVersion)
                                                                <button type="button" wire:click="alternarBloqueoDia({{ $dia->id }})" class="rounded-lg bg-white p-1 text-slate-500 shadow-sm" title="{{ $diaBloqueado ? 'Liberar día' : 'Fijar día' }}">
                                                                    @if ($diaBloqueado)<flux:icon.lock-closed class="h-3.5 w-3.5" />@else<flux:icon.lock-open class="h-3.5 w-3.5" />@endif
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                            @foreach ($horas as $hora)
                                                <tr>
                                                    <td class="sticky left-0 z-10 whitespace-nowrap bg-white px-3 py-3 font-black text-slate-500 dark:bg-neutral-900">{{ substr($hora->hora_inicio,0,5) }}–{{ substr($hora->hora_fin,0,5) }}</td>
                                                    @foreach ($dias as $dia)
                                                        @php($celdaDetalles = $detallesEditor->where('dia_id',$dia->id)->where('hora_id',$hora->id))
                                                        <td
                                                            class="min-w-44 border-l border-slate-100 p-2 align-top dark:border-neutral-800"
                                                            x-on:dragover.prevent
                                                            x-on:drop.prevent="if (dragId) { $wire.moverDetalle(dragId, {{ $dia->id }}, {{ $hora->id }}); dragId = null }"
                                                        >
                                                            <div class="min-h-20 space-y-2 rounded-xl border border-dashed border-slate-200 p-1 dark:border-neutral-700">
                                                                @foreach ($celdaDetalles as $detalle)
                                                                    <article
                                                                        wire:key="editor-detalle-{{ $detalle->id }}"
                                                                        draggable="{{ $editableVersion && !$detalle->bloqueado ? 'true' : 'false' }}"
                                                                        x-on:dragstart="dragId = {{ $detalle->id }}"
                                                                        x-on:dragend="dragId = null"
                                                                        @class([
                                                                            'cursor-grab rounded-xl border p-3 shadow-sm transition active:cursor-grabbing',
                                                                            'border-sky-200 bg-sky-50' => !$detalle->traslape_excepcional && !$detalle->sesion_compartida,
                                                                            'border-emerald-200 bg-emerald-50' => $detalle->sesion_compartida,
                                                                            'border-amber-200 bg-amber-50' => $detalle->traslape_excepcional,
                                                                            'ring-2 ring-violet-400' => $detalleIntercambioId === $detalle->id,
                                                                        ])
                                                                    >
                                                                        <div class="flex items-start justify-between gap-2"><p class="text-xs font-black text-slate-900">{{ $detalle->asignacionMateria?->materia?->materia ?? 'Actividad' }}</p>@if($detalle->bloqueado)<flux:icon.lock-closed class="h-4 w-4 text-slate-500" />@endif</div>
                                                                        <p class="mt-1 text-[11px] text-slate-600">{{ $detalle->grado?->nombre }} · Grupo {{ $detalle->grupo?->asignacionGrupo?->nombre ?? '—' }}</p>
                                                                        <p class="mt-1 text-[10px] text-slate-500">{{ trim(($detalle->profesor?->nombre ?? '').' '.($detalle->profesor?->apellido_paterno ?? '')) ?: 'Sin docente' }}</p>
                                                                        @if($detalle->sesion_compartida)<span class="mt-2 inline-flex rounded-full bg-emerald-100 px-2 py-1 text-[9px] font-black uppercase text-emerald-700">Compartida</span>@endif
                                                                        @if($detalle->traslape_excepcional)<span class="mt-2 inline-flex rounded-full bg-amber-100 px-2 py-1 text-[9px] font-black uppercase text-amber-700">Excepcional</span>@endif
                                                                        @if ($editableVersion)
                                                                            <div class="mt-2 flex flex-wrap gap-1">
                                                                                <button type="button" wire:click="alternarBloqueo({{ $detalle->id }})" class="rounded-lg bg-white px-2 py-1 text-[10px] font-black text-slate-600">{{ $detalle->bloqueado ? 'Liberar' : 'Fijar' }}</button>
                                                                                <button type="button" wire:click="seleccionarIntercambio({{ $detalle->id }})" class="rounded-lg bg-white px-2 py-1 text-[10px] font-black text-violet-600">Intercambiar</button>
                                                                                <button type="button" wire:click="abrirCambioProfesor({{ $detalle->id }})" class="rounded-lg bg-white px-2 py-1 text-[10px] font-black text-blue-600">Docente</button>
                                                                                <button type="button" wire:click="abrirCoensenanza({{ $detalle->id }})" class="rounded-lg bg-white px-2 py-1 text-[10px] font-black text-emerald-600">Coenseñanza</button>
                                                                            </div>
                                                                        @endif
                                                                    </article>
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            <section class="grid gap-5 xl:grid-cols-2">
                                <div class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                    <div class="flex items-center justify-between"><flux:heading>Simultaneidades docentes</flux:heading><flux:badge color="blue">{{ $simultaneidadesEditor->count() }}</flux:badge></div>
                                    <div class="mt-4 space-y-3">
                                        @forelse($simultaneidadesEditor as $sim)
                                            <article class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-800"><div class="flex items-start justify-between gap-3"><div><p class="font-black text-slate-900 dark:text-white">{{ $sim['docente'] }}</p><p class="mt-1 text-sm text-slate-500">{{ $sim['dia'] }} · {{ $sim['hora'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $sim['grupos'] }}</p></div><flux:badge :color="$sim['clasificada'] ? 'green' : 'red'">{{ $sim['clasificada'] ? 'Clasificada' : 'Pendiente' }}</flux:badge></div>@if($editableVersion)<flux:button wire:click="abrirClasificacion({{ $sim['profesor_id'] }},{{ $sim['dia_id'] }},{{ $sim['hora_id'] }})" size="sm" class="mt-3" icon="tag">Clasificar</flux:button>@endif</article>
                                        @empty<p class="text-sm text-slate-500">No existen docentes atendiendo varios grupos en el mismo bloque.</p>@endforelse
                                    </div>
                                </div>
                                <div class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                    <flux:heading>Diagnóstico de publicación</flux:heading>
                                    @php($conflictosVersion = $versionSeleccionada->conflictos ?? [])
                                    <div class="mt-4 max-h-96 space-y-3 overflow-y-auto">
                                        @foreach (['criticos'=>'rose','advertencias'=>'amber','informativos'=>'sky'] as $tipo => $tono)
                                            @foreach (($conflictosVersion[$tipo] ?? []) as $hallazgo)
                                                <article class="rounded-2xl border p-4 {{ $tono === 'rose' ? 'border-rose-200 bg-rose-50' : ($tono === 'amber' ? 'border-amber-200 bg-amber-50' : 'border-sky-200 bg-sky-50') }}"><p class="font-black text-slate-900">{{ $hallazgo['titulo'] }}</p><p class="mt-1 text-sm text-slate-600">{{ $hallazgo['mensaje'] }}</p><p class="mt-2 text-xs text-slate-400">{{ $hallazgo['grupo'] ?? '' }} · {{ $hallazgo['dia'] ?? '' }} {{ $hallazgo['hora'] ?? '' }}</p></article>
                                            @endforeach
                                        @endforeach
                                        @if(empty($conflictosVersion['criticos']) && empty($conflictosVersion['advertencias']) && empty($conflictosVersion['informativos']))<flux:callout variant="success" icon="check-circle" heading="Sin conflictos">La versión puede avanzar a revisión.</flux:callout>@endif
                                    </div>
                                </div>
                            </section>
                        @else
                            <div class="rounded-3xl border border-dashed border-slate-300 p-12 text-center dark:border-neutral-700"><p class="font-black text-slate-700 dark:text-neutral-200">Selecciona una versión para abrir el editor.</p></div>
                        @endif
                    </div>
                @endif

                @if ($seccion === 'versiones')
                    <div class="space-y-6">
                        <section class="overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-800">
                            <div class="border-b border-slate-200 p-5 dark:border-neutral-800"><flux:heading size="lg">Historial de versiones</flux:heading><flux:text variant="subtle">Una versión publicada nunca se sobrescribe; restaurar crea un nuevo borrador.</flux:text></div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-950"><tr><th class="px-4 py-3 text-left">Versión</th><th class="px-4 py-3 text-center">Estado</th><th class="px-4 py-3 text-center">Puntaje</th><th class="px-4 py-3 text-center">Bloques</th><th class="px-4 py-3 text-left">Publicación</th><th class="px-4 py-3 text-center">Acciones</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                    @foreach($versiones as $version)
                                        <tr><td class="px-4 py-4"><p class="font-black text-slate-900 dark:text-white">V{{ $version->numero }} · {{ $version->nombre }}</p><p class="text-xs text-slate-400">{{ $version->objetivo }} · {{ $version->created_at?->format('d/m/Y H:i') }}</p></td><td class="px-4 py-4 text-center"><flux:badge :color="match($version->estado){'publicada'=>'green','programada'=>'blue','en_revision'=>'amber','sustituida'=>'zinc','archivada'=>'zinc','propuesta'=>'violet',default=>'orange'}">{{ $version->etiqueta_estado }}</flux:badge></td><td class="px-4 py-4 text-center font-black">{{ $version->puntaje !== null ? number_format((float)$version->puntaje,0).'/100' : '—' }}</td><td class="px-4 py-4 text-center">{{ $version->detalles_count }}</td><td class="px-4 py-4 text-xs text-slate-500">{{ $version->publicado_at?->format('d/m/Y H:i') ?? ($version->publicar_at ? 'Programada '.$version->publicar_at->format('d/m/Y H:i') : 'Sin publicar') }}</td><td class="px-4 py-4"><div class="flex justify-center gap-2"><flux:button wire:click="abrirVersionEditor({{ $version->id }})" size="sm" icon="eye">Abrir</flux:button>@if(in_array($version->estado,['publicada','sustituida','archivada']) && $puedeEditar)<flux:button wire:click="crearRestauracion({{ $version->id }})" size="sm" icon="arrow-uturn-left">Restaurar</flux:button>@endif</div></td></tr>
                                    @endforeach
                                </tbody></table>
                            </div>
                        </section>

                        @if($versionSeleccionada)
                            <section class="grid gap-5 xl:grid-cols-[1fr_1fr]">
                                <div class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800"><flux:heading>Eventos de la versión seleccionada</flux:heading><div class="mt-4 max-h-96 space-y-4 overflow-y-auto">@forelse($versionSeleccionada->eventos as $evento)<div class="relative pl-6 before:absolute before:left-[5px] before:top-5 before:h-full before:w-px before:bg-slate-200 last:before:hidden"><span class="absolute left-0 top-1.5 h-3 w-3 rounded-full bg-[#006492]"></span><p class="font-black text-slate-900 dark:text-white">{{ $evento->titulo }}</p>@if($evento->descripcion)<p class="mt-1 text-sm text-slate-500">{{ $evento->descripcion }}</p>@endif<p class="mt-1 text-xs text-slate-400">{{ $evento->ocurrido_at?->format('d/m/Y H:i') }} · {{ $evento->usuario?->name ?? 'Sistema' }}</p></div>@empty<p class="text-sm text-slate-500">Sin eventos.</p>@endforelse</div></div>
                                <div class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800"><flux:heading>Publicar o programar</flux:heading><flux:text variant="subtle">Se reemplaza únicamente la versión oficial del ciclo y nivel; la anterior queda como sustituida.</flux:text>@if($puedePublicar && in_array($versionSeleccionada->estado,['propuesta','borrador','en_revision','programada']))<div class="mt-5 space-y-4"><flux:textarea wire:model="publicacionMotivo" label="Motivo de publicación" rows="3" /><flux:input type="datetime-local" wire:model="publicacionFecha" label="Vigencia o publicación" /><flux:checkbox wire:model="aceptarAdvertencias" label="Acepto y justifico las advertencias no críticas" /><flux:input type="password" wire:model="publicacionPassword" label="Contraseña" /><flux:input wire:model="publicacionConfirmacion" label="Escribe PUBLICAR" /><flux:button wire:click="publicarVersion" variant="primary" icon="cloud-arrow-up" class="w-full">Publicar / programar</flux:button></div>@else<flux:callout class="mt-4" icon="information-circle" heading="Publicación no disponible">Selecciona un borrador, propuesta o versión en revisión y verifica tu permiso.</flux:callout>@endif</div>
                            </section>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div x-cloak x-show="$wire.modalCambiarProfesor" x-transition.opacity class="fixed inset-0 z-[113] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
        <div x-on:click.outside="$wire.modalCambiarProfesor = false" class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
            <flux:heading size="lg">Cambiar docente del bloque</flux:heading>
            <flux:text variant="subtle">El cambio solo afecta la versión en edición y vuelve a calcular todos los conflictos.</flux:text>
            <div class="mt-5 space-y-4">
                <flux:select wire:model="cambioProfesorId" label="Docente">
                    <flux:select.option value="">Sin docente</flux:select.option>
                    @foreach ($profesores as $profesor)
                        <flux:select.option value="{{ $profesor->id }}">{{ trim($profesor->apellido_paterno.' '.$profesor->apellido_materno.' '.$profesor->nombre) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:textarea wire:model="cambioProfesorMotivo" label="Motivo del cambio" rows="4" />
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <flux:button x-on:click="$wire.modalCambiarProfesor = false">Cancelar</flux:button>
                <flux:button wire:click="guardarCambioProfesor" variant="primary" icon="check">Guardar cambio</flux:button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="$wire.modalCoensenanza" x-transition.opacity class="fixed inset-0 z-[112] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
        <div x-on:click.outside="$wire.modalCoensenanza = false" class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
            <flux:heading size="lg">Agregar docente de apoyo</flux:heading>
            <flux:text variant="subtle">El grupo conservará una sola actividad, pero aparecerán ambos docentes como coenseñanza en el mismo bloque.</flux:text>
            <div class="mt-5 space-y-4">
                <flux:select wire:model="coensenanzaProfesorId" label="Docente de apoyo">
                    <flux:select.option value="">Selecciona</flux:select.option>
                    @foreach ($profesores as $profesor)
                        <flux:select.option value="{{ $profesor->id }}">{{ trim($profesor->apellido_paterno.' '.$profesor->apellido_materno.' '.$profesor->nombre) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:textarea wire:model="coensenanzaMotivo" label="Motivo" rows="4" />
            </div>
            <div class="mt-6 flex justify-end gap-2"><flux:button x-on:click="$wire.modalCoensenanza = false">Cancelar</flux:button><flux:button wire:click="guardarCoensenanza" variant="primary" icon="user-plus">Agregar apoyo</flux:button></div>
        </div>
    </div>

    <div x-cloak x-show="$wire.modalClasificar" x-transition.opacity class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
        <div x-on:click.outside="$wire.modalClasificar = false" class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
            <flux:heading size="lg">Clasificar simultaneidad docente</flux:heading>
            <flux:text variant="subtle">Una sesión compartida representa una sola clase con varios grupos. Un traslape excepcional representa actividades distintas y exige justificación.</flux:text>
            <div class="mt-5 space-y-4"><flux:select wire:model="clasificarTipo" label="Tipo"><flux:select.option value="compartida">Sesión compartida / multigrado</flux:select.option><flux:select.option value="excepcional">Traslape excepcional</flux:select.option></flux:select><flux:textarea wire:model="clasificarMotivo" label="Motivo obligatorio" rows="4" /></div>
            <div class="mt-6 flex justify-end gap-2"><flux:button x-on:click="$wire.modalClasificar = false">Cancelar</flux:button><flux:button wire:click="guardarClasificacion" variant="primary" icon="check">Guardar clasificación</flux:button></div>
        </div>
    </div>
</div>
