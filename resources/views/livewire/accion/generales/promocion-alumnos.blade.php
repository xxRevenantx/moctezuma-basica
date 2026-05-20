<div class="space-y-6">
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-600"></div>

        <div class="space-y-6 p-5 sm:p-6">
            {{-- ENCABEZADO --}}
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 via-sky-500 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                        <flux:icon.arrow-path-rounded-square class="h-6 w-6" />
                    </div>

                    <div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">
                            Promoción masiva de alumnos
                        </h2>

                        <p class="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                            Promueve alumnos de un ciclo escolar a otro creando una nueva trayectoria académica y
                            actualizando la asignación actual de matrícula.
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                                Origen: {{ $nivel?->nombre ?? 'Nivel no encontrado' }}
                            </span>

                            <span
                                class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50">
                                Seleccionados: {{ $this->totalSeleccionados }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="button" wire:click="limpiarFormulario"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                        <flux:icon.arrow-path class="h-4 w-4" />
                        Limpiar promoción
                    </button>
                </div>
            </div>

            {{-- NOTA --}}
            <div
                class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                <p class="font-black">Uso recomendado</p>
                <p class="mt-1">
                    Este módulo es para promoción oficial entre ciclos escolares. Para corregir grado o grupo dentro
                    del mismo ciclo escolar, usa la corrección administrativa de Matrícula.
                </p>
            </div>

            {{-- FILTROS ORIGEN Y DESTINO --}}
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                {{-- ORIGEN --}}
                <div
                    class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-50/70 dark:border-neutral-800 dark:bg-neutral-950/30">
                    <div class="h-1 w-full bg-gradient-to-r from-slate-500 to-slate-800"></div>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">
                                Datos de origen
                            </h3>
                            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                Selecciona el grupo actual de donde saldrán los alumnos.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <flux:field>
                                <flux:label>Ciclo escolar origen</flux:label>
                                <flux:select wire:model.live="ciclo_escolar_origen_id">
                                    <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                                    @foreach ($cicloEscolares as $cicloEscolar)
                                        <flux:select.option value="{{ $cicloEscolar->id }}">
                                            {{ $cicloEscolar->inicio_anio }} - {{ $cicloEscolar->fin_anio }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="ciclo_escolar_origen_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Nivel origen</flux:label>
                                <flux:select wire:model.live="nivel_origen_id">
                                    <flux:select.option value="">Selecciona un nivel</flux:select.option>
                                    @foreach ($niveles as $nivelItem)
                                        <flux:select.option value="{{ $nivelItem->id }}">
                                            {{ $nivelItem->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="nivel_origen_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Grado origen</flux:label>
                                <flux:select wire:model.live="grado_origen_id"
                                    :disabled="!$nivel_origen_id || $this->gradosOrigen->isEmpty()">
                                    <flux:select.option value="">Selecciona un grado</flux:select.option>
                                    @foreach ($this->gradosOrigen as $grado)
                                        <flux:select.option value="{{ $grado->id }}">
                                            {{ $grado->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="grado_origen_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Generación origen</flux:label>
                                <flux:select wire:model.live="generacion_origen_id"
                                    :disabled="!$nivel_origen_id || $this->generacionesOrigen->isEmpty()">
                                    <flux:select.option value="">Selecciona una generación</flux:select.option>
                                    @foreach ($this->generacionesOrigen as $generacion)
                                        <flux:select.option value="{{ $generacion->id }}">
                                            {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="generacion_origen_id" />
                            </flux:field>

                            @if ($this->esBachilleratoOrigen)
                                <flux:field>
                                    <flux:label>Semestre origen</flux:label>
                                    <flux:select wire:model.live="semestre_origen_id"
                                        :disabled="!$grado_origen_id || !$generacion_origen_id || $this->semestresOrigen->isEmpty()">
                                        <flux:select.option value="">Selecciona un semestre</flux:select.option>
                                        @foreach ($this->semestresOrigen as $semestre)
                                            <flux:select.option value="{{ $semestre->id }}">
                                                Semestre {{ $semestre->numero }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="semestre_origen_id" />
                                </flux:field>
                            @endif

                            <flux:field>
                                <flux:label>Grupo origen</flux:label>
                                <flux:select wire:model.live="grupo_origen_id"
                                    :disabled="!$grado_origen_id || !$generacion_origen_id || ($this->esBachilleratoOrigen && !$semestre_origen_id) || $this->gruposOrigen->isEmpty()">
                                    <flux:select.option value="">Selecciona un grupo</flux:select.option>
                                    @foreach ($this->gruposOrigen as $grupo)
                                        <flux:select.option value="{{ $grupo->id }}">
                                            Grupo {{ $this->textoGrupo($grupo) }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="grupo_origen_id" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- DESTINO --}}
                <div
                    class="overflow-hidden rounded-[1.5rem] border border-sky-200 bg-sky-50/60 dark:border-sky-900/40 dark:bg-sky-950/20">
                    <div class="h-1 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">
                                Datos de destino
                            </h3>
                            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                Selecciona a dónde pasarán los alumnos seleccionados.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <flux:field>
                                <flux:label>Ciclo escolar destino</flux:label>
                                <flux:select wire:model.live="ciclo_escolar_destino_id">
                                    <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                                    @foreach ($cicloEscolares as $cicloEscolar)
                                        <flux:select.option value="{{ $cicloEscolar->id }}">
                                            {{ $cicloEscolar->inicio_anio }} - {{ $cicloEscolar->fin_anio }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="ciclo_escolar_destino_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Periodo inscripción destino</flux:label>
                                <flux:select wire:model.live="ciclo_id_destino">
                                    <flux:select.option value="">Selecciona un periodo</flux:select.option>
                                    @foreach ($ciclos as $ciclo)
                                        <flux:select.option value="{{ $ciclo->id }}">
                                            {{ $ciclo->ciclo }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="ciclo_id_destino" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Nivel destino</flux:label>
                                <flux:select wire:model.live="nivel_destino_id">
                                    <flux:select.option value="">Selecciona un nivel</flux:select.option>
                                    @foreach ($niveles as $nivelItem)
                                        <flux:select.option value="{{ $nivelItem->id }}">
                                            {{ $nivelItem->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="nivel_destino_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Grado destino</flux:label>
                                <flux:select wire:model.live="grado_destino_id"
                                    :disabled="!$nivel_destino_id || $this->gradosDestino->isEmpty()">
                                    <flux:select.option value="">Selecciona un grado</flux:select.option>
                                    @foreach ($this->gradosDestino as $grado)
                                        <flux:select.option value="{{ $grado->id }}">
                                            {{ $grado->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="grado_destino_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Generación destino</flux:label>
                                <flux:select wire:model.live="generacion_destino_id"
                                    :disabled="!$nivel_destino_id || $this->generacionesDestino->isEmpty()">
                                    <flux:select.option value="">Selecciona una generación</flux:select.option>
                                    @foreach ($this->generacionesDestino as $generacion)
                                        <flux:select.option value="{{ $generacion->id }}">
                                            {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="generacion_destino_id" />
                            </flux:field>

                            @if ($this->esBachilleratoDestino)
                                <flux:field>
                                    <flux:label>Semestre destino</flux:label>
                                    <flux:select wire:model.live="semestre_destino_id"
                                        :disabled="!$grado_destino_id || !$generacion_destino_id || $this->semestresDestino->isEmpty()">
                                        <flux:select.option value="">Selecciona un semestre</flux:select.option>
                                        @foreach ($this->semestresDestino as $semestre)
                                            <flux:select.option value="{{ $semestre->id }}">
                                                Semestre {{ $semestre->numero }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="semestre_destino_id" />
                                </flux:field>
                            @endif

                            <flux:field>
                                <flux:label>Grupo destino</flux:label>
                                <flux:select wire:model.live="grupo_destino_id"
                                    :disabled="!$grado_destino_id || !$generacion_destino_id || ($this->esBachilleratoDestino && !$semestre_destino_id) || $this->gruposDestino->isEmpty()">
                                    <flux:select.option value="">Selecciona un grupo</flux:select.option>
                                    @foreach ($this->gruposDestino as $grupo)
                                        <flux:select.option value="{{ $grupo->id }}">
                                            Grupo {{ $this->textoGrupo($grupo) }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="grupo_destino_id" />
                            </flux:field>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BUSCADOR Y RESUMEN --}}
            <div
                class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                <div class="grid grid-cols-1 gap-4 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <flux:field>
                        <flux:label>Buscar alumno</flux:label>
                        <flux:input wire:model.live.debounce.400ms="search"
                            placeholder="Matrícula, folio, CURP, nombre o apellidos..." />
                    </flux:field>

                    <label
                        class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                        <input type="checkbox" wire:model.live="ocultarPromovidos"
                            class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        Ocultar ya promovidos
                    </label>
                </div>

                <div
                    class="grid grid-cols-1 gap-3 border-t border-slate-200 p-4 dark:border-neutral-800 sm:grid-cols-4">
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-neutral-800">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Total
                        </p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                            {{ $this->resumenOrigen['total'] }}</p>
                    </div>

                    <div class="rounded-2xl bg-sky-50 px-4 py-3 dark:bg-sky-950/20">
                        <p class="text-xs font-black uppercase tracking-wide text-sky-700 dark:text-sky-300">Hombres</p>
                        <p class="mt-1 text-2xl font-black text-sky-900 dark:text-sky-100">
                            {{ $this->resumenOrigen['hombres'] }}</p>
                    </div>

                    <div class="rounded-2xl bg-pink-50 px-4 py-3 dark:bg-pink-950/20">
                        <p class="text-xs font-black uppercase tracking-wide text-pink-700 dark:text-pink-300">Mujeres
                        </p>
                        <p class="mt-1 text-2xl font-black text-pink-900 dark:text-pink-100">
                            {{ $this->resumenOrigen['mujeres'] }}</p>
                    </div>

                    <div class="rounded-2xl bg-emerald-50 px-4 py-3 dark:bg-emerald-950/20">
                        <p class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                            Seleccionados</p>
                        <p class="mt-1 text-2xl font-black text-emerald-900 dark:text-emerald-100">
                            {{ $this->totalSeleccionados }}</p>
                    </div>
                </div>
            </div>

            {{-- TABLA DE ALUMNOS --}}
            <div class="relative" wire:loading.class="opacity-60"
                wire:target="ciclo_escolar_origen_id,nivel_origen_id,grado_origen_id,generacion_origen_id,semestre_origen_id,grupo_origen_id,search,ocultarPromovidos,aplicarPromocion">
                <div wire:loading.flex
                    wire:target="ciclo_escolar_origen_id,nivel_origen_id,grado_origen_id,generacion_origen_id,semestre_origen_id,grupo_origen_id,search,ocultarPromovidos,aplicarPromocion"
                    class="absolute inset-0 z-30 hidden items-center justify-center rounded-3xl bg-white/75 backdrop-blur-md dark:bg-neutral-900/75">
                    <div
                        class="rounded-3xl border border-sky-100 bg-white px-8 py-7 text-center shadow-2xl shadow-sky-500/10 dark:border-sky-900/40 dark:bg-neutral-950">
                        <div
                            class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-sky-200 border-t-sky-600">
                        </div>
                        <p class="text-sm font-black text-slate-700 dark:text-slate-200">Actualizando información...
                        </p>
                    </div>
                </div>

                @if (
                    !$ciclo_escolar_origen_id ||
                        !$grado_origen_id ||
                        !$generacion_origen_id ||
                        !$grupo_origen_id ||
                        ($this->esBachilleratoOrigen && !$semestre_origen_id))
                    <div
                        class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-8 text-center dark:border-neutral-700 dark:bg-neutral-950/40">
                        <p class="text-base font-black text-slate-800 dark:text-white">
                            Completa los filtros de origen
                        </p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Al completar ciclo escolar, grado, generación y grupo se mostrarán los alumnos disponibles.
                        </p>
                    </div>
                @else
                    <div
                        class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                        <div
                            class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-800/70 sm:flex-row sm:items-center sm:justify-between">
                            <label
                                class="inline-flex items-center gap-2 text-sm font-black text-slate-700 dark:text-slate-200">
                                <input type="checkbox" wire:model.live="seleccionarTodos"
                                    class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                Seleccionar todos los visibles
                            </label>

                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                Se omiten automáticamente alumnos que ya tengan trayectoria en el ciclo destino.
                            </p>
                        </div>

                        <div class="hidden overflow-x-auto xl:block">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                                <thead class="bg-slate-50 dark:bg-neutral-800">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Sel.</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Alumno</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Matrícula</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Género</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Grado</th>
                                        @if ($this->esBachilleratoOrigen)
                                            <th
                                                class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                Semestre</th>
                                        @endif
                                        <th
                                            class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Grupo</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Estado</th>
                                    </tr>
                                </thead>

                                <tbody
                                    class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                                    @forelse ($this->alumnosDisponibles as $trayectoria)
                                        @php
                                            $alumno = $trayectoria->inscripcion;
                                        @endphp

                                        <tr class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/70">
                                            <td class="px-4 py-3 align-top">
                                                <input type="checkbox" wire:model.live="seleccionados"
                                                    value="{{ $trayectoria->id }}"
                                                    class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                            </td>

                                            <td class="px-4 py-3 align-top">
                                                <p class="font-black text-slate-800 dark:text-white">
                                                    {{ $this->nombreAlumno($alumno) }}
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                                                    CURP: {{ $alumno?->curp ?: '—' }}
                                                </p>
                                            </td>

                                            <td
                                                class="px-4 py-3 align-top font-semibold text-slate-700 dark:text-slate-200">
                                                {{ $alumno?->matricula ?: '—' }}
                                            </td>

                                            <td class="px-4 py-3 align-top">
                                                <span
                                                    class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $alumno?->genero === 'H' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300' : 'bg-pink-100 text-pink-700 dark:bg-pink-950/40 dark:text-pink-300' }}">
                                                    {{ $alumno?->genero ?: '—' }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-3 align-top text-slate-700 dark:text-slate-200">
                                                {{ $trayectoria->grado?->nombre ?? '—' }}
                                            </td>

                                            @if ($this->esBachilleratoOrigen)
                                                <td class="px-4 py-3 align-top text-slate-700 dark:text-slate-200">
                                                    {{ $trayectoria->semestre?->numero ?? '—' }}
                                                </td>
                                            @endif

                                            <td class="px-4 py-3 align-top text-slate-700 dark:text-slate-200">
                                                {{ $this->textoGrupo($trayectoria->grupo) }}
                                            </td>

                                            <td class="px-4 py-3 align-top">
                                                @if ($trayectoria->promovido)
                                                    <span
                                                        class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50">
                                                        Promovido
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex rounded-full bg-slate-50 px-3 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700">
                                                        Disponible
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $this->esBachilleratoOrigen ? 8 : 7 }}"
                                                class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                                No hay alumnos disponibles con los filtros actuales.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- VISTA MÓVIL --}}
                        <div class="space-y-3 p-4 xl:hidden">
                            @forelse ($this->alumnosDisponibles as $trayectoria)
                                @php
                                    $alumno = $trayectoria->inscripcion;
                                @endphp

                                <div
                                    class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-black text-slate-800 dark:text-white">
                                                {{ $this->nombreAlumno($alumno) }}
                                            </p>
                                            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                                Matrícula: {{ $alumno?->matricula ?: '—' }}
                                            </p>
                                        </div>

                                        <input type="checkbox" wire:model.live="seleccionados"
                                            value="{{ $trayectoria->id }}"
                                            class="mt-1 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    </div>

                                    <div
                                        class="mt-3 grid grid-cols-1 gap-2 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                                        <div><span class="font-bold">CURP:</span> {{ $alumno?->curp ?: '—' }}</div>
                                        <div><span class="font-bold">Género:</span> {{ $alumno?->genero ?: '—' }}
                                        </div>
                                        <div><span class="font-bold">Grado:</span>
                                            {{ $trayectoria->grado?->nombre ?? '—' }}</div>
                                        @if ($this->esBachilleratoOrigen)
                                            <div><span class="font-bold">Semestre:</span>
                                                {{ $trayectoria->semestre?->numero ?? '—' }}</div>
                                        @endif
                                        <div><span class="font-bold">Grupo:</span>
                                            {{ $this->textoGrupo($trayectoria->grupo) }}</div>
                                    </div>
                                </div>
                            @empty
                                <div
                                    class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-950/40 dark:text-slate-400">
                                    No hay alumnos disponibles con los filtros actuales.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>

            {{-- ACCIÓN FINAL --}}
            <div
                class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40 sm:p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <label
                        class="inline-flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                        <input type="checkbox" wire:model.live="confirmarPromocion"
                            class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                        <span>
                            Confirmo que esta acción creará una nueva trayectoria académica en el ciclo destino y
                            actualizará la matrícula actual de los alumnos seleccionados.
                        </span>
                    </label>

                    <button type="button" wire:click="aplicarPromocion" wire:loading.attr="disabled"
                        wire:target="aplicarPromocion"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-sky-500/20 transition hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-60"
                        @disabled($this->totalSeleccionados === 0 || !$confirmarPromocion)>
                        <span wire:loading.remove wire:target="aplicarPromocion"
                            class="inline-flex items-center gap-2">
                            <flux:icon.check-circle class="h-4 w-4" />
                            Aplicar promoción
                        </span>

                        <span wire:loading wire:target="aplicarPromocion">
                            Promoviendo...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>
