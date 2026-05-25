<div x-data="scrollLockPro('materias_profesor')" x-init="iniciar()" x-on:pointerdown.capture="guardarScroll()"
    x-on:keydown.capture="guardarScroll()" x-on:change.capture="guardarScroll()"
    x-on:input.capture.debounce.150ms="guardarScroll()" class="space-y-6">
    <section class="space-y-4">
        <article
            class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm transition-all duration-300 dark:border-neutral-800 dark:bg-neutral-900">

            <button type="button" x-on:click.prevent="cambiar('materias_profesor')"
                class="group flex w-full items-center justify-between gap-4 px-5 py-5 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/60 sm:px-6">

                <div class="flex items-center gap-4">
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-700 text-white shadow-lg shadow-indigo-500/20 transition group-hover:scale-105">
                        <flux:icon.academic-cap class="h-5 w-5" />
                    </span>

                    <span>
                        <span class="block text-base font-black text-slate-900 dark:text-white">
                            Materias y listas del profesor
                        </span>

                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">
                            Consulta materias desde horarios y descarga listas de asistencia o evaluación.
                        </span>
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    @if ($this->profesorSeleccionado)
                        <span
                            class="hidden rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900/60 sm:inline-flex">
                            {{ $this->totalMaterias }} materia(s)
                        </span>
                    @endif

                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                        x-bind:class="abierto === 'materias_profesor'
                            ?
                            'rotate-180 border-indigo-200 text-indigo-600 dark:border-indigo-900 dark:text-indigo-300' :
                            ''">
                        <flux:icon.chevron-down class="h-5 w-5" />
                    </span>
                </div>
            </button>

            <div x-cloak x-show="abierto === 'materias_profesor'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                class="border-t border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/30 sm:p-6">

                <div
                    class="relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

                    <div class="h-1.5 w-full bg-gradient-to-r from-violet-600 via-indigo-600 to-blue-700"></div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                    Consulta de materias y generación de listas
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Cada materia usa periodo o parcial según el nivel al que pertenece.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="limpiarFiltrosMaterias"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                    <flux:icon.funnel class="h-4 w-4" />
                                    Limpiar filtros
                                </button>

                                <button type="button" wire:click="limpiarTodo"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
                                    <flux:icon.x-mark class="h-4 w-4" />
                                    Limpiar todo
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
                            <div class="xl:col-span-4">
                                <div
                                    class="overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                    <div
                                        class="border-b border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50">
                                        <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                            Buscar profesor
                                        </h4>

                                        <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                            Escribe nombre, CURP, RFC o correo.
                                        </p>
                                    </div>

                                    <div class="space-y-4 p-4">
                                        <flux:field>
                                            <flux:label>Profesor</flux:label>

                                            <flux:input type="search" wire:model.live.debounce.400ms="buscar_profesor"
                                                placeholder="Buscar profesor..." />
                                        </flux:field>

                                        @if ($this->profesorSeleccionado)
                                            <div
                                                class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                                                <div class="flex items-start gap-3">
                                                    <div
                                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white">
                                                        <flux:icon.user-circle class="h-6 w-6" />
                                                    </div>

                                                    <div class="min-w-0">
                                                        <p class="text-sm font-black text-slate-900 dark:text-white">
                                                            {{ $this->nombreProfesor($this->profesorSeleccionado) }}
                                                        </p>

                                                        <p
                                                            class="mt-1 text-xs font-bold text-indigo-700 dark:text-indigo-300">
                                                            {{ $this->rolPrincipal($this->profesorSeleccionado) }}
                                                        </p>

                                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                            {{ $this->profesorSeleccionado->correo ?? 'Sin correo registrado' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($this->profesores->isNotEmpty())
                                            <div class="max-h-[360px] space-y-2 overflow-y-auto pr-1">
                                                @foreach ($this->profesores as $profesor)
                                                    <button type="button"
                                                        wire:click="seleccionarProfesor({{ $profesor->id }})"
                                                        class="w-full rounded-2xl border border-slate-200 bg-white p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-indigo-900/60 dark:hover:bg-indigo-950/20">
                                                        <p class="text-sm font-black text-slate-900 dark:text-white">
                                                            {{ $this->nombreProfesor($profesor) }}
                                                        </p>

                                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                                            <span
                                                                class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                                                {{ $this->rolPrincipal($profesor) }}
                                                            </span>

                                                            @if ($profesor->rfc)
                                                                <span
                                                                    class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                                                                    RFC: {{ $profesor->rfc }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @elseif ($buscar_profesor !== '' && !$this->profesorSeleccionado)
                                            <div
                                                class="rounded-2xl border border-dashed border-slate-300 p-6 text-center dark:border-neutral-700">
                                                <p class="font-black text-slate-700 dark:text-slate-200">
                                                    No se encontraron profesores.
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="xl:col-span-8">
                                <div
                                    class="overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                    <div
                                        class="border-b border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                            <div>
                                                <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                                    Materias encontradas
                                                </h4>

                                                <p
                                                    class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                    Se muestran únicamente materias que existen dentro de horarios.
                                                </p>
                                            </div>

                                            @if ($this->puedeDescargarTodas())
                                                <div class="flex flex-wrap gap-2">
                                                    <a href="{{ $this->urlAsistencia() }}" target="_blank"
                                                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700">
                                                        <flux:icon.document-arrow-down class="h-4 w-4" />
                                                        Asistencia todas
                                                    </a>

                                                    <a href="{{ $this->urlEvaluacion() }}" target="_blank"
                                                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-700">
                                                        <flux:icon.document-arrow-down class="h-4 w-4" />
                                                        Evaluación todas
                                                    </a>
                                                </div>
                                            @else
                                                @if ($this->profesorSeleccionado && $this->materiasAgrupadas->isNotEmpty())
                                                    <div
                                                        class="rounded-2xl bg-amber-50 px-4 py-2 text-xs font-black text-amber-700 ring-1 ring-amber-100 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/50">
                                                        Selecciona el periodo o parcial de cada materia para descargar
                                                        todas.
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    <div class="p-4">
                                        @if (!$this->profesorSeleccionado)
                                            <div
                                                class="rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center dark:border-neutral-700 dark:bg-neutral-950/40">
                                                <div
                                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                                                    <flux:icon.magnifying-glass class="h-6 w-6" />
                                                </div>

                                                <p class="mt-4 text-base font-black text-slate-800 dark:text-white">
                                                    Selecciona un profesor para consultar sus materias.
                                                </p>
                                            </div>
                                        @else
                                            <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                <flux:field>
                                                    <flux:label>Buscar materia</flux:label>

                                                    <flux:input type="search"
                                                        wire:model.live.debounce.400ms="buscar_materia"
                                                        placeholder="Materia o clave..." />
                                                </flux:field>

                                                <flux:field>
                                                    <flux:label>Nivel</flux:label>

                                                    <flux:select wire:model.live="filtro_nivel">
                                                        <flux:select.option value="">Todos</flux:select.option>

                                                        @foreach ($this->nivelesFiltro as $nivel)
                                                            <flux:select.option value="{{ $nivel->id }}">
                                                                {{ $nivel->nombre }}
                                                            </flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </flux:field>

                                                <flux:field>
                                                    <flux:label>Grado</flux:label>

                                                    <flux:select wire:model.live="filtro_grado">
                                                        <flux:select.option value="">Todos</flux:select.option>

                                                        @foreach ($this->gradosFiltro as $grado)
                                                            <flux:select.option value="{{ $grado->id }}">
                                                                {{ $grado->nombre }}
                                                            </flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </flux:field>

                                                <flux:field>
                                                    <flux:label>Grupo</flux:label>

                                                    <flux:select wire:model.live="filtro_grupo">
                                                        <flux:select.option value="">Todos</flux:select.option>

                                                        @foreach ($this->gruposFiltro as $grupo)
                                                            <flux:select.option value="{{ $grupo->id }}">
                                                                {{ $grupo->asignacionGrupo?->nombre ?? 'Grupo' }}
                                                            </flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </flux:field>

                                                <flux:field>
                                                    <flux:label>Generación</flux:label>

                                                    <flux:select wire:model.live="filtro_generacion">
                                                        <flux:select.option value="">Todas</flux:select.option>

                                                        @foreach ($this->generacionesFiltro as $generacion)
                                                            <flux:select.option value="{{ $generacion->id }}">
                                                                {{ $this->textoGeneracion($generacion) }}
                                                            </flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </flux:field>

                                                @if ($this->semestresFiltro->isNotEmpty())
                                                    <flux:field>
                                                        <flux:label>Semestre</flux:label>

                                                        <flux:select wire:model.live="filtro_semestre">
                                                            <flux:select.option value="">Todos
                                                            </flux:select.option>

                                                            @foreach ($this->semestresFiltro as $semestre)
                                                                <flux:select.option value="{{ $semestre->id }}">
                                                                    {{ $this->textoSemestre($semestre) }}
                                                                </flux:select.option>
                                                            @endforeach
                                                        </flux:select>
                                                    </flux:field>
                                                @endif

                                                <flux:field>
                                                    <flux:label>Día</flux:label>

                                                    <flux:select wire:model.live="filtro_dia">
                                                        <flux:select.option value="">Todos</flux:select.option>

                                                        @foreach ($this->diasFiltro as $dia)
                                                            <flux:select.option value="{{ $dia->id }}">
                                                                {{ $dia->dia }}
                                                            </flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </flux:field>
                                            </div>

                                            <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                                                <div
                                                    class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                                                    <p
                                                        class="text-xs font-black uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                                                        Materias
                                                    </p>

                                                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                                                        {{ $this->totalMaterias }}
                                                    </p>
                                                </div>

                                                <div
                                                    class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
                                                    <p
                                                        class="text-xs font-black uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                                        Horarios
                                                    </p>

                                                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                                                        {{ $this->totalHoras }}
                                                    </p>
                                                </div>

                                                <div
                                                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                                                    <p
                                                        class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                                        Descarga
                                                    </p>

                                                    <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">
                                                        {{ $this->puedeDescargarTodas() ? 'PDF habilitado' : 'Selecciona cada periodo/parcial' }}
                                                    </p>
                                                </div>
                                            </div>

                                            @if ($this->materiasAgrupadas->isEmpty())
                                                <div
                                                    class="rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center dark:border-neutral-700 dark:bg-neutral-950/40">
                                                    <p class="text-base font-black text-slate-800 dark:text-white">
                                                        No hay materias en horario para este profesor.
                                                    </p>
                                                </div>
                                            @else
                                                <div
                                                    class="overflow-hidden rounded-[1.3rem] border border-slate-200 dark:border-neutral-800">
                                                    <div class="overflow-x-auto">
                                                        <table
                                                            class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                                                            <thead class="bg-slate-100 dark:bg-neutral-950">
                                                                <tr>
                                                                    <th
                                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                        Materia
                                                                    </th>
                                                                    <th
                                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                        Grupo
                                                                    </th>
                                                                    <th
                                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                        Generación / semestre
                                                                    </th>
                                                                    <th
                                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                        Horario
                                                                    </th>
                                                                    <th
                                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                        Periodo / Parcial
                                                                    </th>
                                                                    <th
                                                                        class="px-4 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                        PDF
                                                                    </th>
                                                                </tr>
                                                            </thead>

                                                            <tbody
                                                                class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                                                                @foreach ($this->materiasAgrupadas as $item)
                                                                    @php
                                                                        $materia = $item['materia'];
                                                                        $asignacionId = $item['asignacion_id'];
                                                                        $esBachillerato = $this->esBachilleratoMateria(
                                                                            $item,
                                                                        );
                                                                    @endphp

                                                                    <tr
                                                                        class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/60">
                                                                        <td class="px-4 py-3">
                                                                            <p
                                                                                class="font-black text-slate-900 dark:text-white">
                                                                                {{ $materia?->materia ?? 'Materia no especificada' }}
                                                                            </p>

                                                                            <div class="mt-1 flex flex-wrap gap-1.5">
                                                                                <span
                                                                                    class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                                                                    {{ $materia?->clave ?? 'Sin clave' }}
                                                                                </span>

                                                                                @if ($materia?->receso)
                                                                                    <span
                                                                                        class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                                                                                        Receso
                                                                                    </span>
                                                                                @elseif($materia?->calificable)
                                                                                    <span
                                                                                        class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                                                        Calificable
                                                                                    </span>
                                                                                @else
                                                                                    <span
                                                                                        class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                                                                        No calificable
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </td>

                                                                        <td
                                                                            class="px-4 py-3 text-slate-700 dark:text-slate-200">
                                                                            <p class="font-bold">
                                                                                {{ $item['nivel']?->nombre ?? '—' }}
                                                                            </p>

                                                                            <p
                                                                                class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                                                {{ $item['grado']?->nombre ?? '—' }}
                                                                                · Grupo
                                                                                {{ $item['grupo']?->asignacionGrupo?->nombre ?? '—' }}
                                                                            </p>
                                                                        </td>

                                                                        <td
                                                                            class="px-4 py-3 text-slate-700 dark:text-slate-200">
                                                                            <p class="font-bold">
                                                                                {{ $this->textoGeneracion($item['generacion']) }}
                                                                            </p>

                                                                            @if ($item['semestre'])
                                                                                <p
                                                                                    class="mt-1 text-xs font-semibold text-violet-600 dark:text-violet-300">
                                                                                    {{ $this->textoSemestre($item['semestre']) }}
                                                                                </p>
                                                                            @endif
                                                                        </td>

                                                                        <td class="px-4 py-3">
                                                                            <div class="space-y-1">
                                                                                @foreach ($item['horarios'] as $horario)
                                                                                    <p
                                                                                        class="text-xs font-bold text-slate-600 dark:text-slate-300">
                                                                                        {{ $horario->dia?->dia ?? 'Día' }}
                                                                                        ·
                                                                                        {{ $this->textoHora($horario->hora) }}
                                                                                    </p>
                                                                                @endforeach
                                                                            </div>
                                                                        </td>

                                                                        <td class="px-4 py-3">
                                                                            @if ($esBachillerato)
                                                                                <flux:select
                                                                                    wire:model.live="parciales_por_materia.{{ $asignacionId }}">
                                                                                    <flux:select.option value="">
                                                                                        Selecciona parcial
                                                                                    </flux:select.option>

                                                                                    @foreach ($this->parcialesParaMateria($item) as $parcial)
                                                                                        <flux:select.option
                                                                                            value="{{ $parcial->id }}">
                                                                                            Parcial
                                                                                            {{ $parcial->parcial }}
                                                                                            {{ $parcial->descripcion ? ' - ' . $parcial->descripcion : '' }}
                                                                                            @if ($parcial->meses)
                                                                                                / {{ $parcial->meses }}
                                                                                            @endif
                                                                                        </flux:select.option>
                                                                                    @endforeach
                                                                                </flux:select>

                                                                                <p
                                                                                    class="mt-1 text-[11px] font-bold text-violet-600 dark:text-violet-300">
                                                                                    Bachillerato trabaja por parcial.
                                                                                </p>
                                                                            @else
                                                                                <flux:select
                                                                                    wire:model.live="periodos_por_materia.{{ $asignacionId }}">
                                                                                    <flux:select.option value="">
                                                                                        Selecciona periodo
                                                                                    </flux:select.option>

                                                                                    @foreach ($this->periodosParaMateria($item) as $periodo)
                                                                                        <flux:select.option
                                                                                            value="{{ $periodo->id }}">
                                                                                            Periodo
                                                                                            {{ $periodo->periodo }}
                                                                                            {{ $periodo->descripcion ? ' - ' . $periodo->descripcion : '' }}
                                                                                            @if ($periodo->meses)
                                                                                                / {{ $periodo->meses }}
                                                                                            @endif
                                                                                        </flux:select.option>
                                                                                    @endforeach
                                                                                </flux:select>

                                                                                <p
                                                                                    class="mt-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-300">
                                                                                    Básica trabaja por periodo.
                                                                                </p>
                                                                            @endif
                                                                        </td>

                                                                        <td class="px-4 py-3 text-right">
                                                                            <div
                                                                                class="flex flex-wrap justify-end gap-2">
                                                                                @if ($this->puedeDescargarMateria($item))
                                                                                    <a href="{{ $this->urlAsistencia($asignacionId) }}"
                                                                                        target="_blank"
                                                                                        class="inline-flex items-center justify-center rounded-xl bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 ring-1 ring-emerald-100 transition hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50">
                                                                                        Asistencia
                                                                                    </a>

                                                                                    <a href="{{ $this->urlEvaluacion($asignacionId) }}"
                                                                                        target="_blank"
                                                                                        class="inline-flex items-center justify-center rounded-xl bg-indigo-50 px-3 py-2 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 transition hover:bg-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900/50">
                                                                                        Evaluación
                                                                                    </a>
                                                                                @else
                                                                                    <span
                                                                                        class="text-xs font-bold text-slate-400">
                                                                                        {{ $esBachillerato ? 'Selecciona parcial' : 'Selecciona periodo' }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </section>

    @script
        <script>
            Alpine.data('scrollLockPro', (nombre) => ({
                /*
                 * El collapse inicia cerrado siempre.
                 * No se lee localStorage para evitar que se abra automáticamente.
                 */
                abierto: null,

                nombre,
                llaveScroll: `scroll_actual_${nombre}`,
                restaurando: false,

                iniciar() {
                    history.scrollRestoration = 'manual';

                    /*
                     * Se elimina cualquier estado anterior del collapse.
                     * Así siempre carga cerrado al entrar o recargar.
                     */
                    localStorage.removeItem(`collapse_${this.nombre}`);

                    this.guardarScroll();
                    this.registrarHookLivewire();
                },

                registrarHookLivewire() {
                    if (!window.__scrollLockProHooks) {
                        window.__scrollLockProHooks = {};
                    }

                    if (window.__scrollLockProHooks[this.nombre]) {
                        return;
                    }

                    window.__scrollLockProHooks[this.nombre] = true;

                    const registrar = () => {
                        Livewire.hook('commit', ({
                            succeed
                        }) => {
                            const posicion = this.obtenerScrollGuardado();

                            succeed(() => {
                                this.restaurarScrollSeguro(posicion);
                            });
                        });
                    };

                    if (window.Livewire) {
                        registrar();
                        return;
                    }

                    document.addEventListener('livewire:init', () => {
                        registrar();
                    }, {
                        once: true
                    });
                },

                cambiar(seccion) {
                    this.guardarScroll();

                    this.abierto = this.abierto === seccion ? null : seccion;

                    /*
                     * No se guarda el collapse en localStorage.
                     * Así no queda abierto cuando se recarga la página.
                     */
                    this.restaurarScrollSeguro(this.obtenerScrollGuardado());
                },

                guardarScroll() {
                    if (this.restaurando) {
                        return;
                    }

                    const y = window.scrollY ||
                        document.documentElement.scrollTop ||
                        document.body.scrollTop ||
                        0;

                    sessionStorage.setItem(this.llaveScroll, String(y));

                    if (!window.__scrollLockProUltimo) {
                        window.__scrollLockProUltimo = {};
                    }

                    window.__scrollLockProUltimo[this.nombre] = y;
                },

                obtenerScrollGuardado() {
                    const desdeSesion = sessionStorage.getItem(this.llaveScroll);

                    if (desdeSesion !== null) {
                        const y = Number(desdeSesion);

                        if (!Number.isNaN(y)) {
                            return y;
                        }
                    }

                    if (
                        window.__scrollLockProUltimo &&
                        window.__scrollLockProUltimo[this.nombre] !== undefined
                    ) {
                        const y = Number(window.__scrollLockProUltimo[this.nombre]);

                        if (!Number.isNaN(y)) {
                            return y;
                        }
                    }

                    return window.scrollY || 0;
                },

                restaurarScrollSeguro(posicion = null) {
                    const y = Number(posicion ?? this.obtenerScrollGuardado());

                    if (Number.isNaN(y)) {
                        return;
                    }

                    this.restaurando = true;

                    const restaurar = () => {
                        window.scrollTo({
                            top: y,
                            left: 0,
                            behavior: 'auto',
                        });
                    };

                    requestAnimationFrame(restaurar);

                    setTimeout(restaurar, 20);
                    setTimeout(restaurar, 60);
                    setTimeout(restaurar, 120);
                    setTimeout(restaurar, 220);

                    setTimeout(() => {
                        this.restaurando = false;
                    }, 260);
                },
            }));
        </script>
    @endscript

</div>
