@php
    $bloques = $this->bloques;
    $listadoNominal = $this->listadoFiltrado;
    $resumenNominal = $this->resumenNominal;
    $totalGeneraciones = $bloques->count();
    $totalHistorico = (int) $bloques->sum(fn($bloque) => $bloque['totales']['total_historico'] ?? 0);
@endphp

<div class="space-y-6">
    {{-- Filtros generales --}}
    <section
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div
            class="flex flex-col gap-4 border-b border-slate-100 bg-gradient-to-r from-[#006492]/5 via-white to-[#88AC2E]/5 px-5 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800 dark:from-[#006492]/15 dark:via-neutral-900 dark:to-[#88AC2E]/10">
            <div class="flex items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-lg shadow-[#006492]/20">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 4.5h18M6.75 9.75h10.5M9.5 15h5M11 20.25h2" />
                    </svg>
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Filtros de distribución escolar
                        </h2>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700 ring-1 ring-inset ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Solo generaciones activas
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-neutral-400">
                        Combina los filtros para consultar generaciones, grupos y alumnos del nivel seleccionado.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button wire:click="limpiarFiltros" variant="ghost" icon="arrow-path">
                    Limpiar filtros
                </flux:button>

                <a target="_blank" href="{{ $this->urlPdf }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-black text-rose-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-rose-50 dark:border-rose-500/30 dark:bg-neutral-900 dark:text-rose-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 2.75h7.5L19 7.5v13.75H6.75V2.75Zm7.5 0V7.5H19" />
                    </svg>
                    PDF
                </a>

                <a href="{{ $this->urlExcel }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm font-black text-emerald-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-50 dark:border-emerald-500/30 dark:bg-neutral-900 dark:text-emerald-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 2.75h7.5L19 7.5v13.75H6.75V2.75Zm7.5 0V7.5H19M9.25 11l5.5 6m0-6-5.5 6" />
                    </svg>
                    Excel
                </a>

                <a href="{{ $this->urlZip }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-slate-900/10 transition hover:-translate-y-0.5 hover:bg-slate-800 dark:bg-[#006492] dark:hover:bg-[#0077ad]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7.5 3.75h9v16.5h-9V3.75Zm4.5 0v2.5m0 2.5v2.5m0 2.5v2.5m-1.25 1.75h2.5" />
                    </svg>
                    ZIP
                </a>
            </div>
        </div>

        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-6">
            <flux:select wire:model.live="generacion_id" label="Generación">
                <flux:select.option value="">Todas las activas</flux:select.option>
                @foreach ($generaciones as $generacion)
                    <flux:select.option value="{{ $generacion->id }}">
                        {{ $generacion->etiqueta }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="grado_id" label="Grado">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($grados as $grado)
                    <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($slug_nivel === 'bachillerato')
                <flux:select wire:model.live="semestre_id" label="Semestre">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($semestres as $semestre)
                        <flux:select.option value="{{ $semestre->id }}">{{ $semestre->numero }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model.live="grupo_id" label="Grupo">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($grupos as $grupo)
                    <flux:select.option value="{{ $grupo->id }}">
                        {{ $grupo->asignacionGrupo?->nombre ?? $grupo->id }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="estado" label="Estatus">
                <flux:select.option value="todos">Todos</flux:select.option>
                @foreach ($this->categorias as $valor => $texto)
                    <flux:select.option value="{{ $valor }}">{{ $texto }}</flux:select.option>
                @endforeach
            </flux:select>

            <label
                class="flex min-h-[42px] cursor-pointer items-center gap-3 self-end rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-[#006492]/40 hover:bg-[#006492]/5 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-800">
                <input type="checkbox" wire:model.live="solo_ya_no_estan"
                    class="h-4 w-4 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                <span>Solo no activos</span>
            </label>
        </div>
    </section>

    {{-- Accordion principal de generaciones --}}
    <section x-data="{ generacionesAbierto: true }"
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="generacionesAbierto = !generacionesAbierto"
            class="group flex w-full items-center justify-between gap-5 bg-gradient-to-r from-[#006492] to-[#087cae] px-5 py-5 text-left text-white transition hover:brightness-105"
            :aria-expanded="generacionesAbierto">
            <div class="flex min-w-0 items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-inset ring-white/20 backdrop-blur">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                    </svg>
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-black">Generaciones activas</h2>
                        <span
                            class="rounded-full bg-white/15 px-2.5 py-1 text-xs font-black ring-1 ring-inset ring-white/20">
                            {{ $totalGeneraciones }} {{ $totalGeneraciones === 1 ? 'generación' : 'generaciones' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-blue-100">
                        Distribución por grado, semestre y grupo. Las generaciones desactivadas quedan ocultas.
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-4">
                <div class="hidden text-right sm:block">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-blue-100">Alumnos
                        mostrados</span>
                    <span class="text-2xl font-black">{{ $totalHistorico }}</span>
                </div>

                <span
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white/15 ring-1 ring-inset ring-white/20 transition duration-300"
                    :class="generacionesAbierto ? 'rotate-180' : ''">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
            </div>
        </button>

        <div x-show="generacionesAbierto" x-collapse x-cloak>
            <div class="space-y-4 bg-slate-50/60 p-4 sm:p-5 dark:bg-neutral-950/30">
                @forelse ($bloques as $bloque)
                    <article wire:key="generacion-{{ md5($bloque['ciclo']) }}" x-data="{ abierto: {{ $loop->first ? 'true' : 'false' }} }"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:border-[#006492]/30 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                        <button type="button" x-on:click="abierto = !abierto"
                            class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-[#006492]/[0.035] dark:hover:bg-neutral-800/70"
                            :aria-expanded="abierto">
                            <div class="flex min-w-0 items-center gap-4">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#88AC2E] to-[#6f941e] text-white shadow-sm shadow-[#88AC2E]/20">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate text-base font-black text-slate-900 dark:text-white">
                                            {{ $bloque['ciclo'] }}
                                        </h3>
                                        <span
                                            class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-black text-[#006492] ring-1 ring-inset ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20">
                                            {{ count($bloque['filas']) }}
                                            {{ count($bloque['filas']) === 1 ? 'grupo' : 'grupos' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-neutral-400">
                                        Resumen académico y estadístico de la generación
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <div class="hidden text-right sm:block">
                                    <span
                                        class="block text-xs font-bold uppercase tracking-wide text-slate-400">Total</span>
                                    <span class="text-xl font-black text-slate-900 dark:text-white">
                                        {{ $bloque['totales']['total_historico'] }}
                                    </span>
                                </div>

                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition duration-300 group-hover:border-[#006492]/30 group-hover:text-[#006492] dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300"
                                    :class="abierto ? 'rotate-180' : ''">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                        </button>

                        <div x-show="abierto" x-collapse x-cloak
                            class="border-t border-slate-100 dark:border-neutral-800">
                            <div
                                class="grid grid-cols-2 gap-3 border-b border-slate-100 bg-slate-50/70 p-4 sm:grid-cols-3 lg:grid-cols-6 dark:border-neutral-800 dark:bg-neutral-950/40">
                                <div
                                    class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                    <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Total</span>
                                    <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">
                                        {{ $bloque['totales']['total_historico'] }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-3 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Activos</span>
                                    <p class="mt-1 text-xl font-black text-emerald-700 dark:text-emerald-300">
                                        {{ $bloque['totales']['activos'] ?? 0 }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-rose-100 bg-rose-50/70 p-3 dark:border-rose-500/20 dark:bg-rose-500/10">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wide text-rose-600 dark:text-rose-400">Bajas</span>
                                    <p class="mt-1 text-xl font-black text-rose-700 dark:text-rose-300">
                                        {{ $bloque['totales']['bajas'] ?? 0 }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-amber-100 bg-amber-50/70 p-3 dark:border-amber-500/20 dark:bg-amber-500/10">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400">Traslados</span>
                                    <p class="mt-1 text-xl font-black text-amber-700 dark:text-amber-300">
                                        {{ $bloque['totales']['traslados'] ?? 0 }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-violet-100 bg-violet-50/70 p-3 dark:border-violet-500/20 dark:bg-violet-500/10">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-400">Egresados</span>
                                    <p class="mt-1 text-xl font-black text-violet-700 dark:text-violet-300">
                                        {{ $bloque['totales']['egresados'] ?? 0 }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-sky-100 bg-sky-50/70 p-3 dark:border-sky-500/20 dark:bg-sky-500/10">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wide text-sky-600 dark:text-sky-400">Grupos</span>
                                    <p class="mt-1 text-xl font-black text-sky-700 dark:text-sky-300">
                                        {{ count($bloque['filas']) }}
                                    </p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead
                                        class="bg-slate-50/90 text-xs uppercase tracking-wide text-slate-500 dark:bg-neutral-950/60 dark:text-neutral-400">
                                        <tr>
                                            <th class="px-5 py-3.5 text-left font-black">Grado</th>
                                            @if ($slug_nivel === 'bachillerato')
                                                <th class="px-4 py-3.5 text-center font-black">Sem.</th>
                                            @endif
                                            <th class="px-4 py-3.5 text-center font-black">Grupo</th>
                                            <th class="px-4 py-3.5 text-center font-black">H</th>
                                            <th class="px-4 py-3.5 text-center font-black">M</th>
                                            <th class="px-4 py-3.5 text-center font-black">Total</th>
                                            <th class="px-4 py-3.5 text-center font-black">Activos</th>
                                            <th class="px-4 py-3.5 text-center font-black">Bajas</th>
                                            <th class="px-4 py-3.5 text-center font-black">Traslados</th>
                                            <th class="px-5 py-3.5 text-center font-black">Egresados</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                        @foreach ($bloque['filas'] as $fila)
                                            <tr class="transition hover:bg-[#006492]/[0.035] dark:hover:bg-blue-500/5">
                                                <td class="whitespace-nowrap px-5 py-4">
                                                    <span
                                                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2.5 font-black text-slate-800 dark:bg-neutral-800 dark:text-white">
                                                        {{ $fila['grado'] }}
                                                    </span>
                                                </td>

                                                @if ($slug_nivel === 'bachillerato')
                                                    <td
                                                        class="px-4 py-4 text-center font-bold text-slate-700 dark:text-neutral-200">
                                                        {{ $fila['semestre'] }}
                                                    </td>
                                                @endif

                                                <td class="px-4 py-4 text-center">
                                                    <span
                                                        class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-[#006492] dark:bg-blue-500/10 dark:text-blue-300">
                                                        {{ $fila['grupo'] }}
                                                    </span>
                                                </td>
                                                <td
                                                    class="px-4 py-4 text-center font-bold text-slate-600 dark:text-neutral-300">
                                                    {{ $fila['hombres'] }}
                                                </td>
                                                <td
                                                    class="px-4 py-4 text-center font-bold text-slate-600 dark:text-neutral-300">
                                                    {{ $fila['mujeres'] }}
                                                </td>
                                                <td class="px-4 py-4 text-center">
                                                    <span
                                                        class="inline-flex min-w-10 justify-center rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-black text-white dark:bg-white dark:text-slate-900">
                                                        {{ $fila['total_historico'] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 text-center">
                                                    <span class="font-black text-emerald-600 dark:text-emerald-400">
                                                        {{ $fila['activos'] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 text-center">
                                                    <span class="font-black text-rose-600 dark:text-rose-400">
                                                        {{ $fila['bajas'] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 text-center">
                                                    <span class="font-black text-amber-600 dark:text-amber-400">
                                                        {{ $fila['traslados'] }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-4 text-center">
                                                    <span class="font-black text-violet-600 dark:text-violet-400">
                                                        {{ $fila['egresados'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </article>
                @empty
                    <div
                        class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center dark:border-neutral-700 dark:bg-neutral-900">
                        <div
                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M7 12h10M10 18h4" />
                            </svg>
                        </div>
                        <p class="mt-4 font-black text-slate-700 dark:text-neutral-200">Sin información disponible</p>
                        <p class="mt-1 text-sm text-slate-500">No hay alumnos en generaciones activas para los filtros
                            seleccionados.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Accordion de listado nominal --}}
    <section x-data="{ nominalAbierto: false }"
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" x-on:click="nominalAbierto = !nominalAbierto"
            class="group flex w-full items-center justify-between gap-5 px-5 py-5 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70"
            :aria-expanded="nominalAbierto">
            <div class="flex min-w-0 items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#88AC2E] to-[#6f941e] text-white shadow-lg shadow-[#88AC2E]/20">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.25 6.75h10.5M8.25 12h10.5M8.25 17.25h10.5M4.5 6.75h.01M4.5 12h.01M4.5 17.25h.01" />
                    </svg>
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Listado nominal</h2>
                        <span
                            class="rounded-full bg-[#88AC2E]/10 px-2.5 py-1 text-xs font-black text-[#628315] ring-1 ring-inset ring-[#88AC2E]/20 dark:text-lime-300">
                            {{ $resumenNominal['total'] }} resultados
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-neutral-400">
                        Consulta individual con búsqueda, filtros rápidos y ordenamiento.
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-4">
                <div class="hidden items-center gap-2 lg:flex">
                    <span
                        class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-black text-[#006492] dark:bg-blue-500/10 dark:text-blue-300">
                        {{ $resumenNominal['hombres'] }} H
                    </span>
                    <span
                        class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-black text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">
                        {{ $resumenNominal['mujeres'] }} M
                    </span>
                </div>

                <span
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition duration-300 group-hover:border-[#88AC2E]/40 group-hover:text-[#628315] dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300"
                    :class="nominalAbierto ? 'rotate-180' : ''">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
            </div>
        </button>

        <div x-show="nominalAbierto" x-collapse x-cloak class="border-t border-slate-100 dark:border-neutral-800">
            <div class="space-y-4 bg-slate-50/60 p-4 sm:p-5 dark:bg-neutral-950/30">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">Filtros rápidos del listado</h3>
                            <p class="mt-0.5 text-sm text-slate-500 dark:text-neutral-400">
                                Estos filtros solo modifican la tabla nominal; los filtros generales permanecen activos.
                            </p>
                        </div>

                        <flux:button wire:click="limpiarFiltrosNominales" variant="ghost" icon="x-mark">
                            Restablecer listado
                        </flux:button>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-12">
                        <div class="lg:col-span-6">
                            <flux:input wire:model.live.debounce.350ms="busqueda_nominal" label="Buscar alumno"
                                placeholder="Nombre, matrícula, CURP, generación, grupo o estatus..."
                                icon="magnifying-glass" clearable />
                        </div>

                        <div class="lg:col-span-3">
                            <flux:select wire:model.live="genero_nominal" label="Género">
                                <flux:select.option value="todos">Todos</flux:select.option>
                                <flux:select.option value="H">Hombres</flux:select.option>
                                <flux:select.option value="M">Mujeres</flux:select.option>
                            </flux:select>
                        </div>

                        <div class="lg:col-span-3">
                            <flux:select wire:model.live="orden_nominal" label="Ordenar por">
                                <flux:select.option value="nombre_asc">Nombre A–Z</flux:select.option>
                                <flux:select.option value="nombre_desc">Nombre Z–A</flux:select.option>
                                <flux:select.option value="generacion_desc">Generación reciente</flux:select.option>
                                <flux:select.option value="estatus">Estatus</flux:select.option>
                            </flux:select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-400">Resultados</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                            {{ $resumenNominal['total'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                        <p class="text-xs font-black uppercase tracking-wide text-blue-600 dark:text-blue-300">Hombres
                        </p>
                        <p class="mt-1 text-2xl font-black text-blue-700 dark:text-blue-200">
                            {{ $resumenNominal['hombres'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-violet-100 bg-violet-50/70 p-4 dark:border-violet-500/20 dark:bg-violet-500/10">
                        <p class="text-xs font-black uppercase tracking-wide text-violet-600 dark:text-violet-300">
                            Mujeres</p>
                        <p class="mt-1 text-2xl font-black text-violet-700 dark:text-violet-200">
                            {{ $resumenNominal['mujeres'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                        <p class="text-xs font-black uppercase tracking-wide text-emerald-600 dark:text-emerald-300">
                            Activos</p>
                        <p class="mt-1 text-2xl font-black text-emerald-700 dark:text-emerald-200">
                            {{ $resumenNominal['activos'] }}</p>
                    </div>
                    <div
                        class="col-span-2 rounded-2xl border border-amber-100 bg-amber-50/70 p-4 sm:col-span-1 dark:border-amber-500/20 dark:bg-amber-500/10">
                        <p class="text-xs font-black uppercase tracking-wide text-amber-600 dark:text-amber-300">No
                            activos</p>
                        <p class="mt-1 text-2xl font-black text-amber-700 dark:text-amber-200">
                            {{ $resumenNominal['no_activos'] }}</p>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="max-h-[560px] overflow-auto">
                        <table class="min-w-full text-sm">
                            <thead
                                class="sticky top-0 z-10 bg-slate-100/95 text-xs uppercase tracking-wide text-slate-500 backdrop-blur dark:bg-neutral-950/95 dark:text-neutral-400">
                                <tr>
                                    <th class="px-5 py-4 text-left font-black">Alumno</th>
                                    <th class="px-4 py-4 text-center font-black">Generación</th>
                                    <th class="px-4 py-4 text-center font-black">Ubicación</th>
                                    <th class="px-4 py-4 text-center font-black">Estatus</th>
                                    <th class="px-5 py-4 text-center font-black">Fecha</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                @forelse ($listadoNominal as $fila)
                                    @php
                                        $estatusClase = match ($fila['categoria_actual'] ?? '') {
                                            'activo',
                                            'reingreso',
                                            'no_promovido'
                                                => 'bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
                                            'baja'
                                                => 'bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
                                            'traslado'
                                                => 'bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
                                            'egresado'
                                                => 'bg-violet-50 text-violet-700 ring-violet-100 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/20',
                                            default
                                                => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-neutral-800 dark:text-neutral-200 dark:ring-neutral-700',
                                        };
                                    @endphp

                                    <tr class="group transition hover:bg-[#006492]/[0.035] dark:hover:bg-blue-500/5">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 text-sm font-black text-slate-700 transition group-hover:from-[#006492] group-hover:to-[#087cae] group-hover:text-white dark:from-neutral-800 dark:to-neutral-700 dark:text-neutral-200">
                                                    {{ mb_strtoupper(mb_substr($fila['alumno'], 0, 1)) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-black text-slate-900 dark:text-white">
                                                        {{ $fila['alumno'] }}</p>
                                                    <div
                                                        class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                                        <span class="font-bold">{{ $fila['matricula'] }}</span>
                                                        <span class="text-slate-300">•</span>
                                                        <span class="font-mono">{{ $fila['curp'] }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span
                                                class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-[#006492] ring-1 ring-inset ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20">
                                                {{ $fila['generacion'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-center text-slate-600 dark:text-neutral-300">
                                            <div class="inline-flex flex-wrap items-center justify-center gap-1.5">
                                                <span class="font-bold">{{ $fila['grado'] }}</span>
                                                @if ($slug_nivel === 'bachillerato')
                                                    <span class="text-slate-300">•</span>
                                                    <span>Sem. {{ $fila['semestre'] }}</span>
                                                @endif
                                                <span class="text-slate-300">•</span>
                                                <span
                                                    class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-black text-slate-700 dark:bg-neutral-800 dark:text-neutral-200">
                                                    Grupo {{ $fila['grupo'] }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 ring-inset {{ $estatusClase }}">
                                                {{ $fila['estado_actual'] }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <div
                                                class="inline-flex items-center gap-2 text-slate-600 dark:text-neutral-300">
                                                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                                                </svg>
                                                <span class="font-semibold">
                                                    {{ $fila['fecha_baja'] !== '—' ? $fila['fecha_baja'] : $fila['fecha_alta'] }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-16 text-center">
                                            <div
                                                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="1.7">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                                                </svg>
                                            </div>
                                            <p class="mt-4 font-black text-slate-700 dark:text-neutral-200">Sin
                                                coincidencias</p>
                                            <p class="mt-1 text-sm text-slate-500">Cambia la búsqueda o restablece los
                                                filtros del listado.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
