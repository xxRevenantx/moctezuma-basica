@php
    $cicloSeleccionado = $ciclos->firstWhere('id', (int) $ciclo_escolar_id);
    $permiteEdicion = (bool) ($cicloSeleccionado?->es_actual && !$cicloSeleccionado?->cerrado_at);
    $totalMaterias = count($avance);
    $materiasFinalizadas = collect($avance)->filter(fn ($estado) => $estado === 'finalizado')->count();
    $materiasRevision = collect($avance)->filter(fn ($estado) => $estado === 'revision')->count();
    $materiasBorrador = collect($avance)->filter(fn ($estado) => $estado === 'borrador')->count();
    $porcentajeAvance = $totalMaterias ? (int) round(100 * $materiasFinalizadas / $totalMaterias) : 0;
    $materiaSeleccionada = $asignaciones->firstWhere('id', (int) $asignacion_id);
@endphp

<div class="mx-auto max-w-[1580px] space-y-5 px-2 pb-12 sm:px-4" x-data>
    {{-- Encabezado compacto, integrado al lenguaje visual del sistema. --}}
    <header class="relative overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#006492] via-sky-400 to-[#88AC2E]"></div>
        <div class="flex flex-col justify-between gap-5 px-5 py-6 md:flex-row md:items-center md:px-7">
            <div class="flex min-w-0 items-start gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-sm shadow-sky-800/20">
                    <flux:icon.academic-cap class="size-6" />
                </div>
                <div class="min-w-0">
                    <div class="mb-1 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-[.14em] text-[#006492] dark:text-sky-300">Control escolar / Evaluación académica</span>
                        <flux:badge size="sm" color="lime">PRO</flux:badge>
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white md:text-[28px]">Calificaciones por docente</h1>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">Organiza la carga académica, captura calificaciones y valida archivos Excel sin modificar las boletas hasta su confirmación.</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <flux:badge color="green" icon="shield-check">Acceso administrativo</flux:badge>
                <a href="{{ route('submodulos.accion', ['slug_nivel' => $slug_nivel, 'accion' => 'calificaciones']) }}" wire:navigate class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-sky-200 hover:bg-sky-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:bg-neutral-700">
                    <flux:icon.arrow-left class="size-4" /> Captura general
                </a>
            </div>
        </div>
    </header>

    {{-- Navegación por nivel, conserva las rutas existentes. --}}
    <nav aria-label="Niveles de calificaciones" class="flex flex-wrap items-center gap-2">
        @foreach (['primaria' => 'Primaria', 'secundaria' => 'Secundaria', 'bachillerato' => 'Bachillerato'] as $nivelSlug => $nivelNombre)
            <a href="{{ route('misrutas.calificaciones-docente', ['slug_nivel' => $nivelSlug]) }}" wire:navigate
                @if ($slug_nivel === $nivelSlug) aria-current="page" @endif
                class="inline-flex h-10 items-center gap-2 rounded-xl border px-4 text-sm font-semibold shadow-sm transition {{ $slug_nivel === $nivelSlug ? 'border-[#006492] bg-[#006492] text-white shadow-sky-900/10' : 'border-slate-200 bg-white text-slate-600 hover:border-sky-200 hover:text-[#006492] dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300 dark:hover:text-sky-300' }}">
                <flux:icon.book-open class="size-4" /> {{ $nivelNombre }}
            </a>
        @endforeach
        <span class="ml-auto hidden items-center gap-1.5 text-xs font-medium text-slate-400 lg:inline-flex"><flux:icon.lock-closed class="size-3.5" /> Captura protegida</span>
    </nav>

    {{-- Filtros principales. --}}
    <section aria-labelledby="docente-filtros-titulo" class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 md:p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-sky-50 text-[#006492] dark:bg-sky-950/40 dark:text-sky-300"><flux:icon.adjustments-horizontal class="size-5" /></div>
                <div>
                    <h2 id="docente-filtros-titulo" class="text-base font-bold text-slate-900 dark:text-white">Contexto académico</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Selecciona el ciclo, docente y evaluación para comenzar</p>
                </div>
            </div>
            <flux:badge :color="$permiteEdicion ? 'green' : 'amber'" :icon="$permiteEdicion ? 'pencil-square' : 'lock-closed'">{{ $permiteEdicion ? 'Periodo disponible para captura' : 'Consulta histórica / protegida' }}</flux:badge>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar" id="cp-ciclo">
                    <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                    @foreach ($ciclos as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }} – {{ $ciclo->fin_anio }} {{ $ciclo->es_actual && !$ciclo->cerrado_at ? '· Vigente' : '· Histórico' }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="ciclo_escolar_id" />
            </div>
            <div>
                <flux:select wire:model.live="profesor_id" label="Docente" id="cp-profesor" :disabled="!$ciclo_escolar_id">
                    <flux:select.option value="">Selecciona un docente</flux:select.option>
                    @foreach ($profesores as $profesor)
                        <flux:select.option value="{{ $profesor->id }}">{{ trim($profesor->apellido_paterno.' '.$profesor->apellido_materno.' '.$profesor->nombre) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="profesor_id" />
            </div>
            <div>
                <flux:select wire:model.live="evaluacion_id" label="{{ $slug_nivel === 'bachillerato' ? 'Parcial' : 'Periodo de evaluación' }}" id="cp-periodo" :disabled="!$ciclo_escolar_id">
                    <flux:select.option value="">Selecciona una evaluación</flux:select.option>
                    @foreach ($evaluaciones as $evaluacion)
                        <flux:select.option value="{{ $evaluacion['id'] }}">{{ $evaluacion['nombre'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="evaluacion_id" />
            </div>
            <div>
                <flux:select wire:model.live="asignacion_id" label="Materia y grupo" id="cp-materia" :disabled="!$profesor_id || !$evaluacion_id">
                    <flux:select.option value="">Selecciona materia y grupo</flux:select.option>
                    @foreach ($asignaciones as $asig)
                        <flux:select.option value="{{ $asig->id }}">{{ $asig->materia?->materia }} · {{ $asig->grado?->nombre }} {{ $asig->grupo?->asignacionGrupo?->nombre ?? $asig->grupo?->clave }} {{ $asig->semestre?->nombre ? '· '.$asig->semestre->nombre : '' }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="asignacion_id" />
            </div>
        </div>
        @if ($ciclo_escolar_id && !$permiteEdicion)
            <div class="mt-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-200">
                <flux:icon.exclamation-triangle class="mt-0.5 size-5 shrink-0" />
                <p><strong>Ciclo histórico.</strong> Puedes consultar y descargar plantillas. Las correcciones deben autorizarse desde la <a class="font-semibold underline underline-offset-2" href="{{ route('submodulos.accion', ['slug_nivel' => $slug_nivel, 'accion' => 'calificaciones']) }}">captura general</a> con registro en bitácora.</p>
            </div>
        @endif
    </section>

    @if ($profesor_id && $evaluacion_id)
        {{-- Indicadores rápidos. --}}
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ([
                ['titulo' => 'Materias asignadas', 'valor' => $totalMaterias, 'icono' => 'book-open', 'color' => 'text-[#006492]', 'fondo' => 'bg-sky-50 dark:bg-sky-950/30'],
                ['titulo' => 'Finalizadas', 'valor' => $materiasFinalizadas, 'icono' => 'check-circle', 'color' => 'text-emerald-700 dark:text-emerald-300', 'fondo' => 'bg-emerald-50 dark:bg-emerald-950/30'],
                ['titulo' => 'En revisión', 'valor' => $materiasRevision, 'icono' => 'magnifying-glass', 'color' => 'text-amber-700 dark:text-amber-300', 'fondo' => 'bg-amber-50 dark:bg-amber-950/30'],
                ['titulo' => 'Borradores', 'valor' => $materiasBorrador, 'icono' => 'pencil-square', 'color' => 'text-slate-600 dark:text-slate-200', 'fondo' => 'bg-slate-100 dark:bg-neutral-800'],
            ] as $indicador)
                <article class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="min-w-0"><p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $indicador['titulo'] }}</p><p class="mt-1 text-2xl font-extrabold tabular-nums text-slate-900 dark:text-white">{{ $indicador['valor'] }}</p></div>
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $indicador['fondo'] }} {{ $indicador['color'] }}"><flux:icon :name="$indicador['icono']" class="size-5" /></div>
                </article>
            @endforeach
        </div>

        {{-- Avance docente con selección de asignación desde cada tarjeta. --}}
        <section aria-labelledby="docente-avance-titulo" class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 id="docente-avance-titulo" class="text-base font-bold text-slate-900 dark:text-white">Avance académico del docente</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Selecciona una materia para revisar su captura y consultar los alumnos del grupo.</p>
                </div>
                <div class="flex items-center gap-3"><strong class="text-2xl font-extrabold tabular-nums text-[#006492] dark:text-sky-300">{{ $porcentajeAvance }}%</strong><span class="text-xs text-slate-500">{{ $materiasFinalizadas }} / {{ $totalMaterias }} completadas</span></div>
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-neutral-800" role="progressbar" aria-valuenow="{{ $porcentajeAvance }}" aria-valuemin="0" aria-valuemax="100" aria-label="Avance de materias finalizadas"><div class="h-full rounded-full bg-gradient-to-r from-[#006492] to-[#88AC2E] transition-all duration-300" style="width: {{ $porcentajeAvance }}%"></div></div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($asignaciones as $asig)
                    @php
                        $estado = $avance[$asig->id] ?? 'pendiente';
                        $esSeleccionada = (int) $asignacion_id === (int) $asig->id;
                        $estadoColor = match ($estado) { 'finalizado' => 'green', 'revision' => 'amber', 'borrador' => 'blue', 'sin_periodo' => 'red', default => 'zinc' };
                        $estadoTexto = ['finalizado' => 'Finalizada', 'revision' => 'En revisión', 'borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'sin_periodo' => 'Sin periodo'][$estado] ?? $estado;
                    @endphp
                    <button type="button" wire:click="$set('asignacion_id', '{{ $asig->id }}')" wire:key="doc-asig-{{ $asig->id }}"
                        @if ($esSeleccionada) aria-pressed="true" @else aria-pressed="false" @endif
                        class="group relative flex min-h-28 flex-col justify-between gap-3 rounded-2xl border px-4 py-3 text-left transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600 {{ $esSeleccionada ? 'border-sky-400 bg-sky-50 shadow-[inset_3px_0_0_#006492] dark:border-sky-700 dark:bg-sky-950/20' : 'border-slate-200 bg-white hover:-translate-y-px hover:border-sky-300 hover:shadow-sm dark:border-neutral-700 dark:bg-neutral-900 dark:hover:border-sky-700' }}">
                        <div class="flex items-start justify-between gap-2"><span class="line-clamp-2 font-bold text-slate-800 dark:text-white">{{ $asig->materia?->materia }}</span><flux:icon.chevron-right class="size-4 shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-[#006492]" /></div>
                        <div class="flex flex-wrap items-center justify-between gap-2"><span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $asig->grado?->nombre }} · Grupo {{ $asig->grupo?->asignacionGrupo?->nombre ?? $asig->grupo?->clave }} {{ $asig->semestre?->nombre ? '· '.$asig->semestre->nombre : '' }}</span><flux:badge :color="$estadoColor" size="sm">{{ $estadoTexto }}</flux:badge></div>
                    </button>
                @empty
                    <div class="col-span-full flex flex-col items-center rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-center dark:border-neutral-700"><flux:icon.book-open class="mb-2 size-7 text-slate-300" /><p class="text-sm font-semibold text-slate-600 dark:text-slate-300">No hay materias asignadas</p><p class="mt-1 text-xs text-slate-500">Verifica las asignaciones del docente para este ciclo escolar.</p></div>
                @endforelse
            </div>
        </section>

        {{-- Área de Excel independiente de la captura manual. --}}
        <section aria-labelledby="docente-excel-titulo" class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 md:p-6">
            <div class="mb-5 flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-lime-50 text-[#719b20] dark:bg-lime-950/30 dark:text-lime-300"><flux:icon.document-arrow-up class="size-5" /></div>
                <div><h2 id="docente-excel-titulo" class="text-base font-bold text-slate-900 dark:text-white">Plantillas e importación de Excel</h2><p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Descarga una plantilla con los alumnos registrados. Para importar, completa solo la columna <strong>NUEVA</strong>; los valores vacíos no eliminan calificaciones existentes.</p></div>
            </div>
            <div class="grid gap-4 xl:grid-cols-2">
                <div class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-700 dark:bg-neutral-800/40">
                    <div class="flex items-center gap-2"><flux:icon.arrow-down-tray class="size-4 text-[#006492] dark:text-sky-300" /><h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">1. Descargar plantilla</h3></div>
                    <flux:select wire:model="tipo_plantilla" label="Formato de archivo" id="cp-plantilla">
                        <flux:select.option value="materia">Materia y grupo seleccionados</flux:select.option>
                        <flux:select.option value="profesor">Docente completo (una hoja por materia)</flux:select.option>
                        <flux:select.option value="matriz">Matriz de todas las materias</flux:select.option>
                    </flux:select>
                    <flux:button variant="primary" icon="arrow-down-tray" wire:click="descargarExcel" wire:loading.attr="disabled" wire:target="descargarExcel" class="w-full !bg-[#006492] hover:!bg-[#004f77]">Descargar Excel</flux:button>
                </div>
                <div class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-700 dark:bg-neutral-800/40">
                    <div class="flex items-center gap-2"><flux:icon.arrow-up-tray class="size-4 text-[#719b20] dark:text-lime-300" /><h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">2. Importar y validar</h3></div>
                    @if ($permiteEdicion)
                        <flux:input id="cp-archivo" type="file" wire:model="archivoExcel" accept=".xlsx,.xls" label="Archivo Excel (.xlsx o .xls)" />
                        <flux:button wire:click="analizarExcel" wire:loading.attr="disabled" wire:target="analizarExcel,archivoExcel" icon="magnifying-glass" variant="primary" class="w-full !bg-[#88AC2E] hover:!bg-[#729425]">Analizar archivo</flux:button>
                    @else
                        <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200"><flux:icon.lock-closed class="size-4 shrink-0" />La importación está deshabilitada para ciclos históricos o cerrados.</div>
                    @endif
                    <flux:error name="archivoExcel" />
                </div>
            </div>
            <div class="mt-4 flex items-start gap-2 rounded-xl bg-sky-50 px-4 py-3 text-xs leading-5 text-[#005b84] dark:bg-sky-950/30 dark:text-sky-200"><flux:icon.information-circle class="mt-0.5 size-4 shrink-0" /><span>La importación solo prepara una vista previa. <strong>Ninguna boleta se modifica</strong> hasta que confirmes los cambios. Bachillerato conserva sus reglas de truncamiento.</span></div>
            @if ($erroresImportacion)
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20">
                    <div class="flex flex-wrap items-center justify-between gap-3"><div class="flex items-center gap-2"><flux:icon.exclamation-triangle class="size-5 text-amber-700 dark:text-amber-300" /><p class="text-sm font-bold text-amber-900 dark:text-amber-200">{{ count($erroresImportacion) }} incidencias detectadas</p></div><flux:button icon="arrow-down-tray" size="sm" variant="filled" wire:click="descargarIncidencias">Descargar CSV</flux:button></div>
                    <ul class="mt-3 max-h-40 list-inside list-disc space-y-1 overflow-y-auto text-xs text-amber-900 dark:text-amber-200">@foreach ($erroresImportacion as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
        </section>
    @else
        {{-- Estado inicial sin docente y periodo: guía de uso no invasiva. --}}
        <section class="rounded-[24px] border border-dashed border-slate-200 bg-white px-6 py-12 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
            <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-2xl bg-sky-50 text-[#006492] dark:bg-sky-950/30 dark:text-sky-300"><flux:icon.user-group class="size-6" /></div>
            <h2 class="font-bold text-slate-800 dark:text-white">Selecciona un docente y un periodo</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">Aquí aparecerán las materias asignadas, el progreso de captura y las herramientas de importación Excel.</p>
        </section>
    @endif

    {{-- Captura manual: conserva la lógica Livewire y la información por alumno. --}}
    @if ($asignacion_id && $evaluacion_id && $materiaSeleccionada)
        <section aria-labelledby="docente-captura-titulo" class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-5 md:px-6">
                <div class="flex items-start gap-3"><div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-[#006492] dark:bg-sky-950/30 dark:text-sky-300"><flux:icon.pencil-square class="size-5" /></div><div><h2 id="docente-captura-titulo" class="text-base font-bold text-slate-900 dark:text-white">Captura manual de calificaciones</h2><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $materiaSeleccionada->materia?->materia }} · {{ $materiaSeleccionada->grado?->nombre }} {{ $materiaSeleccionada->grupo?->asignacionGrupo?->nombre ?? $materiaSeleccionada->grupo?->clave }} · {{ count($alumnos) }} alumnos</p></div></div>
                <flux:badge :color="($captura?->estado ?? 'pendiente') === 'finalizado' ? 'green' : (($captura?->estado ?? 'pendiente') === 'revision' ? 'amber' : 'zinc')" :icon="$captura?->estado === 'finalizado' ? 'lock-closed' : 'clipboard-document-list'">{{ ['finalizado' => 'Finalizada y protegida', 'revision' => 'En revisión', 'borrador' => 'Borrador', 'pendiente' => 'Pendiente'][$captura?->estado ?? 'pendiente'] ?? ucfirst($captura?->estado ?? 'pendiente') }}</flux:badge>
            </div>
            <div class="max-h-[630px] overflow-auto border-y border-slate-100 dark:border-neutral-800">
                <table class="min-w-[690px] w-full border-collapse text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-neutral-800 dark:text-slate-300">
                        <tr><th class="w-14 px-5 py-3 text-center">#</th><th class="px-4 py-3">Matrícula</th><th class="px-4 py-3">Alumno</th><th class="px-4 py-3 text-center">Actual</th><th class="w-48 px-4 py-3">Nueva calificación</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @forelse ($alumnos as $alumno)
                            <tr wire:key="captura-doc-{{ $asignacion_id }}-{{ $alumno['id'] }}" class="transition hover:bg-sky-50/50 dark:hover:bg-neutral-800/40">
                                <td class="px-5 py-3 text-center text-xs tabular-nums text-slate-400">{{ $loop->iteration }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $alumno['matricula'] }}</td>
                                <td class="px-4 py-3"><span class="font-semibold text-slate-800 dark:text-slate-200">{{ $alumno['nombre'] }}</span>@if ($alumno['pendiente']) <flux:badge size="sm" color="amber" class="mt-1">Contexto pendiente</flux:badge> @endif</td>
                                <td class="px-4 py-3 text-center font-bold tabular-nums text-slate-700 dark:text-slate-200">{{ ($actuales[$alumno['id']] ?? null)?->calificacion ?? '—' }}</td>
                                <td class="px-4 py-2.5"><flux:input size="sm" type="text" maxlength="5" wire:model="valores.{{ $alumno['id'] }}" aria-label="Nueva calificación de {{ $alumno['nombre'] }}" :disabled="$captura?->estado === 'finalizado' || $alumno['pendiente'] || !$permiteEdicion" placeholder="0–10 / AC / NP" class="!max-w-40" /><flux:error name="valores.{{ $alumno['id'] }}" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">No hay alumnos activos para este grupo y periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($permiteEdicion)
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 md:px-6">
                    <p class="flex items-start gap-2 text-xs text-slate-500 dark:text-slate-400"><flux:icon.shield-check class="mt-0.5 size-4 shrink-0" />Guardado en borrador: sin cambios en boletas oficiales.</p>
                    @if ($captura?->estado !== 'finalizado')
                        <div class="flex flex-wrap gap-2"><flux:button wire:click="guardarBorrador" wire:loading.attr="disabled" wire:target="guardarBorrador" icon="document-check" variant="filled">Guardar borrador</flux:button><flux:button wire:click="revisarCaptura" wire:loading.attr="disabled" wire:target="revisarCaptura" icon="magnifying-glass" variant="primary" class="!bg-[#006492] hover:!bg-[#004f77]">Revisar cambios</flux:button></div>
                    @else
                        <span class="text-xs font-medium text-amber-700 dark:text-amber-300">Reabre la materia con un motivo para editar.</span>
                    @endif
                </div>
            @endif
        </section>
    @endif

    {{-- Comparación previa y aprobación administrativa. --}}
    @if ($mostrarPreview)
        <section aria-labelledby="docente-preview-titulo" class="overflow-hidden rounded-[24px] border border-sky-200 bg-white shadow-sm dark:border-sky-900 dark:bg-neutral-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sky-100 bg-sky-50/70 px-5 py-4 dark:border-sky-900 dark:bg-sky-950/30">
                <div class="flex items-center gap-3"><div class="flex size-9 items-center justify-center rounded-xl bg-[#006492] text-white"><flux:icon.clipboard-document-check class="size-5" /></div><div><h2 id="docente-preview-titulo" class="font-bold text-slate-900 dark:text-white">Revisión antes de confirmar</h2><p class="text-xs text-slate-500 dark:text-slate-300">{{ count($preview) }} cambios propuestos · desmarca los que no debas aplicar</p></div></div>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="$set('mostrarPreview', false)">Ocultar vista previa</flux:button>
            </div>
            <div class="max-h-[480px] overflow-auto">
                <table class="min-w-[690px] w-full text-left text-sm"><thead class="sticky top-0 z-10 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-neutral-800 dark:text-slate-300"><tr><th class="px-5 py-3">Aplicar</th><th class="px-4 py-3">Alumno</th><th class="px-4 py-3">Materia #</th><th class="px-4 py-3">Anterior</th><th class="px-4 py-3">Nueva</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @foreach ($preview as $idx => $row)
                            <tr wire:key="revision-{{ $idx }}" class="hover:bg-sky-50/40 dark:hover:bg-neutral-800/40"><td class="px-5 py-3"><flux:checkbox wire:model="aplicar.{{ $idx }}" aria-label="Aplicar cambio para {{ $row['alumno'] }}" /></td><td class="px-4 py-3"><span class="font-semibold text-slate-800 dark:text-white">{{ $row['alumno'] }}</span><span class="block text-xs text-slate-500">{{ $row['matricula'] }}</span></td><td class="px-4 py-3 text-slate-500">{{ $row['asignacion_id'] }}</td><td class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">{{ $row['anterior'] ?? '—' }}</td><td class="px-4 py-2.5"><flux:input size="sm" type="text" maxlength="5" wire:model="preview.{{ $idx }}.nuevo" class="!max-w-28" aria-label="Nueva calificación de {{ $row['alumno'] }}" />@if (!empty($row['aviso']))<span class="mt-1 block max-w-52 text-xs text-amber-700 dark:text-amber-300">{{ $row['aviso'] }}</span>@endif</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="grid gap-4 border-t border-slate-100 bg-slate-50/60 p-5 dark:border-neutral-800 dark:bg-neutral-800/20 lg:grid-cols-[1fr_auto] lg:items-end">
                <div><flux:input wire:model="motivo" maxlength="500" label="Motivo de confirmación (mínimo 10 caracteres)" placeholder="Ej. Calificaciones revisadas por Control Escolar" /><flux:error name="motivo" /></div>
                <flux:button wire:click="confirmar" wire:confirm="¿Confirmas guardar las calificaciones seleccionadas en las boletas oficiales?" wire:loading.attr="disabled" wire:target="confirmar" variant="primary" icon="check-circle" class="!bg-[#88AC2E] hover:!bg-[#729425]">Confirmar cambios seleccionados</flux:button>
            </div>
        </section>
    @endif
    <flux:error name="preview" />

    @if ($captura?->estado === 'finalizado' && $asignacion_id)
        <section class="rounded-[24px] border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-900 dark:bg-amber-950/20 md:p-6">
            <div class="mb-4 flex items-start gap-3"><div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200"><flux:icon.lock-open class="size-5" /></div><div><h3 class="font-bold text-amber-950 dark:text-amber-100">Reabrir materia finalizada</h3><p class="mt-1 text-xs leading-5 text-amber-800 dark:text-amber-200">Acción exclusiva de administración. Requiere justificación y queda registrada en la bitácora; no borra calificaciones.</p></div></div>
            <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end"><div><flux:input wire:model="motivo" maxlength="500" label="Motivo administrativo" placeholder="Describe la razón de reapertura (mínimo 10 caracteres)" /><flux:error name="motivo" /></div><flux:button wire:click="reabrirMateria" wire:confirm="¿Reabrir esta captura?" icon="lock-open" variant="primary" class="!bg-amber-700 hover:!bg-amber-800">Reabrir con bitácora</flux:button></div>
        </section>
    @endif
</div>
