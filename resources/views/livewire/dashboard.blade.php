<div class="space-y-6">

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
