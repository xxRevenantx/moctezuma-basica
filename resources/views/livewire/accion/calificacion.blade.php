@php($esProfesor = (bool) auth()->user()?->isProfessor())

<div x-data="{
    insIds: @js(collect($inscripcionesTabla)->pluck('inscripcion_id')->values()->all()),
    asigIds: @js(collect($materias)->pluck('id')->values()->all()),

    storageKey: 'calificaciones_filtros_{{ $slug_nivel }}',
    contextoBusquedaGlobal: @js($contextoBusquedaGlobal),

    guardandoFiltros: false,
    restaurandoFiltros: false,

    herramientasAcademicasKey: 'calificaciones_herramientas_{{ $slug_nivel }}',
    herramientasAcademicasAbiertas: @js(!empty($resumenImportacion) || !empty($diagnosticoIa)),
    forzarHerramientasAcademicasAbiertas: @js(!empty($resumenImportacion) || !empty($diagnosticoIa)),
    subiendoPlantilla: false,
    porcentajePlantilla: 0,

    iniciarHerramientasAcademicas() {
        if (this.forzarHerramientasAcademicasAbiertas) {
            this.herramientasAcademicasAbiertas = true;
            localStorage.setItem(this.herramientasAcademicasKey, 'true');
            return;
        }

        const estadoGuardado = localStorage.getItem(this.herramientasAcademicasKey);

        if (estadoGuardado !== null) {
            this.herramientasAcademicasAbiertas = estadoGuardado === 'true';
        }
    },

    alternarHerramientasAcademicas() {
        this.herramientasAcademicasAbiertas = !this.herramientasAcademicasAbiertas;
        localStorage.setItem(
            this.herramientasAcademicasKey,
            this.herramientasAcademicasAbiertas ? 'true' : 'false'
        );
    },

    async iniciarFiltrosGuardados() {
        if (this.contextoBusquedaGlobal) {
            localStorage.removeItem(this.storageKey);
        } else {
            await this.restaurarFiltros();
        }

        this.$watch('$wire.ciclo_escolar_id', () => this.guardarFiltros());
        this.$watch('$wire.generacion_id', () => this.guardarFiltros());
        this.$watch('$wire.grado_id', () => this.guardarFiltros());
        this.$watch('$wire.semestre_id', () => this.guardarFiltros());
        this.$watch('$wire.grupo_id', () => this.guardarFiltros());
        this.$watch('$wire.parcial_bachillerato_id', () => this.guardarFiltros());
        this.$watch('$wire.periodo_basica_id', () => this.guardarFiltros());
        this.$watch('$wire.busqueda', () => this.guardarFiltros());
        this.$watch('$wire.filtro_estado', () => this.guardarFiltros());
        this.$watch('$wire.filtro_registros', () => this.guardarFiltros());
        this.$watch('$wire.filtro_estatus_historico', () => this.guardarFiltros());
        this.$watch('$wire.orden_promedio', () => this.guardarFiltros());
    },

    async restaurarFiltros() {
        const filtrosGuardados = localStorage.getItem(this.storageKey);

        if (!filtrosGuardados) {
            return;
        }

        let filtros = null;

        try {
            filtros = JSON.parse(filtrosGuardados);
        } catch (error) {
            localStorage.removeItem(this.storageKey);
            return;
        }

        this.restaurandoFiltros = true;

        if (filtros.ciclo_escolar_id) {
            await this.$wire.set('ciclo_escolar_id', filtros.ciclo_escolar_id);
            await this.esperar(300);

            if (String(this.$wire.ciclo_escolar_id || '') !== String(filtros.ciclo_escolar_id)) {
                localStorage.removeItem(this.storageKey);
                this.restaurandoFiltros = false;
                return;
            }
        }

        if (filtros.generacion_id) {
            await this.$wire.set('generacion_id', filtros.generacion_id);
            await this.esperar(250);

            if (String(this.$wire.generacion_id || '') !== String(filtros.generacion_id)) {
                localStorage.removeItem(this.storageKey);
                this.restaurandoFiltros = false;
                return;
            }
        }

        if (filtros.grado_id) {
            await this.$wire.set('grado_id', filtros.grado_id);
            await this.esperar(250);

            if (String(this.$wire.grado_id || '') !== String(filtros.grado_id)) {
                localStorage.removeItem(this.storageKey);
                this.restaurandoFiltros = false;
                return;
            }
        }

        if (filtros.semestre_id) {
            await this.$wire.set('semestre_id', filtros.semestre_id);
            await this.esperar(250);

            if (String(this.$wire.semestre_id || '') !== String(filtros.semestre_id)) {
                localStorage.removeItem(this.storageKey);
                this.restaurandoFiltros = false;
                return;
            }
        }

        if (filtros.grupo_id) {
            await this.$wire.set('grupo_id', filtros.grupo_id);
            await this.esperar(250);

            if (String(this.$wire.grupo_id || '') !== String(filtros.grupo_id)) {
                localStorage.removeItem(this.storageKey);
                this.restaurandoFiltros = false;
                return;
            }
        }

        if (filtros.parcial_bachillerato_id) {
            await this.$wire.set('parcial_bachillerato_id', filtros.parcial_bachillerato_id);
            await this.esperar(250);

            if (String(this.$wire.parcial_bachillerato_id || '') !== String(filtros.parcial_bachillerato_id)) {
                localStorage.removeItem(this.storageKey);
                this.restaurandoFiltros = false;
                return;
            }
        }

        if (filtros.periodo_basica_id) {
            await this.$wire.set('periodo_basica_id', filtros.periodo_basica_id);
            await this.esperar(250);
        }

        if (filtros.busqueda !== undefined) {
            await this.$wire.set('busqueda', filtros.busqueda);
        }

        if (filtros.filtro_estado !== undefined) {
            await this.$wire.set('filtro_estado', filtros.filtro_estado);
        }

        if (filtros.filtro_registros !== undefined) {
            await this.$wire.set('filtro_registros', filtros.filtro_registros || 'todos');
        }

        if (filtros.filtro_estatus_historico !== undefined) {
            await this.$wire.set('filtro_estatus_historico', filtros.filtro_estatus_historico);
        }

        if (filtros.orden_promedio !== undefined) {
            await this.$wire.set('orden_promedio', filtros.orden_promedio);
        }

        this.restaurandoFiltros = false;

        // Se recargan las gráficas después de restaurar los filtros guardados.
        this.$nextTick(() => {
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('recargar-graficas-calificaciones'));
            }, 600);
        });
    },

    guardarFiltros() {
        if (this.restaurandoFiltros || this.guardandoFiltros) {
            return;
        }

        this.guardandoFiltros = true;

        const filtros = {
            ciclo_escolar_id: this.$wire.ciclo_escolar_id || '',
            generacion_id: this.$wire.generacion_id || '',
            grado_id: this.$wire.grado_id || '',
            semestre_id: this.$wire.semestre_id || '',
            grupo_id: this.$wire.grupo_id || '',
            parcial_bachillerato_id: this.$wire.parcial_bachillerato_id || '',
            periodo_basica_id: this.$wire.periodo_basica_id || '',
            busqueda: this.$wire.busqueda || '',
            filtro_estado: this.$wire.filtro_estado || '',
            filtro_registros: this.$wire.filtro_registros || 'todos',
            filtro_estatus_historico: this.$wire.filtro_estatus_historico || '',
            orden_promedio: this.$wire.orden_promedio || '',
        };

        localStorage.setItem(this.storageKey, JSON.stringify(filtros));

        setTimeout(() => {
            this.guardandoFiltros = false;
        }, 100);
    },

    limpiarFiltrosGuardados() {
        localStorage.removeItem(this.storageKey);
    },

    esperar(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    move(insId, asigId, direction) {
        const rowIndex = this.insIds.indexOf(insId);
        const colIndex = this.asigIds.indexOf(asigId);

        if (rowIndex === -1 || colIndex === -1) return;

        let nextRowIndex = rowIndex;
        let nextColIndex = colIndex;

        if (direction === 'down') nextRowIndex++;
        if (direction === 'up') nextRowIndex--;
        if (direction === 'right') nextColIndex++;
        if (direction === 'left') nextColIndex--;

        if (nextRowIndex < 0 || nextRowIndex >= this.insIds.length) return;
        if (nextColIndex < 0 || nextColIndex >= this.asigIds.length) return;

        const nextInsId = this.insIds[nextRowIndex];
        const nextAsigId = this.asigIds[nextColIndex];
        const el = document.getElementById(`cal-${nextInsId}-${nextAsigId}`);

        if (el) {
            el.focus();

            if (typeof el.select === 'function') {
                el.select();
            }
        }
    }
}" x-init="iniciarFiltrosGuardados();
iniciarHerramientasAcademicas()" x-on:abrir-pdf-entrega.window="window.open($event.detail.url, '_blank', 'noopener')" class="w-full">

    {{-- Niveles --}}
    @unless ($this->esProfesorAutenticado)
    <div class="overflow-hidden">
        <div class="-mx-1 overflow-x-auto pb-1">
            <div class="flex min-w-max items-center justify-center gap-2 px-1">
                @foreach ($niveles as $item)
                    @php($activo = $slug_nivel === $item->slug)

                    <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'calificaciones']) }}"
                        wire:navigate aria-current="{{ $activo ? 'page' : 'false' }}"
                        class="group relative inline-flex items-center gap-2 whitespace-nowrap rounded-2xl border px-4 py-3 text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5
                        {{ $activo
                            ? 'border-sky-200 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20 dark:border-sky-700/50'
                            : 'border-slate-200 bg-white text-slate-700 shadow-sm hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:border-sky-800 dark:hover:bg-neutral-800 dark:hover:text-sky-300' }}">

                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-xl
                            {{ $activo
                                ? 'bg-white/15 text-white'
                                : 'bg-slate-100 text-slate-500 group-hover:bg-sky-100 group-hover:text-sky-700 dark:bg-neutral-700 dark:text-slate-300 dark:group-hover:bg-sky-950/40 dark:group-hover:text-sky-300' }}">
                            <flux:icon.rectangle-stack class="h-4 w-4" />
                        </span>

                        <span>{{ $item->nombre }}</span>

                        @if ($activo)
                            <span
                                class="rounded-full bg-white/15 px-2 py-0.5 text-[11px] font-bold text-white">Activo</span>
                            <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-white/80"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    @endunless

    @if ($this->esProfesorAutenticado && $this->entregaConfirmadaActual)
        <div class="mt-4 flex flex-col gap-4 rounded-[24px] border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/20 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-black text-emerald-900 dark:text-emerald-100">Entrega confirmada y bloqueada</p>
                <p class="mt-1 text-xs leading-5 text-emerald-700 dark:text-emerald-300">Folio {{ $this->entregaConfirmadaActual->folio }} · {{ $this->entregaConfirmadaActual->confirmada_at?->format('d/m/Y H:i') }}. Solo administración puede reabrir esta captura.</p>
            </div>
            <a href="{{ route('docente.entregas.word', $this->entregaConfirmadaActual) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-xs font-black text-[#006492] hover:bg-sky-100">
                <flux:icon.document-text class="size-4" /> Descargar Word
            </a>
            <a href="{{ route('docente.entregas.pdf', $this->entregaConfirmadaActual) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-black text-white hover:bg-emerald-800">
                <flux:icon.document-arrow-down class="size-4" /> Descargar comprobante
            </a>
        </div>
    @endif

    {{-- Acceso separado a la captura por docente (administración, tres niveles numéricos). --}}
    @if (auth()->user()?->is_admin && in_array($slug_nivel, ['primaria', 'secundaria', 'bachillerato'], true))
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 dark:border-sky-900 dark:bg-sky-950/20">
            <div><p class="font-black text-[#006492] dark:text-sky-300">Calificaciones por Docente PRO</p><p class="text-xs text-slate-600 dark:text-slate-300">Captura e importa por profesor sin mezclar la matriz general.</p></div>
            <a href="{{ route('misrutas.calificaciones-docente', ['slug_nivel' => $slug_nivel]) }}" wire:navigate class="rounded-xl bg-[#006492] px-4 py-2.5 text-sm font-bold text-white hover:bg-sky-800">Abrir módulo →</a>
        </div>
    @endif

    {{--
        Indicadores independientes por destino. Solo se muestran mientras Livewire
        procesa el modelo o metodo indicado; no hay overlay global bloqueante.
    --}}
    {{-- Declaraciones inline para evitar conflictos en el compilador Blade. --}}
    @php($objetivosContextoDocumentos = 'ciclo_escolar_id,generacion_id,grado_id,grupo_id,semestre_id,parcial_bachillerato_id,periodo_basica_id,limpiarFiltros,seleccionarModoConsulta')
    @php($objetivosTabla = $objetivosContextoDocumentos . ',busqueda,filtro_estado,filtro_registros,filtro_estatus_historico,orden_promedio,importarPlantillaCalificaciones')

    {{-- Navegación entre captura vigente e historial --}}
    @unless ($this->esProfesorAutenticado)
    <div class="mt-4 flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-3 shadow-sm dark:border-neutral-700 dark:bg-neutral-900 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex rounded-2xl bg-slate-100 p-1 dark:bg-neutral-800">
            <button type="button" wire:click="seleccionarModoConsulta('actual')"
                class="rounded-xl px-4 py-2 text-sm font-black transition {{ $this->modoConsulta === 'actual' ? 'bg-white text-sky-700 shadow-sm dark:bg-neutral-700 dark:text-sky-300' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
                Ciclo actual
            </button>
            <button type="button" wire:click="seleccionarModoConsulta('historico')"
                class="rounded-xl px-4 py-2 text-sm font-black transition {{ $this->modoConsulta === 'historico' ? 'bg-white text-amber-700 shadow-sm dark:bg-neutral-700 dark:text-amber-300' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
                Histórico
            </button>
        </div>

        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
            {{ $this->esConsultaHistorica
                ? 'La lista se reconstruye con la inscripción y asignación que el alumno tuvo en el ciclo seleccionado, aunque ya haya sido promovido.'
                : 'Captura ordinaria del ciclo escolar vigente.' }}
        </p>
    </div>
    @endunless

    {{-- Filtros principales --}}
    <div
        class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 {{ $this->esBachillerato ? 'xl:grid-cols-7' : 'xl:grid-cols-6' }}">

        <div>
            <flux:select label="Ciclo escolar" wire:model.live="ciclo_escolar_id"
                wire:key="ciclo-calificaciones-{{ $slug_nivel }}-{{ $ciclosEscolares->pluck('id')->implode('-') ?: 'sin-ciclos' }}">
                <flux:select.option value="">-- Selecciona un ciclo --</flux:select.option>

                @foreach ($ciclosEscolares as $ciclo)
                    <flux:select.option value="{{ $ciclo->id }}">
                        {{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}
                        @if ($ciclo->es_actual)
                            · Actual
                        @elseif ($ciclo->cerrado_at)
                            · Cerrado
                        @else
                            · Histórico
                        @endif
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div>
            <flux:select label="Generación" wire:model.live="generacion_id"
                wire:key="generacion-{{ $slug_nivel }}-{{ $ciclo_escolar_id ?: 'sin-ciclo' }}-{{ $generaciones->count() }}"
                :disabled="!$ciclo_escolar_id || $generaciones->isEmpty()">
                <flux:select.option value="">-- Selecciona una generación --</flux:select.option>

                @foreach ($generaciones as $generacion)
                    <flux:select.option value="{{ $generacion->id }}">
                        {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                        {{ $generacion->status ? '' : '· Histórica' }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div>
            <flux:select label="Grado" wire:model.live="grado_id"
                wire:key="grado-{{ $ciclo_escolar_id ?: 'sin-ciclo' }}-{{ $generacion_id ?: 'null' }}-{{ $grados->count() }}"
                :disabled="!$ciclo_escolar_id || !$generacion_id || $grados->isEmpty()">
                <flux:select.option value="">-- Selecciona un grado --</flux:select.option>

                @foreach ($grados as $g)
                    <flux:select.option value="{{ $g->id }}">
                        {{ $g->nombre }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if ($this->esBachillerato)
            <div>
                <flux:select label="Semestre" wire:model.live="semestre_id"
                    wire:key="semestre-bachillerato-{{ $ciclo_escolar_id ?: 'sin-ciclo' }}-{{ $generacion_id ?: 'null' }}-{{ $grado_id ?: 'null' }}-{{ $semestres->pluck('id')->implode('-') ?: 'sin-semestres' }}"
                    :disabled="!$ciclo_escolar_id || !$generacion_id || !$grado_id || $semestres->isEmpty()">
                    <flux:select.option value="">-- Selecciona un semestre --</flux:select.option>

                    @foreach ($semestres as $sem)
                        <flux:select.option value="{{ $sem->id }}">
                            {{ $sem->numero }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select label="Grupo" wire:model.live="grupo_id"
                    wire:key="grupo-bachillerato-{{ $ciclo_escolar_id ?: 'sin-ciclo' }}-{{ $generacion_id ?: 'null' }}-{{ $grado_id ?: 'null' }}-{{ $semestre_id ?: 'null' }}-{{ $grupos->count() }}"
                    :disabled="!$ciclo_escolar_id || !$generacion_id || !$grado_id || !$semestre_id || $grupos->isEmpty()">
                    <flux:select.option value="">-- Selecciona un grupo --</flux:select.option>

                    @foreach ($grupos as $gpo)
                        <flux:select.option value="{{ $gpo->id }}">
                            {{ $this->textoGrupo($gpo) }}{{ $gpo->trashed() ? ' · Archivado' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select label="Parcial" wire:model.live="parcial_bachillerato_id"
                    wire:key="parcial-bachillerato-{{ $ciclo_escolar_id ?: 'sin-ciclo' }}-{{ $generacion_id ?: 'null' }}-{{ $grado_id ?: 'null' }}-{{ $semestre_id ?: 'null' }}-{{ $grupo_id ?: 'null' }}-{{ $parciales->pluck('id')->implode('-') ?: 'sin-parciales' }}"
                    :disabled="!$ciclo_escolar_id || !$generacion_id || !$grado_id || !$semestre_id || !$grupo_id || $parciales->isEmpty()">
                    <flux:select.option value="">-- Selecciona un parcial --</flux:select.option>

                    @foreach ($parciales as $parcial)
                        <flux:select.option value="{{ $parcial->id }}">
                            {{ $parcial->descripcion }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @else
            <div>
                <flux:select label="Grupo" wire:model.live="grupo_id"
                    wire:key="grupo-basica-{{ $ciclo_escolar_id ?: 'sin-ciclo' }}-{{ $generacion_id ?: 'null' }}-{{ $grado_id ?: 'null' }}-{{ $grupos->count() }}"
                    :disabled="!$ciclo_escolar_id || !$generacion_id || !$grado_id || $grupos->isEmpty()">
                    <flux:select.option value="">-- Selecciona un grupo --</flux:select.option>

                    @foreach ($grupos as $gpo)
                        <flux:select.option value="{{ $gpo->id }}">
                            {{ $this->textoGrupo($gpo) }}{{ $gpo->trashed() ? ' · Archivado' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select label="Periodo" wire:model.live="periodo_basica_id"
                    wire:key="periodo-basica-{{ $ciclo_escolar_id ?: 'sin-ciclo' }}-{{ $generacion_id ?: 'null' }}-{{ $grado_id ?: 'null' }}-{{ $grupo_id ?: 'null' }}-{{ $periodosBasica->pluck('id')->implode('-') ?: 'sin-periodos' }}"
                    :disabled="!$ciclo_escolar_id || !$generacion_id || !$grado_id || !$grupo_id || $periodosBasica->isEmpty()">
                    <flux:select.option value="">-- Selecciona un periodo --</flux:select.option>

                    @foreach ($periodosBasica as $periodoBasica)
                        <flux:select.option value="{{ $periodoBasica->id }}">
                            {{ $periodoBasica->descripcion ?? ($periodoBasica->periodo ?? 'Periodo ' . $periodoBasica->id) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif

        <div class="mt-7">
            <button type="button" x-on:click="limpiarFiltrosGuardados()" wire:click="limpiarFiltros"
                class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:opacity-95">
                Limpiar filtros
            </button>
        </div>
    </div>

    @if ($mensajeContexto !== '')
        <div
            class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
            <span
                class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200">
                <flux:icon.exclamation-triangle class="h-4 w-4" />
            </span>
            <div>
                <p class="font-bold">No se puede completar la carga con la selección actual</p>
                <p class="mt-0.5 leading-5">{{ $mensajeContexto }}</p>
            </div>
        </div>
    @endif

    @if ($this->esBachillerato && $generacion_id && $grado_id)
        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            Los semestres y parciales se muestran únicamente cuando tienen un periodo académico registrado para la
            generación seleccionada.
        </p>
    @endif

    {{-- Periodo seleccionado --}}
    @if ($this->periodoSeleccionado)
        <div
            class="mt-6 overflow-hidden rounded-[28px] border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
            <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500"></div>

            <div class="p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white shadow-lg">
                            <flux:icon.calendar-days class="h-7 w-7" />
                        </div>

                        <div>
                            <h3 class="text-2xl font-black tracking-tight text-neutral-900 dark:text-neutral-100">
                                {{ $this->esBachillerato ? 'PERIODO SEMESTRAL' : 'PERIODO ESCOLAR' }}
                            </h3>
                            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                {{ $this->esConsultaHistorica
                                    ? 'Evidencia académica del periodo seleccionado, conservada aun cuando los alumnos ya fueron promovidos.'
                                    : 'Información vigente del periodo académico seleccionado.' }}
                            </p>
                        </div>
                    </div>

                    <span
                        class="inline-flex items-center rounded-full px-4 py-1.5 text-sm font-semibold {{ $this->claseEstadoPeriodo }}">
                        {{ $this->estadoPeriodo }}
                    </span>
                </div>

                @if (!empty($this->periodoSeleccionado['ciclo_cerrado']))
                    <div class="mt-5 flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <flux:icon.lock-closed class="mt-0.5 h-5 w-5 shrink-0" />
                            <div>
                                <p class="font-black">
                                    {{ $correccionHistoricaHabilitada ? 'Corrección histórica habilitada.' : 'Ciclo escolar cerrado: consulta histórica protegida.' }}
                                </p>
                                <p class="mt-1 text-xs leading-5">
                                    @if ($correccionHistoricaHabilitada)
                                        Motivo: {{ $this->motivoCorreccionCompleto }}. Puedes editar desde la tabla o importar Excel; cada movimiento quedará en la bitácora y en el historial de correcciones.
                                    @elseif ($this->puedeAdministrarCorreccionHistorica)
                                        Revisa y exporta la evidencia. Para modificarla, habilita una sesión de corrección indicando el motivo.
                                    @else
                                        La información es de solo lectura. Únicamente administración puede habilitar una corrección histórica.
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($this->puedeAdministrarCorreccionHistorica)
                            @if ($correccionHistoricaHabilitada)
                                <button type="button" wire:click="finalizarCorreccionHistorica"
                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl border border-amber-300 bg-white px-4 py-2.5 text-xs font-black text-amber-800 shadow-sm transition hover:bg-amber-100 dark:border-amber-800 dark:bg-neutral-900 dark:text-amber-200 dark:hover:bg-amber-950/40">
                                    <flux:icon.lock-closed class="h-4 w-4" /> Finalizar corrección
                                </button>
                            @else
                                <button type="button" wire:click="abrirCorreccionHistorica"
                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-amber-500/20 transition hover:opacity-95">
                                    <flux:icon.pencil-square class="h-4 w-4" /> Habilitar corrección
                                </button>
                            @endif
                        @endif
                    </div>
                @endif

                @if ($this->hayAlumnosIncluidosPorGeneracion)
                    <div class="mt-4 flex flex-col gap-4 rounded-2xl border border-violet-200 bg-violet-50 px-4 py-4 text-sm text-violet-800 dark:border-violet-900/50 dark:bg-violet-950/20 dark:text-violet-200 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <flux:icon.users class="mt-0.5 h-5 w-5 shrink-0" />
                            <div>
                                <p class="font-black">
                                    {{ $this->cantidadAlumnosIncluidosPorGeneracion }} alumno(s) incluidos por pertenecer a la generación.
                                </p>
                                <p class="mt-1 text-xs leading-5">
                                    Aparecen aunque su fecha de inscripción sea posterior o ya hayan sido promovidos.
                                    @if ($this->cantidadAlumnosConContextoPendiente > 0)
                                        {{ $this->cantidadAlumnosConContextoPendiente }} todavía no tienen grupo histórico confirmado para este ciclo y semestre; el grupo se fijará al guardar su primera calificación.
                                    @else
                                        Todos ya tienen un contexto académico confirmado para esta selección.
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if (!$this->esConsultaHistorica && $this->cantidadAlumnosConContextoPendiente > 0 && $this->puedeAdministrarCorreccionHistorica)
                            @if ($correccionHistoricaHabilitada)
                                <button type="button" wire:click="finalizarCorreccionHistorica"
                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl border border-violet-300 bg-white px-4 py-2.5 text-xs font-black text-violet-800 shadow-sm transition hover:bg-violet-100 dark:border-violet-800 dark:bg-neutral-900 dark:text-violet-200 dark:hover:bg-violet-950/40">
                                    <flux:icon.lock-closed class="h-4 w-4" /> Finalizar confirmación
                                </button>
                            @else
                                <button type="button" wire:click="abrirCorreccionHistorica"
                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-600 px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-violet-500/20 transition hover:opacity-95">
                                    <flux:icon.check-circle class="h-4 w-4" /> Confirmar inclusión
                                </button>
                            @endif
                        @endif
                    </div>
                @endif

                <div
                    class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 {{ $this->esBachillerato ? 'xl:grid-cols-6' : 'xl:grid-cols-5' }}">
                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>Ciclo escolar
                        </div>
                        <div class="mt-2 text-xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            {{ $this->periodoSeleccionado['ciclo_escolar'] ?? 'Sin ciclo' }}
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-violet-500"></span>Generación
                        </div>
                        <div class="mt-2 text-xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            @php($generacionSeleccionada = collect($generaciones)->firstWhere('id', $generacion_id))
                            {{ $generacionSeleccionada ? $generacionSeleccionada->anio_ingreso . ' - ' . $generacionSeleccionada->anio_egreso : 'Sin generación' }}
                        </div>
                    </div>



                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-violet-500"></span>{{ $this->esBachillerato ? 'Parcial' : 'Periodo' }}
                        </div>
                        <div class="mt-2 text-xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            {{ $this->periodoSeleccionado['parcial'] ?? ($this->esBachillerato ? 'Sin parcial' : 'Sin periodo') }}
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Inicio
                        </div>
                        <div class="mt-2 text-xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            {{ !empty($this->periodoSeleccionado['fecha_inicio']) ? \Carbon\Carbon::parse($this->periodoSeleccionado['fecha_inicio'])->format('d/m/Y') : 'Sin fecha' }}
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50/70 p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/50">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>Término
                        </div>
                        <div class="mt-2 text-xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            {{ !empty($this->periodoSeleccionado['fecha_fin']) ? \Carbon\Carbon::parse($this->periodoSeleccionado['fecha_fin'])->format('d/m/Y') : 'Sin fecha' }}
                        </div>
                    </div>
                </div>

                @if (!empty($this->periodoSeleccionado['fecha_inicio']) && !empty($this->periodoSeleccionado['fecha_fin']))
                    <div class="mt-6">
                        <div
                            class="mb-2 flex items-center justify-between text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            <span>{{ \Carbon\Carbon::parse($this->periodoSeleccionado['fecha_inicio'])->format('d/m/Y') }}</span>
                            <span>{{ \Carbon\Carbon::parse($this->periodoSeleccionado['fecha_fin'])->format('d/m/Y') }}</span>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500 transition-all duration-500"
                                style="width: {{ $this->porcentajePeriodo }}%"></div>
                        </div>
                        <div class="mt-2 text-right text-sm font-medium text-neutral-600 dark:text-neutral-300">Avance
                            {{ $this->porcentajePeriodo }}%</div>
                    </div>
                @endif
            </div>
        </div>
    @endif


    @unless ($esProfesor)
    {{-- Acordeón: importación, indicadores y análisis académico --}}
    <section
        class="mt-6 overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-xl shadow-slate-200/50 dark:border-neutral-700 dark:bg-neutral-900 dark:shadow-black/20">
        <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-violet-600"></div>

        <button type="button" x-on:click="alternarHerramientasAcademicas()"
            x-bind:aria-expanded="herramientasAcademicasAbiertas"
            aria-controls="herramientas-academicas-{{ $slug_nivel }}"
            class="group flex w-full flex-col gap-4 px-5 py-5 text-left transition hover:bg-slate-50/80 dark:hover:bg-neutral-800/60 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-md shadow-slate-300/50 dark:bg-sky-700 dark:shadow-none">
                    <flux:icon.rectangle-stack class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-black uppercase tracking-[.15em] text-sky-700 dark:text-sky-300">Panel de herramientas</span>
                        @if (!empty($resumenImportacion))
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Excel procesado</span>
                        @endif
                        @if (!empty($diagnosticoIa))
                            <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-bold text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">Informe IA listo</span>
                        @endif
                    </div>
                    <h3 class="mt-1 text-xl font-black tracking-tight text-neutral-900 dark:text-white sm:text-2xl">Importación y análisis académico</h3>
                    <p class="mt-1 max-w-3xl text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Gestiona calificaciones masivas, consulta indicadores y genera un diagnóstico del grupo desde un mismo lugar.</p>
                </div>
            </div>
            <span class="flex shrink-0 items-center gap-2 self-end rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 shadow-sm transition group-hover:border-sky-200 group-hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 lg:self-auto">
                <span x-text="herramientasAcademicasAbiertas ? 'Ocultar herramientas' : 'Ver herramientas'"></span>
                <flux:icon.chevron-down class="h-4 w-4 transition-transform duration-300" x-bind:class="herramientasAcademicasAbiertas ? 'rotate-180' : ''" />
            </span>
        </button>

        <div id="herramientas-academicas-{{ $slug_nivel }}" x-show="herramientasAcademicasAbiertas" x-collapse
            x-cloak>
            <div
                class="border-t border-slate-200 bg-slate-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-950/20 sm:p-5">

                {{-- Importación de calificaciones por Excel --}}
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="h-1 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-500"></div>
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 text-[11px] font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-300">
                                    <flux:icon.cloud-arrow-up class="h-4 w-4" /> Excel · Importación masiva
                                </div>
                                <h3 class="mt-2 text-xl font-black tracking-tight text-neutral-900 dark:text-white sm:text-2xl">Actualiza las calificaciones desde Excel</h3>
                                <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">Usa la plantilla del grupo seleccionado. Modifica únicamente las columnas de materias; las observaciones no se importan.</p>
                            </div>
                            <button type="button" wire:click="descargarPlantillaImportacion"
                                wire:loading.attr="disabled" wire:target="descargarPlantillaImportacion,archivo_calificaciones,importarPlantillaCalificaciones"
                                @if (!$this->puedeUsarPlantillaImportacion) disabled @endif
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-extrabold text-white shadow-md shadow-emerald-600/15 transition hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 disabled:cursor-not-allowed disabled:opacity-50">
                                <flux:icon.arrow-down-tray class="h-4 w-4" />
                                <span wire:loading.remove wire:target="descargarPlantillaImportacion">Descargar plantilla</span>
                                <span wire:loading wire:target="descargarPlantillaImportacion">Preparando archivo...</span>
                            </button>
                        </div>
                        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 dark:bg-neutral-800"><span class="font-black text-emerald-600">01</span> Descargar</span>
                            <flux:icon.chevron-down class="h-3 w-3 -rotate-90 text-slate-400" />
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 dark:bg-neutral-800"><span class="font-black text-sky-600">02</span> Completar Excel</span>
                            <flux:icon.chevron-down class="h-3 w-3 -rotate-90 text-slate-400" />
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 dark:bg-neutral-800"><span class="font-black text-indigo-600">03</span> Importar y verificar</span>
                        </div>

                        @if (!$this->puedeUsarPlantillaImportacion)
                            <div
                                class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                                Primero selecciona todos los filtros requeridos y asegúrate de que existan alumnos y
                                materias cargadas.
                            </div>
                        @elseif (!$this->puedeImportarPlantilla)
                            <div
                                class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800 dark:border-sky-900/40 dark:bg-sky-950/20 dark:text-sky-300">
                                @if ($this->esConsultaHistorica)
                                    La plantilla histórica se puede descargar como evidencia. Para importarla, administración debe habilitar primero una corrección histórica e indicar el motivo.
                                @else
                                    No tienes permiso para importar calificaciones en este contexto.
                                @endif
                            </div>
                        @endif

                        <div class="mt-5 rounded-2xl border border-dashed border-sky-200 bg-sky-50/40 p-4 dark:border-sky-900/40 dark:bg-sky-950/10 sm:p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                                <div class="min-w-0 flex-1">
                                    <label for="archivo-importacion-calificaciones" class="mb-2 block text-sm font-extrabold text-slate-800 dark:text-slate-100">Selecciona tu archivo de calificaciones</label>
                                    <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">Formato Excel (.xlsx o .xls), máximo 10 MB. Los cambios se aplicarán al pulsar «Importar calificaciones».</p>
                                    <input id="archivo-importacion-calificaciones" type="file" wire:model="archivo_calificaciones" accept=".xlsx,.xls"
                                        x-on:livewire-upload-start="subiendoPlantilla = true; porcentajePlantilla = 0"
                                        x-on:livewire-upload-progress="porcentajePlantilla = $event.detail.progress"
                                        x-on:livewire-upload-finish="subiendoPlantilla = false; porcentajePlantilla = 100"
                                        x-on:livewire-upload-error="subiendoPlantilla = false; porcentajePlantilla = 0"
                                        x-on:livewire-upload-cancel="subiendoPlantilla = false; porcentajePlantilla = 0"
                                        wire:loading.attr="disabled" wire:target="importarPlantillaCalificaciones"
                                        @if (!$this->puedeImportarPlantilla) disabled @endif
                                        class="block w-full rounded-xl border border-slate-200 bg-white p-2.5 text-sm text-slate-700 shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sky-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-sky-800 hover:file:bg-sky-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:file:bg-sky-950/40 dark:file:text-sky-200">
                                    <div x-show="subiendoPlantilla" x-cloak class="mt-3" role="status" aria-live="polite">
                                        <div class="mb-1 flex justify-between text-xs font-bold text-sky-700 dark:text-sky-300"><span>Subiendo archivo...</span><span x-text="porcentajePlantilla + '%'"></span></div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-sky-100 dark:bg-sky-950"><div class="h-full rounded-full bg-sky-500 transition-all duration-200" x-bind:style="'width: ' + porcentajePlantilla + '%'"></div></div>
                                    </div>
                                    @error('archivo_calificaciones')
                                        <p class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="button" wire:click="importarPlantillaCalificaciones"
                                    wire:loading.attr="disabled" wire:target="archivo_calificaciones,importarPlantillaCalificaciones"
                                    @if (!$this->puedeImportarPlantilla) disabled @endif
                                    class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">
                                    <flux:icon.cloud-arrow-up class="h-4 w-4" />
                                    <span wire:loading.remove wire:target="importarPlantillaCalificaciones">Importar calificaciones</span>
                                    <span wire:loading wire:target="importarPlantillaCalificaciones">Procesando...</span>
                                </button>
                            </div>
                        </div>

                        @if (!empty($resumenImportacion))
                            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-4">
                                <div
                                    class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                    <p
                                        class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                        Creadas</p>
                                    <p class="mt-1 text-2xl font-black text-emerald-900 dark:text-emerald-100">
                                        {{ $resumenImportacion['creadas'] ?? 0 }}</p>
                                </div>

                                <div
                                    class="rounded-2xl border border-sky-100 bg-sky-50 p-4 dark:border-sky-900/40 dark:bg-sky-950/20">
                                    <p
                                        class="text-xs font-black uppercase tracking-wide text-sky-700 dark:text-sky-300">
                                        Editadas
                                    </p>
                                    <p class="mt-1 text-2xl font-black text-sky-900 dark:text-sky-100">
                                        {{ $resumenImportacion['editadas'] ?? 0 }}</p>
                                </div>

                                <div
                                    class="rounded-2xl border border-rose-100 bg-rose-50 p-4 dark:border-rose-900/40 dark:bg-rose-950/20">
                                    <p
                                        class="text-xs font-black uppercase tracking-wide text-rose-700 dark:text-rose-300">
                                        Eliminadas</p>
                                    <p class="mt-1 text-2xl font-black text-rose-900 dark:text-rose-100">
                                        {{ $resumenImportacion['eliminadas'] ?? 0 }}</p>
                                </div>

                                <div
                                    class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/60">
                                    <p
                                        class="text-xs font-black uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                        Sin cambios</p>
                                    <p class="mt-1 text-2xl font-black text-neutral-900 dark:text-neutral-100">
                                        {{ $resumenImportacion['sin_cambios'] ?? 0 }}</p>
                                </div>
                            </div>

                            @if (!empty($resumenImportacion['errores']))
                                <div
                                    class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/40 dark:bg-rose-950/20">
                                    <p class="text-sm font-black text-rose-800 dark:text-rose-200">Errores detectados
                                    </p>

                                    <ul
                                        class="mt-3 max-h-56 list-disc space-y-1 overflow-y-auto pl-5 text-sm font-semibold text-rose-700 dark:text-rose-300">
                                        @foreach ($resumenImportacion['errores'] as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                @php($estadisticas = $this->estadisticasCalificaciones)

                @if (count($inscripciones) > 0 && count($materias) > 0)
                    <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-3 2xl:grid-cols-6" aria-label="Indicadores académicos del grupo">
                        @foreach ([
                            ['nombre' => 'Promedio global', 'valor' => $estadisticas['promedio_global'] ?? '—', 'color' => 'sky', 'subtitulo' => 'Rendimiento'],
                            ['nombre' => 'Aprobación', 'valor' => ($estadisticas['porcentaje_aprobacion'] ?? 0). '%', 'color' => 'emerald', 'subtitulo' => 'Aprobadas'],
                            ['nombre' => 'Pendientes', 'valor' => $estadisticas['pendientes'] ?? 0, 'color' => 'amber', 'subtitulo' => 'Sin captura'],
                            ['nombre' => 'Reprobadas', 'valor' => $estadisticas['reprobadas'] ?? 0, 'color' => 'rose', 'subtitulo' => 'Por atender'],
                            ['nombre' => 'Especiales', 'valor' => $estadisticas['especiales'] ?? 0, 'color' => 'violet', 'subtitulo' => 'Valores especiales'],
                            ['nombre' => 'Captura', 'valor' => ($estadisticas['porcentaje_captura'] ?? 0). '%', 'color' => 'indigo', 'subtitulo' => 'Avance registrado'],
                        ] as $metrica)
                            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                <span @class([
                                    'absolute left-0 top-0 h-full w-1',
                                    'bg-sky-500' => $metrica['color'] === 'sky',
                                    'bg-emerald-500' => $metrica['color'] === 'emerald',
                                    'bg-amber-500' => $metrica['color'] === 'amber',
                                    'bg-rose-500' => $metrica['color'] === 'rose',
                                    'bg-violet-500' => $metrica['color'] === 'violet',
                                    'bg-indigo-500' => $metrica['color'] === 'indigo',
                                ])></span>
                                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $metrica['nombre'] }}</p>
                                <p class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ $metrica['valor'] }}</p>
                                <p class="mt-1 text-[11px] font-medium text-slate-400 dark:text-slate-500">{{ $metrica['subtitulo'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif


                {{-- DIAGNÓSTICO ACADÉMICO INTELIGENTE --}}
                @if ($this->diagnosticoCalificaciones['hay_datos'])
                    <div x-data="{ abiertoDiagnosticoAcademico: false }"
                        class="mt-5 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <div class="h-1 w-full bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400"></div>

                        <button type="button" x-on:click="abiertoDiagnosticoAcademico = !abiertoDiagnosticoAcademico"
                            class="group flex w-full items-center justify-between gap-4 bg-amber-50/60 px-5 py-5 text-left transition hover:bg-amber-50 dark:bg-amber-950/10 dark:hover:bg-amber-950/20">
                            <div class="flex min-w-0 items-start gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-amber-200 bg-white text-amber-700 shadow-sm dark:border-amber-900/40 dark:bg-neutral-950 dark:text-amber-300">
                                    <flux:icon.academic-cap class="h-6 w-6" />
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-100 dark:bg-neutral-950 dark:text-amber-300 dark:ring-amber-900/40">
                                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                        Diagnóstico académico automático
                                    </div>

                                    <h3
                                        class="mt-2 text-xl font-black tracking-tight text-neutral-900 dark:text-white">
                                        {{ $this->diagnosticoCalificaciones['titulo'] }}
                                    </h3>

                                    <p
                                        class="mt-1 max-w-3xl text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                        {{ $this->diagnosticoCalificaciones['descripcion'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <span
                                    class="hidden rounded-full border px-3 py-1 text-xs font-black sm:inline-flex {{ $this->claseTarjetaDiagnosticoCalificacion($this->diagnosticoCalificaciones['color']) }}">
                                    Salud {{ $this->diagnosticoCalificaciones['salud'] }}%
                                </span>

                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-neutral-500 shadow-sm ring-1 ring-neutral-200 transition group-hover:text-amber-600 dark:bg-neutral-950 dark:text-neutral-400 dark:ring-neutral-800 dark:group-hover:text-amber-300">
                                    <flux:icon.chevron-down class="h-5 w-5 transition-transform duration-300"
                                        x-bind:class="abiertoDiagnosticoAcademico ? 'rotate-180' : ''" />
                                </span>
                            </div>
                        </button>

                        <div x-show="abiertoDiagnosticoAcademico" x-cloak
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2">
                            <div class="space-y-6 border-t border-neutral-200 p-5 sm:p-6 dark:border-neutral-800">
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 shadow-sm dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                            <flux:icon.academic-cap class="h-7 w-7" />
                                        </div>

                                        <div>
                                            <div
                                                class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-100 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/40">
                                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                                Diagnóstico académico automático
                                            </div>

                                            <h3
                                                class="mt-3 text-2xl font-black tracking-tight text-neutral-900 dark:text-white">
                                                {{ $this->diagnosticoCalificaciones['titulo'] }}
                                            </h3>

                                            <p
                                                class="mt-1 max-w-3xl text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">
                                                {{ $this->diagnosticoCalificaciones['descripcion'] }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="w-full xl:w-[360px]">
                                        <div
                                            class="rounded-3xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/70">
                                            <div class="flex items-center justify-between">
                                                <p
                                                    class="text-xs font-black uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                                    Salud académica
                                                </p>

                                                <span
                                                    class="rounded-full border px-3 py-1 text-xs font-black {{ $this->claseTarjetaDiagnosticoCalificacion($this->diagnosticoCalificaciones['color']) }}">
                                                    {{ $this->diagnosticoCalificaciones['salud'] }}%
                                                </span>
                                            </div>

                                            <div
                                                class="mt-3 h-4 w-full overflow-hidden rounded-full bg-white shadow-inner dark:bg-neutral-900">
                                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-amber-500 to-rose-500 transition-all duration-500"
                                                    style="width: {{ $this->diagnosticoCalificaciones['salud'] }}%">
                                                </div>
                                            </div>

                                            <p
                                                class="mt-2 text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                                                Este porcentaje combina captura, aprobación, alumnos en riesgo y
                                                rendimiento por
                                                materia.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tarjetas principales --}}
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
                                    @foreach ($this->diagnosticoCalificaciones['tarjetas'] as $tarjeta)
                                        <div
                                            class="rounded-3xl border p-4 shadow-sm {{ $this->claseTarjetaDiagnosticoCalificacion($tarjeta['color']) }}">
                                            <p class="text-[11px] font-black uppercase tracking-wide opacity-80">
                                                {{ $tarjeta['titulo'] }}
                                            </p>

                                            <p class="mt-2 text-3xl font-black">
                                                {{ $tarjeta['valor'] }}
                                            </p>

                                            <p class="mt-1 text-xs font-semibold opacity-80">
                                                {{ $tarjeta['detalle'] }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Alertas inteligentes --}}
                                @if ($this->diagnosticoCalificaciones['alertas']->isNotEmpty())
                                    <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                                        @foreach ($this->diagnosticoCalificaciones['alertas'] as $alerta)
                                            <div
                                                class="rounded-3xl border p-4 {{ $this->claseAlertaDiagnosticoCalificacion($alerta['tipo']) }}">
                                                <div class="flex items-start gap-3">
                                                    <div
                                                        class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-white/70 dark:bg-neutral-950/40">
                                                        @if ($alerta['tipo'] === 'danger')
                                                            <flux:icon.x-circle class="h-5 w-5" />
                                                        @elseif ($alerta['tipo'] === 'warning')
                                                            <flux:icon.exclamation-triangle class="h-5 w-5" />
                                                        @elseif ($alerta['tipo'] === 'success')
                                                            <flux:icon.check-circle class="h-5 w-5" />
                                                        @else
                                                            <flux:icon.information-circle class="h-5 w-5" />
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <p class="text-sm font-black">
                                                            {{ $alerta['titulo'] }}
                                                        </p>

                                                        <p
                                                            class="mt-1 text-xs font-semibold leading-relaxed opacity-90">
                                                            {{ $alerta['mensaje'] }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Ranking, riesgo y candidatos --}}
                                <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                                    <div
                                        class="overflow-hidden rounded-[24px] border border-neutral-200 dark:border-neutral-800">
                                        <div
                                            class="border-b border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/70">
                                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                                Ranking académico
                                            </h4>
                                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                                Alumnos con mejor promedio del periodo seleccionado.
                                            </p>
                                        </div>

                                        <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                                            @forelse ($this->diagnosticoCalificaciones['ranking_alumnos']->take(5) as $alumnoRanking)
                                                <div class="flex items-center justify-between gap-3 px-4 py-3">
                                                    <div class="min-w-0">
                                                        <p
                                                            class="truncate text-xs font-black uppercase text-neutral-800 dark:text-white">
                                                            {{ $alumnoRanking['alumno'] }}
                                                        </p>
                                                        <p
                                                            class="text-[11px] font-semibold text-neutral-500 dark:text-neutral-400">
                                                            {{ $alumnoRanking['matricula'] }}
                                                        </p>
                                                    </div>

                                                    <span
                                                        class="rounded-full border px-3 py-1 text-xs font-black {{ $alumnoRanking['clase'] }}">
                                                        {{ $alumnoRanking['promedio_texto'] }}
                                                    </span>
                                                </div>
                                            @empty
                                                <div
                                                    class="px-4 py-6 text-center text-sm font-semibold text-neutral-500 dark:text-neutral-400">
                                                    No hay promedios suficientes para generar ranking.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div
                                        class="overflow-hidden rounded-[24px] border border-neutral-200 dark:border-neutral-800">
                                        <div
                                            class="border-b border-neutral-200 bg-rose-50 px-4 py-3 dark:border-neutral-800 dark:bg-rose-950/20">
                                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                                Alumnos en riesgo
                                            </h4>
                                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                                Promedio menor a 6 o varias materias reprobadas.
                                            </p>
                                        </div>

                                        <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                                            @forelse ($this->diagnosticoCalificaciones['alumnos_riesgo']->take(6) as $alumnoRiesgo)
                                                <div class="flex items-center justify-between gap-3 px-4 py-3">
                                                    <div class="min-w-0">
                                                        <p
                                                            class="truncate text-xs font-black uppercase text-neutral-800 dark:text-white">
                                                            {{ $alumnoRiesgo['alumno'] }}
                                                        </p>
                                                        <p
                                                            class="text-[11px] font-semibold text-neutral-500 dark:text-neutral-400">
                                                            {{ $alumnoRiesgo['reprobadas'] }} reprobada(s) ·
                                                            {{ $alumnoRiesgo['pendientes'] }} pendiente(s)
                                                        </p>
                                                    </div>

                                                    <span
                                                        class="rounded-full border px-3 py-1 text-xs font-black {{ $alumnoRiesgo['clase'] }}">
                                                        {{ $alumnoRiesgo['promedio_texto'] }}
                                                    </span>
                                                </div>
                                            @empty
                                                <div
                                                    class="px-4 py-6 text-center text-sm font-semibold text-emerald-600 dark:text-emerald-300">
                                                    No se detectaron alumnos en riesgo académico.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div
                                        class="overflow-hidden rounded-[24px] border border-neutral-200 dark:border-neutral-800">
                                        <div
                                            class="border-b border-neutral-200 bg-amber-50 px-4 py-3 dark:border-neutral-800 dark:bg-amber-950/20">
                                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                                Candidatos a reconocimiento
                                            </h4>
                                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                                Promedio mínimo 9.5, captura completa y sin materias reprobadas.
                                            </p>
                                        </div>

                                        <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                                            @forelse ($this->diagnosticoCalificaciones['candidatos_reconocimiento']->take(6) as $candidato)
                                                <div class="flex items-center justify-between gap-3 px-4 py-3">
                                                    <div class="min-w-0">
                                                        <p
                                                            class="truncate text-xs font-black uppercase text-neutral-800 dark:text-white">
                                                            {{ $candidato['alumno'] }}
                                                        </p>
                                                        <p
                                                            class="text-[11px] font-semibold text-neutral-500 dark:text-neutral-400">
                                                            {{ $candidato['matricula'] }}
                                                        </p>
                                                    </div>

                                                    <span
                                                        class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                                        {{ $candidato['promedio_texto'] }}
                                                    </span>
                                                </div>
                                            @empty
                                                <div
                                                    class="px-4 py-6 text-center text-sm font-semibold text-neutral-500 dark:text-neutral-400">
                                                    Aún no hay candidatos con criterios completos.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                {{-- Resumen por materia y captura incompleta --}}
                                <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                                    <div
                                        class="overflow-hidden rounded-[24px] border border-neutral-200 dark:border-neutral-800">
                                        <div
                                            class="border-b border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/70">
                                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                                Resumen académico por materia
                                            </h4>
                                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                                Promedio, aprobación, pendientes y estado por asignatura.
                                            </p>
                                        </div>

                                        <div class="overflow-x-auto">
                                            <table class="min-w-full border-collapse">
                                                <thead class="bg-white dark:bg-neutral-950">
                                                    <tr>
                                                        <th
                                                            class="border-b border-r border-neutral-200 px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                                                            Materia</th>
                                                        <th
                                                            class="border-b border-r border-neutral-200 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                                                            Prom.</th>
                                                        <th
                                                            class="border-b border-r border-neutral-200 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                                                            Rep.</th>
                                                        <th
                                                            class="border-b border-r border-neutral-200 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                                                            Pend.</th>
                                                        <th
                                                            class="border-b border-neutral-200 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                                                            Estado</th>
                                                    </tr>
                                                </thead>

                                                <tbody class="bg-white dark:bg-neutral-950/60">
                                                    @foreach ($this->diagnosticoCalificaciones['materias_resumen'] as $materiaResumen)
                                                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                                            <td
                                                                class="border-b border-r border-neutral-200 px-4 py-3 align-top dark:border-neutral-800">
                                                                <div
                                                                    class="text-xs font-black uppercase text-neutral-800 dark:text-white">
                                                                    {{ $materiaResumen['materia'] }}
                                                                </div>
                                                                <div
                                                                    class="mt-1 text-[11px] font-semibold text-neutral-500 dark:text-neutral-400">
                                                                    {{ $materiaResumen['profesor'] }}
                                                                </div>
                                                            </td>

                                                            <td
                                                                class="border-b border-r border-neutral-200 px-4 py-3 text-center dark:border-neutral-800">
                                                                <span
                                                                    class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 dark:bg-sky-950/30 dark:text-sky-300">
                                                                    {{ $materiaResumen['promedio_texto'] }}
                                                                </span>
                                                            </td>

                                                            <td
                                                                class="border-b border-r border-neutral-200 px-4 py-3 text-center text-xs font-black text-rose-600 dark:border-neutral-800 dark:text-rose-300">
                                                                {{ $materiaResumen['reprobadas'] }}
                                                            </td>

                                                            <td
                                                                class="border-b border-r border-neutral-200 px-4 py-3 text-center text-xs font-black text-amber-600 dark:border-neutral-800 dark:text-amber-300">
                                                                {{ $materiaResumen['pendientes'] }}
                                                            </td>

                                                            <td
                                                                class="border-b border-neutral-200 px-4 py-3 text-center dark:border-neutral-800">
                                                                <span
                                                                    class="rounded-full border px-3 py-1 text-xs font-black {{ $materiaResumen['clase'] }}">
                                                                    {{ $materiaResumen['estado'] }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div
                                        class="overflow-hidden rounded-[24px] border border-neutral-200 dark:border-neutral-800">
                                        <div
                                            class="border-b border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/70">
                                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                                Captura incompleta por alumno
                                            </h4>
                                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                                Alumnos con materias pendientes de capturar.
                                            </p>
                                        </div>

                                        <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                                            @forelse ($this->diagnosticoCalificaciones['alumnos_captura_incompleta']->take(10) as $incompleto)
                                                <div class="flex items-center justify-between gap-3 px-4 py-3">
                                                    <div class="min-w-0">
                                                        <p
                                                            class="truncate text-xs font-black uppercase text-neutral-800 dark:text-white">
                                                            {{ $incompleto['alumno'] }}
                                                        </p>
                                                        <p
                                                            class="text-[11px] font-semibold text-neutral-500 dark:text-neutral-400">
                                                            {{ $incompleto['matricula'] }}
                                                        </p>
                                                    </div>

                                                    <span
                                                        class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                                        Faltan {{ $incompleto['pendientes'] }}
                                                    </span>
                                                </div>
                                            @empty
                                                <div
                                                    class="px-4 py-6 text-center text-sm font-semibold text-emerald-600 dark:text-emerald-300">
                                                    Todos los alumnos tienen captura completa.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                {{-- Recomendaciones --}}
                                <div
                                    class="rounded-3xl border border-indigo-200 bg-gradient-to-br from-indigo-50 via-sky-50 to-cyan-50 p-4 dark:border-indigo-900/40 dark:from-indigo-950/20 dark:via-neutral-950 dark:to-cyan-950/20">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                                Recomendaciones automáticas
                                            </h4>

                                            <div class="mt-3 grid grid-cols-1 gap-2 lg:grid-cols-2">
                                                @foreach ($this->diagnosticoCalificaciones['recomendaciones'] as $recomendacion)
                                                    <div
                                                        class="rounded-2xl border border-white bg-white/80 px-4 py-3 text-xs font-semibold leading-relaxed text-neutral-600 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/60 dark:text-neutral-300">
                                                        {{ $recomendacion }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <span
                                            class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-2 text-xs font-black text-indigo-700 shadow-sm ring-1 ring-indigo-100 dark:bg-neutral-950 dark:text-indigo-300 dark:ring-indigo-900/40">
                                            Diagnóstico listo
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- INFORME PEDAGÓGICO REDACTADO CON GROQCLOUD --}}
                @if ($this->diagnosticoCalificaciones['hay_datos'])
                    <div x-data="{ copiado: false }"
                        class="mt-5 overflow-hidden rounded-3xl border border-violet-200/70 bg-white shadow-sm dark:border-violet-900/40 dark:bg-neutral-900">
                        <div class="h-1 bg-gradient-to-r from-violet-500 via-indigo-500 to-sky-500"></div>

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
                                            GroqCloud
                                        </div>

                                        <h3 class="mt-2 text-xl font-black text-neutral-900 dark:text-white">
                                            Informe académico redactado con IA
                                        </h3>

                                        <p
                                            class="mt-1 max-w-3xl text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">
                                            Convierte el diagnóstico automático del sistema en un informe profesional.
                                            Solo se envían
                                            estadísticas grupales; no se comparten nombres, matrículas ni calificaciones
                                            individuales.
                                        </p>
                                    </div>
                                </div>

                                <div class="w-full xl:w-[430px]">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]">
                                        <flux:select label="Tipo de informe" wire:model="tipoDiagnosticoIa">
                                            <flux:select.option value="pedagogico">Pedagógico para docentes
                                            </flux:select.option>
                                            <flux:select.option value="direccion">Resumen para dirección
                                            </flux:select.option>
                                            <flux:select.option value="consejo_tecnico">Consejo técnico escolar
                                            </flux:select.option>
                                            <flux:select.option value="familias">Resumen para familias
                                            </flux:select.option>
                                        </flux:select>

                                        <div class="flex items-end">
                                            <button type="button" wire:click="generarDiagnosticoIa"
                                                wire:loading.attr="disabled" wire:target="generarDiagnosticoIa"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-3 text-sm font-extrabold text-white shadow-md shadow-violet-600/20 transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                                                <span wire:loading.remove wire:target="generarDiagnosticoIa"
                                                    class="inline-flex items-center gap-2">
                                                    <flux:icon.sparkles class="h-4 w-4" />
                                                    Generar informe
                                                </span>

                                                <span wire:loading.flex wire:target="generarDiagnosticoIa"
                                                    class="items-center gap-2">
                                                    <span
                                                        class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                                    Generando…
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    @error('tipoDiagnosticoIa')
                                        <p class="mt-2 text-xs font-bold text-rose-600 dark:text-rose-300">
                                            {{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            @if (!empty($diagnosticoIa))
                                @php($prioridadIa = $diagnosticoIa['prioridad'] ?? 'media')

                                <div class="mt-6 border-t border-neutral-200 pt-6 dark:border-neutral-800">
                                    <div
                                        class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span
                                                class="inline-flex rounded-full border px-3 py-1 text-xs font-black uppercase {{ $this->clasePrioridadDiagnosticoIa($prioridadIa) }}">
                                                Prioridad {{ $prioridadIa }}
                                            </span>

                                            @if ($diagnosticoIaGeneradoEn)
                                                <span
                                                    class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-500 dark:bg-neutral-800 dark:text-neutral-300">
                                                    Generado {{ $diagnosticoIaGeneradoEn }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" wire:click="descargarInformeIaPdf"
                                                wire:loading.attr="disabled"
                                                wire:target="descargarInformeIaPdf,descargarInformeIaWord,generarDiagnosticoIa"
                                                class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-black text-sky-800 transition hover:bg-sky-100 disabled:cursor-wait disabled:opacity-50 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300">
                                                <flux:icon.document-arrow-down class="h-4 w-4" />
                                                <span wire:loading.remove wire:target="descargarInformeIaPdf">Descargar PDF</span>
                                                <span wire:loading wire:target="descargarInformeIaPdf">Preparando PDF…</span>
                                            </button>

                                            <button type="button" wire:click="descargarInformeIaWord"
                                                wire:loading.attr="disabled"
                                                wire:target="descargarInformeIaPdf,descargarInformeIaWord,generarDiagnosticoIa"
                                                class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-black text-blue-800 transition hover:bg-blue-100 disabled:cursor-wait disabled:opacity-50 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-300">
                                                <flux:icon.document-text class="h-4 w-4" />
                                                <span wire:loading.remove wire:target="descargarInformeIaWord">Descargar Word</span>
                                                <span wire:loading wire:target="descargarInformeIaWord">Preparando Word…</span>
                                            </button>

                                            <button type="button"
                                                x-on:click="navigator.clipboard.writeText($refs.informeGroq.innerText).then(() => { copiado = true; setTimeout(() => copiado = false, 1800) })"
                                                class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 text-xs font-black text-neutral-700 transition hover:border-violet-200 hover:text-violet-700 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:border-violet-800 dark:hover:text-violet-300">
                                                <flux:icon.clipboard-document class="h-4 w-4" />
                                                <span x-text="copiado ? 'Copiado' : 'Copiar informe'"></span>
                                            </button>

                                            <button type="button" wire:click="limpiarDiagnosticoIa"
                                                class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-black text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                                <flux:icon.trash class="h-4 w-4" />
                                                Limpiar
                                            </button>
                                        </div>
                                    </div>

                                    <div x-ref="informeGroq" class="space-y-5">
                                        <div
                                            class="rounded-3xl border border-violet-100 bg-gradient-to-br from-violet-50 via-white to-sky-50 p-5 dark:border-violet-900/40 dark:from-violet-950/20 dark:via-neutral-950 dark:to-sky-950/20">
                                            <h4 class="text-xl font-black text-neutral-900 dark:text-white">
                                                {{ $diagnosticoIa['titulo'] ?? 'Diagnóstico académico grupal' }}
                                            </h4>

                                            <p
                                                class="mt-3 whitespace-pre-line text-sm font-semibold leading-7 text-neutral-700 dark:text-neutral-300">
                                                {{ $diagnosticoIa['resumen_ejecutivo'] ?? '' }}
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                                            <div
                                                class="rounded-3xl border border-emerald-200 bg-emerald-50/70 p-5 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                                <h5
                                                    class="flex items-center gap-2 text-sm font-black text-emerald-800 dark:text-emerald-300">
                                                    <flux:icon.check-circle class="h-5 w-5" />
                                                    Fortalezas
                                                </h5>

                                                <div class="mt-3 space-y-2">
                                                    @forelse ($diagnosticoIa['fortalezas'] ?? [] as $fortaleza)
                                                        <div
                                                            class="rounded-2xl bg-white/80 px-4 py-3 text-xs font-semibold leading-relaxed text-neutral-700 shadow-sm dark:bg-neutral-950/60 dark:text-neutral-300">
                                                            {{ $fortaleza }}
                                                        </div>
                                                    @empty
                                                        <p class="text-xs text-neutral-500">No se señalaron fortalezas
                                                            específicas.</p>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div
                                                class="rounded-3xl border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-900/40 dark:bg-amber-950/20">
                                                <h5
                                                    class="flex items-center gap-2 text-sm font-black text-amber-800 dark:text-amber-300">
                                                    <flux:icon.exclamation-triangle class="h-5 w-5" />
                                                    Áreas de atención
                                                </h5>

                                                <div class="mt-3 space-y-2">
                                                    @forelse ($diagnosticoIa['areas_atencion'] ?? [] as $area)
                                                        <div
                                                            class="rounded-2xl bg-white/80 px-4 py-3 text-xs font-semibold leading-relaxed text-neutral-700 shadow-sm dark:bg-neutral-950/60 dark:text-neutral-300">
                                                            {{ $area }}
                                                        </div>
                                                    @empty
                                                        <p class="text-xs text-neutral-500">No se señalaron áreas
                                                            críticas.</p>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div
                                                class="rounded-3xl border border-sky-200 bg-sky-50/70 p-5 dark:border-sky-900/40 dark:bg-sky-950/20">
                                                <h5
                                                    class="flex items-center gap-2 text-sm font-black text-sky-800 dark:text-sky-300">
                                                    <flux:icon.light-bulb class="h-5 w-5" />
                                                    Recomendaciones
                                                </h5>

                                                <div class="mt-3 space-y-2">
                                                    @forelse ($diagnosticoIa['recomendaciones'] ?? [] as $recomendacionIa)
                                                        <div
                                                            class="rounded-2xl bg-white/80 px-4 py-3 text-xs font-semibold leading-relaxed text-neutral-700 shadow-sm dark:bg-neutral-950/60 dark:text-neutral-300">
                                                            {{ $recomendacionIa }}
                                                        </div>
                                                    @empty
                                                        <p class="text-xs text-neutral-500">No se generaron
                                                            recomendaciones.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="rounded-3xl border border-neutral-200 bg-neutral-50 p-5 dark:border-neutral-800 dark:bg-neutral-950/60">
                                            <h5 class="text-sm font-black text-neutral-900 dark:text-white">Diagnóstico
                                                pedagógico</h5>
                                            <p
                                                class="mt-3 whitespace-pre-line text-sm leading-7 text-neutral-600 dark:text-neutral-300">
                                                {{ $diagnosticoIa['diagnostico_pedagogico'] ?? '' }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold leading-relaxed text-slate-600 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-300">
                                            {{ $diagnosticoIa['aviso'] ?? 'Este informe es orientativo y debe revisarse antes de utilizarse.' }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div
                                    class="mt-5 rounded-2xl border border-dashed border-violet-200 bg-violet-50/50 px-4 py-4 text-sm font-semibold text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/20 dark:text-violet-300">
                                    Selecciona el tipo de informe y pulsa <strong>Generar informe</strong>. El contenido
                                    no se guarda
                                    automáticamente en la base de datos.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </section>

    @endunless

    {{-- Tabla --}}
    <div
        class="mt-6 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="relative">
            {{-- Los loaders son locales y no bloquean toda la matriz de calificaciones. --}}

            <div class="border-b border-slate-200 bg-slate-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-900/60 sm:p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-sky-100 bg-white text-sky-700 shadow-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-sky-300"><flux:icon.clipboard-document class="h-5 w-5" /></span>
                        <div><h3 class="text-sm font-black text-slate-900 dark:text-white">Explorar calificaciones</h3><p class="text-xs text-slate-500 dark:text-slate-400">Busca, filtra y consulta el desempeño del grupo.</p></div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">{{ count($inscripcionesTabla) }} alumnos</span>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">{{ count($materias) }} materias</span>
                        <span wire:loading.delay.flex wire:target="importarPlantillaCalificaciones"
                            class="items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/30 dark:text-indigo-300"
                            role="status" aria-live="polite" style="display: none">
                            <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-indigo-200 border-t-indigo-600 dark:border-indigo-900 dark:border-t-indigo-300" aria-hidden="true"></span>
                            Importando calificaciones…
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-2 xl:grid-cols-6">
                    <div class="xl:col-span-2">
                        <label for="calificaciones-busqueda" class="text-xs font-bold text-slate-700 dark:text-neutral-200">Buscar alumno o matrícula</label>

                        <input id="calificaciones-busqueda" type="search" wire:model.live.debounce.300ms="busqueda"
                            placeholder="Nombre o matrícula..."
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:ring-sky-950">

                        @if (!$esProfesor && filled($busqueda))
                            <a href="{{ route('misrutas.matricula.historial.pdf', [
                                'slug_nivel' => $slug_nivel,
                                'historial_completo' => 1,
                                'mostrar_archivados' => 1,
                                'search' => $busqueda,
                            ]) }}" target="_blank" rel="noopener"
                                class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-sky-700 hover:underline dark:text-sky-300">
                                <flux:icon.clock class="h-3.5 w-3.5" />
                                Consultar historial del alumno en todos los ciclos
                            </a>
                        @endif
                    </div>

                    <div>
                        <flux:select label="Estado en el ciclo" wire:model.live="filtro_estatus_historico">
                            <flux:select.option value="">Todos los estados</flux:select.option>
                            <flux:select.option value="activo">Activos en el ciclo</flux:select.option>
                            <flux:select.option value="reingreso">Reingresos</flux:select.option>
                            <flux:select.option value="promovido">Promovidos</flux:select.option>
                            <flux:select.option value="no_promovido">No promovidos</flux:select.option>
                            <flux:select.option value="egresado">Egresados</flux:select.option>
                            <flux:select.option value="baja_temporal">Baja temporal</flux:select.option>
                            <flux:select.option value="baja_definitiva">Baja definitiva</flux:select.option>
                            <flux:select.option value="trasladado">Trasladados</flux:select.option>
                            <flux:select.option value="suspendido">Suspendidos</flux:select.option>
                            <flux:select.option value="inactivo">Inactivos</flux:select.option>
                        </flux:select>
                    </div>

                    <div>
                        <flux:select label="Registros" wire:model.live="filtro_registros">
                            <flux:select.option value="todos">Todos los alumnos</flux:select.option>
                            <flux:select.option value="con_calificaciones">Con calificaciones</flux:select.option>
                            <flux:select.option value="sin_calificaciones">Sin calificaciones</flux:select.option>
                            @if ($this->esBachillerato)
                                <flux:select.option value="incluidos_generacion">Incluidos por generación</flux:select.option>
                                <flux:select.option value="contexto_pendiente">Pendientes de confirmar grupo</flux:select.option>
                            @endif
                        </flux:select>
                    </div>

                    <div>
                        <flux:select label="Vista académica" wire:model.live="filtro_estado">
                            <flux:select.option value="">Todos</flux:select.option>
                            <flux:select.option value="pendientes">Pendientes</flux:select.option>
                            <flux:select.option value="aprobados">Aprobados</flux:select.option>
                            <flux:select.option value="reprobados">Reprobados</flux:select.option>
                            <flux:select.option value="especiales">Valores especiales</flux:select.option>
                            <flux:select.option value="cambios">Con cambios</flux:select.option>
                        </flux:select>
                    </div>

                    <div>
                        <flux:select label="Ordenar promedios" wire:model.live="orden_promedio">
                            <flux:select.option value="">Normal</flux:select.option>
                            <flux:select.option value="mayor_menor">Mayor a menor</flux:select.option>
                            <flux:select.option value="menor_mayor">Menor a mayor</flux:select.option>
                        </flux:select>
                    </div>
                </div>
                @unless ($esProfesor)
                    {{-- Exportaciones masivas: no se usan selectores Livewire por alumno. --}}
                    <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-950/40 sm:p-5"
                        x-data="{
                            descargando: '', errorDescarga: '',
                            async descargarMasivo(event) {
                                const enlace = event.currentTarget;
                                if (this.descargando || enlace.getAttribute('aria-disabled') === 'true') return;
                                this.descargando = enlace.dataset.documento;
                                this.errorDescarga = '';
                                try {
                                    const respuesta = await fetch(enlace.href, {
                                        method: 'GET', credentials: 'same-origin',
                                        headers: { 'Accept': 'application/json' }
                                    });
                                    if (!respuesta.ok) {
                                        const tipo = respuesta.headers.get('content-type') || '';
                                        const mensaje = tipo.includes('application/json')
                                            ? (await respuesta.json()).message
                                            : '';
                                        throw new Error(mensaje || 'No fue posible generar el archivo (HTTP ' + respuesta.status + ').');
                                    }
                                    const cabecera = respuesta.headers.get('content-disposition') || '';
                                    const nombre = cabecera.match(/filename=[^;]+/i)?.[0]
                                        ?.slice(9).replaceAll(String.fromCharCode(34), '').trim() || 'documentos-academicos';
                                    const datos = await respuesta.blob();
                                    const url = URL.createObjectURL(datos);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = nombre;
                                    document.body.appendChild(a);
                                    a.click();
                                    a.remove();
                                    setTimeout(() => URL.revokeObjectURL(url), 60000);
                                } catch (error) {
                                    this.errorDescarga = error?.message || 'No fue posible preparar el documento.';
                                } finally {
                                    this.descargando = '';
                                }
                            }
                        }">
                        <div class="flex flex-wrap items-start justify-between gap-3 p-4 sm:p-0 sm:pb-5">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-300"><flux:icon.document-text class="h-5 w-5" /></span>
                                <div>
                                    <h4 class="text-sm font-black text-slate-900 dark:text-white">Exportación masiva de documentos</h4>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Un PDF o Word con todos los documentos del grupo y periodo elegidos, sin seleccionar alumnos uno por uno.</p>
                                </div>
                            </div>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">{{ count($inscripciones) }} alumnos en el contexto</span>
                        </div>

                        <div class="grid grid-cols-1 gap-4 px-4 pb-4 sm:px-0 sm:pb-0 xl:grid-cols-2">
                            @foreach ([
                                ['tipo' => 'boletas', 'nombre' => 'Boletas del grupo', 'descripcion' => 'Una boleta por alumno; conserva las materias, calificaciones y promedio del periodo.', 'clase' => 'sky'],
                                ['tipo' => 'reconocimientos', 'nombre' => 'Reconocimientos por lugar', 'descripcion' => 'Solo 1.º, 2.º y 3.º lugar, incluyendo empates, según el cálculo oficial.', 'clase' => 'amber'],
                            ] as $configuracion)
                                <div class="relative rounded-2xl border p-4 sm:p-5 {{ $configuracion['tipo'] === 'boletas' ? 'border-sky-100 bg-sky-50/40 dark:border-sky-900/40 dark:bg-sky-950/15' : 'border-amber-100 bg-amber-50/40 dark:border-amber-900/40 dark:bg-amber-950/15' }}">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-neutral-900 {{ $configuracion['tipo'] === 'boletas' ? 'text-sky-600' : 'text-amber-600' }}">
                                            @if ($configuracion['tipo'] === 'boletas')
                                                <flux:icon.document-arrow-down class="h-5 w-5" />
                                            @else
                                                <flux:icon.trophy class="h-5 w-5" />
                                            @endif
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <h5 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $configuracion['nombre'] }}</h5>
                                            <p class="mt-1 min-h-9 text-xs leading-relaxed text-slate-600 dark:text-neutral-400">{{ $configuracion['descripcion'] }}</p>
                                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                                @foreach (['pdf' => 'PDF', 'word' => 'Word'] as $formato => $etiqueta)
                                                    <a
                                                        href="{{ route('misrutas.calificaciones.documentos.masivos', array_filter([
                                                            'tipo' => $configuracion['tipo'],
                                                            'formato' => $formato,
                                                            'slug_nivel' => $slug_nivel,
                                                            'ciclo_escolar_id' => $ciclo_escolar_id,
                                                            'generacion_id' => $generacion_id,
                                                            'grado_id' => $grado_id,
                                                            'grupo_id' => $grupo_id,
                                                            'periodo_id' => $periodo_id,
                                                            'semestre_id' => $this->esBachillerato ? $semestre_id : null,
                                                        ])) }}"
                                                        data-documento="{{ $configuracion['tipo'] }}-{{ $formato }}"
                                                        x-on:click.prevent="descargarMasivo($event)"
                                                        x-bind:aria-disabled="descargando !== '' || {{ $this->puedeExportarPdf && count($inscripciones) > 0 ? 'false' : 'true' }}"
                                                        x-bind:class="descargando !== '' ? 'pointer-events-none opacity-50' : ''"
                                                        @if (!$this->puedeExportarPdf || count($inscripciones) === 0) aria-disabled="true" tabindex="-1" @endif
                                                        class="inline-flex min-h-10 items-center gap-2 rounded-xl border px-4 py-2 text-xs font-extrabold shadow-sm transition hover:-translate-y-px {{ $configuracion['tipo'] === 'boletas' ? 'border-sky-200 bg-white text-sky-700 hover:bg-sky-100 dark:border-sky-900 dark:bg-neutral-900 dark:text-sky-300' : 'border-amber-200 bg-white text-amber-800 hover:bg-amber-100 dark:border-amber-900 dark:bg-neutral-900 dark:text-amber-300' }}">
                                                        <span x-show="descargando !== '{{ $configuracion['tipo'] }}-{{ $formato }}'" class="inline-flex"><flux:icon.arrow-down-tray class="h-4 w-4" /></span>
                                                        <span x-show="descargando === '{{ $configuracion['tipo'] }}-{{ $formato }}'" style="display: none" class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                                        <span x-text="descargando === '{{ $configuracion['tipo'] }}-{{ $formato }}' ? 'Preparando…' : 'Descargar {{ $etiqueta }}'"></span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p x-show="descargando !== ''" style="display:none" role="status" aria-live="polite" class="mx-4 mt-3 text-xs font-semibold text-sky-700 dark:text-sky-300 sm:mx-0">Generando el archivo con los datos del periodo. La descarga comenzará al finalizar.</p>
                        <p x-show="errorDescarga !== ''" x-text="errorDescarga" style="display:none" role="alert" class="mx-4 mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 dark:border-rose-900 dark:bg-rose-950/20 dark:text-rose-200 sm:mx-0"></p>
                        @if (!$this->puedeExportarPdf)
                            <p class="mx-4 mb-4 text-xs font-semibold text-amber-700 dark:text-amber-300 sm:mx-0">Selecciona ciclo, generación, grado, grupo y {{ $this->esBachillerato ? 'parcial' : 'periodo' }} para exportar.</p>
                        @endif
                    </section>
                @endunless



            </div>

            <div class="relative">
                {{-- No se desmontan filas ni se interceptan clics: evita loaders permanentes o saltos de scroll. --}}
                <div wire:loading.delay.short wire:target="{{ $objetivosTabla }}"
                    style="display: none" aria-hidden="true"
                    class="pointer-events-none absolute inset-x-0 top-0 z-30 h-1 overflow-hidden bg-sky-100 dark:bg-sky-950">
                    <div class="h-full w-1/3 animate-pulse rounded-full bg-gradient-to-r from-emerald-400 via-sky-500 to-indigo-500"></div>
                </div>
                <div wire:loading.delay.short.flex wire:target="{{ $objetivosTabla }}"
                    style="display: none" role="status" aria-live="polite"
                    class="pointer-events-none absolute right-3 top-3 z-30 items-center gap-3 rounded-2xl border border-sky-200 bg-white/95 px-4 py-3 shadow-xl shadow-sky-900/10 backdrop-blur-sm dark:border-sky-900 dark:bg-neutral-900/95 sm:right-5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-50 dark:bg-sky-950/40">
                        <span class="h-5 w-5 animate-spin rounded-full border-[3px] border-sky-200 border-t-blue-600 dark:border-sky-900 dark:border-t-sky-300" aria-hidden="true"></span>
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-900 dark:text-white">Actualizando calificaciones</p>
                        <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Sin perder tu posición en la tabla</p>
                    </div>
                    <div class="ml-1 flex items-end gap-1" aria-hidden="true">
                        <span class="h-2 w-1 animate-pulse rounded-full bg-sky-300"></span>
                        <span class="h-4 w-1 animate-pulse rounded-full bg-blue-400"></span>
                        <span class="h-3 w-1 animate-pulse rounded-full bg-indigo-400"></span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="degradado sticky top-0 z-20 bg-neutral-50 dark:bg-neutral-950/60">
                        <tr class="text-neutral-600 dark:text-neutral-300">
                            <th class="px-4 py-3 text-left font-semibold text-white">#</th>
                            <th
                                class="sticky left-0 z-20 min-w-[140px] bg-sky-600 px-4 py-3 text-left font-semibold text-white">
                                MATRÍCULA</th>
                            <th
                                class="sticky left-[140px] z-20 min-w-[260px] bg-sky-700 px-4 py-3 text-left font-semibold text-white">
                                ALUMNO</th>
                            @foreach ($materias as $m)
                                <th @class([
                                    'min-w-[190px] px-4 py-2 text-center font-semibold text-white',
                                    'bg-violet-700/90' => $this->esBachillerato && !empty($m['extra']),
                                ])>
                                    <div class="text-white">{{ mb_strtoupper($m['materia']) }}</div>

                                    @if ($this->esBachillerato && !empty($m['extra']))
                                        <span
                                            class="mt-1 inline-flex rounded-full border border-violet-200/40 bg-white/15 px-2 py-0.5 text-[9px] font-black tracking-wide text-white">
                                            EXTRA · NO PROMEDIA
                                        </span>
                                    @endif

                                    <div
                                        class="mt-1 text-[11px] leading-tight font-normal text-neutral-200 dark:text-neutral-300">
                                        {{ $m['profesor'] ?? 'SIN PROFESOR ASIGNADO' }}</div>
                                </th>
                            @endforeach
                            <th class="min-w-[110px] px-4 py-3 text-center font-semibold text-white">PROMEDIO</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @forelse ($inscripcionesTabla as $index => $fila)
                            @php($insId = (int) $fila['inscripcion_id'])
                            @php($puedeEditarFila = $this->puedeEditarFilaCalificacion($insId))

                            @php($esResultadoBusqueda = $contextoBusquedaGlobal && (int) $alumnoBusquedaId === $insId)

                            <tr wire:key="fila-calificacion-{{ $insId }}"
                                @class([
                                    'transition hover:bg-neutral-50/70 dark:hover:bg-neutral-950/40',
                                    'bg-sky-50/90 ring-2 ring-inset ring-[#006492]/20 dark:bg-sky-500/10' => $esResultadoBusqueda,
                                ])
                                @if ($esResultadoBusqueda)
                                    x-data
                                    x-init="$nextTick(() => setTimeout(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' }), 350))"
                                @endif>

                                <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $index + 1 }}</td>
                                <td @class([
                                    'sticky left-0 z-10 min-w-[140px] px-4 py-3 font-medium text-neutral-900 dark:text-neutral-100',
                                    'bg-sky-50 dark:bg-sky-950' => $esResultadoBusqueda,
                                    'bg-white dark:bg-neutral-900' => ! $esResultadoBusqueda,
                                ])>
                                    {{ $fila['matricula'] }}</td>
                                <td @class([
                                    'sticky left-[140px] z-10 min-w-[260px] px-4 py-3 text-neutral-700 dark:text-neutral-200 uppercase',
                                    'bg-sky-50 dark:bg-sky-950' => $esResultadoBusqueda,
                                    'bg-white dark:bg-neutral-900' => ! $esResultadoBusqueda,
                                ])>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span>{{ $fila['alumno'] }}</span>

                                        @php($estatusHistorico = $fila['estatus_historico'] ?? 'activo')
                                        <span class="rounded-full border px-2 py-0.5 text-[9px] font-black normal-case tracking-wide {{ $this->claseEstatusHistorico($estatusHistorico) }}">
                                            {{ $this->etiquetaEstatusHistorico($estatusHistorico) }}
                                        </span>

                                        @if (!empty($fila['incluido_por_generacion']))
                                            <span class="rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-[9px] font-black normal-case tracking-wide text-violet-700 dark:border-violet-800 dark:bg-violet-950/30 dark:text-violet-300">
                                                {{ !empty($fila['asignacion_contexto_pendiente'])
                                                    ? 'Incluido por generación · sin inscripción histórica'
                                                    : 'Incluido por generación · contexto confirmado' }}
                                            </span>
                                        @endif

                                        @if ($esResultadoBusqueda)
                                            <span class="rounded-full bg-[#006492]/10 px-2 py-0.5 text-[9px] font-black normal-case tracking-wide text-[#006492] ring-1 ring-[#006492]/20 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20">
                                                Resultado de búsqueda
                                            </span>
                                        @endif
                                    </div>

                                    @if (!empty($fila['ubicacion_actual_distinta']) && !empty($fila['ubicacion_actual']))
                                        <p class="mt-1 text-[10px] font-semibold normal-case text-sky-700 dark:text-sky-300">
                                            Actualmente: {{ $fila['ubicacion_actual'] }}
                                        </p>
                                    @endif
                                </td>

                                @foreach ($materias as $m)
                                    @php($asigId = (int) $m['id'])
                                    <td class="px-4 py-3 text-center">
                                        <div class="mx-auto w-28">
                                            <input
                                                wire:key="input-calificacion-{{ $insId }}-{{ $asigId }}"
                                                id="cal-{{ $insId }}-{{ $asigId }}" type="text"
                                                maxlength="{{ $this->esBachillerato ? 2 : 5 }}"
                                                inputmode="{{ $this->esBachillerato ? 'numeric' : 'text' }}"
                                                wire:model.blur="calificaciones.{{ $insId }}.{{ $asigId }}"
                                                @focus="$event.target.select()"
                                                @keydown.enter.prevent="move({{ $insId }}, {{ $asigId }}, $event.shiftKey ? 'up' : 'down')"
                                                @keydown.tab.prevent="move({{ $insId }}, {{ $asigId }}, $event.shiftKey ? 'left' : 'right')"
                                                @keydown.arrow-down.prevent="move({{ $insId }}, {{ $asigId }}, 'down')"
                                                @keydown.arrow-up.prevent="move({{ $insId }}, {{ $asigId }}, 'up')"
                                                @keydown.arrow-right.prevent="move({{ $insId }}, {{ $asigId }}, 'right')"
                                                @keydown.arrow-left.prevent="move({{ $insId }}, {{ $asigId }}, 'left')"
                                                class="{{ $this->claseInputCalificacion($insId, $asigId) }} {{ !$puedeEditarFila ? 'cursor-not-allowed opacity-70' : '' }}"
                                                @disabled(!$puedeEditarFila)
                                                title="{{ !$puedeEditarFila
                                                    ? (!empty($fila['asignacion_contexto_pendiente'])
                                                        ? 'Habilita la corrección para confirmar el primer contexto académico del alumno dentro de su generación.'
                                                        : 'Habilita la corrección histórica para editar.')
                                                    : '' }}"
                                                placeholder="{{ $this->esBachillerato ? 'Entero 0-10 / AC' : '0-10 / AC' }}" />

                                            @error('calificaciones.' . $insId . '.' . $asigId)
                                                <div class="mt-1 text-[11px] leading-tight text-red-600 dark:text-red-300">
                                                    {{ $message }}</div>
                                            @enderror


                                        </div>
                                    </td>
                                @endforeach

                                <td class="px-4 py-3 text-center font-semibold text-neutral-900 dark:text-neutral-100">
                                    {{ $this->promedioAlumnoTabla($insId) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 4 + count($materias) }}" class="px-6 py-10">
                                    <div
                                        class="rounded-2xl border border-dashed border-neutral-200 p-6 text-center dark:border-neutral-800">
                                        <div class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">No
                                            hay datos para mostrar</div>
                                        <div class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $mensajeContexto !== ''
                                                ? $mensajeContexto
                                                : ($this->esBachillerato
                                                    ? 'Selecciona generación, grado, semestre, grupo y parcial para cargar alumnos y materias.'
                                                    : 'Selecciona generación, grado, grupo y periodo para cargar alumnos y materias.') }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="border-t border-neutral-200 p-5 dark:border-neutral-800">
                @error('calificaciones')
                    <div
                        class="mb-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
                        {{ $message }}</div>
                @enderror

                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="w-full space-y-3 md:w-2/3">
                        <div>
                            <div class="flex items-center justify-between gap-3 text-xs text-neutral-600 dark:text-neutral-300">
                                <span>
                                    {{ $this->esBachillerato ? 'Captura académica oficial' : 'Calificaciones introducidas' }}:
                                    {{ $this->celdasCapturadas }} de {{ $this->totalCeldas }}
                                    ({{ $this->porcentajeCaptura }}%)
                                </span>
                                @if ($this->esBachillerato)
                                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-sky-700 dark:bg-sky-950/30 dark:text-sky-300">
                                        Calificaciones enteras · 0 a 10
                                    </span>
                                @endif
                            </div>
                            <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-950">
                                <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-indigo-500"
                                    style="width: {{ $this->porcentajeCaptura }}%"></div>
                            </div>
                        </div>

                        @if ($this->esBachillerato && $this->totalCeldasExtra > 0)
                            <div class="rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2.5 dark:border-amber-900/40 dark:bg-amber-950/20">
                                <div class="flex items-center justify-between gap-3 text-xs text-amber-800 dark:text-amber-200">
                                    <span>
                                        Materias extra informativas: {{ $this->celdasExtraCapturadas }} de
                                        {{ $this->totalCeldasExtra }} ({{ $this->porcentajeCapturaExtra }}%)
                                    </span>
                                    <span class="font-black">No promedian</span>
                                </div>
                                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-amber-100 dark:bg-amber-950/50">
                                    <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500"
                                        style="width: {{ $this->porcentajeCapturaExtra }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col items-end gap-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                class="rounded-full px-4 py-2 text-xs font-semibold {{ $this->claseEstadoCambios }}">{{ $this->mensajeCambios }}</span>
                            @if ($hayCambios)
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Revisa y guarda los
                                    cambios realizados.</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            @if (! $this->esProfesorAutenticado && $this->mostrarBotonBitacora)
                                <button type="button" wire:click="abrirModalBitacora" wire:loading.attr="disabled"
                                    wire:target="abrirModalBitacora"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-fuchsia-200 bg-white px-5 py-3 text-sm font-semibold text-fuchsia-700 shadow-sm transition hover:bg-fuchsia-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-fuchsia-900/40 dark:bg-neutral-900 dark:text-fuchsia-300 dark:hover:bg-fuchsia-950/20">
                                    <span wire:loading.remove wire:target="abrirModalBitacora"
                                        class="inline-flex items-center gap-2">
                                        <flux:icon.clock class="h-4 w-4" />Bitácora
                                    </span>
                                    <span wire:loading wire:target="abrirModalBitacora"
                                        class="inline-flex items-center gap-2"><span
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-fuchsia-300 border-t-fuchsia-700"></span>Abriendo...</span>
                                </button>
                            @endif

                            @unless ($this->esProfesorAutenticado)
                            <button type="button" wire:click="exportarCalificaciones" wire:loading.attr="disabled"
                                wire:target="exportarCalificaciones"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-white px-5 py-3 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-emerald-900/40 dark:bg-neutral-900 dark:text-emerald-300 dark:hover:bg-emerald-950/20">
                                <span wire:loading.remove wire:target="exportarCalificaciones"
                                    class="inline-flex items-center gap-2"><flux:icon.arrow-down-tray
                                        class="h-4 w-4" />Exportar</span>
                                <span wire:loading wire:target="exportarCalificaciones"
                                    class="inline-flex items-center gap-2"><span
                                        class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-300 border-t-emerald-700"></span>Exportando...</span>
                            </button>

                            <button type="button" @if (!$this->puedeExportarPdf) disabled @endif
                                x-on:click="window.open('{{ route(
                                    'misrutas.calificaciones.pdf',
                                    array_filter([
                                        'slug_nivel' => $slug_nivel,
                                        'ciclo_escolar_id' => $ciclo_escolar_id,
                                        'filtro_estatus_historico' => $filtro_estatus_historico ?: null,
                                        'filtro_registros' => $filtro_registros !== 'todos' ? $filtro_registros : null,
                                        'generacion_id' => $generacion_id,
                                        'grado_id' => $grado_id,
                                        'grupo_id' => $grupo_id,
                                        'periodo_id' => $periodo_id,
                                        'busqueda' => $busqueda ?: null,
                                        'semestre_id' => $this->esBachillerato ? $semestre_id : null,
                                        'parcial_bachillerato_id' => $this->esBachillerato ? $parcial_bachillerato_id : null,
                                        'periodo_basica_id' => !$this->esBachillerato ? $periodo_basica_id : null,
                                    ]),
                                ) }}', '_blank')"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-white px-5 py-3 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-rose-900/40 dark:bg-neutral-900 dark:text-rose-300 dark:hover:bg-rose-950/20">
                                <flux:icon.document-arrow-down class="h-4 w-4" />
                                PDF
                            </button>

                            @endunless

                            @if ($this->esProfesorAutenticado)
                                <button type="button" wire:click="guardarBorrador" wire:loading.attr="disabled" wire:target="guardarBorrador"
                                    @if (!$this->puedeGuardar || !$hayCambios) disabled @endif
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-sky-200 bg-white px-5 py-3 text-sm font-black text-sky-700 shadow-sm transition hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-sky-900/50 dark:bg-neutral-900 dark:text-sky-300">
                                    <flux:icon.cloud-arrow-up class="size-4" />
                                    <span wire:loading.remove wire:target="guardarBorrador">Guardar borrador</span>
                                    <span wire:loading wire:target="guardarBorrador">Guardando...</span>
                                </button>
                            @endif

                            <button type="button" wire:click="abrirRevisionGuardado"
                                @if (!$this->puedeGuardar) disabled @endif class="{{ $this->claseGuardar }}">
                                {{ $this->esProfesorAutenticado ? 'Revisar y entregar' : 'Revisar y guardar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Gráficas --}}
    @if (count($inscripciones) > 0 && count($materias) > 0)
        <div wire:key="graficas-calificaciones-wrapper-{{ md5(
            json_encode([
                'nivel' => $nivel_id,
                'ciclo' => $ciclo_escolar_id,
                'generacion' => $generacion_id,
                'grado' => $grado_id,
                'grupo' => $grupo_id,
                'semestre' => $semestre_id,
                'periodo' => $periodo_id,
                'parcial' => $parcial_bachillerato_id,
                'periodo_basica' => $periodo_basica_id,
                'busqueda' => $busqueda,
                'filtro_estado' => $filtro_estado,
                'filtro_registros' => $filtro_registros,
                'filtro_estatus_historico' => $filtro_estatus_historico,
                'orden_promedio' => $orden_promedio ?? '',
                'datos' => $graficasCalificaciones,
            ]),
        ) }}"
            x-data="graficasCalificacionesPro(@js($graficasCalificaciones))" x-init="iniciar()"
            x-on:recargar-graficas-calificaciones.window="actualizar(@js($graficasCalificaciones))"
            class="mt-6 overflow-hidden rounded-[28px] border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">

            <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-600"></div>

            <div class="p-5 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                            <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                            Análisis visual
                        </div>

                        <h3 class="mt-3 text-2xl font-black tracking-tight text-neutral-900 dark:text-white">
                            Gráficas de calificaciones
                        </h3>

                        <p class="mt-1 max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">
                            Visualización automática de promedios por alumno, por materia y rendimiento global del
                            grupo.
                        </p>
                        @if (($graficasCalificaciones['global']['total_numericas'] ?? 0) > 0)
                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <button type="button" x-on:click="exportarImagen('todas', 'copiar')"
                                    x-bind:disabled="Boolean(exportandoGrafica)"
                                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-wait disabled:opacity-60">
                                    <flux:icon.document-duplicate class="size-4" />
                                    <span x-text="exportandoGrafica === 'todas' ? 'Preparando imagen…' : 'Copiar las 3 gráficas'"></span>
                                </button>
                                <button type="button" x-on:click="exportarImagen('todas', 'descargar')"
                                    x-bind:disabled="Boolean(exportandoGrafica)"
                                    class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3.5 py-2 text-xs font-bold text-sky-700 transition hover:bg-sky-100 disabled:cursor-wait disabled:opacity-60 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300">
                                    <flux:icon.arrow-down-tray class="size-4" />
                                    PNG completo
                                </button>
                                <span class="text-[11px] text-slate-500 dark:text-neutral-400">Con nombres y valores completos, sin recortes.</span>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div
                                class="text-[11px] font-bold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                Promedio global
                            </div>

                            <div class="mt-1 text-2xl font-black text-neutral-900 dark:text-white">
                                {{ isset($graficasCalificaciones['global']['promedio'])
                                    ? number_format((float) $graficasCalificaciones['global']['promedio'], 1)
                                    : '—' }}
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div
                                class="text-[11px] font-bold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                Aprobación
                            </div>

                            <div class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-300">
                                {{ $graficasCalificaciones['global']['porcentaje_aprobacion'] ?? 0 }}%
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div
                                class="text-[11px] font-bold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                Aprobadas
                            </div>

                            <div class="mt-1 text-2xl font-black text-sky-600 dark:text-sky-300">
                                {{ $graficasCalificaciones['global']['aprobadas'] ?? 0 }}
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div
                                class="text-[11px] font-bold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                Reprobadas
                            </div>

                            <div class="mt-1 text-2xl font-black text-rose-600 dark:text-rose-300">
                                {{ $graficasCalificaciones['global']['reprobadas'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                @if (($graficasCalificaciones['global']['total_numericas'] ?? 0) > 0)
                    <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-3">
                        <div
                            class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/40">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                        Promedio por alumno
                                    </h4>
                                    <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                        Promedio individual de cada estudiante.
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" x-on:click="exportarImagen('alumnos', 'copiar')"
                                        x-bind:disabled="Boolean(exportandoGrafica)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-[11px] font-bold text-sky-700 transition hover:bg-sky-100 disabled:cursor-wait disabled:opacity-60 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300"
                                        aria-label="Copiar imagen completa: Promedio por alumno">
                                        <flux:icon.document-duplicate class="size-3.5" />
                                        Copiar
                                    </button>
                                    <button type="button" x-on:click="exportarImagen('alumnos', 'descargar')"
                                        x-bind:disabled="Boolean(exportandoGrafica)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-neutral-600 transition hover:bg-neutral-100 disabled:cursor-wait disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300"
                                        aria-label="Descargar PNG completo: Promedio por alumno">
                                        <flux:icon.arrow-down-tray class="size-3.5" />
                                        PNG
                                    </button>
                                </div>
                            </div>

                            <div wire:ignore>
                                <div id="graficaCalificacionesAlumnos" class="min-h-[330px]"></div>
                            </div>
                        </div>

                        <div
                            class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/40">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                        Promedio por materia
                                    </h4>
                                    <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                        Comparativo del rendimiento por asignatura.
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" x-on:click="exportarImagen('materias', 'copiar')"
                                        x-bind:disabled="Boolean(exportandoGrafica)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-[11px] font-bold text-sky-700 transition hover:bg-sky-100 disabled:cursor-wait disabled:opacity-60 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300"
                                        aria-label="Copiar imagen completa: Promedio por materia">
                                        <flux:icon.document-duplicate class="size-3.5" />
                                        Copiar
                                    </button>
                                    <button type="button" x-on:click="exportarImagen('materias', 'descargar')"
                                        x-bind:disabled="Boolean(exportandoGrafica)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-neutral-600 transition hover:bg-neutral-100 disabled:cursor-wait disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300"
                                        aria-label="Descargar PNG completo: Promedio por materia">
                                        <flux:icon.arrow-down-tray class="size-3.5" />
                                        PNG
                                    </button>
                                </div>
                            </div>

                            <div wire:ignore>
                                <div id="graficaCalificacionesMaterias" class="min-h-[330px]"></div>
                            </div>
                        </div>

                        <div
                            class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/40">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                        Rendimiento global
                                    </h4>
                                    <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                        Promedio general del grupo seleccionado.
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" x-on:click="exportarImagen('global', 'copiar')"
                                        x-bind:disabled="Boolean(exportandoGrafica)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-[11px] font-bold text-sky-700 transition hover:bg-sky-100 disabled:cursor-wait disabled:opacity-60 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300"
                                        aria-label="Copiar imagen completa: Rendimiento global">
                                        <flux:icon.document-duplicate class="size-3.5" />
                                        Copiar
                                    </button>
                                    <button type="button" x-on:click="exportarImagen('global', 'descargar')"
                                        x-bind:disabled="Boolean(exportandoGrafica)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-neutral-600 transition hover:bg-neutral-100 disabled:cursor-wait disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300"
                                        aria-label="Descargar PNG completo: Rendimiento global">
                                        <flux:icon.arrow-down-tray class="size-3.5" />
                                        PNG
                                    </button>
                                </div>
                            </div>

                            <div wire:ignore>
                                <div id="graficaCalificacionesGlobal" class="min-h-[330px]"></div>
                            </div>
                        </div>
                    </div>
                @else
                    <div
                        class="mt-6 rounded-2xl border border-dashed border-neutral-300 p-6 text-center dark:border-neutral-700">
                        <div class="text-sm font-bold text-neutral-800 dark:text-neutral-100">
                            Todavía no hay calificaciones numéricas para graficar
                        </div>

                        <div class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Captura calificaciones de 0 a 10 para generar las gráficas.
                        </div>
                    </div>
                @endif
            </div>

            {{-- La notificación no cambia la altura del documento ni la posición de la tabla. --}}
            <div x-cloak x-show="notificacionExportacion" x-transition.opacity.duration.200ms
                class="fixed bottom-6 right-6 z-[200] w-[min(25rem,calc(100vw-3rem))] rounded-2xl border bg-white px-4 py-3 shadow-xl dark:bg-neutral-900"
                x-bind:class="avisoExportacionAlternativo ? 'border-amber-300 dark:border-amber-900' : 'border-emerald-300 dark:border-emerald-900'"
                role="status" aria-live="polite">
                <div class="flex items-start gap-3">
                    <flux:icon.photo class="mt-0.5 size-5 shrink-0 text-sky-600" />
                    <span class="flex-1 text-sm font-semibold text-slate-800 dark:text-neutral-100" x-text="notificacionExportacion"></span>
                    <button type="button" class="shrink-0 rounded p-1 text-slate-500 hover:bg-slate-100 dark:hover:bg-neutral-800"
                        x-on:click="notificacionExportacion = ''" aria-label="Cerrar aviso de exportación">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal para habilitar corrección histórica --}}
    <div x-data="{ show: @entangle('mostrarModalCorreccionHistorica').live }" x-cloak>
        <div x-show="show" x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm"
            @keydown.escape.window="$wire.cancelarCorreccionHistorica()" @click.self="$wire.cancelarCorreccionHistorica()">
            <div x-show="show" x-transition
                class="relative w-full max-w-2xl overflow-hidden rounded-[28px] border border-white/10 bg-white shadow-2xl dark:bg-neutral-900">
                <div class="h-1 w-full bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400"></div>
                <div class="space-y-5 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                <flux:icon.shield-check class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-neutral-900 dark:text-white">
                                    {{ $this->esConsultaHistorica ? 'Habilitar corrección histórica' : 'Confirmar inclusión por generación' }}
                                </h3>
                                <p class="mt-1 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                    {{ $this->esConsultaHistorica
                                        ? 'La sesión permite editar e importar calificaciones del ciclo cerrado.'
                                        : 'La sesión permite confirmar el primer grupo histórico de los alumnos incluidos por generación.' }}
                                    Todos los cambios conservarán valor anterior, valor nuevo, usuario, fecha, IP y motivo.
                                </p>
                            </div>
                        </div>
                        <button type="button" wire:click="cancelarCorreccionHistorica"
                            class="rounded-xl p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 dark:hover:bg-neutral-800 dark:hover:text-white">
                            <flux:icon.x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-black text-neutral-700 dark:text-neutral-200">Motivo</label>
                        <select wire:model="motivoCorreccionCatalogo"
                            class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 outline-none focus:ring-2 focus:ring-amber-300 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                            <option value="">Selecciona un motivo</option>
                            @foreach ($this->motivosCorreccionHistorica as $valorMotivo => $etiquetaMotivo)
                                <option value="{{ $valorMotivo }}">{{ $etiquetaMotivo }}</option>
                            @endforeach
                        </select>
                        @error('motivoCorreccionCatalogo')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-black text-neutral-700 dark:text-neutral-200">Descripción obligatoria</label>
                        <textarea wire:model="detalleCorreccionHistorica" rows="5"
                            class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 outline-none focus:ring-2 focus:ring-amber-300 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white"
                            placeholder="Explica qué se corregirá, por qué y qué evidencia respalda el cambio."></textarea>
                        @error('detalleCorreccionHistorica')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs font-semibold leading-5 text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-200">
                        Esta acción no mueve al alumno de su grado actual ni altera su fecha real de inscripción. Las calificaciones quedarán vinculadas al ciclo, generación, semestre, grupo, parcial y materia seleccionados.
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="cancelarCorreccionHistorica"
                            class="rounded-2xl border border-neutral-200 px-5 py-3 text-sm font-bold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">Cancelar</button>
                        <button type="button" wire:click="habilitarCorreccionHistorica" wire:loading.attr="disabled" wire:target="habilitarCorreccionHistorica"
                            class="rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-amber-500/20 hover:opacity-95 disabled:opacity-60">
                            <span wire:loading.remove wire:target="habilitarCorreccionHistorica">Habilitar sesión</span>
                            <span wire:loading wire:target="habilitarCorreccionHistorica">Habilitando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal revisión --}}
    <div x-data="{ show: @entangle('mostrarModalRevision').live }" x-cloak>
        <div x-show="show" x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
            @keydown.escape.window="$wire.cerrarRevisionGuardado()" @click.self="$wire.cerrarRevisionGuardado()">
            <div x-show="show" x-transition
                class="relative w-full max-w-6xl overflow-hidden rounded-[28px] border border-white/10 bg-white shadow-2xl dark:bg-neutral-900">
                <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-600"></div>
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-black text-neutral-900 dark:text-white">{{ $this->esProfesorAutenticado ? 'Confirmación de entrega' : 'Revisión antes de guardar' }}</h3>
                            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $this->esProfesorAutenticado ? 'Revisa la captura, acepta la declaración y confirma con tu contraseña.' : 'Verifica los cambios detectados antes de guardar las calificaciones.' }}</p>
                        </div>
                        <button type="button" wire:click="cerrarRevisionGuardado"
                            class="rounded-xl p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 dark:hover:bg-neutral-800 dark:hover:text-white"><flux:icon.x-mark
                                class="h-5 w-5" /></button>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-6">
                        <div class="rounded-2xl bg-sky-50 p-4 dark:bg-sky-950/30">
                            <p class="text-xs font-bold uppercase text-sky-700 dark:text-sky-300">Cambios</p>
                            <p class="text-2xl font-black text-sky-900 dark:text-sky-100">
                                {{ $resumenRevision['total'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-950/30">
                            <p class="text-xs font-bold uppercase text-emerald-700 dark:text-emerald-300">Numéricas</p>
                            <p class="text-2xl font-black text-emerald-900 dark:text-emerald-100">
                                {{ $resumenRevision['numericas'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl bg-violet-50 p-4 dark:bg-violet-950/30">
                            <p class="text-xs font-bold uppercase text-violet-700 dark:text-violet-300">Especiales</p>
                            <p class="text-2xl font-black text-violet-900 dark:text-violet-100">
                                {{ $resumenRevision['especiales'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl bg-rose-50 p-4 dark:bg-rose-950/30">
                            <p class="text-xs font-bold uppercase text-rose-700 dark:text-rose-300">Reprobatorias</p>
                            <p class="text-2xl font-black text-rose-900 dark:text-rose-100">
                                {{ $resumenRevision['reprobatorias'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl bg-indigo-50 p-4 dark:bg-indigo-950/30">
                            <p class="text-xs font-bold uppercase text-indigo-700 dark:text-indigo-300">Alumnos</p>
                            <p class="text-2xl font-black text-indigo-900 dark:text-indigo-100">
                                {{ $resumenRevision['alumnos_afectados'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl bg-amber-50 p-4 dark:bg-amber-950/30">
                            <p class="text-xs font-bold uppercase text-amber-700 dark:text-amber-300">Materias</p>
                            <p class="text-2xl font-black text-amber-900 dark:text-amber-100">
                                {{ $resumenRevision['materias_afectadas'] ?? 0 }}</p>
                        </div>
                    </div>

                    @unless ($this->esProfesorAutenticado)
                    <div class="mt-5">
                        <label class="text-xs font-bold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            {{ $this->esConsultaHistorica ? 'Motivo de la corrección histórica' : 'Motivo del guardado' }}
                        </label>

                        @if ($this->esConsultaHistorica)
                            <div class="mt-1 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                {{ $this->motivoCorreccionCompleto }}
                            </div>
                        @else
                            <textarea wire:model.live="motivo_guardado" rows="3"
                                class="mt-1 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 outline-none focus:ring-2 focus:ring-sky-300 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white"
                                placeholder="Ejemplo: Captura de calificaciones del periodo, corrección administrativa, revisión final..."></textarea>
                        @endif
                    </div>
                    @endunless

                    @if ($this->esProfesorAutenticado)
                        <div class="mt-5 rounded-[22px] border border-sky-200 bg-sky-50 p-5 dark:border-sky-900/50 dark:bg-sky-950/20">
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div><p class="text-[10px] font-black uppercase tracking-wider text-sky-600">Usuario ID</p><p class="mt-1 text-sm font-black text-sky-950 dark:text-sky-100">#{{ auth()->id() }}</p></div>
                                <div><p class="text-[10px] font-black uppercase tracking-wider text-sky-600">Nombre completo</p><p class="mt-1 text-sm font-black text-sky-950 dark:text-sky-100">{{ auth()->user()->name }}</p></div>
                                <div><p class="text-[10px] font-black uppercase tracking-wider text-sky-600">CURP</p><p class="mt-1 text-sm font-black text-sky-950 dark:text-sky-100">{{ auth()->user()->persona?->curp }}</p></div>
                                <div><p class="text-[10px] font-black uppercase tracking-wider text-sky-600">Usuario institucional</p><p class="mt-1 break-all text-sm font-black text-sky-950 dark:text-sky-100">{{ auth()->user()->email }}</p></div>
                            </div>

                            <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl border border-sky-200 bg-white p-4 dark:border-sky-900/60 dark:bg-neutral-900">
                                <input type="checkbox" wire:model="acepta_conformidad" class="mt-1 size-5 rounded border-sky-300 text-[#006492] focus:ring-[#006492]">
                                <span class="text-sm font-semibold leading-6 text-slate-700 dark:text-slate-200">{{ $this->declaracionConformidad }}</span>
                            </label>
                            @error('acepta_conformidad')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror

                            <label class="mt-4 block">
                                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Contraseña actual</span>
                                <input type="password" wire:model="password_confirmacion" autocomplete="current-password" class="mt-2 h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm outline-none focus:border-[#006492] focus:ring-2 focus:ring-[#006492]/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white" placeholder="Confirma tu identidad para realizar la entrega">
                            </label>
                            @error('password_confirmacion')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror

                            @unless ($this->capturaCompleta)
                                <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold leading-5 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">La entrega definitiva requiere todas las calificaciones. Puedes cerrar este cuadro y usar “Guardar borrador” para continuar después.</p>
                            @endunless
                        </div>
                    @endif

                    <div
                        class="mt-5 max-h-[45vh] overflow-auto rounded-2xl border border-neutral-200 dark:border-neutral-800">
                        <table class="min-w-full text-sm">
                            <thead class="sticky top-0 bg-neutral-50 dark:bg-neutral-950">
                                <tr>
                                    <th class="px-4 py-3 text-left font-bold text-neutral-500">Alumno</th>
                                    <th class="px-4 py-3 text-left font-bold text-neutral-500">Materia</th>
                                    <th class="px-4 py-3 text-center font-bold text-neutral-500">Antes</th>
                                    <th class="px-4 py-3 text-center font-bold text-neutral-500">Ahora</th>
                                    <th class="px-4 py-3 text-center font-bold text-neutral-500">Tipo</th>
                                    <th class="px-4 py-3 text-left font-bold text-neutral-500">Observación</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                                @forelse (($resumenRevision['cambios'] ?? []) as $cambio)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-neutral-900 dark:text-white">
                                                {{ $cambio['alumno'] }}</div>
                                            <div class="text-xs text-neutral-500">{{ $cambio['matricula'] }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">
                                            {{ $cambio['materia'] }}</td>
                                        <td
                                            class="px-4 py-3 text-center font-bold text-neutral-700 dark:text-neutral-200">
                                            {{ $cambio['anterior'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center font-bold text-neutral-900 dark:text-white">
                                            {{ $cambio['nuevo'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center"><span
                                                class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:bg-sky-950/30 dark:text-sky-300">{{ mb_strtoupper($cambio['tipo']) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                            {{ $cambio['observacion'] ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-sm text-neutral-500">No
                                            hay cambios pendientes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="cerrarRevisionGuardado"
                            class="rounded-2xl border border-neutral-200 px-5 py-3 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">Cancelar</button>
                        <button type="button" wire:click="guardarCalificaciones" wire:loading.attr="disabled"
                            wire:target="guardarCalificaciones" @if ($this->esProfesorAutenticado && !$this->capturaCompleta) disabled @endif
                            class="rounded-2xl bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-sky-500/20 hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="guardarCalificaciones">{{ $this->esProfesorAutenticado ? 'Confirmar, entregar y generar PDF' : 'Confirmar y guardar' }}</span>
                            <span wire:loading wire:target="guardarCalificaciones">{{ $this->esProfesorAutenticado ? 'Generando comprobante...' : 'Guardando...' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @unless ($this->esProfesorAutenticado)
    {{-- Modal bitácora --}}
    <div x-data="{ show: @entangle('mostrarModalBitacora').live }" x-cloak>
        <div x-show="show" x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
            @keydown.escape.window="$wire.cerrarModalBitacora()" @click.self="$wire.cerrarModalBitacora()">
            <div x-show="show" x-transition
                class="relative w-full max-w-7xl overflow-hidden rounded-[28px] border border-white/10 bg-white shadow-2xl dark:bg-neutral-900">
                <div class="h-1.5 w-full bg-gradient-to-r from-fuchsia-500 via-sky-500 to-indigo-600"></div>
                <div class="space-y-5 p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-fuchsia-50 text-fuchsia-600 dark:bg-fuchsia-950/30 dark:text-fuchsia-300">
                                <flux:icon.clock class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Bitácora de calificaciones
                                </h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Historial de movimientos del
                                    contexto seleccionado.</p>
                            </div>
                        </div>
                        <button type="button" wire:click="cerrarModalBitacora"
                            class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-neutral-800 dark:hover:text-slate-200"><flux:icon.x-mark
                                class="h-5 w-5" /></button>
                    </div>

                    <div class="relative min-h-[260px]">
                        <div wire:loading.flex wire:target="abrirModalBitacora"
                            class="absolute inset-0 z-20 hidden items-center justify-center rounded-3xl border border-white/60 bg-white/75 backdrop-blur-md dark:border-white/10 dark:bg-neutral-900/75">
                            <div
                                class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white px-5 py-4 shadow-lg dark:border-neutral-800 dark:bg-neutral-950">
                                <div
                                    class="h-5 w-5 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-white">
                                </div>
                                <div class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">Cargando
                                    bitácora…</div>
                            </div>
                        </div>

                        <div wire:loading.remove wire:target="abrirModalBitacora">
                            <livewire:accion.bitacora-calificaciones :nivel_id="$nivel_id" :ciclo_escolar_id="$ciclo_escolar_id" :grado_id="$grado_id"
                                :grupo_id="$grupo_id" :semestre_id="$semestre_id" :generacion_id="$generacion_id" :periodo_id="$periodo_id"
                                :esBachillerato="$this->esBachillerato" :key="'bitacora-calificaciones-' .
                                    md5(
                                        json_encode([
                                            'nivel' => $nivel_id,
                                            'ciclo' => $ciclo_escolar_id,
                                            'grado' => $grado_id,
                                            'grupo' => $grupo_id,
                                            'semestre' => $semestre_id,
                                            'generacion' => $generacion_id,
                                            'periodo' => $periodo_id,
                                            'parcial' => $parcial_bachillerato_id,
                                            'periodo_basica' => $periodo_basica_id,
                                            'modal' => $mostrarModalBitacora,
                                        ]),
                                    )" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <flux:button type="button" variant="ghost" wire:click="cerrarModalBitacora">Cerrar
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endunless

    @once



        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script src="{{ asset('js/calificaciones-graficas-export.js') }}"></script>
    @endonce

    <script>
        function graficasCalificacionesPro(datosIniciales) {
            return {
                datos: datosIniciales || {},

                graficaAlumnos: null,
                graficaMaterias: null,
                graficaGlobal: null,
                timer: null,
                exportandoGrafica: null,
                notificacionExportacion: '',
                avisoExportacionAlternativo: false,
                timerAvisoExportacion: null,

                async exportarImagen(tipo, accion) {
                    if (this.exportandoGrafica) return;
                    this.exportandoGrafica = tipo;
                    try {
                        const exportador = window.MoctezumaGraficasExporter;
                        if (!exportador || typeof exportador[accion] !== 'function') {
                            throw new Error('No se pudo cargar el exportador de imágenes. Recarga la página.');
                        }
                        const resultado = await exportador[accion](this.datos, tipo);
                        this.notificarExportacion(resultado.mensaje, Boolean(resultado.alternativa));
                    } catch (error) {
                        this.notificarExportacion(error?.message || 'No se pudo generar la imagen.', true);
                    } finally {
                        this.exportandoGrafica = null;
                    }
                },

                notificarExportacion(mensaje, alternativo = false) {
                    clearTimeout(this.timerAvisoExportacion);
                    this.notificacionExportacion = mensaje;
                    this.avisoExportacionAlternativo = alternativo;
                    this.timerAvisoExportacion = setTimeout(() => {
                        this.notificacionExportacion = '';
                    }, 7500);
                },

                iniciar() {
                    this.programarCarga();
                },

                actualizar(datosActualizados) {
                    this.datos = datosActualizados || {};
                    this.programarCarga();
                },

                recargar() {
                    this.programarCarga();
                },

                programarCarga() {
                    clearTimeout(this.timer);

                    this.timer = setTimeout(() => {
                        this.$nextTick(() => {
                            this.esperarApexCharts(() => {
                                this.crearTodas();
                            });
                        });
                    }, 500);
                },

                crearTodas() {
                    this.destruirGraficas();

                    const totalNumericas = Number(this.datos?.global?.total_numericas || 0);

                    if (totalNumericas <= 0) {
                        return;
                    }

                    this.crearGraficaAlumnos();
                    this.crearGraficaMaterias();
                    this.crearGraficaGlobal();
                },

                esperarApexCharts(callback) {
                    if (typeof ApexCharts !== 'undefined') {
                        callback();
                        return;
                    }

                    setTimeout(() => {
                        this.esperarApexCharts(callback);
                    }, 120);
                },

                destruirGraficas() {
                    if (this.graficaAlumnos) {
                        this.graficaAlumnos.destroy();
                        this.graficaAlumnos = null;
                    }

                    if (this.graficaMaterias) {
                        this.graficaMaterias.destroy();
                        this.graficaMaterias = null;
                    }

                    if (this.graficaGlobal) {
                        this.graficaGlobal.destroy();
                        this.graficaGlobal = null;
                    }

                    this.limpiarContenedor('#graficaCalificacionesAlumnos');
                    this.limpiarContenedor('#graficaCalificacionesMaterias');
                    this.limpiarContenedor('#graficaCalificacionesGlobal');
                },

                limpiarContenedor(selector) {
                    const elemento = document.querySelector(selector);

                    if (elemento) {
                        elemento.innerHTML = '';
                    }
                },

                crearGraficaAlumnos() {
                    const elemento = document.querySelector('#graficaCalificacionesAlumnos');

                    if (!elemento || !this.datos?.alumnos) {
                        return;
                    }

                    const labels = this.datos.alumnos.labels || [];
                    const series = this.datos.alumnos.series || [];

                    if (labels.length === 0 || series.length === 0) {
                        return;
                    }

                    this.graficaAlumnos = new ApexCharts(
                        elemento,
                        this.opcionesBarra(labels, series)
                    );

                    this.graficaAlumnos.render();
                },

                crearGraficaMaterias() {
                    const elemento = document.querySelector('#graficaCalificacionesMaterias');

                    if (!elemento || !this.datos?.materias) {
                        return;
                    }

                    const labels = this.datos.materias.labels || [];
                    const series = this.datos.materias.series || [];

                    if (labels.length === 0 || series.length === 0) {
                        return;
                    }

                    this.graficaMaterias = new ApexCharts(
                        elemento,
                        this.opcionesBarra(labels, series)
                    );

                    this.graficaMaterias.render();
                },

                crearGraficaGlobal() {
                    const elemento = document.querySelector('#graficaCalificacionesGlobal');

                    if (!elemento || !this.datos?.global) {
                        return;
                    }

                    const promedio = Number(this.datos.global.promedio || 0);
                    const porcentaje = Number(this.datos.global.porcentaje || 0);

                    this.graficaGlobal = new ApexCharts(elemento, {
                        chart: {
                            type: 'radialBar',
                            height: 330,
                            toolbar: {
                                show: false
                            },
                            fontFamily: 'Inter, ui-sans-serif, system-ui'
                        },
                        series: [porcentaje],
                        labels: ['Promedio global'],
                        plotOptions: {
                            radialBar: {
                                hollow: {
                                    size: '64%'
                                },
                                dataLabels: {
                                    name: {
                                        fontSize: '14px',
                                        fontWeight: 800
                                    },
                                    value: {
                                        fontSize: '34px',
                                        fontWeight: 900,
                                        formatter: () => promedio.toFixed(1)
                                    }
                                }
                            }
                        }
                    });

                    this.graficaGlobal.render();
                },

                opcionesBarra(labels, series) {
                    return {
                        chart: {
                            type: 'bar',
                            height: 330,
                            toolbar: {
                                show: false
                            },
                            fontFamily: 'Inter, ui-sans-serif, system-ui'
                        },
                        series: [{
                            name: 'Promedio',
                            data: series
                        }],
                        xaxis: {
                            categories: labels,
                            labels: {
                                rotate: -45,
                                style: {
                                    fontSize: '11px'
                                }
                            }
                        },
                        yaxis: {
                            min: 0,
                            max: 10,
                            tickAmount: 5,
                            labels: {
                                formatter: valor => Number(valor).toFixed(0)
                            }
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 8,
                                columnWidth: '55%'
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: valor => Number(valor).toFixed(1),
                            style: {
                                fontSize: '11px',
                                fontWeight: 700
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: valor => Number(valor).toFixed(1) + ' promedio'
                            }
                        },
                        grid: {
                            strokeDashArray: 4
                        }
                    };
                }
            }
        }
    </script>
</div>
