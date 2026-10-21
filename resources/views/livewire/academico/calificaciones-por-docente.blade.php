<div class="mx-auto max-w-[1600px] space-y-6 px-2 pb-10 sm:px-4">
    <section class="overflow-hidden rounded-[28px] border border-sky-200 bg-gradient-to-br from-sky-950 via-[#006492] to-teal-800 p-6 text-white shadow-lg dark:border-slate-700 md:p-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[.22em] text-sky-100">Centro Universitario Moctezuma · Control escolar</p>
                <h1 class="text-2xl font-black tracking-tight md:text-3xl">Calificaciones por Docente PRO</h1>
                <p class="mt-2 max-w-2xl text-sm text-sky-100">Captura administrativa, plantillas Excel y seguimiento de materias. Las boletas solo cambian tras la confirmación explícita.</p>
            </div>
            <a href="{{ route('submodulos.accion', ['slug_nivel' => $slug_nivel, 'accion' => 'calificaciones']) }}" wire:navigate class="inline-flex items-center justify-center rounded-xl border border-white/30 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/10">← Captura general</a>
        </div>
    </section>

    <div class="flex flex-wrap gap-2">
        @foreach (['primaria' => 'Primaria', 'secundaria' => 'Secundaria', 'bachillerato' => 'Bachillerato'] as $nivelSlug => $nivelNombre)
            <a wire:navigate href="{{ route('misrutas.calificaciones-docente', ['slug_nivel' => $nivelSlug]) }}" class="rounded-xl border px-4 py-2.5 text-sm font-bold {{ $slug_nivel === $nivelSlug ? 'border-sky-700 bg-[#006492] text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200' }}">{{ $nivelNombre }}</a>
        @endforeach
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-black text-slate-900 dark:text-white">1. Seleccionar contexto académico</h2>
            <span class="rounded-full bg-lime-100 px-3 py-1 text-xs font-bold text-lime-800 dark:bg-lime-900/30 dark:text-lime-200">Solo administración</span>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="cp-ciclo" class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-slate-300">Ciclo escolar</label>
                <select id="cp-ciclo" wire:model.live="ciclo_escolar_id" class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                    <option value="">Seleccionar ciclo</option>
                    @foreach ($ciclos as $ciclo)
                        <option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }} {{ $ciclo->es_actual && !$ciclo->cerrado_at ? '· Vigente' : '· Histórico' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="cp-profesor" class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-slate-300">Docente</label>
                <select id="cp-profesor" wire:model.live="profesor_id" class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                    <option value="">Seleccionar docente</option>
                    @foreach ($profesores as $profesor)
                        <option value="{{ $profesor->id }}">{{ trim($profesor->apellido_paterno.' '.$profesor->apellido_materno.' '.$profesor->nombre) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="cp-periodo" class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-slate-300">{{ $slug_nivel === 'bachillerato' ? 'Parcial' : 'Periodo de evaluación' }}</label>
                <select id="cp-periodo" wire:model.live="evaluacion_id" class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                    <option value="">Seleccionar evaluación</option>
                    @foreach ($evaluaciones as $evaluacion)
                        <option value="{{ $evaluacion['id'] }}">{{ $evaluacion['nombre'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="cp-materia" class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-slate-300">Materia y grupo</label>
                <select id="cp-materia" wire:model.live="asignacion_id" class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                    <option value="">Seleccionar materia</option>
                    @foreach ($asignaciones as $asig)
                        <option value="{{ $asig->id }}">{{ $asig->materia?->materia }} · {{ $asig->grado?->nombre }} {{ $asig->grupo?->asignacionGrupo?->nombre ?? $asig->grupo?->clave }} {{ $asig->semestre?->nombre ? '· '.$asig->semestre->nombre : '' }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if ($ciclo_escolar_id && (!$ciclos->firstWhere('id', (int)$ciclo_escolar_id)?->es_actual || $ciclos->firstWhere('id', (int)$ciclo_escolar_id)?->cerrado_at))
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-200">Ciclo histórico: consulta y descarga de plantillas habilitadas. Las correcciones requieren la autorización y bitácora de la <a class="underline" href="{{ route('submodulos.accion', ['slug_nivel' => $slug_nivel, 'accion' => 'calificaciones']) }}">captura general</a>.</div>
        @endif
        @error('ciclo_escolar_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('asignacion_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('evaluacion_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('profesor_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </section>

    @if ($profesor_id && $evaluacion_id)
        @php($numeroFinalizados = collect($avance)->filter(fn ($e) => $e === 'finalizado')->count())
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">2. Avance del docente</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $numeroFinalizados }} de {{ count($avance) }} materias finalizadas para esta evaluación.</p>
                </div>
                <span class="text-2xl font-black text-[#006492] dark:text-sky-300">{{ count($avance) ? round($numeroFinalizados * 100 / count($avance)) : 0 }}%</span>
            </div>
            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-neutral-700"><div class="h-full rounded-full bg-[#88AC2E]" style="width: {{ count($avance) ? round($numeroFinalizados * 100 / count($avance)) : 0 }}%"></div></div>
            <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($asignaciones as $asig)
                    @php($estado = $avance[$asig->id] ?? 'pendiente')
                    <button type="button" wire:click="$set('asignacion_id', '{{ $asig->id }}')" wire:key="doc-asig-{{ $asig->id }}" class="rounded-xl border p-3 text-left transition hover:border-sky-400 {{ (int)$asignacion_id === $asig->id ? 'border-sky-500 bg-sky-50 dark:bg-sky-950/30' : 'border-slate-200 dark:border-neutral-700' }}">
                        <p class="text-sm font-bold text-slate-800 dark:text-white">{{ $asig->materia?->materia }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $asig->grado?->nombre }} · {{ $asig->grupo?->asignacionGrupo?->nombre ?? $asig->grupo?->clave }}</p>
                        <span class="mt-2 inline-block rounded-lg px-2 py-1 text-[11px] font-black {{ $estado === 'finalizado' ? 'bg-lime-100 text-lime-800' : ($estado === 'revision' ? 'bg-amber-100 text-amber-800' : ($estado === 'sin_periodo' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600')) }}">{{ ['finalizado'=>'Finalizado', 'revision'=>'En revisión', 'borrador'=>'Borrador', 'pendiente'=>'Pendiente', 'sin_periodo'=>'Sin periodo'][$estado] ?? $estado }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
            <h2 class="text-lg font-black text-slate-900 dark:text-white">3. Plantillas e importación Excel</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Rellena únicamente la columna <strong>NUEVA</strong>; no edites las columnas de identificación. Las celdas vacías no borran calificaciones. En Bachillerato se truncan los decimales conforme a las reglas existentes.</p>
            <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="w-full lg:max-w-xs">
                    <label for="cp-plantilla" class="mb-1 block text-xs font-bold text-slate-600 dark:text-slate-300">Tipo de plantilla</label>
                    <select id="cp-plantilla" wire:model="tipo_plantilla" class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-white">
                        <option value="materia">Materia y grupo seleccionados</option>
                        <option value="profesor">Docente completo (una hoja por materia)</option>
                        <option value="matriz">Matriz de todas las materias</option>
                    </select>
                </div>
                <button wire:click="descargarExcel" wire:loading.attr="disabled" wire:target="descargarExcel" type="button" class="rounded-xl bg-[#006492] px-5 py-3 text-sm font-black text-white hover:bg-sky-800 disabled:opacity-50">↓ Descargar Excel</button>
                @if ($ciclos->firstWhere('id', (int)$ciclo_escolar_id)?->es_actual && !$ciclos->firstWhere('id', (int)$ciclo_escolar_id)?->cerrado_at)
                    <div class="w-full lg:max-w-sm"><label for="cp-archivo" class="mb-1 block text-xs font-bold text-slate-600 dark:text-slate-300">Seleccionar Excel (.xlsx o .xls)</label><input id="cp-archivo" type="file" wire:model="archivoExcel" accept=".xlsx,.xls" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm dark:border-neutral-700 dark:text-white"></div>
                    <button type="button" wire:click="analizarExcel" wire:loading.attr="disabled" wire:target="analizarExcel" class="rounded-xl bg-[#88AC2E] px-5 py-3 text-sm font-black text-white hover:bg-lime-700 disabled:opacity-50">Analizar Excel</button>
                @endif
            </div>
            @error('archivoExcel') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            @if ($erroresImportacion)
                <div class="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-black">{{ count($erroresImportacion) }} incidencias que requieren revisión</p>
                    <button type="button" wire:click="descargarIncidencias" class="mt-2 rounded-lg border border-amber-500 px-3 py-1.5 text-xs font-bold hover:bg-amber-100">↓ Descargar incidencias CSV</button>
                    <ul class="mt-2 max-h-40 list-inside list-disc space-y-1 overflow-auto">@foreach ($erroresImportacion as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
        </section>
    @endif

    @if ($asignacion_id && $evaluacion_id && $asignaciones->contains('id', (int)$asignacion_id))
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="text-lg font-black text-slate-900 dark:text-white">4. Captura manual por materia</h2><p class="text-sm text-slate-500">{{ count($alumnos) }} alumnos · {{ $captura?->estado ?? 'Pendiente' }}</p></div>
                @if ($captura?->estado === 'finalizado') <span class="rounded-lg bg-lime-100 px-3 py-2 text-sm font-bold text-lime-800">✓ Materia finalizada y protegida</span> @endif
            </div>
            <div class="mt-4 max-h-[620px] overflow-auto rounded-xl border border-slate-200 dark:border-neutral-700">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-700">
                    <thead class="sticky top-0 z-10 bg-slate-100 text-left text-xs font-black uppercase text-slate-600 dark:bg-neutral-800 dark:text-white"><tr><th class="p-3">Matrícula</th><th class="p-3">Alumno</th><th class="p-3">Actual</th><th class="min-w-28 p-3">Nueva</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($alumnos as $alumno)
                        <tr wire:key="captura-doc-{{ $asignacion_id }}-{{ $alumno['id'] }}" class="hover:bg-sky-50/60 dark:hover:bg-neutral-800">
                            <td class="whitespace-nowrap p-3 font-mono text-xs text-slate-500">{{ $alumno['matricula'] }}</td>
                            <td class="p-3 font-semibold text-slate-700 dark:text-slate-200">{{ $alumno['nombre'] }} @if ($alumno['pendiente'])<span class="text-xs text-amber-700"> · Contexto pendiente</span>@endif</td>
                            <td class="p-3 font-bold text-slate-600 dark:text-slate-300">{{ ($actuales[$alumno['id']] ?? null)?->calificacion ?? '—' }}</td>
                            <td class="p-2"><input type="text" maxlength="5" wire:model="valores.{{ $alumno['id'] }}" @disabled($captura?->estado === 'finalizado' || $alumno['pendiente'] || !$ciclos->firstWhere('id', (int)$ciclo_escolar_id)?->es_actual || $ciclos->firstWhere('id', (int)$ciclo_escolar_id)?->cerrado_at) placeholder="0–10 / AC / NP" class="w-32 rounded-lg border border-slate-300 bg-white p-2 font-bold text-slate-800 disabled:opacity-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white"> @error('valores.'.$alumno['id'])<span class="block text-xs text-red-600">{{ $message }}</span>@enderror</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-7 text-center text-sm text-slate-500">No hay alumnos activos para este grupo y periodo.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($ciclos->firstWhere('id', (int)$ciclo_escolar_id)?->es_actual && !$ciclos->firstWhere('id', (int)$ciclo_escolar_id)?->cerrado_at)
                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($captura?->estado !== 'finalizado')
                        <button wire:click="guardarBorrador" wire:loading.attr="disabled" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:bg-neutral-800 dark:text-white">Guardar borrador</button>
                        <button wire:click="revisarCaptura" wire:loading.attr="disabled" class="rounded-xl bg-[#006492] px-5 py-3 text-sm font-bold text-white hover:bg-sky-800">Revisar cambios</button>
                    @else
                        <p class="text-sm text-slate-500">Para volver a editar, proporciona un motivo administrativo de reapertura abajo.</p>
                    @endif
                </div>
            @endif
        </section>
    @endif

    @if ($mostrarPreview)
        <section class="rounded-3xl border border-sky-300 bg-sky-50 p-5 shadow-sm dark:border-sky-900 dark:bg-sky-950/20">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-black text-slate-900 dark:text-white">Vista previa · {{ count($preview) }} cambios propuestos</h2><button wire:click="$set('mostrarPreview', false)" class="text-sm font-bold text-sky-700 hover:underline dark:text-sky-300">Ocultar</button></div>
            <p class="mb-3 text-sm text-slate-600 dark:text-slate-300">Desmarca cualquier cambio que no deba guardarse. Los registros con error se omiten y se muestran arriba.</p>
            <div class="max-h-[450px] overflow-auto rounded-xl border border-sky-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
                <table class="min-w-full divide-y text-sm dark:divide-neutral-700">
                    <thead class="sticky top-0 bg-slate-100 text-xs font-bold text-slate-700 dark:bg-neutral-800 dark:text-white"><tr><th class="p-3">Aplicar</th><th class="p-3">Alumno</th><th class="p-3">Materia #</th><th class="p-3">Anterior</th><th class="p-3">Nuevo</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @foreach ($preview as $idx => $row)
                            <tr wire:key="revision-{{ $idx }}"><td class="p-3"><input type="checkbox" wire:model="aplicar.{{ $idx }}" class="size-4 accent-[#006492]"></td><td class="p-3"><span class="font-semibold">{{ $row['alumno'] }}</span><span class="block text-xs text-slate-500">{{ $row['matricula'] }}</span></td><td class="p-3">{{ $row['asignacion_id'] }}</td><td class="p-3 font-semibold">{{ $row['anterior'] ?? '—' }}</td><td class="p-3 font-black text-sky-700 dark:text-sky-300"><input type="text" maxlength="5" wire:model="preview.{{ $idx }}.nuevo" class="w-24 rounded-lg border border-sky-200 bg-white p-2 text-sm font-bold text-sky-800 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white" aria-label="Nueva calificación de {{ $row['alumno'] }}">@if (!empty($row['aviso']))<span class="block max-w-48 text-xs font-normal text-amber-700">{{ $row['aviso'] }}</span>@endif</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                <div><label class="mb-1 block text-xs font-bold text-slate-600 dark:text-slate-300">Motivo de confirmación (obligatorio, mínimo 10 caracteres)</label><input wire:model="motivo" maxlength="500" type="text" placeholder="Ej. Calificaciones del primer periodo verificadas por Control Escolar" class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-white">@error('motivo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <button wire:click="confirmar" wire:confirm="¿Confirmas guardar las calificaciones seleccionadas en las boletas oficiales?" wire:loading.attr="disabled" class="rounded-xl bg-[#88AC2E] px-6 py-3 text-sm font-black text-white hover:bg-lime-700">Confirmar cambios seleccionados</button>
            </div>
        </section>
    @endif
    @error('preview') <p class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $message }}</p> @enderror

    @if ($captura?->estado === 'finalizado' && $asignacion_id)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/20">
            <h3 class="font-black text-amber-900 dark:text-amber-200">Reabrir materia finalizada</h3>
            <p class="my-2 text-sm text-amber-800 dark:text-amber-300">Solo administración. Se registrará el motivo y la reapertura en la bitácora; las calificaciones existentes no se borrarán.</p>
            <div class="flex flex-col gap-3 md:flex-row"><input wire:model="motivo" maxlength="500" placeholder="Motivo administrativo (mínimo 10 caracteres)" class="min-w-0 flex-1 rounded-xl border border-amber-300 p-3 text-sm dark:bg-neutral-800 dark:text-white"><button wire:click="reabrirMateria" wire:confirm="¿Reabrir esta captura?" class="rounded-xl bg-amber-700 px-4 py-3 text-sm font-bold text-white">Reabrir con bitácora</button></div>
            @error('motivo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endif
</div>
