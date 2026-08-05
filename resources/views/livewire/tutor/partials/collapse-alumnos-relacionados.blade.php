@php
    $usuarioActual = auth()->user();
    $puedeVerSensibles = (bool) $usuarioActual?->canAccess('alumnos.responsables_sensibles');
    $puedeVerCurpAlumno = (bool) $usuarioActual?->canAccess('alumnos.editar');
    $puedeEditarMatriculaAlumno = (bool) $usuarioActual?->canAccess('alumnos.editar');
    $puedeGestionarExpedienteAlumno = (bool) $usuarioActual?->canAccess('documentos.organizar');
@endphp

<div class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm dark:border-blue-500/20 dark:bg-zinc-950">
    <div class="flex flex-col gap-3 border-b border-zinc-100 bg-gradient-to-r from-blue-50 via-white to-emerald-50/60 px-4 py-4 dark:border-zinc-800 dark:from-blue-950/25 dark:via-zinc-950 dark:to-emerald-950/15 sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm shadow-blue-600/25">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
            </span>

            <div>
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Alumnos relacionados</h3>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    Relaciones vigentes e históricas de {{ $tutor->nombre_completo }}.
                </p>
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
        <div class="px-5 py-10 text-center">
            <svg class="mx-auto h-9 w-9 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="m17 8 5 5" />
                <path d="m22 8-5 5" />
            </svg>
            <p class="mt-2 text-sm font-semibold text-zinc-700 dark:text-zinc-200">Sin alumnos relacionados</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Este responsable todavía no tiene relaciones activas ni históricas.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-[1260px] w-full border-collapse text-left text-sm">
                <thead class="bg-zinc-50/95 text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:bg-zinc-900/90 dark:text-zinc-400">
                    <tr>
                        <th class="w-[260px] px-5 py-3.5">Alumno</th>
                        <th class="w-[190px] px-4 py-3.5">Relación</th>
                        <th class="w-[245px] px-4 py-3.5">Ubicación académica</th>
                        <th class="w-[190px] px-4 py-3.5">Ciclo y generación</th>
                        <th class="w-[150px] px-4 py-3.5">Estatus</th>
                        <th class="w-[145px] px-4 py-3.5">Vigencia</th>
                        <th class="w-[230px] px-5 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
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

                            $slugNivelAlumno = trim((string) ($alumno?->nivel?->slug ?? ''));

                            if ($slugNivelAlumno === '' && $alumno?->nivel?->nombre) {
                                $slugNivelAlumno = \Illuminate\Support\Str::slug((string) $alumno->nivel->nombre);
                            }

                            $urlEditarMatricula = $alumno
                                && ! $alumno->trashed()
                                && $slugNivelAlumno !== ''
                                ? route('misrutas.matricula.editar', [
                                    'slug_nivel' => $slugNivelAlumno,
                                    'inscripcion' => $alumno->id,
                                ])
                                : null;

                            $urlExpediente = $alumno
                                ? route('misrutas.expedientes.show', ['inscripcion' => $alumno->id])
                                : null;
                        @endphp

                        <tr class="group align-top transition duration-200 hover:bg-blue-50/45 dark:hover:bg-blue-950/10">
                            <td class="relative px-5 py-4">
                                <span class="absolute inset-y-0 left-0 w-1 {{ $relacionActiva ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-700' }}"></span>
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-bold {{ $relacionActiva ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                        {{ $alumno?->iniciales ?: 'AL' }}
                                    </span>

                                    <div class="min-w-0">
                                        <p class="font-semibold leading-5 text-zinc-900 dark:text-zinc-100">
                                            {{ $nombreAlumno !== '' ? $nombreAlumno : 'Alumno sin nombre' }}
                                        </p>
                                        <p class="mt-1 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            {{ $alumno?->matricula ?: ($alumno?->folio ?: 'Sin matrícula') }}
                                        </p>
                                        @if ($puedeVerCurpAlumno && $alumno)
                                            <p class="mt-0.5 text-[11px] text-zinc-400 dark:text-zinc-500">
                                                {{ $alumno->curp ?: 'Sin CURP' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $relacionActiva ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $relacionActiva ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                    {{ $relacionActiva ? 'Activa' : 'Histórica' }}
                                </span>

                                <p class="mt-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200">
                                    {{ $relacion->parentesco ?: 'Sin parentesco' }}
                                </p>

                                @if ($funciones->isNotEmpty())
                                    <div class="mt-2 flex max-w-[190px] flex-wrap gap-1">
                                        @foreach ($funciones as $funcionRelacion)
                                            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                                {{ $funcionRelacion }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                <p class="font-semibold text-zinc-800 dark:text-zinc-100">
                                    {{ $alumno?->nivel?->nombre ?: 'Sin nivel' }} · {{ $gradoSemestre }}
                                </p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    Grupo {{ $alumno?->grupo?->clave ?: 'N/D' }}
                                </p>
                            </td>

                            <td class="px-4 py-4">
                                <p class="font-semibold text-zinc-800 dark:text-zinc-100">
                                    {{ $alumno?->cicloEscolar?->nombre ?: 'Sin ciclo' }}
                                </p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $generacion }}
                                </p>
                            </td>

                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $alumno?->visibleEnListas() ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' }}">
                                    {{ $alumno?->etiqueta_estatus ?: 'Sin estatus' }}
                                </span>

                                @if ($alumno?->trashed())
                                    <p class="mt-2 text-[11px] font-medium text-rose-600 dark:text-rose-400">Registro eliminado</p>
                                @elseif ($alumno && ! $alumno->visibleEnListas())
                                    <p class="mt-2 text-[11px] text-zinc-500 dark:text-zinc-400">No visible en listas</p>
                                @endif
                            </td>

                            <td class="px-4 py-4 text-xs text-zinc-600 dark:text-zinc-300">
                                <p><span class="font-semibold">Inicio:</span> {{ $relacion->fecha_inicio?->format('d/m/Y') ?: 'Sin fecha' }}</p>
                                @if ($relacion->fecha_fin)
                                    <p class="mt-1"><span class="font-semibold">Fin:</span> {{ $relacion->fecha_fin->format('d/m/Y') }}</p>
                                @endif
                                @if ($relacion->motivo_fin)
                                    <p class="mt-1 max-w-[145px] truncate text-[11px] text-zinc-500" title="{{ $relacion->motivo_fin }}">
                                        {{ $relacion->motivo_fin }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                @if ($alumno && ($puedeEditarMatriculaAlumno || $puedeGestionarExpedienteAlumno))
                                    <div class="flex flex-col items-stretch gap-2 sm:items-end">
                                        @if ($puedeEditarMatriculaAlumno && $urlEditarMatricula)
                                            <a href="{{ $urlEditarMatricula }}"
                                                wire:navigate
                                                class="inline-flex min-w-[174px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-3.5 py-2.5 text-xs font-bold text-white shadow-sm shadow-amber-500/20 transition duration-200 hover:-translate-y-0.5 hover:bg-amber-600 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-amber-500/20 motion-reduce:transform-none"
                                                title="Editar matrícula de {{ $nombreAlumno }}">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="M12 20h9" />
                                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                                </svg>
                                                Editar matrícula
                                            </a>
                                        @elseif ($puedeEditarMatriculaAlumno)
                                            <span class="inline-flex min-w-[174px] cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-zinc-100 px-3.5 py-2.5 text-xs font-semibold text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-500"
                                                title="No se puede editar porque el alumno no tiene nivel válido o su registro fue eliminado">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="M12 20h9" />
                                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                                </svg>
                                                Edición no disponible
                                            </span>
                                        @endif

                                        @if ($puedeGestionarExpedienteAlumno && $urlExpediente)
                                            <a href="{{ $urlExpediente }}"
                                                wire:navigate
                                                class="inline-flex min-w-[174px] items-center justify-center gap-2 rounded-xl bg-[#006492] px-3.5 py-2.5 text-xs font-bold text-white shadow-sm shadow-sky-700/20 transition duration-200 hover:-translate-y-0.5 hover:bg-[#00557b] hover:shadow-md focus:outline-none focus:ring-4 focus:ring-sky-500/20 motion-reduce:transform-none"
                                                title="Abrir expediente digital de {{ $nombreAlumno }}">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                    <path d="M14 2v6h6" />
                                                    <path d="M12 18v-6" />
                                                    <path d="m9 15 3-3 3 3" />
                                                </svg>
                                                Cargar expediente
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400">Sin acciones disponibles</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center gap-2 border-t border-zinc-100 bg-zinc-50/70 px-5 py-3 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 16v-4" />
                <path d="M12 8h.01" />
            </svg>
            Desliza horizontalmente para consultar todas las columnas cuando la pantalla sea pequeña.
        </div>
    @endif
</div>
