<div class="relative space-y-6">
    <div wire:loading.delay
        wire:target="ciclo_escolar_id,generacion_id,grado_id,grupo_id,buscar,alcance_exportacion,limpiarFiltros,exportarExcel,actualizarCampoFormativo"
        class="absolute inset-0 z-40 flex items-start justify-center rounded-[2rem] bg-white/70 pt-24 backdrop-blur-sm dark:bg-neutral-950/70">
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl dark:border-neutral-700 dark:bg-neutral-900">
            <span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-[#006492] dark:border-neutral-700 dark:border-t-sky-300"></span>
            <span class="text-sm font-black text-slate-700 dark:text-slate-200">Actualizando concentrado…</span>
        </div>
    </div>

    <section class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] via-sky-400 to-[#88AC2E]"></div>
        <div class="space-y-5 p-5 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-lg shadow-sky-900/20">
                        <flux:icon.table-cells class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.2em] text-[#006492] dark:text-sky-300">Concentrado institucional</p>
                        <h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Promedio de los tres periodos por materia</h3>
                        <p class="mt-2 max-w-4xl text-sm text-slate-600 dark:text-slate-300">
                            Muestra todos los grados en una sola tabla, agrupa las materias por campo formativo y calcula el promedio del turno Matutino, por grado, grupo y alumno.
                        </p>
                    </div>
                </div>

                @if ($this->disponible)
                    <div class="flex flex-wrap gap-2">
                        @if ($this->puedeExportar)
                            <a href="{{ $this->pdfUrl }}" target="_blank"
                                class="inline-flex items-center gap-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-black text-red-700 transition hover:bg-red-100 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300">
                                <flux:icon.document-arrow-down class="h-4 w-4" />
                                Exportar PDF
                            </a>
                        @else
                            <button type="button" disabled
                                class="inline-flex cursor-not-allowed items-center gap-2 rounded-2xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-black text-slate-400">
                                <flux:icon.document-arrow-down class="h-4 w-4" />
                                Exportar PDF
                            </button>
                        @endif
                        <flux:button type="button" wire:click="exportarExcel" wire:loading.attr="disabled"
                            :disabled="! $this->puedeExportar" variant="primary" icon="arrow-down-tray">
                            Exportar Excel
                        </flux:button>
                    </div>
                @endif
            </div>

            @if ($this->disponible)
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar">
                        <option value="">Selecciona</option>
                        @foreach ($cicloEscolares as $ciclo)
                            <option value="{{ $ciclo->id }}">
                                {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · Actual' : '' }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="generacion_id" label="Generación">
                        <option value="">Todas</option>
                        @foreach ($generaciones as $generacion)
                            <option value="{{ $generacion->id }}">
                                {{ $generacion->anio_ingreso }}-{{ $generacion->anio_egreso }}{{ $generacion->status ? '' : ' · Cerrada' }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="grado_id" label="Grado">
                        <option value="">Todos</option>
                        @foreach ($grados as $grado)
                            <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="grupo_id" label="Grupo">
                        <option value="">Todos</option>
                        @foreach ($grupos as $grupo)
                            <option value="{{ $grupo->id }}">
                                {{ $grupo->grado?->nombre }} · {{ $grupo->asignacionGrupo?->nombre ?? 'Sin grupo' }}
                                @if ($grupo->semestre)
                                    · Sem. {{ $grupo->semestre->numero }}
                                @endif
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="alcance_exportacion" label="Alcance de exportación">
                        <option value="completo">Concentrado completo</option>
                        <option value="nivel">Por nivel</option>
                        <option value="grado">Por grado seleccionado</option>
                        <option value="grupo">Por grupo seleccionado</option>
                    </flux:select>

                    <div class="flex items-end">
                        <button type="button" wire:click="limpiarFiltros"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                            <flux:icon.arrow-path class="h-4 w-4" />
                            Limpiar
                        </button>
                    </div>
                </div>

                @error('grado_id')
                    <p class="text-sm font-bold text-red-600">{{ $message }}</p>
                @enderror
                @error('grupo_id')
                    <p class="text-sm font-bold text-red-600">{{ $message }}</p>
                @enderror
            @endif
        </div>
    </section>

    @unless ($this->disponible)
        <section class="rounded-[1.8rem] border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-900/50 dark:bg-amber-950/20">
            <flux:icon.information-circle class="mx-auto h-10 w-10 text-amber-600" />
            <h3 class="mt-3 text-lg font-black text-amber-950 dark:text-amber-100">Este nivel no utiliza calificaciones numéricas</h3>
            <p class="mt-2 text-sm text-amber-800 dark:text-amber-200">El concentrado está disponible para primaria, secundaria y bachillerato.</p>
        </section>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            <x-promedios.resumen-card titulo="Alumnos" :valor="$this->reporte['resumen']['total_alumnos']" icono="users" />
            <x-promedios.resumen-card titulo="Grupos" :valor="$this->reporte['resumen']['total_grupos']" icono="rectangle-group" />
            <x-promedios.resumen-card titulo="Grados / semestres" :valor="$this->reporte['resumen']['total_bloques']" icono="academic-cap" />
            <x-promedios.resumen-card titulo="Materias" :valor="$this->reporte['resumen']['total_materias']" icono="book-open" />
            <x-promedios.resumen-card titulo="Promedio general" :valor="$this->formatearPromedio($this->reporte['resumen']['promedio_general'])" icono="chart-bar-square" destacado />
            <x-promedios.resumen-card titulo="Provisionales" :valor="$this->reporte['resumen']['alumnos_provisionales']" icono="exclamation-triangle" advertencia />
        </div>

        @if (collect($this->reporte['bloques'])->isNotEmpty())
            <section class="overflow-hidden rounded-[1.8rem] border border-slate-300 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between dark:border-neutral-800">
                    <div>
                        <h3 class="font-black text-slate-950 dark:text-white">Concentrado Matutino</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Método A: promedio de los promedios finales por alumno. Método B: suma de evaluaciones numéricas dividida entre registros válidos.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->reporte['campos'] as $campo)
                            <span class="rounded-full px-3 py-1 text-xs font-black"
                                style="background: {{ $campo->color_fondo }}; color: {{ $campo->color_texto }};">
                                {{ $campo->nombre }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-max border-collapse text-center text-xs">
                        <thead>
                            <tr class="bg-[#FEF9C3] text-slate-950">
                                <th rowspan="4" class="sticky left-0 z-20 w-28 border border-slate-400 bg-[#FEF9C3] px-3 py-3 font-black">TURNO</th>
                                <th colspan="{{ collect($this->reporte['bloques'])->sum(fn ($bloque) => count($bloque['materias']) + 1) }}"
                                    class="border border-slate-400 px-3 py-2 text-base font-black">
                                    CAMPOS FORMATIVOS
                                </th>
                            </tr>
                            <tr>
                                @foreach ($this->reporte['bloques'] as $bloque)
                                    <th colspan="{{ count($bloque['materias']) + 1 }}"
                                        class="border border-slate-400 bg-[#F0FDF4] px-3 py-2 text-sm font-black text-[#14532D]">
                                        {{ $bloque['titulo'] }}
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($this->reporte['bloques'] as $bloque)
                                    @foreach ($bloque['campos'] as $campo)
                                        <th colspan="{{ $campo['colspan'] }}" class="border border-slate-400 px-2 py-2 font-black"
                                            style="background: {{ $campo['color_fondo'] }}; color: {{ $campo['color_texto'] }};">
                                            {{ mb_strtoupper($campo['nombre']) }}
                                        </th>
                                    @endforeach
                                    <th rowspan="2" class="w-16 border border-slate-400 bg-lime-100 px-2 py-2 font-black text-lime-900">
                                        PROM. GRAL.
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($this->reporte['bloques'] as $bloque)
                                    @foreach ($bloque['materias'] as $materia)
                                        <th class="h-40 w-12 border border-slate-400 p-0 align-bottom font-black"
                                            style="background: {{ $materia['campo_color_fondo'] }}; color: {{ $materia['campo_color_texto'] }};">
                                            <span class="mx-auto block [writing-mode:vertical-rl] rotate-180 px-2 py-3">
                                                {{ mb_strtoupper($materia['materia']) }}
                                            </span>
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-slate-50 font-black text-slate-900 dark:bg-neutral-950 dark:text-white">
                                <th class="sticky left-0 z-10 border border-slate-400 bg-slate-100 px-3 py-5 dark:bg-neutral-800">MATUTINO</th>
                                @foreach ($this->reporte['bloques'] as $bloque)
                                    @foreach ($bloque['materias'] as $materia)
                                        <td class="border border-slate-400 px-3 py-5 text-base">
                                            {{ $this->formatearPromedio($materia['promedio_metodo_a']) }}
                                            @if ($materia['provisional'])
                                                <span class="mt-1 block text-[9px] font-black text-amber-600">PROV.</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="border border-slate-400 bg-lime-50 px-3 py-5 text-base font-black text-lime-900">
                                        {{ $this->formatearPromedio($bloque['promedio_general']) }}
                                    </td>
                                @endforeach
                            </tr>
                            <tr class="bg-yellow-300 font-black text-slate-950">
                                <th class="sticky left-0 z-10 border border-slate-400 bg-yellow-300 px-3 py-5 text-left">PROM. GRAL. DE LA ESCUELA</th>
                                @foreach ($this->reporte['bloques'] as $bloque)
                                    @foreach ($bloque['materias'] as $materia)
                                        <td class="border border-slate-400 px-3 py-5 text-base">
                                            {{ $this->formatearPromedio($materia['promedio_metodo_b']) }}
                                        </td>
                                    @endforeach
                                    <td class="border border-slate-400 bg-yellow-200 px-3 py-5 text-base">
                                        {{ $this->formatearPromedio($bloque['promedio_general']) }}
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="{{ 1 + collect($this->reporte['bloques'])->sum(fn ($bloque) => count($bloque['materias']) + 1) }}"
                                    class="border border-slate-400 bg-yellow-50 px-4 py-3 text-left text-sm font-black text-slate-800">
                                    {{ $this->reporte['nota'] }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <h3 class="text-lg font-black text-slate-950 dark:text-white">Detalle por grupo</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Compara los dos métodos de cálculo y conserva los resultados provisionales.</p>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach ($this->reporte['grupos'] as $grupo)
                        <details class="group overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm open:shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5">
                                <div>
                                    <h4 class="font-black text-slate-950 dark:text-white">{{ $grupo['titulo'] }}</h4>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        {{ $grupo['total_alumnos'] }} alumnos · Generación {{ $grupo['generacion'] }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-black text-[#006492] dark:text-sky-300">{{ $this->formatearPromedio($grupo['promedio_general']) }}</p>
                                    <span class="text-xs font-black {{ $grupo['provisional'] ? 'text-amber-600' : 'text-emerald-600' }}">
                                        {{ $grupo['provisional'] ? 'Provisional' : 'Definitivo' }}
                                    </span>
                                </div>
                            </summary>
                            <div class="border-t border-slate-200 dark:border-neutral-800">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-900 text-xs uppercase text-white">
                                            <tr>
                                                <th class="px-4 py-3 text-left">Materia</th>
                                                <th class="px-4 py-3 text-left">Campo</th>
                                                <th class="px-4 py-3 text-center">Método A</th>
                                                <th class="px-4 py-3 text-center">Método B</th>
                                                <th class="px-4 py-3 text-center">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                            @foreach ($grupo['materias'] as $materia)
                                                <tr>
                                                    <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">{{ $materia['materia'] }}</td>
                                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $materia['campo_formativo'] }}</td>
                                                    <td class="px-4 py-3 text-center font-black">{{ $this->formatearPromedio($materia['promedio_metodo_a']) }}</td>
                                                    <td class="px-4 py-3 text-center font-black">{{ $this->formatearPromedio($materia['promedio_metodo_b']) }}</td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $materia['provisional'] ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                            {{ $materia['provisional'] ? 'Provisional' : 'Definitivo' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>
            </section>

            <section class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex flex-col gap-3 border-b border-slate-200 p-5 md:flex-row md:items-center md:justify-between dark:border-neutral-800">
                    <div>
                        <h3 class="font-black text-slate-950 dark:text-white">Detalle por alumno y materia</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Los valores AC, NP u otras claves se muestran, pero no participan en el promedio.</p>
                    </div>
                    <div class="w-full md:max-w-md">
                        <flux:input wire:model.live.debounce.350ms="buscar" icon="magnifying-glass" placeholder="Buscar alumno, matrícula o materia" />
                    </div>
                </div>
                <div class="max-h-[720px] overflow-auto">
                    <table class="min-w-[1050px] w-full text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-900 text-xs uppercase text-white">
                            <tr>
                                <th class="px-4 py-3 text-left">Alumno</th>
                                <th class="px-4 py-3 text-left">Ubicación</th>
                                <th class="px-4 py-3 text-left">Materia</th>
                                <th class="px-4 py-3 text-center">Evaluaciones</th>
                                <th class="px-4 py-3 text-center">Prom. materia</th>
                                <th class="px-4 py-3 text-center">Prom. general</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                            @forelse ($this->alumnosFiltrados as $alumno)
                                @foreach ($alumno['materias'] as $materia)
                                    <tr class="hover:bg-sky-50/50 dark:hover:bg-sky-950/10">
                                        <td class="px-4 py-3">
                                            <p class="font-black text-slate-950 dark:text-white">{{ $alumno['alumno'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $alumno['matricula'] }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                            {{ $alumno['grado'] }} · Grupo {{ $alumno['grupo'] }}
                                            @if ($alumno['semestre'])
                                                · Sem. {{ $alumno['semestre'] }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-slate-900 dark:text-white">{{ $materia['materia'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $materia['campo_formativo'] }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-center gap-1.5">
                                                @foreach ($materia['evaluaciones_esperadas'] as $clave)
                                                    <span class="inline-flex min-w-10 justify-center rounded-lg border px-2 py-1 text-xs font-black {{ array_key_exists($clave, $materia['especiales']) ? 'border-violet-200 bg-violet-50 text-violet-700' : 'border-slate-200 bg-slate-50 text-slate-700' }}">
                                                        {{ $materia['evaluaciones'][$clave] !== null ? $this->formatearPromedio($materia['evaluaciones'][$clave]) : ($materia['especiales'][$clave] ?? '—') }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center text-lg font-black">{{ $this->formatearPromedio($materia['promedio']) }}</td>
                                        <td class="px-4 py-3 text-center text-lg font-black text-[#006492] dark:text-sky-300">{{ $this->formatearPromedio($alumno['promedio_general']) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $materia['provisional'] ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                {{ $materia['provisional'] ? 'Provisional' : 'Definitivo' }}
                                            </span>
                                            @if ($materia['tiene_calificacion_externa'])
                                                <span class="mt-1 block text-[10px] font-black text-violet-600">EQUIVALENCIA</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-14 text-center text-slate-500">No hay alumnos que coincidan con la búsqueda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if (auth()->user()?->is_admin)
                <details class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5">
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">Revisión de campos formativos</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">La clasificación fue sugerida automáticamente y puede corregirse aquí.</p>
                        </div>
                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-700">{{ $this->materiasClasificacion->count() }} materias</span>
                    </summary>
                    <div class="border-t border-slate-200 p-5 dark:border-neutral-800">
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($this->materiasClasificacion as $materia)
                                <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                                    <p class="mb-3 font-black text-slate-900 dark:text-white">{{ $materia->materia }}</p>
                                    <flux:select
                                        value="{{ $materia->campo_formativo_id }}"
                                        label="Campo formativo"
                                        wire:change="actualizarCampoFormativo({{ $materia->id }}, $event.target.value)">
                                        @foreach ($camposFormativos as $campo)
                                            <option value="{{ $campo->id }}" @selected((int) $materia->campo_formativo_id === (int) $campo->id)>
                                                {{ $campo->nombre }}
                                            </option>
                                        @endforeach
                                    </flux:select>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </details>
            @endif
        @else
            <section class="rounded-[1.8rem] border border-dashed border-slate-300 bg-white p-10 text-center dark:border-neutral-700 dark:bg-neutral-900">
                <flux:icon.chart-bar class="mx-auto h-10 w-10 text-slate-400" />
                <h3 class="mt-3 text-lg font-black text-slate-900 dark:text-white">Sin calificaciones para concentrar</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Selecciona otro ciclo, grado o grupo, o verifica que existan calificaciones numéricas.</p>
            </section>
        @endif
    @endunless
</div>
