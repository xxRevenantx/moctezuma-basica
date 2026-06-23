<div class="space-y-6" x-data="{ copiadoInformeGrupo: false }">
    <div
        class="overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div
            class="border-b border-neutral-100 bg-gradient-to-r from-pink-50 via-white to-rose-50 p-5 dark:border-neutral-800 dark:from-pink-500/10 dark:via-neutral-900 dark:to-rose-500/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-pink-600 dark:text-pink-300">
                        Preescolar
                    </p>

                    <h2 class="mt-1 text-xl font-black text-neutral-900 dark:text-white">
                        Ficha descriptiva individual
                    </h2>

                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Captura por campos formativos y recomendaciones. Este módulo reemplaza calificaciones para
                        preescolar.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ([1, 2, 3] as $p)
                        <flux:button type="button" wire:click="cambiarPeriodo({{ $p }})"
                            :variant="$periodo === $p ? 'primary' : 'outline'" size="sm">
                            {{ $this->periodoCorto($p) }}
                        </flux:button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-4 p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <flux:field>
                    <flux:label>Ciclo escolar</flux:label>
                    <flux:select wire:model.live="ciclo_escolar_id">
                        @foreach ($this->ciclosEscolares as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">
                                {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="ciclo_escolar_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Generación</flux:label>
                    <flux:select wire:model.live="generacion_id">
                        <flux:select.option value="">Todas</flux:select.option>

                        @foreach ($this->generaciones as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">
                                {{ $generacion->anio_ingreso }}-{{ $generacion->anio_egreso }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="generacion_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Grado</flux:label>
                    <flux:select wire:model.live="grado_id">
                        <flux:select.option value="">Todos</flux:select.option>

                        @foreach ($this->grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">
                                {{ $grado->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="grado_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Grupo</flux:label>
                    <flux:select wire:model.live="grupo_id">
                        <flux:select.option value="">Todos</flux:select.option>

                        @foreach ($this->grupos as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">
                                {{ $grupo->asignacionGrupo?->nombre ?? ($grupo->nombre ?? 'Grupo ' . $grupo->id) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="grupo_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Filtrar</flux:label>
                    <flux:input type="search" wire:model.live.debounce.350ms="busqueda"
                        placeholder="Alumno, CURP o matrícula" />
                    <flux:error name="busqueda" />
                </flux:field>
            </div>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="w-full lg:max-w-xl">
                    <flux:field>
                        <flux:label>Fecha y lugar</flux:label>
                        <flux:input type="text" wire:model.live.debounce.500ms="fecha_lugar"
                            class="uppercase font-semibold"
                            placeholder="CD. ALTAMIRANO, GRO., A 11 DE JULIO DEL 2025" />
                        <flux:error name="fecha_lugar" />
                    </flux:field>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button href="{{ $this->urlExcel }}" target="_blank" variant="primary" icon="arrow-down-tray">
                        Excel
                    </flux:button>

                    <flux:button href="{{ $this->urlPdfGrupo }}" target="_blank" variant="filled" icon="document-text">
                        PDF grupo
                    </flux:button>

                    <flux:button type="button" wire:click="descargarPlantillaImportacion" wire:loading.attr="disabled"
                        wire:target="descargarPlantillaImportacion" variant="outline" icon="document-arrow-down">
                        <span wire:loading.remove wire:target="descargarPlantillaImportacion">
                            Plantilla importación
                        </span>
                        <span wire:loading wire:target="descargarPlantillaImportacion">
                            Generando...
                        </span>
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <div
        class="rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-sm font-black uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                    Importar fichas por grado
                </h3>

                <p class="mt-1 text-xs text-neutral-600 dark:text-neutral-400">
                    Primero selecciona ciclo, grado y periodo. Descarga la plantilla, llena los campos formativos y
                    vuelve a subirla.
                </p>

                @if (!$grado_id)
                    <p class="mt-2 text-xs font-semibold text-amber-700">
                        Debes seleccionar un grado antes de descargar o importar.
                    </p>
                @endif
            </div>

            <div class="flex w-full flex-col gap-2 lg:w-auto lg:min-w-[360px]">
                <flux:input type="file" wire:model="archivo_fichas" accept=".xlsx,.xls" />

                <flux:error name="archivo_fichas" />

                <div class="flex flex-wrap gap-2">
                    <flux:button type="button" wire:click="importarPlantillaFichas" wire:loading.attr="disabled"
                        wire:target="archivo_fichas,importarPlantillaFichas" variant="primary" icon="arrow-up-tray">
                        <span wire:loading.remove wire:target="archivo_fichas,importarPlantillaFichas">
                            Importar fichas
                        </span>

                        <span wire:loading wire:target="archivo_fichas">
                            Cargando archivo...
                        </span>

                        <span wire:loading wire:target="importarPlantillaFichas">
                            Importando...
                        </span>
                    </flux:button>

                    @if ($archivo_fichas)
                        <flux:button type="button" variant="ghost" wire:click="$set('archivo_fichas', null)">
                            Quitar archivo
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>
    </div>

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
                @php($prioridadGrupo = $informe_grupo_ia['prioridad_seguimiento'] ?? 'media')
                @php($coberturaGrupo = (int) ($resumen_informe_grupo_ia['porcentaje_cobertura'] ?? 0))

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

    <div
        class="overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                <thead
                    class="bg-neutral-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-neutral-950 dark:text-neutral-400">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Foto</th>
                        <th class="min-w-[240px] px-4 py-3 text-left">Nombre completo</th>
                        <th class="min-w-[170px] px-4 py-3 text-left">CURP</th>

                        @foreach ($campos as $clave => $campoInfo)
                            <th class="min-w-[190px] px-4 py-3 text-center">
                                {{ $campoInfo['label'] }}
                            </th>
                        @endforeach

                        <th class="px-4 py-3 text-center">PDF</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @forelse ($alumnos as $alumno)
                        <tr class="hover:bg-indigo-50/40 dark:hover:bg-white/5">
                            <td class="px-4 py-4 font-bold text-neutral-900 dark:text-white">
                                {{ $alumnos->firstItem() + $loop->index }}
                            </td>

                            <td class="px-4 py-4">
                                @if ($alumno->foto_path)
                                    <img src="{{ Storage::url($alumno->foto_path) }}" alt="Foto"
                                        class="h-11 w-11 rounded-full object-cover ring-2 ring-white shadow">
                                @else
                                    <div
                                        class="grid h-11 w-11 place-items-center rounded-full bg-indigo-100 text-xl shadow-sm dark:bg-indigo-500/15">
                                        👦
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4 font-semibold text-neutral-900 dark:text-white">
                                {{ $this->alumnoNombre($alumno) }}

                                <div class="mt-1 text-xs font-normal text-neutral-500">
                                    {{ $alumno->grado?->nombre }} · Grupo
                                    {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'S/G' }}
                                </div>
                            </td>

                            <td class="px-4 py-4 font-mono text-xs text-neutral-700 dark:text-neutral-300">
                                {{ $alumno->curp }}
                            </td>

                            @foreach ($campos as $clave => $campoInfo)
                                @php($completo = filled($fichasResumen[$alumno->id][$clave] ?? null))

                                <td class="px-4 py-4 text-center">
                                    <flux:button type="button"
                                        wire:click="abrirModal({{ $alumno->id }}, '{{ $clave }}')"
                                        :variant="$completo ? 'primary' : 'filled'" size="sm" square
                                        title="Capturar {{ $campoInfo['label'] }}">
                                        @if ($completo)
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2Z" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M5 19h1.4l9.6-9.6L14.6 8 5 17.6V19Zm13.7-10.3-3.4-3.4 1.1-1.1a1.5 1.5 0 0 1 2.1 0l1.3 1.3a1.5 1.5 0 0 1 0 2.1l-1.1 1.1Z" />
                                            </svg>
                                        @endif
                                    </flux:button>
                                </td>
                            @endforeach

                            <td class="px-4 py-4 text-center">
                                <flux:button href="{{ $this->urlPdfAlumno($alumno->id) }}" target="_blank"
                                    variant="filled" size="sm" square title="Descargar ficha PDF">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm-1 1.5L18.5 9H13V3.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Z" />
                                    </svg>
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 5 + count($campos) }}" class="px-4 py-12 text-center text-neutral-500">
                                No se encontraron alumnos de preescolar con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div
            class="flex flex-col gap-3 border-t border-neutral-100 px-5 py-4 text-sm dark:border-neutral-800 md:flex-row md:items-center md:justify-between">
            <p class="font-semibold text-neutral-600 dark:text-neutral-400">
                {{ $alumnos->total() }} registros totales
            </p>

            {{ $alumnos->links() }}
        </div>
    </div>

    @if ($modalAbierto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-950/70 p-4 backdrop-blur-sm"
            wire:key="modal-ficha">
            <div
                class="max-h-[95vh] w-full max-w-6xl overflow-y-auto rounded-2xl bg-white shadow-2xl dark:bg-neutral-900">
                <div
                    class="sticky top-0 z-20 flex items-start justify-between gap-4 bg-indigo-600 px-5 py-4 text-white">
                    <h3 class="text-base font-black uppercase leading-6">
                        {{ $campos[$campo]['label'] ?? '' }} |
                        {{ $this->alumnoNombre($this->alumnoModal) }} |
                        {{ $this->periodoNombre() }}
                    </h3>

                    <flux:button type="button" wire:click="cerrarModal" variant="ghost" size="sm" square
                        class="text-white hover:bg-white/15" wire:loading.attr="disabled"
                        wire:target="generarDescripcionIA,guardar">
                        ✕
                    </flux:button>
                </div>

                <div class="grid gap-5 p-5 lg:grid-cols-[270px_minmax(0,1fr)]">
                    @if ($campo !== 'recomendaciones')
                        <div
                            class="rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <div class="border-b border-neutral-100 px-5 py-4 text-sm dark:border-neutral-800">
                                Descripción |
                                <strong>{{ $campos[$campo]['label'] ?? '' }}</strong>
                            </div>

                            <div class="space-y-4 px-5 py-5 text-sm leading-6 text-neutral-700 dark:text-neutral-300">
                                <p class="text-justify">
                                    {{ $campos[$campo]['descripcion'] ?? '' }}
                                </p>

                                <div
                                    class="grid min-h-32 place-items-center rounded-xl border border-dashed border-indigo-200 bg-indigo-50 p-4 text-center text-xs font-black uppercase tracking-wide text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
                                    <img src="{{ asset($campos[$campo]['imagen'] ?? '') }}"
                                        class="max-h-40 object-contain" alt="{{ $campos[$campo]['label'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                    @endif

                    <div @class(['min-w-0', 'lg:col-span-2' => $campo === 'recomendaciones'])>
                        <div wire:init="verificarGroq"
                            class="mb-5 overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-cyan-50 shadow-sm dark:border-emerald-500/30 dark:from-emerald-500/10 dark:via-neutral-900 dark:to-cyan-500/10">
                            <div class="border-b border-emerald-100 px-5 py-4 dark:border-emerald-500/20">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-600 text-xl text-white shadow-sm">
                                            ✨
                                        </div>

                                        <div>
                                            <h4 class="font-black text-emerald-950 dark:text-emerald-100">
                                                Asistente de redacción con GroqCloud
                                            </h4>

                                            <p class="mt-1 text-xs leading-5 text-emerald-700 dark:text-emerald-300">
                                                La redacción se genera mediante GroqCloud. El sistema elimina
                                                identificadores directos antes de enviar el texto.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <span @class([
                                            'rounded-full border px-3 py-1 text-xs font-bold',
                                            'border-neutral-200 bg-neutral-100 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300' => is_null(
                                                $groq_disponible),
                                            'border-emerald-200 bg-emerald-100 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-300' =>
                                                $groq_disponible && $groq_modelo_disponible,
                                            'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-300' =>
                                                $groq_disponible && !$groq_modelo_disponible,
                                            'border-red-200 bg-red-100 text-red-700 dark:border-red-500/30 dark:bg-red-500/15 dark:text-red-300' =>
                                                $groq_disponible === false,
                                        ])>
                                            @if (is_null($groq_disponible))
                                                Verificando...
                                            @elseif ($groq_disponible && $groq_modelo_disponible)
                                                GroqCloud listo
                                            @elseif ($groq_disponible)
                                                Modelo no disponible
                                            @else
                                                GroqCloud sin conexión
                                            @endif
                                        </span>

                                        <flux:button type="button" variant="ghost" size="sm" icon="arrow-path"
                                            wire:click="verificarGroq" wire:loading.attr="disabled"
                                            wire:target="verificarGroq,generarDescripcionIA">
                                            <span wire:loading.remove wire:target="verificarGroq">
                                                Verificar
                                            </span>

                                            <span wire:loading wire:target="verificarGroq">
                                                Revisando...
                                            </span>
                                        </flux:button>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 p-5">
                                <div @class([
                                    'rounded-xl border px-4 py-3 text-xs leading-5',
                                    'border-neutral-200 bg-neutral-50 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800/60 dark:text-neutral-300' => is_null(
                                        $groq_disponible),
                                    'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200' =>
                                        $groq_disponible && $groq_modelo_disponible,
                                    'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200' =>
                                        $groq_disponible && !$groq_modelo_disponible,
                                    'border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200' =>
                                        $groq_disponible === false,
                                ])>
                                    <div class="font-semibold">
                                        {{ $groq_mensaje }}
                                    </div>

                                    <div class="mt-1">
                                        Modelo configurado:
                                        <code
                                            class="rounded bg-black/5 px-1.5 py-0.5 font-mono dark:bg-white/10">{{ $groq_modelo }}</code>
                                    </div>

                                    @if ($groq_disponible && !$groq_modelo_disponible)
                                        <div class="mt-2">
                                            Cambia <code
                                                class="rounded bg-black/5 px-1.5 py-0.5 font-mono dark:bg-white/10">GROQ_MODEL</code>
                                            por un modelo activo de tu cuenta y ejecuta <code
                                                class="rounded bg-black/5 px-1.5 py-0.5 font-mono dark:bg-white/10">php
                                                artisan optimize:clear</code>.
                                        </div>
                                    @elseif ($groq_disponible === false)
                                        <div class="mt-2">
                                            Revisa tu conexión a internet, la API key y pulsa
                                            <strong>Verificar</strong>.
                                        </div>
                                    @endif
                                </div>

                                <flux:field>
                                    <flux:label>
                                        @if ($campo === 'recomendaciones')
                                            Observaciones adicionales para las recomendaciones
                                        @else
                                            Ideas u observaciones de la educadora
                                        @endif
                                    </flux:label>

                                    <flux:textarea wire:model="observaciones_ia" rows="4"
                                        placeholder="{{ $campo === 'recomendaciones'
                                            ? 'Ejemplo: reforzar en casa la expresión de emociones, respetar turnos y mantener rutinas de lectura...'
                                            : 'Ejemplo: reconoce algunas vocales, participa con entusiasmo, escribe su nombre con apoyo y requiere acompañamiento para respetar turnos...' }}" />

                                    <flux:description>
                                        @if ($campo === 'recomendaciones' && config('groq.include_context', false))
                                            También se utilizarán los demás campos formativos capturados durante este
                                            periodo.
                                        @elseif ($campo === 'recomendaciones')
                                            Solo se utilizarán las observaciones escritas en este cuadro y el texto
                                            actual.
                                        @else
                                            Puedes escribir ideas breves; GroqCloud las convertirá en una descripción
                                            pedagógica completa.
                                        @endif
                                        No escribas nombre, CURP, matrícula, domicilio, teléfono ni datos médicos.
                                    </flux:description>

                                    <flux:error name="observaciones_ia" />
                                </flux:field>

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex flex-wrap gap-2">
                                        <flux:button type="button" variant="primary" icon="sparkles"
                                            x-on:click="window.generarDescripcionFichaIA?.()"
                                            wire:loading.attr="disabled"
                                            wire:target="generarDescripcionIA,verificarGroq"
                                            :disabled="!($groq_disponible && $groq_modelo_disponible)">
                                            <span wire:loading.remove wire:target="generarDescripcionIA">
                                                @if ($campo === 'recomendaciones')
                                                    Generar recomendaciones
                                                @elseif (filled(strip_tags($descripcion ?? '')))
                                                    Mejorar descripción
                                                @else
                                                    Generar descripción
                                                @endif
                                            </span>

                                            <span wire:loading wire:target="generarDescripcionIA">
                                                Generando descripción...
                                            </span>
                                        </flux:button>

                                        @if (filled($observaciones_ia ?? ''))
                                            <flux:button type="button" variant="ghost"
                                                wire:click="$set('observaciones_ia', '')" wire:loading.attr="disabled"
                                                wire:target="generarDescripcionIA">
                                                Limpiar observaciones
                                            </flux:button>
                                        @endif
                                    </div>

                                    <div wire:loading wire:target="generarDescripcionIA"
                                        class="flex items-center gap-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4" />

                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
                                        </svg>

                                        La respuesta normalmente tarda pocos segundos. No cierres esta ventana.
                                    </div>
                                </div>

                                <div
                                    class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                    <strong>Revisión docente:</strong>
                                    GroqCloud crea una sugerencia. La educadora debe comprobar que el texto corresponda
                                    realmente al desempeño del alumno antes de guardarlo.
                                </div>
                            </div>
                        </div>

                        <flux:field>
                            <flux:label>
                                {{ $campos[$campo]['label'] ?? 'Descripción' }}
                            </flux:label>

                            <div wire:ignore>
                                <textarea id="editor_ficha_descriptiva"></textarea>
                            </div>

                            <div class="mt-1 flex items-center justify-between gap-3 text-xs">
                                <flux:description>
                                    {{ mb_strlen(strip_tags($descripcion ?? '')) }} caracteres
                                </flux:description>

                                <flux:error name="descripcion" />
                            </div>
                        </flux:field>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <flux:button type="button" x-on:click="window.guardarFichaDesdeEditor?.()"
                                wire:loading.attr="disabled" wire:target="guardar,generarDescripcionIA"
                                variant="primary" icon="check">
                                <span wire:loading.remove wire:target="guardar">
                                    Guardar
                                </span>

                                <span wire:loading wire:target="guardar">
                                    Guardando...
                                </span>
                            </flux:button>

                            <flux:button type="button" wire:click="cerrarModal" wire:loading.attr="disabled"
                                wire:target="guardar,generarDescripcionIA" variant="outline">
                                Cancelar
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                let temporizadorFicha = null;

                const esperarTinyMCEFicha = (callback) => {
                    if (window.tinymce) {
                        callback();
                        return;
                    }

                    let intentos = 0;

                    const intervalo = setInterval(() => {
                        intentos++;

                        if (window.tinymce) {
                            clearInterval(intervalo);
                            callback();
                            return;
                        }

                        if (intentos >= 40) {
                            clearInterval(intervalo);

                            console.error(
                                'TinyMCE no se pudo cargar. Revisa que el script de TinyMCE esté en el layout.'
                            );
                        }
                    }, 250);
                };

                const quitarEditorFicha = () => {
                    if (
                        window.tinymce &&
                        tinymce.get('editor_ficha_descriptiva')
                    ) {
                        tinymce.get('editor_ficha_descriptiva').remove();
                    }
                };

                const obtenerEditorFicha = () => {
                    return window.tinymce ?
                        tinymce.get('editor_ficha_descriptiva') :
                        null;
                };

                const enviarDescripcionConDebounce = (contenido) => {
                    clearTimeout(temporizadorFicha);

                    temporizadorFicha = setTimeout(() => {
                        @this.set('descripcion', contenido, false);
                    }, 500);
                };

                const iniciarEditorFicha = (contenido = '') => {
                    esperarTinyMCEFicha(() => {
                        setTimeout(() => {
                            const elemento = document.getElementById(
                                'editor_ficha_descriptiva'
                            );

                            if (!elemento) {
                                return;
                            }

                            quitarEditorFicha();

                            tinymce.init({
                                selector: '#editor_ficha_descriptiva',
                                height: 420,
                                min_height: 360,
                                menubar: true,
                                branding: false,
                                promotion: false,
                                language: 'es',
                                browser_spellcheck: true,
                                contextmenu: false,
                                plugins: [
                                    'lists',
                                    'link',
                                    'table',
                                    'code',
                                    'preview',
                                    'fullscreen',
                                    'searchreplace',
                                    'wordcount',
                                    'autoresize'
                                ].join(' '),
                                toolbar: [
                                    'undo redo',
                                    'blocks',
                                    'bold italic underline strikethrough',
                                    'forecolor backcolor',
                                    'alignleft aligncenter alignright alignjustify',
                                    'bullist numlist',
                                    'table link',
                                    'searchreplace preview fullscreen code'
                                ].join(' | '),
                                content_style: `
                                    body {
                                        font-family: Arial, Helvetica, sans-serif;
                                        font-size: 14px;
                                        line-height: 1.7;
                                        padding: 12px;
                                    }

                                    p {
                                        margin: 0 0 10px;
                                    }

                                    ul,
                                    ol {
                                        margin: 0 0 10px;
                                    }
                                `,
                                setup: function(editor) {
                                    editor.on('init', function() {
                                        editor.setContent(contenido ?? '');
                                    });

                                    editor.on(
                                        'change undo redo input keyup',
                                        function() {
                                            enviarDescripcionConDebounce(
                                                editor.getContent()
                                            );
                                        }
                                    );

                                    editor.on('blur', function() {
                                        @this.set(
                                            'descripcion',
                                            editor.getContent(),
                                            false
                                        );
                                    });
                                },
                            });
                        }, 250);
                    });
                };

                window.sincronizarEditorFicha = async () => {
                    const editor = obtenerEditorFicha();

                    if (editor) {
                        await @this.set(
                            'descripcion',
                            editor.getContent(),
                            false
                        );
                    }
                };

                window.generarDescripcionFichaIA = async () => {
                    await window.sincronizarEditorFicha?.();
                    await @this.call('generarDescripcionIA');
                };

                window.guardarFichaDesdeEditor = async () => {
                    await window.sincronizarEditorFicha?.();
                    await @this.call('guardar');
                };

                window.addEventListener('abrir-modal-ficha', (event) => {
                    iniciarEditorFicha(event.detail.contenido ?? '');
                });

                window.addEventListener('cerrar-modal-ficha', () => {
                    clearTimeout(temporizadorFicha);
                    quitarEditorFicha();
                });

                window.addEventListener(
                    'actualizar-editor-ficha',
                    (event) => {
                        const contenido = event.detail.contenido ?? '';
                        const editor = obtenerEditorFicha();

                        if (!editor) {
                            iniciarEditorFicha(contenido);
                            return;
                        }

                        editor.setContent(contenido);
                        editor.focus();

                        @this.set(
                            'descripcion',
                            contenido,
                            false
                        );
                    }
                );

                document.addEventListener('livewire:navigating', () => {
                    clearTimeout(temporizadorFicha);
                    quitarEditorFicha();
                });
            });
        </script>
    @endpush
</div>
