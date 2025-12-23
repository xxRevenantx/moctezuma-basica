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

                        <!-- AGRUPADO POR NIVEL EN COLLAPSE -->
                        <div class="space-y-4">
                            @foreach ($groupedByNivel as $nivelNombre => $items)
                                @php
                                    // Agrupamos los grupos de este nivel por generación
                                    $groupedByGeneracion = $items->groupBy(function ($g) {
                                        if ($g->generacion) {
                                            return $g->generacion->anio_ingreso . ' - ' . $g->generacion->anio_egreso;
                                        }
                                        return 'Sin generación asignada';
                                    });
                                    $nivelKey = \Illuminate\Support\Str::slug($nivelNombre ?: 'sin-nivel');
                                @endphp

                                <section x-data="{
                                    open: false,
                                    key: 'swce_grupos_nivel_{{ $nivelKey }}',
                                    init() {
                                        const saved = localStorage.getItem(this.key);
                                        this.open = saved ? JSON.parse(saved) : false; // por defecto cerrados
                                        this.$watch('open', value => localStorage.setItem(this.key, JSON.stringify(value)));
                                    }
                                }"
                                    class="rounded-2xl border border-slate-200/80 dark:border-neutral-800/80 bg-white/80 dark:bg-neutral-950/80 shadow-sm overflow-hidden">

                                    <!-- Header colapsable -->
                                    <button type="button" @click="open = !open"
                                        class="w-full flex items-center justify-between gap-3 px-4 sm:px-5 py-3 sm:py-3.5 bg-slate-50/80 dark:bg-neutral-900/80 hover:bg-slate-100/80 dark:hover:bg-neutral-900 transition-colors">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="inline-flex items-center gap-2 rounded-full bg-indigo-50 dark:bg-indigo-950/70 px-3 py-1 shadow-sm ring-1 ring-indigo-100 dark:ring-indigo-900/60">
                                                <span
                                                    class="h-2 w-2 rounded-full bg-indigo-500 dark:bg-indigo-300"></span>
                                                <span
                                                    class="text-xs font-semibold tracking-wide uppercase text-indigo-700 dark:text-indigo-100">
                                                    {{ $nivelNombre }}
                                                </span>
                                            </div>

                                            <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                                {{ $items->count() }} grupos
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400 hidden sm:inline">
                                                Mostrar / ocultar
                                            </span>
                                            <flux:icon.chevron-down
                                                class="w-4 h-4 text-gray-500 dark:text-gray-300 transform transition-transform duration-200"
                                                x-bind:class="open ? 'rotate-180' : ''" />
                                        </div>
                                    </button>

                                    <!-- Contenido colapsable -->
                                    <div x-show="open" x-transition.opacity x-transition.duration.200ms
                                        class="px-4 sm:px-5 pb-4 sm:pb-5 pt-3 space-y-5">
                                        @foreach ($groupedByGeneracion as $genNombre => $gruposGen)
                                            <!-- Header de generación dentro del nivel -->
                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="inline-flex items-center gap-2 rounded-full bg-sky-50 dark:bg-sky-900/50 px-3 py-1 text-[11px] font-semibold text-sky-800 dark:text-sky-100 ring-1 ring-sky-100 dark:ring-sky-800">
                                                        <flux:icon.calendar-clock class="w-3.5 h-3.5" />
                                                        {{ $genNombre }}
                                                    </span>
                                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                                        {{ $gruposGen->count() }} grupos
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Grid de cards de grupos de esta generación -->
                                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                                @foreach ($gruposGen as $grupo)
                                                    <div
                                                        class="group relative overflow-hidden rounded-2xl border border-slate-200/90 dark:border-neutral-800 bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-neutral-900 dark:via-neutral-950 dark:to-neutral-900 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
                                                        <!-- Glow de fondo -->
                                                        <div
                                                            class="pointer-events-none absolute inset-x-0 -top-10 h-24 bg-gradient-to-br from-indigo-500/25 via-sky-400/20 to-transparent opacity-0 group-hover:opacity-100 blur-2xl transition-opacity duration-300">
                                                        </div>

                                                        <div class="relative p-4 sm:p-5 flex flex-col gap-4">
                                                            <!-- Top: nombre grupo + grado/semestre -->
                                                            <div class="flex items-start justify-between gap-3">
                                                                <div class="space-y-1">
                                                                    <h2
                                                                        class="text-base sm:text-lg font-semibold tracking-tight text-gray-900 dark:text-white">
                                                                        {{ $grupo->nombre ?: '---' }}
                                                                    </h2>
                                                                    <div
                                                                        class="flex flex-wrap gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                                                        <span class="inline-flex items-center gap-1">
                                                                            <flux:icon.book-open class="w-3.5 h-3.5" />
                                                                            Grado:
                                                                            <strong
                                                                                class="font-semibold text-gray-700 dark:text-gray-200">
                                                                                {{ optional($grupo->grado)->nombre ? optional($grupo->grado)->nombre . '°' : 'Sin grado' }}
                                                                            </strong>
                                                                        </span>
                                                                        @if ($grupo->semestre)
                                                                            <span class="mx-1 text-gray-400">•</span>
                                                                            <span
                                                                                class="inline-flex items-center gap-1">
                                                                                Semestre:
                                                                                <strong
                                                                                    class="font-semibold text-gray-700 dark:text-gray-200">
                                                                                    {{ $grupo->semestre->numero }}°
                                                                                    semestre
                                                                                </strong>
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Footer: info + acciones -->
                                                            <div class="mt-1 flex items-center justify-between gap-3">
                                                                <div
                                                                    class="flex flex-wrap items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                                                    <span class="inline-flex items-center gap-1">
                                                                        <flux:icon.school class="w-3.5 h-3.5" />
                                                                        Nivel:
                                                                        <strong
                                                                            class="font-semibold text-gray-700 dark:text-gray-200">
                                                                            {{ $nivelNombre }}
                                                                        </strong>
                                                                    </span>
                                                                </div>

                                                                <div class="flex items-center gap-2">


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
                                                                        <flux:icon.square-pen
                                                                            class="w-3.5 h-3.5 mr-1" />

                                                                    </flux:button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
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
