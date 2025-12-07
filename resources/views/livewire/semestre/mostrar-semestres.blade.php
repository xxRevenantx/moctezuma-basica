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
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Semestres</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Busca, edita o elimina semestres educativos.
        </p>

        <!-- Nota SOLO bachillerato -->
        <div
            class="inline-flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/30 dark:text-amber-100 text-xs sm:text-sm">
            <span
                class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-[11px] font-semibold text-amber-800 dark:bg-amber-800/70 dark:text-amber-50">
                i
            </span>
            <span>
                <span class="font-semibold">Nota:</span>
                Este módulo de semestres aplica <span class="font-semibold uppercase">únicamente</span> para el
                nivel <span class="font-semibold">Bachillerato</span>.
            </span>
        </div>
    </div>

    <!-- Contenedor listado -->
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-800 shadow">
        <!-- Acabado superior -->
        <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-sky-400 to-indigo-600"></div>

        <!-- Toolbar -->
        <div class="p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col gap-3 lg:gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Buscador -->
                <div class="w-full sm:max-w-xl">
                    <label for="buscar-semestre" class="sr-only">Buscar Semestre</label>
                    <flux:input id="buscar-semestre" type="text" wire:model.live="search" placeholder="Buscar…"
                        icon="magnifying-glass" class="w-full" />
                </div>
                {{ $semestres }}
                <!-- Resumen -->
                <div class="flex items-center gap-3">
                    <div
                        class="hidden sm:flex items-center gap-2 rounded-lg border border-gray-200 dark:border-neutral-800 px-3 py-1.5 bg-gray-50 dark:bg-neutral-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
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
                    class="absolute inset-0 z-10 grid place-items-center rounded-xl bg-white/70 dark:bg-neutral-900/70 backdrop-blur"
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

                <!-- ⬇️ Contenido que se desenfoca mientras se busca/carga -->
                <div class="transition filter duration-200" wire:loading.class="blur-sm" wire:target="search,eliminar">

                    <!-- Tabla (desktop) -->
                    {{ $semestres }}
                    <div
                        class="hidden md:block overflow-hidden rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-800">
                        <div class="overflow-x-auto max-h-[65vh]">
                            <table class="min-w-full text-sm">
                                <thead
                                    class="sticky top-0 z-10 bg-gradient-to-r from-indigo-600 via-sky-500 to-blue-600 text-white shadow-sm">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-center font-semibold text-xs uppercase tracking-wide border-r border-white/10">
                                            #
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-semibold text-xs uppercase tracking-wide border-r border-white/10">
                                            Semestre
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-semibold text-xs uppercase tracking-wide border-r border-white/10">
                                            Grado Educativo
                                        </th>

                                        <th
                                            class="px-4 py-3 text-left font-semibold text-xs uppercase tracking-wide border-r border-white/10">
                                            Meses
                                        </th>
                                        <th class="px-4 py-3 text-center font-semibold text-xs uppercase tracking-wide">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>


                                <tbody class="divide-y divide-gray-100/70 dark:divide-neutral-800">
                                    @if ($semestres->isEmpty())
                                        <tr>
                                            <td colspan="9"
                                                class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                                <div class="mx-auto w-full max-w-md">
                                                    <div
                                                        class="rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 p-6 bg-white/70 dark:bg-neutral-900/70">
                                                        <div
                                                            class="mb-1 text-base font-semibold text-gray-800 dark:text-gray-100">
                                                            No hay semestres disponibles
                                                        </div>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            Ajusta tu búsqueda.
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        @php
                                            $nivelActual = null;
                                        @endphp

                                        @foreach ($semestres as $key => $semestre)
                                            @php
                                                $nivelId = optional($semestre->grado->nivel)->id;
                                            @endphp

                                            {{-- Fila de cabecera de grupo por nivel --}}
                                            @if ($nivelId !== $nivelActual)
                                                <tr>
                                                    <td colspan="4" class="px-4 py-2">
                                                        <div
                                                            class="inline-flex items-center gap-2 rounded-full bg-indigo-50 dark:bg-indigo-950/40 px-3 py-1 shadow-sm ring-1 ring-indigo-100 dark:ring-indigo-900/60">
                                                            <span
                                                                class="h-2 w-2 rounded-full bg-indigo-500 dark:bg-indigo-400"></span>
                                                            <span
                                                                class="text-xs font-semibold tracking-wide uppercase text-indigo-700 dark:text-indigo-200">
                                                                BACHILLERATO
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @php
                                                    $nivelActual = $nivelId;
                                                @endphp
                                            @endif

                                            {{-- Fila principal del grado --}}
                                            <tr
                                                class="transition-colors duration-150 odd:bg-slate-50/80 even:bg-white dark:odd:bg-neutral-900/60 dark:even:bg-neutral-800/60 hover:bg-indigo-50/80 dark:hover:bg-indigo-950/40">
                                                <!-- # -->
                                                <td class="px-4 py-3 text-center text-gray-800 dark:text-gray-200">
                                                    <span
                                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white dark:bg-neutral-900 text-[11px] font-semibold text-indigo-700 dark:text-indigo-300 ring-1 ring-indigo-100 dark:ring-indigo-800">
                                                        {{ $key + 1 }}
                                                    </span>
                                                </td>

                                                <!-- Grado -->
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    <div class="flex flex-col">
                                                        <span class="font-semibold">
                                                            {{ $semestre->numero ? $semestre->numero . '° SEMESTRE' : '---' }}
                                                        </span>
                                                    </div>
                                                </td>

                                                <!-- Nivel -->
                                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-sky-50 dark:bg-sky-900/40 px-3 py-1 text-xs font-medium text-sky-700 dark:text-sky-200 ring-1 ring-sky-100 dark:ring-sky-800">

                                                        {{ $semestre->grado->nombre ? $semestre->grado->nombre . '° GRADO' : '---' }}
                                                    </span>
                                                </td>
                                                <!-- Meses -->
                                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/40 px-3 py-1 text-xs font-medium text-green-700 dark:text-green-200 ring-1 ring-green-100 dark:ring-green-800">

                                                        {{ $semestre->mesesBachillerato->meses ?? '---' }}
                                                    </span>
                                                </td>

                                                <!-- Acciones -->
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <flux:button variant="primary"
                                                            class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                                            @click="$dispatch('abrir-modal-editar');
                                                                Livewire.dispatch('editarModal', { id: {{ $semestre->id }} });
                                                            ">
                                                            <flux:icon.square-pen class="w-3.5 h-3.5" />
                                                            <!-- ícono -->
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

                    <!-- Tarjetas (mobile) -->
                    <div class="md:hidden space-y-3">
                        @if ($semestres->isEmpty())
                            <div
                                class="rounded-xl border border-dashed border-gray-300 dark:border-neutral-700 p-6 text-center">
                                <div class="mb-1 font-semibold text-gray-700 dark:text-gray-200">
                                    No hay semestres disponibles
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Ajusta tu búsqueda o importa datos.
                                </p>
                            </div>
                        @else
                            @foreach ($semestres as $key => $semestre)
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="space-y-2">
                                            <div
                                                class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 px-3 py-1 text-white text-xs font-medium shadow-sm">
                                                <span>#{{ $key + 1 }}</span>
                                            </div>
                                            <div>
                                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                    {{ $semestre->grado->nombre ?: '---' }}
                                                </h2>
                                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                                    Nivel: {{ $semestre->grado->nivel->nombre ?: '---' }}
                                                </p>
                                            </div>
                                            <div>
                                                <span
                                                    class="inline-flex items-center rounded-full bg-sky-50 dark:bg-sky-900/40 px-3 py-1 text-xs font-medium text-sky-700 dark:text-sky-200 ring-1 ring-sky-100 dark:ring-sky-800">

                                                    {{ $semestre->numero ? $semestre->numero . '° SEMESTRE' : '---' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-center gap-2">
                                            <flux:button variant="primary"
                                                class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                                @click="$dispatch('abrir-modal-editar');
                                                                Livewire.dispatch('editarModal', { id: {{ $semestre->id }} });
                                                            ">
                                                <flux:icon.square-pen class="w-3.5 h-3.5" />
                                                <!-- ícono -->
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
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
