<div x-data="{
    openRow: null,

    eliminar(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `El periodo se eliminará de forma permanente`,
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
            Periodos
        </h1>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            Busca, edita o elimina periodos de básica y bachillerato agrupados por nivel.
        </p>
    </div>

    {{-- Contenedor listado --}}
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow dark:border-neutral-800 dark:bg-neutral-800">

        {{-- Acabado superior --}}
        <div class="h-1 w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500"></div>

        {{-- Toolbar --}}
        <div class="p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:gap-4">

                {{-- Buscador --}}
                <div class="w-full sm:max-w-xl">
                    <label for="buscar-periodo" class="sr-only">
                        Buscar periodo
                    </label>

                    <flux:input id="buscar-periodo" type="text" wire:model.live="search"
                        placeholder="Buscar por nivel, ciclo, mes, periodo, generación, semestre o parcial…"
                        icon="magnifying-glass" class="w-full" />
                </div>

                {{-- Resumen --}}
                <div class="flex items-center gap-3">
                    <div
                        class="hidden items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 dark:border-neutral-800 dark:bg-neutral-700 sm:flex">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>

                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            Resultados:
                            <strong>
                                {{ method_exists($periodos, 'total') ? $periodos->total() : $periodos->count() }}
                            </strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Área de resultados --}}
        <div class="px-4 pb-4 sm:px-5 sm:pb-6 lg:px-6">
            <div class="relative">

                {{-- Loader --}}
                <div wire:loading.delay wire:target="search,eliminar"
                    class="absolute inset-0 z-10 grid place-items-center rounded-xl bg-white/70 backdrop-blur dark:bg-neutral-900/70"
                    aria-live="polite" aria-busy="true">

                    <div
                        class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow ring-1 ring-gray-200 dark:bg-neutral-900 dark:ring-neutral-800">

                        <svg class="h-5 w-5 animate-spin text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24"
                            fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z">
                            </path>
                        </svg>

                        <span class="text-sm text-gray-700 dark:text-gray-200">
                            Cargando…
                        </span>
                    </div>
                </div>

                {{-- Contenido --}}
                <div class="transition filter duration-200" wire:loading.class="blur-sm" wire:target="search,eliminar">

                    {{-- Tabla desktop --}}
                    <div
                        class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-neutral-800 dark:bg-neutral-800 md:block">

                        <div class="max-h-[65vh] overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="table-gradient sticky top-0 z-10">
                                    <tr>
                                        <th
                                            class="border-r border-white/10 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                                            #
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Ciclo escolar
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Tipo
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Generación
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Semestre
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Mes
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                            Periodo / Parcial
                                        </th>

                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                                            Inicio
                                        </th>

                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                                            Fin
                                        </th>

                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100/70 dark:divide-neutral-800">
                                    @if ($periodos->isEmpty())
                                        <tr>
                                            <td colspan="10"
                                                class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">

                                                <div class="mx-auto w-full max-w-md">
                                                    <div
                                                        class="rounded-2xl border border-dashed border-gray-300 bg-white/70 p-6 dark:border-neutral-700 dark:bg-neutral-900/70">

                                                        <div
                                                            class="mb-1 text-base font-semibold text-gray-800 dark:text-gray-100">
                                                            No hay periodos disponibles
                                                        </div>

                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            Ajusta tu búsqueda o registra un nuevo periodo.
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        @php
                                            $nivelActual = null;
                                        @endphp

                                        @foreach ($periodos as $key => $periodo)
                                            @php
                                                $nivelId = optional($periodo->nivel)->id;
                                                $esBachillerato = (int) $periodo->nivel_id === 4;

                                                $tipoPeriodo = $esBachillerato ? 'Bachillerato' : 'Básica';

                                                $nombreMes = $esBachillerato
                                                    ? $periodo->mesesBachillerato->meses ?? '---'
                                                    : $periodo->mesesBasica->meses ?? '---';

                                                $nombrePeriodo = $esBachillerato
                                                    ? $periodo->parcialBachillerato->descripcion ?? '---'
                                                    : $periodo->periodoBasica->descripcion ?? '---';

                                                $nombreEliminar =
                                                    $periodo->nivel->nombre .
                                                    ' - ' .
                                                    $nombreMes .
                                                    ' - ' .
                                                    $nombrePeriodo;
                                            @endphp

                                            {{-- Cabecera por nivel --}}
                                            @if ($nivelId !== $nivelActual)
                                                <tr>
                                                    <td colspan="10" class="px-4 pb-2 pt-4">
                                                        <div
                                                            class="inline-flex flex-wrap items-center gap-2 rounded-full bg-sky-50 px-3 py-1 shadow-sm ring-1 ring-sky-100 dark:bg-sky-950/40 dark:ring-sky-900/60">
                                                            <span
                                                                class="h-2 w-2 rounded-full bg-sky-500 dark:bg-sky-400"></span>

                                                            <span
                                                                class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-200">
                                                                Nivel:
                                                                {{ $periodo->nivel->nombre ?? '---' }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>

                                                @php
                                                    $nivelActual = $nivelId;
                                                @endphp
                                            @endif

                                            {{-- Fila del periodo --}}
                                            <tr
                                                class="odd:bg-slate-50/80 even:bg-white transition-colors duration-150 hover:bg-emerald-50/80 dark:odd:bg-neutral-900/60 dark:even:bg-neutral-800/60 dark:hover:bg-emerald-950/40">

                                                {{-- Número --}}
                                                <td class="px-4 py-3 text-center text-gray-800 dark:text-gray-200">
                                                    <span
                                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100 dark:bg-neutral-900 dark:text-emerald-300 dark:ring-emerald-800">
                                                        {{ $key + 1 + ($periodos->currentPage() - 1) * $periodos->perPage() }}
                                                    </span>
                                                </td>

                                                {{-- Ciclo --}}
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    {{ $periodo->cicloEscolar->inicio_anio ?? '---' }}
                                                    -
                                                    {{ $periodo->cicloEscolar->fin_anio ?? '---' }}
                                                </td>

                                                {{-- Tipo --}}
                                                <td class="px-4 py-3">
                                                    @if ($esBachillerato)
                                                        <span
                                                            class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/60">
                                                            Bachillerato
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60">
                                                            Básica
                                                        </span>
                                                    @endif
                                                </td>

                                                {{-- Generación --}}
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    @if ($esBachillerato)
                                                        {{ $periodo->generacion->anio_ingreso ?? '---' }}
                                                        -
                                                        {{ $periodo->generacion->anio_egreso ?? '---' }}
                                                    @else
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                                            No aplica
                                                        </span>
                                                    @endif
                                                </td>

                                                {{-- Semestre --}}
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    @if ($esBachillerato && $periodo->semestre)
                                                        {{ $periodo->semestre->numero }}° Semestre
                                                    @else
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                                            No aplica
                                                        </span>
                                                    @endif
                                                </td>

                                                {{-- Mes --}}
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    {{ $nombreMes }}
                                                </td>

                                                {{-- Periodo / Parcial --}}
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    @if ($esBachillerato)
                                                        @if ($periodo->parcialBachillerato)
                                                            <span
                                                                class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:ring-indigo-900/60">
                                                                {{ $periodo->parcialBachillerato->descripcion }}
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-amber-600 dark:text-amber-400">
                                                                Sin parcial
                                                            </span>
                                                        @endif
                                                    @else
                                                        @if ($periodo->periodoBasica)
                                                            <span
                                                                class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 ring-1 ring-teal-100 dark:bg-teal-950/40 dark:text-teal-300 dark:ring-teal-900/60">
                                                                {{ $periodo->periodoBasica->descripcion }}
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-amber-600 dark:text-amber-400">
                                                                Sin periodo
                                                            </span>
                                                        @endif
                                                    @endif
                                                </td>

                                                {{-- Inicio --}}
                                                <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                                    {{ $periodo->fecha_inicio ? \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') : '---' }}
                                                </td>

                                                {{-- Fin --}}
                                                <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                                    {{ $periodo->fecha_fin ? \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') : '---' }}
                                                </td>

                                                {{-- Acciones --}}
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <flux:button variant="primary"
                                                            class="cursor-pointer bg-amber-500 px-2.5 py-1.5 text-xs text-white hover:bg-amber-600"
                                                            @click="$dispatch('abrir-modal-editar');
                                                                Livewire.dispatch('editarModal', { id: {{ $periodo->id }} });">
                                                            <flux:icon.square-pen class="h-3.5 w-3.5" />
                                                        </flux:button>

                                                        <flux:button variant="danger"
                                                            class="cursor-pointer bg-red-600 px-2.5 py-1.5 text-xs text-white hover:bg-red-700"
                                                            @click="eliminar(
                                                                {{ $periodo->id }}
                                                            )">
                                                            <flux:icon.trash class="h-3.5 w-3.5" />
                                                        </flux:button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tarjetas mobile --}}
                    <div class="space-y-3 md:hidden">
                        @if ($periodos->isEmpty())
                            <div
                                class="rounded-xl border border-dashed border-gray-300 p-6 text-center dark:border-neutral-700">

                                <div class="mb-1 font-semibold text-gray-700 dark:text-gray-200">
                                    No hay periodos disponibles
                                </div>

                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Ajusta tu búsqueda o registra un nuevo periodo.
                                </p>
                            </div>
                        @else
                            @php
                                $nivelActualMobile = null;
                            @endphp

                            @foreach ($periodos as $key => $periodo)
                                @php
                                    $nivelId = optional($periodo->nivel)->id;
                                    $esBachillerato = (int) $periodo->nivel_id === 4;

                                    $tipoPeriodo = $esBachillerato ? 'Bachillerato' : 'Básica';

                                    $nombreMes = $esBachillerato
                                        ? $periodo->mesesBachillerato->meses ?? '---'
                                        : $periodo->mesesBasica->meses ?? '---';

                                    $nombrePeriodo = $esBachillerato
                                        ? $periodo->parcialBachillerato->descripcion ?? '---'
                                        : $periodo->periodoBasica->descripcion ?? '---';

                                    $nombreEliminar =
                                        $periodo->nivel->nombre . ' - ' . $nombreMes . ' - ' . $nombrePeriodo;
                                @endphp

                                {{-- Cabecera por nivel mobile --}}
                                @if ($nivelId !== $nivelActualMobile)
                                    <div
                                        class="mt-4 inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 shadow-sm ring-1 ring-sky-100 dark:bg-sky-950/40 dark:ring-sky-900/60">
                                        <span class="h-2 w-2 rounded-full bg-sky-500 dark:bg-sky-400"></span>

                                        <span
                                            class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-200">
                                            Nivel:
                                            {{ $periodo->nivel->nombre ?? '---' }}
                                        </span>
                                    </div>

                                    @php
                                        $nivelActualMobile = $nivelId;
                                    @endphp
                                @endif

                                <div
                                    class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

                                    <div class="flex items-start justify-between gap-3">
                                        <div class="space-y-2">
                                            <div
                                                class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-emerald-600 to-sky-500 px-3 py-1 text-xs font-medium text-white shadow-sm">
                                                <span>
                                                    #{{ $key + 1 + ($periodos->currentPage() - 1) * $periodos->perPage() }}
                                                </span>

                                                <span>
                                                    {{ $periodo->cicloEscolar->inicio_anio ?? 'Sin ciclo' }}
                                                    -
                                                    {{ $periodo->cicloEscolar->fin_anio ?? 'Sin ciclo' }}
                                                </span>
                                            </div>

                                            <div class="space-y-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                                        {{ $nombreMes }}
                                                    </h2>

                                                    @if ($esBachillerato)
                                                        <span
                                                            class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/60">
                                                            Bachillerato
                                                        </span>
                                                    @else
                                                        <span
                                                            class="rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-semibold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60">
                                                            Básica
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                                    Periodo / Parcial:
                                                    <span class="font-semibold text-gray-800 dark:text-gray-100">
                                                        {{ $nombrePeriodo }}
                                                    </span>
                                                </p>

                                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                                    Generación:
                                                    @if ($esBachillerato)
                                                        <span class="font-semibold text-gray-800 dark:text-gray-100">
                                                            {{ $periodo->generacion->anio_ingreso ?? '---' }}
                                                            -
                                                            {{ $periodo->generacion->anio_egreso ?? '---' }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400 dark:text-gray-500">
                                                            No aplica
                                                        </span>
                                                    @endif
                                                </p>

                                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                                    Semestre:
                                                    @if ($esBachillerato && $periodo->semestre)
                                                        <span class="font-semibold text-gray-800 dark:text-gray-100">
                                                            {{ $periodo->semestre->numero }}° Semestre
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400 dark:text-gray-500">
                                                            No aplica
                                                        </span>
                                                    @endif
                                                </p>

                                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                                    Inicio:
                                                    <span class="font-semibold text-gray-800 dark:text-gray-100">
                                                        {{ $periodo->fecha_inicio ? \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') : '---' }}
                                                    </span>

                                                    ·

                                                    Fin:
                                                    <span class="font-semibold text-gray-800 dark:text-gray-100">
                                                        {{ $periodo->fecha_fin ? \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') : '---' }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <flux:button variant="primary"
                                                class="cursor-pointer bg-amber-500 px-2.5 py-1.5 text-xs text-white hover:bg-amber-600"
                                                @click="$dispatch('abrir-modal-editar');
                                                    Livewire.dispatch('editarModal', { id: {{ $periodo->id }} });">
                                                <flux:icon.square-pen class="h-3.5 w-3.5" />
                                            </flux:button>

                                            <flux:button variant="danger"
                                                class="cursor-pointer bg-red-600 px-2.5 py-1.5 text-xs text-white hover:bg-red-700"
                                                @click="eliminar(
                                                    {{ $periodo->id }}
                                                )">
                                                <flux:icon.trash class="h-3.5 w-3.5" />
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- Paginación --}}
                <div class="mt-5">
                    {{ $periodos->links() }}
                </div>
            </div>
        </div>
    </div>

    <livewire:periodo.editar-periodo />

</div>
