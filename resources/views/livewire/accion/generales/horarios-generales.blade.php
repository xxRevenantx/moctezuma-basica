<div class="space-y-6">
    @php
        $tabla = $this->tablaGeneral;
    @endphp

    <section
        class="relative overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 w-full bg-gradient-to-r from-cyan-500 via-blue-600 to-violet-600"></div>

        <div wire:loading.delay.flex
            wire:target="ciclo_escolar_id,alcance,generacion_id,grado_id,semestre_id,grupo_id,filtro_grado_id,filtro_grupo_id,filtro_dia_id,filtro_materia,limpiarFiltros,limpiarFiltrosTabla"
            class="absolute inset-0 z-30 hidden items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-neutral-900/75">
            <div
                class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                Construyendo horario general...
            </div>
        </div>

        <div class="space-y-6 p-5 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-600 to-violet-600 text-white shadow-lg shadow-blue-500/20">
                        <flux:icon.calendar-days class="h-6 w-6" />
                    </div>

                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">
                            Horario general concentrado
                        </h3>

                        <p class="mt-1 max-w-4xl text-sm text-slate-500 dark:text-slate-400">
                            Reúne en una sola tabla todos los grupos del nivel seleccionado. Cada actividad muestra
                            grado, grupo y materia; los recesos se detectan directamente desde la base de datos según
                            el horario configurado para cada nivel.
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700 ring-1 ring-cyan-100 dark:bg-cyan-950/30 dark:text-cyan-300 dark:ring-cyan-900/50">
                                Nivel: {{ $nivel?->nombre ?? '—' }}
                            </span>

                            <span
                                class="inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-xs font-black text-violet-700 ring-1 ring-violet-100 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/50">
                                Alcance: {{ $this->textoAlcance }}
                            </span>

                            <span
                                class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50">
                                {{ $this->totalGruposConHorario }}
                                {{ $this->totalGruposConHorario === 1 ? 'grupo con horario' : 'grupos con horario' }}
                            </span>
                        </div>
                    </div>
                </div>

                <button type="button" wire:click="limpiarFiltros"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                    <flux:icon.arrow-path class="h-4 w-4" />
                    Limpiar todo
                </button>
            </div>

            <div
                class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-200">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200">
                        <flux:icon.information-circle class="h-5 w-5" />
                    </div>

                    <div>
                        <p class="font-black">Cómo se construye el horario</p>
                        <p class="mt-1">
                            Las columnas corresponden a los días y las filas a los bloques de hora. Dentro de cada
                            celda se muestran varios grupos al mismo tiempo, por ejemplo: <strong>1° A ·
                                Matemáticas</strong>.
                            Los grupos sin horario se omiten y el PDF conserva exactamente los filtros de la tabla.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Filtros principales del alcance --}}
            <div
                class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/30 sm:p-5">
                <div class="mb-4">
                    <p class="text-sm font-black text-slate-900 dark:text-white">Alcance del horario</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Define los grupos base que formarán parte de la tabla y del PDF.
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
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Alcance de descarga</flux:label>
                        <flux:select wire:model.change="alcance">
                            <flux:select.option value="nivel">Nivel completo</flux:select.option>
                            <flux:select.option value="grado">Grado seleccionado</flux:select.option>
                            <flux:select.option value="grupo">Grupo específico</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Generación</flux:label>
                        <flux:select wire:model.change="generacion_id" :disabled="$alcance === 'nivel'">
                            <flux:select.option value="">Selecciona una generación</flux:select.option>

                            @foreach ($generaciones as $generacion)
                                <flux:select.option value="{{ $generacion->id }}">
                                    {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Grado</flux:label>
                        <flux:select wire:model.change="grado_id"
                            :disabled="$alcance === 'nivel' || !$generacion_id">
                            <flux:select.option value="">Selecciona un grado</flux:select.option>

                            @foreach ($grados as $grado)
                                <flux:select.option value="{{ $grado->id }}">
                                    {{ $grado->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    @if ($esBachillerato)
                        <flux:field>
                            <flux:label>Semestre</flux:label>
                            <flux:select wire:model.change="semestre_id"
                                :disabled="$alcance === 'nivel' || !$grado_id">
                                <flux:select.option value="">Selecciona un semestre</flux:select.option>

                                @foreach ($semestres as $semestre)
                                    <flux:select.option value="{{ $semestre->id }}">
                                        Semestre {{ $semestre->numero }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:label>Grupo</flux:label>
                        <flux:select wire:model.change="grupo_id"
                            :disabled="$alcance !== 'grupo' || !$generacion_id || !$grado_id || ($esBachillerato && !
                                $semestre_id)">
                            <flux:select.option value="">Selecciona un grupo</flux:select.option>

                            @foreach ($grupos as $grupo)
                                <flux:select.option value="{{ $grupo->id }}">
                                    {{ $grupo->asignacionGrupo?->nombre ?? 'Grupo' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            </div>

            {{-- Filtros de la tabla concentrada --}}
            <div
                class="rounded-3xl border border-indigo-200 bg-indigo-50/40 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/10 sm:p-5">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-black text-slate-900 dark:text-white">Filtros de la tabla y del PDF</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Puedes reducir el horario por grado, grupo, día o materia sin perder el formato concentrado.
                        </p>
                    </div>

                    <button type="button" wire:click="limpiarFiltrosTabla"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-indigo-200 bg-white px-4 py-2 text-xs font-black text-indigo-700 shadow-sm transition hover:bg-indigo-50 dark:border-indigo-900/50 dark:bg-neutral-900 dark:text-indigo-300 dark:hover:bg-neutral-800">
                        <flux:icon.funnel class="h-4 w-4" />
                        Quitar filtros de tabla
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <flux:field>
                        <flux:label>Filtrar por grado</flux:label>
                        <flux:select wire:model.change="filtro_grado_id" :disabled="$this->gradosTabla->isEmpty()">
                            <flux:select.option value="">Todos los grados</flux:select.option>

                            @foreach ($this->gradosTabla as $gradoFiltro)
                                <flux:select.option value="{{ $gradoFiltro->id }}">
                                    {{ $gradoFiltro->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Filtrar por grupo</flux:label>
                        <flux:select wire:model.change="filtro_grupo_id" :disabled="$this->gruposTabla->isEmpty()">
                            <flux:select.option value="">Todos los grupos</flux:select.option>

                            @foreach ($this->gruposTabla as $grupoFiltro)
                                <flux:select.option value="{{ $grupoFiltro->id }}">
                                    {{ $this->etiquetaGrupo($grupoFiltro) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Filtrar por día</flux:label>
                        <flux:select wire:model.change="filtro_dia_id" :disabled="$this->diasTabla->isEmpty()">
                            <flux:select.option value="">Todos los días</flux:select.option>

                            @foreach ($this->diasTabla as $diaFiltro)
                                <flux:select.option value="{{ $diaFiltro->id }}">
                                    {{ $diaFiltro->dia }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Filtrar por materia</flux:label>
                        <flux:select wire:model.change="filtro_materia" :disabled="$this->materiasTabla->isEmpty()">
                            <flux:select.option value="">Todas las materias</flux:select.option>

                            @foreach ($this->materiasTabla as $materiaFiltro)
                                <flux:select.option value="{{ $materiaFiltro['id'] }}">
                                    {{ $materiaFiltro['nombre'] }}
                                    @if ($materiaFiltro['tipo'] === 'Taller')
                                        — Taller
                                    @endif
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            </div>

            {{-- Estado y descarga --}}
            <div
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <div @class([
                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' =>
                                $this->puedeDescargar,
                            'bg-slate-200 text-slate-500 dark:bg-neutral-800 dark:text-slate-400' => !$this->puedeDescargar,
                        ])>
                            @if ($this->puedeDescargar)
                                <flux:icon.check-circle class="h-5 w-5" />
                            @else
                                <flux:icon.lock-closed class="h-5 w-5" />
                            @endif
                        </div>

                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white">Estado del horario</p>

                            @if ($this->puedeDescargar)
                                <p class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-300">
                                    La tabla concentra {{ $this->totalGruposVisibles }}
                                    {{ $this->totalGruposVisibles === 1 ? 'grupo' : 'grupos' }} y
                                    {{ $tabla['total_actividades'] ?? 0 }} actividades.
                                </p>
                            @elseif (!$this->totalGruposConHorario)
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    No hay grupos con horario para el ciclo y alcance seleccionados.
                                </p>
                            @else
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Los filtros actuales no contienen actividades para mostrar.
                                </p>
                            @endif
                        </div>
                    </div>

                    @if ($this->urlDescarga)
                        <a href="{{ $this->urlDescarga }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-500 via-blue-600 to-violet-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:shadow-xl">
                            <flux:icon.document-arrow-down class="h-5 w-5" />
                            Descargar horario PDF
                        </a>
                    @else
                        <button type="button" disabled
                            class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-200 px-5 py-3 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-500">
                            <flux:icon.lock-closed class="h-5 w-5" />
                            Descargar horario PDF
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Vista previa concentrada --}}
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-neutral-800 sm:px-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">
                        Vista previa
                    </p>
                    <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                        Horario general de {{ $nivel?->nombre ?? 'nivel' }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Horas en vertical, días en horizontal y todos los grados y grupos dentro de cada celda.
                    </p>
                </div>

                @if ($tabla && ($tabla['grupos'] ?? collect())->isNotEmpty())
                    <div class="flex max-w-3xl flex-wrap gap-2 lg:justify-end">
                        @foreach ($tabla['grupos'] as $grupoVisible)
                            <span
                                class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-200 dark:bg-neutral-800 dark:text-slate-200 dark:ring-neutral-700">
                                {{ $this->etiquetaGrupo($grupoVisible) }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="p-4 sm:p-6">
            @if (!$tabla || ($tabla['total_actividades'] ?? 0) === 0)
                <div
                    class="flex min-h-64 flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 text-center dark:border-neutral-700 dark:bg-neutral-950/40">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-200 text-slate-500 dark:bg-neutral-800 dark:text-slate-400">
                        <flux:icon.calendar-days class="h-7 w-7" />
                    </div>
                    <p class="mt-4 text-base font-black text-slate-700 dark:text-slate-200">
                        Sin actividades para mostrar
                    </p>
                    <p class="mt-2 max-w-xl text-sm text-slate-500 dark:text-slate-400">
                        Selecciona un ciclo con horarios capturados o modifica los filtros de grado, grupo, día y
                        materia.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto rounded-3xl border border-slate-200 dark:border-neutral-800">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-cyan-500 via-blue-600 to-violet-600 text-white">
                                <th
                                    class="sticky left-0 z-[3] min-w-[145px] border-r border-white/20 bg-blue-700 px-4 py-4 text-left text-xs font-black uppercase tracking-wide">
                                    Horario
                                </th>

                                @foreach ($tabla['dias'] as $dia)
                                    <th
                                        class="min-w-[235px] border-r border-white/20 px-4 py-4 text-center text-sm font-black uppercase tracking-wide last:border-r-0">
                                        {{ $dia->dia }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="bg-white dark:bg-neutral-900">
                            @foreach ($tabla['filas'] as $indiceFila => $fila)
                                @php
                                    $hora = $fila['hora'];
                                @endphp

                                @if ($fila['es_receso'])
                                    <tr wire:key="fila-receso-{{ $hora->id }}-{{ $filtro_dia_id ?? 'todos' }}">
                                        <td
                                            class="sticky left-0 z-[2] border-b border-r border-amber-300 bg-amber-100 px-4 py-4 align-middle dark:border-amber-900/60 dark:bg-amber-950/40">
                                            <p
                                                class="whitespace-nowrap text-sm font-black text-amber-900 dark:text-amber-100">
                                                {{ $this->formatoHora($hora->hora_inicio) }}
                                            </p>
                                            <p
                                                class="mt-1 whitespace-nowrap text-xs font-semibold text-amber-700 dark:text-amber-300">
                                                a {{ $this->formatoHora($hora->hora_fin) }}
                                            </p>
                                        </td>
                                        <td colspan="{{ max(1, count($tabla['dias'])) }}"
                                            class="border-b border-amber-300 bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 px-5 py-4 text-center dark:border-amber-900/60">
                                            <p
                                                class="text-2xl font-black tracking-[0.35em] text-white drop-shadow-sm sm:text-3xl">
                                                {{ $fila['receso_label'] }}
                                            </p>
                                        </td>
                                    </tr>
                                @else
                                    <tr wire:key="fila-hora-{{ $hora->id }}-{{ $filtro_dia_id ?? 'todos' }}"
                                        class="transition hover:bg-slate-50/70 dark:hover:bg-neutral-800/30">
                                        <td
                                            class="sticky left-0 z-[2] border-b border-r border-slate-200 bg-slate-50 px-4 py-4 align-top dark:border-neutral-800 dark:bg-neutral-950">
                                            <p
                                                class="whitespace-nowrap text-sm font-black text-slate-800 dark:text-slate-100">
                                                {{ $this->formatoHora($hora->hora_inicio) }}
                                            </p>
                                            <p
                                                class="mt-1 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">
                                                a {{ $this->formatoHora($hora->hora_fin) }}
                                            </p>
                                        </td>

                                        @foreach ($tabla['dias'] as $dia)
                                            @php
                                                $actividades = $fila['celdas']->get((int) $dia->id, collect());
                                            @endphp

                                            <td
                                                class="min-h-[100px] border-b border-r border-slate-200 p-2.5 align-top last:border-r-0 dark:border-neutral-800">
                                                @if ($actividades->isEmpty())
                                                    <div
                                                        class="flex min-h-[76px] items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-3 text-center text-xs font-semibold text-slate-400 dark:border-neutral-800 dark:bg-neutral-950/30 dark:text-neutral-600">
                                                        Sin actividad
                                                    </div>
                                                @else
                                                    <div class="space-y-2">
                                                        @foreach ($actividades as $actividad)
                                                            <article @class([
                                                                'rounded-2xl border px-3 py-2.5 shadow-sm',
                                                                'border-violet-200 bg-violet-50 text-violet-950 dark:border-violet-900/50 dark:bg-violet-950/20 dark:text-violet-100' =>
                                                                    $actividad['tipo'] === 'taller',
                                                                'border-sky-200 bg-sky-50 text-sky-950 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-100' =>
                                                                    $actividad['tipo'] === 'materia',
                                                            ])>
                                                                <div class="flex items-start gap-2">
                                                                    <span @class([
                                                                        'inline-flex shrink-0 rounded-lg px-2 py-1 text-[10px] font-black uppercase tracking-wide text-white',
                                                                        'bg-violet-600' => $actividad['tipo'] === 'taller',
                                                                        'bg-blue-600' => $actividad['tipo'] === 'materia',
                                                                    ])>
                                                                        {{ $actividad['grado_grupo'] }}
                                                                    </span>

                                                                    <div class="min-w-0">
                                                                        <p class="text-sm font-black leading-tight">
                                                                            {{ $actividad['nombre'] }}
                                                                        </p>

                                                                        @if ($actividad['tipo'] === 'taller')
                                                                            <p
                                                                                class="mt-1 text-[10px] font-black uppercase tracking-wide opacity-70">
                                                                                Taller
                                                                            </p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </article>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Grupos
                            visibles</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                            {{ $tabla['total_grupos'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            Actividades</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                            {{ $tabla['total_actividades'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            Bloques especiales</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                            {{ $tabla['total_recesos'] }}</p>
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
