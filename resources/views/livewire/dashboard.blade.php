<div class="space-y-6">

    {{-- Encabezado principal del dashboard --}}
    <section
        class="relative overflow-hidden rounded-3xl border border-sky-100/70 bg-gradient-to-br from-sky-50 via-white to-fuchsia-50 shadow-sm dark:border-sky-900/40 dark:from-slate-950 dark:via-[#0b1220] dark:to-[#1a1033]">

        {{-- Fondos decorativos --}}
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-16 -left-10 h-56 w-56 rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10">
            </div>
            <div class="absolute top-0 right-0 h-64 w-64 rounded-full bg-fuchsia-300/20 blur-3xl dark:bg-fuchsia-500/10">
            </div>
            <div class="absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-blue-300/20 blur-3xl dark:bg-blue-500/10">
            </div>
        </div>

        <div class="relative h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-fuchsia-500"></div>

        <div class="relative p-5 sm:p-6 lg:p-7">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

                <div class="space-y-3">
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 text-xs font-medium text-sky-700 shadow-sm ring-1 ring-white/60 backdrop-blur-sm dark:bg-white/5 dark:text-sky-300 dark:ring-white/10">
                        <flux:icon.sparkles class="h-4 w-4" />
                        Panel administrativo
                    </div>

                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white sm:text-3xl">
                            Dashboard — Moctezuma Básica
                        </h1>

                        <p class="max-w-2xl text-sm text-neutral-600 dark:text-neutral-300 sm:text-base">
                            Consulta alumnos, docentes, grupos, horarios, periodos, calificaciones y alertas importantes
                            desde un solo lugar.
                        </p>
                    </div>
                </div>

                {{-- Filtro --}}
                <div class="w-full lg:max-w-sm">
                    <div
                        class="rounded-3xl border border-white/60 bg-white/70 p-5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
                        <label
                            class="mb-2 block text-[11px] font-medium uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                            Nivel educativo
                        </label>

                        <select wire:model.live="nivel_id"
                            class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm font-semibold text-neutral-700 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-200">
                            <option value="">Todos los niveles</option>

                            @foreach ($niveles as $nivel)
                                <option value="{{ $nivel['id'] }}">
                                    {{ $nivel['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Loader --}}
    <div wire:loading.flex wire:target="nivel_id"
        class="fixed inset-0 z-50 items-center justify-center bg-slate-950/30 backdrop-blur-sm">
        <div class="rounded-3xl border border-white/20 bg-white px-6 py-5 shadow-2xl dark:bg-slate-900">
            <div class="flex items-center gap-3">
                <div class="h-5 w-5 animate-spin rounded-full border-2 border-sky-500 border-t-transparent"></div>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Actualizando dashboard...
                </span>
            </div>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <article
            class="relative min-h-[150px] overflow-hidden rounded-[20px] bg-gradient-to-br from-sky-500 to-blue-600 p-5 text-white shadow-sm">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute right-8 top-10 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative z-10 flex h-full flex-col justify-between">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[13px] font-medium text-white/80">Alumnos activos</p>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10">
                        <flux:icon.users class="h-5 w-5" />
                    </div>
                </div>

                <h2 class="mt-4 text-3xl font-bold tracking-tight">
                    {{ number_format($resumen['alumnos'] ?? 0) }}
                </h2>

                <p class="mt-4 text-xs font-medium text-white/80">
                    Total de alumnos inscritos activos.
                </p>
            </div>
        </article>

        <article
            class="relative min-h-[150px] overflow-hidden rounded-[20px] bg-gradient-to-br from-violet-500 to-fuchsia-600 p-5 text-white shadow-sm">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -bottom-8 left-16 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="relative z-10 flex h-full flex-col justify-between">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[13px] font-medium text-white/80">Docentes activos</p>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10">
                        <flux:icon.user-circle class="h-5 w-5" />
                    </div>
                </div>

                <h2 class="mt-4 text-3xl font-bold tracking-tight">
                    {{ number_format($resumen['docentes'] ?? 0) }}
                </h2>

                <p class="mt-4 text-xs font-medium text-white/80">
                    Personal docente registrado.
                </p>
            </div>
        </article>

        <article
            class="relative min-h-[150px] overflow-hidden rounded-[20px] bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white shadow-sm">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute right-8 top-10 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative z-10 flex h-full flex-col justify-between">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[13px] font-medium text-white/80">Grupos registrados</p>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10">
                        <flux:icon.academic-cap class="h-5 w-5" />
                    </div>
                </div>

                <h2 class="mt-4 text-3xl font-bold tracking-tight">
                    {{ number_format($resumen['grupos'] ?? 0) }}
                </h2>

                <p class="mt-4 text-xs font-medium text-white/80">
                    Grupos creados en el sistema.
                </p>
            </div>
        </article>

        <article
            class="relative min-h-[150px] overflow-hidden rounded-[20px] bg-gradient-to-br from-amber-500 to-orange-600 p-5 text-white shadow-sm">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -bottom-8 left-16 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="relative z-10 flex h-full flex-col justify-between">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[13px] font-medium text-white/80">Materias asignadas</p>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10">
                        <flux:icon.book-open class="h-5 w-5" />
                    </div>
                </div>

                <h2 class="mt-4 text-3xl font-bold tracking-tight">
                    {{ number_format($resumen['materias'] ?? 0) }}
                </h2>

                <p class="mt-4 text-xs font-medium text-white/80">
                    Materias configuradas por nivel y grupo.
                </p>
            </div>
        </article>

    </section>

    {{-- Indicadores secundarios --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <article
            class="rounded-3xl border border-neutral-200/70 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400">
                    <flux:icon.calendar-days class="h-5 w-5" />
                </div>

                <div>
                    <p
                        class="text-[11px] font-medium uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                        Horarios
                    </p>
                    <p class="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                        {{ number_format($resumen['horarios'] ?? 0) }}
                    </p>
                </div>
            </div>
        </article>

        <article
            class="rounded-3xl border border-neutral-200/70 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                    <flux:icon.clock class="h-5 w-5" />
                </div>

                <div>
                    <p
                        class="text-[11px] font-medium uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                        Periodos
                    </p>
                    <p class="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                        {{ number_format($resumen['periodos'] ?? 0) }}
                    </p>
                </div>
            </div>
        </article>

        <article
            class="rounded-3xl border border-neutral-200/70 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <flux:icon.clipboard-document-check class="h-5 w-5" />
                </div>

                <div>
                    <p
                        class="text-[11px] font-medium uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                        Calificaciones
                    </p>
                    <p class="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                        {{ number_format($resumen['calificaciones'] ?? 0) }}
                    </p>
                </div>
            </div>
        </article>

        <article
            class="rounded-3xl border border-neutral-200/70 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p
                        class="text-[11px] font-medium uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                        Avance
                    </p>
                    <p class="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                        {{ $resumen['avance_calificaciones'] ?? 0 }}%
                    </p>
                </div>

                <span
                    class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-700 dark:bg-sky-500/10 dark:text-sky-300">
                    Captura
                </span>
            </div>

            <div class="mt-4 h-3 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800">
                <div class="h-full rounded-full bg-gradient-to-r from-sky-500 via-blue-600 to-fuchsia-500"
                    style="width: {{ $resumen['avance_calificaciones'] ?? 0 }}%">
                </div>
            </div>
        </article>

    </section>

    {{-- Resumen por nivel --}}
    <section
        class="rounded-3xl border border-neutral-200/70 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
            <h3 class="text-base font-semibold text-neutral-900 dark:text-white">
                Resumen por nivel educativo
            </h3>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Indicadores generales de preescolar, primaria, secundaria y bachillerato.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
            @forelse ($resumenNiveles as $nivel)
                <article
                    class="relative overflow-hidden rounded-3xl border border-neutral-200 bg-neutral-50 p-5 dark:border-neutral-800 dark:bg-neutral-950">
                    <div class="absolute inset-y-0 right-0 w-32 bg-sky-100/40 blur-2xl dark:bg-sky-500/10"></div>

                    <div class="relative">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h4 class="text-base font-bold text-neutral-900 dark:text-white">
                                {{ $nivel['nombre'] }}
                            </h4>

                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400">
                                <flux:icon.academic-cap class="h-5 w-5" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="rounded-2xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">
                                <p class="text-[11px] text-neutral-500 dark:text-neutral-400">Alumnos</p>
                                <p class="mt-1 text-xl font-bold text-neutral-900 dark:text-white">
                                    {{ $nivel['alumnos'] }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">
                                <p class="text-[11px] text-neutral-500 dark:text-neutral-400">Grupos</p>
                                <p class="mt-1 text-xl font-bold text-neutral-900 dark:text-white">
                                    {{ $nivel['grupos'] }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">
                                <p class="text-[11px] text-neutral-500 dark:text-neutral-400">Materias</p>
                                <p class="mt-1 text-xl font-bold text-neutral-900 dark:text-white">
                                    {{ $nivel['materias'] }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">
                                <p class="text-[11px] text-neutral-500 dark:text-neutral-400">Horarios</p>
                                <p class="mt-1 text-xl font-bold text-neutral-900 dark:text-white">
                                    {{ $nivel['horarios'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div
                    class="col-span-full rounded-2xl border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-700">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">
                        No hay niveles registrados.
                    </p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Alertas --}}
    <section
        class="rounded-3xl border border-neutral-200/70 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
            <h3 class="text-base font-semibold text-neutral-900 dark:text-white">
                Alertas administrativas
            </h3>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Pendientes importantes detectados en el sistema.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-5">
            @foreach ($alertas as $alerta)
                <article
                    class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
                    <div
                        class="mb-4 inline-flex rounded-2xl bg-gradient-to-r {{ $alerta['color'] }} px-3 py-1 text-xs font-bold text-white">
                        {{ $alerta['cantidad'] }}
                    </div>

                    <h4 class="text-sm font-bold text-neutral-900 dark:text-white">
                        {{ $alerta['titulo'] }}
                    </h4>

                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        {{ $alerta['descripcion'] }}
                    </p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Tablas rápidas --}}
    <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- Periodos próximos --}}
        <article
            class="rounded-3xl border border-neutral-200/70 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
                <h3 class="text-base font-semibold text-neutral-900 dark:text-white">
                    Periodos próximos
                </h3>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    Fechas cercanas de cierre.
                </p>
            </div>

            <div class="space-y-3 p-5">
                @forelse ($periodosProximos as $periodo)
                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950">
                        <p class="text-sm font-semibold text-neutral-900 dark:text-white">
                            {{ $periodo['nivel'] }}
                        </p>

                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Del {{ \Carbon\Carbon::parse($periodo['fecha_inicio'])->format('d/m/Y') }}
                            al {{ \Carbon\Carbon::parse($periodo['fecha_fin'])->format('d/m/Y') }}
                        </p>
                    </div>
                @empty
                    <div
                        class="rounded-2xl border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-700">
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            No hay periodos próximos.
                        </p>
                    </div>
                @endforelse
            </div>
        </article>

        {{-- Documentos pendientes --}}
        <article
            class="rounded-3xl border border-neutral-200/70 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
                <h3 class="text-base font-semibold text-neutral-900 dark:text-white">
                    Documentos pendientes
                </h3>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    Alumnos con expediente incompleto.
                </p>
            </div>

            <div class="space-y-3 p-5">
                @forelse ($alumnosDocumentosPendientes as $alumno)
                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950">
                        <p class="text-sm font-semibold text-neutral-900 dark:text-white">
                            {{ $alumno['nombre'] }}
                        </p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">
                                {{ $alumno['nivel'] }}
                            </span>

                            <span
                                class="rounded-full bg-fuchsia-100 px-2.5 py-1 text-[11px] font-semibold text-fuchsia-700 dark:bg-fuchsia-500/10 dark:text-fuchsia-400">
                                {{ $alumno['grupo'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-2xl border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-700">
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            No hay documentos pendientes.
                        </p>
                    </div>
                @endforelse
            </div>
        </article>

        {{-- Grupos sin horario --}}
        <article
            class="rounded-3xl border border-neutral-200/70 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
                <h3 class="text-base font-semibold text-neutral-900 dark:text-white">
                    Grupos sin horario
                </h3>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    Grupos pendientes de configuración.
                </p>
            </div>

            <div class="space-y-3 p-5">
                @forelse ($gruposSinHorario as $grupo)
                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950">
                        <p class="text-sm font-semibold text-neutral-900 dark:text-white">
                            {{ $grupo['grupo'] }}
                        </p>

                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            {{ $grupo['nivel'] }}
                        </p>
                    </div>
                @empty
                    <div
                        class="rounded-2xl border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-700">
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            Todos los grupos tienen horario.
                        </p>
                    </div>
                @endforelse
            </div>
        </article>

    </section>

    {{-- Accesos rápidos --}}
    <section
        class="rounded-3xl border border-neutral-200/70 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
            <h3 class="text-base font-semibold text-neutral-900 dark:text-white">
                Accesos rápidos
            </h3>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Enlaces frecuentes del panel administrativo.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $accesos = [
                    [
                        'titulo' => 'Registrar alumno',
                        'descripcion' => 'Crear una nueva inscripción.',
                        'icono' => 'users',
                        'clase' => 'from-sky-500 to-blue-600',
                        'url' => '#',
                    ],
                    [
                        'titulo' => 'Crear grupo',
                        'descripcion' => 'Administrar grupos escolares.',
                        'icono' => 'academic-cap',
                        'clase' => 'from-emerald-500 to-teal-600',
                        'url' => '#',
                    ],
                    [
                        'titulo' => 'Crear horario',
                        'descripcion' => 'Configurar bloques de clase.',
                        'icono' => 'calendar-days',
                        'clase' => 'from-violet-500 to-fuchsia-600',
                        'url' => '#',
                    ],
                    [
                        'titulo' => 'Calificaciones',
                        'descripcion' => 'Capturar y consultar notas.',
                        'icono' => 'clipboard',
                        'clase' => 'from-amber-500 to-orange-600',
                        'url' => '#',
                    ],
                ];
            @endphp

            @foreach ($accesos as $item)
                <a href="{{ $item['url'] }}"
                    class="group block rounded-2xl border border-neutral-200 bg-neutral-50 p-4 transition hover:-translate-y-0.5 hover:shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br {{ $item['clase'] }} text-white shadow-sm">
                            @switch($item['icono'])
                                @case('users')
                                    <flux:icon.users class="h-5 w-5" />
                                @break

                                @case('academic-cap')
                                    <flux:icon.academic-cap class="h-5 w-5" />
                                @break

                                @case('calendar-days')
                                    <flux:icon.calendar-days class="h-5 w-5" />
                                @break

                                @default
                                    <flux:icon.clipboard-document-check class="h-5 w-5" />
                            @endswitch
                        </div>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-neutral-900 dark:text-white">
                                {{ $item['titulo'] }}
                            </p>

                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $item['descripcion'] }}
                            </p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Gráfica con ApexCharts --}}
    <section
        class="rounded-3xl border border-neutral-200/70 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
            <h3 class="text-base font-semibold text-neutral-900 dark:text-white">
                Alumnos por nivel
            </h3>

            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Distribución de alumnos activos por nivel educativo.
            </p>
        </div>

        <div class="p-5">
            <div wire:ignore id="graficaAlumnosNivel"
                class="min-h-[340px] rounded-2xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        document.addEventListener('livewire:init', () => {
            let graficaAlumnosNivel = null;

            const crearGraficaAlumnosNivel = (data) => {
                const contenedor = document.querySelector("#graficaAlumnosNivel");

                if (!contenedor) {
                    return;
                }

                if (graficaAlumnosNivel) {
                    graficaAlumnosNivel.destroy();
                }

                graficaAlumnosNivel = new ApexCharts(contenedor, {
                    chart: {
                        type: 'bar',
                        height: 320,
                        toolbar: {
                            show: false
                        },
                        fontFamily: 'Inter, ui-sans-serif, system-ui'
                    },
                    series: [{
                        name: 'Alumnos',
                        data: data.series ?? []
                    }],
                    xaxis: {
                        categories: data.labels ?? [],
                        labels: {
                            style: {
                                fontSize: '12px',
                                fontWeight: 600
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(value) {
                                return Math.floor(value);
                            }
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 10,
                            columnWidth: '45%',
                            distributed: true
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '12px',
                            fontWeight: 700
                        }
                    },
                    legend: {
                        show: false
                    },
                    grid: {
                        strokeDashArray: 5
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) {
                                return value + ' alumnos';
                            }
                        }
                    },
                    noData: {
                        text: 'Sin información para mostrar'
                    }
                });

                graficaAlumnosNivel.render();
            };

            crearGraficaAlumnosNivel(@json($graficaAlumnosNivel));

            Livewire.on('actualizarGraficaAlumnosNivel', (event) => {
                crearGraficaAlumnosNivel(event.data);
            });
        });
    </script>
</div>
