@php
    $concentrado = $this->concentrado;
    $resumen = $concentrado['resumen'];
    $gruposPromedios = $concentrado['grupos'];
    $grafica = $concentrado['grafica'];
    $encabezadosPeriodos = $this->encabezadosPeriodos;
    $esBachillerato = $this->esBachillerato;
@endphp

<div class="space-y-6">
    <div
        class="relative overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-emerald-400 via-sky-500 to-indigo-600"></div>

        <div class="grid gap-6 p-5 lg:grid-cols-[1fr_420px] lg:p-6">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 via-sky-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                    <flux:icon.academic-cap class="h-7 w-7" />
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-sky-600 dark:text-sky-300">
                        Promedios generales
                    </p>

                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">
                        Concentrado final de {{ $nivel->nombre }}
                    </h2>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        @if ($esBachillerato)
                            Calcula el promedio semestral con calificaciones numéricas, ignorando textos como AC, NP,
                            SD, ED o RA. Solo toma materias calificables, no extra y no receso.
                        @else
                            Calcula el promedio anual con calificaciones numéricas, ignorando textos como AC, NP, SD, ED
                            o RA. Solo toma materias calificables, no extra y no receso.
                        @endif
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            {{ $esBachillerato ? '2 parciales por semestre' : '3 periodos de básica' }}
                        </span>

                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                            <flux:icon.calculator class="h-3.5 w-3.5" />
                            Promedio truncado
                        </span>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <flux:field>
            <flux:label>Ciclo escolar</flux:label>
            <flux:select wire:model.live="ciclo_escolar_id">
                <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                @foreach ($cicloEscolares as $ciclo)
                    <flux:select.option value="{{ $ciclo->id }}">
                        {{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>Generación</flux:label>
            <flux:select wire:model.live="generacion_id">
                <flux:select.option value="">Todas las generaciones</flux:select.option>
                @foreach ($generaciones as $generacion)
                    <flux:select.option value="{{ $generacion->id }}">
                        {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>Grado</flux:label>
            <flux:select wire:model.live="grado_id">
                <flux:select.option value="">Todos los grados</flux:select.option>
                @foreach ($grados as $grado)
                    <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>Grupo</flux:label>
            <flux:select wire:model.live="grupo_id">
                <flux:select.option value="">Todos los grupos</flux:select.option>
                @foreach ($grupos as $grupo)
                    <flux:select.option value="{{ $grupo->id }}">
                        {{ $grupo->grado?->nombre ?? 'Grado' }} · Grupo
                        {{ $grupo->asignacionGrupo?->nombre ?? '—' }}
                        @if ($esBachillerato && $grupo->semestre)
                            · Semestre {{ $grupo->semestre->numero }}
                        @endif
                    </flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        @if ($esBachillerato)
            <flux:field>
                <flux:label>Semestre</flux:label>
                <flux:select wire:model.live="semestre_id">
                    <flux:select.option value="">Todos los semestres</flux:select.option>
                    @foreach ($semestres as $semestre)
                        <flux:select.option value="{{ $semestre->id }}">
                            Semestre {{ $semestre->numero }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
        @endif

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-1 lg:grid-cols-1">
            <flux:field>
                <flux:label>Ordenar alumnos</flux:label>
                <flux:select wire:model.live="orden">
                    <flux:select.option value="promedio_desc">Promedio mayor a menor</flux:select.option>
                    <flux:select.option value="promedio_asc">Promedio menor a mayor</flux:select.option>
                    <flux:select.option value="nombre_asc">Nombre A-Z</flux:select.option>
                </flux:select>
            </flux:field>

            <button type="button" wire:click="limpiarFiltros"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                <flux:icon.arrow-path class="h-4 w-4" />
                Limpiar filtros
            </button>
        </div>
    </div>




    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div
            class="rounded-[1.4rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Alumnos</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['total_alumnos'] }}</p>
            <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">Con calificaciones numéricas</p>
        </div>

        <div
            class="rounded-[1.4rem] border border-sky-200 bg-sky-50 p-4 shadow-sm dark:border-sky-900/50 dark:bg-sky-950/20">
            <p class="text-xs font-black uppercase tracking-wide text-sky-700 dark:text-sky-300">Promedio general</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['promedio_general'] }}</p>
            <p class="mt-1 text-xs font-bold text-sky-700 dark:text-sky-300">Cálculo global truncado</p>
        </div>

        <div
            class="rounded-[1.4rem] border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/20">
            <p class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Aprobados</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['aprobados'] }}</p>
            <p class="mt-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">Promedio mínimo 6.0</p>
        </div>

        <div
            class="rounded-[1.4rem] border border-rose-200 bg-rose-50 p-4 shadow-sm dark:border-rose-900/50 dark:bg-rose-950/20">
            <p class="text-xs font-black uppercase tracking-wide text-rose-700 dark:text-rose-300">En riesgo</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['riesgo'] }}</p>
            <p class="mt-1 text-xs font-bold text-rose-700 dark:text-rose-300">Promedio menor a 6.0</p>
        </div>

        <div
            class="rounded-[1.4rem] border border-violet-200 bg-violet-50 p-4 shadow-sm dark:border-violet-900/50 dark:bg-violet-950/20">
            <p class="text-xs font-black uppercase tracking-wide text-violet-700 dark:text-violet-300">Mejor promedio
            </p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['mejor_promedio'] }}</p>
            <p class="mt-1 truncate text-xs font-bold text-violet-700 dark:text-violet-300">
                {{ $resumen['mejor_alumno'] }}</p>
        </div>
    </div>



    <button type="button" wire:click="exportarExcel" wire:loading.attr="disabled" wire:target="exportarExcel"
        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 via-sky-600 to-indigo-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-sky-500/20 transition hover:-translate-y-0.5 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-70">

        <div class="flex justify-between">
            <span wire:loading.remove wire:target="exportarExcel" class="inline-flex items-center gap-2">
                <flux:icon.document-arrow-down class="h-4 w-4" />
                Exportar Excel
            </span>

            <span wire:loading wire:target="exportarExcel" class="inline-flex items-center gap-2">
                <flux:icon.arrow-path class="h-4 w-4 animate-spin" />
                Exportando...
            </span>
        </div>
    </button>

    <div class="space-y-4">
        @forelse ($gruposPromedios as $grupoPromedio)
            <section x-data="{ abierto: false }"
                class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <button type="button" x-on:click="abierto = !abierto"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70">
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-black text-slate-950 dark:text-white">
                            {{ $grupoPromedio['titulo'] }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ $grupoPromedio['total'] }} alumnos · Promedio {{ $grupoPromedio['promedio'] }} ·
                            {{ $grupoPromedio['aprobados'] }} aprobados · {{ $grupoPromedio['riesgo'] }} en riesgo
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <span
                            class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                            Prom. {{ $grupoPromedio['promedio'] }}
                        </span>

                        <span
                            class="rounded-2xl border border-slate-200 p-2 text-slate-500 transition dark:border-neutral-700 dark:text-slate-300"
                            :class="abierto ? 'rotate-180' : ''">
                            <flux:icon.chevron-down class="h-5 w-5" />
                        </span>
                    </div>
                </button>



                <div x-cloak x-show="abierto" x-transition.opacity.duration.200ms
                    class="border-t border-slate-200 dark:border-neutral-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                            <thead class="bg-slate-50 dark:bg-neutral-950/40">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        #</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Lugar por grupo</th>
                                    <th
                                        class="min-w-[260px] px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Alumno</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Matrícula</th>
                                    @foreach ($encabezadosPeriodos as $periodo => $etiqueta)
                                        <th
                                            class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            {{ $etiqueta }}</th>
                                    @endforeach
                                    <th
                                        class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Suma</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Promedio</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Estatus</th>

                                    @if ($esBachillerato)
                                        <th
                                            class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Boleta semestral
                                        </th>
                                    @else
                                        <th
                                            class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Boleta anual
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            RECONOCIMIENTO ANUAL
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Diploma
                                        </th>
                                    @endif
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                @foreach ($grupoPromedio['alumnos'] as $index => $alumno)
                                    <tr class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/50">
                                        <td class="px-4 py-3 font-black text-slate-400">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="inline-flex min-w-20 justify-center rounded-xl bg-indigo-50 px-2.5 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900/50">
                                                {{ $alumno['texto_lugar'] ?? 'Pendiente' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-black text-slate-900 dark:text-white">
                                                {{ $alumno['alumno'] }}</p>
                                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                {{ $alumno['grado'] }} · Grupo {{ $alumno['grupo'] }}
                                                @if ($esBachillerato)
                                                    · Semestre {{ $alumno['semestre'] ?? '—' }}
                                                @endif
                                            </p>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">
                                            {{ $alumno['matricula'] ?? '—' }}</td>

                                        @foreach ($encabezadosPeriodos as $periodo => $etiqueta)
                                            <td class="px-4 py-3 text-center">
                                                @if ($alumno['periodos'][$periodo] !== null)
                                                    <span
                                                        class="inline-flex min-w-12 justify-center rounded-xl bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700 dark:bg-neutral-800 dark:text-slate-200">
                                                        {{ $this->formatearDecimal($alumno['periodos'][$periodo]) }}
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex min-w-12 justify-center rounded-xl bg-amber-50 px-2.5 py-1 text-xs font-black text-amber-700 ring-1 ring-amber-100 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/50">
                                                        Pend.
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach

                                        <td
                                            class="px-4 py-3 text-center font-black text-slate-700 dark:text-slate-200">
                                            {{ $this->formatearDecimal($alumno['suma_periodos']) }}
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="inline-flex min-w-16 justify-center rounded-xl px-3 py-1 text-sm font-black
                                                {{ ($alumno['promedio_final'] ?? 0) >= 9 ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50' : '' }}
                                                {{ ($alumno['promedio_final'] ?? 0) >= 6 && ($alumno['promedio_final'] ?? 0) < 9 ? 'bg-sky-50 text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50' : '' }}
                                                {{ ($alumno['promedio_final'] ?? 0) > 0 && ($alumno['promedio_final'] ?? 0) < 6 ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-100 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50' : '' }}">
                                                {{ $this->formatearDecimal($alumno['promedio_final']) }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            @php
                                                $estatusClases = match ($alumno['estatus']) {
                                                    'Destacado'
                                                        => 'bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50',
                                                    'Aprobado'
                                                        => 'bg-sky-50 text-sky-700 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50',
                                                    'En riesgo'
                                                        => 'bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50',
                                                    default
                                                        => 'bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/50',
                                                };
                                            @endphp
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $estatusClases }}">
                                                {{ $alumno['estatus'] }}
                                            </span>
                                        </td>

                                        @php
                                            /*
                                             * Se preparan los ids del alumno para generar los PDF.
                                             * Así los botones funcionan aunque se esté consultando con filtros en "Todos".
                                             */
                                            $alumnoInscripcionId = $alumno['inscripcion_id'] ?? null;
                                            $alumnoGeneracionId = $alumno['generacion_id'] ?? ($generacion_id ?? null);
                                            $alumnoGradoId = $alumno['grado_id'] ?? ($grado_id ?? null);
                                            $alumnoGrupoId = $alumno['grupo_id'] ?? ($grupo_id ?? null);
                                            $alumnoSemestreId = $alumno['semestre_id'] ?? ($semestre_id ?? null);
                                        @endphp

                                        @if ($esBachillerato)
                                            <td class="px-4 py-3 text-center">
                                                <a href="{{ route('misrutas.promedios.boleta.pdf', [
                                                    'slug_nivel' => $slug_nivel,
                                                    'tipo' => 'semestral',
                                                    'inscripcion_id' => $alumnoInscripcionId,
                                                    'ciclo_escolar_id' => $ciclo_escolar_id,
                                                    'generacion_id' => $alumnoGeneracionId,
                                                    'grado_id' => $alumnoGradoId,
                                                    'grupo_id' => $alumnoGrupoId,
                                                    'semestre_id' => $alumnoSemestreId,
                                                ]) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 px-3 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                                    <flux:icon.document-arrow-down class="h-4 w-4" />
                                                    PDF
                                                </a>
                                            </td>
                                        @else
                                            <td class="px-4 py-3 text-center">
                                                <a href="{{ route('misrutas.promedios.boleta.pdf', [
                                                    'slug_nivel' => $slug_nivel,
                                                    'tipo' => 'boleta',
                                                    'inscripcion_id' => $alumnoInscripcionId,
                                                    'ciclo_escolar_id' => $ciclo_escolar_id,
                                                    'generacion_id' => $alumnoGeneracionId,
                                                    'grado_id' => $alumnoGradoId,
                                                    'grupo_id' => $alumnoGrupoId,
                                                ]) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-sky-600 px-3 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                                    <flux:icon.document-arrow-down class="h-4 w-4" />
                                                    PDF
                                                </a>
                                            </td>

                                            <td class="px-4 py-3 text-center">
                                                <a href="{{ route('misrutas.promedios.boleta.pdf', [
                                                    'slug_nivel' => $slug_nivel,
                                                    'tipo' => 'reconocimiento',
                                                    'inscripcion_id' => $alumnoInscripcionId,
                                                    'ciclo_escolar_id' => $ciclo_escolar_id,
                                                    'generacion_id' => $alumnoGeneracionId,
                                                    'grado_id' => $alumnoGradoId,
                                                    'grupo_id' => $alumnoGrupoId,
                                                ]) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-blue-600 px-3 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                                    <flux:icon.trophy class="h-4 w-4" />
                                                    PDF
                                                </a>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <a href="{{ route('misrutas.diploma.pdf', [
                                                    'slug_nivel' => $slug_nivel,
                                                    'tipo' => 'diploma',
                                                    'inscripcion_id' => $alumnoInscripcionId,
                                                    'ciclo_escolar_id' => $ciclo_escolar_id,
                                                    'generacion_id' => $alumnoGeneracionId,
                                                    'grado_id' => $alumnoGradoId,
                                                    'grupo_id' => $alumnoGrupoId,
                                                ]) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 px-3 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                                    <flux:icon.trophy class="h-4 w-4" />
                                                    PDF
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @empty
            <div
                class="rounded-[1.7rem] border border-dashed border-slate-300 bg-white p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-neutral-800 dark:text-slate-300">
                    <flux:icon.inbox class="h-7 w-7" />
                </div>
                <h3 class="mt-4 text-lg font-black text-slate-950 dark:text-white">Sin promedios para mostrar</h3>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500 dark:text-slate-400">
                    Verifica que existan calificaciones numéricas capturadas en el ciclo escolar seleccionado y que las
                    materias estén marcadas como calificables, no extra y no receso.
                </p>
            </div>
        @endforelse
    </div>
