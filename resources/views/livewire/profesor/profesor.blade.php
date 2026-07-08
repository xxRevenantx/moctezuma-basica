<div x-data="panelProfesoresPro(@js(request('seccion', 'lista')))" class="min-h-screen dark:bg-zinc-950 sm:px-6">
    <div class="mx-auto space-y-6">

        {{-- Encabezado principal --}}
        <section
            class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

            <div class="relative p-6 sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-300">
                            <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                            Módulo de profesores
                        </div>

                        <div>
                            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                                Gestión de profesores
                            </h1>

                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-zinc-400">
                                Consulta profesores, revisa sus materias, genera listas académicas y descarga
                                credenciales institucionales.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-center dark:border-zinc-800 dark:bg-zinc-950/60">
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400">
                                Consulta
                            </p>
                            <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">
                                Profesores
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-center dark:border-zinc-800 dark:bg-zinc-950/60">
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400">
                                Académico
                            </p>
                            <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">
                                Materias y listas
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-center dark:border-zinc-800 dark:bg-zinc-950/60">
                            <p class="text-xs font-bold text-slate-500 dark:text-zinc-400">
                                Descarga
                            </p>
                            <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">
                                Credenciales
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Accesos rápidos --}}
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <button type="button" x-on:click.prevent="cambiar('lista')"
                x-bind:style="abierto === 'lista' ?
                    'background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff;' : ''"
                class="group rounded-3xl border p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                x-bind:class="abierto === 'lista'
                    ?
                    'border-emerald-500 bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/20 dark:border-emerald-400 dark:from-emerald-600 dark:to-emerald-700' :
                    'border-emerald-200 bg-white hover:border-emerald-300 dark:border-emerald-900/40 dark:bg-zinc-900 dark:hover:border-emerald-800'">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl transition"
                            x-bind:class="abierto === 'lista'
                                ?
                                'bg-white/20 text-white ring-1 ring-white/30' :
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'">
                            <flux:icon.users class="h-5 w-5" />
                        </div>

                        <h2 class="mt-4 text-base font-black transition"
                            x-bind:class="abierto === 'lista' ? 'text-white' : 'text-slate-900 dark:text-white'">
                            Lista de profesores
                        </h2>

                        <p class="mt-1 text-sm transition"
                            x-bind:class="abierto === 'lista' ? 'text-emerald-50' : 'text-slate-500 dark:text-zinc-400'">
                            Consulta y administra el personal docente registrado.
                        </p>
                    </div>

                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border transition"
                        x-bind:class="abierto === 'lista'
                            ?
                            'rotate-180 border-white/30 bg-white/20 text-white' :
                            'border-slate-200 bg-slate-50 text-slate-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400'">
                        <flux:icon.chevron-down class="h-5 w-5" />
                    </span>
                </div>
            </button>



            <button type="button" x-on:click.prevent="cambiar('credenciales')"
                x-bind:style="abierto === 'credenciales' ?
                    'background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #ffffff;' : ''"
                class="group rounded-3xl border p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                x-bind:class="abierto === 'credenciales'
                    ?
                    'border-blue-500 bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/20 dark:border-blue-400 dark:from-blue-600 dark:to-blue-700' :
                    'border-blue-200 bg-white hover:border-blue-300 dark:border-blue-900/40 dark:bg-zinc-900 dark:hover:border-blue-800'">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl transition"
                            x-bind:class="abierto === 'credenciales'
                                ?
                                'bg-white/20 text-white ring-1 ring-white/30' :
                                'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300'">
                            <flux:icon.identification class="h-5 w-5" />
                        </div>

                        <h2 class="mt-4 text-base font-black transition"
                            x-bind:class="abierto === 'credenciales' ? 'text-white' : 'text-slate-900 dark:text-white'">
                            Credenciales
                        </h2>

                        <p class="mt-1 text-sm transition"
                            x-bind:class="abierto === 'credenciales' ? 'text-blue-50' : 'text-slate-500 dark:text-zinc-400'">
                            Genera credenciales del personal por nivel académico.
                        </p>
                    </div>

                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border transition"
                        x-bind:class="abierto === 'credenciales'
                            ?
                            'rotate-180 border-white/30 bg-white/20 text-white' :
                            'border-slate-200 bg-slate-50 text-slate-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400'">
                        <flux:icon.chevron-down class="h-5 w-5" />
                    </span>
                </div>
            </button>

            <button type="button" x-on:click.prevent="cambiar('horario')"
                x-bind:style="abierto === 'horario' ?
                    'background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%); color: #ffffff;' : ''"
                class="group rounded-3xl border p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                x-bind:class="abierto === 'horario'
                    ?
                    'border-indigo-500 bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-500/20 dark:border-indigo-400 dark:from-indigo-600 dark:to-violet-700' :
                    'border-indigo-200 bg-white hover:border-indigo-300 dark:border-indigo-900/40 dark:bg-zinc-900 dark:hover:border-indigo-800'">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl transition"
                            x-bind:class="abierto === 'horario'
                                ?
                                'bg-white/20 text-white ring-1 ring-white/30' :
                                'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'">
                            <flux:icon.calendar-days class="h-5 w-5" />
                        </div>

                        <h2 class="mt-4 text-base font-black transition"
                            x-bind:class="abierto === 'horario' ? 'text-white' : 'text-slate-900 dark:text-white'">
                            Horario docente
                        </h2>

                        <p class="mt-1 text-sm transition"
                            x-bind:class="abierto === 'horario' ? 'text-indigo-50' : 'text-slate-500 dark:text-zinc-400'">
                            Consulta el horario del profesor por nivel o completo.
                        </p>
                    </div>

                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border transition"
                        x-bind:class="abierto === 'horario'
                            ?
                            'rotate-180 border-white/30 bg-white/20 text-white' :
                            'border-slate-200 bg-slate-50 text-slate-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400'">
                        <flux:icon.chevron-down class="h-5 w-5" />
                    </span>
                </div>
            </button>


        </section>

        {{-- Contenido de collapses --}}
        <section class="space-y-5">

            {{-- Lista de profesores --}}
            <div x-cloak x-show="abierto === 'lista'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div
                    class="border-b border-slate-200 bg-gradient-to-r from-emerald-50 via-white to-slate-50 px-5 py-4 dark:border-zinc-800 dark:from-emerald-950/20 dark:via-zinc-900 dark:to-zinc-900">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-black text-slate-900 dark:text-white">
                                Lista de profesores
                            </h2>

                            <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
                                Visualiza y consulta la información registrada del personal docente.
                            </p>
                        </div>

                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Información docente
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <livewire:profesor.listas-profesores wire:key="profesor-listas" />
                </div>
            </div>

            {{-- Credenciales --}}
            <div x-cloak x-show="abierto === 'credenciales'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div
                    class="border-b border-slate-200 bg-gradient-to-r from-blue-50 via-white to-indigo-50 px-5 py-4 dark:border-zinc-800 dark:from-blue-950/20 dark:via-zinc-900 dark:to-indigo-950/20">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-black text-slate-900 dark:text-white">
                                Credenciales de profesores
                            </h2>

                            <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
                                Genera, consulta o descarga credenciales del personal docente.
                            </p>
                        </div>

                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            Credencial institucional
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <livewire:profesor.credencial-profesor wire:key="profesor-credenciales" />
                </div>
            </div>


            {{-- Horario docente --}}
            <div x-cloak x-show="abierto === 'horario'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div
                    class="border-b border-slate-200 bg-gradient-to-r from-indigo-50 via-white to-sky-50 px-5 py-4 dark:border-zinc-800 dark:from-indigo-950/20 dark:via-zinc-900 dark:to-sky-950/20">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-black text-slate-900 dark:text-white">
                                Horario del profesor
                            </h2>

                            <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
                                Consulta el horario docente por nivel académico o descarga el horario completo en PDF.
                            </p>
                        </div>

                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                            Horario académico
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <livewire:profesor.horario-profesor wire:key="profesor-horario" />
                </div>
            </div>

        </section>
    </div>

    @script
        <script>
            Alpine.data('panelProfesoresPro', (seccionInicial = 'lista') => ({
                abierto: ['lista', 'credenciales', 'horario'].includes(seccionInicial) ?
                    seccionInicial : 'lista',

                cambiar(seccion) {
                    this.abierto = seccion;
                },
            }));
        </script>
    @endscript
</div>
