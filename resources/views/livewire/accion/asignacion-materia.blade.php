<div x-data="{
    mostrarFormulario: true,
    mostrarMateriasPromediar: false,

    init() {
        window.addEventListener('abrir-formulario-materia', () => {
            this.mostrarFormulario = true;

            this.$nextTick(() => {
                const panel = document.getElementById('panel-asignacion-materia');

                if (panel) {
                    panel.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        window.addEventListener('cerrar-formulario-materia', () => {
            this.mostrarFormulario = false;
        });

        window.addEventListener('scroll-editar-materia', () => {
            this.mostrarFormulario = true;

            this.$nextTick(() => {
                const panel = document.getElementById('panel-asignacion-materia');

                if (panel) {
                    panel.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        window.addEventListener('toggle-materias-promediar', () => {
            this.mostrarMateriasPromediar = !this.mostrarMateriasPromediar;

            if (this.mostrarMateriasPromediar) {
                this.$nextTick(() => {
                    const panel = document.getElementById('panel-materias-promediar');

                    if (panel) {
                        panel.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            }
        });
    }
}" class="space-y-8">
    {{-- Navegación de niveles --}}
    <div class="flex flex-wrap justify-center gap-3">
        @foreach ($niveles as $item)
            <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'asignacion-de-materias']) }}"
                @class([
                    'group flex items-center gap-3 rounded-2xl border px-5 py-4 text-sm font-semibold transition-all duration-300',
                    'bg-gradient-to-r from-sky-500 to-indigo-600 text-white shadow-lg shadow-sky-500/25 border-transparent scale-[1.02]' =>
                        $slug_nivel === $item->slug,
                    'bg-white text-slate-600 border-slate-200 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-800' =>
                        $slug_nivel !== $item->slug,
                ])>
                <span @class([
                    'flex h-9 w-9 items-center justify-center rounded-xl transition',
                    'bg-white/15 text-white' => $slug_nivel === $item->slug,
                    'bg-slate-100 text-slate-500 group-hover:bg-sky-100 group-hover:text-sky-600 dark:bg-slate-800 dark:text-slate-300' =>
                        $slug_nivel !== $item->slug,
                ])>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15Z" />
                    </svg>
                </span>

                <span>{{ $item->nombre }}</span>

                @if ($slug_nivel === $item->slug)
                    <span class="rounded-full bg-white/20 px-2 py-0.5 text-[11px] font-bold">
                        Activo
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Encabezado --}}
    <section
        class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/80">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-xl">
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                    Asignación de materias
                </h1>

                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Asigna materias del catálogo a un grupo y profesor. El grado, generación y semestre se toman
                    automáticamente del grupo.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <flux:input wire:model.live.debounce.400ms="buscar"
                    placeholder="Buscar por materia, profesor, grado o grupo..." class="w-full sm:w-80" />

                <button type="button" x-on:click="mostrarFormulario = !mostrarFormulario"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-sky-500/20 transition hover:scale-[1.01] hover:shadow-xl">
                    <span x-show="mostrarFormulario">×</span>
                    <span x-show="!mostrarFormulario">+</span>
                    <span x-text="mostrarFormulario ? 'Ocultar asignación' : 'Nueva asignación'"></span>
                </button>

                @if ($nivel?->slug === 'secundaria')
                    <button type="button" x-on:click="$dispatch('abrir-taller-conjunto')"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-500 to-sky-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/20 transition hover:scale-[1.01] hover:shadow-xl">
                        <flux:icon.user-group class="h-4 w-4" />
                        Taller conjunto
                    </button>
                @endif

                <button type="button" x-on:click="$dispatch('toggle-materias-promediar')"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-pink-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-pink-500/20 transition hover:scale-[1.01] hover:shadow-xl">
                    <svg class="h-4 w-4 transition-transform duration-300"
                        :class="mostrarMateriasPromediar ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                        stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                    </svg>

                    <span
                        x-text="mostrarMateriasPromediar ? 'Ocultar materias a promediar' : 'Materias a promediar'"></span>
                </button>
            </div>
        </div>
    </section>

    @if ($nivel?->slug === 'secundaria')
        <livewire:accion.taller-conjunto :slug_nivel="$slug_nivel" :key="'taller-conjunto-asignacion-' . $nivel->id" />
    @endif

    {{-- Collapse de materias a promediar --}}
    <section id="panel-materias-promediar" x-show="mostrarMateriasPromediar" x-cloak
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4"
        class="overflow-hidden rounded-3xl border border-violet-200 bg-white shadow-sm dark:border-violet-900 dark:bg-slate-950">
        <div class="h-1.5 bg-gradient-to-r from-violet-500 via-fuchsia-500 to-pink-500"></div>

        <div class="border-b border-slate-200 p-6 dark:border-slate-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">
                        Materias a promediar
                    </h2>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Configura cuántas materias se tomarán en cuenta para calcular el promedio del grupo.
                    </p>
                </div>

                <button type="button" x-on:click="mostrarMateriasPromediar = false"
                    class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-500 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                    Cerrar
                </button>
            </div>
        </div>

        <div class="p-6">
            @livewire('materia-promediar', ['slug_nivel' => $slug_nivel], key('materia-promediar-' . $slug_nivel))
        </div>
    </section>

    {{-- Formulario --}}
    <section id="panel-asignacion-materia" x-show="mostrarFormulario"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4"
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="h-1.5 bg-gradient-to-r from-sky-500 via-indigo-600 to-violet-600"></div>

        <div class="border-b border-slate-200 p-6 dark:border-slate-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">
                        {{ $editandoId ? 'Editar asignación de materia' : 'Nueva asignación de materia' }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Selecciona un grupo. Las materias disponibles se filtrarán automáticamente según el nivel, grado
                        y semestre del grupo.
                    </p>
                </div>

                <span
                    class="inline-flex w-fit items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-300">
                    {{ $nivel?->nombre }}
                </span>
            </div>
        </div>

        <form wire:submit.prevent="guardarMateria" class="p-6">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                {{-- Grupo --}}
                <flux:field class="md:col-span-2 xl:col-span-2">
                    <flux:label>Grupo</flux:label>

                    <flux:select wire:model.live="grupo_id">
                        <option value="">Selecciona un grupo</option>

                        @foreach ($grupos as $item)
                            <option value="{{ $item['id'] }}">
                                {{ $item['nombre'] }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:error name="grupo_id" />
                </flux:field>

                {{-- Materia --}}
                <flux:field class="md:col-span-2 xl:col-span-2">
                    <flux:label>Materia</flux:label>

                    <flux:select wire:model.live="materia_id" :disabled="blank($grupo_id)">
                        <option value="">
                            {{ blank($grupo_id) ? 'Primero selecciona un grupo' : 'Selecciona una materia' }}
                        </option>

                        @foreach ($this->materiasDisponibles as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->materia }}

                                @if ($item->clave)
                                    - {{ $item->clave }}
                                @endif

                                @if ($item->extra)
                                    - Extra
                                @endif

                                @if ($item->receso)
                                    - Receso
                                @endif
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:error name="materia_id" />
                </flux:field>

                {{-- Datos detectados del grupo --}}
                @if ($this->grupoSeleccionado)
                    <div class="md:col-span-2 xl:col-span-4">
                        <div
                            class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/60 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">
                                    Grado
                                </p>

                                <p class="mt-1 text-sm font-bold text-slate-700 dark:text-slate-200">
                                    {{ $this->grupoSeleccionado?->grado?->nombre ?? 'Sin grado' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">
                                    Grupo
                                </p>

                                <p class="mt-1 text-sm font-bold text-slate-700 dark:text-slate-200">
                                    {{ $this->grupoSeleccionado?->asignacionGrupo?->nombre ?? 'Sin grupo' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">
                                    Generación
                                </p>

                                <p class="mt-1 text-sm font-bold text-slate-700 dark:text-slate-200">
                                    @if ($this->grupoSeleccionado?->generacion)
                                        {{ $this->grupoSeleccionado->generacion->anio_ingreso }}
                                        -
                                        {{ $this->grupoSeleccionado->generacion->anio_egreso }}
                                    @else
                                        Sin generación
                                    @endif
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">
                                    Semestre
                                </p>

                                <p class="mt-1 text-sm font-bold text-slate-700 dark:text-slate-200">
                                    {{ $this->grupoSeleccionado?->semestre?->numero ? $this->grupoSeleccionado->semestre->numero . '° semestre' : 'No aplica' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Profesor --}}
                <div x-data="{
                    abierto: false
                }" class="relative md:col-span-2 xl:col-span-3">
                    <flux:field>
                        <flux:label>Profesor</flux:label>

                        <flux:input wire:model.live.debounce.300ms="buscarProfesor" x-on:focus="abierto = true"
                            x-on:click.outside="abierto = false"
                            placeholder="Buscar profesor por nombre o apellido..." autocomplete="off" />

                        <flux:error name="profesor_id" />
                    </flux:field>

                    <div x-show="abierto" x-transition
                        class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                        @forelse ($this->profesoresFiltrados as $profesor)
                            <button type="button" wire:click="$set('profesor_id', {{ $profesor['id'] }})"
                                x-on:click="
                                    abierto = false;
                                    $wire.set('buscarProfesor', @js($profesor['nombre']));
                                "
                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm transition hover:bg-sky-50 dark:hover:bg-slate-800">
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-100 text-xs font-black text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                                    {{ mb_substr($profesor['nombre'], 0, 1) }}
                                </span>

                                <span class="font-semibold text-slate-700 dark:text-slate-200">
                                    {{ $profesor['nombre'] }}
                                </span>
                            </button>
                        @empty
                            <div
                                class="rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500 dark:border-slate-700">
                                No se encontraron profesores para este nivel.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Profesor seleccionado --}}
                <div class="md:col-span-2 xl:col-span-1">
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">
                            Profesor seleccionado
                        </p>

                        <p class="mt-1 text-sm font-bold text-slate-700 dark:text-slate-200">
                            {{ $profesor_id ? 'ID: ' . $profesor_id : 'Sin seleccionar' }}
                        </p>

                        @if ($profesor_id)
                            <button type="button" wire:click="$set('profesor_id', null)"
                                x-on:click="$wire.set('buscarProfesor', '')"
                                class="mt-2 text-xs font-bold text-red-500 hover:text-red-600">
                                Quitar profesor
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div
                class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
                <button type="button" wire:click="limpiarFormulario"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                    Cancelar
                </button>

                <button type="submit" wire:loading.attr="disabled" wire:target="guardarMateria"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/20 transition hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-slate-900">
                    <svg wire:loading wire:target="guardarMateria" class="h-4 w-4 animate-spin" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" />
                    </svg>

                    <span>
                        {{ $editandoId ? 'Actualizar asignación' : 'Guardar asignación' }}
                    </span>
                </button>
            </div>
        </form>
    </section>

    {{-- Listado --}}
    <section
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div
            class="flex flex-col gap-3 border-b border-slate-200 p-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white">
                    Materias asignadas
                </h2>

                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Listado de materias asignadas al nivel {{ $nivel?->nombre }}.
                </p>
            </div>

            <span
                class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                {{ $this->asignacionesFiltradas->count() }} registros
            </span>
        </div>

        <div class="space-y-6 p-6">
            @php
                $asignacionesPorGeneracion = $this->asignacionesFiltradas->groupBy(function ($item) {
                    $generacionId = $item->grupo?->generacion?->id ?? 0;

                    return 'generacion_' . $generacionId;
                });
            @endphp

            @forelse ($asignacionesPorGeneracion as $generacionKey => $materiasGeneracion)
                @php
                    $generacionBase = $materiasGeneracion->first()?->grupo?->generacion;

                    $nombreGeneracion = $generacionBase
                        ? $generacionBase->anio_ingreso . ' - ' . $generacionBase->anio_egreso
                        : 'Sin generación';

                    $gruposDeGeneracion = $materiasGeneracion->groupBy(function ($item) {
                        $gradoId = $item->grupo?->grado_id ?? 0;
                        $grupoId = $item->grupo?->asignacion_grupo_id ?? 0;

                        return 'grado_' . $gradoId . '_grupo_' . $grupoId;
                    });
                @endphp

                {{-- Collapse principal de generación --}}
                <div x-data="{ abiertoGeneracion: false }"
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                    <button type="button" x-on:click="abiertoGeneracion = !abiertoGeneracion"
                        class="flex w-full flex-col gap-3 border-b border-slate-200 bg-white px-5 py-5 text-left transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-sky-100 text-sm font-black text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                                    G
                                </span>

                                <div>
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                        Generación {{ $nombreGeneracion }}
                                    </h3>

                                    <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                        Contiene {{ $gruposDeGeneracion->count() }} grupo(s) y
                                        {{ $materiasGeneracion->count() }} materia(s).
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ $materiasGeneracion->count() }} materias
                            </span>

                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                <svg class="h-4 w-4 transition-transform duration-300"
                                    :class="abiertoGeneracion ? 'rotate-180' : ''" fill="none"
                                    stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </span>
                        </div>
                    </button>

                    <div x-show="abiertoGeneracion" x-cloak x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-3"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-3" class="space-y-5 p-5">

                        @foreach ($gruposDeGeneracion as $grupoKey => $materiasGrupo)
                            @php
                                $grupoBase = $materiasGrupo->first()?->grupo;

                                $nombreGrado = $grupoBase?->grado?->nombre ?? 'Sin grado';
                                $nombreGrupo = $grupoBase?->asignacionGrupo?->nombre ?? 'Sin grupo';

                                if ((int) $nivel?->id === 4) {
                                    $semestresDelGrupo = $materiasGrupo->groupBy(function ($item) {
                                        return 'semestre_' . ($item->grupo?->semestre_id ?? 0);
                                    });
                                } else {
                                    $semestresDelGrupo = collect([
                                        'basica' => $materiasGrupo,
                                    ]);
                                }
                            @endphp

                            {{-- Subcollapse de grado y grupo --}}
                            <div x-data="{ abiertoGrupo: false }"
                                class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                                <button type="button" x-on:click="abiertoGrupo = !abiertoGrupo"
                                    class="flex w-full flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 text-left transition hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-900/70 dark:hover:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span
                                                class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                                {{ $nombreGrado }}
                                            </span>

                                            <span
                                                class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                Grupo {{ $nombreGrupo }}
                                            </span>

                                            @if ((int) $nivel?->id === 4)
                                                <span
                                                    class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                                    Bachillerato
                                                </span>
                                            @endif
                                        </div>

                                        <h4 class="mt-2 text-base font-black text-slate-900 dark:text-white">
                                            {{ $nombreGrado }} / Grupo {{ $nombreGrupo }}
                                        </h4>

                                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                            @if ((int) $nivel?->id === 4)
                                                Agrupado por semestres dentro de la misma generación.
                                            @else
                                                Agrupado por grado y grupo dentro de la misma generación.
                                            @endif
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span
                                            class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600 shadow-sm dark:bg-slate-800 dark:text-slate-300">
                                            {{ $materiasGrupo->count() }} materias
                                        </span>

                                        <span
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                            <svg class="h-4 w-4 transition-transform duration-300"
                                                :class="abiertoGrupo ? 'rotate-180' : ''" fill="none"
                                                stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m6 9 6 6 6-6" />
                                            </svg>
                                        </span>
                                    </div>
                                </button>

                                <div x-show="abiertoGrupo" x-cloak
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 -translate-y-3"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-3" class="space-y-4 p-4">

                                    @foreach ($semestresDelGrupo as $semestreKey => $materiasSemestre)
                                        @php
                                            $grupoSemestre = $materiasSemestre->first()?->grupo;
                                            $grupoId = $grupoSemestre?->id;

                                            $nombreSemestre = $grupoSemestre?->semestre
                                                ? $grupoSemestre->semestre->numero . '° semestre'
                                                : 'Materias del grupo';
                                        @endphp

                                        {{-- Subcollapse de semestre --}}
                                        <div x-data="{ abiertoSemestre: false }"
                                            class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                                            <button type="button" x-on:click="abiertoSemestre = !abiertoSemestre"
                                                class="flex w-full flex-col gap-3 border-b border-slate-200 bg-white px-5 py-4 text-left transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span
                                                            class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                                                            Generación {{ $nombreGeneracion }}
                                                        </span>

                                                        <span
                                                            class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                                            {{ $nombreGrado }}
                                                        </span>

                                                        <span
                                                            class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                            Grupo {{ $nombreGrupo }}
                                                        </span>

                                                        @if ((int) $nivel?->id === 4)
                                                            <span
                                                                class="inline-flex rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-700 dark:bg-violet-950 dark:text-violet-300">
                                                                {{ $nombreSemestre }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <h5 class="mt-2 text-sm font-black text-slate-900 dark:text-white">
                                                        @if ((int) $nivel?->id === 4)
                                                            {{ $nombreSemestre }}
                                                        @else
                                                            Materias asignadas
                                                        @endif
                                                    </h5>
                                                </div>

                                                <div class="flex items-center gap-3">
                                                    <span
                                                        class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                        {{ $materiasSemestre->count() }} materias
                                                    </span>

                                                    <span
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                                        <svg class="h-4 w-4 transition-transform duration-300"
                                                            :class="abiertoSemestre ? 'rotate-180' : ''" fill="none"
                                                            stroke="currentColor" stroke-width="1.8"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="m6 9 6 6 6-6" />
                                                        </svg>
                                                    </span>
                                                </div>
                                            </button>

                                            <div x-show="abiertoSemestre" x-cloak
                                                x-transition:enter="transition ease-out duration-300"
                                                x-transition:enter-start="opacity-0 -translate-y-3"
                                                x-transition:enter-end="opacity-100 translate-y-0"
                                                x-transition:leave="transition ease-in duration-200"
                                                x-transition:leave-start="opacity-100 translate-y-0"
                                                x-transition:leave-end="opacity-0 -translate-y-3">

                                                {{-- Tabla escritorio --}}
                                                <div class="hidden overflow-x-auto lg:block">
                                                    <table
                                                        class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                                        <thead class="bg-slate-50 dark:bg-slate-900/70">
                                                            <tr>
                                                                <th
                                                                    class="w-12 px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-400">
                                                                    Orden
                                                                </th>

                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-400">
                                                                    Materia
                                                                </th>

                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-400">
                                                                    Profesor
                                                                </th>

                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-400">
                                                                    Tipo
                                                                </th>

                                                                <th
                                                                    class="px-4 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-400">
                                                                    Acciones
                                                                </th>
                                                            </tr>
                                                        </thead>

                                                        <tbody data-sortable="grupo"
                                                            data-grupo-id="{{ $grupoId }}"
                                                            class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-950">
                                                            @foreach ($materiasSemestre as $item)
                                                                <tr data-id="{{ $item->id }}"
                                                                    @class([
                                                                        'transition hover:bg-slate-50 dark:hover:bg-slate-900/70',
                                                                        'bg-emerald-50/70 dark:bg-emerald-950/20' =>
                                                                            $ultimoRegistroId === $item->id,
                                                                    ])>
                                                                    <td class="px-4 py-4">
                                                                        <button type="button" data-handle
                                                                            class="inline-flex h-9 w-9 cursor-grab items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 transition hover:border-sky-200 hover:text-sky-600 active:cursor-grabbing dark:border-slate-700 dark:bg-slate-900"
                                                                            title="Mover">
                                                                            <svg class="h-4 w-4" fill="none"
                                                                                stroke="currentColor"
                                                                                stroke-width="1.8"
                                                                                viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round"
                                                                                    d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01" />
                                                                            </svg>
                                                                        </button>
                                                                    </td>

                                                                    <td class="px-4 py-4">
                                                                        <div class="flex flex-col">
                                                                            <span
                                                                                class="font-bold text-slate-800 dark:text-slate-100">
                                                                                {{ $item->materia?->materia ?? 'Sin materia' }}
                                                                            </span>

                                                                            <span
                                                                                class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                                                {{ $item->materia?->clave ?? 'Sin clave' }}
                                                                            </span>
                                                                        </div>
                                                                    </td>

                                                                    <td class="px-4 py-4">
                                                                        <div class="flex flex-col">
                                                                            <span
                                                                                class="font-semibold text-slate-700 dark:text-slate-200">
                                                                                @if ($item->profesor)
                                                                                    {{ trim(($item->profesor->titulo ?? '') . ' ' . ($item->profesor->nombre ?? '') . ' ' . ($item->profesor->apellido_paterno ?? '') . ' ' . ($item->profesor->apellido_materno ?? '')) }}
                                                                                @else
                                                                                    Sin profesor
                                                                                @endif
                                                                            </span>

                                                                            <span
                                                                                class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                                                Grupo
                                                                                {{ $item->grupo?->asignacionGrupo?->nombre ?? '—' }}

                                                                                @if ((int) $nivel?->id === 4 && $item->grupo?->semestre)
                                                                                    ·
                                                                                    {{ $item->grupo->semestre->numero }}°
                                                                                    semestre
                                                                                @endif
                                                                            </span>
                                                                        </div>
                                                                    </td>

                                                                    <td class="px-4 py-4">
                                                                        <div class="flex flex-wrap gap-2">
                                                                            @if ($item->materia?->calificable)
                                                                                <span
                                                                                    class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                                                                    Calificable
                                                                                </span>
                                                                            @else
                                                                                <span
                                                                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                                                    No calificable
                                                                                </span>
                                                                            @endif

                                                                            @if ($item->materia?->extra)
                                                                                <span
                                                                                    class="rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-700 dark:bg-violet-950 dark:text-violet-300">
                                                                                    Extra
                                                                                </span>
                                                                            @endif

                                                                            @if ($item->materia?->receso)
                                                                                <span
                                                                                    class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                                                    Receso
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                    </td>

                                                                    <td class="px-4 py-4 text-right">
                                                                        <div class="flex justify-end gap-2">
                                                                            <button type="button"
                                                                                wire:click="editar({{ $item->id }})"
                                                                                class="inline-flex items-center justify-center rounded-xl bg-blue-50 px-3 py-2 text-xs font-black text-blue-600 transition hover:bg-blue-100 dark:bg-blue-950 dark:text-blue-300 dark:hover:bg-blue-900">
                                                                                Editar
                                                                            </button>

                                                                            <button type="button"
                                                                                wire:click="eliminar({{ $item->id }})"
                                                                                wire:confirm="¿Seguro que deseas eliminar esta asignación?"
                                                                                class="inline-flex items-center justify-center rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-100 dark:bg-red-950 dark:text-red-300 dark:hover:bg-red-900">
                                                                                Eliminar
                                                                            </button>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                                {{-- Vista móvil --}}
                                                <div class="grid gap-3 p-4 lg:hidden">
                                                    @foreach ($materiasSemestre as $item)
                                                        <article data-id="{{ $item->id }}"
                                                            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                                                            <div class="flex items-start justify-between gap-3">
                                                                <div>
                                                                    <h4
                                                                        class="font-black text-slate-900 dark:text-white">
                                                                        {{ $item->materia?->materia ?? 'Sin materia' }}
                                                                    </h4>

                                                                    <p
                                                                        class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                                        {{ $item->materia?->clave ?? 'Sin clave' }}
                                                                    </p>
                                                                </div>

                                                                <span
                                                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                                    #{{ $item->orden }}
                                                                </span>
                                                            </div>

                                                            <div class="mt-4 space-y-2 text-sm">
                                                                <p class="text-slate-600 dark:text-slate-300">
                                                                    <span class="font-bold">Profesor:</span>

                                                                    @if ($item->profesor)
                                                                        {{ trim(($item->profesor->titulo ?? '') . ' ' . ($item->profesor->nombre ?? '') . ' ' . ($item->profesor->apellido_paterno ?? '') . ' ' . ($item->profesor->apellido_materno ?? '')) }}
                                                                    @else
                                                                        Sin profesor
                                                                    @endif
                                                                </p>

                                                                <p class="text-slate-600 dark:text-slate-300">
                                                                    <span class="font-bold">Generación:</span>
                                                                    {{ $nombreGeneracion }}
                                                                </p>

                                                                <p class="text-slate-600 dark:text-slate-300">
                                                                    <span class="font-bold">Grado:</span>
                                                                    {{ $item->grupo?->grado?->nombre ?? 'Sin grado' }}
                                                                </p>

                                                                <p class="text-slate-600 dark:text-slate-300">
                                                                    <span class="font-bold">Grupo:</span>
                                                                    {{ $item->grupo?->asignacionGrupo?->nombre ?? '—' }}
                                                                </p>

                                                                @if ((int) $nivel?->id === 4)
                                                                    <p class="text-slate-600 dark:text-slate-300">
                                                                        <span class="font-bold">Semestre:</span>

                                                                        @if ($item->grupo?->semestre)
                                                                            {{ $item->grupo->semestre->numero }}°
                                                                            semestre
                                                                        @else
                                                                            Sin semestre
                                                                        @endif
                                                                    </p>
                                                                @endif
                                                            </div>

                                                            <div class="mt-4 flex flex-wrap gap-2">
                                                                @if ($item->materia?->calificable)
                                                                    <span
                                                                        class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                                                        Calificable
                                                                    </span>
                                                                @else
                                                                    <span
                                                                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                                        No calificable
                                                                    </span>
                                                                @endif

                                                                @if ($item->materia?->extra)
                                                                    <span
                                                                        class="rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-700 dark:bg-violet-950 dark:text-violet-300">
                                                                        Extra
                                                                    </span>
                                                                @endif

                                                                @if ($item->materia?->receso)
                                                                    <span
                                                                        class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                                        Receso
                                                                    </span>
                                                                @endif
                                                            </div>

                                                            <div class="mt-4 flex gap-2">
                                                                <button type="button"
                                                                    wire:click="editar({{ $item->id }})"
                                                                    class="flex-1 rounded-xl bg-blue-50 px-3 py-2 text-xs font-black text-blue-600 transition hover:bg-blue-100 dark:bg-blue-950 dark:text-blue-300">
                                                                    Editar
                                                                </button>

                                                                <button type="button"
                                                                    wire:click="eliminar({{ $item->id }})"
                                                                    wire:confirm="¿Seguro que deseas eliminar esta asignación?"
                                                                    class="flex-1 rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-100 dark:bg-red-950 dark:text-red-300">
                                                                    Eliminar
                                                                </button>
                                                            </div>
                                                        </article>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-700">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75v10.5M6.75 12h10.5" />
                        </svg>
                    </div>

                    <h3 class="mt-4 text-lg font-black text-slate-900 dark:text-white">
                        No hay materias asignadas
                    </h3>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Selecciona un grupo, una materia y un profesor para crear la primera asignación.
                    </p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- SORTABLE --}}
    @once
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

        <script>
            (function() {
                function getLivewireComponentFrom(el) {
                    const root = el.closest('[wire\\:id]');

                    if (!root) {
                        return null;
                    }

                    const componentId = root.getAttribute('wire:id');

                    return componentId ? Livewire.find(componentId) : null;
                }

                function destruirSortableSiExiste(el) {
                    if (el._sortable) {
                        el._sortable.destroy();
                        el._sortable = null;
                    }
                }

                function initSortableMateriasPorGrupo() {
                    if (typeof Sortable === 'undefined') {
                        return;
                    }

                    document.querySelectorAll('tbody[data-sortable="grupo"]').forEach((el) => {
                        const grupoId = parseInt(el.dataset.grupoId || '0', 10);

                        if (!grupoId) {
                            destruirSortableSiExiste(el);
                            return;
                        }

                        if (el._sortable) {
                            return;
                        }

                        el._sortable = new Sortable(el, {
                            animation: 150,
                            handle: '[data-handle]',
                            ghostClass: 'bg-sky-50',
                            chosenClass: 'bg-slate-50',
                            dragClass: 'opacity-80',

                            onEnd: function() {
                                const component = getLivewireComponentFrom(el);

                                if (!component) {
                                    return;
                                }

                                const ids = Array.from(el.querySelectorAll('tr[data-id]'))
                                    .map((row) => parseInt(row.dataset.id || '0', 10))
                                    .filter((id) => id > 0);

                                component.call('ordenarMateriasPorGrupoJs', grupoId, ids);
                            }
                        });
                    });
                }

                document.addEventListener('livewire:init', () => {
                    initSortableMateriasPorGrupo();

                    Livewire.hook('morph.updated', () => {
                        initSortableMateriasPorGrupo();
                    });
                });

                document.addEventListener('DOMContentLoaded', () => {
                    initSortableMateriasPorGrupo();
                });
            })
            ();
        </script>
    @endonce
</div>