</div>

@script
    <script>
        Alpine.data('graficaPromediosGenerales', (datos) => ({
            grafica: null,

            iniciar() {
                this.cargarApexCharts().then(() => {
                    this.dibujar();
                });
            },

            cargarApexCharts() {
                return new Promise((resolve) => {
                    if (window.ApexCharts) {
                        resolve();
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                    script.onload = resolve;
                    document.head.appendChild(script);
                });
            },

            dibujar() {
                if (!this.$refs.contenedor || !window.ApexCharts) {
                    return;
                }

                if (this.grafica) {
                    this.grafica.destroy();
                }

                const categorias = datos.categorias || [];

                if (categorias.length === 0) {
                    this.$refs.contenedor.innerHTML =
                        '<div class="flex min-h-[320px] items-center justify-center rounded-2xl border border-dashed border-slate-300 text-sm font-bold text-slate-500 dark:border-neutral-700 dark:text-slate-400">Sin datos suficientes para graficar.</div>';
                    return;
                }

                this.grafica = new ApexCharts(this.$refs.contenedor, {
                    chart: {
                        type: 'line',
                        height: 330,
                        toolbar: {
                            show: true,
                            tools: {
                                download: true,
                                selection: false,
                                zoom: false,
                                zoomin: false,
                                zoomout: false,
                                pan: false,
                                reset: false,
                            }
                        }
                    },
                    series: [{
                            name: 'Promedio',
                            type: 'line',
                            data: datos.promedios || []
                        },
                        {
                            name: 'Aprobados',
                            type: 'column',
                            data: datos.aprobados || []
                        },
                        {
                            name: 'En riesgo',
                            type: 'column',
                            data: datos.riesgo || []
                        },
                        {
                            name: 'Incompletos',
                            type: 'column',
                            data: datos.incompletos || []
                        }
                    ],
                    stroke: {
                        width: [4, 0, 0, 0],
                        curve: 'smooth'
                    },
                    dataLabels: {
                        enabled: false
                    },
                    xaxis: {
                        categories,
                        labels: {
                            rotate: -15,
                            trim: true
                        }
                    },
                    yaxis: [{
                            title: {
                                text: 'Promedio'
                            },
                            min: 0,
                            max: 10
                        },
                        {
                            opposite: true,
                            title: {
                                text: 'Alumnos'
                            },
                            min: 0
                        }
                    ],
                    tooltip: {
                        shared: true,
                        intersect: false
                    },
                    legend: {
                        position: 'top'
                    },
                    grid: {
                        strokeDashArray: 4
                    }
                });

                this.grafica.render();
            }
        }));
    </script>
@endscript
