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

    <!-- Encabezado -->
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Grupos</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Busca, edita o elimina grupos.
        </p>
    </div>

    <!-- Contenedor listado -->
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white/90 dark:bg-neutral-900/90 shadow-sm">

        <!-- Acabado superior -->
        <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-sky-400 to-indigo-600"></div>

        <!-- Toolbar -->
        <div
            class="p-4 sm:p-5 lg:p-6 border-b border-gray-100/80 dark:border-neutral-800/80 bg-gradient-to-r from-slate-50 via-white to-sky-50 dark:from-neutral-900 dark:via-neutral-950 dark:to-sky-950/20">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Buscador -->
                <div class="w-full sm:max-w-xl">
                    <label for="buscar-grupo" class="sr-only">Buscar Grupo</label>
                    <flux:input id="buscar-grupo" type="text" wire:model.live="search" placeholder="Buscar…"
                        icon="magnifying-glass" class="w-full" />
                </div>

                <!-- Resumen -->
                <div class="flex items-center gap-3 justify-between sm:justify-end">
                    <div
                        class="hidden sm:flex items-center gap-2 rounded-lg border border-gray-200 dark:border-neutral-800 px-3 py-1.5 bg-white/70 dark:bg-neutral-900/70 shadow-sm">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            Resultados:
                            <strong>{{ $totalGrupos }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de resultados -->
        <div class="px-4 pb-4 pt-3 sm:px-5 sm:pb-6 lg:px-6">
            <div class="relative">

                <!-- Loader -->
                <div wire:loading.delay wire:target="search, eliminar"
                    class="absolute inset-0 z-10 grid place-items-center rounded-2xl bg-white/70 dark:bg-neutral-900/70 backdrop-blur"
                    aria-live="polite" aria-busy="true">
                    <div
                        class="flex items-center gap-3 rounded-xl bg-white dark:bg-neutral-900 px-4 py-3 ring-1 ring-gray-200 dark:ring-neutral-800 shadow">
                        <svg class="h-5 w-5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24"
                            fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-200">Cargando…</span>
                    </div>
                </div>

                <!-- Contenido -->
                <div class="transition filter duration-200" wire:loading.class="blur-sm" wire:target="search,eliminar">

                    @if ($groupedByNivel->isEmpty())
                        <!-- Estado vacío -->
                        <div
                            class="mt-4 rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 p-8 bg-white/80 dark:bg-neutral-900/80 text-center">
                            <div
                                class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-950">
                                <flux:icon.search class="w-5 h-5 text-indigo-600 dark:text-indigo-300" />
                            </div>
                            <div class="mb-1 text-base font-semibold text-gray-800 dark:text-gray-100">
                                No hay grupos disponibles
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Ajusta tu búsqueda o registra un nuevo grupo.
                            </p>
                        </div>
                    @else
                        <!-- Cards resumen arriba -->
                        <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <!-- Total grupos -->
                            <div
                                class="relative overflow-hidden rounded-xl border border-indigo-100 dark:border-indigo-900/60 bg-gradient-to-r from-indigo-600 via-sky-500 to-blue-600 text-white shadow-sm">
                                <div
                                    class="absolute inset-0 opacity-25 bg-[radial-gradient(circle_at_top,_#ffffff_0,_transparent_55%)]">
                                </div>
                                <div class="relative p-4 sm:p-5 flex items-center justify-between gap-3">
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
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 border border-white/20 backdrop-blur-sm">
                                        <flux:icon.layout-grid class="w-5 h-5" />
                                    </div>
                                </div>
                            </div>

                            <!-- Niveles distintos -->
                            <div
                                class="relative overflow-hidden rounded-xl border border-emerald-100 dark:border-emerald-900/60 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-500 text-white shadow-sm">
                                <div
                                    class="absolute inset-0 opacity-25 bg-[radial-gradient(circle_at_top,_#ffffff_0,_transparent_55%)]">
                                </div>
                                <div class="relative p-4 sm:p-5 flex items-center justify-between gap-3">
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
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 border border-white/20 backdrop-blur-sm">
                                        <flux:icon.layers class="w-5 h-5" />
                                    </div>
                                </div>
                            </div>

                            <!-- Grupos sin nivel -->
                            <div
                                class="relative overflow-hidden rounded-xl border border-amber-100 dark:border-amber-900/60 bg-gradient-to-r from-amber-500 via-orange-400 to-rose-500 text-white shadow-sm">
                                <div
                                    class="absolute inset-0 opacity-25 bg-[radial-gradient(circle_at_top,_#ffffff_0,_transparent_55%)]">
                                </div>
                                <div class="relative p-4 sm:p-5 flex items-center justify-between gap-3">
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
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 border border-white/20 backdrop-blur-sm">
                                        <flux:icon.triangle-alert class="w-5 h-5" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TABLA -->
                        <div
                            class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 text-gray-700 dark:bg-neutral-900 dark:text-gray-200">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                                Grupo
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                                Grado
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                                Semestre
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                                Generación
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                                Nivel
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">
                                        @foreach ($groupedByNivel as $nivelNombre => $items)
                                            @php
                                                // ✅ Agrupar dentro del nivel por generación + status
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

                                            <!-- HEADER NIVEL -->
                                            <tr class="bg-slate-50/80 dark:bg-neutral-900/70">
                                                <td colspan="6" class="px-4 py-3">
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
                                                    [$genNombre, $genStatus] = array_pad(
                                                        explode('||', $genKey),
                                                        2,
                                                        '1',
                                                    );
                                                    $genStatus = (int) $genStatus;
                                                    $genIsInactive = $genStatus === 0;
                                                @endphp

                                                <!-- SUBHEADER GENERACIÓN -->
                                                <tr class="bg-white dark:bg-neutral-950">
                                                    <td colspan="6" class="px-4 py-2">
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

                                                                <span
                                                                    class="text-[11px] text-gray-500 dark:text-gray-400">
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
                                                    @endphp

                                                    @php
                                                        $rowInactive = optional($grupo->generacion)->status === 0;
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
                                                                {{ $grupo->nombre ?: '---' }}
                                                            </div>

                                                        </td>

                                                        <td class="px-4 py-3">
                                                            @php
                                                                $gradoNombre = $grupo->grado?->nombre;
                                                            @endphp

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
                                                                <span
                                                                    class=" text-gray-500 dark:text-gray-400">—</span>
                                                            @endif
                                                        </td>

                                                        <!-- ✅ Generación en rojo si status=0 -->
                                                        <td class="px-4 py-3">
                                                            <span
                                                                class="font-medium
                                                                {{ $rowInactive ? 'text-rose-700 dark:text-rose-300' : 'text-gray-700 dark:text-gray-200' }}">
                                                                {{ $rowGenLabel }}
                                                            </span>
                                                        </td>

                                                        <td class="px-4 py-3">
                                                            <span
                                                                class="font-medium
                                                                {{ $rowInactive ? 'text-rose-700 dark:text-rose-300' : 'text-gray-700 dark:text-gray-200' }}">
                                                                {{ $nivelLabel }}
                                                            </span>
                                                        </td>

                                                        <td class="px-4 py-3">
                                                            <div class="flex justify-end gap-2">
                                                                <flux:button variant="danger"
                                                                    class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white p-1"
                                                                    @click="eliminar({{ $grupo->id }}, '{{ addslashes($grupo->nombre) }}')">
                                                                    <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                                                </flux:button>

                                                                <flux:button variant="primary"
                                                                    class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white !px-3 !py-1.5 text-xs"
                                                                    @click="$dispatch('abrir-modal-editar');
                                                                        Livewire.dispatch('editarModal', { id: {{ $grupo->id }} });
                                                                    ">
                                                                    <flux:icon.square-pen class="w-3.5 h-3.5 mr-1" />
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

                <!-- Paginación -->
                <div class="mt-5">
                    {{ $grupos->links() }}
                </div>
            </div>
        </div>

        <!-- Modal editar -->
        <livewire:grupo.editar-grupo />
    </div>
</div>
