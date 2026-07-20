<div x-data="{
    openRow: null,
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `El grupo ${nombre} se eliminará de forma permanente`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminar', id))
    }
}" class="space-y-5">

    {{-- Encabezado --}}
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Grupos
        </h1>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            Busca, filtra, edita o elimina grupos por nivel, generación, grado o semestre.
        </p>
    </div>

    {{-- Contenedor listado --}}
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white/90 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/90">

        {{-- Acabado superior --}}
        <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-sky-400 to-indigo-600"></div>

        {{-- Toolbar --}}
        <div
            class="border-b border-gray-100/80 bg-gradient-to-r from-slate-50 via-white to-sky-50 p-4 dark:border-neutral-800/80 dark:from-neutral-900 dark:via-neutral-950 dark:to-sky-950/20 sm:p-5 lg:p-6">

            <div class="grid gap-4 xl:grid-cols-[1.2fr_2fr_auto] xl:items-end">

                {{-- Buscador --}}
                <div>
                    <label for="buscar-grupo"
                        class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Buscar grupo
                    </label>

                    <flux:input id="buscar-grupo" type="text" wire:model.live="search"
                        placeholder="Buscar por nombre del grupo..." icon="magnifying-glass" class="w-full" />
                </div>

                {{-- Filtros --}}
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">

                    {{-- Ciclo escolar --}}
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Ciclo escolar
                        </label>

                        <flux:select wire:model.live="ciclo_escolar_id" placeholder="Todos los ciclos">
                            <flux:select.option value="">
                                Todos los ciclos
                            </flux:select.option>

                            @foreach ($ciclosEscolares as $cicloEscolar)
                                <flux:select.option value="{{ $cicloEscolar->id }}">
                                    {{ $cicloEscolar->inicio_anio }} - {{ $cicloEscolar->fin_anio }}
                                    @if ($cicloEscolar->es_actual)
                                        · Actual
                                    @endif
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Nivel --}}
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Nivel
                        </label>

                        <flux:select wire:model.live="nivel_id" placeholder="Todos los niveles">
                            <flux:select.option value="">
                                Todos los niveles
                            </flux:select.option>

                            @foreach ($niveles as $nivel)
                                <flux:select.option value="{{ $nivel->id }}">
                                    {{ $nivel->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Generación --}}
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Generación
                        </label>

                        <flux:select wire:model.live="generacion_id" placeholder="Todas las generaciones"
                            :disabled="!$nivel_id">
                            <flux:select.option value="">
                                Todas las generaciones
                            </flux:select.option>

                            @foreach ($generaciones as $generacion)
                                <flux:select.option value="{{ $generacion->id }}">
                                    {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                    @if ((int) $generacion->status === 0)
                                        / Inactiva
                                    @endif
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Grado --}}
                    @if (!$esBachillerato)
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Grado
                            </label>

                            <flux:select wire:model.live="grado_id" placeholder="Todos los grados"
                                :disabled="!$nivel_id">
                                <flux:select.option value="">
                                    Todos los grados
                                </flux:select.option>

                                @foreach ($grados as $grado)
                                    <flux:select.option value="{{ $grado->id }}">
                                        {{ $grado->nombre }}°
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif

                    {{-- Semestre para bachillerato --}}
                    @if ($esBachillerato)
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Semestre
                            </label>

                            <flux:select wire:model.live="semestre_id" placeholder="Todos los semestres"
                                :disabled="!$nivel_id">
                                <flux:select.option value="">
                                    Todos los semestres
                                </flux:select.option>

                                @foreach ($semestres as $semestre)
                                    <flux:select.option value="{{ $semestre->id }}">
                                        {{ $semestre->numero }}° semestre
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif

                    {{-- Limpieza --}}
                    <div class="flex items-end">
                        <flux:button type="button" variant="ghost" wire:click="limpiarFiltros"
                            class="w-full cursor-pointer border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-gray-200 dark:hover:bg-neutral-800">

                            <span class="flex items-center justify-center gap-2">
                                <flux:icon.x-mark class="h-4 w-4" />
                                Limpiar
                            </span>
                        </flux:button>
                    </div>

                </div>
            </div>

            {{-- Resumen --}}
            <div class="flex items-center justify-between gap-3 xl:justify-end mt-3">
                <div
                    class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white/70 px-3 py-2 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>

                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                        Resultados:
                        <strong>{{ $totalGrupos }}</strong>
                    </span>
                </div>
            </div>
        </div>

        {{-- Filtros activos --}}
        <div class="mt-4 flex flex-wrap gap-2">
            @if ($search)
                <span
                    class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950/40 dark:text-blue-200 dark:ring-blue-900/60">
                    Búsqueda: {{ $search }}
                </span>
            @endif

            @if ($ciclo_escolar_id)
                @php
                    $cicloActivo = $ciclosEscolares->firstWhere('id', $ciclo_escolar_id);
                @endphp

                <span
                    class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-900/60">
                    Ciclo: {{ $cicloActivo?->inicio_anio }} - {{ $cicloActivo?->fin_anio }}
                </span>
            @endif

            @if ($nivel_id)
                @php
                    $nivelActivo = $niveles->firstWhere('id', $nivel_id);
                @endphp

                <span
                    class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-200 dark:ring-indigo-900/60">
                    Nivel: {{ $nivelActivo?->nombre ?? 'Seleccionado' }}
                </span>
            @endif

            @if ($generacion_id)
                @php
                    $generacionActiva = $generaciones->firstWhere('id', $generacion_id);
                @endphp

                <span
                    class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/40 dark:text-sky-200 dark:ring-sky-900/60">
                    Generación:
                    {{ $generacionActiva?->anio_ingreso }} - {{ $generacionActiva?->anio_egreso }}
                </span>
            @endif

            @if ($grado_id && !$esBachillerato)
                @php
                    $gradoActivo = $grados->firstWhere('id', $grado_id);
                @endphp

                <span
                    class="inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-100 dark:bg-violet-950/40 dark:text-violet-200 dark:ring-violet-900/60">
                    Grado: {{ $gradoActivo?->nombre }}°
                </span>
            @endif

            @if ($semestre_id && $esBachillerato)
                @php
                    $semestreActivo = $semestres->firstWhere('id', $semestre_id);
                @endphp

                <span
                    class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-900/60">
                    Semestre: {{ $semestreActivo?->numero }}°
                </span>
            @endif
        </div>
    </div>

    {{-- Área de resultados --}}
    <div class="px-4 pb-4 pt-3 sm:px-5 sm:pb-6 lg:px-6">
        <div class="relative">

            {{-- Loader --}}
            <div wire:loading.delay
                wire:target="search,ciclo_escolar_id,nivel_id,generacion_id,grado_id,semestre_id,limpiarFiltros,eliminar"
                class="absolute inset-0 z-10 grid place-items-center rounded-2xl bg-white/70 backdrop-blur dark:bg-neutral-900/70"
                aria-live="polite" aria-busy="true">
                <div
                    class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow ring-1 ring-gray-200 dark:bg-neutral-900 dark:ring-neutral-800">
                    <svg class="h-5 w-5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24"
                        fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>

                    <span class="text-sm text-gray-700 dark:text-gray-200">
                        Cargando…
                    </span>
                </div>
            </div>

            {{-- Contenido --}}
            <div class="transition filter duration-200" wire:loading.class="blur-sm"
                wire:target="search,ciclo_escolar_id,nivel_id,generacion_id,grado_id,semestre_id,limpiarFiltros,eliminar">

                @if ($groupedByNivel->isEmpty())
                    {{-- Estado vacío --}}
                    <div
                        class="mt-4 rounded-2xl border border-dashed border-gray-300 bg-white/80 p-8 text-center dark:border-neutral-700 dark:bg-neutral-900/80">
                        <div
                            class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-950">
                            <flux:icon.search class="h-5 w-5 text-indigo-600 dark:text-indigo-300" />
                        </div>

                        <div class="mb-1 text-base font-semibold text-gray-800 dark:text-gray-100">
                            No hay grupos disponibles
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Ajusta tu búsqueda o modifica los filtros.
                        </p>
                    </div>
                @else
                    {{-- Cards resumen --}}
                    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">

                        {{-- Total grupos --}}
                        <div
                            class="relative overflow-hidden rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-600 via-sky-500 to-blue-600 text-white shadow-sm dark:border-indigo-900/60">
                            <div
                                class="absolute inset-0 bg-[radial-gradient(circle_at_top,_#ffffff_0,_transparent_55%)] opacity-25">
                            </div>

                            <div class="relative flex items-center justify-between gap-3 p-4 sm:p-5">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-indigo-100/90">
                                        Total de grupos
                                    </p>

                                    <p class="mt-1 text-2xl font-bold leading-tight">
                                        {{ $totalGrupos }}
                                    </p>

                                    <p class="mt-1 text-[11px] text-indigo-100/90">
                                        Coinciden con el filtro actual.
                                    </p>
                                </div>

                                <div
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/15 backdrop-blur-sm">
                                    <flux:icon.layout-grid class="h-5 w-5" />
                                </div>
                            </div>
                        </div>

                        {{-- Niveles distintos --}}
                        <div
                            class="relative overflow-hidden rounded-xl border border-emerald-100 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-500 text-white shadow-sm dark:border-emerald-900/60">
                            <div
                                class="absolute inset-0 bg-[radial-gradient(circle_at_top,_#ffffff_0,_transparent_55%)] opacity-25">
                            </div>

                            <div class="relative flex items-center justify-between gap-3 p-4 sm:p-5">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-emerald-50/90">
                                        Niveles encontrados
                                    </p>

                                    <p class="mt-1 text-2xl font-bold leading-tight">
                                        {{ $totalNiveles }}
                                    </p>

                                    <p class="mt-1 text-[11px] text-emerald-50/90">
                                        Niveles con al menos un grupo.
                                    </p>
                                </div>

                                <div
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/15 backdrop-blur-sm">
                                    <flux:icon.layers class="h-5 w-5" />
                                </div>
                            </div>
                        </div>

                        {{-- Grupos sin nivel --}}
                        <div
                            class="relative overflow-hidden rounded-xl border border-amber-100 bg-gradient-to-r from-amber-500 via-orange-400 to-rose-500 text-white shadow-sm dark:border-amber-900/60">
                            <div
                                class="absolute inset-0 bg-[radial-gradient(circle_at_top,_#ffffff_0,_transparent_55%)] opacity-25">
                            </div>

                            <div class="relative flex items-center justify-between gap-3 p-4 sm:p-5">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-amber-50/90">
                                        Grupos sin nivel
                                    </p>

                                    <p class="mt-1 text-2xl font-bold leading-tight">
                                        {{ $gruposSinNivel }}
                                    </p>

                                    <p class="mt-1 text-[11px] text-amber-50/90">
                                        Revisa su asignación de nivel.
                                    </p>
                                </div>

                                <div
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/15 backdrop-blur-sm">
                                    <flux:icon.triangle-alert class="h-5 w-5" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla --}}
                    <div
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-700 dark:bg-neutral-900 dark:text-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Grupo
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Grado
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Semestre
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Generación
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Ciclo
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Nivel
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">
                                    @foreach ($groupedByNivel as $nivelNombre => $items)
                                        @php
                                            $groupedByGeneracion = $items->groupBy(function ($g) {
                                                if ($g->generacion) {
                                                    return $g->generacion->anio_ingreso .
                                                        ' - ' .
                                                        $g->generacion->anio_egreso .
                                                        '||' .
                                                        (int) $g->generacion->status;
                                                }

                                                return 'Sin generación asignada||1';
                                            });

                                            $nivelCount = $items->count();
                                            $nivelLabel = $nivelNombre ?: 'Sin nivel asignado';
                                        @endphp

                                        {{-- Header nivel --}}
                                        <tr class="bg-slate-50/80 dark:bg-neutral-900/70">
                                            <td colspan="7" class="px-4 py-3">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-200 dark:ring-indigo-900/60">
                                                            <span
                                                                class="h-2 w-2 rounded-full bg-indigo-500 dark:bg-indigo-300"></span>
                                                            {{ $nivelLabel }}
                                                        </span>

                                                        <span
                                                            class="text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                                            {{ $nivelCount }} grupos
                                                        </span>
                                                    </div>

                                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                                        Agrupado por generación
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>

                                        @foreach ($groupedByGeneracion as $genKey => $gruposGen)
                                            @php
                                                [$genNombre, $genStatus] = array_pad(explode('||', $genKey), 2, '1');

                                                $genStatus = (int) $genStatus;
                                                $genIsInactive = $genStatus === 0;
                                            @endphp

                                            {{-- Subheader generación --}}
                                            <tr class="bg-white dark:bg-neutral-950">
                                                <td colspan="7" class="px-4 py-2">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold ring-1
                                                                    {{ $genIsInactive
                                                                        ? 'bg-rose-50 text-rose-800 ring-rose-100 dark:bg-rose-950/40 dark:text-rose-200 dark:ring-rose-900/60'
                                                                        : 'bg-sky-50 text-sky-800 ring-sky-100 dark:bg-sky-900/50 dark:text-sky-100 dark:ring-sky-800' }}">
                                                                <flux:icon.calendar-clock class="h-3.5 w-3.5" />

                                                                {{ $genNombre }}

                                                                @if ($genIsInactive)
                                                                    <span
                                                                        class="ml-1 inline-flex items-center rounded-full bg-rose-600/10 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:text-rose-200">
                                                                        INACTIVA
                                                                    </span>
                                                                @endif
                                                            </span>

                                                            <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                                                {{ $gruposGen->count() }} grupos
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                            @foreach ($gruposGen as $grupo)
                                                @php
                                                    $rowInactive = optional($grupo->generacion)->status === 0;

                                                    $rowGenLabel =
                                                        optional($grupo->generacion)->anio_ingreso &&
                                                        optional($grupo->generacion)->anio_egreso
                                                            ? optional($grupo->generacion)->anio_ingreso .
                                                                ' - ' .
                                                                optional($grupo->generacion)->anio_egreso
                                                            : 'Sin generación asignada';

                                                    $gradoNombre = $grupo->grado?->nombre;

                                                    $nombreGrupo = $grupo->asignacionGrupo?->nombre ?? '---';
                                                @endphp

                                                <tr
                                                    class="
                                                            transition-colors
                                                            {{ $rowInactive ? 'bg-rose-50/80 dark:bg-rose-950/20' : 'hover:bg-slate-50/70 dark:hover:bg-neutral-900/60' }}
                                                            {{ $rowInactive ? 'hover:bg-rose-100/80 dark:hover:bg-rose-950/30' : '' }}
                                                        ">

                                                    <td class="px-4 py-3">
                                                        <div
                                                            class="font-semibold {{ $rowInactive ? 'text-rose-700 dark:text-rose-300' : 'text-gray-900 dark:text-white' }}">
                                                            {{ $nombreGrupo }}
                                                        </div>
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        <span
                                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1
                                                                {{ $rowInactive
                                                                    ? 'bg-rose-50 text-rose-800 ring-rose-100 dark:bg-rose-950/40 dark:text-rose-200 dark:ring-rose-900/60'
                                                                    : 'bg-indigo-50 text-indigo-700 ring-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-200 dark:ring-indigo-900/60' }}">
                                                            {{ $gradoNombre ? $gradoNombre . '°' : 'Sin grado' }}
                                                        </span>
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        @if ($grupo->semestre)
                                                            <span
                                                                class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-900/60">
                                                                {{ $grupo->semestre->numero }}° semestre
                                                            </span>
                                                        @else
                                                            <span class="text-gray-500 dark:text-gray-400">
                                                                —
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        <span
                                                            class="font-medium {{ $rowInactive ? 'text-rose-700 dark:text-rose-300' : 'text-gray-700 dark:text-gray-200' }}">
                                                            {{ $rowGenLabel }}
                                                        </span>
                                                        @if ($grupo->motivo_generacion_excepcional)
                                                            <span class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black uppercase text-amber-700 dark:bg-amber-950/50 dark:text-amber-200" title="{{ $grupo->motivo_generacion_excepcional }}">
                                                                Excepcional
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        @if ($grupo->cicloEscolar)
                                                            <span
                                                                class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-900/60">
                                                                {{ $grupo->cicloEscolar->inicio_anio }} - {{ $grupo->cicloEscolar->fin_anio }}
                                                            </span>
                                                        @else
                                                            <span class="text-xs font-semibold text-amber-600 dark:text-amber-300">
                                                                Sin ciclo asignado
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        <div class="flex flex-col gap-1">
                                                            <span
                                                                class="font-medium {{ $rowInactive ? 'text-rose-700 dark:text-rose-300' : 'text-gray-700 dark:text-gray-200' }}">
                                                                {{ $nivelLabel }}
                                                            </span>
                                                            <span class="text-[10px] font-semibold uppercase tracking-wide {{ $grupo->estado === 'activo' ? 'text-emerald-600 dark:text-emerald-300' : 'text-amber-600 dark:text-amber-300' }}">
                                                                {{ $grupo->estado === 'activo' ? 'Activo' : 'Inactivo' }} · cupo ilimitado
                                                            </span>
                                                        </div>
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        <div class="flex justify-end gap-2">
                                                            <flux:button variant="danger"
                                                                class="cursor-pointer bg-rose-600 p-1 text-white hover:bg-rose-700"
                                                                @click="eliminar({{ $grupo->id }}, '{{ addslashes($nombreGrupo) }}')">
                                                                <flux:icon.trash-2 class="h-3.5 w-3.5" />
                                                            </flux:button>

                                                            <flux:button variant="primary"
                                                                class="cursor-pointer bg-amber-500 text-white hover:bg-amber-600 !px-3 !py-1.5 text-xs"
                                                                @click="$dispatch('abrir-modal-editar');
                                                                        Livewire.dispatch('editarModal', { id: {{ $grupo->id }} });
                                                                    ">
                                                                <flux:icon.square-pen class="mr-1 h-3.5 w-3.5" />
                                                            </flux:button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Paginación --}}
            <div class="mt-5">
                {{ $grupos->links() }}
            </div>
        </div>
    </div>

    {{-- Modal editar --}}
    <livewire:grupo.editar-grupo />
</div>
</div>
