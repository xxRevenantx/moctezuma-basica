<div class="space-y-5">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">
                Control escolar
            </p>
            <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">Generaciones</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Consulta, filtra, cierra o reabre una generación sin perder su historial académico.
            </p>
        </div>

        <div
            class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Resultados</p>
            <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">
                {{ number_format($generaciones->total()) }}
                <span class="text-sm font-semibold text-slate-500">generación(es)</span>
            </p>
        </div>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-[#006492] dark:text-sky-300">
                    Filtros de consulta
                </p>
                <h2 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Localiza una generación</h2>
            </div>

            <flux:button wire:click="limpiarFiltros" icon="arrow-path" :disabled="!$hayFiltrosActivos">
                Limpiar filtros
            </flux:button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-8">
            <div class="md:col-span-2 xl:col-span-2">
                <flux:input wire:model.live.debounce.350ms="search" label="Buscar"
                    placeholder="Generación, nivel o ciclo escolar" icon="magnifying-glass" />
            </div>

            <flux:select wire:model.live="nivel_id" label="Nivel educativo">
                <flux:select.option value="">Todos los niveles</flux:select.option>
                @foreach ($niveles as $nivel)
                    <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo consultado">
                <flux:select.option value="">Toda la trayectoria</flux:select.option>
                @foreach ($ciclosEscolares as $ciclo)
                    <flux:select.option value="{{ $ciclo->id }}">
                        {{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}
                        {{ $ciclo->es_actual ? '· Actual' : ($ciclo->cerrado_at ? '· Cerrado' : '') }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="estado" label="Estado generación">
                <flux:select.option value="">Todos los estados</flux:select.option>
                <flux:select.option value="activa">Activas</flux:select.option>
                <flux:select.option value="cerrada">Cerradas / inactivas</flux:select.option>
                <flux:select.option value="en_proceso">En proceso de cierre</flux:select.option>
                <flux:select.option value="egresada">Egresadas</flux:select.option>
                <flux:select.option value="archivada">Archivadas</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="resultado_historial" label="Resultado de alumnos">
                <flux:select.option value="">Todos los resultados</flux:select.option>
                <flux:select.option value="en_curso">En curso</flux:select.option>
                <flux:select.option value="promovido">Promovidos</flux:select.option>
                <flux:select.option value="no_promovido">No promovidos</flux:select.option>
                <flux:select.option value="egresado">Egresados</flux:select.option>
                <flux:select.option value="trasladado">Trasladados</flux:select.option>
                <flux:select.option value="baja">Bajas / inactivos</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="contenido_historial" label="Integridad histórica">
                <flux:select.option value="">Todos los registros</flux:select.option>
                <flux:select.option value="con_historial">Con historial por ciclo</flux:select.option>
                <flux:select.option value="sin_historial">Alumnos sin historial</flux:select.option>
                <flux:select.option value="con_inconsistencias">Requieren revisión</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="anio_ingreso" label="Año de ingreso">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($aniosIngreso as $anio)
                    <flux:select.option value="{{ $anio }}">{{ $anio }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-8">
            <flux:select wire:model.live="anio_egreso" label="Año de egreso">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($aniosEgreso as $anio)
                    <flux:select.option value="{{ $anio }}">{{ $anio }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="xl:col-start-7 xl:col-span-2">
                <flux:select wire:model.live="orden" label="Ordenar por">
                    <flux:select.option value="recientes">Ingreso más reciente</flux:select.option>
                    <flux:select.option value="antiguas">Ingreso más antiguo</flux:select.option>
                    <flux:select.option value="nivel">Nivel educativo</flux:select.option>
                    <flux:select.option value="nombre">Nombre</flux:select.option>
                    <flux:select.option value="alumnos">Matrícula actual</flux:select.option>
                    <flux:select.option value="historicos">Mayor matrícula histórica</flux:select.option>
                    <flux:select.option value="ciclos">Mayor número de ciclos</flux:select.option>
                    <flux:select.option value="revisar">Más casos por revisar</flux:select.option>
                </flux:select>
            </div>
        </div>

        <div wire:loading.flex wire:target="search,nivel_id,ciclo_escolar_id,estado,resultado_historial,contenido_historial,anio_ingreso,anio_egreso,orden,limpiarFiltros"
            class="mt-4 items-center gap-2 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-200">
            <flux:icon.arrow-path class="h-4 w-4 animate-spin" />
            Actualizando generaciones...
        </div>
    </section>

    <div
        class="rounded-2xl border border-sky-100 bg-gradient-to-r from-sky-50 to-emerald-50 p-4 dark:border-sky-900/40 dark:from-sky-950/20 dark:to-emerald-950/20">
        <div class="flex items-start gap-3">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#006492] text-white shadow-sm">
                <flux:icon.information-circle class="h-5 w-5" />
            </div>
            <div>
                <p class="font-black text-slate-900 dark:text-white">Reapertura para correcciones</p>
                <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Al reabrir una generación puedes reactivar temporalmente a sus egresados para corregir
                    calificaciones, boletas o documentos. Al finalizar, vuelve a desactivarla y marca nuevamente
                    a los alumnos activos como egresados.
                </p>
            </div>
        </div>
    </div>

    <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead
                    class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Generación</th>
                        <th class="px-4 py-3">Nivel</th>
                        <th class="px-4 py-3">Periodo</th>
                        <th class="px-4 py-3">Alumnos</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($generaciones as $g)
                        <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <p class="font-black text-slate-900 dark:text-white">{{ $g->etiqueta }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $g->nombre ?: 'Generación escolar' }}
                                </p>
                            </td>

                            <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">
                                {{ $g->nivel?->nombre ?: '—' }}
                            </td>

                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                {{ optional($g->fecha_inicio)->format('d/m/Y') ?: '—' }}
                                <span class="mx-1 text-slate-400">—</span>
                                {{ optional($g->fecha_termino)->format('d/m/Y') ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @php
                                    $historicos = (int) ($g->alumnos_historicos_count ?? 0);
                                    $inferidos = (int) ($g->contextos_inferidos_count ?? 0);
                                    $sinVinculo = (int) ($g->calificaciones_sin_historial_count ?? 0);
                                @endphp
                                <div class="flex flex-wrap gap-1.5">
                                    <flux:badge color="blue">Históricos {{ $historicos }}</flux:badge>
                                    <flux:badge color="green">En curso {{ (int) ($g->alumnos_en_curso_count ?? 0) }}</flux:badge>
                                    <flux:badge color="sky">Promovidos {{ (int) ($g->alumnos_promovidos_count ?? 0) }}</flux:badge>
                                    <flux:badge color="purple">Egresados {{ (int) ($g->alumnos_egresados_historicos_count ?? 0) }}</flux:badge>
                                    <flux:badge color="indigo">Traslados {{ (int) ($g->alumnos_trasladados_count ?? 0) }}</flux:badge>
                                    <flux:badge color="amber">Bajas {{ (int) ($g->alumnos_bajas_historicas_count ?? 0) }}</flux:badge>
                                </div>
                                <p class="mt-2 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                    {{ (int) ($g->ciclos_historicos_count ?? 0) }} ciclo(s)
                                    · {{ (int) ($g->grupos_historicos_count ?? 0) }} grupo(s)
                                    · matrícula actual {{ (int) $g->alumnos_total_count }}
                                </p>
                                @if ($inferidos > 0 || $sinVinculo > 0)
                                    <p class="mt-1 text-[11px] font-bold text-amber-700 dark:text-amber-300">
                                        Revisión: {{ $inferidos }} contexto(s) inferido(s)
                                        @if ($sinVinculo > 0) · {{ $sinVinculo }} calificación(es) sin vínculo @endif
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @switch($g->estado_cierre_normalizado)
                                    @case('en_proceso')
                                        <flux:badge color="amber" icon="clock">En proceso</flux:badge>
                                    @break

                                    @case('egresada')
                                        <flux:badge color="purple" icon="academic-cap">Egresada</flux:badge>
                                    @break

                                    @case('archivada')
                                        <flux:badge color="blue" icon="archive-box">Archivada</flux:badge>
                                    @break

                                    @case('cerrada')
                                        <flux:badge color="zinc" icon="lock-closed">Cerrada</flux:badge>
                                    @break

                                    @default
                                        @if ($g->status)
                                            <flux:badge color="green" icon="check-circle">Activa</flux:badge>
                                        @else
                                            <flux:badge color="zinc" icon="lock-closed">Cerrada</flux:badge>
                                        @endif
                                @endswitch
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <flux:button size="sm" icon="clock" wire:click="abrirTrayectoria({{ $g->id }})"
                                       >
                                        Trayectoria
                                    </flux:button>

                                    <flux:button size="sm" icon="pencil-square"
                                        @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $g->id }} })">
                                        Editar
                                    </flux:button>

                                    @if ($g->status)
                                        <flux:button size="sm" variant="danger" icon="lock-closed"
                                            wire:click="prepararDesactivacion({{ $g->id }})">
                                            Cerrar
                                        </flux:button>
                                    @else
                                        <flux:button size="sm" variant="primary" icon="lock-open"
                                            wire:click="prepararReactivacion({{ $g->id }})">
                                            Reabrir para correcciones
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                                    No hay generaciones que coincidan con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 p-4 dark:border-slate-800">
                {{ $generaciones->links() }}
            </div>
        </div>

        @if ($modalTrayectoria)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
                wire:keydown.escape="cerrarTrayectoria">
                <div class="max-h-[92vh] w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-sky-50 to-emerald-50 px-6 py-5 dark:border-slate-800 dark:from-sky-950/30 dark:to-emerald-950/20">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700 dark:text-sky-300">
                                Trayectoria académica histórica
                            </p>
                            <h2 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                                {{ $trayectoriaGeneracion['etiqueta'] ?? 'Generación' }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                {{ $trayectoriaGeneracion['nivel'] ?? 'Sin nivel' }}
                                · {{ $trayectoriaGeneracion['estado'] ?? 'Sin estado' }}
                                @if (!empty($trayectoriaGeneracion['periodo'])) · {{ $trayectoriaGeneracion['periodo'] }} @endif
                            </p>
                        </div>
                        <button type="button" wire:click="cerrarTrayectoria"
                            class="rounded-xl p-2 text-slate-500 transition hover:bg-white hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-white">
                            <flux:icon.x-mark class="h-6 w-6" />
                        </button>
                    </div>

                    <div class="max-h-[calc(92vh-92px)] space-y-6 overflow-y-auto p-6">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                            @foreach ([
                                ['Alumnos históricos', 'alumnos', 'blue'],
                                ['Ciclos registrados', 'ciclos', 'sky'],
                                ['Promovidos', 'promovidos', 'green'],
                                ['Egresados', 'egresados', 'purple'],
                                ['Bajas / traslados', 'salidas', 'amber'],
                            ] as [$etiqueta, $clave, $color])
                                @php
                                    $valor = $clave === 'salidas'
                                        ? (int) ($trayectoriaResumen['bajas'] ?? 0) + (int) ($trayectoriaResumen['trasladados'] ?? 0)
                                        : (int) ($trayectoriaResumen[$clave] ?? 0);
                                @endphp
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $etiqueta }}</p>
                                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($valor) }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if ((int) ($trayectoriaResumen['inferidos'] ?? 0) > 0)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                                <b>{{ (int) $trayectoriaResumen['inferidos'] }} alumno(s)</b> tienen algún contexto reconstruido o inferido. Permanecen visibles, pero conviene revisar su ciclo, grado, semestre y grupo.
                            </div>
                        @endif

                        <section>
                            <div class="mb-3">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[#006492] dark:text-sky-300">Resumen por ciclo escolar</p>
                                <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Evolución de la generación</h3>
                            </div>
                            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        <tr>
                                            <th class="px-4 py-3">Ciclo</th>
                                            <th class="px-4 py-3">Alumnos</th>
                                            <th class="px-4 py-3">En curso</th>
                                            <th class="px-4 py-3">Promovidos</th>
                                            <th class="px-4 py-3">No promovidos</th>
                                            <th class="px-4 py-3">Egresados</th>
                                            <th class="px-4 py-3">Salidas</th>
                                            <th class="px-4 py-3">Evidencia</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        @forelse ($trayectoriaCiclos as $ciclo)
                                            <tr>
                                                <td class="px-4 py-3 font-black text-slate-900 dark:text-white">
                                                    {{ $ciclo['inicio_anio'] }} - {{ $ciclo['fin_anio'] }}
                                                    @if (!empty($ciclo['es_actual']))
                                                        <span class="ml-1 text-[10px] text-emerald-600">ACTUAL</span>
                                                    @elseif (!empty($ciclo['cerrado_at']))
                                                        <span class="ml-1 text-[10px] text-slate-500">CERRADO</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3">{{ (int) $ciclo['alumnos'] }}</td>
                                                <td class="px-4 py-3">{{ (int) $ciclo['en_curso'] }}</td>
                                                <td class="px-4 py-3">{{ (int) $ciclo['promovidos'] }}</td>
                                                <td class="px-4 py-3">{{ (int) $ciclo['no_promovidos'] }}</td>
                                                <td class="px-4 py-3">{{ (int) $ciclo['egresados'] }}</td>
                                                <td class="px-4 py-3">{{ (int) $ciclo['bajas'] + (int) $ciclo['trasladados'] }}</td>
                                                <td class="px-4 py-3 text-xs text-slate-500">
                                                    {{ (int) $ciclo['grupos'] }} grupo(s) · {{ (int) $ciclo['calificaciones'] }} calificación(es)
                                                    @if ((int) $ciclo['inferidos'] > 0)
                                                        <span class="block font-bold text-amber-700 dark:text-amber-300">{{ (int) $ciclo['inferidos'] }} inferido(s)</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No hay historial por ciclo para esta generación.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section>
                            <div class="mb-3">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[#88AC2E]">Contextos registrados</p>
                                <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Grados, semestres y grupos</h3>
                            </div>
                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                @forelse ($trayectoriaContextos as $contexto)
                                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                            {{ $contexto['inicio_anio'] }} - {{ $contexto['fin_anio'] }}
                                        </p>
                                        <p class="mt-1 font-black text-slate-900 dark:text-white">
                                            {{ $contexto['grado'] ?: 'Sin grado' }}
                                            @if (!empty($contexto['semestre'])) · Semestre {{ $contexto['semestre'] }} @endif
                                        </p>
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                            Grupo {{ $contexto['grupo'] ?: 'sin nombre' }} · {{ (int) $contexto['alumnos'] }} alumno(s)
                                        </p>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-sm text-slate-500 dark:border-slate-700">
                                        No existen asignaciones históricas detalladas para esta generación.
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        @endif

        @if ($modalDesactivar)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm">
                <div class="w-full max-w-xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div
                        class="border-b border-slate-200 bg-gradient-to-r from-rose-50 to-amber-50 px-6 py-5 dark:border-slate-800 dark:from-rose-950/30 dark:to-amber-950/20">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-600 text-white shadow-lg">
                                <flux:icon.lock-closed class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-white">Cerrar generación</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                    La generación seguirá disponible en consultas y reportes históricos.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <flux:textarea wire:model="motivo" label="Motivo obligatorio"
                            placeholder="Ejemplo: conclusión oficial de la generación" rows="3" />

                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-rose-300 hover:bg-rose-50/60 dark:border-slate-700 dark:hover:border-rose-900/70 dark:hover:bg-rose-950/20">
                            <input type="checkbox" wire:model="egresar_activos"
                                class="mt-1 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                            <span>
                                <b class="block text-slate-900 dark:text-white">Marcar como egresados a los alumnos
                                    activos</b>
                                <small class="mt-1 block leading-5 text-slate-500 dark:text-slate-400">
                                    Úsalo también después de terminar una reapertura por correcciones.
                                </small>
                            </span>
                        </label>
                    </div>

                    <div
                        class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/30">
                        <flux:button wire:click="$set('modalDesactivar', false)">Cancelar</flux:button>
                        <flux:button variant="danger" icon="lock-closed" wire:click="desactivar" spinner="desactivar">
                            Confirmar cierre
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif

        @if ($modalReactivar)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm">
                <div class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div
                        class="border-b border-slate-200 bg-gradient-to-r from-sky-50 to-emerald-50 px-6 py-5 dark:border-slate-800 dark:from-sky-950/30 dark:to-emerald-950/20">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] text-white shadow-lg">
                                <flux:icon.lock-open class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700 dark:text-sky-300">
                                    Reapertura administrativa
                                </p>
                                <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">
                                    {{ $generacionReactivar?->etiqueta ?: 'Generación seleccionada' }}
                                </h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $generacionReactivar?->nivel?->nombre ?: 'Nivel educativo' }}
                                    · {{ $generacionReactivar?->egresados_count ?? 0 }} egresado(s)
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5 p-6">
                        <div
                            class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                            Esta acción no crea otra inscripción ni un reingreso. Conserva la misma matrícula,
                            generación, grupo, calificaciones e historial del alumno.
                        </div>

                        <flux:textarea wire:model="motivo_reactivacion" label="Motivo de la reapertura"
                            placeholder="Ejemplo: corrección de calificaciones del tercer periodo" rows="3" />

                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50/60 p-4 transition hover:border-sky-300 dark:border-sky-900/50 dark:bg-sky-950/20">
                            <input type="checkbox" wire:model="reactivar_egresados"
                                class="mt-1 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                            <span>
                                <b class="block text-slate-900 dark:text-white">
                                    Reactivar temporalmente a los alumnos egresados
                                </b>
                                <small class="mt-1 block leading-5 text-slate-600 dark:text-slate-400">
                                    Los alumnos volverán a aparecer en Calificaciones y en los módulos que muestran
                                    matrícula activa. Bajas, traslados, suspendidos e inactivos no serán modificados.
                                </small>
                            </span>
                        </label>

                        <div
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                            <p class="font-black text-emerald-900 dark:text-emerald-100">Cuando termines las correcciones
                            </p>
                            <p class="mt-1 text-sm leading-6 text-emerald-800 dark:text-emerald-200">
                                Regresa a esta sección, cierra la generación y deja marcada la opción
                                “Marcar como egresados a los alumnos activos”.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/30">
                        <flux:button wire:click="$set('modalReactivar', false)">Cancelar</flux:button>
                        <flux:button variant="primary" icon="lock-open" wire:click="reactivar" spinner="reactivar">
                            Reabrir generación
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif

        <livewire:generacion.editar-generacion />
    </div>
