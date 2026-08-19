<div class="space-y-6">
    @php
        $esFormatoGlobal = $this->esFormatoGlobal();
        $esBachillerato = $this->esBachillerato();
        $bloquearContexto = in_array($modo_descarga, ['nivel', 'todos_activos'], true) || !$nivel_id;
        $contextoGrupoCompleto = $generacion_id && $grado_id && $grupo_id && (!$esBachillerato || $semestre_id);
        $filtrosOpcionales = $esFormatoGlobal && $modo_descarga === 'seleccionados';
    @endphp

    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>

        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <flux:badge color="blue" size="sm">Módulo global</flux:badge>
                    <flux:badge color="green" size="sm">Solo alumnos activos</flux:badge>
                    @if ($esFormatoGlobal)
                        <flux:badge color="purple" size="sm">Selección multinivel</flux:badge>
                    @endif
                </div>

                <flux:heading size="xl">Listas generales</flux:heading>
                <flux:text variant="subtle" class="mt-2">
                    Genera listas académicas por nivel y crea Personalizadores o Etiquetas con alumnos de cualquier nivel desde una sola pantalla.
                </flux:text>
            </div>

            <button type="button" wire:click="limpiarFiltros"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                <flux:icon.arrow-path class="h-4 w-4" />
                Limpiar filtros
            </button>
        </div>
    </section>

    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div wire:loading.flex
            wire:target="nivel_id,generacion_id,grado_id,semestre_id,grupo_id,tipo_descarga,opcion_descarga,modo_descarga,limpiarFiltros"
            class="absolute inset-0 z-30 hidden items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-neutral-900/75">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                Actualizando contexto...
            </div>
        </div>

        <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950/30 sm:px-6">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">
                    <flux:icon.adjustments-horizontal class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="text-base font-black text-slate-900 dark:text-white">Filtros de descarga</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Selecciona el nivel y el documento. Personalizadores y Etiquetas habilitan búsqueda global y selección multinivel.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            @if ($esFormatoGlobal)
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <flux:icon.users class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-black text-emerald-900 dark:text-emerald-100">Modo global de alumnos</p>
                            <p class="mt-1 text-xs font-semibold leading-5 text-emerald-700 dark:text-emerald-300">
                                Puedes buscar por nombre, apellidos, matrícula o CURP, cambiar de nivel y conservar la selección. Los filtros de generación, grado y grupo son opcionales cuando el alcance es “Alumnos seleccionados”.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif (!$nivel_id)
                <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                            <flux:icon.information-circle class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-black text-amber-900 dark:text-amber-100">Selecciona un nivel</p>
                            <p class="mt-1 text-xs font-semibold leading-5 text-amber-700 dark:text-amber-300">
                                Las listas de evaluación, asistencia, grupo, SECE y SECE interna son exclusivas del nivel seleccionado.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <flux:field>
                    <flux:label>Nivel</flux:label>
                    <flux:select id="nivel_id" wire:model.live="nivel_id" :disabled="$modo_descarga === 'todos_activos'">
                        <flux:select.option value="">Todos los niveles</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="nivel_id" />
                    @if (!$nivel_id)
                        <p class="mt-1 text-xs font-semibold text-violet-600 dark:text-violet-300">
                            “Todos los niveles” solo está disponible para Personalizadores y Etiquetas.
                        </p>
                    @endif
                </flux:field>

                <flux:field>
                    <flux:label>Alcance de descarga</flux:label>
                    <flux:select id="modo_descarga" wire:model.live="modo_descarga">
                        @foreach ($this->modosDescarga() as $valor => $texto)
                            <flux:select.option value="{{ $valor }}">{{ $texto }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="modo_descarga" />
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de documento</flux:label>
                    <flux:select id="tipo_descarga" wire:model.live="tipo_descarga">
                        @foreach ($this->tiposDescarga() as $valor => $texto)
                            <flux:select.option value="{{ $valor }}">{{ $texto }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="tipo_descarga" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ $this->etiquetaOpcionDescarga() }}</flux:label>
                    <flux:select id="opcion_descarga" wire:model.live="opcion_descarga"
                        wire:key="listas-globales-opcion-{{ $nivel_id ?? 'todos' }}-{{ $tipo_descarga }}-{{ $parciales->count() }}">
                        @foreach ($this->opcionesDescarga() as $valor => $texto)
                            <flux:select.option value="{{ $valor }}">{{ $texto }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="opcion_descarga" />
                </flux:field>

                <flux:field>
                    <flux:label>
                        Generación @if ($filtrosOpcionales)<span class="font-normal text-slate-400">(opcional)</span>@endif
                    </flux:label>
                    <flux:select id="generacion_id" wire:model.live="generacion_id" :disabled="$bloquearContexto">
                        <flux:select.option value="">{{ $filtrosOpcionales ? 'Todas las generaciones' : 'Selecciona una generación' }}</flux:select.option>
                        @foreach ($generaciones as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">
                                {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="generacion_id" />
                </flux:field>

                <flux:field>
                    <flux:label>
                        Grado @if ($filtrosOpcionales)<span class="font-normal text-slate-400">(opcional)</span>@endif
                    </flux:label>
                    <flux:select id="grado_id" wire:model.live="grado_id"
                        :disabled="$bloquearContexto || (!$filtrosOpcionales && !$generacion_id)">
                        <flux:select.option value="">{{ $filtrosOpcionales ? 'Todos los grados' : 'Selecciona un grado' }}</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="grado_id" />
                </flux:field>

                @if ($esBachillerato)
                    <flux:field>
                        <flux:label>
                            Semestre @if ($filtrosOpcionales)<span class="font-normal text-slate-400">(opcional)</span>@endif
                        </flux:label>
                        <flux:select id="semestre_id" wire:model.live="semestre_id"
                            :disabled="$bloquearContexto || !$grado_id || $semestres->isEmpty()">
                            <flux:select.option value="">{{ $filtrosOpcionales ? 'Todos los semestres' : 'Selecciona un semestre' }}</flux:select.option>
                            @foreach ($semestres as $semestre)
                                <flux:select.option value="{{ $semestre->id }}">
                                    Semestre {{ $semestre->numero ?? $semestre->id }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="semestre_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>
                        Grupo @if ($filtrosOpcionales)<span class="font-normal text-slate-400">(opcional)</span>@endif
                    </flux:label>
                    <flux:select id="grupo_id" wire:model.live="grupo_id"
                        wire:key="listas-globales-grupo-{{ $nivel_id ?? 'todos' }}-{{ $generacion_id ?? 'null' }}-{{ $grado_id ?? 'null' }}-{{ $semestre_id ?? 'null' }}-{{ $grupos->count() }}"
                        :disabled="$bloquearContexto || !$grado_id || ($esBachillerato && !$semestre_id) || $grupos->isEmpty()">
                        <flux:select.option value="">{{ $filtrosOpcionales ? 'Todos los grupos' : 'Selecciona un grupo' }}</flux:select.option>
                        @foreach ($grupos as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">
                                {{ $grupo->asignacionGrupo?->nombre ?? 'Sin grupo' }}
                                @if ($filtrosOpcionales && $grupo->generacion)
                                    · {{ $grupo->generacion->anio_ingreso }}-{{ $grupo->generacion->anio_egreso }}
                                @endif
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="grupo_id" />
                </flux:field>
            </div>

            @if ($tipo_descarga === 'grupo' && in_array($modo_descarga, ['grupo', 'seleccionados'], true))
                <div class="mt-5 rounded-2xl border border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" wire:model.live="mostrar_motivo"
                            class="mt-1 h-5 w-5 rounded border-indigo-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-indigo-700">
                        <span>
                            <span class="block text-sm font-black text-slate-800 dark:text-slate-100">Agregar columna de motivo</span>
                            <span class="mt-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">La lista de grupo incluirá una columna adicional para anotaciones.</span>
                        </span>
                    </label>
                </div>
            @endif
        </div>
    </section>

    @if ($modo_descarga === 'seleccionados')
        <section class="relative overflow-hidden rounded-3xl border border-emerald-200 bg-white shadow-sm dark:border-emerald-900/50 dark:bg-neutral-900">
            <div wire:loading.flex wire:target="buscar_alumno,seleccionarTodos,limpiarSeleccion,alumnos_seleccionados"
                class="absolute inset-0 z-30 hidden items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-neutral-900/75">
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                    <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Actualizando alumnos...
                </div>
            </div>

            <div class="flex flex-col gap-4 border-b border-emerald-200 bg-emerald-50/50 p-5 dark:border-emerald-900/50 dark:bg-emerald-950/15 lg:flex-row lg:items-center lg:justify-between sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                        <flux:icon.users class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Selección manual de alumnos</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ $esFormatoGlobal
                                ? 'La selección se conserva aunque cambies de nivel o modifiques los filtros.'
                                : 'Selecciona los alumnos del grupo que deben incluirse en el documento.' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-black text-emerald-700 dark:border-emerald-900/50 dark:bg-neutral-900 dark:text-emerald-300">
                        {{ $this->totalAlumnosSeleccionados }} seleccionados
                    </span>
                    <button type="button" wire:click="seleccionarTodos" @disabled($this->totalAlumnosDisponibles === 0)
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-black text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <flux:icon.check class="h-4 w-4" />
                        {{ $esFormatoGlobal ? 'Agregar visibles' : 'Seleccionar todos' }}
                    </button>
                    <button type="button" wire:click="limpiarSeleccion" @disabled($this->totalAlumnosSeleccionados === 0)
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                        <flux:icon.x-mark class="h-4 w-4" />
                        Limpiar selección
                    </button>
                </div>
            </div>

            @if ($esFormatoGlobal)
                <div class="border-b border-emerald-100 bg-white px-4 py-3 dark:border-emerald-900/40 dark:bg-neutral-900 sm:px-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-[11px] font-black uppercase tracking-[0.12em] text-slate-400">Filtro rápido</span>
                        <button type="button" wire:click="seleccionarNivelRapido(null)"
                            class="rounded-full border px-3 py-1.5 text-xs font-black transition {{ !$nivel_id ? 'border-[#006492] bg-[#006492] text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300' }}">
                            Todos
                        </button>
                        @foreach ($niveles as $nivelRapido)
                            <button type="button" wire:click="seleccionarNivelRapido({{ $nivelRapido->id }})"
                                class="rounded-full border px-3 py-1.5 text-xs font-black transition {{ (int) $nivel_id === (int) $nivelRapido->id ? 'border-[#006492] bg-[#006492] text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300' }}">
                                {{ $nivelRapido->nombre }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (!$esFormatoGlobal && !$contextoGrupoCompleto)
                <div class="p-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                        <flux:icon.information-circle class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-black text-slate-800 dark:text-slate-100">Selecciona primero el contexto escolar</p>
                    <p class="mx-auto mt-1 max-w-xl text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">
                        {{ $esBachillerato
                            ? 'Elige nivel, generación, grado, semestre y grupo para cargar los alumnos activos.'
                            : 'Elige nivel, generación, grado y grupo para cargar los alumnos activos.' }}
                    </p>
                </div>
            @else
                <div class="grid min-h-[430px] grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="border-b border-slate-200 xl:border-b-0 xl:border-r dark:border-neutral-800">
                        <div class="p-4 sm:p-5">
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <flux:icon.magnifying-glass class="h-5 w-5" />
                                </span>
                                <input type="search" wire:model.live.debounce.350ms="buscar_alumno"
                                    placeholder="Buscar por nombre, apellidos, matrícula o CURP..."
                                    class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm font-semibold text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-100 dark:focus:border-emerald-600 dark:focus:ring-emerald-950/60">
                            </div>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                <span>{{ $this->totalAlumnosDisponibles }} alumno(s) visibles con los filtros actuales.</span>
                                @if ($esFormatoGlobal)
                                    <span>Solo se muestran alumnos con matrícula activa.</span>
                                @endif
                            </div>
                        </div>

                        <div class="max-h-[520px] overflow-y-auto border-t border-slate-100 dark:border-neutral-800">
                            @forelse ($this->alumnosDisponibles as $alumno)
                                @php $incluido = in_array((int) $alumno->id, $this->idsAlumnosSeleccionadosValidos, true); @endphp
                                <label wire:key="lista-global-alumno-{{ $alumno->id }}"
                                    class="flex cursor-pointer items-center gap-4 border-b border-slate-100 px-4 py-3 transition last:border-b-0 hover:bg-emerald-50/60 dark:border-neutral-800 dark:hover:bg-emerald-950/15 sm:px-5">
                                    <input type="checkbox" value="{{ $alumno->id }}" wire:model.live="alumnos_seleccionados"
                                        class="h-5 w-5 shrink-0 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-950">

                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xs font-black text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                        {{ $alumno->iniciales }}
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-black text-slate-900 dark:text-white">{{ $this->nombreAlumno($alumno) }}</span>
                                        <span class="mt-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">
                                            Matrícula: {{ $alumno->matricula ?: 'Sin matrícula' }}
                                            @if ($alumno->curp)
                                                · CURP: {{ $alumno->curp }}
                                            @endif
                                        </span>
                                        @if ($esFormatoGlobal)
                                            <span class="mt-1.5 block text-xs font-bold text-sky-700 dark:text-sky-300">{{ $this->textoContextoAlumno($alumno) }}</span>
                                        @endif
                                    </span>

                                    @if ($incluido)
                                        <span class="hidden rounded-full bg-emerald-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-white sm:inline-flex">Incluido</span>
                                    @endif
                                </label>
                            @empty
                                <div class="px-6 py-12 text-center">
                                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-neutral-800 dark:text-slate-300">
                                        <flux:icon.users class="h-6 w-6" />
                                    </span>
                                    <p class="mt-3 text-sm font-black text-slate-800 dark:text-slate-100">No hay alumnos activos con estos filtros.</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Prueba con otro nivel, contexto o término de búsqueda.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <aside class="bg-slate-50/70 p-4 dark:bg-neutral-950/30 sm:p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Bandeja</p>
                                <h3 class="mt-1 text-sm font-black text-slate-900 dark:text-white">Alumnos seleccionados</h3>
                            </div>
                            <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-black text-white dark:bg-white dark:text-slate-900">{{ $this->totalAlumnosSeleccionados }}</span>
                        </div>

                        @if ($this->resumenSeleccionPorNivel->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($this->resumenSeleccionPorNivel as $resumen)
                                    <span class="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-black text-sky-700 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300">
                                        {{ $resumen['nivel'] }} · {{ $resumen['total'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-4 max-h-[430px] space-y-4 overflow-y-auto pr-1">
                            @forelse ($this->alumnosSeleccionadosDetalle->groupBy(fn ($alumno) => (string) ($alumno->nivel?->nombre ?? 'Sin nivel')) as $nombreNivelSeleccion => $alumnosNivelSeleccion)
                                <div wire:key="lista-global-grupo-seleccion-{{ \Illuminate\Support\Str::slug($nombreNivelSeleccion) }}">
                                    <div class="mb-2 flex items-center justify-between gap-2 px-1">
                                        <p class="text-[11px] font-black uppercase tracking-[0.12em] text-sky-700 dark:text-sky-300">{{ $nombreNivelSeleccion }}</p>
                                        <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-black text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">{{ $alumnosNivelSeleccion->count() }}</span>
                                    </div>
                                    <div class="space-y-2">
                                        @foreach ($alumnosNivelSeleccion as $alumno)
                                            <div wire:key="lista-global-seleccionado-{{ $alumno->id }}"
                                                class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                                                <div class="flex items-start gap-3">
                                                    <div class="min-w-0 flex-1">
                                                        <p class="truncate text-xs font-black text-slate-900 dark:text-white">{{ $this->nombreAlumno($alumno) }}</p>
                                                        <p class="mt-1 text-[11px] font-semibold leading-4 text-slate-500 dark:text-slate-400">{{ $this->textoContextoAlumno($alumno) }}</p>
                                                    </div>
                                                    <button type="button" wire:click="quitarAlumnoSeleccionado({{ $alumno->id }})"
                                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30 dark:hover:text-rose-300"
                                                        aria-label="Quitar alumno">
                                                        <flux:icon.x-mark class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-5 text-center dark:border-neutral-700 dark:bg-neutral-900/70">
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200">Todavía no hay alumnos seleccionados.</p>
                                    <p class="mt-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400">Marca alumnos en la lista de la izquierda.</p>
                                </div>
                            @endforelse
                        </div>
                    </aside>
                </div>
            @endif
        </section>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $this->puedeDescargar ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300' }}">
                    @if ($this->puedeDescargar)
                        <flux:icon.check-circle class="h-5 w-5" />
                    @else
                        <flux:icon.information-circle class="h-5 w-5" />
                    @endif
                </span>
                <div>
                    <p class="text-sm font-black text-slate-900 dark:text-white">Estado de la descarga</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $this->mensajeEstadoDescarga }}</p>
                </div>
            </div>

            @if ($this->urlPdf)
                <a href="{{ $this->urlPdf }}" target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#006492] px-5 py-3 text-sm font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#00547a]">
                    <flux:icon.arrow-down-tray class="h-5 w-5" />
                    {{ $this->textoBotonDescarga }}
                </a>
            @else
                <button type="button" disabled
                    class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-200 px-5 py-3 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-500">
                    <flux:icon.lock-closed class="h-5 w-5" />
                    Descargar PDF
                </button>
            @endif
        </div>

        @if ($modo_descarga === 'todos_activos')
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold leading-5 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-300">
                Esta opción puede generar un PDF grande porque incluye toda la matrícula activa institucional. Úsala cuando realmente necesites el lote completo.
            </div>
        @endif
    </section>
</div>
