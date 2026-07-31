<div class="space-y-6">
    @php
        $esSecundaria = $this->esSecundaria();

        $ocultarEvaluacionAsistencia = $this->esBachillerato() || $esSecundaria;

        $tiposVisibles = collect($this->tiposDescarga())
            ->reject(function ($texto, $valor) use ($ocultarEvaluacionAsistencia) {
                return $ocultarEvaluacionAsistencia && in_array($valor, ['evaluacion', 'asistencia']);
            })
            ->toArray();

    @endphp

    <section class="space-y-4">
        <article
            class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm transition-all duration-300 dark:border-neutral-800 dark:bg-neutral-900">

            <div class="bg-slate-50/70 p-4 dark:bg-neutral-950/30 sm:p-6">

                <div
                    class="relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

                    <div wire:loading.flex
                        wire:target="modo_descarga,generacion_id,grado_id,semestre_id,grupo_id,tipo_descarga,opcion_descarga,limpiarFiltros"
                        class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                </path>
                            </svg>
                            Actualizando filtros...
                        </div>
                    </div>

                    <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                    Filtros de descarga
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    @if ($this->esBachillerato())
                                        Selecciona la información escolar y el parcial del documento que deseas generar.
                                    @else
                                        Selecciona la información escolar y el periodo del documento que deseas generar.
                                    @endif
                                </p>
                            </div>

                            <button type="button" wire:click="limpiarFiltros"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                <flux:icon.arrow-path class="h-4 w-4" />
                                Limpiar
                            </button>
                        </div>

                        @if ($modo_descarga === 'nivel')
                            <div
                                class="mb-5 rounded-2xl border border-purple-200 bg-purple-50/80 p-4 text-sm text-purple-800 dark:border-purple-900/60 dark:bg-purple-950/30 dark:text-purple-200">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-200">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="font-black">
                                            Descarga por nivel activa
                                        </p>

                                        <p class="mt-1 text-sm">
                                            En este modo se descargan todas las listas del nivel seleccionado. No es
                                            necesario elegir grado, semestre ni grupo.
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
                                            Las listas de evaluación y asistencia están ocultas. Las opciones de periodo
                                            se reemplazaron por parciales.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($esSecundaria)
                            <div
                                class="mb-5 rounded-2xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="font-black">
                                            Modo secundaria activo
                                        </p>

                                        <p class="mt-1 text-sm">
                                            Las listas de evaluación y asistencia están ocultas para secundaria. Las
                                            opciones de periodo se mantienen disponibles.
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
                                <flux:label>Alcance de descarga</flux:label>

                                <flux:select id="modo_descarga" wire:model.live="modo_descarga">
                                    <flux:select.option value="grupo">
                                        Grupo seleccionado
                                    </flux:select.option>

                                    <flux:select.option value="seleccionados">
                                        Alumnos seleccionados
                                    </flux:select.option>

                                    <flux:select.option value="nivel">
                                        Todas las listas del nivel
                                    </flux:select.option>
                                </flux:select>

                                <flux:error name="modo_descarga" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Generación</flux:label>

                                <flux:select id="generacion_id" wire:model.live="generacion_id">
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

                                <flux:select id="grado_id" wire:model.live="grado_id"
                                    :disabled="!$generacion_id || $modo_descarga === 'nivel'">
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

                                @if ($modo_descarga === 'nivel')
                                    <p class="mt-2 text-xs font-semibold text-purple-600 dark:text-purple-400">
                                        No se requiere grado cuando la descarga es por nivel.
                                    </p>
                                @endif
                            </flux:field>

                            @if ($this->esBachillerato())
                                <flux:field>
                                    <flux:label>Semestre</flux:label>

                                    <flux:select id="semestre_id" wire:model.live="semestre_id"
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

                                    @if ($modo_descarga === 'nivel')
                                        <p class="mt-2 text-xs font-semibold text-purple-600 dark:text-purple-400">
                                            No se requiere semestre cuando la descarga es por nivel.
                                        </p>
                                    @endif
                                </flux:field>
                            @endif

                            <flux:field>
                                <flux:label>Grupo</flux:label>

                                <flux:select id="grupo_id" wire:model.live="grupo_id"
                                    wire:key="lista-grupo-select-{{ $slug_nivel }}-{{ $modo_descarga }}-{{ $generacion_id ?? 'null' }}-{{ $grado_id ?? 'null' }}-{{ $semestre_id ?? 'null' }}-{{ $grupos->count() }}"
                                    :disabled="$modo_descarga === 'nivel'
                                                                                                                                                                                        ? true
                                                                                                                                                                                        : (
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

                                @if ($modo_descarga === 'nivel')
                                    <p class="mt-2 text-xs font-semibold text-purple-600 dark:text-purple-400">
                                        No se requiere grupo cuando la descarga es por nivel.
                                    </p>
                                @endif

                                @if (in_array($modo_descarga, ['grupo', 'seleccionados'], true) && !$this->esBachillerato() && $generacion_id && $grado_id && $grupos->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        No hay grupos registrados para la generación y grado seleccionados.
                                    </p>
                                @endif

                                @if (
                                    in_array($modo_descarga, ['grupo', 'seleccionados'], true) &&
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
                                <flux:label>Tipo de documento</flux:label>

                                <flux:select id="tipo_descarga" wire:model.live="tipo_descarga">
                                    @foreach ($tiposVisibles as $valor => $texto)
                                        <flux:select.option value="{{ $valor }}">
                                            {{ $texto }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="tipo_descarga" />
                            </flux:field>

                            <flux:field>
                                <flux:label>
                                    {{ $this->etiquetaOpcionDescarga() }}
                                </flux:label>

                                <flux:select id="opcion_descarga" wire:model.live="opcion_descarga"
                                    wire:key="opcion-descarga-{{ $slug_nivel }}-{{ $tipo_descarga }}-{{ $parciales->count() }}">
                                    @foreach ($this->opcionesDescarga() as $valor => $texto)
                                        <flux:select.option value="{{ $valor }}">
                                            {{ $texto }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="opcion_descarga" />

                                @if ($this->esBachillerato() && $tipo_descarga !== 'formatos' && $parciales->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        No hay parciales registrados. Agrega parciales para poder generar documentos de
                                        bachillerato.
                                    </p>
                                @endif
                            </flux:field>

                            @if ($tipo_descarga === 'grupo' && in_array($modo_descarga, ['grupo', 'seleccionados'], true))
                                <div
                                    class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50/80 p-4 shadow-sm dark:border-indigo-900/60 dark:bg-indigo-950/30">
                                    <label class="flex cursor-pointer items-start gap-3">
                                        <input type="checkbox" wire:model.live="mostrar_motivo"
                                            class="mt-1 h-5 w-5 rounded border-indigo-300 text-indigo-600 shadow-sm focus:ring-2 focus:ring-indigo-500 dark:border-indigo-700">

                                        <span>
                                            <span
                                                class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                                                Agregar columna de motivo
                                            </span>

                                            <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                                                Al activar esta opción, la lista de grupo incluirá una columna adicional
                                                para escribir el motivo.
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            @endif
                        </div>

                        @if ($modo_descarga === 'seleccionados')
                            <div
                                class="relative mt-6 overflow-hidden rounded-[1.4rem] border border-emerald-200 bg-emerald-50/40 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/15">
                                <div wire:loading.flex
                                    wire:target="buscar_alumno,seleccionarTodos,limpiarSeleccion,alumnos_seleccionados"
                                    class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                                    <div
                                        class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                        <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                            </path>
                                        </svg>
                                        Actualizando alumnos...
                                    </div>
                                </div>

                                <div
                                    class="flex flex-col gap-4 border-b border-emerald-200 bg-white/80 p-4 dark:border-emerald-900/50 dark:bg-neutral-900/80 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            <flux:icon.users class="h-5 w-5" />
                                        </div>

                                        <div>
                                            <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                                Selección manual de alumnos
                                            </h4>
                                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                El PDF incluirá únicamente a los alumnos marcados. El buscador no elimina las selecciones previas.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                                            {{ $this->totalAlumnosSeleccionados }} de {{ $this->totalAlumnosDisponibles }} seleccionados
                                        </span>

                                        <button type="button" wire:click="seleccionarTodos"
                                            wire:loading.attr="disabled"
                                            wire:target="seleccionarTodos,limpiarSeleccion,alumnos_seleccionados"
                                            @disabled($this->totalAlumnosDisponibles === 0)
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                                            <flux:icon.check class="h-4 w-4" />
                                            Seleccionar todos
                                        </button>

                                        <button type="button" wire:click="limpiarSeleccion"
                                            wire:loading.attr="disabled"
                                            wire:target="seleccionarTodos,limpiarSeleccion,alumnos_seleccionados"
                                            @disabled($this->totalAlumnosSeleccionados === 0)
                                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                            <flux:icon.x-mark class="h-4 w-4" />
                                            Limpiar selección
                                        </button>
                                    </div>
                                </div>

                                @if (!$generacion_id || !$grado_id || !$grupo_id || ($this->esBachillerato() && !$semestre_id))
                                    <div class="p-6 text-center">
                                        <div
                                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                                            <flux:icon.information-circle class="h-6 w-6" />
                                        </div>
                                        <p class="mt-3 text-sm font-black text-slate-800 dark:text-slate-100">
                                            Selecciona primero el contexto escolar
                                        </p>
                                        <p class="mx-auto mt-1 max-w-xl text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">
                                            {{ $this->esBachillerato()
                                                ? 'Elige generación, grado, semestre y grupo para cargar los alumnos activos.'
                                                : 'Elige generación, grado y grupo para cargar los alumnos activos.' }}
                                        </p>
                                    </div>
                                @else
                                    <div class="p-4">
                                        <div class="relative">
                                            <span
                                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                                <flux:icon.magnifying-glass class="h-5 w-5" />
                                            </span>
                                            <input type="search" wire:model.live.debounce.350ms="buscar_alumno"
                                                placeholder="Buscar por nombre o matrícula..."
                                                class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm font-semibold text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-100 dark:focus:border-emerald-600 dark:focus:ring-emerald-950/60">
                                        </div>
                                    </div>

                                    <div class="max-h-[430px] overflow-y-auto border-t border-emerald-100 dark:border-emerald-900/40">
                                        @forelse ($this->alumnosFiltrados as $alumno)
                                            <label wire:key="lista-seleccion-alumno-{{ $grupo_id }}-{{ $alumno->id }}"
                                                class="flex cursor-pointer items-center gap-4 border-b border-slate-100 bg-white px-4 py-3 transition last:border-b-0 hover:bg-emerald-50/70 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-emerald-950/20">
                                                <input type="checkbox" value="{{ $alumno->id }}"
                                                    wire:model.live="alumnos_seleccionados"
                                                    class="h-5 w-5 shrink-0 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-950">

                                                <span
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xs font-black text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                                    {{ $alumno->iniciales }}
                                                </span>

                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-black text-slate-900 dark:text-white">
                                                        {{ $this->nombreAlumno($alumno) }}
                                                    </span>
                                                    <span class="mt-1 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                        <span>Matrícula: {{ $alumno->matricula ?: 'Sin matrícula' }}</span>
                                                        <span
                                                            class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                            Activo
                                                        </span>
                                                    </span>
                                                </span>

                                                @if (in_array((int) $alumno->id, $this->idsAlumnosSeleccionadosValidos, true))
                                                    <span
                                                        class="hidden rounded-full bg-emerald-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-white sm:inline-flex">
                                                        Incluido
                                                    </span>
                                                @endif
                                            </label>
                                        @empty
                                            <div class="px-6 py-10 text-center">
                                                <div
                                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-neutral-800 dark:text-slate-300">
                                                    <flux:icon.users class="h-6 w-6" />
                                                </div>
                                                <p class="mt-3 text-sm font-black text-slate-800 dark:text-slate-100">
                                                    {{ $this->totalAlumnosDisponibles === 0
                                                        ? 'No hay alumnos activos en este grupo.'
                                                        : 'No hay coincidencias con la búsqueda.' }}
                                                </p>
                                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                    {{ $this->totalAlumnosDisponibles === 0
                                                        ? 'Verifica la matrícula activa y los filtros seleccionados.'
                                                        : 'Prueba con otro nombre o matrícula.' }}
                                                </p>
                                            </div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 dark:border-purple-900/40 dark:bg-purple-950/30 dark:text-purple-300">
                                Alcance: {{ $this->textoAlcanceDescarga }}
                            </span>

                            @if ($this->generacionSeleccionada)
                                <span
                                    class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300">
                                    Generación:
                                    {{ $this->generacionSeleccionada->anio_ingreso }} -
                                    {{ $this->generacionSeleccionada->anio_egreso }}
                                </span>
                            @endif

                            @if ($this->gradoSeleccionado)
                                <span
                                    class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                    Grado: {{ $this->gradoSeleccionado->nombre }}
                                </span>
                            @endif

                            @if ($this->esBachillerato() && $this->semestreSeleccionado)
                                <span
                                    class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                    {{ $this->textoSemestre($this->semestreSeleccionado) }}
                                </span>
                            @endif

                            @if ($this->grupoSeleccionado)
                                <span
                                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    Grupo: {{ $this->textoGrupo($this->grupoSeleccionado) }}
                                </span>
                            @endif

                            <span
                                class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                {{ $this->textoTipoDescarga }}: {{ $this->textoOpcionDescarga }}
                            </span>

                            <span
                                class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-100 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/60">
                                Formato: {{ $this->extensionDescarga }}
                            </span>

                            @if ($modo_descarga === 'seleccionados')
                                <span
                                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    Seleccionados: {{ $this->totalAlumnosSeleccionados }} de {{ $this->totalAlumnosDisponibles }}
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

                        <div
                            class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">
                                            Estado de la descarga
                                        </p>

                                        @if ($this->puedeDescargar)
                                            <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">
                                                Ya puedes descargar:
                                                <span class="font-bold">
                                                    {{ $this->textoTipoDescarga }} - {{ $this->textoOpcionDescarga }}
                                                </span>
                                            </p>
                                        @endif

                                        <p class="mt-1 text-sm {{ $this->puedeDescargar ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">
                                            {{ $this->mensajeEstadoDescarga }}
                                        </p>
                                    </div>
                                </div>

                                @if ($this->puedeDescargar)
                                    <a href="{{ $this->urlDescarga }}" target="_blank"
                                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-500 via-rose-600 to-pink-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-rose-500/20 transition hover:-translate-y-0.5 hover:shadow-xl">
                                        <flux:icon.document-arrow-down class="h-5 w-5" />

                                        {{ $this->textoBotonDescarga }}
                                    </a>
                                @else
                                    <button type="button" :disabled="true"
                                        class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-200 px-5 py-3 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-neutral-500">
                                        <flux:icon.lock-closed class="h-5 w-5" />
                                        {{ $this->textoBotonDescarga }}
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
