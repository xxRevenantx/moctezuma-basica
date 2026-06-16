<div class="space-y-6">
    {{-- Encabezado --}}
    <section
        class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500"></div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/40 dark:text-indigo-300">
                        <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                        Horario docente
                    </div>

                    <h2 class="mt-3 text-xl font-black tracking-tight text-slate-900 dark:text-white sm:text-2xl">
                        Consulta de horario del profesor
                    </h2>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-zinc-400">
                        Visualiza el horario por nivel académico o consulta el horario completo del docente.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    @if ($pdfUrl)
                        <a href="{{ $pdfUrl }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-red-600 px-4 py-3 text-sm font-black text-white shadow-sm transition hover:bg-red-700">
                            <flux:icon.document-arrow-down class="h-5 w-5" />
                            Descargar PDF
                        </a>
                    @else
                        <button type="button" disabled
                            class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-300 px-4 py-3 text-sm font-black text-white dark:bg-zinc-700">
                            <flux:icon.document-arrow-down class="h-5 w-5" />
                            Descargar PDF
                        </button>
                    @endif

                    <button type="button" wire:click="limpiarFiltros"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-900">
                        <flux:icon.arrow-path class="h-5 w-5" />
                        Ver completo
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Filtros --}}
    <section
        class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-5">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                    Profesor
                </label>

                <select wire:model.live="profesorId"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200 dark:focus:ring-indigo-950/50">
                    <option value="">Selecciona un profesor</option>

                    @foreach ($profesores as $profesor)
                        <option value="{{ $profesor->id }}">
                            {{ $profesor->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                    Nivel
                </label>

                <select wire:model.live="nivelId"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200 dark:focus:ring-indigo-950/50">
                    <option value="">Horario completo</option>

                    @foreach ($niveles as $nivel)
                        <option value="{{ $nivel->id }}">
                            {{ $nivel->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    {{-- Estadísticas --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                Clases
            </p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                {{ $estadisticas['clases'] }}
            </p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                Materias
            </p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                {{ $estadisticas['materias'] }}
            </p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                Niveles
            </p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                {{ $estadisticas['niveles'] }}
            </p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                Grupos
            </p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                {{ $estadisticas['grupos'] }}
            </p>
        </div>
    </section>

    {{-- Información del profesor --}}
    @if ($profesorSeleccionado)
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-lg font-black text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                        {{ mb_substr($profesorSeleccionado->nombre, 0, 1) }}{{ mb_substr($profesorSeleccionado->apellido_paterno, 0, 1) }}
                    </div>

                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">
                            {{ $profesorSeleccionado->nombre_completo }}
                        </h3>

                        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
                            {{ $profesorSeleccionado->correo ?: 'Sin correo registrado' }}
                            @if ($profesorSeleccionado->telefono_movil)
                                · {{ $profesorSeleccionado->telefono_movil }}
                            @endif
                        </p>
                    </div>
                </div>

                <div
                    class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-zinc-950 dark:text-zinc-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    {{ $nivelId ? 'Vista por nivel' : 'Vista completa' }}
                </div>
            </div>
        </section>
    @endif

    {{-- Horario --}}
    @forelse ($matriz as $bloque)
        <section
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div
                class="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-5 py-4 dark:border-zinc-800 dark:from-zinc-950 dark:via-zinc-900 dark:to-indigo-950/20">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">
                            {{ $bloque['nivel']->nombre ?? 'Nivel no definido' }}
                        </h3>

                        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
                            {{ $bloque['total_clases'] }} clases ·
                            {{ $bloque['total_materias'] }} materias ·
                            {{ $bloque['total_grupos'] }} grupos
                        </p>
                    </div>

                    @if (!empty($bloque['nivel']->cct))
                        <span
                            class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-600 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300">
                            C.C.T. {{ $bloque['nivel']->cct }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-zinc-950">
                            <th
                                class="sticky left-0 z-10 border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
                                Hora
                            </th>

                            @foreach ($bloque['dias'] as $dia)
                                <th
                                    class="min-w-[230px] border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:border-zinc-800 dark:text-zinc-400">
                                    {{ $dia->dia }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($bloque['horas'] as $hora)
                            <tr class="align-top">
                                <td
                                    class="sticky left-0 z-10 border-b border-r border-slate-200 bg-white px-4 py-4 text-xs font-black text-slate-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                                    {{ \Carbon\Carbon::parse($hora->hora_inicio)->format('H:i') }}
                                    -
                                    {{ \Carbon\Carbon::parse($hora->hora_fin)->format('H:i') }}
                                </td>

                                @foreach ($bloque['dias'] as $dia)
                                    @php
                                        $celdas = $bloque['celdas'][$hora->id][$dia->id] ?? [];
                                    @endphp

                                    <td class="border-b border-r border-slate-200 px-3 py-3 dark:border-zinc-800">
                                        @forelse ($celdas as $horario)
                                            <div
                                                class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-3 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                                                <p class="text-sm font-black text-slate-900 dark:text-white">
                                                    {{ $horario->asignacionMateria?->materia?->materia ?? 'Materia no definida' }}
                                                </p>

                                                <p class="mt-1 text-xs font-bold text-indigo-700 dark:text-indigo-300">
                                                    {{ $horario->grado?->nombre ?? 'Grado' }}
                                                    @if ($horario->grupo?->asignacionGrupo?->nombre)
                                                        · Grupo {{ $horario->grupo->asignacionGrupo->nombre }}
                                                    @endif
                                                </p>

                                                @if ($horario->generacion)
                                                    <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                                                        Generación:
                                                        {{ $horario->generacion->anio_ingreso }}-{{ $horario->generacion->anio_egreso }}
                                                    </p>
                                                @endif

                                                @if ($horario->semestre)
                                                    <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                                                        Semestre {{ $horario->semestre->numero }}
                                                    </p>
                                                @endif
                                            </div>
                                        @empty
                                            <div
                                                class="rounded-2xl border border-dashed border-slate-200 px-3 py-4 text-center text-xs font-bold text-slate-400 dark:border-zinc-800 dark:text-zinc-600">
                                                Libre
                                            </div>
                                        @endforelse
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @empty
            <section
                class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-500 dark:bg-zinc-950 dark:text-zinc-400">
                    <flux:icon.calendar-days class="h-8 w-8" />
                </div>

                <h3 class="mt-4 text-lg font-black text-slate-900 dark:text-white">
                    Sin horario registrado
                </h3>

                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500 dark:text-zinc-400">
                    El profesor seleccionado todavía no tiene clases asignadas en horarios o no coincide con el nivel
                    filtrado.
                </p>
            </section>
        @endforelse
    </div>
