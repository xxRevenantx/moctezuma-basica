@php
    $concentrado = $this->concentrado;
    $resumen = $concentrado['resumen'];
    $gruposPromedios = $concentrado['grupos'];
    $encabezadosPeriodos = $this->encabezadosPeriodos;
    $esPreescolar = $this->esPreescolar;
    $esPrimaria = $this->esPrimaria;
    $esSecundaria = $this->esSecundaria;
    $esBachillerato = $this->esBachillerato;
    $esAnualBachillerato = $this->esAnualBachillerato;
    $esBasicaConDetalle = $esPrimaria || $esSecundaria;
    $mostrarDetalleMaterias = $esBasicaConDetalle || $esAnualBachillerato;
    $diagnosticoAnual = $concentrado['diagnostico'] ?? null;
    $contextoAnual = $concentrado['contexto'] ?? $this->contextoAnualBachillerato;
    $puedeMostrarBachillerato = !$esBachillerato || $generacion_id !== '';
    $gradoTerminal = $grados->sortByDesc('orden')->first();
    $semestreTerminal = $semestres->sortByDesc('numero')->first();
    $puedeDescargarPorGrado = $ciclo_escolar_id !== '' && $grado_id !== '';
    $puedeDescargarReconocimientos = $puedeDescargarPorGrado && (!$esBachillerato || $semestre_id !== '');
    $puedeDescargarAnualBachillerato =
        $esAnualBachillerato &&
        $ciclo_escolar_id !== '' &&
        $generacion_id !== '' &&
        (bool) data_get($contextoAnual, 'valido', false);
    $esGradoTerminalSeleccionado =
        $puedeDescargarPorGrado && $gradoTerminal && (int) $grado_id === (int) $gradoTerminal->id;
    $esSemestreTerminalSeleccionado =
        !$esBachillerato ||
        ($semestre_id !== '' &&
            $semestreTerminal &&
            (int) $semestre_id === (int) $semestreTerminal->id &&
            (int) $semestreTerminal->numero === 6);
    $puedeDescargarDiplomas = $esGradoTerminalSeleccionado && $esSemestreTerminalSeleccionado;
    $parametrosDescargaMasiva = [
        'ciclo_escolar_id' => $ciclo_escolar_id,
        'grado_id' => $grado_id,
        'fecha' => $fecha_pdf ?: now()->format('Y-m-d'),
    ];

    if ($generacion_id !== '') {
        $parametrosDescargaMasiva['generacion_id'] = $generacion_id;
    }

    if ($esBachillerato && $semestre_id !== '') {
        $parametrosDescargaMasiva['semestre_id'] = $semestre_id;
    }

    $parametrosListaPromedios = $parametrosDescargaMasiva;

    if ($grupo_id !== '') {
        $parametrosListaPromedios['grupo_id'] = $grupo_id;
    }

    $parametrosAnualesBachillerato = [
        'ciclo_escolar_id' => $ciclo_escolar_id,
        'generacion_id' => $generacion_id,
        'fecha' => $fecha_pdf ?: now()->format('Y-m-d'),
    ];

    $sugerenciasAlumnos = $this->sugerenciasAlumnos;
    $avisoBusqueda = $this->avisoBusqueda;
    $datosAlumnoSeleccionado = $this->datosAlumnoSeleccionado;
    $filaAcademicaSeleccionada = $alumno_seleccionado_id
        ? $gruposPromedios
            ->flatMap(fn($grupo) => collect($grupo['alumnos'] ?? []))
            ->firstWhere('inscripcion_id', $alumno_seleccionado_id)
        : null;
    $alumnoSeleccionado = $datosAlumnoSeleccionado
        ? array_merge($datosAlumnoSeleccionado, $filaAcademicaSeleccionada ?? [])
        : $filaAcademicaSeleccionada;
@endphp

