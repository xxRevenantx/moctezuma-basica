@php
    $puedeVerSensibles = (bool) auth()->user()?->canAccess('alumnos.responsables_sensibles');
    $puedeVerCurpAlumno = (bool) auth()->user()?->canAccess('alumnos.editar');
@endphp

<div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50/80 via-white to-indigo-50/70 p-4 shadow-inner dark:border-blue-500/20 dark:from-blue-950/20 dark:via-zinc-950 dark:to-indigo-950/20 sm:p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm shadow-blue-600/20">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </span>
                <div>
                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Alumnos relacionados</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Relaciones vigentes e históricas de {{ $tutor->nombre_completo }}.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1.5 font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                {{ (int) $tutor->relaciones_activas_count }} activas
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-200/80 px-3 py-1.5 font-semibold text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                {{ max(0, (int) $tutor->relaciones_total_count - (int) $tutor->relaciones_activas_count) }} históricas
            </span>
        </div>
    </div>

    @if ($tutor->relaciones->isEmpty())
        <div class="mt-4 rounded-xl border border-dashed border-zinc-300 bg-white/70 px-4 py-8 text-center dark:border-zinc-700 dark:bg-zinc-900/60">
            <svg class="mx-auto h-8 w-8 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="m17 8 5 5"/>
                <path d="m22 8-5 5"/>
            </svg>
            <p class="mt-2 text-sm font-semibold text-zinc-700 dark:text-zinc-200">Sin alumnos relacionados</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Este responsable todavía no tiene relaciones activas ni históricas.</p>
        </div>
    @else
        <div class="mt-4 grid gap-3 xl:grid-cols-2">
            @foreach ($tutor->relaciones as $relacion)
                @php
                    $alumno = $relacion->inscripcion;
                    $relacionActiva = (bool) $relacion->activo && $relacion->fecha_fin === null;

                    $nombreAlumno = $alumno
                        ? trim(collect([
                            $alumno->nombre,
                            $alumno->apellido_paterno,
                            $alumno->apellido_materno,
                        ])->filter()->join(' '))
                        : 'Alumno no disponible';

                    $gradoSemestre = $alumno?->semestre
                        ? $alumno->semestre->numero . '° semestre'
                        : ($alumno?->grado?->nombre ?: 'Sin grado');

                    $generacion = $alumno?->generacion
                        ? ($alumno->generacion->nombre
                            ?: trim((string) $alumno->generacion->anio_ingreso . '-' . (string) $alumno->generacion->anio_egreso, '-'))
                        : 'Sin generación';

                    $funciones = collect([
                        $relacion->es_principal ? 'Principal' : null,
                        $puedeVerSensibles && $relacion->es_tutor_legal ? 'Tutor legal' : null,
                        $relacion->contacto_emergencia ? 'Emergencia' : null,
                        $puedeVerSensibles && $relacion->autorizado_recoger ? 'Puede recoger' : null,
                        $relacion->responsable_economico ? 'Responsable económico' : null,
                        $relacion->recibe_avisos ? 'Recibe avisos' : null,
                        $relacion->recibe_calificaciones ? 'Recibe calificaciones' : null,
                    ])->filter();
                @endphp

                <article class="overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-zinc-900 {{ $relacionActiva ? 'border-emerald-200 dark:border-emerald-500/25' : 'border-zinc-200 dark:border-zinc-700' }}">
                    <div class="flex items-start gap-3 border-b border-zinc-100 p-4 dark:border-zinc-800">
                        <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl font-bold {{ $relacionActiva ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">
                            {{ $alumno?->iniciales ?: 'AL' }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <h4 class="truncate font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $nombreAlumno !== '' ? $nombreAlumno : 'Alumno sin nombre' }}
                                    </h4>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $alumno?->matricula ?: ($alumno?->folio ?: 'Sin matrícula') }}
                                        @if ($puedeVerCurpAlumno && $alumno)
                                            · {{ $alumno->curp ?: 'Sin CURP' }}
                                        @endif
                                    </p>
                                </div>

                                <span class="inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $relacionActiva ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $relacionActiva ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                    {{ $relacionActiva ? 'Relación activa' : 'Histórica' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 p-4 text-xs sm:grid-cols-2">
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/70">
                            <span class="block font-bold uppercase tracking-wide text-zinc-400">Parentesco</span>
                            <span class="mt-1 block font-semibold text-zinc-700 dark:text-zinc-200">
                                {{ $relacion->parentesco ?: 'Sin parentesco' }}
                            </span>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/70">
                            <span class="block font-bold uppercase tracking-wide text-zinc-400">Ubicación académica</span>
                            <span class="mt-1 block font-semibold text-zinc-700 dark:text-zinc-200">
                                {{ $alumno?->nivel?->nombre ?: 'Sin nivel' }} · {{ $gradoSemestre }} · Grupo {{ $alumno?->grupo?->clave ?: 'N/D' }}
                            </span>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/70">
                            <span class="block font-bold uppercase tracking-wide text-zinc-400">Ciclo y generación</span>
                            <span class="mt-1 block font-semibold text-zinc-700 dark:text-zinc-200">
                                {{ $alumno?->cicloEscolar?->nombre ?: 'Sin ciclo' }} · {{ $generacion }}
                            </span>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/70">
                            <span class="block font-bold uppercase tracking-wide text-zinc-400">Estatus del alumno</span>
                            <span class="mt-1 block font-semibold text-zinc-700 dark:text-zinc-200">
                                {{ $alumno?->etiqueta_estatus ?: 'Sin estatus' }}
                                @if ($alumno?->trashed())
                                    · Registro eliminado
                                @elseif ($alumno && ! $alumno->visibleEnListas())
                                    · No visible en listas
                                @endif
                            </span>
                        </div>
                    </div>

                    @if ($funciones->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5 border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            @foreach ($funciones as $funcionRelacion)
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                    {{ $funcionRelacion }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-zinc-100 bg-zinc-50/80 px-4 py-2.5 text-[11px] text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950/50 dark:text-zinc-400">
                        <span>Inicio: {{ $relacion->fecha_inicio?->format('d/m/Y') ?: 'Sin fecha' }}</span>
                        @if ($relacion->fecha_fin)
                            <span>Fin: {{ $relacion->fecha_fin->format('d/m/Y') }}</span>
                        @endif
                        @if ($relacion->motivo_fin)
                            <span class="min-w-0 truncate" title="{{ $relacion->motivo_fin }}">Motivo: {{ $relacion->motivo_fin }}</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
