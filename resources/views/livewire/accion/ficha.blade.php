@php
    $esProfesor = auth()->user()?->isProfessor();
@endphp
<div class="space-y-5" x-data="{ vistaPrevia: false, urlPrevia: '', copiadoInformeGrupo: false }"
     @keydown.escape.window="vistaPrevia = false"
     @ficha-vista-pdf-lista.window="urlPrevia=$event.detail.url;vistaPrevia=true">
    {{-- Encabezado institucional y selector independiente de evaluación --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="relative overflow-hidden bg-gradient-to-r from-[#006492] via-[#087eaf] to-[#88ac2e] p-6 text-white sm:p-8">
            <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full border-[32px] border-white/10"></div>
            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-widest">
                        <flux:icon.academic-cap class="size-4" /> Preescolar · Gestión académica
                    </div>
                    <h1 class="text-2xl font-black tracking-tight sm:text-3xl">Fichas Descriptivas <span class="text-lime-200">PRO 3.0</span></h1>
                    <p class="mt-2 max-w-2xl text-sm text-white/90">Captura pedagógica, revisión académica y documentos institucionales por estudiante y periodo.</p>
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/10 p-3 text-center backdrop-blur-sm">
                    <div class="text-xs font-semibold text-white/80">Evaluación seleccionada</div>
                    <div class="mt-1 text-lg font-black">{{ $this->periodoCorto($periodo) }}</div>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 border-b border-slate-100 px-5 py-4 dark:border-neutral-800">
            @foreach ([1, 2, 3] as $p)
                <flux:button type="button" wire:click="cambiarPeriodo({{ $p }})" :variant="$periodo === $p ? 'primary' : 'outline'" size="sm">
                    {{ $this->periodoCorto($p) }}
                </flux:button>
            @endforeach
        </div>
        <div class="grid gap-3 p-5 md:grid-cols-2 xl:grid-cols-7">
            <flux:field><flux:label>Ciclo escolar</flux:label>
                <flux:select wire:model.live="ciclo_escolar_id">
                    @foreach ($this->ciclosEscolares as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}</flux:select.option>
                    @endforeach
                </flux:select><flux:error name="ciclo_escolar_id" />
            </flux:field>
            <flux:field><flux:label>Generación</flux:label>
                <flux:select wire:model.live="generacion_id"><flux:select.option value="">Todas</flux:select.option>
                    @foreach ($this->generaciones as $generacion)
                        <flux:select.option value="{{ $generacion->id }}">{{ $generacion->anio_ingreso }}-{{ $generacion->anio_egreso }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:field><flux:label>Grado</flux:label>
                <flux:select wire:model.live="grado_id"><flux:select.option value="">Todos</flux:select.option>
                    @foreach ($this->grados as $grado)
                        <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:field><flux:label>Grupo</flux:label>
                <flux:select wire:model.live="grupo_id"><flux:select.option value="">Todos</flux:select.option>
                    @foreach ($this->grupos as $grupo)
                        <flux:select.option value="{{ $grupo->id }}">{{ $grupo->asignacionGrupo?->nombre ?? ($grupo->nombre ?? 'Grupo ' . $grupo->id) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:field><flux:label>Buscar alumno</flux:label>
                <flux:input type="search" wire:model.live.debounce.350ms="busqueda" icon="magnifying-glass" placeholder="Nombre, apellidos, matrícula o CURP" />
            </flux:field>
            <flux:field><flux:label>Estado de la ficha</flux:label>
                <flux:select wire:model.live="estadoFiltro">
                    <flux:select.option value="todos">Todos</flux:select.option>
                    <flux:select.option value="sin_captura">Sin captura</flux:select.option>
                    <flux:select.option value="en_proceso">En proceso</flux:select.option>
                    <flux:select.option value="completas">Con 5 apartados</flux:select.option>
                    <flux:select.option value="en_revision">En revisión</flux:select.option>
                    <flux:select.option value="finalizada">Finalizadas</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field><flux:label>Fecha y lugar (documentos)</flux:label>
                <flux:input wire:model.live.debounce.500ms="fecha_lugar" placeholder="Lugar y fecha" />
            </flux:field>
        </div>
    </section>

    @unless ($tablasControlListas)
        <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-900 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-200" role="alert">
            Para activar guardado, revisión e historial de Fichas PRO, ejecuta desde la raíz del proyecto <code class="rounded bg-amber-100 px-2 py-1 dark:bg-amber-900">php artisan migrate</code> y <code class="rounded bg-amber-100 px-2 py-1 dark:bg-amber-900">php artisan optimize:clear</code>. Las fichas existentes siguen disponibles para consulta.
        </div>
    @endunless

    {{-- Panel de indicadores por filtros, sin atribuir finalización a fichas incompletas --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['Total estudiantes', $estadisticasFichas['total'], 'users', 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300'],
            ['Completas (5/5)', $estadisticasFichas['completas'], 'check-circle', 'bg-lime-50 text-lime-700 dark:bg-lime-500/10 dark:text-lime-300'],
            ['En proceso', $estadisticasFichas['en_proceso'], 'pencil-square', 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'],
            ['Sin captura', $estadisticasFichas['pendientes'], 'clock', 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-300'],
            ['Finalizadas', $estadisticasFichas['finalizadas'], 'shield-check', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'],
        ] as [$titulo, $cantidad, $icono, $clases])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-bold text-slate-500 dark:text-neutral-400">{{ $titulo }}</p>
                    <span class="rounded-xl p-2 {{ $clases }}"><flux:icon :name="$icono" class="size-5" /></span>
                </div>
                <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $cantidad }}</p>
            </div>
        @endforeach
    </div>

    {{-- Acciones: las rutas PDF/Word preexistentes se conservan. --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="font-extrabold text-slate-900 dark:text-white">Centro documental</h2>
                <p class="text-xs text-slate-500 dark:text-neutral-400">Previsualiza el PDF, imprime o descarga Word editable. Respeta los filtros y el periodo.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button type="button" icon="eye" variant="outline" x-on:click="urlPrevia = $el.dataset.pdfUrl; vistaPrevia = true" data-pdf-url="{{ $this->urlPdfGrupo }}">Vista previa del grupo</flux:button>
                <flux:button href="{{ $this->urlPdfGrupo }}" target="_blank" icon="document-arrow-down" variant="primary">PDF grupo</flux:button>
                <flux:button href="{{ $this->urlWordGrupo }}" target="_blank" icon="document-text" variant="outline">Word grupo</flux:button>
                @unless ($esProfesor)
                    <flux:button href="{{ $this->urlExcel }}" target="_blank" icon="table-cells" variant="outline">Exportar Excel</flux:button>
                @endunless
            </div>
        </div>
    </section>

    @unless ($esProfesor)
    <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-bold text-slate-900 dark:text-white">
            <span class="flex items-center gap-2"><flux:icon.arrow-up-tray class="size-5 text-[#006492]" /> Importación Excel inteligente</span>
            <flux:icon.chevron-down class="size-5 transition-transform group-open:rotate-180" />
        </summary>
        <div class="space-y-4 border-t border-slate-100 p-5 dark:border-neutral-800">
            <p class="text-sm text-slate-500">Selecciona grado, ciclo y periodo. Descarga la plantilla, rellena los campos y valida el archivo antes de confirmar; no se modificarán fichas finalizadas.</p>
            <div class="flex flex-wrap items-end gap-3">
                <flux:button type="button" wire:click="descargarPlantillaImportacion" wire:loading.attr="disabled" wire:target="descargarPlantillaImportacion" variant="outline" icon="document-arrow-down">Descargar plantilla</flux:button>
                <div class="min-w-[210px] flex-1"><flux:input type="file" wire:model="archivo_fichas" accept=".xlsx,.xls" /><flux:error name="archivo_fichas" /></div>
                <flux:button type="button" wire:click="previsualizarImportacion" wire:loading.attr="disabled" wire:target="previsualizarImportacion,archivo_fichas" variant="primary" icon="magnifying-glass">Validar archivo</flux:button>
            </div>
            @if ($previsualizacionImportacion)
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm dark:border-sky-500/25 dark:bg-sky-500/10">
                    <p class="font-bold">Resultado de la validación</p>
                    <p class="mt-1">{{ $previsualizacionImportacion['total'] }} filas · {{ $previsualizacionImportacion['validos'] }} alumnos válidos · {{ $previsualizacionImportacion['apartados'] }} apartados por importar · {{ $previsualizacionImportacion['reemplazos'] }} textos existentes que serían reemplazados.</p>
                    @foreach (array_slice($previsualizacionImportacion['errores'], 0, 15) as $error)
                        <p class="mt-1 text-rose-700 dark:text-rose-300">{{ $error }}</p>
                    @endforeach
                    @if ($previsualizacionImportacion['errores'] !== [])
                        <div class="mt-2"><flux:button size="sm" variant="outline" wire:click="descargarErroresImportacion" icon="document-arrow-down">Descargar errores CSV</flux:button></div>
                    @endif
                    @if ($previsualizacionImportacion['errores'] === [] && $previsualizacionImportacion['validos'] > 0)
                        <div class="mt-3"><flux:button type="button" wire:click="importarPlantillaFichas" wire:loading.attr="disabled" wire:target="importarPlantillaFichas" variant="primary" icon="check">Confirmar importación</flux:button></div>
                    @endif
                </div>
            @endif
        </div>
    </details>
    <details class="group rounded-2xl border border-violet-200 bg-white shadow-sm dark:border-violet-900/40 dark:bg-neutral-900">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-bold dark:text-white"><span class="flex items-center gap-2"><flux:icon.sparkles class="size-5 text-violet-600" /> Informe pedagógico grupal con IA</span><flux:icon.chevron-down class="size-5 transition-transform group-open:rotate-180" /></summary>
    <div
        class="overflow-hidden rounded-3xl border border-violet-200 bg-white shadow-sm dark:border-violet-900/40 dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-violet-600 via-fuchsia-500 to-pink-500"></div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 ring-1 ring-violet-200 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/50">
                        <flux:icon.sparkles class="h-6 w-6" />
                    </div>

                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full bg-violet-50 px-3 py-1 text-xs font-black text-violet-700 ring-1 ring-violet-100 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/40">
                            <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                            GroqCloud · Informe por periodo
                        </div>

                        <h3 class="mt-2 text-xl font-black text-neutral-900 dark:text-white">
                            Descripción general del grado y grupo
                        </h3>

                        <p class="mt-1 max-w-3xl text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">
                            Analiza las fichas capturadas del periodo y redacta tendencias grupales, fortalezas,
                            aspectos por acompañar y estrategias docentes. No envía nombres, matrículas, CURP ni
                            identificadores individuales.
                        </p>

                        <p class="mt-2 text-xs font-bold text-neutral-500 dark:text-neutral-400">
                            La búsqueda de alumno no afecta este informe: se toma el grupo completo seleccionado.
                        </p>
                    </div>
                </div>

                <div class="w-full xl:w-[460px]">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]">
                        <flux:select label="Tipo de informe" wire:model="tipo_informe_grupo_ia">
                            <flux:select.option value="pedagogico">Pedagógico para docente</flux:select.option>
                            <flux:select.option value="direccion">Resumen para dirección</flux:select.option>
                            <flux:select.option value="consejo_tecnico">Consejo técnico escolar</flux:select.option>
                            <flux:select.option value="familias">Descripción para familias</flux:select.option>
                        </flux:select>

                        <div class="flex items-end">
                            <button type="button" wire:click="generarInformeGrupoIa" wire:loading.attr="disabled"
                                wire:target="generarInformeGrupoIa"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-violet-500/20 transition hover:-translate-y-0.5 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                                <span wire:loading.remove wire:target="generarInformeGrupoIa"
                                    class="inline-flex items-center gap-2">
                                    <flux:icon.sparkles class="h-4 w-4" />
                                    Generar informe
                                </span>

                                <span wire:loading.flex wire:target="generarInformeGrupoIa"
                                    class="items-center gap-2">
                                    <span
                                        class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                    Analizando…
                                </span>
                            </button>
                        </div>
                    </div>

                    @error('tipo_informe_grupo_ia')
                        <p class="mt-2 text-xs font-bold text-rose-600 dark:text-rose-300">{{ $message }}</p>
                    @enderror

                    @if (!$grado_id || !$grupo_id)
                        <div
                            class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold leading-relaxed text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                            Selecciona un grado y un grupo específico antes de generar la descripción general.
                        </div>
                    @endif
                </div>
            </div>

            @if (!empty($informe_grupo_ia))
                @php
                    $prioridadGrupo = $informe_grupo_ia['prioridad_seguimiento'] ?? 'media';
                @endphp
                @php
                    $coberturaGrupo = (int) ($resumen_informe_grupo_ia['porcentaje_cobertura'] ?? 0);
                @endphp

                <div class="mt-6 border-t border-neutral-200 pt-6 dark:border-neutral-800">
                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex rounded-full border px-3 py-1 text-xs font-black uppercase {{ $this->clasePrioridadInformeGrupoIa($prioridadGrupo) }}">
                                Seguimiento {{ $prioridadGrupo }}
                            </span>

                            <span
                                class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-black text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                                {{ $resumen_informe_grupo_ia['grado'] ?? '' }} · Grupo
                                {{ $resumen_informe_grupo_ia['grupo'] ?? '' }}
                            </span>

                            <span
                                class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-black text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                                {{ $resumen_informe_grupo_ia['periodo'] ?? '' }}
                            </span>

                            <span
                                class="rounded-full px-3 py-1 text-xs font-black {{ $coberturaGrupo >= 90 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : ($coberturaGrupo >= 60 ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300') }}">
                                Cobertura {{ $coberturaGrupo }}%
                            </span>

                            @if ($informe_grupo_ia_generado_en)
                                <span
                                    class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-500 dark:bg-neutral-800 dark:text-neutral-300">
                                    Generado {{ $informe_grupo_ia_generado_en }}
                                </span>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                x-on:click="navigator.clipboard.writeText($refs.informeFichaGrupo.innerText).then(() => { copiadoInformeGrupo = true; setTimeout(() => copiadoInformeGrupo = false, 1800) })"
                                class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 text-xs font-black text-neutral-700 transition hover:border-violet-200 hover:text-violet-700 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:border-violet-800 dark:hover:text-violet-300">
                                <flux:icon.clipboard-document class="h-4 w-4" />
                                <span x-text="copiadoInformeGrupo ? 'Copiado' : 'Copiar informe'"></span>
                            </button>

                            <button type="button" wire:click="limpiarInformeGrupoIa"
                                class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-black text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                <flux:icon.trash class="h-4 w-4" />
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div class="mb-5 grid gap-3 sm:grid-cols-3">
                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/60">
                            <p class="text-xs font-black uppercase tracking-wide text-neutral-500">Alumnos del grupo
                            </p>
                            <p class="mt-1 text-2xl font-black text-neutral-900 dark:text-white">
                                {{ $resumen_informe_grupo_ia['total_alumnos'] ?? 0 }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/60">
                            <p class="text-xs font-black uppercase tracking-wide text-neutral-500">Apartados capturados
                            </p>
                            <p class="mt-1 text-2xl font-black text-neutral-900 dark:text-white">
                                {{ $resumen_informe_grupo_ia['fichas_capturadas'] ?? 0 }} /
                                {{ $resumen_informe_grupo_ia['fichas_esperadas'] ?? 0 }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/60">
                            <p class="text-xs font-black uppercase tracking-wide text-neutral-500">Estado de captura
                            </p>
                            <p class="mt-1 text-lg font-black capitalize text-neutral-900 dark:text-white">
                                {{ $resumen_informe_grupo_ia['estado_captura'] ?? 'Parcial' }}
                            </p>
                        </div>
                    </div>

                    @if ($coberturaGrupo < 90)
                        <div
                            class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold leading-relaxed text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                            El informe es preliminar porque no están capturados todos los apartados del grupo. Al
                            completar
                            las fichas, genera nuevamente el informe para obtener una descripción más representativa.
                        </div>
                    @endif

                    <div x-ref="informeFichaGrupo" class="space-y-5">
                        <div
                            class="rounded-3xl border border-violet-100 bg-gradient-to-br from-violet-50 via-white to-pink-50 p-5 dark:border-violet-900/40 dark:from-violet-950/20 dark:via-neutral-950 dark:to-pink-950/20">
                            <h4 class="text-xl font-black text-neutral-900 dark:text-white">
                                {{ $informe_grupo_ia['titulo'] ?? 'Informe descriptivo grupal de preescolar' }}
                            </h4>

                            <p
                                class="mt-3 whitespace-pre-line text-sm font-semibold leading-7 text-neutral-700 dark:text-neutral-300">
                                {{ $informe_grupo_ia['descripcion_general'] ?? '' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                            <div
                                class="rounded-3xl border border-emerald-200 bg-emerald-50/70 p-5 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                <h5
                                    class="flex items-center gap-2 text-sm font-black text-emerald-800 dark:text-emerald-300">
                                    <flux:icon.check-circle class="h-5 w-5" />
                                    Fortalezas grupales
                                </h5>

                                <div class="mt-3 space-y-2">
                                    @forelse ($informe_grupo_ia['fortalezas_grupales'] ?? [] as $fortaleza)
                                        <div
                                            class="rounded-2xl bg-white/80 px-4 py-3 text-xs font-semibold leading-relaxed text-neutral-700 shadow-sm dark:bg-neutral-950/60 dark:text-neutral-300">
                                            {{ $fortaleza }}
                                        </div>
                                    @empty
                                        <p class="text-xs text-neutral-500">No se señalaron fortalezas específicas.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div
                                class="rounded-3xl border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-900/40 dark:bg-amber-950/20">
                                <h5
                                    class="flex items-center gap-2 text-sm font-black text-amber-800 dark:text-amber-300">
                                    <flux:icon.exclamation-triangle class="h-5 w-5" />
                                    Áreas de acompañamiento
                                </h5>

                                <div class="mt-3 space-y-2">
                                    @forelse ($informe_grupo_ia['areas_acompanamiento'] ?? [] as $area)
                                        <div
                                            class="rounded-2xl bg-white/80 px-4 py-3 text-xs font-semibold leading-relaxed text-neutral-700 shadow-sm dark:bg-neutral-950/60 dark:text-neutral-300">
                                            {{ $area }}
                                        </div>
                                    @empty
                                        <p class="text-xs text-neutral-500">No se señalaron áreas específicas.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div
                                class="rounded-3xl border border-sky-200 bg-sky-50/70 p-5 dark:border-sky-900/40 dark:bg-sky-950/20">
                                <h5 class="flex items-center gap-2 text-sm font-black text-sky-800 dark:text-sky-300">
                                    <flux:icon.sparkles class="h-5 w-5" />
                                    Recomendaciones grupales
                                </h5>

                                <div class="mt-3 space-y-2">
                                    @forelse ($informe_grupo_ia['recomendaciones_grupales'] ?? [] as $recomendacion)
                                        <div
                                            class="rounded-2xl bg-white/80 px-4 py-3 text-xs font-semibold leading-relaxed text-neutral-700 shadow-sm dark:bg-neutral-950/60 dark:text-neutral-300">
                                            {{ $recomendacion }}
                                        </div>
                                    @empty
                                        <p class="text-xs text-neutral-500">No se generaron recomendaciones.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div
                            class="rounded-3xl border border-neutral-200 bg-neutral-50 p-5 dark:border-neutral-800 dark:bg-neutral-950/60">
                            <h5 class="text-sm font-black text-neutral-900 dark:text-white">
                                Síntesis por campo formativo
                            </h5>

                            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                @forelse ($informe_grupo_ia['sintesis_campos'] ?? [] as $campoGrupo)
                                    <article
                                        class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                        <h6 class="text-sm font-black text-violet-700 dark:text-violet-300">
                                            {{ $campoGrupo['campo'] ?? 'Campo formativo' }}
                                        </h6>

                                        <p
                                            class="mt-2 text-sm font-semibold leading-7 text-neutral-700 dark:text-neutral-300">
                                            {{ $campoGrupo['sintesis'] ?? '' }}
                                        </p>

                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <p
                                                    class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                                    Fortalezas
                                                </p>
                                                <ul
                                                    class="mt-2 space-y-1.5 text-xs font-semibold leading-relaxed text-neutral-600 dark:text-neutral-400">
                                                    @forelse ($campoGrupo['fortalezas'] ?? [] as $fortalezaCampo)
                                                        <li>• {{ $fortalezaCampo }}</li>
                                                    @empty
                                                        <li>Sin fortalezas específicas registradas.</li>
                                                    @endforelse
                                                </ul>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-xs font-black uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                                    Por fortalecer
                                                </p>
                                                <ul
                                                    class="mt-2 space-y-1.5 text-xs font-semibold leading-relaxed text-neutral-600 dark:text-neutral-400">
                                                    @forelse ($campoGrupo['aspectos_por_fortalecer'] ?? [] as $aspectoCampo)
                                                        <li>• {{ $aspectoCampo }}</li>
                                                    @empty
                                                        <li>Sin aspectos específicos registrados.</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        </div>
                                    </article>
                                @empty
                                    <p class="text-sm text-neutral-500">No se generó síntesis por campo formativo.</p>
                                @endforelse
                            </div>
                        </div>

                        <div
                            class="rounded-3xl border border-indigo-200 bg-indigo-50/70 p-5 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                            <h5 class="text-sm font-black text-indigo-800 dark:text-indigo-300">
                                Estrategias sugeridas para el trabajo docente
                            </h5>

                            <div class="mt-3 grid gap-2 md:grid-cols-2">
                                @forelse ($informe_grupo_ia['estrategias_docentes'] ?? [] as $estrategia)
                                    <div
                                        class="rounded-2xl bg-white/80 px-4 py-3 text-xs font-semibold leading-relaxed text-neutral-700 shadow-sm dark:bg-neutral-950/60 dark:text-neutral-300">
                                        {{ $estrategia }}
                                    </div>
                                @empty
                                    <p class="text-xs text-neutral-500">No se generaron estrategias específicas.</p>
                                @endforelse
                            </div>
                        </div>

                        <p
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs font-semibold leading-relaxed text-neutral-500 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-400">
                            {{ $informe_grupo_ia['aviso'] ?? 'Revisa el informe antes de utilizarlo.' }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    </details>
    @endunless

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 dark:border-neutral-800">
            <div><h2 class="font-black text-lg text-slate-900 dark:text-white">Estudiantes de Preescolar</h2><p class="mt-1 text-xs text-slate-500 dark:text-neutral-400">{{ $alumnos->total() }} resultados · {{ $this->periodoNombre() }}</p></div>
            <div class="flex items-center rounded-xl border border-slate-200 p-1 dark:border-neutral-700">
                <flux:button type="button" size="sm" :variant="$vistaAlumnos === 'tabla' ? 'primary' : 'ghost'" icon="list-bullet" wire:click="$set('vistaAlumnos', 'tabla')">Tabla</flux:button>
                <flux:button type="button" size="sm" :variant="$vistaAlumnos === 'tarjetas' ? 'primary' : 'ghost'" icon="squares-2x2" wire:click="$set('vistaAlumnos', 'tarjetas')">Tarjetas</flux:button>
            </div>
        </div>
        @if ($vistaAlumnos === 'tarjetas')
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($alumnos as $alumno)
                    @php
                        $completados = collect(array_keys($campos))->filter(fn ($key) => filled(trim(strip_tags((string) ($fichasResumen[$alumno->id][$key] ?? '')))))->count();
                        $porcentaje = (int) round($completados * 100 / max(count($campos), 1));
                        $estado = $estadosPagina[$alumno->id] ?? 'borrador';
                    @endphp
                    <article wire:key="ficha-card-{{ $alumno->id }}" class="rounded-2xl border border-slate-200 p-4 transition hover:border-[#006492]/50 hover:shadow-md dark:border-neutral-800">
                        <div class="flex items-center gap-3">
                            @if ($alumno->foto_existe && $alumno->foto_url)
                                <img src="{{ $alumno->foto_url }}" alt="Foto" class="h-12 w-12 rounded-full object-cover" data-foto-visor data-foto-nombre="{{ $this->alumnoNombre($alumno) }}" data-foto-matricula="{{ $alumno->matricula }}" />
                            @else
                                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-sky-100 text-sky-700 dark:bg-sky-500/20"> <flux:icon.user class="size-6" /> </div>
                            @endif
                            <div class="min-w-0 flex-1"><h3 class="truncate font-bold text-slate-900 dark:text-white">{{ $this->alumnoNombre($alumno) }}</h3>
                                <p class="text-xs text-slate-500">{{ $alumno->grado?->nombre }} · {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'S/G' }}</p>
                                <p class="truncate font-mono text-xs text-slate-400">{{ $alumno->matricula }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs"><span class="font-bold text-slate-600 dark:text-neutral-300">{{ $completados }}/{{ count($campos) }} apartados</span>
                            <span class="font-bold {{ $estado === 'finalizada' ? 'text-emerald-700' : 'text-sky-700' }}">{{ $estado === 'finalizada' ? 'Finalizada' : ($estado === 'en_revision' ? 'En revisión' : ($completados === count($campos) ? 'Lista para revisar' : 'Borrador')) }}</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-neutral-800"><div class="h-full rounded-full bg-gradient-to-r from-[#006492] to-[#88ac2e]" style="width: {{ $porcentaje }}%"></div></div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <flux:button href="{{ $this->urlEditorAlumno($alumno->id) }}" size="sm" variant="primary" icon="pencil-square">{{ $estado === 'finalizada' ? 'Consultar' : 'Editar' }}</flux:button>
                            <flux:button type="button" size="sm" variant="outline" icon="eye" x-on:click="urlPrevia = $el.dataset.pdfUrl; vistaPrevia = true" data-pdf-url="{{ $this->urlPdfAlumno($alumno->id) }}">PDF</flux:button>
                            <flux:button href="{{ $this->urlWordAlumno($alumno->id) }}" target="_blank" size="sm" variant="ghost" icon="document-text">Word</flux:button>
                        </div>
                        @include('livewire.accion.partials.ficha-acciones-estado', ['estadoFicha' => $estado, 'cantidadCapturada' => $completados])
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-200 p-10 text-center text-slate-500">No hay alumnos con los filtros seleccionados.</div>
                @endforelse
            </div>
        @else
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-neutral-800">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:bg-neutral-950"><tr>
                    <th class="px-5 py-4">Estudiante</th><th class="px-4 py-4">Matrícula / CURP</th><th class="px-4 py-4">Avance</th><th class="px-4 py-4">Estado</th><th class="px-4 py-4">Acciones</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                @forelse ($alumnos as $alumno)
                    @php
                        $completados = collect(array_keys($campos))->filter(fn ($key) => filled(trim(strip_tags((string) ($fichasResumen[$alumno->id][$key] ?? '')))))->count();
                        $porcentaje = (int) round($completados * 100 / max(count($campos), 1));
                        $estado = $estadosPagina[$alumno->id] ?? 'borrador';
                    @endphp
                    <tr wire:key="ficha-row-{{ $alumno->id }}" class="transition hover:bg-sky-50/50 dark:hover:bg-white/5">
                        <td class="px-5 py-4"><div class="flex items-center gap-3">
                            @if ($alumno->foto_existe && $alumno->foto_url)
                                <img src="{{ $alumno->foto_url }}" alt="Foto" class="h-11 w-11 shrink-0 rounded-full object-cover" data-foto-visor data-foto-nombre="{{ $this->alumnoNombre($alumno) }}" data-foto-matricula="{{ $alumno->matricula }}" />
                            @else
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-sky-100 text-sky-700 dark:bg-sky-500/20"><flux:icon.user class="size-5" /></span>
                            @endif
                            <div><div class="min-w-[190px] font-bold text-slate-900 dark:text-white">{{ $this->alumnoNombre($alumno) }}</div><div class="text-xs text-slate-500">{{ $alumno->grado?->nombre }} · {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'S/G' }}</div></div>
                        </div></td>
                        <td class="px-4 py-4 text-xs text-slate-600 dark:text-neutral-300"><div class="font-semibold">{{ $alumno->matricula }}</div><div class="mt-1 font-mono text-[11px] text-slate-500">{{ $alumno->curp }}</div></td>
                        <td class="min-w-[150px] px-4 py-4"><div class="flex items-center justify-between text-xs font-bold text-slate-600 dark:text-neutral-300"><span>{{ $completados }}/5</span><span>{{ $porcentaje }}%</span></div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-neutral-800"><div class="h-full rounded-full bg-gradient-to-r from-[#006492] to-[#88ac2e]" style="width: {{ $porcentaje }}%"></div></div>
                        </td>
                        <td class="px-4 py-4"><span @class(['inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' => $estado === 'finalizada', 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300' => $estado === 'en_revision', 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300' => $estado === 'borrador'])>{{ $estado === 'finalizada' ? 'Finalizada' : ($estado === 'en_revision' ? 'En revisión' : 'Borrador') }}</span></td>
                        <td class="min-w-[300px] px-4 py-4"><div class="flex flex-wrap items-center gap-2">
                            <flux:button href="{{ $this->urlEditorAlumno($alumno->id) }}" size="sm" variant="primary" icon="pencil-square">{{ $estado === 'finalizada' ? 'Ver ficha' : 'Editar' }}</flux:button>
                            <flux:button type="button" size="sm" variant="outline" icon="eye" x-on:click="urlPrevia = $el.dataset.pdfUrl; vistaPrevia = true" data-pdf-url="{{ $this->urlPdfAlumno($alumno->id) }}">PDF</flux:button>
                            <flux:button href="{{ $this->urlWordAlumno($alumno->id) }}" size="sm" target="_blank" variant="ghost" icon="document-text">Word</flux:button>
                            @include('livewire.accion.partials.ficha-acciones-estado', ['estadoFicha' => $estado, 'cantidadCapturada' => $completados])
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">No se encontraron alumnos de Preescolar.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        @endif
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 dark:border-neutral-800"><p class="text-xs font-semibold text-slate-500">{{ $alumnos->total() }} estudiantes</p>{{ $alumnos->links() }}</div>
    </section>

    {{-- Vista previa en el sistema: iframe sólo se monta cuando el usuario la solicita. --}}
    <div x-cloak x-show="vistaPrevia" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/80 p-3 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Vista previa PDF">
        <div class="flex h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-neutral-900">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-3 dark:border-neutral-800"><h3 class="font-black text-slate-900 dark:text-white">Vista previa institucional · PDF</h3>
                <div class="flex gap-2"><a :href="urlPrevia" target="_blank" rel="noopener" class="rounded-lg bg-[#006492] px-4 py-2 text-sm font-bold text-white">Abrir / imprimir</a><flux:button type="button" size="sm" variant="outline" x-on:click="vistaPrevia=false">Cerrar</flux:button></div>
            </div>
            <template x-if="vistaPrevia"><iframe :src="urlPrevia" title="Ficha descriptiva PDF" class="w-full flex-1 bg-white"></iframe></template>
        </div>
    </div>



</div>
