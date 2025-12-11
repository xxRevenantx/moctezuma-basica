<div x-data="{
    openRow: null,
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `El semestre ${nombre} se eliminará de forma permanente`,
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
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Semestres
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Busca, edita o elimina semestres educativos.
        </p>

        <div
            class="inline-flex items-center gap-2 text-xs rounded-full bg-amber-50 text-amber-800 dark:bg-amber-900/40 dark:text-amber-100 px-3 py-1 ring-1 ring-amber-100/70 dark:ring-amber-800/60 w-fit">
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
            <span>Los semestres aplican únicamente para el nivel Bachillerato.</span>
        </div>
    </div>

    <!-- Contenedor listado -->
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white/90 dark:bg-neutral-900/90 shadow-xl">
        <!-- Acabado superior -->
        <div class="h-1 w-full bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500"></div>

        <!-- Toolbar -->
        <div class="p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col gap-3 lg:gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Buscador -->
                <div class="w-full sm:max-w-xl">
                    <label for="buscar-semestre" class="sr-only">Buscar Semestre</label>
                    <flux:input id="buscar-semestre" type="text" wire:model.live="search" placeholder="Buscar…"
                        icon="magnifying-glass" class="w-full" />
                </div>

                <!-- Resumen -->
                <div class="flex items-center gap-3 justify-end">
                    <div
                        class="hidden sm:flex items-center gap-2 rounded-lg border border-gray-200 dark:border-neutral-800 px-3 py-1.5 bg-gray-50/80 dark:bg-neutral-800/80 backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            Resultados:
                            <strong>{{ method_exists($semestres, 'total') ? $semestres->total() : $semestres->count() }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de resultados -->
        <div class="px-4 pb-4 sm:px-5 sm:pb-6 lg:px-6">
            <div class="relative">

                <!-- Loader -->
                <div wire:loading.delay wire:target="search, eliminar"
                    class="absolute inset-0 z-10 grid place-items-center rounded-2xl bg-white/70 dark:bg-neutral-900/70 backdrop-blur"
                    aria-live="polite" aria-busy="true">
                    <div
                        class="flex items-center gap-3 rounded-xl bg-white dark:bg-neutral-900 px-4 py-3 ring-1 ring-gray-200 dark:ring-neutral-800 shadow-lg">
                        <svg class="h-5 w-5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24"
                            fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-200">Cargando…</span>
                    </div>
                </div>

                <!-- Contenido (cards) -->
                <div class="transition filter duration-200" wire:loading.class="blur-sm" wire:target="search,eliminar">

                    @if ($semestres->isEmpty())
                        <div
                            class="rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 p-8 bg-white/80 dark:bg-neutral-900/80 text-center max-w-md mx-auto">
                            <div class="mb-2 text-base font-semibold text-gray-800 dark:text-gray-100">
                                No hay semestres disponibles
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Ajusta tu búsqueda o registra nuevos semestres.
                            </p>
                        </div>
                    @else
                        <div class="grid gap-4 sm:gap-5 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 auto-rows-fr">

                            @foreach ($semestres as $key => $semestre)
                                @php
                                    $grado = $semestre->grado ?? null;
                                    $nivel = $grado?->nivel ?? null;
                                @endphp

                                <article
                                    class="group relative overflow-hidden rounded-2xl border border-gray-200/80 dark:border-neutral-800/80 bg-gradient-to-b from-slate-50/80 via-white to-white dark:from-neutral-900 dark:via-neutral-900 dark:to-neutral-950 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
                                    <!-- Barra superior -->
                                    <div
                                        class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500">
                                    </div>

                                    <div class="p-4 pt-5 sm:p-5 flex flex-col h-full">
                                        <!-- Header: etiqueta y número -->
                                        <div class="flex items-start justify-between gap-3 mb-3">
                                            <div class="space-y-1">
                                                <div
                                                    class="inline-flex items-center gap-2 rounded-full bg-indigo-50 dark:bg-indigo-950/50 px-3 py-1 text-[11px] font-semibold tracking-wide uppercase text-indigo-700 dark:text-indigo-200 ring-1 ring-indigo-100/80 dark:ring-indigo-900/60">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                                                    <span>Semestre #{{ $key + 1 }}</span>
                                                </div>

                                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                    {{ $semestre->numero ? $semestre->numero . '° Semestre' : 'Semestre sin número' }}
                                                </h2>
                                            </div>

                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 dark:bg-sky-900/40 ring-1 ring-sky-100 dark:ring-sky-800 text-sky-700 dark:text-sky-200 text-xs font-semibold">
                                                {{ $grado?->nombre ? $grado->nombre . '°' : '--' }}
                                            </div>
                                        </div>

                                        <!-- Info principal -->
                                        <div class="space-y-2 text-sm mb-4 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex items-center rounded-full bg-sky-50 dark:bg-sky-900/40 px-2.5 py-1 text-[11px] font-medium text-sky-700 dark:text-sky-200 ring-1 ring-sky-100 dark:ring-sky-800">
                                                    <flux:icon.academic-cap class="w-3.5 h-3.5 mr-1" />
                                                    {{ $nivel->nombre ?? 'Bachillerato' }}
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2">
                                                <span
                                                    class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 text-[11px] font-medium text-emerald-700 dark:text-emerald-200 ring-1 ring-emerald-100 dark:ring-emerald-800">
                                                    <flux:icon.calendar class="w-3.5 h-3.5 mr-1" />
                                                    Meses:
                                                    {{ $semestre->mesesBachillerato->meses ?? 'Sin asignar' }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Footer acciones -->
                                        <div
                                            class="mt-auto pt-3 border-t border-dashed border-gray-200 dark:border-neutral-800 flex items-center justify-between gap-3">
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                                ID: <span class="font-mono text-gray-700 dark:text-gray-200">
                                                    {{ $semestre->id }}
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <flux:button variant="primary" size="xs"
                                                    class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white shadow-sm"
                                                    @click="$dispatch('abrir-modal-editar');
                                                        Livewire.dispatch('editarModal', { id: {{ $semestre->id }} });
                                                    ">
                                                    <flux:icon.square-pen class="w-3.5 h-3.5" />
                                                    <span class="hidden sm:inline-block text-xs">Editar</span>
                                                </flux:button>

                                                <flux:button variant="ghost" size="xs"
                                                    class="text-red-500 hover:text-red-600 hover:bg-red-50/70 dark:hover:bg-red-900/30"
                                                    @click="eliminar({{ $semestre->id }}, '{{ $semestre->numero ? $semestre->numero . '° Semestre' : 'Semestre' }}')">
                                                    <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                                </flux:button>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Paginación -->
                <div class="mt-5">
                    {{ $semestres->links() }}
                </div>
            </div>
        </div>

        <!-- Modal editar -->
        <livewire:semestre.editar-semestres />
    </div>
</div>
