<div class="space-y-6">
    <section class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] via-indigo-500 to-[#88AC2E]"></div>
        <div class="flex flex-col gap-4 p-5 sm:p-7 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-lg shadow-blue-500/20">
                    <flux:icon.document-text class="h-7 w-7" />
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-[#006492]">Media Superior</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Documentos oficiales</h1>
                    <p class="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                        Registro de escolaridad, actas, kardex y certificados construidos con las calificaciones históricas de bachillerato.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($modulo !== 'inicio')
                    <button type="button" wire:click="seleccionarModulo('inicio')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <flux:icon.squares-2x2 class="h-4 w-4" /> Panel principal
                    </button>
                @endif
                <a href="{{ route('media-superior.documentos.configuracion') }}" wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white shadow-sm hover:bg-slate-800 dark:bg-white dark:text-slate-900">
                    <flux:icon.cog-6-tooth class="h-4 w-4" /> Configuración documental
                </a>
            </div>
        </div>
    </section>

    @if($modulo === 'inicio')
        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @php
                $modulos = [
                    ['id' => 'registro-escolaridad', 'titulo' => 'Registro de escolaridad', 'texto' => 'Oficio horizontal por ciclo, generación, semestre y grupo.', 'icono' => 'table-cells', 'tono' => 'blue'],
                    ['id' => 'acta-resultados', 'titulo' => 'Acta de resultados', 'texto' => 'Acta por materia con calificación final, letra y asistencia.', 'icono' => 'clipboard-document-check', 'tono' => 'emerald'],
                    ['id' => 'kardex', 'titulo' => 'Kardex', 'texto' => 'Historial individual de los seis semestres y materias extra separadas.', 'icono' => 'academic-cap', 'tono' => 'violet'],
                    ['id' => 'certificado', 'titulo' => 'Certificados', 'texto' => 'Certificado parcial o definitivo usando el folio de inscripción.', 'icono' => 'identification', 'tono' => 'amber'],
                ];
            @endphp
            @foreach($modulos as $item)
                <button type="button" wire:click="seleccionarModulo('{{ $item['id'] }}')"
                    class="group overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white text-left shadow-sm transition hover:-translate-y-1 hover:shadow-xl dark:border-slate-800 dark:bg-slate-950">
                    <div @class([
                        'h-1.5',
                        'bg-blue-500' => $item['tono'] === 'blue',
                        'bg-emerald-500' => $item['tono'] === 'emerald',
                        'bg-violet-500' => $item['tono'] === 'violet',
                        'bg-amber-500' => $item['tono'] === 'amber',
                    ])></div>
                    <div class="p-6">
                        <div @class([
                            'flex h-12 w-12 items-center justify-center rounded-2xl',
                            'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300' => $item['tono'] === 'blue',
                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' => $item['tono'] === 'emerald',
                            'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300' => $item['tono'] === 'violet',
                            'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300' => $item['tono'] === 'amber',
                        ])>
                            <flux:icon :name="$item['icono']" class="h-6 w-6" />
                        </div>
                        <h2 class="mt-5 text-lg font-black text-slate-950 dark:text-white">{{ $item['titulo'] }}</h2>
                        <p class="mt-2 min-h-16 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $item['texto'] }}</p>
                        <span class="mt-5 inline-flex items-center gap-1 text-sm font-black text-[#006492] group-hover:gap-2">
                            Abrir módulo <flux:icon.arrow-right class="h-4 w-4" />
                        </span>
                    </div>
                </button>
            @endforeach
        </section>

        <section class="rounded-[1.7rem] border border-blue-100 bg-blue-50/70 p-5 dark:border-blue-900/40 dark:bg-blue-950/20">
            <div class="flex items-start gap-3">
                <flux:icon.information-circle class="mt-0.5 h-6 w-6 shrink-0 text-blue-700 dark:text-blue-300" />
                <div>
                    <h3 class="font-black text-slate-900 dark:text-white">Reglas académicas compartidas</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Solo las materias oficiales de bachillerato intervienen en acreditación y promedios. Las materias extra se muestran por separado y nunca modifican los resultados. Las columnas de regularización permanecen vacías.
                    </p>
                </div>
            </div>
        </section>


        <section x-data="{ abierto: false }" class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <button type="button" @click="abierto = !abierto" class="flex w-full items-center justify-between gap-4 p-5 text-left sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300"><flux:icon.clock class="h-5 w-5" /></div>
                    <div><h3 class="font-black text-slate-950 dark:text-white">Historial reciente de emisiones</h3><p class="text-sm text-slate-500">Al reemitir se actualiza el documento vigente del mismo contexto; no se conserva una copia anterior.</p></div>
                </div>
                <div class="flex items-center gap-3"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-900 dark:text-slate-300">{{ $this->emisionesRecientes->count() }} registros</span><flux:icon.chevron-down class="h-5 w-5 text-slate-400 transition" x-bind:class="abierto && 'rotate-180'" /></div>
            </button>
            <div x-show="abierto" x-collapse class="border-t border-slate-200 dark:border-slate-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-900"><tr><th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Fecha</th><th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Documento</th><th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Alumno / contexto</th><th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Formato</th><th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Emitió</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($this->emisionesRecientes as $emision)
                                <tr><td class="px-4 py-3 whitespace-nowrap">{{ optional($emision->emitido_at)->format('d/m/Y H:i') }}</td><td class="px-4 py-3 font-black">{{ str($emision->tipo)->replace('-', ' ')->headline() }}</td><td class="px-4 py-3">@if($emision->inscripcion){{ $emision->inscripcion->matricula }} · {{ $emision->inscripcion->apellido_paterno }} {{ $emision->inscripcion->nombre }}@else<span class="text-xs text-slate-500">{{ $emision->clave_contexto }}</span>@endif</td><td class="px-4 py-3 uppercase font-bold">{{ $emision->formato }}</td><td class="px-4 py-3">{{ $emision->usuario?->name ?: 'Sistema' }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Todavía no se han emitido documentos oficiales.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @else
        @php
            $titulos = [
                'registro-escolaridad' => ['Registro de escolaridad', 'Documento oficio horizontal de 21.59 × 35.56 cm.', 'table-cells'],
                'acta-resultados' => ['Acta de resultados de evaluación', 'Documento letter vertical por materia.', 'clipboard-document-check'],
                'kardex' => ['Kardex del alumno', 'Historial académico individual en letter vertical.', 'academic-cap'],
                'certificado' => ['Certificado de estudios', 'Certificación parcial o definitiva en letter vertical.', 'identification'],
            ];
            [$tituloModulo, $descripcionModulo, $iconoModulo] = $titulos[$modulo];
        @endphp

        <section class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <div class="border-b border-slate-200 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-900/70 sm:p-6">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#006492] text-white">
                        <flux:icon :name="$iconoModulo" class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-950 dark:text-white">{{ $tituloModulo }}</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $descripcionModulo }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                @if(in_array($modulo, ['registro-escolaridad','acta-resultados'], true))
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <flux:field>
                            <flux:label>Ciclo escolar</flux:label>
                            <flux:select wire:model.live="ciclo_escolar_id">
                                <flux:select.option value="">Selecciona</flux:select.option>
                                @foreach($this->ciclos as $ciclo)
                                    <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}{{ $ciclo->es_actual ? ' · Actual' : '' }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Generación</flux:label>
                            <flux:select wire:model.live="generacion_id">
                                <flux:select.option value="">Selecciona</flux:select.option>
                                @foreach($this->generaciones as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">{{ $generacion->etiqueta }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Semestre</flux:label>
                            <flux:select wire:model.live="semestre_id" :disabled="blank($generacion_id)">
                                <flux:select.option value="">Selecciona</flux:select.option>
                                @foreach($this->semestres as $semestre)
                                    <flux:select.option value="{{ $semestre->id }}">{{ $semestre->numero }}° semestre</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Grupo</flux:label>
                            <flux:select wire:model.live="grupo_id" :disabled="blank($semestre_id)">
                                <flux:select.option value="">Selecciona</flux:select.option>
                                @foreach($this->grupos as $grupo)
                                    <flux:select.option value="{{ $grupo->id }}">{{ $grupo->grado?->nombre }} · Grupo {{ $grupo->asignacionGrupo?->nombre }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @if($modulo === 'acta-resultados')
                            <flux:field>
                                <flux:label>Materia oficial</flux:label>
                                <flux:select wire:model.live="asignacion_materia_id" :disabled="blank($grupo_id)">
                                    <flux:select.option value="">Selecciona una materia</flux:select.option>
                                    @foreach($this->asignaciones as $asignacion)
                                        <flux:select.option value="{{ $asignacion->id }}">{{ $asignacion->materia?->materia }}{{ $asignacion->materia?->clave ? ' · '.$asignacion->materia->clave : '' }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        @endif
                        <flux:field>
                            <flux:label>Estatus de alumnos</flux:label>
                            <flux:select wire:model.live="estatus">
                                <flux:select.option value="todos">Todos los del corte histórico</flux:select.option>
                                <flux:select.option value="activos">Solo activos</flux:select.option>
                                <flux:select.option value="egresado">Egresados</flux:select.option>
                                <flux:select.option value="baja_temporal">Baja temporal</flux:select.option>
                                <flux:select.option value="baja_definitiva">Baja definitiva</flux:select.option>
                                <flux:select.option value="trasladado">Trasladados</flux:select.option>
                                <flux:select.option value="suspendido">Suspendidos</flux:select.option>
                                <flux:select.option value="inactivo">Inactivos</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <flux:field>
                            <flux:label>Generación</flux:label>
                            <flux:select wire:model.live="generacion_id">
                                <flux:select.option value="">Selecciona</flux:select.option>
                                @foreach($this->generaciones as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">{{ $generacion->etiqueta }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Grupo (opcional)</flux:label>
                            <flux:select wire:model.live="grupo_id" :disabled="blank($generacion_id)">
                                <flux:select.option value="">Todos los grupos</flux:select.option>
                                @foreach($this->grupos as $grupo)
                                    <flux:select.option value="{{ $grupo->id }}">{{ $grupo->semestre?->numero }}° · Grupo {{ $grupo->asignacionGrupo?->nombre }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Buscar alumno</flux:label>
                            <flux:input wire:model.live.debounce.350ms="buscar_alumno" icon="magnifying-glass" placeholder="Nombre, matrícula, CURP o folio" />
                        </flux:field>
                        @if($modulo === 'certificado')
                            <flux:field>
                                <flux:label>Tipo de certificado</flux:label>
                                <flux:select wire:model.live="modalidad_certificado">
                                    <flux:select.option value="parcial">Parcial · semestres acreditados</flux:select.option>
                                    <flux:select.option value="definitivo">Definitivo · seis semestres</flux:select.option>
                                </flux:select>
                            </flux:field>
                        @endif
                    </div>
                    <flux:field>
                        <flux:label>Alumno</flux:label>
                        <flux:select wire:model.live="inscripcion_id" :disabled="blank($generacion_id)">
                            <flux:select.option value="">Selecciona un alumno</flux:select.option>
                            @foreach($this->alumnos as $alumno)
                                <flux:select.option value="{{ $alumno->id }}">{{ $alumno->matricula }} · {{ $alumno->apellido_paterno }} {{ $alumno->apellido_materno }} {{ $alumno->nombre }}{{ $alumno->folio ? ' · Folio '.$alumno->folio : ' · Sin folio' }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                @endif

                <div class="flex flex-col gap-3 rounded-2xl border border-blue-100 bg-blue-50/60 p-4 dark:border-blue-900/40 dark:bg-blue-950/20 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-black text-slate-900 dark:text-white">Fecha de expedición</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Se imprimirá en el acta, certificado y fecha de acreditación del registro.</p>
                    </div>
                    <div class="w-full sm:w-64">
                        <flux:field>
                            <flux:label>Fecha del documento</flux:label>
                            <flux:input type="date" wire:model.live="fecha_documento" />
                        </flux:field>
                    </div>
                </div>
            </div>
        </section>

        @if($modulo === 'acta-resultados' && filled($asignacion_materia_id))
            <section x-data="{ abierto: false }" class="overflow-hidden rounded-[1.7rem] border border-emerald-200 bg-white shadow-sm dark:border-emerald-900/50 dark:bg-slate-950">
                <button type="button" @click="abierto = !abierto" class="flex w-full items-center justify-between gap-4 p-5 text-left sm:p-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"><flux:icon.user-group class="h-5 w-5" /></div>
                        <div><h3 class="font-black text-slate-950 dark:text-white">Asistencia final de la materia</h3><p class="text-sm text-slate-500">Captura manual o importa una plantilla Excel. No modifica la acreditación.</p></div>
                    </div>
                    <flux:icon.chevron-down class="h-5 w-5 text-slate-400 transition" x-bind:class="abierto && 'rotate-180'" />
                </button>
                <div x-show="abierto" x-collapse class="border-t border-emerald-100 p-5 dark:border-emerald-900/40 sm:p-6">
                    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div class="flex-1">
                            <flux:field>
                                <flux:label>Importar asistencias</flux:label>
                                <flux:input type="file" wire:model="archivo_asistencias" accept=".xlsx,.xls,.csv" />
                                <flux:error name="archivo_asistencias" />
                            </flux:field>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @php($plantillaQuery = http_build_query($this->queryDescarga()))
                            <a href="{{ route('media-superior.documentos.asistencias.plantilla') }}?{{ $plantillaQuery }}" class="rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm font-black text-emerald-700 hover:bg-emerald-50">Descargar plantilla</a>
                            <button type="button" wire:click="importarAsistencias" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white hover:bg-emerald-700">Importar</button>
                            <button type="button" wire:click="guardarAsistencias" class="rounded-xl bg-[#006492] px-4 py-2.5 text-sm font-black text-white hover:bg-[#005474]">Guardar captura</button>
                        </div>
                    </div>
                    <div class="max-h-[28rem] overflow-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                            <thead class="sticky top-0 bg-slate-100 dark:bg-slate-900"><tr><th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Matrícula</th><th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Alumno</th><th class="w-44 px-4 py-3 text-center text-xs font-black uppercase text-slate-500">% asistencia</th></tr></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($this->alumnosActa as $alumno)
                                    <tr><td class="px-4 py-3 font-bold">{{ $alumno->matricula }}</td><td class="px-4 py-3">{{ $alumno->apellido_paterno }} {{ $alumno->apellido_materno }} {{ $alumno->nombre }}</td><td class="px-4 py-2"><input type="number" min="0" max="100" step="1" wire:model.defer="asistencias.{{ $alumno->id }}" class="w-full rounded-xl border-slate-200 text-center text-sm dark:border-slate-700 dark:bg-slate-900"></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        @php($preview = $this->vistaPrevia)
        @if($preview)
            @if(isset($preview['error']))
                <section class="rounded-[1.7rem] border border-rose-200 bg-rose-50 p-5 dark:border-rose-900/50 dark:bg-rose-950/20">
                    <div class="flex items-start gap-3"><flux:icon.exclamation-triangle class="h-6 w-6 shrink-0 text-rose-600" /><div><h3 class="font-black text-rose-800 dark:text-rose-200">No se puede generar todavía</h3><p class="mt-1 text-sm text-rose-700 dark:text-rose-300">{{ $preview['error'] }}</p></div></div>
                </section>
            @else
                @php
                    $query = http_build_query($this->queryDescarga());
                    $base = route('media-superior.documentos.descargar', ['tipo' => $modulo, 'formato' => 'pdf']);
                @endphp
                <section class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div class="flex flex-col gap-4 border-b border-slate-200 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-900/70 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div><h3 class="text-lg font-black text-slate-950 dark:text-white">Vista previa de información</h3><p class="mt-1 text-sm text-slate-500">Revisa el contenido antes de emitir el documento.</p></div>
                        <div class="flex flex-wrap gap-2">
                            <a target="_blank" href="{{ $base }}?{{ $query }}&preview=1" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-black text-blue-700 hover:bg-blue-100"><flux:icon.eye class="h-4 w-4" /> Vista PDF</a>
                            <a href="{{ route('media-superior.documentos.descargar', ['tipo'=>$modulo,'formato'=>'pdf']) }}?{{ $query }}" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-black text-white hover:bg-rose-700">PDF</a>
                            <a href="{{ route('media-superior.documentos.descargar', ['tipo'=>$modulo,'formato'=>'word']) }}?{{ $query }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white hover:bg-blue-700">Word</a>
                            <a href="{{ route('media-superior.documentos.descargar', ['tipo'=>$modulo,'formato'=>'excel']) }}?{{ $query }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white hover:bg-emerald-700">Excel</a>
                            <div class="flex overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-sm">
                                <select wire:model.live="formato_zip" aria-label="Formato del ZIP" class="border-0 bg-white px-2.5 py-2 text-xs font-black text-slate-700 focus:ring-0 dark:bg-slate-800 dark:text-white">
                                    <option value="pdf">PDF</option>
                                    <option value="word">Word</option>
                                </select>
                                <a href="{{ route('media-superior.documentos.zip', ['tipo'=>$modulo]) }}?{{ $query }}&formato={{ $formato_zip }}" title="Descarga masiva según los filtros seleccionados" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-black text-white hover:bg-slate-800">ZIP</a>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        @if($modulo === 'registro-escolaridad')
                            <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                                <div class="rounded-2xl bg-blue-50 p-4"><p class="text-xs font-black uppercase text-blue-700">Alumnos</p><p class="mt-1 text-2xl font-black">{{ $preview['estadistica']['total'] }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-500">Materias oficiales</p><p class="mt-1 text-2xl font-black">{{ $preview['asignaciones']->count() }}</p></div>
                                <div class="rounded-2xl bg-emerald-50 p-4"><p class="text-xs font-black uppercase text-emerald-700">Hombres / Mujeres</p><p class="mt-1 text-2xl font-black">{{ $preview['estadistica']['hombres'] }} / {{ $preview['estadistica']['mujeres'] }}</p></div>
                                <div class="rounded-2xl bg-amber-50 p-4"><p class="text-xs font-black uppercase text-amber-700">Pendientes</p><p class="mt-1 text-2xl font-black">{{ $preview['diagnostico']['calificaciones_pendientes'] }}</p></div>
                            </div>
                            <div class="max-h-[32rem] overflow-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                                <table class="min-w-full divide-y divide-slate-200 text-xs dark:divide-slate-800"><thead class="sticky top-0 bg-slate-100 dark:bg-slate-900"><tr><th class="px-3 py-3 text-left">Matrícula</th><th class="px-3 py-3 text-left">Alumno</th>@foreach($preview['asignaciones'] as $a)<th class="px-3 py-3 text-center">{{ $a->materia?->clave ?: $a->materia?->materia }}</th>@endforeach<th class="px-3 py-3">Situación</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">@foreach($preview['filas'] as $fila)<tr><td class="px-3 py-3 font-bold">{{ $fila['matricula'] }}</td><td class="px-3 py-3">{{ $fila['nombre'] }}</td>@foreach($fila['materias'] as $m)<td class="px-3 py-3 text-center">{{ $m['valor'] ?: '—' }}</td>@endforeach<td class="px-3 py-3 text-center font-bold">{{ $fila['situacion_escolar'] }}</td></tr>@endforeach</tbody></table>
                            </div>
                        @elseif($modulo === 'acta-resultados')
                            <div class="max-h-[32rem] overflow-auto rounded-2xl border border-slate-200 dark:border-slate-800"><table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800"><thead class="sticky top-0 bg-slate-100 dark:bg-slate-900"><tr><th class="px-4 py-3 text-left">Matrícula</th><th class="px-4 py-3 text-left">Alumno</th><th class="px-4 py-3 text-center">Número</th><th class="px-4 py-3 text-center">Letra</th><th class="px-4 py-3 text-center">Asist.</th><th class="px-4 py-3 text-center">Acreditado</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">@foreach($preview['filas'] as $fila)<tr><td class="px-4 py-3 font-bold">{{ $fila['matricula'] }}</td><td class="px-4 py-3">{{ $fila['nombre'] }}</td><td class="px-4 py-3 text-center">{{ $fila['calificacion_numero'] ?: '—' }}</td><td class="px-4 py-3 text-center">{{ $fila['calificacion_letra'] ?: '—' }}</td><td class="px-4 py-3 text-center">{{ $fila['asistencia'] !== null ? number_format((float)$fila['asistencia'],0).'%' : '—' }}</td><td class="px-4 py-3 text-center font-black">{{ $fila['acreditado'] ?: 'PENDIENTE' }}</td></tr>@endforeach</tbody></table></div>
                        @else
                            <div class="mb-5 rounded-2xl border border-slate-200 p-4 dark:border-slate-800"><p class="font-black text-slate-950 dark:text-white">{{ $preview['alumno']->matricula }} · {{ $preview['alumno']->apellido_paterno }} {{ $preview['alumno']->apellido_materno }} {{ $preview['alumno']->nombre }}</p><p class="mt-1 text-sm text-slate-500">Folio: {{ $preview['alumno']->folio ?: 'Pendiente' }} · Generación: {{ $preview['alumno']->generacion?->etiqueta }}</p></div>
                            @if($modulo === 'kardex' && ($preview['diagnostico']['catalogos_inconsistentes'] ?? collect())->isNotEmpty())
                                <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                    <div class="flex items-start gap-3">
                                        <flux:icon.exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0" />
                                        <div>
                                            <p class="font-black">Hay semestres con un catálogo de materias incompleto.</p>
                                            <p class="mt-1 text-sm">El promedio y la acreditación quedan pendientes hasta que el número de materias oficiales coincida con la configuración de “Materias a promediar”.</p>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach($preview['diagnostico']['catalogos_inconsistentes'] as $inconsistencia)
                                                    <span class="rounded-full border border-amber-300 bg-white/70 px-3 py-1 text-xs font-black dark:border-amber-800 dark:bg-slate-950/40">
                                                        {{ $inconsistencia['semestre'] }}°: {{ $inconsistencia['encontradas'] }}/{{ $inconsistencia['esperadas'] }} materias
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="space-y-4">
                                @foreach(($modulo === 'certificado' ? $preview['semestres_certificados'] : $preview['semestres']) as $semestre)
                                    <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                                        <div class="flex flex-col gap-2 bg-slate-50 px-4 py-3 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-black">{{ $semestre['numero'] }}° semestre</p>
                                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $semestre['catalogo_consistente'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300' }}">
                                                    {{ $semestre['materias_oficiales'] }}/{{ $semestre['materias_esperadas'] }} materias oficiales
                                                </span>
                                                @if($semestre['numero_materias_configurado'])
                                                    <span class="text-[11px] font-bold text-slate-400">Configuración manual</span>
                                                @endif
                                            </div>
                                            <span class="w-fit rounded-full bg-white px-3 py-1 text-xs font-black shadow-sm dark:bg-slate-950">Prom. {{ $semestre['promedio'] }}</span>
                                        </div>
                                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800"><tbody class="divide-y divide-slate-100 dark:divide-slate-800">@foreach($semestre['oficiales'] as $materia)<tr><td class="px-4 py-3 font-mono text-xs">{{ $materia['clave'] }}</td><td class="px-4 py-3">{{ $materia['nombre'] }}</td><td class="px-4 py-3 text-right font-black">{{ $materia['valor'] ?: '—' }}</td></tr>@endforeach</tbody></table>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        @endif
    @endif
</div>
