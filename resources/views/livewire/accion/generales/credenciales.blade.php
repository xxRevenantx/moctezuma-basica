<div x-data="{
    guardarScroll() {
            localStorage.setItem('scroll_credenciales_{{ $slug_nivel }}', window.scrollY || 0);
        },

        restaurarScroll() {
            const posicion = localStorage.getItem('scroll_credenciales_{{ $slug_nivel }}');

            if (posicion !== null) {
                requestAnimationFrame(() => {
                    window.scrollTo(0, Number(posicion));
                });
            }
        }
}" x-init="document.addEventListener('livewire:init', () => {
    Livewire.hook('commit', ({ succeed }) => {
        guardarScroll();

        succeed(() => {
            setTimeout(() => restaurarScroll(), 30);
        });
    });
});" class="space-y-6">

    <section class="space-y-4">
        <article
            class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm transition-all duration-300 dark:border-neutral-800 dark:bg-neutral-900">

            <div class="bg-slate-50/70 p-4 dark:bg-neutral-950/30 sm:p-6">

                <div
                    class="relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

                    <div wire:loading.delay.flex
                        wire:target="generacion_id,grado_id,semestre_id,grupo_id,modo_descarga,buscar_alumno,alumno_individual_id,alumnos_seleccionados,limpiarFiltros,seleccionarTodosVisibles,limpiarSeleccion,quitarAlumnoSeleccionado"
                        class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                </path>
                            </svg>
                            Actualizando información...
                        </div>
                    </div>

                    <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-600 to-indigo-600"></div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                    Filtros de credenciales
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Selecciona el alcance de descarga y después aplica los filtros necesarios.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="limpiarSeleccion" x-on:click="guardarScroll()"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                    <flux:icon.x-mark class="h-4 w-4" />
                                    Limpiar selección
                                </button>

                                <button type="button" wire:click="limpiarFiltros" x-on:click="guardarScroll()"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                    <flux:icon.arrow-path class="h-4 w-4" />
                                    Limpiar filtros
                                </button>
                            </div>
                        </div>

                        @if ($modo_descarga === 'nivel')
                            <div
                                class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="font-black">
                                            Descarga por nivel activo
                                        </p>

                                        <p class="mt-1 text-sm">
                                            Se generarán las credenciales de todos los alumnos registrados en el nivel
                                            {{ $nivel?->nombre ?? 'seleccionado' }}.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($this->esBachillerato())
                            <div
                                class="mb-5 rounded-2xl border border-violet-200 bg-violet-50/80 p-4 text-sm text-violet-800 dark:border-violet-900/60 dark:bg-violet-950/30 dark:text-violet-200">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-200">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="font-black">
                                            Modo bachillerato activo
                                        </p>

                                        <p class="mt-1 text-sm">
                                            Para descargar por semestre o grupo, primero selecciona generación, grado y
                                            semestre.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <flux:field>
                                <flux:label>Nivel</flux:label>
                                <flux:input readonly variant="filled" value="{{ $nivel?->nombre ?? '—' }}" disabled />
                            </flux:field>

                            <flux:field>
                                <flux:label>Modo de descarga</flux:label>

                                <flux:select id="modo_descarga" wire:model.live="modo_descarga"
                                    x-on:change="guardarScroll()">
                                    @foreach ($this->modosDescarga() as $valor => $texto)
                                        <flux:select.option value="{{ $valor }}">
                                            {{ $texto }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="modo_descarga" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Generación</flux:label>

                                <flux:select id="generacion_id" wire:model.live="generacion_id"
                                    x-on:change="guardarScroll()" :disabled="$modo_descarga === 'nivel'">
                                    <flux:select.option value="">
                                        Selecciona una generación
                                    </flux:select.option>

                                    @foreach ($generaciones as $generacion)
                                        <flux:select.option value="{{ $generacion->id }}">
                                            {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="generacion_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Grado</flux:label>

                                <flux:select id="grado_id" wire:model.live="grado_id" x-on:change="guardarScroll()"
                                    :disabled="$modo_descarga === 'nivel' || !$generacion_id">
                                    <flux:select.option value="">
                                        Selecciona un grado
                                    </flux:select.option>

                                    @foreach ($grados as $grado)
                                        <flux:select.option value="{{ $grado->id }}">
                                            {{ $grado->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="grado_id" />
                            </flux:field>

                            @if ($this->esBachillerato())
                                <flux:field>
                                    <flux:label>Semestre</flux:label>

                                    <flux:select id="semestre_id" wire:model.live="semestre_id"
                                        x-on:change="guardarScroll()"
                                        :disabled="$modo_descarga === 'nivel' || !$generacion_id || !$grado_id || $semestres->isEmpty()">
                                        <flux:select.option value="">
                                            Selecciona un semestre
                                        </flux:select.option>

                                        @foreach ($semestres as $semestre)
                                            <flux:select.option value="{{ $semestre->id }}">
                                                {{ $this->textoSemestre($semestre) }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="semestre_id" />
                                </flux:field>
                            @endif

                            <flux:field>
                                <flux:label>Grupo</flux:label>

                                <flux:select id="grupo_id" wire:model.live="grupo_id" x-on:change="guardarScroll()"
                                    wire:key="credenciales-grupo-select-{{ $slug_nivel }}-{{ $generacion_id ?? 'null' }}-{{ $grado_id ?? 'null' }}-{{ $semestre_id ?? 'null' }}-{{ $grupos->count() }}"
                                    :disabled="$modo_descarga === 'nivel' || (
                                                                                                                $this->esBachillerato()
                                                                                                                    ? (!$generacion_id || !$grado_id || !$semestre_id || $grupos->isEmpty())
                                                                                                                    : (!$generacion_id || !$grado_id || $grupos->isEmpty())
                                                                                                            )">

                                    <flux:select.option value="">
                                        Selecciona un grupo
                                    </flux:select.option>

                                    @foreach ($grupos as $grupo)
                                        <flux:select.option value="{{ $grupo->id }}">
                                            {{ $this->textoGrupo($grupo) }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="grupo_id" />

                                @if ($modo_descarga !== 'nivel' && !$this->esBachillerato() && $generacion_id && $grado_id && $grupos->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        No hay grupos registrados para la generación y grado seleccionados.
                                    </p>
                                @endif

                                @if (
                                    $modo_descarga !== 'nivel' &&
                                        $this->esBachillerato() &&
                                        $generacion_id &&
                                        $grado_id &&
                                        $semestre_id &&
                                        $grupos->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        No hay grupos registrados para la generación, grado y semestre seleccionados.
                                    </p>
                                @endif
                            </flux:field>

                            <flux:field>
                                <flux:label>Buscar alumno</flux:label>
                                <flux:input type="search" wire:model.live.debounce.400ms="buscar_alumno"
                                    x-on:input="guardarScroll()" placeholder="Nombre, apellidos o matrícula..." />
                            </flux:field>

                            @if ($modo_descarga === 'individual')
                                <flux:field>
                                    <flux:label>Alumno individual</flux:label>

                                    <flux:select id="alumno_individual_id" wire:model.live="alumno_individual_id"
                                        x-on:change="guardarScroll()">
                                        <flux:select.option value="">
                                            Selecciona un alumno
                                        </flux:select.option>

                                        @foreach ($this->alumnos as $alumno)
                                            <flux:select.option value="{{ $alumno->id }}">
                                                {{ $alumno->matricula ?? 'S/M' }} — {{ $this->nombreAlumno($alumno) }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="alumno_individual_id" />
                                </flux:field>
                            @endif
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                Modo: {{ $this->textoModoDescarga }}
                            </span>

                            @if ($modo_descarga === 'nivel')
                                <span
                                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    Nivel completo: {{ $nivel?->nombre ?? '—' }}
                                </span>
                            @endif

                            @if ($this->generacionSeleccionada && $modo_descarga !== 'nivel')
                                <span
                                    class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300">
                                    Generación:
                                    {{ $this->generacionSeleccionada->anio_ingreso }} -
                                    {{ $this->generacionSeleccionada->anio_egreso }}
                                </span>
                            @endif

                            @if ($this->gradoSeleccionado && $modo_descarga !== 'nivel')
                                <span
                                    class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                    Grado: {{ $this->gradoSeleccionado->nombre }}
                                </span>
                            @endif

                            @if ($this->esBachillerato() && $this->semestreSeleccionado && $modo_descarga !== 'nivel')
                                <span
                                    class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                    {{ $this->textoSemestre($this->semestreSeleccionado) }}
                                </span>
                            @endif

                            @if ($this->grupoSeleccionado && $modo_descarga !== 'nivel')
                                <span
                                    class="inline-flex items-center rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700 dark:border-cyan-900/40 dark:bg-cyan-950/30 dark:text-cyan-300">
                                    Grupo: {{ $this->textoGrupo($this->grupoSeleccionado) }}
                                </span>
                            @endif

                            @if ($modo_descarga === 'seleccionados')
                                <span
                                    class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Seleccionados: {{ count($alumnos_seleccionados) }}
                                </span>
                            @endif

                            @if ($this->puedeDescargar)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    <flux:icon.check-circle class="h-4 w-4" />
                                    Listo para descargar
                                </span>
                            @endif
                        </div>

                        @if ($modo_descarga === 'seleccionados')
                            <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-12">
                                <div
                                    class="overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900 xl:col-span-7">
                                    <div
                                        class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                                Resultados de búsqueda
                                            </h4>

                                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                Marca alumnos desde la búsqueda. Los seleccionados se conservarán aunque
                                                busques otro alumno.
                                            </p>
                                        </div>

                                        <button type="button" wire:click="seleccionarTodosVisibles"
                                            x-on:click="guardarScroll()"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 dark:bg-white dark:text-slate-900">
                                            <flux:icon.check class="h-4 w-4" />
                                            Seleccionar visibles
                                        </button>
                                    </div>

                                    <div class="max-h-[430px] overflow-y-auto">
                                        <table
                                            class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                                            <thead class="sticky top-0 z-10 bg-slate-100 dark:bg-neutral-950">
                                                <tr>
                                                    <th class="w-12 px-4 py-3 text-left"></th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                        Matrícula
                                                    </th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                        Alumno
                                                    </th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                        Grupo
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                                @forelse ($this->alumnos as $alumno)
                                                    <tr wire:key="resultado-alumno-{{ $alumno->id }}"
                                                        class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/60">
                                                        <td class="px-4 py-3">
                                                            <input type="checkbox" value="{{ $alumno->id }}"
                                                                wire:model.live="alumnos_seleccionados"
                                                                x-on:change="guardarScroll()"
                                                                class="h-4 w-4 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 dark:border-neutral-700">
                                                        </td>

                                                        <td
                                                            class="px-4 py-3 font-bold text-slate-700 dark:text-slate-200">
                                                            {{ $alumno->matricula ?? 'S/M' }}
                                                        </td>

                                                        <td class="px-4 py-3">
                                                            <p class="font-black text-slate-900 dark:text-white">
                                                                {{ $this->nombreAlumno($alumno) }}
                                                            </p>

                                                            <div class="mt-1 flex flex-wrap gap-1.5">
                                                                @if ($alumno->generacion)
                                                                    <span
                                                                        class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-black text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900/40">
                                                                        {{ $alumno->generacion->anio_ingreso }} -
                                                                        {{ $alumno->generacion->anio_egreso }}
                                                                    </span>
                                                                @endif

                                                                @if ($alumno->grado)
                                                                    <span
                                                                        class="rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-black text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/40">
                                                                        {{ $alumno->grado->nombre }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </td>

                                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                            {{ $alumno->grupo?->asignacionGrupo?->nombre ?? '—' }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="px-4 py-10 text-center">
                                                            <p class="font-black text-slate-700 dark:text-slate-200">
                                                                No hay alumnos para mostrar.
                                                            </p>

                                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                                Ajusta los filtros o escribe en el buscador.
                                                            </p>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div
                                    class="overflow-hidden rounded-[1.4rem] border border-emerald-200 bg-emerald-50/50 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/10 xl:col-span-5">
                                    <div
                                        class="border-b border-emerald-200 bg-gradient-to-r from-emerald-500 via-sky-600 to-indigo-600 p-4 text-white dark:border-emerald-900/50">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <h4 class="text-sm font-black">
                                                    Alumnos agregados para descargar
                                                </h4>

                                                <p class="mt-1 text-xs font-semibold text-white/80">
                                                    {{ count($alumnos_seleccionados) }} alumno(s) seleccionado(s).
                                                </p>
                                            </div>

                                            <button type="button" wire:click="limpiarSeleccion"
                                                x-on:click="guardarScroll()"
                                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-black text-white ring-1 ring-white/20 transition hover:bg-white/25">
                                                <flux:icon.x-mark class="h-4 w-4" />
                                                Limpiar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="max-h-[430px] overflow-y-auto">
                                        <table
                                            class="min-w-full divide-y divide-emerald-100 text-sm dark:divide-neutral-800">
                                            <thead class="sticky top-0 z-10 bg-emerald-50 dark:bg-neutral-950">
                                                <tr>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                                        Alumno
                                                    </th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                                        Matrícula
                                                    </th>
                                                    <th class="w-12 px-4 py-3 text-right"></th>
                                                </tr>
                                            </thead>

                                            <tbody
                                                class="divide-y divide-emerald-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                                                @forelse ($this->alumnosSeleccionadosLista as $alumno)
                                                    <tr wire:key="seleccionado-alumno-{{ $alumno->id }}"
                                                        class="transition hover:bg-emerald-50/70 dark:hover:bg-neutral-800/60">
                                                        <td class="px-4 py-3">
                                                            <p class="font-black text-slate-900 dark:text-white">
                                                                {{ $this->nombreAlumno($alumno) }}
                                                            </p>

                                                            <p
                                                                class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                                {{ $alumno->grado?->nombre ?? 'Sin grado' }}
                                                                ·
                                                                {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo' }}
                                                            </p>
                                                        </td>

                                                        <td
                                                            class="px-4 py-3 font-bold text-slate-700 dark:text-slate-200">
                                                            {{ $alumno->matricula ?? 'S/M' }}
                                                        </td>

                                                        <td class="px-4 py-3 text-right">
                                                            <button type="button"
                                                                wire:click="quitarAlumnoSeleccionado({{ $alumno->id }})"
                                                                x-on:click="guardarScroll()"
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
                                                                <flux:icon.trash class="h-4 w-4" />
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="px-4 py-10 text-center">
                                                            <div
                                                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                                <flux:icon.user-plus class="h-5 w-5" />
                                                            </div>

                                                            <p
                                                                class="mt-3 font-black text-slate-700 dark:text-slate-200">
                                                                Todavía no hay alumnos seleccionados.
                                                            </p>

                                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                                Marca alumnos desde la tabla de resultados.
                                                            </p>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div
                            class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">
                                            Estado de la descarga
                                        </p>

                                        @if ($this->puedeDescargar)
                                            <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">
                                                Ya puedes descargar las credenciales.
                                            </p>

                                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                Alcance seleccionado: {{ $this->textoModoDescarga }}.
                                            </p>

                                            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                                Si algún alumno no tiene fotografía, se generará con el espacio “FOTO + SELLO” y el ZIP incluirá una advertencia.
                                            </p>
                                        @else
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                Completa los campos necesarios según el modo de descarga.
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if ($this->puedeDescargar)
                                    <div x-data="{ formatos: false, previa: false }" class="relative flex flex-wrap items-center justify-end gap-2">
                                        <button type="button" x-on:click="previa = true"
                                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            <flux:icon.eye class="h-5 w-5" />
                                            Vista previa
                                        </button>

                                        <div class="relative">
                                            <button type="button" x-on:click="formatos = !formatos"
                                                x-on:click.outside="formatos = false"
                                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 via-sky-600 to-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-500/20 transition hover:-translate-y-0.5 hover:shadow-xl">
                                                <flux:icon.document-arrow-down class="h-5 w-5" />
                                                Descargar
                                                <flux:icon.chevron-down class="h-4 w-4" />
                                            </button>

                                            <div x-cloak x-show="formatos" x-transition
                                                class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">
                                                <a href="{{ $this->urlDescarga }}" target="_blank"
                                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                    <flux:icon.document-text class="h-5 w-5 text-rose-500" />
                                                    Formato PDF
                                                </a>
                                                <a href="{{ $this->urlDescargaPng }}"
                                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                    <flux:icon.photo class="h-5 w-5 text-emerald-500" />
                                                    Imagen PNG
                                                </a>
                                                <a href="{{ $this->urlDescargaJpg }}"
                                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                    <flux:icon.photo class="h-5 w-5 text-sky-500" />
                                                    Imagen JPG · 100%
                                                </a>
                                            </div>
                                        </div>

                                        <template x-teleport="body">
                                            <div x-cloak x-show="previa" x-transition.opacity
                                                x-on:keydown.escape.window="previa = false"
                                                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm">
                                                <div x-on:click.outside="previa = false"
                                                    class="w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
                                                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-neutral-800">
                                                        <div>
                                                            <p class="font-black text-slate-900 dark:text-white">Vista previa de credencial</p>
                                                            <p class="text-xs text-slate-500">Se muestra la primera credencial del alcance seleccionado.</p>
                                                        </div>
                                                        <button type="button" x-on:click="previa = false"
                                                            class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-neutral-800">
                                                            <flux:icon.x-mark class="h-5 w-5" />
                                                        </button>
                                                    </div>
                                                    <div class="bg-slate-100 p-4 dark:bg-neutral-950">
                                                        <img x-bind:src="previa ? @js($this->urlVistaPrevia) : ''" alt="Vista previa de la credencial"
                                                            class="mx-auto h-auto max-h-[75vh] w-full rounded-xl object-contain shadow-lg">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                @else
                                    <button type="button" disabled
                                        class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-200 px-5 py-3 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-neutral-500">
                                        <flux:icon.lock-closed class="h-5 w-5" />
                                        Descargar credenciales
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </section>
</div>