<div class="space-y-6">
    <section
        class="relative overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-emerald-400 via-sky-500 to-indigo-600"></div>

        <div class="flex flex-col gap-5 p-5 lg:flex-row lg:items-center lg:justify-between lg:p-6">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 via-sky-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                    <flux:icon.academic-cap class="h-7 w-7" />
                </div>

                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-sky-600 dark:text-sky-300">
                        Promedios generales
                    </p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">
                        Concentrado final de {{ $nivel->nombre }}
                    </h2>
                    <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        @if ($esPreescolar)
                            La consulta individual permite localizar a cada alumno activo de preescolar y revisar la
                            información académica disponible para el ciclo seleccionado, sin convertir registros vacíos
                            en calificaciones.
                        @elseif ($esPrimaria)
                            El promedio oficial se obtiene sumando los promedios precisos de los cuatro campos
                            formativos y dividiendo entre cuatro. Cada campo se calcula con sus tres periodos y el
                            truncamiento se aplica únicamente al mostrar el resultado.
                        @elseif ($esSecundaria)
                            El promedio de cada materia se obtiene con sus tres periodos. El promedio general se calcula
                            con los promedios anuales de las materias oficiales configuradas en la base de datos.
                            Materias extra, recesos, talleres y materias informativas no se muestran ni participan.
                        @elseif ($esBachillerato && $esAnualBachillerato)
                            Cada parcial se usa como entero truncado y el promedio de cada materia también queda en
                            entero. Los promedios semestrales se calculan con esas materias enteras y conservan sus
                            decimales. El promedio anual integra ambos promedios semestrales sin truncarlos.
                        @elseif ($esBachillerato)
                            Cada parcial se usa como entero truncado. El promedio de la materia se calcula con esos dos
                            enteros y también se trunca a entero. El promedio semestral se obtiene con los promedios
                            enteros de las materias y puede conservar decimales.
                        @else
                            El promedio se calcula con las evaluaciones numéricas configuradas para el nivel.
                        @endif
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            {{ $esAnualBachillerato ? 'Dos semestres · cuatro parciales' : ($esBachillerato ? 'Parciales por semestre' : ($esPreescolar ? 'Seguimiento individual' : 'Tres periodos')) }}
                        </span>
                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                            <flux:icon.calculator class="h-3.5 w-3.5" />
                            {{ $esBachillerato ? 'Parciales y materias enteros · semestre con decimales' : 'Precisión completa · truncamiento final' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                <button type="button" wire:click="exportarExcelGeneracion" wire:loading.attr="disabled"
                    wire:target="exportarExcelGeneracion" @disabled($esBachillerato && $generacion_id === '')
                    title="{{ $esBachillerato && $generacion_id === '' ? 'Selecciona una generación de bachillerato' : 'Exportar la generación y filtros actuales' }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 via-sky-600 to-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-sky-500/20 transition hover:-translate-y-0.5 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="exportarExcelGeneracion"
                        class="inline-flex items-center gap-2">
                        <flux:icon.document-arrow-down class="h-4 w-4" />
                        Exportar generación
                    </span>
                    <span wire:loading wire:target="exportarExcelGeneracion" class="inline-flex items-center gap-2">
                        <flux:icon.arrow-path class="h-4 w-4 animate-spin" />
                        Exportando…
                    </span>
                </button>

                <button type="button" wire:click="exportarExcelAlumno" wire:loading.attr="disabled"
                    wire:target="exportarExcelAlumno" @disabled(!$alumno_seleccionado_id)
                    title="{{ $alumno_seleccionado_id ? 'Exportar únicamente al alumno seleccionado' : 'Selecciona un alumno en el buscador' }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-sky-200 bg-white px-5 py-3 text-sm font-black text-sky-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-sky-900/60 dark:bg-neutral-900 dark:text-sky-300 dark:hover:bg-sky-950/20">
                    <span wire:loading.remove wire:target="exportarExcelAlumno" class="inline-flex items-center gap-2">
                        <flux:icon.user class="h-4 w-4" />
                        Exportar alumno
                    </span>
                    <span wire:loading wire:target="exportarExcelAlumno" class="inline-flex items-center gap-2">
                        <flux:icon.arrow-path class="h-4 w-4 animate-spin" />
                        Exportando…
                    </span>
                </button>
            </div>
        </div>
    </section>

    @if ($esBachillerato)
        <section
            class="rounded-[1.6rem] border border-sky-200 bg-gradient-to-r from-sky-50 via-white to-indigo-50 p-4 shadow-sm dark:border-sky-900/50 dark:from-sky-950/20 dark:via-neutral-900 dark:to-indigo-950/20">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700 dark:text-sky-300">
                        Modalidad de cálculo para bachillerato
                    </p>
                    <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">
                        El cálculo anual se activa únicamente para bachillerato y no modifica los promedios de los demás
                        niveles.
                    </p>
                </div>

                <div
                    class="inline-flex rounded-2xl border border-slate-200 bg-white p-1 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                    <button type="button" wire:click="$set('modalidad_bachillerato', 'semestral')"
                        class="rounded-xl px-4 py-2 text-sm font-black transition {{ !$esAnualBachillerato ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-neutral-800' }}">
                        Promedio semestral
                    </button>
                    <button type="button" wire:click="$set('modalidad_bachillerato', 'anual')"
                        class="rounded-xl px-4 py-2 text-sm font-black transition {{ $esAnualBachillerato ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-neutral-800' }}">
                        Promedio anual
                    </button>
                </div>
            </div>
        </section>
    @endif

    <section
        class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <flux:field>
                <flux:label>Ciclo escolar</flux:label>
                <flux:select wire:model.live="ciclo_escolar_id">
                    <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                    @foreach ($cicloEscolares as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">
                            {{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Generación</flux:label>
                <flux:select wire:model.live="generacion_id">
                    <flux:select.option value="">{{ $esBachillerato ? 'Selecciona una generación' : 'Todas' }}
                    </flux:select.option>
                    @foreach ($generaciones as $generacion)
                        <flux:select.option value="{{ $generacion->id }}">
                            {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="generacion_id" />
            </flux:field>

            @if (!$esAnualBachillerato)
                <flux:field>
                    <flux:label>Grado</flux:label>
                    <flux:select wire:model.live="grado_id">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Grupo</flux:label>
                    <flux:select wire:model.live="grupo_id">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($grupos as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">
                                {{ $grupo->grado?->nombre ?? 'Grado' }} ·
                                {{ $grupo->asignacionGrupo?->nombre ?? '—' }}
                                @if ($esBachillerato && $grupo->semestre)
                                    · Semestre {{ $grupo->semestre->numero }}
                                @endif
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @else
                <div
                    class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                    <p class="text-xs font-black uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Año
                        académico</p>
                    <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">
                        {{ data_get($contextoAnual, 'nombre_anio', 'Selecciona ciclo y generación') }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 dark:border-sky-900/50 dark:bg-sky-950/20">
                    <p class="text-xs font-black uppercase tracking-wide text-sky-700 dark:text-sky-300">Semestres
                        automáticos</p>
                    <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">
                        @if (count(data_get($contextoAnual, 'numeros_semestre', [])) === 2)
                            {{ data_get($contextoAnual, 'numeros_semestre.0') }} y
                            {{ data_get($contextoAnual, 'numeros_semestre.1') }}
                        @else
                            Pendientes de determinar
                        @endif
                    </p>
                </div>
            @endif

            @if ($esBachillerato && !$esAnualBachillerato)
                <flux:field>
                    <flux:label>Semestre</flux:label>
                    <flux:select wire:model.live="semestre_id">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($semestres as $semestre)
                            <flux:select.option value="{{ $semestre->id }}">Semestre {{ $semestre->numero }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Ordenar</flux:label>
                <flux:select wire:model.live="orden">
                    <flux:select.option value="promedio_desc">Mayor promedio</flux:select.option>
                    <flux:select.option value="promedio_asc">Menor promedio</flux:select.option>
                    <flux:select.option value="nombre_asc">Nombre A-Z</flux:select.option>
                </flux:select>
            </flux:field>

            <div class="flex items-end">
                <button type="button" wire:click="limpiarFiltros"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                    <flux:icon.arrow-path class="h-4 w-4" />
                    Limpiar
                </button>
            </div>
        </div>
    </section>

    <section
        class="rounded-[1.6rem] border border-sky-200 bg-gradient-to-r from-white via-sky-50/70 to-indigo-50/70 p-5 shadow-sm dark:border-sky-900/50 dark:from-neutral-900 dark:via-sky-950/10 dark:to-indigo-950/10"
        x-data @click.outside="$wire.cerrarSugerencias()">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0 flex-1">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700 dark:text-sky-300">
                            Búsqueda individual
                        </p>
                        <h3 class="mt-1 text-lg font-black text-slate-950 dark:text-white">
                            Localiza un alumno por nombre, matrícula o CURP
                        </h3>
                        <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">
                            Disponible para preescolar, primaria, secundaria y bachillerato. La búsqueda respeta los
                            filtros académicos seleccionados.
                        </p>
                    </div>

                    @if ($alumno_seleccionado_id)
                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50">
                            <flux:icon.check-circle class="h-4 w-4" />
                            Vista individual activa
                        </span>
                    @endif
                </div>

                <div class="relative">
                    <flux:input wire:model.live.debounce.350ms="busqueda_alumno" wire:focus="abrirSugerencias"
                        wire:keydown.arrow-down.prevent="moverSugerencia(1)"
                        wire:keydown.arrow-up.prevent="moverSugerencia(-1)"
                        wire:keydown.enter.prevent="seleccionarSugerenciaActual"
                        wire:keydown.escape.prevent="cerrarSugerencias" icon="magnifying-glass"
                        placeholder="Escribe al menos 2 caracteres…" autocomplete="off" />

                    <div class="pointer-events-none absolute inset-y-0 right-12 flex items-center">
                        <flux:icon.arrow-path wire:loading wire:target="busqueda_alumno"
                            class="h-4 w-4 animate-spin text-sky-600" />
                    </div>

                    @if ($busqueda_alumno !== '')
                        <button type="button" wire:click="limpiarBusquedaAlumno"
                            class="absolute inset-y-0 right-2 my-auto inline-flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-neutral-800 dark:hover:text-white"
                            title="Limpiar búsqueda">
                            <flux:icon.x-mark class="h-4 w-4" />
                        </button>
                    @endif

                    @if ($mostrar_sugerencias && mb_strlen(trim($busqueda_alumno)) >= 2 && !$alumno_seleccionado_id)
                        <div
                            class="absolute z-50 mt-2 max-h-[26rem] w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">
                            <div wire:loading.flex wire:target="busqueda_alumno"
                                class="items-center justify-center gap-2 px-4 py-8 text-sm font-bold text-slate-500">
                                <flux:icon.arrow-path class="h-5 w-5 animate-spin text-sky-600" />
                                Buscando alumnos…
                            </div>

                            <div wire:loading.remove wire:target="busqueda_alumno">
                                @forelse ($sugerenciasAlumnos as $indice => $sugerencia)
                                    <button type="button" wire:key="sugerencia-alumno-{{ $sugerencia['id'] }}"
                                        wire:mouseenter="$set('indice_sugerencia', {{ $indice }})"
                                        wire:click="seleccionarAlumno({{ $sugerencia['id'] }})"
                                        class="mb-1 flex w-full items-start gap-3 rounded-xl px-3 py-3 text-left transition last:mb-0 {{ $indice_sugerencia === $indice ? 'bg-sky-50 ring-1 ring-sky-200 dark:bg-sky-950/30 dark:ring-sky-900/60' : 'hover:bg-slate-50 dark:hover:bg-neutral-800' }}">
                                        <span
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white shadow-sm">
                                            <flux:icon.user class="h-5 w-5" />
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span
                                                class="block truncate text-sm font-black text-slate-900 dark:text-white">
                                                {{ $sugerencia['alumno'] }}
                                            </span>
                                            <span
                                                class="mt-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                Matrícula: {{ $sugerencia['matricula'] }} · CURP:
                                                {{ $sugerencia['curp'] }}
                                            </span>
                                            <span class="mt-1 block text-xs font-bold text-sky-700 dark:text-sky-300">
                                                {{ $sugerencia['generacion'] }} · {{ $sugerencia['grado'] }} · Grupo
                                                {{ $sugerencia['grupo'] }}
                                                @if ($sugerencia['semestre'])
                                                    · Semestre {{ $sugerencia['semestre'] }}
                                                @endif
                                            </span>
                                        </span>

                                        <span
                                            class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-black uppercase text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                            Activo
                                        </span>
                                    </button>
                                @empty
                                    <div class="px-4 py-8 text-center">
                                        <flux:icon.magnifying-glass
                                            class="mx-auto h-7 w-7 text-slate-300 dark:text-slate-600" />
                                        <p class="mt-2 text-sm font-black text-slate-700 dark:text-slate-200">
                                            Sin coincidencias en los filtros actuales
                                        </p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">
                                            Revisa la generación, grado, grupo o semestre seleccionados.
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>

                <flux:error name="busqueda_alumno" />

                @if ($avisoBusqueda)
                    <div
                        class="mt-3 flex items-start gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                        <flux:icon.exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>{{ $avisoBusqueda }}</span>
                    </div>
                @endif
            </div>

            <div class="xl:w-[26rem]">
                @if ($alumnoSeleccionado)
                    <article
                        class="overflow-hidden rounded-2xl border border-indigo-200 bg-white shadow-sm dark:border-indigo-900/60 dark:bg-neutral-900">
                        <div class="bg-gradient-to-r from-sky-600 to-indigo-600 px-4 py-3 text-white">
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-sky-100">Alumno
                                seleccionado</p>
                            <h4 class="mt-1 text-base font-black leading-tight">{{ $alumnoSeleccionado['alumno'] }}
                            </h4>
                        </div>

                        <div class="grid grid-cols-2 gap-x-4 gap-y-3 p-4 text-xs">
                            <div>
                                <p class="font-black uppercase text-slate-400">Matrícula</p>
                                <p class="mt-1 font-black text-slate-800 dark:text-slate-100">
                                    {{ $alumnoSeleccionado['matricula'] ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="font-black uppercase text-slate-400">CURP</p>
                                <p class="mt-1 truncate font-black text-slate-800 dark:text-slate-100"
                                    title="{{ $alumnoSeleccionado['curp'] ?? '—' }}">
                                    {{ $alumnoSeleccionado['curp'] ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="font-black uppercase text-slate-400">Generación</p>
                                <p class="mt-1 font-black text-slate-800 dark:text-slate-100">
                                    {{ $alumnoSeleccionado['generacion'] ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="font-black uppercase text-slate-400">Grado / grupo</p>
                                <p class="mt-1 font-black text-slate-800 dark:text-slate-100">
                                    {{ $alumnoSeleccionado['grado'] ?? '—' }} ·
                                    {{ $alumnoSeleccionado['grupo'] ?? '—' }}
                                    @if ($alumnoSeleccionado['semestre'] ?? null)
                                        · S{{ $alumnoSeleccionado['semestre'] }}
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="font-black uppercase text-slate-400">Promedio general</p>
                                <p class="mt-1 text-lg font-black text-indigo-700 dark:text-indigo-300">
                                    {{ $this->formatearDecimal($alumnoSeleccionado['promedio_final'] ?? ($alumnoSeleccionado['promedio_provisional'] ?? null)) }}
                                </p>
                            </div>
                            <div>
                                <p class="font-black uppercase text-slate-400">Lugar</p>
                                <p class="mt-1 text-lg font-black text-slate-800 dark:text-slate-100">
                                    {{ $alumnoSeleccionado['texto_lugar'] ?? '—' }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 dark:border-neutral-800">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Estado
                                    académico</p>
                                <p class="mt-0.5 text-sm font-black text-slate-800 dark:text-slate-100">
                                    {{ $alumnoSeleccionado['estatus'] ?? 'Sin promedio en los filtros actuales' }}
                                </p>
                            </div>
                            <button type="button" wire:click="limpiarBusquedaAlumno"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800">
                                <flux:icon.x-mark class="h-3.5 w-3.5" />
                                Quitar
                            </button>
                        </div>
                    </article>
                @else
                    <div
                        class="flex min-h-48 flex-col items-center justify-center rounded-2xl border border-dashed border-sky-200 bg-white/70 p-6 text-center dark:border-sky-900/50 dark:bg-neutral-900/60">
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 dark:bg-sky-950/30 dark:text-sky-300">
                            <flux:icon.user class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-black text-slate-800 dark:text-slate-100">Sin alumno seleccionado
                        </p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Selecciona una coincidencia para mostrar
                            únicamente su promedio.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section
        class="rounded-[1.6rem] border border-indigo-200 bg-gradient-to-r from-indigo-50 via-sky-50 to-violet-50 p-5 shadow-sm dark:border-indigo-900/50 dark:from-indigo-950/20 dark:via-sky-950/20 dark:to-violet-950/20">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-300">
                    {{ $esAnualBachillerato ? 'Descargas anuales por generación' : ($esBachillerato ? 'Descargas masivas por semestre' : 'Descargas masivas por grado') }}
                </p>
                <h3 class="mt-1 text-lg font-black text-slate-950 dark:text-white">
                    {{ $esAnualBachillerato ? 'Reconocimientos y listas del promedio anual' : 'Reconocimientos, diplomas y listas académicas' }}
                </h3>
                <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">
                    @if ($esAnualBachillerato)
                        Selecciona ciclo y generación. El sistema integra automáticamente los dos semestres
                        correspondientes y genera un solo ranking para toda la generación.
                    @elseif ($esBachillerato)
                        Selecciona generación, grado y semestre. Descarga los documentos ZIP o la lista institucional
                        ordenada por promedio y lugar.
                    @else
                        Selecciona un grado. Descarga los documentos ZIP o la lista institucional ordenada por promedio
                        y lugar.
                    @endif
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <flux:field class="min-w-[190px]">
                    <flux:label>Fecha para documentos</flux:label>
                    <flux:input type="date" wire:model.live="fecha_pdf" />
                    <flux:error name="fecha_pdf" />
                </flux:field>

                <div class="flex flex-wrap gap-2">
                    @if ($esAnualBachillerato)
                        @if ($puedeDescargarAnualBachillerato)
                            <a href="{{ route('generales.bachillerato.promedio-anual.reconocimientos.zip', $parametrosAnualesBachillerato) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700">
                                <flux:icon.trophy class="h-4 w-4" />
                                Reconocimientos ZIP
                            </a>

                            <a href="{{ route('generales.bachillerato.promedio-anual.lista', array_merge(['formato' => 'pdf'], $parametrosAnualesBachillerato)) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-2xl bg-sky-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-sky-700">
                                <flux:icon.document-text class="h-4 w-4" />
                                Lista PDF
                            </a>

                            <a href="{{ route('generales.bachillerato.promedio-anual.lista', array_merge(['formato' => 'word'], $parametrosAnualesBachillerato)) }}"
                                class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-emerald-700">
                                <flux:icon.document-arrow-down class="h-4 w-4" />
                                Lista Word
                            </a>
                        @else
                            @foreach (['Reconocimientos ZIP', 'Lista PDF', 'Lista Word'] as $boton)
                                <span
                                    class="inline-flex cursor-not-allowed items-center gap-2 rounded-2xl bg-slate-200 px-4 py-2.5 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-400"
                                    title="Selecciona una generación compatible con el ciclo escolar">
                                    @if ($boton === 'Reconocimientos ZIP')
                                        <flux:icon.trophy class="h-4 w-4" />
                                    @else
                                        <flux:icon.document-text class="h-4 w-4" />
                                    @endif
                                    {{ $boton }}
                                </span>
                            @endforeach
                        @endif
                    @else
                        @if ($puedeDescargarReconocimientos)
                            <a href="{{ route(
                                'generales.documentos-academicos.zip',
                                array_merge(
                                    [
                                        'slug_nivel' => $slug_nivel,
                                        'tipo' => 'reconocimientos',
                                    ],
                                    $parametrosDescargaMasiva,
                                ),
                            ) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700">
                                <flux:icon.trophy class="h-4 w-4" />
                                Reconocimientos ZIP
                            </a>
                        @else
                            <span
                                class="inline-flex cursor-not-allowed items-center gap-2 rounded-2xl bg-slate-200 px-4 py-2.5 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-400"
                                title="{{ $esBachillerato ? 'Selecciona generación, grado y semestre' : 'Selecciona un grado' }}">
                                <flux:icon.trophy class="h-4 w-4" />
                                Reconocimientos ZIP
                            </span>
                        @endif

                        @if ($puedeDescargarDiplomas)
                            <a href="{{ route(
                                'generales.documentos-academicos.zip',
                                array_merge(
                                    [
                                        'slug_nivel' => $slug_nivel,
                                        'tipo' => 'diplomas',
                                    ],
                                    $parametrosDescargaMasiva,
                                ),
                            ) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-2xl bg-violet-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-violet-700">
                                <flux:icon.academic-cap class="h-4 w-4" />
                                Diplomas ZIP
                            </a>
                        @else
                            <span
                                class="inline-flex cursor-not-allowed items-center gap-2 rounded-2xl bg-slate-200 px-4 py-2.5 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-400"
                                title="{{ $esBachillerato ? 'Selecciona sexto semestre y su grado correspondiente' : 'Selecciona el último grado del nivel' }}">
                                <flux:icon.academic-cap class="h-4 w-4" />
                                Diplomas ZIP
                            </span>
                        @endif

                        @if ($puedeDescargarReconocimientos)
                            <a href="{{ route(
                                'generales.cuadro-honor',
                                array_merge(
                                    [
                                        'slug_nivel' => $slug_nivel,
                                        'formato' => 'pdf',
                                    ],
                                    $parametrosListaPromedios,
                                ),
                            ) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-2xl bg-sky-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-sky-700">
                                <flux:icon.document-text class="h-4 w-4" />
                                Lista PDF
                            </a>

                            <a href="{{ route(
                                'generales.cuadro-honor',
                                array_merge(
                                    [
                                        'slug_nivel' => $slug_nivel,
                                        'formato' => 'word',
                                    ],
                                    $parametrosListaPromedios,
                                ),
                            ) }}"
                                class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-emerald-700">
                                <flux:icon.document-arrow-down class="h-4 w-4" />
                                Lista Word
                            </a>
                        @else
                            <span
                                class="inline-flex cursor-not-allowed items-center gap-2 rounded-2xl bg-slate-200 px-4 py-2.5 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-400"
                                title="{{ $esBachillerato ? 'Selecciona generación, grado y semestre' : 'Selecciona un grado' }}">
                                <flux:icon.document-text class="h-4 w-4" />
                                Lista PDF
                            </span>

                            <span
                                class="inline-flex cursor-not-allowed items-center gap-2 rounded-2xl bg-slate-200 px-4 py-2.5 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-400"
                                title="{{ $esBachillerato ? 'Selecciona generación, grado y semestre' : 'Selecciona un grado' }}">
                                <flux:icon.document-arrow-down class="h-4 w-4" />
                                Lista Word
                            </span>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>

    @error('promocion')
        <div
            class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300">
            {{ $message }}
        </div>
    @enderror

    @if (!$puedeMostrarBachillerato)
        <div
            class="rounded-[1.6rem] border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
            <div class="flex items-start gap-3">
                <flux:icon.exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <p class="font-black">Selecciona una generación de bachillerato</p>
                    <p class="mt-1 text-sm font-semibold">
                        La generación es obligatoria para evitar mezclar alumnos de cohortes diferentes en los promedios
                        semestrales y anuales.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if ($esAnualBachillerato && $puedeMostrarBachillerato && !empty($diagnosticoAnual))
        <section
            class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Diagnóstico anual por
                        generación</p>
                    <h3 class="mt-1 text-base font-black text-slate-950 dark:text-white">
                        Validación de semestres, capturas e inconsistencias
                    </h3>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-black">
                    <span
                        class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700 ring-1 ring-emerald-100">Completos:
                        {{ $diagnosticoAnual['completos'] ?? 0 }}</span>
                    <span
                        class="rounded-full bg-amber-50 px-3 py-1.5 text-amber-700 ring-1 ring-amber-100">Incompletos:
                        {{ $diagnosticoAnual['incompletos'] ?? 0 }}</span>
                    <span class="rounded-full bg-rose-50 px-3 py-1.5 text-rose-700 ring-1 ring-rose-100">Duplicados:
                        {{ $diagnosticoAnual['duplicados'] ?? 0 }}</span>
                    <span
                        class="rounded-full bg-violet-50 px-3 py-1.5 text-violet-700 ring-1 ring-violet-100">Inconsistencias:
                        {{ $diagnosticoAnual['inconsistencias'] ?? 0 }}</span>
                </div>
            </div>

            @if (!empty($diagnosticoAnual['alertas']))
                <div class="mt-4 space-y-2">
                    @foreach ($diagnosticoAnual['alertas'] as $alerta)
                        <div
                            class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">
                            <flux:icon.exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{{ $alerta }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if ($puedeMostrarBachillerato)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div
                class="rounded-[1.4rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Alumnos</p>
                <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['total_alumnos'] }}</p>
                <p class="mt-1 text-xs font-bold text-slate-500">Incluidos en el concentrado</p>
            </div>

            <div
                class="rounded-[1.4rem] border border-sky-200 bg-sky-50 p-4 shadow-sm dark:border-sky-900/50 dark:bg-sky-950/20">
                <p class="text-xs font-black uppercase tracking-wide text-sky-700">Promedio general</p>
                <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['promedio_general'] }}
                </p>
                <p class="mt-1 text-xs font-bold text-sky-700">Solo alumnos con resultado definitivo</p>
            </div>

            <div
                class="rounded-[1.4rem] border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/20">
                <p class="text-xs font-black uppercase tracking-wide text-emerald-700">Acreditados</p>
                <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['aprobados'] }}</p>
                <p class="mt-1 text-xs font-bold text-emerald-700">
                    {{ $esAnualBachillerato ? 'Ambos semestres completos y todas las materias acreditadas' : ($esBachillerato ? 'Semestre completo y promedio general mínimo de 6' : 'Con todos los requisitos completos') }}
                </p>
            </div>

            <div
                class="rounded-[1.4rem] border border-rose-200 bg-rose-50 p-4 shadow-sm dark:border-rose-900/50 dark:bg-rose-950/20">
                <p class="text-xs font-black uppercase tracking-wide text-rose-700">En riesgo</p>
                <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['riesgo'] }}</p>
                <p class="mt-1 text-xs font-bold text-rose-700">
                    {{ $esAnualBachillerato ? 'Promedio anual menor de 6 o materias no acreditadas' : ($esBachillerato ? 'Promedio semestral general menor de 6' : 'Campos o materias por debajo de 6') }}
                </p>
            </div>

            <div
                class="rounded-[1.4rem] border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-900/50 dark:bg-amber-950/20">
                <p class="text-xs font-black uppercase tracking-wide text-amber-700">Incompletos</p>
                <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['incompletos'] }}</p>
                <p class="mt-1 text-xs font-bold text-amber-700">Con datos pendientes</p>
            </div>

            <div
                class="rounded-[1.4rem] border border-violet-200 bg-violet-50 p-4 shadow-sm dark:border-violet-900/50 dark:bg-violet-950/20">
                <p class="text-xs font-black uppercase tracking-wide text-violet-700">Mejor promedio</p>
                <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['mejor_promedio'] }}
                </p>
                <p class="mt-1 truncate text-xs font-bold text-violet-700">{{ $resumen['mejor_alumno'] }}</p>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($gruposPromedios as $grupoPromedio)
                <section x-data="{ abierto: false, tab: 'resumen' }"
                    class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <button type="button" x-on:click="abierto = !abierto"
                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70">
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-black text-slate-950 dark:text-white">
                                {{ $grupoPromedio['titulo'] }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                @if ($esAnualBachillerato)
                                    {{ $grupoPromedio['total'] }} alumnos · {{ $grupoPromedio['incompletos'] }}
                                    incompletos ·
                                    {{ $grupoPromedio['con_reconocimiento'] ?? 0 }} con reconocimiento anual
                                @else
                                    {{ $grupoPromedio['total'] }} alumnos · {{ $grupoPromedio['incompletos'] }}
                                    incompletos ·
                                    {{ $grupoPromedio['pendientes_decision'] ?? 0 }} decisiones pendientes
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                                Prom. {{ $grupoPromedio['promedio'] }}
                            </span>
                            <span
                                class="rounded-2xl border border-slate-200 p-2 text-slate-500 transition dark:border-neutral-700 dark:text-slate-300"
                                :class="abierto ? 'rotate-180' : ''">
                                <flux:icon.chevron-down class="h-5 w-5" />
                            </span>
                        </div>
                    </button>

                    <div x-cloak x-show="abierto" x-transition.opacity.duration.150ms
                        class="border-t border-slate-200 dark:border-neutral-800">
                        @if ($mostrarDetalleMaterias)
                            <div
                                class="flex flex-wrap gap-2 border-b border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/30">
                                <button type="button" x-on:click="tab = 'resumen'"
                                    class="rounded-xl px-4 py-2 text-xs font-black transition"
                                    :class="tab === 'resumen' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' :
                                        'bg-white text-slate-600 ring-1 ring-slate-200 dark:bg-neutral-900 dark:text-slate-300 dark:ring-neutral-700'">
                                    {{ $esAnualBachillerato ? 'Resumen anual por generación' : 'Resumen anual' }}
                                </button>
                                <button type="button" x-on:click="tab = 'materias'"
                                    class="rounded-xl px-4 py-2 text-xs font-black transition"
                                    :class="tab === 'materias' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' :
                                        'bg-white text-slate-600 ring-1 ring-slate-200 dark:bg-neutral-900 dark:text-slate-300 dark:ring-neutral-700'">
                                    {{ $esAnualBachillerato ? 'Detalle de los dos semestres' : 'Detalle por materia' }}
                                </button>
                                @if ($esPrimaria)
                                    <button type="button" x-on:click="tab = 'campos'"
                                        class="rounded-xl px-4 py-2 text-xs font-black transition"
                                        :class="tab === 'campos' ?
                                            'bg-slate-900 text-white dark:bg-white dark:text-slate-900' :
                                            'bg-white text-slate-600 ring-1 ring-slate-200 dark:bg-neutral-900 dark:text-slate-300 dark:ring-neutral-700'">
                                        Campos formativos
                                    </button>
                                @endif
                            </div>
                        @endif

                        <div x-show="tab === 'resumen'" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                                <thead class="bg-slate-50 dark:bg-neutral-950/40">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-black uppercase text-slate-500">#
                                        </th>
                                        <th class="px-3 py-3 text-center text-xs font-black uppercase text-slate-500">
                                            Lugar
                                        </th>
                                        <th
                                            class="min-w-[250px] px-3 py-3 text-left text-xs font-black uppercase text-slate-500">
                                            Alumno</th>
                                        <th class="px-3 py-3 text-left text-xs font-black uppercase text-slate-500">
                                            Matrícula</th>
                                        @foreach ($encabezadosPeriodos as $periodo => $etiqueta)
                                            <th
                                                class="px-3 py-3 text-center text-xs font-black uppercase text-slate-500">
                                                {{ $etiqueta }}</th>
                                        @endforeach
                                        @if ($esAnualBachillerato)
                                            <th
                                                class="px-3 py-3 text-center text-xs font-black uppercase text-slate-500">
                                                Promedio anual</th>
                                        @endif
                                        <th
                                            class="min-w-[180px] px-3 py-3 text-center text-xs font-black uppercase text-slate-500">
                                            Situación</th>
                                        @if ($esBasicaConDetalle)
                                            <th
                                                class="min-w-[190px] px-3 py-3 text-center text-xs font-black uppercase text-slate-500">
                                                Promoción</th>
                                        @endif
                                        <th
                                            class="min-w-[210px] px-3 py-3 text-center text-xs font-black uppercase text-slate-500">
                                            Documentos</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                    @foreach ($grupoPromedio['alumnos'] as $index => $alumno)
                                        @php
                                            $definitivo = (bool) ($alumno['completo'] ?? false);
                                            $alumnoInscripcionId = $alumno['inscripcion_id'] ?? null;
                                            $alumnoGeneracionId = $alumno['generacion_id'] ?? ($generacion_id ?: null);
                                            $alumnoGradoId = $alumno['grado_id'] ?? ($grado_id ?: null);
                                            $alumnoGrupoId = $alumno['grupo_id'] ?? ($grupo_id ?: null);
                                            $alumnoSemestreId = $alumno['semestre_id'] ?? ($semestre_id ?: null);
                                        @endphp
                                        <tr class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/50">
                                            <td class="px-3 py-3 font-black text-slate-400">{{ $index + 1 }}</td>
                                            <td class="px-3 py-3 text-center">
                                                <span
                                                    class="inline-flex flex-col items-center rounded-xl bg-indigo-50 px-2.5 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900/50">
                                                    <span>{{ $alumno['texto_lugar'] ?? 'Pendiente' }}</span>
                                                    @if (!$esAnualBachillerato)
                                                        <span class="mt-0.5 text-[10px] font-bold">
                                                            Prom.
                                                            {{ \App\Support\PromedioExcel::formatear($alumno['promedio_final'] ?? ($alumno['promedio_provisional'] ?? null), 2, '—') }}
                                                        </span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <p class="font-black text-slate-900 dark:text-white">
                                                    {{ $alumno['alumno'] }}</p>
                                                <p class="text-xs font-semibold text-slate-500">
                                                    @if ($esAnualBachillerato)
                                                        Generación
                                                        {{ data_get($contextoAnual, 'generacion.anio_ingreso', '—') }}-{{ data_get($contextoAnual, 'generacion.anio_egreso', '—') }}
                                                        ·
                                                        {{ data_get($contextoAnual, 'nombre_anio', 'Año académico') }}
                                                    @else
                                                        {{ $alumno['grado'] }} · Grupo {{ $alumno['grupo'] }}
                                                    @endif
                                                </p>
                                            </td>
                                            <td class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300">
                                                {{ $alumno['matricula'] ?? '—' }}</td>

                                            @foreach ($encabezadosPeriodos as $periodo => $etiqueta)
                                                @php
                                                    $valorPeriodo = $alumno['periodos'][$periodo] ?? null;
                                                    $periodoCompleto =
                                                        (bool) ($alumno['periodos_completos'][$periodo] ?? false);
                                                @endphp
                                                <td class="px-3 py-3 text-center">
                                                    @if ($valorPeriodo !== null)
                                                        <span
                                                            class="inline-flex min-w-14 flex-col items-center rounded-xl bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700 dark:bg-neutral-800 dark:text-slate-200">
                                                            {{ $esAnualBachillerato
                                                                ? \App\Support\PromedioExcel::formatear($valorPeriodo, 2, '—')
                                                                : $this->formatearDecimal($valorPeriodo) }}
                                                            @if (!$periodoCompleto)
                                                                <small
                                                                    class="text-[9px] uppercase text-amber-600">Prov.</small>
                                                            @endif
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex rounded-xl bg-amber-50 px-2.5 py-1 text-xs font-black text-amber-700 ring-1 ring-amber-100">Pend.</span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            @if ($esAnualBachillerato)
                                                <td class="px-3 py-3 text-center">
                                                    <span
                                                        class="inline-flex min-w-16 flex-col items-center rounded-xl bg-indigo-50 px-2.5 py-1 text-sm font-black text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900/50">
                                                        {{ \App\Support\PromedioExcel::formatear($alumno['promedio_final'] ?? ($alumno['promedio_provisional'] ?? null), 2, '—') }}
                                                        @if (!($alumno['completo'] ?? false))
                                                            <small
                                                                class="text-[9px] uppercase text-amber-600">Prov.</small>
                                                        @endif
                                                    </span>
                                                </td>
                                            @endif

                                            <td class="px-3 py-3 text-center">
                                                <span
                                                    class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-200 dark:bg-neutral-800 dark:text-slate-200 dark:ring-neutral-700">
                                                    {{ $alumno['estatus'] }}
                                                </span>
                                                @if ($esPrimaria && !empty($alumno['campos_reprobados']))
                                                    <p class="mt-1 text-[10px] font-bold text-rose-600">
                                                        {{ implode(', ', $alumno['campos_reprobados']) }}</p>
                                                @elseif ($esSecundaria && !empty($alumno['materias_reprobadas']))
                                                    <p class="mt-1 text-[10px] font-bold text-rose-600">
                                                        {{ implode(', ', $alumno['materias_reprobadas']) }}</p>
                                                @elseif ($esAnualBachillerato && !empty($alumno['materias_reprobadas']))
                                                    <p class="mt-1 text-[10px] font-bold text-rose-600">
                                                        {{ implode(', ', array_slice($alumno['materias_reprobadas'], 0, 2)) }}
                                                        @if (count($alumno['materias_reprobadas']) > 2)
                                                            y {{ count($alumno['materias_reprobadas']) - 2 }} más
                                                        @endif
                                                    </p>
                                                @elseif ($esAnualBachillerato && !empty($alumno['faltantes_anuales']))
                                                    <p class="mt-1 text-[10px] font-bold text-amber-600">
                                                        {{ implode(' · ', $alumno['faltantes_anuales']) }}
                                                    </p>
                                                @endif
                                            </td>

                                            @if ($esBasicaConDetalle)
                                                <td class="px-3 py-3 text-center">
                                                    @if (!$definitivo)
                                                        <span class="text-xs font-bold text-amber-600">Completa todas
                                                            las
                                                            evaluaciones</span>
                                                    @else
                                                        <div class="flex flex-wrap justify-center gap-2">
                                                            <button type="button"
                                                                wire:click="confirmarPromocion({{ $alumnoInscripcionId }}, true)"
                                                                class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white transition hover:bg-emerald-700">
                                                                Promover
                                                            </button>
                                                            <button type="button"
                                                                wire:click="confirmarPromocion({{ $alumnoInscripcionId }}, false)"
                                                                class="rounded-xl bg-rose-600 px-3 py-2 text-xs font-black text-white transition hover:bg-rose-700">
                                                                No promover
                                                            </button>
                                                        </div>
                                                        @if (($alumno['promocion_confirmada'] ?? null) !== null)
                                                            <p
                                                                class="mt-2 text-[10px] font-black uppercase {{ $alumno['promocion_confirmada'] ? 'text-emerald-600' : 'text-rose-600' }}">
                                                                {{ $alumno['promocion_confirmada'] ? 'Promoción confirmada' : 'No promoción confirmada' }}
                                                            </p>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endif

                                            <td class="px-3 py-3 text-center">
                                                <div class="flex flex-wrap justify-center gap-2">
                                                    @if ($esAnualBachillerato)
                                                        @if ($alumno['reconocimiento_disponible'] ?? false)
                                                            <a href="{{ route(
                                                                'generales.bachillerato.promedio-anual.reconocimiento',
                                                                array_merge(
                                                                    [
                                                                        'inscripcion' => $alumnoInscripcionId,
                                                                    ],
                                                                    $parametrosAnualesBachillerato,
                                                                ),
                                                            ) }}"
                                                                target="_blank" rel="noopener"
                                                                class="inline-flex items-center gap-1 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-black text-white transition hover:bg-indigo-700">
                                                                <flux:icon.trophy class="h-4 w-4" /> Reconocimiento
                                                                anual
                                                            </a>
                                                        @elseif (!($alumno['completo'] ?? false))
                                                            <span class="text-xs font-bold text-amber-600">Anual
                                                                incompleto</span>
                                                        @elseif (!($alumno['todas_materias_acreditadas'] ?? false))
                                                            <span class="text-xs font-bold text-rose-600">Tiene
                                                                materias no acreditadas</span>
                                                        @else
                                                            <span class="text-xs font-bold text-slate-500">Sin
                                                                reconocimiento</span>
                                                        @endif
                                                    @else
                                                        @if ($esPrimaria)
                                                            <a href="{{ route('calificaciones.boleta-oficial-primaria', [
                                                                'inscripcion' => $alumnoInscripcionId,
                                                                'ciclo_escolar_id' => $ciclo_escolar_id,
                                                                'generacion_id' => $alumnoGeneracionId,
                                                                'grado_id' => $alumnoGradoId,
                                                                'grupo_id' => $alumnoGrupoId,
                                                            ]) }}"
                                                                target="_blank"
                                                                class="inline-flex items-center gap-1 rounded-xl bg-amber-500 px-3 py-2 text-xs font-black text-white">
                                                                <flux:icon.document-arrow-down class="h-4 w-4" />
                                                                Oficial
                                                            </a>
                                                        @endif

                                                        <a href="{{ route('misrutas.promedios.boleta.pdf', [
                                                            'slug_nivel' => $slug_nivel,
                                                            'tipo' => $esBachillerato ? 'semestral' : 'boleta',
                                                            'inscripcion_id' => $alumnoInscripcionId,
                                                            'ciclo_escolar_id' => $ciclo_escolar_id,
                                                            'generacion_id' => $alumnoGeneracionId,
                                                            'grado_id' => $alumnoGradoId,
                                                            'grupo_id' => $alumnoGrupoId,
                                                            'semestre_id' => $alumnoSemestreId,
                                                        ]) }}"
                                                            target="_blank"
                                                            class="inline-flex items-center gap-1 rounded-xl bg-sky-600 px-3 py-2 text-xs font-black text-white">
                                                            <flux:icon.document-arrow-down class="h-4 w-4" /> Boleta
                                                        </a>

                                                        @if (
                                                            $definitivo &&
                                                                (($esBachillerato && ($alumno['reconocimiento_disponible'] ?? false)) ||
                                                                    (!$esBachillerato && ($alumno['lugar'] ?? null))))
                                                            <a href="{{ route('misrutas.promedios.boleta.pdf', [
                                                                'slug_nivel' => $slug_nivel,
                                                                'tipo' => 'reconocimiento',
                                                                'inscripcion_id' => $alumnoInscripcionId,
                                                                'ciclo_escolar_id' => $ciclo_escolar_id,
                                                                'generacion_id' => $alumnoGeneracionId,
                                                                'grado_id' => $alumnoGradoId,
                                                                'grupo_id' => $alumnoGrupoId,
                                                                'semestre_id' => $alumnoSemestreId,
                                                                'fecha' => $fecha_pdf ?: now()->format('Y-m-d'),
                                                            ]) }}"
                                                                target="_blank"
                                                                class="inline-flex items-center gap-1 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-black text-white">
                                                                <flux:icon.trophy class="h-4 w-4" /> Reconocimiento
                                                            </a>
                                                        @endif

                                                        @if ($alumno['diploma_disponible'] ?? false)
                                                            <a href="{{ route('misrutas.promedios.boleta.pdf', [
                                                                'slug_nivel' => $slug_nivel,
                                                                'tipo' => 'diploma',
                                                                'inscripcion_id' => $alumnoInscripcionId,
                                                                'ciclo_escolar_id' => $ciclo_escolar_id,
                                                                'generacion_id' => $alumnoGeneracionId,
                                                                'grado_id' => $alumnoGradoId,
                                                                'grupo_id' => $alumnoGrupoId,
                                                                'semestre_id' => $alumnoSemestreId,
                                                                'fecha' => $fecha_pdf ?: now()->format('Y-m-d'),
                                                            ]) }}"
                                                                target="_blank"
                                                                class="inline-flex items-center gap-1 rounded-xl bg-violet-600 px-3 py-2 text-xs font-black text-white">
                                                                <flux:icon.academic-cap class="h-4 w-4" /> Diploma
                                                            </a>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($mostrarDetalleMaterias)
                            <div x-cloak x-show="tab === 'materias'" class="space-y-5 p-4">
                                @foreach ($grupoPromedio['alumnos'] as $alumno)
                                    <article
                                        class="overflow-hidden rounded-2xl border border-slate-200 dark:border-neutral-800">
                                        <div
                                            class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 px-4 py-3 dark:bg-neutral-950/40">
                                            <div>
                                                <h4 class="font-black text-slate-900 dark:text-white">
                                                    {{ $alumno['alumno'] }}</h4>
                                                <p class="text-xs font-semibold text-slate-500">
                                                    {{ $alumno['matricula'] ?? '—' }}</p>
                                            </div>
                                            <span
                                                class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-100">
                                                {{ $esAnualBachillerato ? 'Promedio anual:' : 'General:' }}
                                                {{ $this->formatearDecimal($alumno['promedio_final'] ?? ($alumno['promedio_provisional'] ?? null)) }}
                                                {{ $alumno['completo'] ?? false ? '' : 'PROV.' }}
                                            </span>
                                        </div>

                                        @if ($esAnualBachillerato)
                                            <div class="space-y-4 p-4">
                                                @foreach (data_get($contextoAnual, 'numeros_semestre', []) as $numeroSemestre)
                                                    @php
                                                        $detalleSemestre = data_get(
                                                            $alumno,
                                                            'semestres_detalle.' . $numeroSemestre,
                                                        );
                                                    @endphp

                                                    @if ($detalleSemestre)
                                                        <section
                                                            class="overflow-hidden rounded-2xl border border-slate-200 dark:border-neutral-800">
                                                            <div
                                                                class="flex flex-wrap items-center justify-between gap-2 bg-indigo-50 px-4 py-3 dark:bg-indigo-950/20">
                                                                <div>
                                                                    <p
                                                                        class="text-xs font-black uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                                                                        Semestre {{ $numeroSemestre }} · Grupo
                                                                        {{ $detalleSemestre['grupo'] ?? '—' }}
                                                                    </p>
                                                                    <p
                                                                        class="mt-1 text-xs font-semibold text-slate-500">
                                                                        Parciales enteros · promedio de materia =
                                                                        truncar((P1 + P2) ÷ 2)
                                                                    </p>
                                                                </div>
                                                                <span
                                                                    class="rounded-full bg-white px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-200 dark:bg-neutral-900 dark:text-indigo-300 dark:ring-indigo-900/60">
                                                                    Promedio semestral:
                                                                    {{ \App\Support\PromedioExcel::formatear($detalleSemestre['promedio_general_preciso'] ?? ($detalleSemestre['promedio_provisional_preciso'] ?? null), 2, '—') }}
                                                                    {{ $detalleSemestre['completo'] ?? false ? '' : 'PROV.' }}
                                                                </span>
                                                            </div>

                                                            <div class="overflow-x-auto">
                                                                <table
                                                                    class="min-w-full divide-y divide-slate-200 text-xs dark:divide-neutral-800">
                                                                    <thead>
                                                                        <tr class="bg-white dark:bg-neutral-900">
                                                                            <th
                                                                                class="min-w-[230px] px-3 py-2 text-left font-black uppercase text-slate-500">
                                                                                Materia calificable</th>
                                                                            <th
                                                                                class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                                Parcial 1</th>
                                                                            <th
                                                                                class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                                Parcial 2</th>
                                                                            <th
                                                                                class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                                Promedio</th>
                                                                            <th
                                                                                class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                                Estado</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody
                                                                        class="divide-y divide-slate-100 dark:divide-neutral-800">
                                                                        @forelse ($detalleSemestre['materias'] ?? [] as $materia)
                                                                            <tr>
                                                                                <td
                                                                                    class="px-3 py-2 font-bold text-slate-800 dark:text-slate-200">
                                                                                    {{ $materia['materia'] }}
                                                                                </td>
                                                                                @foreach ([1, 2] as $parcial)
                                                                                    @php
                                                                                        $evaluacionParcial =
                                                                                            $materia['evaluaciones'][
                                                                                                $parcial
                                                                                            ] ?? null;
                                                                                        $especialParcial =
                                                                                            $materia['especiales'][
                                                                                                $parcial
                                                                                            ] ?? null;
                                                                                    @endphp
                                                                                    <td class="px-3 py-2 text-center">
                                                                                        @if ($evaluacionParcial !== null)
                                                                                            {{ \App\Support\CalificacionBachillerato::formatearEntero($evaluacionParcial) }}
                                                                                        @else
                                                                                            @if (!empty($especialParcial))
                                                                                                <span
                                                                                                    class="font-black text-amber-700">{{ $especialParcial }}</span>
                                                                                            @else
                                                                                                —
                                                                                            @endif
                                                                                        @endif
                                                                                    </td>
                                                                                @endforeach
                                                                                <td
                                                                                    class="px-3 py-2 text-center font-black">
                                                                                    {{ \App\Support\CalificacionBachillerato::formatearEntero($materia['promedio_final_preciso'] ?? ($materia['promedio_provisional_preciso'] ?? null)) }}
                                                                                </td>
                                                                                @php
                                                                                    $materiaCompleta =
                                                                                        (bool) ($materia['completo'] ??
                                                                                            false);
                                                                                    $promedioMateria =
                                                                                        (float) ($materia[
                                                                                            'promedio_final_preciso'
                                                                                        ] ?? 0);
                                                                                    $materiaAcreditada =
                                                                                        $materiaCompleta &&
                                                                                        $promedioMateria >= 6;

                                                                                    $claseEstadoMateria = !$materiaCompleta
                                                                                        ? 'bg-amber-50 text-amber-700'
                                                                                        : ($materiaAcreditada
                                                                                            ? 'bg-emerald-50 text-emerald-700'
                                                                                            : 'bg-rose-50 text-rose-700');

                                                                                    $textoEstadoMateria = !$materiaCompleta
                                                                                        ? 'Incompleta'
                                                                                        : ($materiaAcreditada
                                                                                            ? 'Acreditada'
                                                                                            : 'No acreditada');
                                                                                @endphp

                                                                                <td class="px-3 py-2 text-center">
                                                                                    <span
                                                                                        class="rounded-full px-2 py-1 font-black {{ $claseEstadoMateria }}">
                                                                                        {{ $textoEstadoMateria }}
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                        @empty
                                                                            <tr>
                                                                                <td colspan="5"
                                                                                    class="px-4 py-6 text-center font-semibold text-slate-500">
                                                                                    No hay materias calificables
                                                                                    configuradas para este semestre.
                                                                                </td>
                                                                            </tr>
                                                                        @endforelse
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </section>
                                                    @else
                                                        <div
                                                            class="flex items-start gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                                            <flux:icon.exclamation-triangle
                                                                class="mt-0.5 h-4 w-4 shrink-0" />
                                                            Sin información del semestre {{ $numeroSemestre }}. El
                                                            alumno no participa en el ranking anual.
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="overflow-x-auto">
                                                <table
                                                    class="min-w-full divide-y divide-slate-200 text-xs dark:divide-neutral-800">
                                                    <thead>
                                                        <tr class="bg-white dark:bg-neutral-900">
                                                            <th
                                                                class="px-3 py-2 text-left font-black uppercase text-slate-500">
                                                                Materia</th>
                                                            @if ($esPrimaria)
                                                                <th
                                                                    class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                    Participa</th>
                                                            @endif
                                                            @foreach ($encabezadosPeriodos as $periodo => $etiqueta)
                                                                <th
                                                                    class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                    {{ $etiqueta }}</th>
                                                            @endforeach
                                                            <th
                                                                class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                Promedio anual</th>
                                                            <th
                                                                class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                                        @foreach ($alumno['materias'] ?? [] as $materia)
                                                            <tr>
                                                                <td
                                                                    class="px-3 py-2 font-bold text-slate-800 dark:text-slate-200">
                                                                    {{ $materia['materia'] }}</td>
                                                                @if ($esPrimaria)
                                                                    <td class="px-3 py-2 text-center">
                                                                        {{ $materia['participa'] ?? true ? 'Sí' : 'No' }}
                                                                    </td>
                                                                @endif
                                                                @foreach ($encabezadosPeriodos as $periodo => $etiqueta)
                                                                    @php
                                                                        $evaluacionPeriodo =
                                                                            $materia['evaluaciones'][$periodo] ?? null;
                                                                        $especialPeriodo =
                                                                            $materia['especiales'][$periodo] ?? null;
                                                                    @endphp
                                                                    <td class="px-3 py-2 text-center">
                                                                        @if ($evaluacionPeriodo !== null)
                                                                            {{ $this->formatearDecimal($evaluacionPeriodo) }}
                                                                        @else
                                                                            @if (!empty($especialPeriodo))
                                                                                {{ $especialPeriodo }}
                                                                            @else
                                                                                —
                                                                            @endif
                                                                        @endif
                                                                    </td>
                                                                @endforeach
                                                                <td class="px-3 py-2 text-center font-black">
                                                                    @if ($esSecundaria)
                                                                        {{ $materia['promedio'] ?? '—' }}
                                                                    @else
                                                                        {{ $this->formatearDecimal($materia['promedio_final_preciso'] ?? ($materia['promedio_provisional_preciso'] ?? null)) }}
                                                                    @endif
                                                                </td>
                                                                <td class="px-3 py-2 text-center">
                                                                    <span
                                                                        class="rounded-full px-2 py-1 font-black {{ $materia['completo'] ?? false ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                                                        {{ $materia['completo'] ?? false ? 'Completa' : 'Provisional' }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>

                            @if ($esPrimaria)
                                <div x-cloak x-show="tab === 'campos'" class="space-y-5 p-4">
                                    @foreach ($grupoPromedio['alumnos'] as $alumno)
                                        <article
                                            class="overflow-hidden rounded-2xl border border-slate-200 dark:border-neutral-800">
                                            <div class="bg-slate-50 px-4 py-3 dark:bg-neutral-950/40">
                                                <h4 class="font-black text-slate-900 dark:text-white">
                                                    {{ $alumno['alumno'] }}</h4>
                                                <p class="text-xs font-semibold text-slate-500">
                                                    {{ $alumno['matricula'] ?? '—' }}</p>
                                            </div>

                                            @if ($esPrimaria)
                                                <div class="overflow-x-auto">
                                                    <table
                                                        class="min-w-full divide-y divide-slate-200 text-xs dark:divide-neutral-800">
                                                        <thead>
                                                            <tr class="bg-white dark:bg-neutral-900">
                                                                <th
                                                                    class="min-w-[250px] px-3 py-2 text-left font-black uppercase text-slate-500">
                                                                    Campo formativo</th>
                                                                @foreach ($encabezadosPeriodos as $periodo => $etiqueta)
                                                                    <th
                                                                        class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                        {{ $etiqueta }}</th>
                                                                @endforeach
                                                                <th
                                                                    class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                    Promedio final</th>
                                                                <th
                                                                    class="px-3 py-2 text-center font-black uppercase text-slate-500">
                                                                    Estado</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody
                                                            class="divide-y divide-slate-100 dark:divide-neutral-800">
                                                            @foreach ($alumno['campos'] ?? [] as $campo)
                                                                <tr>
                                                                    <td class="px-3 py-3 font-black"
                                                                        style="background-color: {{ $campo['color_fondo'] ?? '#E2E8F0' }}; color: {{ $campo['color_texto'] ?? '#334155' }}">
                                                                        {{ $campo['campo'] }}
                                                                    </td>
                                                                    @foreach ($encabezadosPeriodos as $periodo => $etiqueta)
                                                                        <td class="px-3 py-3 text-center font-black">
                                                                            {{ $this->formatearDecimal($campo['periodos'][$periodo] ?? null) }}
                                                                            @if (($campo['fuentes_periodo'][$periodo] ?? null) === 'automatica')
                                                                                <small
                                                                                    class="block text-[9px] uppercase text-slate-400">Materias</small>
                                                                            @endif
                                                                        </td>
                                                                    @endforeach
                                                                    <td class="px-3 py-3 text-center font-black">
                                                                        {{ $this->formatearDecimal($campo['final_preciso'] ?? ($campo['provisional_preciso'] ?? null)) }}
                                                                    </td>
                                                                    <td class="px-3 py-3 text-center">
                                                                        {{ $campo['completo'] ?? false ? 'Definitivo' : 'Provisional' }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                            <tr class="bg-yellow-50 font-black dark:bg-yellow-950/20">
                                                                <td class="px-3 py-3">PROMEDIO GENERAL = suma de los 4
                                                                    campos ÷ 4</td>
                                                                <td colspan="{{ count($encabezadosPeriodos) }}"
                                                                    class="px-3 py-3 text-center text-slate-500">
                                                                    Se promedian los cuatro valores finales mostrados
                                                                </td>
                                                                <td class="px-3 py-3 text-center text-lg">
                                                                    {{ $this->formatearDecimal($alumno['promedio_final'] ?? ($alumno['promedio_provisional'] ?? null)) }}
                                                                </td>
                                                                <td class="px-3 py-3 text-center">
                                                                    {{ $alumno['completo'] ?? false ? 'Definitivo' : 'Provisional' }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="grid gap-4 p-4 lg:grid-cols-2">
                                                    @foreach ($alumno['campos'] ?? [] as $campo)
                                                        <div
                                                            class="overflow-hidden rounded-2xl border border-slate-200 dark:border-neutral-800">
                                                            <div class="px-4 py-3 font-black"
                                                                style="background-color: {{ $campo['color_fondo'] ?? '#E2E8F0' }}; color: {{ $campo['color_texto'] ?? '#334155' }}">
                                                                {{ $campo['campo'] }}
                                                            </div>
                                                            <div
                                                                class="divide-y divide-slate-100 dark:divide-neutral-800">
                                                                @foreach ($campo['materias'] ?? [] as $materia)
                                                                    <div
                                                                        class="flex items-center justify-between gap-3 px-4 py-3 text-xs">
                                                                        <div>
                                                                            <p
                                                                                class="font-black text-slate-800 dark:text-slate-200">
                                                                                {{ $materia['materia'] }}</p>
                                                                            <p class="text-slate-500">
                                                                                {{ $materia['participa'] ?? false ? 'Participa en promedio' : 'Solo informativa' }}
                                                                            </p>
                                                                        </div>
                                                                        <span
                                                                            class="rounded-xl bg-white px-3 py-1 font-black ring-1 ring-slate-200 dark:bg-neutral-900 dark:ring-neutral-700">
                                                                            {{ $this->formatearDecimal($materia['promedio_final_preciso'] ?? ($materia['promedio_provisional_preciso'] ?? null)) }}
                                                                        </span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </section>
            @empty
                <div
                    class="rounded-[1.7rem] border border-dashed border-slate-300 bg-white p-10 text-center dark:border-neutral-700 dark:bg-neutral-900">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-neutral-800 dark:text-slate-300">
                        <flux:icon.inbox class="h-7 w-7" />
                    </div>
                    <h3 class="mt-4 text-lg font-black text-slate-950 dark:text-white">Sin datos para mostrar</h3>
                    <p class="mx-auto mt-2 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                        @if ($alumno_seleccionado_id)
                            El alumno seleccionado no tiene promedios registrados en el ciclo y filtros actuales. Puedes
                            quitar la selección o cambiar los filtros académicos.
                        @else
                            Verifica el ciclo escolar, los filtros y que existan calificaciones en materias
                            calificables. Los
                            vacíos y las claves especiales no se convierten en cero.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
    @endif
</div>
