<div class="space-y-6">
    {{-- ITERA NIVELES --}}
    <div class="overflow-hidden">
        <div>
            <div class="-mx-1 overflow-x-auto pb-1">
                <div class="flex min-w-max items-center justify-center gap-2 px-1">
                    @foreach ($niveles as $item)
                        @php
                            $activo = $slug_nivel === $item->slug;
                        @endphp

                        <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'horarios']) }}"
                            wire:navigate aria-current="{{ $activo ? 'page' : 'false' }}"
                            class="group relative inline-flex items-center gap-2 whitespace-nowrap rounded-2xl border px-4 py-3 text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5
                            {{ $activo
                                ? 'border-sky-200 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20 dark:border-sky-700/50'
                                : 'border-slate-200 bg-white text-slate-700 shadow-sm hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:border-sky-800 dark:hover:bg-neutral-800 dark:hover:text-sky-300' }}">

                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-xl
                                {{ $activo
                                    ? 'bg-white/15 text-white'
                                    : 'bg-slate-100 text-slate-500 group-hover:bg-sky-100 group-hover:text-sky-700 dark:bg-neutral-700 dark:text-slate-300 dark:group-hover:bg-sky-950/40 dark:group-hover:text-sky-300' }}">
                                <flux:icon.rectangle-stack class="h-4 w-4" />
                            </span>

                            <span>{{ $item->nombre }}</span>

                            @if ($activo)
                                <span class="rounded-full bg-white/15 px-2 py-0.5 text-[11px] font-bold text-white">
                                    Activo
                                </span>

                                <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-white/80"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIONES --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <livewire:hora :nivel="$nivel" :key="'hora-' . $nivel->id" />
        <livewire:dia :nivel="$nivel" :key="'dia-' . $nivel->id" />
    </div>

    {{-- TABLA BASE DEL HORARIO --}}
    <section
        class="relative overflow-hidden rounded-[28px] border border-white/60 bg-white/85 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
        <div class="h-1.5 w-full bg-gradient-to-r from-violet-500 via-fuchsia-500 to-pink-500"></div>

        {{-- Loader --}}
        <div wire:loading.flex
            wire:target="refrescarHorasDias,generacion_id,grado_id,grupo_id,semestre_id,limpiarFiltros"
            class="absolute inset-0 z-30 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-black/50">
            <div
                class="flex flex-col items-center gap-3 rounded-3xl border border-slate-200 bg-white/90 px-6 py-5 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900/90">
                <svg class="h-8 w-8 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                    </path>
                </svg>

                <div class="text-center">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Cargando sección de horario...
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Espera un momento
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-5 p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-800/50 dark:bg-violet-950/30 dark:text-violet-300">
                        <flux:icon.table-cells class="h-5 w-5" />
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                            Sección de horario
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Filtra por generación, grado, grupo y semestre para preparar la asignación del horario.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300">
                        Horas: {{ $horas->count() }}
                    </span>

                    <span
                        class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-300">
                        Días: {{ $dias->count() }}
                    </span>

                    <span
                        class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                        Asignadas: {{ $this->celdasAsignadas }}/{{ $this->totalCeldas }}
                    </span>

                    <span
                        class="rounded-full border border-fuchsia-200 bg-fuchsia-50 px-3 py-1 text-xs font-semibold text-fuchsia-700 dark:border-fuchsia-900/50 dark:bg-fuchsia-950/30 dark:text-fuchsia-300">
                        Avance: {{ $this->avanceHorario }}%
                    </span>

                    <button type="button" wire:click="limpiarFiltros"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300 dark:hover:bg-neutral-700">
                        <flux:icon.arrow-path class="h-4 w-4" />
                        Limpiar
                    </button>

                    <a target="_blank" href="{{ $this->urlDescargaHorario }}" @class([
                        'inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-semibold transition-all duration-300' => true,
                        'border border-emerald-200 bg-gradient-to-r from-emerald-500 via-green-500 to-teal-500 text-white shadow-lg shadow-emerald-500/20 hover:-translate-y-0.5 hover:shadow-xl dark:border-emerald-700/50' =>
                            $this->puedeDescargarHorario,
                        'pointer-events-none cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-500' => !$this->puedeDescargarHorario,
                    ])
                        aria-disabled="{{ $this->puedeDescargarHorario ? 'false' : 'true' }}"
                        title="{{ $this->puedeDescargarHorario ? 'Descargar horario' : 'Completa los filtros para habilitar la descarga' }}">
                        <flux:icon.arrow-down-tray class="h-4 w-4" />
                        <span>Descargar Horario</span>
                    </a>
                    <button type="button" wire:click="exportarHorario" wire:loading.attr="disabled"
                        wire:target="exportarHorario" @if (!$this->puedeDescargarHorario) disabled @endif
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-lime-200 bg-white px-4 py-2 text-sm font-semibold text-lime-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-lime-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-lime-900/40 dark:bg-neutral-900 dark:text-lime-300 dark:hover:bg-lime-950/20">

                        <span wire:loading.remove wire:target="exportarHorario" class="inline-flex items-center gap-2">
                            <flux:icon.table-cells class="h-4 w-4" />
                            Excel
                        </span>

                        <span wire:loading wire:target="exportarHorario" class="inline-flex items-center gap-2">
                            <span
                                class="h-4 w-4 animate-spin rounded-full border-2 border-lime-300 border-t-lime-700"></span>
                            Exportando...
                        </span>
                    </button>
                </div>
            </div>

            @if ($mensajeActualizacionHorario === 'Horario actualizado correctamente.')
                <div
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-950/30 dark:text-emerald-300">
                    {{ $mensajeActualizacionHorario }}
                </div>
            @endif

            {{-- FILTROS --}}
            <div
                class="rounded-3xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <flux:field>
                        <flux:label>Generación</flux:label>

                        <flux:select wire:model.live="generacion_id">
                            <option value="">Selecciona una generación</option>

                            @foreach ($generaciones as $generacion)
                                <option value="{{ $generacion->id }}">
                                    {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                </option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Grado</flux:label>

                        <flux:select wire:model.live="grado_id">
                            <option value="">Selecciona un grado</option>

                            @foreach ($grados as $grado)
                                <option value="{{ $grado->id }}">
                                    {{ $grado->nombre }}
                                </option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    @if ($esBachillerato)
                        <flux:field>
                            <flux:label>Semestre</flux:label>

                            <flux:select wire:model.live="semestre_id" :disabled="!$grado_id">
                                <option value="">Selecciona un semestre</option>

                                @foreach ($semestres as $semestre)
                                    <option value="{{ $semestre->id }}">
                                        {{ $semestre->numero }}° semestre
                                    </option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:label>Grupo</flux:label>

                        <flux:select wire:model.live="grupo_id"
                            :disabled="$esBachillerato
                                ?
                                (!$generacion_id || !$grado_id || !$semestre_id) :
                                (!$generacion_id || !$grado_id)">
                            <option value="">Selecciona un grupo</option>

                            @foreach ($grupos as $grupo)
                                <option value="{{ $grupo->id }}">
                                    {{ $this->textoGrupo($grupo) }}
                                </option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <div class="flex items-end">
                        <div
                            class="w-full rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-3 text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-950/40 dark:text-slate-400">
                            @if ($generacion_id || $grado_id || $grupo_id || $semestre_id)
                                <span class="font-semibold text-slate-700 dark:text-slate-200">
                                    Filtros activos.
                                </span>
                                La tabla ya está lista para conectar la asignación.
                            @else
                                Selecciona los filtros para trabajar el horario.
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($horas->isEmpty() || $dias->isEmpty())
                <div
                    class="rounded-3xl border border-dashed border-slate-300 bg-slate-50/70 px-6 py-10 text-center dark:border-neutral-700 dark:bg-neutral-900/60">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-neutral-800">
                        <flux:icon.exclamation-circle class="h-6 w-6 text-slate-400 dark:text-slate-500" />
                    </div>

                    <h4 class="mt-4 text-base font-bold text-slate-800 dark:text-white">
                        Aún no se puede construir el horario
                    </h4>

                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Primero agrega al menos una hora y un día para mostrar la tabla del horario.
                    </p>
                </div>
            @elseif (!$generacion_id || !$grado_id || !$grupo_id || ($esBachillerato && !$semestre_id))
                <div
                    class="rounded-3xl border border-dashed border-violet-300 bg-violet-50/70 px-6 py-10 text-center dark:border-violet-900/40 dark:bg-violet-950/20">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-neutral-800">
                        <flux:icon.funnel class="h-6 w-6 text-violet-500 dark:text-violet-300" />
                    </div>

                    <h4 class="mt-4 text-base font-bold text-slate-800 dark:text-white">
                        Faltan filtros por seleccionar
                    </h4>

                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        @if ($esBachillerato)
                            Selecciona generación, grado, semestre y grupo para mostrar la tabla del horario.
                        @else
                            Selecciona generación, grado y grupo para mostrar la tabla del horario.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-hidden rounded-3xl border border-slate-200/80 dark:border-neutral-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead class="bg-slate-50 dark:bg-neutral-900/70">
                                <tr>
                                    <th
                                        class="min-w-[170px] border-b border-r border-slate-200 px-4 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                        Hora
                                    </th>

                                    @foreach ($dias as $dia)
                                        <th
                                            class="min-w-[220px] border-b border-r border-slate-200 px-4 py-4 text-center text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                            <div class="flex flex-col items-center gap-1">
                                                <span>{{ $dia->dia }}</span>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="bg-white dark:bg-neutral-950/40">
                                @foreach ($horas as $hora)
                                    <tr class="transition hover:bg-slate-50/60 dark:hover:bg-neutral-800/40">
                                        <td
                                            class="border-b border-r border-slate-200 px-4 py-4 align-middle dark:border-neutral-800">
                                            <div class="space-y-1">
                                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_inicio)->format('h:i A') }}
                                                    -
                                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_fin)->format('h:i A') }}
                                                </p>
                                            </div>
                                        </td>

                                        @foreach ($dias as $dia)
                                            @php
                                                $claveCelda = $hora->id . '-' . $dia->id;
                                                $horarioGuardado = $horariosGuardados->get($claveCelda);

                                                $asignacionGuardada = $horarioGuardado
                                                    ? $materiasDisponibles->firstWhere(
                                                        'id',
                                                        $horarioGuardado?->asignacion_materia_id,
                                                    )
                                                    : null;

                                                $profesor = $asignacionGuardada?->profesor;

                                                $nombreProfesor = $profesor
                                                    ? trim(
                                                        ($profesor->nombre ?? '') .
                                                            ' ' .
                                                            ($profesor->apellido_paterno ?? '') .
                                                            ' ' .
                                                            ($profesor->apellido_materno ?? ''),
                                                    )
                                                    : 'Sin profesor asignado';

                                                $estiloProfesor = $this->obtenerEstiloProfesor($nombreProfesor);

                                                $materiaGuardada = $asignacionGuardada?->materia;
                                            @endphp

                                            <td
                                                class="h-28 border-b border-r border-slate-200 px-3 py-3 align-top dark:border-neutral-800">
                                                <div class="space-y-2">
                                                    <flux:field>
                                                        <flux:select
                                                            wire:model.live="seleccionesHorario.{{ $claveCelda }}">
                                                            <flux:select.option value="">
                                                                Selecciona una materia
                                                            </flux:select.option>

                                                            @foreach ($materiasDisponibles as $asignacion)
                                                                @php
                                                                    $materia = $asignacion->materia;
                                                                @endphp

                                                                <flux:select.option value="{{ $asignacion->id }}">
                                                                    {{ $materia?->materia ?? 'Sin materia' }}

                                                                    @if ($materia?->clave)
                                                                        - {{ $materia->clave }}
                                                                    @endif

                                                                    @if ($materia?->extra)
                                                                        - Extra
                                                                    @endif

                                                                    @if ($materia?->receso)
                                                                        - Receso
                                                                    @endif
                                                                </flux:select.option>
                                                            @endforeach
                                                        </flux:select>
                                                    </flux:field>

                                                    @if ($horarioGuardado && $asignacionGuardada)
                                                        <div class="rounded-2xl border px-3 py-2 text-center shadow-sm"
                                                            style="
                                                                background-color: {{ $estiloProfesor['background'] }};
                                                                color: {{ $estiloProfesor['color'] }};
                                                                border-color: {{ $estiloProfesor['border'] }};
                                                            ">
                                                            <p class="text-[12px] font-bold leading-tight">
                                                                {{ $materiaGuardada?->materia ?? 'Sin materia' }}
                                                            </p>

                                                            <p class="mt-1 text-[11px] font-semibold opacity-90">
                                                                {{ $nombreProfesor }}
                                                            </p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TABLA DE DOCENTES DEL HORARIO --}}
                <div
                    class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950/40">
                    <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-600"></div>

                    <div class="space-y-5 p-5 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    <flux:icon.user-group class="h-5 w-5" />
                                </div>

                                <div>
                                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                                        Docentes del horario
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        Resumen de docentes, materias, días y horas asignadas en el horario
                                        seleccionado.
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <div
                                    class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-center dark:border-sky-900/40 dark:bg-sky-950/30">
                                    <p
                                        class="text-[11px] font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">
                                        Docentes
                                    </p>
                                    <p class="mt-1 text-xl font-black text-sky-900 dark:text-sky-100">
                                        {{ $this->totalDocentesHorario }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-center dark:border-emerald-900/40 dark:bg-emerald-950/30">
                                    <p
                                        class="text-[11px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                        Materias
                                    </p>
                                    <p class="mt-1 text-xl font-black text-emerald-900 dark:text-emerald-100">
                                        {{ $this->totalMateriasHorario }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-center dark:border-indigo-900/40 dark:bg-indigo-950/30">
                                    <p
                                        class="text-[11px] font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                                        Carga
                                    </p>
                                    <p class="mt-1 text-xl font-black text-indigo-900 dark:text-indigo-100">
                                        {{ $this->totalHorasHorarioTexto }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-center dark:border-rose-900/40 dark:bg-rose-950/30">
                                    <p
                                        class="text-[11px] font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">
                                        Sin profesor
                                    </p>
                                    <p class="mt-1 text-xl font-black text-rose-900 dark:text-rose-100">
                                        {{ $this->totalSinProfesorHorario }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if ($this->resumenDocentesHorario->isEmpty())
                            <div
                                class="rounded-3xl border border-dashed border-slate-300 bg-slate-50/70 px-6 py-10 text-center dark:border-neutral-700 dark:bg-neutral-900/60">
                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-neutral-800">
                                    <flux:icon.users class="h-6 w-6 text-slate-400 dark:text-slate-500" />
                                </div>

                                <h4 class="mt-4 text-base font-bold text-slate-800 dark:text-white">
                                    Todavía no hay docentes asignados
                                </h4>

                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                    Cuando asignes materias al horario, aquí aparecerá el resumen completo por docente.
                                </p>
                            </div>
                        @else
                            <div
                                class="overflow-hidden rounded-3xl border border-slate-200/80 dark:border-neutral-800">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full border-collapse">
                                        <thead class="bg-slate-50 dark:bg-neutral-900/70">
                                            <tr>
                                                <th
                                                    class="min-w-[260px] border-b border-r border-slate-200 px-4 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                    Docente
                                                </th>

                                                <th
                                                    class="min-w-[260px] border-b border-r border-slate-200 px-4 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                    Materias
                                                </th>

                                                <th
                                                    class="min-w-[120px] border-b border-r border-slate-200 px-4 py-4 text-center text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                    Módulos
                                                </th>

                                                <th
                                                    class="min-w-[130px] border-b border-r border-slate-200 px-4 py-4 text-center text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                    Horas
                                                </th>

                                                <th
                                                    class="min-w-[200px] border-b border-r border-slate-200 px-4 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                    Días
                                                </th>

                                                <th
                                                    class="min-w-[360px] border-b border-slate-200 px-4 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                    Detalle de horarios
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody class="bg-white dark:bg-neutral-950/40">
                                            @foreach ($this->resumenDocentesHorario as $docente)
                                                <tr
                                                    class="transition hover:bg-slate-50/70 dark:hover:bg-neutral-900/70">
                                                    <td
                                                        class="border-b border-r border-slate-200 px-4 py-4 align-top dark:border-neutral-800">
                                                        <div class="flex items-start gap-3">
                                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border text-sm font-black shadow-sm"
                                                                style="
                                                    background-color: {{ $docente['estilo']['background'] }};
                                                    color: {{ $docente['estilo']['color'] }};
                                                    border-color: {{ $docente['estilo']['border'] }};
                                                ">
                                                                {{ mb_substr($docente['profesor'], 0, 1) }}
                                                            </div>

                                                            <div class="min-w-0">
                                                                <p
                                                                    class="text-sm font-black uppercase leading-snug text-slate-800 dark:text-white">
                                                                    {{ $docente['profesor'] }}
                                                                </p>

                                                                @if ($docente['sin_profesor'])
                                                                    <span
                                                                        class="mt-2 inline-flex rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-[11px] font-bold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                                                        Requiere asignar docente
                                                                    </span>
                                                                @else
                                                                    <span
                                                                        class="mt-2 inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                                        Docente asignado
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td
                                                        class="border-b border-r border-slate-200 px-4 py-4 align-top dark:border-neutral-800">
                                                        <div class="flex flex-col gap-2">
                                                            @foreach ($docente['materias'] as $materia)
                                                                <div
                                                                    class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-neutral-800 dark:bg-neutral-900/70">
                                                                    <div class="flex flex-wrap items-center gap-2">
                                                                        <span
                                                                            class="text-xs font-black uppercase text-slate-700 dark:text-slate-200">
                                                                            {{ $materia['materia'] }}
                                                                        </span>

                                                                        @if ($materia['clave'])
                                                                            <span
                                                                                class="rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200 dark:bg-neutral-950 dark:text-slate-400 dark:ring-neutral-700">
                                                                                {{ $materia['clave'] }}
                                                                            </span>
                                                                        @endif

                                                                        @if ($materia['extra'])
                                                                            <span
                                                                                class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700 ring-1 ring-violet-200 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/40">
                                                                                Extra
                                                                            </span>
                                                                        @endif

                                                                        @if ($materia['receso'])
                                                                            <span
                                                                                class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/40">
                                                                                Receso
                                                                            </span>
                                                                        @endif
                                                                    </div>

                                                                    <p
                                                                        class="mt-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                                                        {{ $materia['modulos'] }} módulo(s) ·
                                                                        {{ $this->formatearMinutosHorario($materia['minutos']) }}
                                                                    </p>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </td>

                                                    <td
                                                        class="border-b border-r border-slate-200 px-4 py-4 text-center align-top dark:border-neutral-800">
                                                        <span
                                                            class="inline-flex min-w-12 justify-center rounded-2xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-black text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                                            {{ $docente['total_modulos'] }}
                                                        </span>
                                                    </td>

                                                    <td
                                                        class="border-b border-r border-slate-200 px-4 py-4 text-center align-top dark:border-neutral-800">
                                                        <span
                                                            class="inline-flex rounded-2xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-black text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300">
                                                            {{ $docente['total_horas_texto'] }}
                                                        </span>
                                                    </td>

                                                    <td
                                                        class="border-b border-r border-slate-200 px-4 py-4 align-top text-sm font-semibold text-slate-600 dark:border-neutral-800 dark:text-slate-300">
                                                        {{ $docente['dias'] ?: 'Sin días' }}
                                                    </td>

                                                    <td
                                                        class="border-b border-slate-200 px-4 py-4 align-top dark:border-neutral-800">
                                                        <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                                                            @foreach ($docente['materias'] as $materia)
                                                                <div
                                                                    class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70">
                                                                    <p
                                                                        class="text-[11px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                        {{ $materia['materia'] }}
                                                                    </p>

                                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                                        @foreach ($materia['horarios'] as $horarioDetalle)
                                                                            <span
                                                                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:border-neutral-700 dark:bg-neutral-950 dark:text-slate-300">
                                                                                <span
                                                                                    class="text-sky-600 dark:text-sky-300">
                                                                                    {{ $horarioDetalle['dia'] }}
                                                                                </span>
                                                                                <span class="text-slate-400">·</span>
                                                                                {{ $horarioDetalle['hora'] }}
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @if ($this->totalSinProfesorHorario > 0)
                                <div
                                    class="rounded-3xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200">
                                    <div class="flex items-start gap-3">
                                        <flux:icon.exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0" />

                                        <div>
                                            <p class="font-bold">
                                                Hay módulos sin profesor asignado.
                                            </p>

                                            <p class="mt-1 text-xs leading-relaxed">
                                                Revisa las materias marcadas como “Sin profesor asignado” para completar
                                                la información
                                                antes de descargar o imprimir el horario.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>


                {{-- DIAGNÓSTICO INTELIGENTE DEL HORARIO --}}
                @if ($this->diagnosticoHorario['hay_datos'])
                    <div
                        class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white shadow-xl shadow-slate-200/50 dark:border-neutral-800 dark:bg-neutral-950/50 dark:shadow-black/20">

                        <div class="h-1.5 w-full bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500"></div>

                        <div class="space-y-6 p-5 sm:p-6">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 shadow-sm dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        <flux:icon.sparkles class="h-7 w-7" />
                                    </div>

                                    <div>
                                        <div
                                            class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-100 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/40">
                                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                            Diagnóstico automático
                                        </div>

                                        <h3 class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                                            {{ $this->diagnosticoHorario['titulo'] }}
                                        </h3>

                                        <p class="mt-1 max-w-3xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                                            {{ $this->diagnosticoHorario['descripcion'] }}
                                        </p>
                                    </div>
                                </div>

                                <div class="w-full xl:w-[360px]">
                                    <div
                                        class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/70">
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                Salud general
                                            </p>

                                            <span
                                                class="rounded-full px-3 py-1 text-xs font-black {{ $this->claseTarjetaDiagnosticoHorario($this->diagnosticoHorario['color']) }}">
                                                {{ $this->diagnosticoHorario['porcentaje_salud'] }}%
                                            </span>
                                        </div>

                                        <div class="mt-3 h-4 w-full overflow-hidden rounded-full bg-white shadow-inner dark:bg-neutral-950">
                                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-amber-500 to-rose-500 transition-all duration-500"
                                                style="width: {{ $this->diagnosticoHorario['porcentaje_salud'] }}%"></div>
                                        </div>

                                        <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                            Entre más alto sea el porcentaje, más completo y limpio está el horario.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Tarjetas principales --}}
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach ($this->diagnosticoHorario['tarjetas'] as $tarjeta)
                                    <div
                                        class="rounded-3xl border p-4 shadow-sm {{ $this->claseTarjetaDiagnosticoHorario($tarjeta['color']) }}">
                                        <p class="text-[11px] font-black uppercase tracking-wide opacity-80">
                                            {{ $tarjeta['titulo'] }}
                                        </p>

                                        <p class="mt-2 text-3xl font-black">
                                            {{ $tarjeta['valor'] }}
                                        </p>

                                        <p class="mt-1 text-xs font-semibold opacity-80">
                                            {{ $tarjeta['detalle'] }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Alertas inteligentes --}}
                            @if ($this->diagnosticoHorario['alertas']->isNotEmpty())
                                <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                                    @foreach ($this->diagnosticoHorario['alertas'] as $alerta)
                                        <div
                                            class="rounded-3xl border p-4 {{ $this->claseAlertaDiagnosticoHorario($alerta['tipo']) }}">
                                            <div class="flex items-start gap-3">
                                                <div
                                                    class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-white/70 dark:bg-neutral-950/40">
                                                    @if ($alerta['tipo'] === 'danger')
                                                        <flux:icon.x-circle class="h-5 w-5" />
                                                    @elseif ($alerta['tipo'] === 'warning')
                                                        <flux:icon.exclamation-triangle class="h-5 w-5" />
                                                    @elseif ($alerta['tipo'] === 'success')
                                                        <flux:icon.check-circle class="h-5 w-5" />
                                                    @else
                                                        <flux:icon.information-circle class="h-5 w-5" />
                                                    @endif
                                                </div>

                                                <div>
                                                    <p class="text-sm font-black">
                                                        {{ $alerta['titulo'] }}
                                                    </p>

                                                    <p class="mt-1 text-xs font-semibold leading-relaxed opacity-90">
                                                        {{ $alerta['mensaje'] }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Materias pendientes + distribución por día --}}
                            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                                <div
                                    class="rounded-[24px] border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/70">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                                Materias disponibles sin colocar
                                            </h4>

                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Materias asignadas al grupo que todavía no aparecen dentro del horario.
                                            </p>
                                        </div>

                                        <span
                                            class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200 dark:bg-neutral-950 dark:text-slate-300 dark:ring-neutral-700">
                                            {{ $this->diagnosticoHorario['materias_pendientes']->count() }}
                                        </span>
                                    </div>

                                    <div class="mt-4 space-y-2">
                                        @forelse ($this->diagnosticoHorario['materias_pendientes'] as $materiaPendiente)
                                            <div
                                                class="rounded-2xl border border-white bg-white px-4 py-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/60">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xs font-black uppercase text-slate-800 dark:text-white">
                                                        {{ $materiaPendiente['materia'] }}
                                                    </span>

                                                    @if ($materiaPendiente['clave'])
                                                        <span
                                                            class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200 dark:bg-neutral-900 dark:text-slate-400 dark:ring-neutral-700">
                                                            {{ $materiaPendiente['clave'] }}
                                                        </span>
                                                    @endif

                                                    @if ($materiaPendiente['extra'])
                                                        <span
                                                            class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700 ring-1 ring-violet-200 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/40">
                                                            Extra
                                                        </span>
                                                    @endif

                                                    @if ($materiaPendiente['receso'])
                                                        <span
                                                            class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/40">
                                                            Receso
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="mt-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                                    Docente: {{ $materiaPendiente['profesor'] }}
                                                </p>
                                            </div>
                                        @empty
                                            <div
                                                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-5 text-center text-sm font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                Todas las materias disponibles ya fueron colocadas en el horario.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <div
                                    class="rounded-[24px] border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/70">
                                    <div>
                                        <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                            Carga por día
                                        </h4>

                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Comparativo rápido de módulos asignados por día.
                                        </p>
                                    </div>

                                    <div class="mt-4 space-y-3">
                                        @foreach ($this->diagnosticoHorario['distribucion_dias'] as $dia)
                                            @php
                                                $porcentajeDia = $this->horas->count() > 0
                                                    ? min(100, round(($dia['modulos'] / $this->horas->count()) * 100))
                                                    : 0;
                                            @endphp

                                            <div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-xs font-black uppercase text-slate-600 dark:text-slate-300">
                                                        {{ $dia['dia'] }}
                                                    </p>

                                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                                        {{ $dia['modulos'] }} módulo(s) · {{ $this->formatearMinutosHorario($dia['minutos']) }}
                                                    </p>
                                                </div>

                                                <div class="mt-1 h-3 w-full overflow-hidden rounded-full bg-white shadow-inner dark:bg-neutral-950">
                                                    <div class="h-full rounded-full bg-gradient-to-r from-sky-500 to-indigo-600"
                                                        style="width: {{ $porcentajeDia }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if ($this->diagnosticoHorario['dia_mayor_carga'])
                                        <div
                                            class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs font-semibold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                            Día con mayor carga:
                                            <span class="font-black">
                                                {{ $this->diagnosticoHorario['dia_mayor_carga']['dia'] }}
                                            </span>
                                            con
                                            <span class="font-black">
                                                {{ $this->diagnosticoHorario['dia_mayor_carga']['modulos'] }}
                                            </span>
                                            módulo(s).
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Distribución semanal por materia + carga docente --}}
                            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                                <div class="overflow-hidden rounded-[24px] border border-slate-200 dark:border-neutral-800">
                                    <div
                                        class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-900/80">
                                        <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                            Distribución semanal por materia
                                        </h4>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Permite detectar materias demasiado cargadas o poco distribuidas.
                                        </p>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border-collapse">
                                            <thead class="bg-white dark:bg-neutral-950">
                                                <tr>
                                                    <th
                                                        class="border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                        Materia
                                                    </th>
                                                    <th
                                                        class="border-b border-r border-slate-200 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                        Módulos
                                                    </th>
                                                    <th
                                                        class="border-b border-r border-slate-200 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                        Horas
                                                    </th>
                                                    <th
                                                        class="border-b border-slate-200 px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                        Días
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody class="bg-white dark:bg-neutral-950/60">
                                                @forelse ($this->diagnosticoHorario['distribucion_materias'] as $materia)
                                                    <tr class="hover:bg-slate-50 dark:hover:bg-neutral-900">
                                                        <td
                                                            class="border-b border-r border-slate-200 px-4 py-3 align-top dark:border-neutral-800">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <span class="text-xs font-black uppercase text-slate-800 dark:text-white">
                                                                    {{ $materia['materia'] }}
                                                                </span>

                                                                @if ($materia['extra'])
                                                                    <span
                                                                        class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700 ring-1 ring-violet-200 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/40">
                                                                        Extra
                                                                    </span>
                                                                @endif

                                                                @if ($materia['receso'])
                                                                    <span
                                                                        class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/40">
                                                                        Receso
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </td>

                                                        <td
                                                            class="border-b border-r border-slate-200 px-4 py-3 text-center dark:border-neutral-800">
                                                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 dark:bg-sky-950/30 dark:text-sky-300">
                                                                {{ $materia['modulos'] }}
                                                            </span>
                                                        </td>

                                                        <td
                                                            class="border-b border-r border-slate-200 px-4 py-3 text-center text-xs font-bold text-slate-600 dark:border-neutral-800 dark:text-slate-300">
                                                            {{ $this->formatearMinutosHorario($materia['minutos']) }}
                                                        </td>

                                                        <td
                                                            class="border-b border-slate-200 px-4 py-3 text-xs font-semibold text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                            {{ $materia['dias'] ?: 'Sin días' }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="px-6 py-8 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">
                                                            Todavía no hay materias colocadas en el horario.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-[24px] border border-slate-200 dark:border-neutral-800">
                                    <div
                                        class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-900/80">
                                        <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                            Estado de carga docente
                                        </h4>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Clasificación automática de carga por cantidad de módulos.
                                        </p>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border-collapse">
                                            <thead class="bg-white dark:bg-neutral-950">
                                                <tr>
                                                    <th
                                                        class="border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                        Docente
                                                    </th>
                                                    <th
                                                        class="border-b border-r border-slate-200 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                        Módulos
                                                    </th>
                                                    <th
                                                        class="border-b border-r border-slate-200 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                        Horas
                                                    </th>
                                                    <th
                                                        class="border-b border-slate-200 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                                        Estado
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody class="bg-white dark:bg-neutral-950/60">
                                                @forelse ($this->diagnosticoHorario['docentes_carga'] as $docente)
                                                    <tr class="hover:bg-slate-50 dark:hover:bg-neutral-900">
                                                        <td
                                                            class="border-b border-r border-slate-200 px-4 py-3 text-xs font-black uppercase text-slate-800 dark:border-neutral-800 dark:text-white">
                                                            {{ $docente['profesor'] }}
                                                        </td>

                                                        <td
                                                            class="border-b border-r border-slate-200 px-4 py-3 text-center dark:border-neutral-800">
                                                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 dark:bg-sky-950/30 dark:text-sky-300">
                                                                {{ $docente['modulos'] }}
                                                            </span>
                                                        </td>

                                                        <td
                                                            class="border-b border-r border-slate-200 px-4 py-3 text-center text-xs font-bold text-slate-600 dark:border-neutral-800 dark:text-slate-300">
                                                            {{ $docente['horas'] }}
                                                        </td>

                                                        <td class="border-b border-slate-200 px-4 py-3 text-center dark:border-neutral-800">
                                                            <span class="rounded-full border px-3 py-1 text-xs font-black {{ $docente['clase'] }}">
                                                                {{ $docente['estado'] }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="px-6 py-8 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">
                                                            Todavía no hay docentes para analizar.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="rounded-3xl border border-indigo-200 bg-gradient-to-br from-indigo-50 via-sky-50 to-cyan-50 p-4 dark:border-indigo-900/40 dark:from-indigo-950/20 dark:via-neutral-950 dark:to-cyan-950/20">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                            Recomendación del sistema
                                        </h4>

                                        <p class="mt-1 text-xs font-semibold leading-relaxed text-slate-600 dark:text-slate-300">
                                            Antes de descargar el horario, revisa que no existan materias pendientes,
                                            módulos sin profesor o días con carga muy desigual.
                                        </p>
                                    </div>

                                    <span
                                        class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-2 text-xs font-black text-indigo-700 shadow-sm ring-1 ring-indigo-100 dark:bg-neutral-950 dark:text-indigo-300 dark:ring-indigo-900/40">
                                        Diagnóstico listo
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- GRÁFICAS DEL HORARIO --}}
                @if ($this->graficasHorario['hay_datos'])
                    <div wire:key="graficas-horario-wrapper-{{ md5(
                        json_encode([
                            'nivel' => $nivel?->id,
                            'generacion' => $generacion_id,
                            'grado' => $grado_id,
                            'grupo' => $grupo_id,
                            'semestre' => $semestre_id,
                            'datos' => $this->graficasHorario,
                        ]),
                    ) }}"
                        x-data="graficasHorarioPro(@js($this->graficasHorario))" x-init="iniciar()"
                        class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white shadow-xl shadow-slate-200/50 dark:border-neutral-800 dark:bg-neutral-950/50 dark:shadow-black/20">

                        <div class="h-1.5 w-full bg-gradient-to-r from-cyan-500 via-sky-500 to-indigo-600"></div>

                        <div class="space-y-6 p-5 sm:p-6">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div
                                        class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                                        <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                                        Análisis visual del horario
                                    </div>

                                    <h3 class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                                        Gráficas del horario
                                    </h3>

                                    <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                                        Visualización de carga por docente, distribución por materia, módulos por día y
                                        avance general
                                        del horario seleccionado.
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div
                                        class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-center dark:border-sky-900/40 dark:bg-sky-950/30">
                                        <p
                                            class="text-[11px] font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">
                                            Avance
                                        </p>
                                        <p class="mt-1 text-2xl font-black text-sky-900 dark:text-sky-100">
                                            {{ $this->graficasHorario['global']['avance'] }}%
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-center dark:border-emerald-900/40 dark:bg-emerald-950/30">
                                        <p
                                            class="text-[11px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                            Docentes
                                        </p>
                                        <p class="mt-1 text-2xl font-black text-emerald-900 dark:text-emerald-100">
                                            {{ $this->graficasHorario['global']['docentes'] }}
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-center dark:border-indigo-900/40 dark:bg-indigo-950/30">
                                        <p
                                            class="text-[11px] font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                                            Materias
                                        </p>
                                        <p class="mt-1 text-2xl font-black text-indigo-900 dark:text-indigo-100">
                                            {{ $this->graficasHorario['global']['materias'] }}
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-center dark:border-rose-900/40 dark:bg-rose-950/30">
                                        <p
                                            class="text-[11px] font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">
                                            Sin profesor
                                        </p>
                                        <p class="mt-1 text-2xl font-black text-rose-900 dark:text-rose-100">
                                            {{ $this->graficasHorario['global']['sin_profesor'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                                <div
                                    class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                                Carga por docente
                                            </h4>

                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Total de módulos asignados a cada profesor.
                                            </p>
                                        </div>

                                        <span
                                            class="rounded-full bg-sky-50 px-3 py-1 text-[11px] font-bold text-sky-700 dark:bg-sky-950/30 dark:text-sky-300">
                                            Docentes
                                        </span>
                                    </div>

                                    <div wire:ignore>
                                        <div id="graficaHorarioDocentes" class="min-h-[360px]"></div>
                                    </div>
                                </div>

                                <div
                                    class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                                Módulos por materia
                                            </h4>

                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Cantidad de espacios ocupados por cada materia.
                                            </p>
                                        </div>

                                        <span
                                            class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                            Materias
                                        </span>
                                    </div>

                                    <div wire:ignore>
                                        <div id="graficaHorarioMaterias" class="min-h-[360px]"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                                <div
                                    class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70">
                                    <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                        Distribución por día
                                    </h4>

                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Módulos asignados por día de clase.
                                    </p>

                                    <div wire:ignore>
                                        <div id="graficaHorarioDias" class="min-h-[330px]"></div>
                                    </div>
                                </div>

                                <div
                                    class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70">
                                    <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                        Avance del horario
                                    </h4>

                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Porcentaje de celdas ocupadas.
                                    </p>

                                    <div wire:ignore>
                                        <div id="graficaHorarioAvance" class="min-h-[330px]"></div>
                                    </div>
                                </div>

                                <div
                                    class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70">
                                    <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                        Estado de celdas
                                    </h4>

                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Comparación entre módulos asignados y pendientes.
                                    </p>

                                    <div wire:ignore>
                                        <div id="graficaHorarioEstado" class="min-h-[330px]"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </section>

    {{-- MODAL TRASLAPE PROFESOR --}}
    <div x-data="{ open: @entangle('mostrarModalTraslapeProfesor').live }" x-cloak>
        <div x-show="open" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
            wire:click="cancelarGuardarConTraslape">

            <div x-show="open" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 blur-sm"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100 blur-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100 blur-0"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95 blur-sm"
                class="w-full max-w-4xl overflow-hidden rounded-[28px] border border-white/10 bg-white shadow-2xl dark:bg-neutral-900"
                wire:click.stop>

                <div class="h-1.5 w-full bg-gradient-to-r from-rose-500 via-orange-500 to-amber-500"></div>

                <div class="space-y-5 p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-300">
                                <flux:icon.exclamation-triangle class="h-6 w-6" />
                            </div>

                            <div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                                    Traslape de profesor detectado
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    El profesor ya tiene una asignación en el mismo día y en un horario que se cruza.
                                </p>
                            </div>
                        </div>

                        <button type="button" wire:click="cancelarGuardarConTraslape"
                            class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-neutral-800 dark:hover:text-slate-200">
                            <flux:icon.x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <div
                        class="rounded-3xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200">
                        ¿Deseas permitir que el profesor también quede asignado en este mismo horario?
                    </div>

                    <div
                        class="max-h-[340px] overflow-y-auto rounded-3xl border border-slate-200 dark:border-neutral-800">
                        <table class="min-w-full border-collapse">
                            <thead class="bg-slate-50 dark:bg-neutral-800/60">
                                <tr>
                                    <th
                                        class="border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                                        Profesor
                                    </th>

                                    <th
                                        class="border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                                        Nivel
                                    </th>

                                    <th
                                        class="border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                                        Grado
                                    </th>

                                    <th
                                        class="border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                                        Grupo
                                    </th>

                                    <th
                                        class="border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                                        Materia
                                    </th>

                                    <th
                                        class="border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                                        Día
                                    </th>

                                    <th
                                        class="border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                                        Hora
                                    </th>

                                    <th
                                        class="border-b border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                                        Semestre
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="bg-white dark:bg-neutral-900">
                                @foreach ($conflictosProfesor as $conflicto)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-neutral-800/40">
                                        <td
                                            class="border-b border-r border-slate-200 px-4 py-3 text-sm text-slate-700 dark:border-neutral-800 dark:text-slate-200">
                                            {{ $conflicto['profesor'] }}
                                        </td>

                                        <td
                                            class="border-b border-r border-slate-200 px-4 py-3 text-sm text-slate-700 dark:border-neutral-800 dark:text-slate-200">
                                            {{ $conflicto['nivel'] }}
                                        </td>

                                        <td
                                            class="border-b border-r border-slate-200 px-4 py-3 text-sm text-slate-700 dark:border-neutral-800 dark:text-slate-200">
                                            {{ $conflicto['grado'] }}
                                        </td>

                                        <td
                                            class="border-b border-r border-slate-200 px-4 py-3 text-sm text-slate-700 dark:border-neutral-800 dark:text-slate-200">
                                            {{ $conflicto['grupo'] }}
                                        </td>

                                        <td
                                            class="border-b border-r border-slate-200 px-4 py-3 text-sm text-slate-700 dark:border-neutral-800 dark:text-slate-200">
                                            {{ $conflicto['materia'] }}
                                        </td>

                                        <td
                                            class="border-b border-r border-slate-200 px-4 py-3 text-sm text-slate-700 dark:border-neutral-800 dark:text-slate-200">
                                            {{ $conflicto['dia'] }}
                                        </td>

                                        <td
                                            class="border-b border-r border-slate-200 px-4 py-3 text-sm text-slate-700 dark:border-neutral-800 dark:text-slate-200">
                                            @if (!empty($conflicto['hora_inicio']) && !empty($conflicto['hora_fin']))
                                                {{ \Carbon\Carbon::createFromFormat('H:i:s', $conflicto['hora_inicio'])->format('h:i A') }}
                                                -
                                                {{ \Carbon\Carbon::createFromFormat('H:i:s', $conflicto['hora_fin'])->format('h:i A') }}
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <td
                                            class="border-b border-slate-200 px-4 py-3 text-sm text-slate-700 dark:border-neutral-800 dark:text-slate-200">
                                            {{ $conflicto['semestre'] ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <flux:button type="button" variant="ghost" wire:click="cancelarGuardarConTraslape">
                            Cancelar
                        </flux:button>

                        <flux:button type="button" wire:click="confirmarGuardarConTraslape">
                            Sí, guardar de todos modos
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @once
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @endonce

    <script>
        function graficasHorarioPro(datosIniciales) {
            return {
                datos: datosIniciales || {},

                graficaDocentes: null,
                graficaMaterias: null,
                graficaDias: null,
                graficaAvance: null,
                graficaEstado: null,
                timer: null,

                iniciar() {
                    this.programarCarga();
                },

                programarCarga() {
                    clearTimeout(this.timer);

                    this.timer = setTimeout(() => {
                        this.$nextTick(() => {
                            this.esperarApexCharts(() => {
                                this.crearTodas();
                            });
                        });
                    }, 450);
                },

                esperarApexCharts(callback) {
                    if (typeof ApexCharts !== 'undefined') {
                        callback();
                        return;
                    }

                    setTimeout(() => {
                        this.esperarApexCharts(callback);
                    }, 120);
                },

                crearTodas() {
                    this.destruirGraficas();

                    if (!this.datos?.hay_datos) {
                        return;
                    }

                    this.crearGraficaDocentes();
                    this.crearGraficaMaterias();
                    this.crearGraficaDias();
                    this.crearGraficaAvance();
                    this.crearGraficaEstado();
                },

                destruirGraficas() {
                    if (this.graficaDocentes) {
                        this.graficaDocentes.destroy();
                        this.graficaDocentes = null;
                    }

                    if (this.graficaMaterias) {
                        this.graficaMaterias.destroy();
                        this.graficaMaterias = null;
                    }

                    if (this.graficaDias) {
                        this.graficaDias.destroy();
                        this.graficaDias = null;
                    }

                    if (this.graficaAvance) {
                        this.graficaAvance.destroy();
                        this.graficaAvance = null;
                    }

                    if (this.graficaEstado) {
                        this.graficaEstado.destroy();
                        this.graficaEstado = null;
                    }

                    this.limpiarContenedor('#graficaHorarioDocentes');
                    this.limpiarContenedor('#graficaHorarioMaterias');
                    this.limpiarContenedor('#graficaHorarioDias');
                    this.limpiarContenedor('#graficaHorarioAvance');
                    this.limpiarContenedor('#graficaHorarioEstado');
                },

                limpiarContenedor(selector) {
                    const elemento = document.querySelector(selector);

                    if (elemento) {
                        elemento.innerHTML = '';
                    }
                },

                opcionesToolbar(nombreArchivo) {
                    return {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        },
                        export: {
                            csv: {
                                filename: nombreArchivo,
                                columnDelimiter: ',',
                                headerCategory: 'Categoría',
                                headerValue: 'Valor'
                            },
                            svg: {
                                filename: nombreArchivo
                            },
                            png: {
                                filename: nombreArchivo
                            }
                        },
                        autoSelected: 'zoom'
                    };
                },

                crearGraficaDocentes() {
                    const elemento = document.querySelector('#graficaHorarioDocentes');

                    if (!elemento) {
                        return;
                    }

                    const labels = this.datos?.docentes?.labels || [];
                    const series = this.datos?.docentes?.series || [];

                    if (labels.length === 0 || series.length === 0) {
                        return;
                    }

                    this.graficaDocentes = new ApexCharts(elemento, {
                        chart: {
                            type: 'bar',
                            height: 360,
                            toolbar: this.opcionesToolbar('horario_carga_por_docente'),
                            fontFamily: 'Inter, ui-sans-serif, system-ui'
                        },
                        series: [{
                            name: 'Módulos',
                            data: series
                        }],
                        xaxis: {
                            categories: labels,
                            labels: {
                                rotate: -35,
                                style: {
                                    fontSize: '11px',
                                    fontWeight: 700
                                }
                            }
                        },
                        yaxis: {
                            min: 0,
                            forceNiceScale: true
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 9,
                                columnWidth: '48%',
                                distributed: true
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            style: {
                                fontSize: '11px',
                                fontWeight: 900
                            }
                        },
                        legend: {
                            show: false
                        },
                        grid: {
                            strokeDashArray: 4
                        },
                        tooltip: {
                            y: {
                                formatter: valor => Number(valor) + ' módulo(s)'
                            }
                        }
                    });

                    this.graficaDocentes.render();
                },

                crearGraficaMaterias() {
                    const elemento = document.querySelector('#graficaHorarioMaterias');

                    if (!elemento) {
                        return;
                    }

                    const labels = this.datos?.materias?.labels || [];
                    const series = this.datos?.materias?.series || [];

                    if (labels.length === 0 || series.length === 0) {
                        return;
                    }

                    this.graficaMaterias = new ApexCharts(elemento, {
                        chart: {
                            type: 'bar',
                            height: 360,
                            toolbar: this.opcionesToolbar('horario_modulos_por_materia'),
                            fontFamily: 'Inter, ui-sans-serif, system-ui'
                        },
                        series: [{
                            name: 'Módulos',
                            data: series
                        }],
                        xaxis: {
                            categories: labels,
                            labels: {
                                rotate: -35,
                                style: {
                                    fontSize: '11px',
                                    fontWeight: 700
                                }
                            }
                        },
                        yaxis: {
                            min: 0,
                            forceNiceScale: true
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 9,
                                horizontal: false,
                                columnWidth: '50%',
                                distributed: true
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            style: {
                                fontSize: '11px',
                                fontWeight: 900
                            }
                        },
                        legend: {
                            show: false
                        },
                        grid: {
                            strokeDashArray: 4
                        },
                        tooltip: {
                            y: {
                                formatter: valor => Number(valor) + ' módulo(s)'
                            }
                        }
                    });

                    this.graficaMaterias.render();
                },

                crearGraficaDias() {
                    const elemento = document.querySelector('#graficaHorarioDias');

                    if (!elemento) {
                        return;
                    }

                    const labels = this.datos?.dias?.labels || [];
                    const series = this.datos?.dias?.series || [];

                    if (labels.length === 0 || series.length === 0) {
                        return;
                    }

                    this.graficaDias = new ApexCharts(elemento, {
                        chart: {
                            type: 'donut',
                            height: 330,
                            toolbar: this.opcionesToolbar('horario_distribucion_por_dia'),
                            fontFamily: 'Inter, ui-sans-serif, system-ui'
                        },
                        series: series,
                        labels: labels,
                        stroke: {
                            width: 3
                        },
                        legend: {
                            position: 'bottom',
                            fontSize: '12px',
                            fontWeight: 700
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '68%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Módulos',
                                            formatter: () => series.reduce((total, valor) => total + Number(valor),
                                                0)
                                        }
                                    }
                                }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            style: {
                                fontWeight: 900
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: valor => Number(valor) + ' módulo(s)'
                            }
                        }
                    });

                    this.graficaDias.render();
                },

                crearGraficaAvance() {
                    const elemento = document.querySelector('#graficaHorarioAvance');

                    if (!elemento) {
                        return;
                    }

                    const avance = Number(this.datos?.global?.avance || 0);

                    this.graficaAvance = new ApexCharts(elemento, {
                        chart: {
                            type: 'radialBar',
                            height: 330,
                            toolbar: this.opcionesToolbar('horario_avance_general'),
                            fontFamily: 'Inter, ui-sans-serif, system-ui'
                        },
                        series: [avance],
                        labels: ['Avance'],
                        plotOptions: {
                            radialBar: {
                                hollow: {
                                    size: '64%'
                                },
                                track: {
                                    strokeWidth: '100%'
                                },
                                dataLabels: {
                                    name: {
                                        fontSize: '14px',
                                        fontWeight: 900
                                    },
                                    value: {
                                        fontSize: '34px',
                                        fontWeight: 900,
                                        formatter: valor => Number(valor).toFixed(0) + '%'
                                    }
                                }
                            }
                        },
                        stroke: {
                            lineCap: 'round'
                        }
                    });

                    this.graficaAvance.render();
                },

                crearGraficaEstado() {
                    const elemento = document.querySelector('#graficaHorarioEstado');

                    if (!elemento) {
                        return;
                    }

                    const asignadas = Number(this.datos?.global?.celdas_asignadas || 0);
                    const pendientes = Number(this.datos?.global?.celdas_pendientes || 0);
                    const sinProfesor = Number(this.datos?.global?.sin_profesor || 0);

                    this.graficaEstado = new ApexCharts(elemento, {
                        chart: {
                            type: 'bar',
                            height: 330,
                            toolbar: this.opcionesToolbar('horario_estado_de_celdas'),
                            fontFamily: 'Inter, ui-sans-serif, system-ui'
                        },
                        series: [{
                            name: 'Cantidad',
                            data: [asignadas, pendientes, sinProfesor]
                        }],
                        xaxis: {
                            categories: ['Asignadas', 'Pendientes', 'Sin profesor'],
                            labels: {
                                style: {
                                    fontSize: '12px',
                                    fontWeight: 800
                                }
                            }
                        },
                        yaxis: {
                            min: 0,
                            forceNiceScale: true
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 10,
                                columnWidth: '46%',
                                distributed: true
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            style: {
                                fontSize: '12px',
                                fontWeight: 900
                            }
                        },
                        legend: {
                            show: false
                        },
                        grid: {
                            strokeDashArray: 4
                        },
                        tooltip: {
                            y: {
                                formatter: valor => Number(valor) + ' celda(s)'
                            }
                        }
                    });

                    this.graficaEstado.render();
                }
            }
        }
    </script>
</div>
