@php
    $riesgoClases = [
        'bajo' => ['badge' => 'green', 'fondo' => 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-900', 'punto' => 'bg-emerald-500', 'texto' => 'text-emerald-700 dark:text-emerald-300'],
        'moderado' => ['badge' => 'amber', 'fondo' => 'bg-amber-50 border-amber-200 dark:bg-amber-950/30 dark:border-amber-900', 'punto' => 'bg-amber-500', 'texto' => 'text-amber-700 dark:text-amber-300'],
        'alto' => ['badge' => 'orange', 'fondo' => 'bg-orange-50 border-orange-200 dark:bg-orange-950/30 dark:border-orange-900', 'punto' => 'bg-orange-500', 'texto' => 'text-orange-700 dark:text-orange-300'],
        'critico' => ['badge' => 'red', 'fondo' => 'bg-rose-50 border-rose-200 dark:bg-rose-950/30 dark:border-rose-900', 'punto' => 'bg-rose-500', 'texto' => 'text-rose-700 dark:text-rose-300'],
    ];
@endphp

<div class="space-y-6" x-data>
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>
        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <flux:badge color="blue" size="sm">Semáforo académico</flux:badge>
                    <flux:badge color="green" size="sm">Seguimiento preventivo</flux:badge>
                </div>
                <flux:heading size="xl">Riesgo académico y seguimiento</flux:heading>
                <flux:text variant="subtle" class="mt-2">
                    Detecta señales tempranas, explica cada puntaje y convierte las alertas en planes de intervención con responsables, fechas y evidencia.
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($puedeGestionar)
                    <flux:button wire:click="abrirReglas" icon="adjustments-horizontal" variant="filled">
                        Reglas y umbrales
                    </flux:button>
                    <flux:button wire:click="evaluarAhora" wire:loading.attr="disabled" wire:target="evaluarAhora" icon="arrow-path" variant="primary">
                        <span wire:loading.remove wire:target="evaluarAhora">Evaluar ahora</span>
                        <span wire:loading wire:target="evaluarAhora">Evaluando...</span>
                    </flux:button>
                @endif
            </div>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
        @foreach ([
            ['clave' => 'bajo', 'titulo' => 'Riesgo bajo', 'valor' => $resumen['bajo'], 'icono' => 'check-circle'],
            ['clave' => 'moderado', 'titulo' => 'Moderado', 'valor' => $resumen['moderado'], 'icono' => 'exclamation-circle'],
            ['clave' => 'alto', 'titulo' => 'Alto', 'valor' => $resumen['alto'], 'icono' => 'exclamation-triangle'],
            ['clave' => 'critico', 'titulo' => 'Crítico', 'valor' => $resumen['critico'], 'icono' => 'shield-exclamation'],
        ] as $tarjeta)
            @php($estilo = $riesgoClases[$tarjeta['clave']])
            <article class="rounded-2xl border p-4 {{ $estilo['fondo'] }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide {{ $estilo['texto'] }}">{{ $tarjeta['titulo'] }}</p>
                        <p class="mt-1 text-3xl font-black text-slate-900 dark:text-white">{{ $tarjeta['valor'] }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/80 shadow-sm dark:bg-neutral-900/70">
                        <flux:icon.chart-bar-square class="h-5 w-5 {{ $estilo['texto'] }}" />
                    </span>
                </div>
            </article>
        @endforeach

        <article class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/30">
            <p class="text-xs font-black uppercase tracking-wide text-sky-700 dark:text-sky-300">Casos activos</p>
            <p class="mt-1 text-3xl font-black text-slate-900 dark:text-white">{{ $resumen['casos_abiertos'] }}</p>
        </article>
        <article class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950/30">
            <p class="text-xs font-black uppercase tracking-wide text-violet-700 dark:text-violet-300">Acciones vencidas</p>
            <p class="mt-1 text-3xl font-black text-slate-900 dark:text-white">{{ $resumen['acciones_vencidas'] }}</p>
        </article>
        <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900 dark:bg-rose-950/30">
            <p class="text-xs font-black uppercase tracking-wide text-rose-700 dark:text-rose-300">Alertas</p>
            <p class="mt-1 text-3xl font-black text-slate-900 dark:text-white">{{ $resumen['alertas'] }}</p>
        </article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <flux:heading size="lg">Filtros de seguimiento</flux:heading>
                <flux:text variant="subtle">Combina ciclo, nivel, semáforo y estado del caso.</flux:text>
            </div>
            <flux:button wire:click="limpiarFiltros" variant="ghost" icon="x-mark">Limpiar</flux:button>
        </div>

        <div class="grid gap-4 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <flux:input wire:model.live.debounce.350ms="buscar" label="Buscar alumno" icon="magnifying-glass" placeholder="Nombre, matrícula o CURP" clearable />
            </div>
            <div class="lg:col-span-2">
                <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($ciclos as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="lg:col-span-2">
                <flux:select wire:model.live="nivel_id" label="Nivel">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($niveles as $nivel)
                        <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="lg:col-span-2">
                <flux:select wire:model.live="nivel_riesgo" label="Semáforo">
                    <flux:select.option value="todos">Todos</flux:select.option>
                    <flux:select.option value="bajo">Bajo</flux:select.option>
                    <flux:select.option value="moderado">Moderado</flux:select.option>
                    <flux:select.option value="alto">Alto</flux:select.option>
                    <flux:select.option value="critico">Crítico</flux:select.option>
                </flux:select>
            </div>
            <div class="lg:col-span-2">
                <flux:select wire:model.live="estado_seguimiento" label="Seguimiento">
                    <flux:select.option value="todos">Todos</flux:select.option>
                    <flux:select.option value="sin_caso">Sin caso</flux:select.option>
                    <flux:select.option value="abierto">Abierto</flux:select.option>
                    <flux:select.option value="en_seguimiento">En seguimiento</flux:select.option>
                    <flux:select.option value="pausado">Pausado</flux:select.option>
                    <flux:select.option value="cerrado">Cerrado</flux:select.option>
                </flux:select>
            </div>
            <div class="lg:col-span-3">
                <flux:select wire:model.live="responsable_id" label="Responsable">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($usuarios as $usuario)
                        <flux:select.option value="{{ $usuario->id }}">{{ $usuario->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="lg:col-span-3">
                <flux:select wire:model.live="orden" label="Ordenar">
                    <flux:select.option value="riesgo_desc">Mayor riesgo primero</flux:select.option>
                    <flux:select.option value="riesgo_asc">Menor riesgo primero</flux:select.option>
                    <flux:select.option value="reciente">Evaluación reciente</flux:select.option>
                    <flux:select.option value="nombre">Nombre A–Z</flux:select.option>
                </flux:select>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-neutral-950/70 dark:text-neutral-400">
                    <tr>
                        <th class="px-5 py-4 text-left font-black">Alumno</th>
                        <th class="px-4 py-4 text-center font-black">Semáforo</th>
                        <th class="px-4 py-4 text-center font-black">Contexto</th>
                        <th class="px-4 py-4 text-left font-black">Factores principales</th>
                        <th class="px-4 py-4 text-center font-black">Seguimiento</th>
                        <th class="px-5 py-4 text-center font-black">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($evaluaciones as $evaluacion)
                        @php
                            $estilo = $riesgoClases[$evaluacion->nivel_riesgo] ?? $riesgoClases['bajo'];
                            $caso = $evaluacion->casos->first();
                        @endphp
                        <tr class="transition hover:bg-slate-50/70 dark:hover:bg-neutral-800/40">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] font-black text-white">
                                        {{ $evaluacion->inicial_alumno }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-black text-slate-900 dark:text-white">{{ $evaluacion->nombre_alumno }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $evaluacion->inscripcion?->matricula ?: 'Sin matrícula' }} · {{ $evaluacion->inscripcion?->curp ?: 'Sin CURP' }}</p>
                                        <p class="mt-1 text-[11px] text-slate-400">Evaluado {{ $evaluacion->evaluado_at?->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="inline-flex min-w-24 flex-col items-center rounded-2xl border px-3 py-2 {{ $estilo['fondo'] }}">
                                    <span class="flex items-center gap-2 text-xs font-black uppercase {{ $estilo['texto'] }}">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $estilo['punto'] }}"></span>
                                        {{ $evaluacion->etiqueta_riesgo }}
                                    </span>
                                    <span class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $evaluacion->puntaje }}</span>
                                    <span class="text-[10px] text-slate-500">de 100</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center text-slate-600 dark:text-neutral-300">
                                <p class="font-bold">{{ $evaluacion->nivel?->nombre }}</p>
                                <p class="text-xs">{{ $evaluacion->semestre?->numero ? $evaluacion->semestre->numero.'° semestre' : $evaluacion->grado?->nombre }}</p>
                                <p class="mt-1 text-xs">Grupo {{ $evaluacion->grupo?->asignacionGrupo?->nombre ?? '—' }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $evaluacion->cicloEscolar?->nombre }}</p>
                            </td>
                            <td class="max-w-md px-4 py-4">
                                <div class="space-y-1.5">
                                    @forelse (collect($evaluacion->factores ?? [])->sortByDesc('puntos')->take(3) as $factor)
                                        <div class="flex items-start justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 dark:bg-neutral-950/60">
                                            <span class="text-xs font-semibold text-slate-700 dark:text-neutral-200">{{ $factor['detalle'] ?? $factor['nombre'] }}</span>
                                            <flux:badge color="red" size="sm">+{{ (int) $factor['puntos'] }}</flux:badge>
                                        </div>
                                    @empty
                                        <span class="text-xs font-semibold text-emerald-600">Sin factores de riesgo activos</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if ($caso)
                                    <flux:badge :color="match($caso->estado) { 'cerrado' => 'zinc', 'en_seguimiento' => 'blue', 'pausado' => 'amber', default => 'orange' }">
                                        {{ ucfirst(str_replace('_', ' ', $caso->estado)) }}
                                    </flux:badge>
                                    <p class="mt-2 text-xs font-bold text-slate-600 dark:text-neutral-300">{{ $caso->folio }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $caso->responsable?->name ?? 'Sin responsable' }}</p>
                                @else
                                    <flux:badge color="zinc">Sin seguimiento</flux:badge>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <flux:button wire:click="verEvaluacion({{ $evaluacion->id }})" size="sm" variant="primary" icon="eye">
                                    Revisar
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <flux:icon.chart-bar-square class="mx-auto h-10 w-10 text-slate-300" />
                                <p class="mt-4 font-black text-slate-700 dark:text-neutral-200">No hay evaluaciones para los filtros actuales</p>
                                <p class="mt-1 text-sm text-slate-500">Ejecuta “Evaluar ahora” para generar el semáforo del ciclo seleccionado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4 dark:border-neutral-800">
            {{ $evaluaciones->links() }}
        </div>
    </section>

    {{-- Modal de detalle y seguimiento --}}
    <div x-cloak x-show="$wire.modalDetalle" x-transition.opacity class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-6">
        <div x-on:click.outside="$wire.cerrarDetalle()" class="max-h-[94vh] w-full max-w-7xl overflow-y-auto rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
            @if ($evaluacionSeleccionada)
                @php
                    $estiloDetalle = $riesgoClases[$evaluacionSeleccionada->nivel_riesgo] ?? $riesgoClases['bajo'];
                @endphp
                <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 px-5 py-5 backdrop-blur dark:border-neutral-800 dark:bg-neutral-900/95 sm:px-7">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-4">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] text-lg font-black text-white">{{ $evaluacionSeleccionada->inicial_alumno }}</span>
                            <div>
                                <flux:heading size="lg">{{ $evaluacionSeleccionada->nombre_alumno }}</flux:heading>
                                <flux:text variant="subtle">{{ $evaluacionSeleccionada->inscripcion?->matricula ?: 'Sin matrícula' }} · {{ $evaluacionSeleccionada->nivel?->nombre ?: 'Sin nivel' }} · {{ $evaluacionSeleccionada->cicloEscolar?->nombre ?: 'Sin ciclo' }}</flux:text>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="rounded-2xl border px-4 py-2 {{ $estiloDetalle['fondo'] }}">
                                <span class="font-black {{ $estiloDetalle['texto'] }}">{{ $evaluacionSeleccionada->etiqueta_riesgo }} · {{ $evaluacionSeleccionada->puntaje }}/100</span>
                            </div>
                            <flux:button wire:click="verTrayectoria" icon="clock">Trayectoria</flux:button>
                            <flux:button wire:click="cerrarDetalle" variant="ghost" icon="x-mark">Cerrar</flux:button>
                        </div>
                    </div>
                </header>

                <div class="grid gap-6 p-5 sm:p-7 xl:grid-cols-[1.05fr_1.95fr]">
                    <aside class="space-y-5">
                        <section class="rounded-2xl border border-slate-200 p-5 dark:border-neutral-800">
                            <flux:heading>Factores detectados</flux:heading>
                            <div class="mt-4 space-y-3">
                                @forelse (collect($evaluacionSeleccionada->factores ?? [])->sortByDesc('puntos') as $factor)
                                    <article class="rounded-2xl border border-rose-100 bg-rose-50/60 p-4 dark:border-rose-900/60 dark:bg-rose-950/20">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-black text-slate-900 dark:text-white">{{ $factor['nombre'] }}</p>
                                                <p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">{{ $factor['detalle'] }}</p>
                                            </div>
                                            <flux:badge color="red">+{{ (int) $factor['puntos'] }}</flux:badge>
                                        </div>
                                    </article>
                                @empty
                                    <flux:callout variant="success" icon="check-circle" heading="Sin factores activos">El alumno permanece en riesgo bajo con la información disponible.</flux:callout>
                                @endforelse
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-200 p-5 dark:border-neutral-800">
                            <flux:heading>Métricas utilizadas</flux:heading>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                @foreach ([
                                    'Promedio' => $evaluacionSeleccionada->metricas['promedio'] ?? '—',
                                    'Reprobadas' => $evaluacionSeleccionada->metricas['reprobadas'] ?? 0,
                                    'Pendientes' => $evaluacionSeleccionada->metricas['calificaciones_pendientes'] ?? 0,
                                    'Descenso' => $evaluacionSeleccionada->metricas['descenso_periodos'] ?? 0,
                                    'Asistencia' => isset($evaluacionSeleccionada->metricas['asistencia_promedio']) && $evaluacionSeleccionada->metricas['asistencia_promedio'] !== null ? $evaluacionSeleccionada->metricas['asistencia_promedio'].'%' : '—',
                                    'Integridad crítica' => $evaluacionSeleccionada->metricas['integridad_critica'] ?? 0,
                                ] as $etiqueta => $valor)
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-neutral-950/60">
                                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">{{ $etiqueta }}</p>
                                        <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ $valor }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-200 p-5 dark:border-neutral-800">
                            <flux:heading>Evolución del semáforo</flux:heading>
                            <div class="mt-4 space-y-3">
                                @forelse ($evolucion as $historialRiesgo)
                                    @php($estiloEvolucion = $riesgoClases[$historialRiesgo->nivel_riesgo] ?? $riesgoClases['bajo'])
                                    <div class="flex items-center gap-3">
                                        <span class="h-3 w-3 rounded-full {{ $estiloEvolucion['punto'] }}"></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="font-black {{ $estiloEvolucion['texto'] }}">{{ $historialRiesgo->etiqueta_riesgo }} · {{ $historialRiesgo->puntaje }}</span>
                                                <span class="text-xs text-slate-400">{{ $historialRiesgo->evaluado_at?->format('d/m/Y H:i') }}</span>
                                            </div>
                                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-neutral-800">
                                                <div class="h-full {{ $estiloEvolucion['punto'] }}" style="width: {{ $historialRiesgo->puntaje }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">Aún no existen reevaluaciones históricas.</p>
                                @endforelse
                            </div>
                        </section>
                    </aside>

                    <main class="space-y-5">
                        @if (! $casoSeleccionado)
                            <section class="rounded-3xl border border-dashed border-[#006492]/40 bg-blue-50/40 p-7 text-center dark:bg-blue-950/20">
                                <flux:icon.user-plus class="mx-auto h-10 w-10 text-[#006492]" />
                                <flux:heading size="lg" class="mt-3">Aún no existe un seguimiento</flux:heading>
                                <flux:text variant="subtle" class="mt-2">Abre un expediente de intervención para asignar responsable, crear un plan y dar seguimiento a cada acción.</flux:text>
                                @if ($puedeGestionar)
                                    <flux:button wire:click="abrirFormularioSeguimiento" variant="primary" icon="plus" class="mt-5">Abrir seguimiento</flux:button>
                                @endif
                            </section>
                        @else
                            <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:badge color="blue">{{ $casoSeleccionado->folio }}</flux:badge>
                                            <flux:badge :color="$casoSeleccionado->estado === 'cerrado' ? 'zinc' : 'green'">{{ ucfirst(str_replace('_', ' ', $casoSeleccionado->estado)) }}</flux:badge>
                                            <flux:badge :color="$casoSeleccionado->prioridad === 'critica' ? 'red' : ($casoSeleccionado->prioridad === 'alta' ? 'orange' : 'amber')">Prioridad {{ $casoSeleccionado->prioridad }}</flux:badge>
                                        </div>
                                        <flux:heading size="lg" class="mt-3">Expediente de seguimiento académico</flux:heading>
                                        <flux:text variant="subtle">Abierto {{ $casoSeleccionado->abierto_at?->diffForHumans() }} · {{ $casoSeleccionado->apertura_automatica ? 'Apertura automática' : 'Apertura manual' }}</flux:text>
                                    </div>
                                </div>

                                @if ($puedeGestionar && $casoSeleccionado->estado !== 'cerrado')
                                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                                        <flux:textarea wire:model="resumen_caso" label="Resumen del seguimiento" rows="3" />
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <flux:select wire:model="prioridad_caso" label="Prioridad">
                                                <flux:select.option value="moderada">Moderada</flux:select.option>
                                                <flux:select.option value="alta">Alta</flux:select.option>
                                                <flux:select.option value="critica">Crítica</flux:select.option>
                                            </flux:select>
                                            <flux:select wire:model="responsable_caso_id" label="Responsable">
                                                <flux:select.option value="">Sin asignar</flux:select.option>
                                                @foreach ($usuarios as $usuario)
                                                    <flux:select.option value="{{ $usuario->id }}">{{ $usuario->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:input type="date" wire:model="proxima_revision_at" label="Próxima revisión" />
                                            <div class="flex items-end">
                                                <flux:button wire:click="guardarDatosCaso" variant="primary" icon="check" class="w-full">Guardar datos</flux:button>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-neutral-950/60"><span class="text-xs text-slate-400">Responsable</span><p class="font-bold">{{ $casoSeleccionado->responsable?->name ?? 'Sin asignar' }}</p></div>
                                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-neutral-950/60"><span class="text-xs text-slate-400">Próxima revisión</span><p class="font-bold">{{ $casoSeleccionado->proxima_revision_at?->format('d/m/Y') ?? 'Sin fecha' }}</p></div>
                                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-neutral-950/60"><span class="text-xs text-slate-400">Puntaje actual</span><p class="font-bold">{{ $casoSeleccionado->puntaje_actual }}/100</p></div>
                                    </div>
                                @endif
                            </section>

                            <section class="grid gap-5 xl:grid-cols-2">
                                <div class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                    <div class="flex items-center justify-between gap-3">
                                        <div><flux:heading>Planes de intervención</flux:heading><flux:text variant="subtle">Objetivos generales del caso.</flux:text></div>
                                        <flux:badge color="blue">{{ $casoSeleccionado->planes->count() }}</flux:badge>
                                    </div>
                                    <div class="mt-4 space-y-3">
                                        @forelse ($casoSeleccionado->planes as $plan)
                                            <article class="rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div><p class="font-black text-slate-900 dark:text-white">{{ $plan->nombre }}</p><p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">{{ $plan->objetivo }}</p></div>
                                                    <flux:badge color="green">{{ ucfirst($plan->estado) }}</flux:badge>
                                                </div>
                                                <p class="mt-2 text-xs text-slate-400">Responsable: {{ $plan->responsable?->name ?? 'Sin asignar' }} · Meta: {{ $plan->fecha_fin_prevista?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                                            </article>
                                        @empty
                                            <p class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-500 dark:border-neutral-700">Todavía no se ha definido un plan.</p>
                                        @endforelse
                                    </div>

                                    @if ($puedeGestionar && $casoSeleccionado->estado !== 'cerrado')
                                        <div class="mt-5 space-y-3 border-t border-slate-100 pt-5 dark:border-neutral-800">
                                            <flux:input wire:model="plan_nombre" label="Nombre del plan" placeholder="Ej. Recuperación académica del primer periodo" />
                                            <flux:textarea wire:model="plan_objetivo" label="Objetivo" rows="3" />
                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <flux:input type="date" wire:model="plan_fecha_fin" label="Fecha prevista" />
                                                <flux:select wire:model="plan_responsable_id" label="Responsable">
                                                    <flux:select.option value="">Sin asignar</flux:select.option>
                                                    @foreach ($usuarios as $usuario)<flux:select.option value="{{ $usuario->id }}">{{ $usuario->name }}</flux:select.option>@endforeach
                                                </flux:select>
                                            </div>
                                            <flux:button wire:click="crearPlan" variant="primary" icon="plus" class="w-full">Crear plan</flux:button>
                                        </div>
                                    @endif
                                </div>

                                <div class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                    <div class="flex items-center justify-between gap-3">
                                        <div><flux:heading>Nueva acción</flux:heading><flux:text variant="subtle">Una tarea concreta, medible y con fecha.</flux:text></div>
                                    </div>
                                    @if ($puedeGestionar && $casoSeleccionado->estado !== 'cerrado')
                                        <div class="mt-4 space-y-3">
                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <flux:select wire:model="accion_tipo" label="Tipo">
                                                    <flux:select.option value="regularizacion">Regularización</flux:select.option>
                                                    <flux:select.option value="tutoria">Tutoría</flux:select.option>
                                                    <flux:select.option value="asistencia">Asistencia</flux:select.option>
                                                    <flux:select.option value="orientacion">Orientación</flux:select.option>
                                                    <flux:select.option value="contacto_tutor">Contacto con tutor</flux:select.option>
                                                    <flux:select.option value="documentacion">Documentación</flux:select.option>
                                                    <flux:select.option value="otra">Otra</flux:select.option>
                                                </flux:select>
                                                <flux:select wire:model="accion_plan_id" label="Plan relacionado">
                                                    <flux:select.option value="">Sin plan</flux:select.option>
                                                    @foreach ($casoSeleccionado->planes as $plan)<flux:select.option value="{{ $plan->id }}">{{ $plan->nombre }}</flux:select.option>@endforeach
                                                </flux:select>
                                            </div>
                                            <flux:textarea wire:model="accion_descripcion" label="Descripción de la acción" rows="3" />
                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <flux:input type="date" wire:model="accion_fecha_limite" label="Fecha límite" />
                                                <flux:select wire:model="accion_responsable_id" label="Responsable">
                                                    <flux:select.option value="">Sin asignar</flux:select.option>
                                                    @foreach ($usuarios as $usuario)<flux:select.option value="{{ $usuario->id }}">{{ $usuario->name }}</flux:select.option>@endforeach
                                                </flux:select>
                                            </div>
                                            <flux:button wire:click="crearAccion" variant="primary" icon="plus" class="w-full">Agregar acción</flux:button>
                                        </div>
                                    @else
                                        <flux:callout icon="lock-closed" heading="Seguimiento cerrado">Reabre el expediente para agregar nuevas acciones.</flux:callout>
                                    @endif
                                </div>
                            </section>

                            <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                <div class="flex items-center justify-between gap-3"><div><flux:heading>Acciones del plan</flux:heading><flux:text variant="subtle">Controla el avance, evidencia y resultado.</flux:text></div><flux:badge color="violet">{{ $casoSeleccionado->acciones->count() }}</flux:badge></div>
                                <div class="mt-4 space-y-4">
                                    @forelse ($casoSeleccionado->acciones as $accion)
                                        @php($vencida = $accion->fecha_limite && $accion->fecha_limite->lt(today()) && !in_array($accion->estado, ['completada', 'cancelada']))
                                        <article class="rounded-2xl border p-4 {{ $vencida ? 'border-rose-200 bg-rose-50/40 dark:border-rose-900 dark:bg-rose-950/20' : 'border-slate-200 dark:border-neutral-800' }}">
                                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                <div><div class="flex flex-wrap items-center gap-2"><flux:badge color="blue">{{ ucfirst(str_replace('_', ' ', $accion->tipo)) }}</flux:badge>@if($vencida)<flux:badge color="red">Vencida</flux:badge>@endif</div><p class="mt-2 font-black text-slate-900 dark:text-white">{{ $accion->descripcion }}</p><p class="mt-1 text-xs text-slate-500">Responsable: {{ $accion->responsable?->name ?? 'Sin asignar' }} · Límite: {{ $accion->fecha_limite?->format('d/m/Y') ?? 'Sin fecha' }} · Plan: {{ $accion->plan?->nombre ?? 'General' }}</p></div>
                                            </div>
                                            @if ($puedeGestionar && $casoSeleccionado->estado !== 'cerrado')
                                                <div class="mt-4 grid gap-3 lg:grid-cols-12">
                                                    <div class="lg:col-span-3"><flux:select wire:model="acciones_estado.{{ $accion->id }}" label="Estado"><flux:select.option value="pendiente">Pendiente</flux:select.option><flux:select.option value="en_proceso">En proceso</flux:select.option><flux:select.option value="completada">Completada</flux:select.option><flux:select.option value="cancelada">Cancelada</flux:select.option></flux:select></div>
                                                    <div class="lg:col-span-4"><flux:textarea wire:model="acciones_evidencia.{{ $accion->id }}" label="Evidencia" rows="2" /></div>
                                                    <div class="lg:col-span-4"><flux:textarea wire:model="acciones_resultado.{{ $accion->id }}" label="Resultado" rows="2" /></div>
                                                    <div class="flex items-end lg:col-span-1"><flux:button wire:click="guardarAccion({{ $accion->id }})" variant="primary" icon="check" class="w-full"></flux:button></div>
                                                </div>
                                            @else
                                                @if($accion->evidencia || $accion->resultado)<div class="mt-3 rounded-xl bg-slate-50 p-3 text-sm dark:bg-neutral-950/60"><p><strong>Evidencia:</strong> {{ $accion->evidencia ?: '—' }}</p><p class="mt-1"><strong>Resultado:</strong> {{ $accion->resultado ?: '—' }}</p></div>@endif
                                            @endif
                                        </article>
                                    @empty
                                        <p class="rounded-xl border border-dashed border-slate-200 p-5 text-center text-sm text-slate-500 dark:border-neutral-700">No hay acciones registradas.</p>
                                    @endforelse
                                </div>
                            </section>

                            <section class="grid gap-5 xl:grid-cols-2">
                                <div class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                    <flux:heading>Alertas del caso</flux:heading>
                                    <div class="mt-4 space-y-3">
                                        @forelse ($casoSeleccionado->alertas->take(8) as $alerta)
                                            <article class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-800">
                                                <div class="flex items-start justify-between gap-3"><div><p class="font-black text-slate-900 dark:text-white">{{ $alerta->titulo }}</p><p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">{{ $alerta->mensaje }}</p><p class="mt-2 text-xs text-slate-400">{{ $alerta->generada_at?->format('d/m/Y H:i') }}</p></div><flux:badge :color="$alerta->estado === 'pendiente' ? 'red' : 'green'">{{ ucfirst($alerta->estado) }}</flux:badge></div>
                                                @if($puedeGestionar && $alerta->estado === 'pendiente')<flux:button wire:click="atenderAlerta({{ $alerta->id }})" size="sm" variant="ghost" icon="check" class="mt-3">Marcar atendida</flux:button>@endif
                                            </article>
                                        @empty
                                            <p class="text-sm text-slate-500">No hay alertas asociadas.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                    <flux:heading>Evolución y notas</flux:heading>
                                    @if ($puedeGestionar && $casoSeleccionado->estado !== 'cerrado')
                                        <div class="mt-4 space-y-3 rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60">
                                            <flux:input wire:model="nota_titulo" label="Título de la nota" />
                                            <flux:textarea wire:model="nota_descripcion" label="Descripción" rows="3" />
                                            <flux:button wire:click="registrarNota" variant="primary" icon="plus" class="w-full">Registrar nota</flux:button>
                                        </div>
                                    @endif
                                    <div class="mt-5 space-y-4">
                                        @forelse ($casoSeleccionado->eventos->take(15) as $evento)
                                            <div class="relative pl-6 before:absolute before:left-[5px] before:top-5 before:h-full before:w-px before:bg-slate-200 last:before:hidden dark:before:bg-neutral-700">
                                                <span class="absolute left-0 top-1.5 h-3 w-3 rounded-full bg-[#006492] ring-4 ring-blue-50 dark:ring-blue-950"></span>
                                                <p class="font-black text-slate-900 dark:text-white">{{ $evento->titulo }}</p>
                                                @if($evento->descripcion)<p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">{{ $evento->descripcion }}</p>@endif
                                                <p class="mt-1 text-xs text-slate-400">{{ $evento->ocurrido_at?->format('d/m/Y H:i') }} · {{ $evento->usuario?->name ?? 'Sistema' }}</p>
                                            </div>
                                        @empty
                                            <p class="text-sm text-slate-500">Aún no hay eventos.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </section>

                            @if ($puedeGestionar)
                                <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                                    @if ($casoSeleccionado->estado === 'cerrado')
                                        <flux:heading>Reabrir seguimiento</flux:heading>
                                        <div class="mt-3 flex flex-col gap-3 lg:flex-row"><div class="flex-1"><flux:textarea wire:model="motivo_reapertura" label="Motivo obligatorio" rows="2" /></div><div class="flex items-end"><flux:button wire:click="reabrirCaso" variant="primary" icon="arrow-uturn-left">Reabrir</flux:button></div></div>
                                    @else
                                        <flux:heading>Cerrar seguimiento</flux:heading>
                                        <flux:text variant="subtle">Solo se permite cuando no hay acciones pendientes o en proceso.</flux:text>
                                        <div class="mt-3 flex flex-col gap-3 lg:flex-row"><div class="flex-1"><flux:textarea wire:model="motivo_cierre" label="Resultado y motivo de cierre" rows="2" /></div><div class="flex items-end"><flux:button wire:click="cerrarCaso" variant="danger" icon="lock-closed">Cerrar caso</flux:button></div></div>
                                    @endif
                                </section>
                            @endif
                        @endif
                    </main>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal de apertura --}}
    <div x-cloak x-show="$wire.modalSeguimiento" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
        <div x-on:click.outside="$wire.modalSeguimiento = false" class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
            <flux:heading size="lg">Abrir seguimiento académico</flux:heading>
            <flux:text variant="subtle">La evaluación queda como evidencia y el caso puede asignarse desde este momento.</flux:text>
            <div class="mt-5 space-y-4">
                <flux:textarea wire:model="motivo_apertura" label="Motivo de apertura" rows="3" />
                <flux:textarea wire:model="resumen_caso" label="Resumen inicial" rows="3" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model="prioridad_caso" label="Prioridad"><flux:select.option value="moderada">Moderada</flux:select.option><flux:select.option value="alta">Alta</flux:select.option><flux:select.option value="critica">Crítica</flux:select.option></flux:select>
                    <flux:select wire:model="responsable_caso_id" label="Responsable"><flux:select.option value="">Sin asignar</flux:select.option>@foreach($usuarios as $usuario)<flux:select.option value="{{ $usuario->id }}">{{ $usuario->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:input type="date" wire:model="proxima_revision_at" label="Próxima revisión" />
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2"><flux:button x-on:click="$wire.modalSeguimiento = false">Cancelar</flux:button><flux:button wire:click="guardarSeguimiento" variant="primary" icon="check">Abrir caso</flux:button></div>
        </div>
    </div>

    {{-- Modal de reglas --}}
    <div x-cloak x-show="$wire.modalReglas" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-6">
        <div x-on:click.outside="$wire.modalReglas = false" class="max-h-[94vh] w-full max-w-5xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
            <div class="flex items-center justify-between gap-3"><div><flux:heading size="lg">Reglas configurables del semáforo</flux:heading><flux:text variant="subtle">Los cambios se aplican en la siguiente evaluación; no alteran calificaciones ni decisiones académicas.</flux:text></div><flux:button x-on:click="$wire.modalReglas = false" variant="ghost" icon="x-mark"></flux:button></div>

            <section class="mt-5 rounded-2xl border border-slate-200 p-5 dark:border-neutral-800">
                <flux:heading>Umbrales globales</flux:heading>
                <div class="mt-4 grid gap-4 sm:grid-cols-4"><flux:input type="number" wire:model="umbral_moderado" label="Moderado desde" /><flux:input type="number" wire:model="umbral_alto" label="Alto desde" /><flux:input type="number" wire:model="umbral_critico" label="Crítico desde" /><div class="flex items-end"><flux:button wire:click="guardarUmbrales" variant="primary" icon="check" class="w-full">Guardar</flux:button></div></div>
            </section>

            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 dark:border-neutral-800">
                <table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-950/60"><tr><th class="px-4 py-3 text-left">Regla</th><th class="px-4 py-3 text-center">Niveles</th><th class="px-4 py-3 text-center">Peso</th><th class="px-4 py-3 text-center">Máximo</th><th class="px-4 py-3 text-center">Estado</th><th class="px-4 py-3 text-center">Acción</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @foreach($reglas as $regla)
                            <tr><td class="px-4 py-4"><p class="font-black text-slate-900 dark:text-white">{{ $regla->nombre }}</p><p class="mt-1 text-xs text-slate-500">{{ $regla->codigo }}</p></td><td class="px-4 py-4 text-center text-xs">{{ collect($regla->aplica_niveles ?? [])->map(fn($n) => ucfirst($n))->implode(', ') ?: 'Todos' }}</td><td class="px-4 py-4 text-center font-black">{{ (float) $regla->peso }}</td><td class="px-4 py-4 text-center font-black">{{ (float) $regla->max_puntos }}</td><td class="px-4 py-4 text-center"><flux:badge :color="$regla->activo ? 'green' : 'zinc'">{{ $regla->activo ? 'Activa' : 'Inactiva' }}</flux:badge></td><td class="px-4 py-4 text-center"><flux:button wire:click="editarRegla({{ $regla->id }})" size="sm" icon="pencil-square">Editar</flux:button></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal editar regla --}}
    <div x-cloak x-show="$wire.modalEditarRegla" x-transition.opacity class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm">
        <div x-on:click.outside="$wire.modalEditarRegla = false" class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
            <flux:heading size="lg">Editar regla de riesgo</flux:heading>
            <div class="mt-5 space-y-4">
                <flux:input wire:model="regla_nombre" label="Nombre" />
                <flux:checkbox wire:model="regla_activa" label="Regla activa" />
                <div class="grid gap-4 sm:grid-cols-2"><flux:input type="number" step="0.01" wire:model="regla_peso" label="Puntos por ocurrencia" /><flux:input type="number" step="0.01" wire:model="regla_max_puntos" label="Máximo de puntos" /></div>
                <flux:textarea wire:model="regla_parametros_json" label="Parámetros JSON" rows="8" class="font-mono text-sm" />
                <flux:callout icon="information-circle" heading="Configuración avanzada">Conserva las claves existentes del JSON. El sistema valida la sintaxis antes de guardar.</flux:callout>
            </div>
            <div class="mt-6 flex justify-end gap-2"><flux:button x-on:click="$wire.modalEditarRegla = false">Cancelar</flux:button><flux:button wire:click="guardarRegla" variant="primary" icon="check">Guardar regla</flux:button></div>
        </div>
    </div>

    <livewire:alumno.linea-tiempo-academica :key="'linea-tiempo-seguimiento'" />
</div>
