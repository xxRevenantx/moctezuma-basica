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
        <div wire:loading.flex wire:target="refrescarHorasDias,generacion_id,grado_id,grupo_id,semestre_id"
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
                        class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300">
                        Horas: {{ $horas->count() }}
                    </span>

                    <span
                        class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300">
                        Días: {{ $dias->count() }}
                    </span>

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
                            :disabled="$esBachillerato ? (!$generacion_id || !$grado_id || !$semestre_id) : (!$generacion_id || !
                                $grado_id)">
                            <option value="">Selecciona un grupo</option>
                            @foreach ($grupos as $grupo)
                                <option value="{{ $grupo->id }}">
                                    {{ $grupo->nombre }}
                                </option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <div class="flex items-end">
                        <div
                            class="w-full rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-3 text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-950/40 dark:text-slate-400">
                            @if ($generacion_id || $grado_id || $grupo_id || $semestre_id)
                                <span class="font-semibold text-slate-700 dark:text-slate-200">Filtros activos.</span>
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
                                            class="min-w-[180px] border-b border-r border-slate-200 px-4 py-4 text-center text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
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
                                            @endphp

                                            <td
                                                class="h-24 border-b border-r border-slate-200 px-3 py-3 align-top dark:border-neutral-800">
                                                <div class="space-y-2">
                                                    <flux:field>
                                                        <flux:select
                                                            wire:model.live="seleccionesHorario.{{ $claveCelda }}">
                                                            <flux:select.option value="">Selecciona una materia
                                                            </flux:select.option>

                                                            @foreach ($materiasDisponibles as $materia)
                                                                <flux:select.option value="{{ $materia->id }}">
                                                                    {{ $materia->materia }}
                                                                </flux:select.option>
                                                            @endforeach
                                                        </flux:select>
                                                    </flux:field>

                                                    @if ($horarioGuardado && $materiasDisponibles->firstWhere('id', $horarioGuardado?->asignacion_materia_id))
                                                        @php
                                                            $materiaSeleccionada = $materiasDisponibles->firstWhere(
                                                                'id',
                                                                $horarioGuardado?->asignacion_materia_id,
                                                            );

                                                            $profesor = $materiaSeleccionada?->profesor;

                                                            $nombreProfesor = $profesor
                                                                ? trim(
                                                                    ($profesor->nombre ?? '') .
                                                                        ' ' .
                                                                        ($profesor->apellido_paterno ?? '') .
                                                                        ' ' .
                                                                        ($profesor->apellido_materno ?? ''),
                                                                )
                                                                : 'Sin profesor asignado';

                                                            $estiloProfesor = $this->obtenerEstiloProfesor(
                                                                $nombreProfesor,
                                                            );
                                                        @endphp

                                                        <div class="rounded-2xl border px-3 py-2 text-center shadow-sm"
                                                            style="
                                                                background-color: {{ $estiloProfesor['background'] }};
                                                                color: {{ $estiloProfesor['color'] }};
                                                                border-color: {{ $estiloProfesor['border'] }};
                                                            ">
                                                            <p class="text-[11px] font-semibold">
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

                {{-- RESUMEN --}}
                <div
                    class="rounded-3xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <div class="rounded-2xl bg-white px-4 py-3 dark:bg-neutral-950/50">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Generación</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                @php
                                    $generacionSeleccionada = $generaciones->firstWhere('id', $generacion_id);
                                @endphp
                                {{ $generacionSeleccionada ? $generacionSeleccionada->anio_ingreso . ' - ' . $generacionSeleccionada->anio_egreso : 'No seleccionado' }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-white px-4 py-3 dark:bg-neutral-950/50">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Grado</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                {{ optional($grados->firstWhere('id', $grado_id))->nombre ?? 'No seleccionado' }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-white px-4 py-3 dark:bg-neutral-950/50">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Grupo</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                {{ optional($grupos->firstWhere('id', $grupo_id))->nombre ?? 'No seleccionado' }}
                            </p>
                        </div>

                        @if ($esBachillerato)
                            <div class="rounded-2xl bg-white px-4 py-3 dark:bg-neutral-950/50">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Semestre</p>
                                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    @php
                                        $semestreSeleccionado = $semestres->firstWhere('id', $semestre_id);
                                    @endphp
                                    {{ $semestreSeleccionado ? $semestreSeleccionado->numero . '° semestre' : 'No seleccionado' }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
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
                                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $conflicto['hora_inicio'])->format('h:i A') }}
                                            -
                                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $conflicto['hora_fin'])->format('h:i A') }}
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
</div>
