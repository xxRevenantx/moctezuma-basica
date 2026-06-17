<div x-data="horarioProfesorScroll()" x-init="iniciar()" x-on:pointerdown.capture="guardarScroll()"
    x-on:keydown.capture="guardarScroll()" x-on:change.capture="guardarScroll()"
    x-on:input.capture.debounce.150ms="guardarScroll()" class="space-y-6">

    <style>
        .flux-like-input {
            width: 100%;
            border-radius: 1rem;
            border: 1px solid rgb(226 232 240);
            background: rgba(255, 255, 255, .92);
            padding: .78rem 1rem;
            font-size: .875rem;
            font-weight: 700;
            color: rgb(51 65 85);
            outline: none;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            transition: all .18s ease;
        }

        .flux-like-input:focus {
            border-color: rgb(99 102 241);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .14);
        }

        .flux-like-select {
            width: 100%;
            appearance: none;
            border-radius: 1rem;
            border: 1px solid rgb(226 232 240);
            background-color: rgba(255, 255, 255, .92);
            background-image:
                linear-gradient(45deg, transparent 50%, rgb(100 116 139) 50%),
                linear-gradient(135deg, rgb(100 116 139) 50%, transparent 50%);
            background-position:
                calc(100% - 20px) calc(50% - 3px),
                calc(100% - 15px) calc(50% - 3px);
            background-size: 5px 5px, 5px 5px;
            background-repeat: no-repeat;
            padding: .78rem 2.5rem .78rem 1rem;
            font-size: .875rem;
            font-weight: 800;
            color: rgb(51 65 85);
            outline: none;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            transition: all .18s ease;
        }

        .flux-like-select:focus {
            border-color: rgb(99 102 241);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .14);
        }

        .dark .flux-like-input,
        .dark .flux-like-select {
            border-color: rgb(39 39 42);
            background-color: rgb(9 9 11);
            color: rgb(228 228 231);
        }

        .dark .flux-like-input:focus,
        .dark .flux-like-select:focus {
            border-color: rgb(129 140 248);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .22);
        }
    </style>

    {{-- Encabezado premium --}}
    <section
        class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500"></div>

        <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full bg-indigo-500/10 blur-3xl dark:bg-indigo-400/10">
        </div>

        <div
            class="absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-emerald-500/10 blur-3xl dark:bg-emerald-400/10">
        </div>

        <div class="relative p-5 sm:p-7">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="space-y-3">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/40 dark:text-indigo-300">
                        <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                        Horario docente
                    </div>

                    <div>
                        <h2 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                            Horario general del profesor
                        </h2>

                        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600 dark:text-zinc-400">
                            Consulta todas las materias del docente en una sola tabla. Filtra por nivel, materia, grado,
                            grupo o día sin que la pantalla pierda su posición.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    @if ($pdfUrl)
                        <a href="{{ $pdfUrl }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-red-600 px-4 py-3 text-sm font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-red-700 hover:shadow-md">
                            <flux:icon.document-arrow-down class="h-5 w-5" />
                            Descargar PDF
                        </a>
                        <a href="{{ $todosPdfUrl }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#006492] px-4 py-3 text-sm font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#005175] hover:shadow-md">
                            <flux:icon.document-duplicate class="h-5 w-5" />
                            Descargar todos
                        </a>
                    @else
                        <button type="button" disabled
                            class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-300 px-4 py-3 text-sm font-black text-white dark:bg-zinc-700">
                            <flux:icon.document-arrow-down class="h-5 w-5" />
                            Descargar PDF
                        </button>
                        <a href="{{ $todosPdfUrl }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#006492] px-4 py-3 text-sm font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#005175] hover:shadow-md">
                            <flux:icon.document-duplicate class="h-5 w-5" />
                            Descargar todos
                        </a>
                    @endif

                    <button type="button" wire:click="limpiarFiltros"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-900">
                        <flux:icon.arrow-path class="h-5 w-5" />
                        Limpiar filtros
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Filtros --}}
    <section
        class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

        <div
            class="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-5 py-4 dark:border-zinc-800 dark:from-zinc-950 dark:via-zinc-900 dark:to-indigo-950/20">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">
                        Filtros de consulta
                    </h3>

                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
                        Selecciona un filtro para actualizar la tabla general del horario.
                    </p>
                </div>

                <div wire:loading.flex
                    class="w-fit items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                    <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Actualizando
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-5">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-6">
                <div class="xl:col-span-2">
                    <label
                        class="mb-2 flex items-center gap-2 text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        <flux:icon.user class="h-4 w-4" />
                        Profesor
                    </label>

                    <select wire:model.live="profesorId" class="flux-like-select">
                        <option value="">Selecciona un profesor</option>

                        @foreach ($profesores as $profesor)
                            <option value="{{ $profesor->id }}">
                                {{ $profesor->nombre_completo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        class="mb-2 flex items-center gap-2 text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        <flux:icon.academic-cap class="h-4 w-4" />
                        Nivel
                    </label>

                    <select wire:model.live="nivelId" class="flux-like-select">
                        <option value="">Todos</option>

                        @foreach ($catalogos['niveles'] as $nivel)
                            <option value="{{ $nivel->id }}">
                                {{ $nivel->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        class="mb-2 flex items-center gap-2 text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        <flux:icon.book-open class="h-4 w-4" />
                        Materia
                    </label>

                    <select wire:model.live="materiaId" class="flux-like-select">
                        <option value="">Todas</option>

                        @foreach ($catalogos['materias'] as $materia)
                            <option value="{{ $materia->id }}">
                                {{ $materia->materia }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        class="mb-2 flex items-center gap-2 text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        <flux:icon.adjustments-horizontal class="h-4 w-4" />
                        Grado
                    </label>

                    <select wire:model.live="gradoId" class="flux-like-select">
                        <option value="">Todos</option>

                        @foreach ($catalogos['grados'] as $grado)
                            <option value="{{ $grado->id }}">
                                {{ $grado->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        class="mb-2 flex items-center gap-2 text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        <flux:icon.users class="h-4 w-4" />
                        Grupo
                    </label>

                    <select wire:model.live="grupoId" class="flux-like-select">
                        <option value="">Todos</option>

                        @foreach ($catalogos['grupos'] as $grupo)
                            <option value="{{ $grupo->id }}">
                                {{ $grupo->asignacionGrupo?->nombre ?? 'Grupo' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div>
                    <label
                        class="mb-2 flex items-center gap-2 text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        <flux:icon.calendar-days class="h-4 w-4" />
                        Día
                    </label>

                    <select wire:model.live="diaKey" class="flux-like-select">
                        <option value="">Todos los días</option>

                        @foreach ($catalogos['dias'] as $dia)
                            <option value="{{ $dia['key'] }}">
                                {{ $dia['nombre'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label
                        class="mb-2 flex items-center gap-2 text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        <flux:icon.magnifying-glass class="h-4 w-4" />
                        Búsqueda rápida
                    </label>

                    <div class="relative">


                        <input type="search" wire:model.live.debounce.400ms="busqueda"
                            placeholder="Buscar por materia, nivel, grado o grupo..." class="flux-like-input pl-11">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Estadísticas --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
            class="group relative overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-indigo-500/10 blur-2xl"></div>

            <div class="relative flex items-center justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        Clases
                    </p>
                    <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                        {{ $estadisticas['clases'] }}
                    </p>
                </div>

                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                    <flux:icon.clock class="h-6 w-6" />
                </div>
            </div>
        </div>

        <div
            class="group relative overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-sky-500/10 blur-2xl"></div>

            <div class="relative flex items-center justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        Materias
                    </p>
                    <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                        {{ $estadisticas['materias'] }}
                    </p>
                </div>

                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                    <flux:icon.book-open class="h-6 w-6" />
                </div>
            </div>
        </div>

        <div
            class="group relative overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-emerald-500/10 blur-2xl"></div>

            <div class="relative flex items-center justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        Niveles
                    </p>
                    <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                        {{ $estadisticas['niveles'] }}
                    </p>
                </div>

                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                    <flux:icon.academic-cap class="h-6 w-6" />
                </div>
            </div>
        </div>

        <div
            class="group relative overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-violet-500/10 blur-2xl"></div>

            <div class="relative flex items-center justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                        Grupos
                    </p>
                    <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                        {{ $estadisticas['grupos'] }}
                    </p>
                </div>

                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                    <flux:icon.users class="h-6 w-6" />
                </div>
            </div>
        </div>
    </section>

    {{-- Horario general --}}
    <section
        class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div
            class="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-indigo-50 px-5 py-4 dark:border-zinc-800 dark:from-zinc-950 dark:via-zinc-900 dark:to-indigo-950/20">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">
                        Tabla general de horario
                    </h3>

                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
                        Vista consolidada de todas las clases del profesor seleccionado.
                    </p>
                </div>

                <div
                    class="inline-flex w-fit items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    {{ $horarios->count() }} registros
                </div>
            </div>
        </div>

        @if ($horarioGeneral['dias']->isNotEmpty() && $horarioGeneral['horas']->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-zinc-950">
                            <th
                                class="sticky left-0 z-20 min-w-[115px] border-b border-r border-slate-200 bg-slate-50 px-4 py-4 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
                                Hora
                            </th>

                            @foreach ($horarioGeneral['dias'] as $dia)
                                <th
                                    class="min-w-[280px] border-b border-r border-slate-200 px-4 py-4 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:border-zinc-800 dark:text-zinc-400">
                                    {{ $dia['nombre'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($horarioGeneral['horas'] as $hora)
                            <tr class="align-top transition hover:bg-slate-50/60 dark:hover:bg-zinc-950/50">
                                <td
                                    class="sticky left-0 z-10 border-b border-r border-slate-200 bg-white px-4 py-4 text-xs font-black text-slate-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                                    <div class="rounded-2xl bg-slate-100 px-3 py-2 text-center dark:bg-zinc-950">
                                        {{ $hora['inicio'] }}
                                        <div class="text-slate-400 dark:text-zinc-500">
                                            {{ $hora['fin'] }}
                                        </div>
                                    </div>
                                </td>

                                @foreach ($horarioGeneral['dias'] as $dia)
                                    @php
                                        $celdas = $horarioGeneral['celdas'][$hora['key']][$dia['key']] ?? [];
                                    @endphp

                                    <td class="border-b border-r border-slate-200 px-3 py-3 dark:border-zinc-800">
                                        @forelse ($celdas as $horario)
                                            <div
                                                class="mb-2 rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-3 shadow-sm last:mb-0 dark:border-indigo-900/50 dark:from-indigo-950/25 dark:to-zinc-950">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div
                                                        class="text-sm font-black leading-5 text-slate-900 dark:text-white">
                                                        {{ $horario->asignacionMateria?->materia?->materia ?? 'Materia no definida' }}
                                                    </div>

                                                    @if ($horario->asignacionMateria?->materia?->receso)
                                                        <span
                                                            class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-black text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                                            Receso
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="mt-3 flex flex-wrap gap-1.5">
                                                    <span
                                                        class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-indigo-700 shadow-sm dark:bg-zinc-900 dark:text-indigo-300">
                                                        {{ $horario->nivel?->nombre ?? 'Nivel' }}
                                                    </span>

                                                    <span
                                                        class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-600 shadow-sm dark:bg-zinc-900 dark:text-zinc-300">
                                                        {{ $horario->grado?->nombre ?? 'Grado' }}
                                                    </span>

                                                    @if ($horario->grupo?->asignacionGrupo?->nombre)
                                                        <span
                                                            class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-600 shadow-sm dark:bg-zinc-900 dark:text-zinc-300">
                                                            Grupo {{ $horario->grupo->asignacionGrupo->nombre }}
                                                        </span>
                                                    @endif

                                                    @if ($horario->semestre)
                                                        <span
                                                            class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-600 shadow-sm dark:bg-zinc-900 dark:text-zinc-300">
                                                            Sem. {{ $horario->semestre->numero }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if ($horario->generacion)
                                                    <div
                                                        class="mt-3 text-xs font-semibold text-slate-500 dark:text-zinc-400">
                                                        Generación:
                                                        {{ $horario->generacion->anio_ingreso }}-{{ $horario->generacion->anio_egreso }}
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <div
                                                class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 px-3 py-5 text-center text-xs font-bold text-slate-400 dark:border-zinc-800 dark:bg-zinc-950/50 dark:text-zinc-600">
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
        @else
            <div class="py-16 text-center">
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-500 dark:bg-zinc-950 dark:text-zinc-400">
                    <flux:icon.calendar-days class="h-8 w-8" />
                </div>

                <div class="mt-4 text-lg font-black text-slate-900 dark:text-white">
                    Sin horario registrado
                </div>

                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500 dark:text-zinc-400">
                    No hay clases registradas para el profesor seleccionado o los filtros aplicados no encontraron
                    resultados.
                </p>
            </div>
        @endif
    </section>

    @script
        <script>
            Alpine.data('horarioProfesorScroll', () => ({
                llaveScroll: 'scroll_horario_profesor_general',
                restaurando: false,

                iniciar() {
                    history.scrollRestoration = 'manual';
                    this.guardarScroll();
                    this.registrarHookLivewire();
                },

                registrarHookLivewire() {
                    if (window.__scrollLockHorarioProfesorGeneral) {
                        return;
                    }

                    window.__scrollLockHorarioProfesorGeneral = true;

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

                guardarScroll() {
                    if (this.restaurando) {
                        return;
                    }

                    const y = window.scrollY ||
                        document.documentElement.scrollTop ||
                        document.body.scrollTop ||
                        0;

                    sessionStorage.setItem(this.llaveScroll, String(y));
                    window.__horarioProfesorGeneralUltimoScroll = y;
                },

                obtenerScrollGuardado() {
                    const desdeSesion = sessionStorage.getItem(this.llaveScroll);

                    if (desdeSesion !== null) {
                        const y = Number(desdeSesion);

                        if (!Number.isNaN(y)) {
                            return y;
                        }
                    }

                    if (window.__horarioProfesorGeneralUltimoScroll !== undefined) {
                        const y = Number(window.__horarioProfesorGeneralUltimoScroll);

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
