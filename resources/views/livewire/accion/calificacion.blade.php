<div x-data="{
    insIds: @js(collect($inscripcionesTabla)->pluck('inscripcion_id')->values()->all()),
    asigIds: @js(collect($materias)->pluck('id')->values()->all()),

    storageKey: 'calificaciones_filtros_{{ $slug_nivel }}',

    guardandoFiltros: false,
    restaurandoFiltros: false,

    async iniciarFiltrosGuardados() {
        await this.restaurarFiltros();

        this.$watch('$wire.generacion_id', () => this.guardarFiltros());
        this.$watch('$wire.grado_id', () => this.guardarFiltros());
        this.$watch('$wire.semestre_id', () => this.guardarFiltros());
        this.$watch('$wire.grupo_id', () => this.guardarFiltros());
        this.$watch('$wire.parcial_bachillerato_id', () => this.guardarFiltros());
        this.$watch('$wire.periodo_basica_id', () => this.guardarFiltros());
        this.$watch('$wire.busqueda', () => this.guardarFiltros());
        this.$watch('$wire.filtro_estado', () => this.guardarFiltros());
        this.$watch('$wire.boleta_inscripcion_id', () => this.guardarFiltros());
        this.$watch('$wire.diploma_inscripcion_id', () => this.guardarFiltros());
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

        if (filtros.generacion_id) {
            await this.$wire.set('generacion_id', filtros.generacion_id);
            await this.esperar(250);
        }

        if (filtros.grado_id) {
            await this.$wire.set('grado_id', filtros.grado_id);
            await this.esperar(250);
        }

        if (filtros.semestre_id) {
            await this.$wire.set('semestre_id', filtros.semestre_id);
            await this.esperar(250);
        }

        if (filtros.grupo_id) {
            await this.$wire.set('grupo_id', filtros.grupo_id);
            await this.esperar(250);
        }

        if (filtros.parcial_bachillerato_id) {
            await this.$wire.set('parcial_bachillerato_id', filtros.parcial_bachillerato_id);
            await this.esperar(250);
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

        if (filtros.boleta_inscripcion_id) {
            await this.$wire.set('boleta_inscripcion_id', filtros.boleta_inscripcion_id);
        }

        if (filtros.diploma_inscripcion_id) {
            await this.$wire.set('diploma_inscripcion_id', filtros.diploma_inscripcion_id);
        }

        this.restaurandoFiltros = false;
    },

    guardarFiltros() {
        if (this.restaurandoFiltros || this.guardandoFiltros) {
            return;
        }

        this.guardandoFiltros = true;

        const filtros = {
            generacion_id: this.$wire.generacion_id || '',
            grado_id: this.$wire.grado_id || '',
            semestre_id: this.$wire.semestre_id || '',
            grupo_id: this.$wire.grupo_id || '',
            parcial_bachillerato_id: this.$wire.parcial_bachillerato_id || '',
            periodo_basica_id: this.$wire.periodo_basica_id || '',
            busqueda: this.$wire.busqueda || '',
            filtro_estado: this.$wire.filtro_estado || '',
            boleta_inscripcion_id: this.$wire.boleta_inscripcion_id || '',
            diploma_inscripcion_id: this.$wire.diploma_inscripcion_id || '',
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
}" x-init="iniciarFiltrosGuardados()" class="w-full">

    {{-- Niveles --}}
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

    {{-- Filtros principales --}}
    <div
        class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 {{ $this->esBachillerato ? 'xl:grid-cols-6' : 'xl:grid-cols-5' }}">

        <div>
            <flux:select label="Generación" wire:model.live="generacion_id"
                wire:key="generacion-{{ $slug_nivel }}-{{ $generaciones->count() }}">
                <flux:select.option value="">-- Selecciona una generación --</flux:select.option>

                @foreach ($generaciones as $generacion)
                    <flux:select.option value="{{ $generacion->id }}">
                        {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div>
            <flux:select label="Grado" wire:model.live="grado_id"
                wire:key="grado-{{ $generacion_id ?: 'null' }}-{{ $grados->count() }}" :disabled="!$generacion_id">
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
                    wire:key="semestre-bachillerato-{{ $generacion_id ?: 'null' }}-{{ $grado_id ?: 'null' }}-{{ $semestres->count() }}"
                    :disabled="!$generacion_id || !$grado_id">
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
                    wire:key="grupo-bachillerato-{{ $generacion_id ?: 'null' }}-{{ $grado_id ?: 'null' }}-{{ $semestre_id ?: 'null' }}-{{ $grupos->count() }}"
                    :disabled="!$generacion_id || !$grado_id || !$semestre_id || $grupos->isEmpty()">
                    <flux:select.option value="">-- Selecciona un grupo --</flux:select.option>

                    @foreach ($grupos as $gpo)
                        <flux:select.option value="{{ $gpo->id }}">
                            {{ $this->textoGrupo($gpo) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select label="Parcial" wire:model.live="parcial_bachillerato_id"
                    wire:key="parcial-bachillerato-{{ $generacion_id ?: 'null' }}-{{ $grado_id ?: 'null' }}-{{ $semestre_id ?: 'null' }}-{{ $grupo_id ?: 'null' }}"
                    :disabled="!$generacion_id || !$grado_id || !$semestre_id || !$grupo_id">
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
                    wire:key="grupo-basica-{{ $generacion_id ?: 'null' }}-{{ $grado_id ?: 'null' }}-{{ $grupos->count() }}"
                    :disabled="!$generacion_id || !$grado_id || $grupos->isEmpty()">
                    <flux:select.option value="">-- Selecciona un grupo --</flux:select.option>

                    @foreach ($grupos as $gpo)
                        <flux:select.option value="{{ $gpo->id }}">
                            {{ $this->textoGrupo($gpo) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select label="Periodo" wire:model.live="periodo_basica_id"
                    wire:key="periodo-basica-{{ $generacion_id ?: 'null' }}-{{ $grado_id ?: 'null' }}-{{ $grupo_id ?: 'null' }}"
                    :disabled="!$generacion_id || !$grado_id || !$grupo_id">
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
                                Información vigente del periodo académico seleccionado.
                            </p>
                        </div>
                    </div>

                    <span
                        class="inline-flex items-center rounded-full px-4 py-1.5 text-sm font-semibold {{ $this->claseEstadoPeriodo }}">
                        {{ $this->estadoPeriodo }}
                    </span>
                </div>

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
                            <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>Periodo escolar
                        </div>
                        <div class="mt-2 text-xl font-extrabold text-neutral-900 dark:text-neutral-100">
                            {{ $this->nombrePeriodo }}</div>
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

    @php($estadisticas = $this->estadisticasCalificaciones)

    @if (count($inscripciones) > 0 && count($materias) > 0)
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-2xl border border-sky-100 bg-sky-50 p-4 dark:border-sky-900/40 dark:bg-sky-950/30">
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">Promedio global</p>
                <p class="mt-1 text-2xl font-black text-sky-900 dark:text-sky-100">
                    {{ $estadisticas['promedio_global'] ?? '—' }}</p>
            </div>
            <div
                class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Aprobación
                </p>
                <p class="mt-1 text-2xl font-black text-emerald-900 dark:text-emerald-100">
                    {{ $estadisticas['porcentaje_aprobacion'] }}%</p>
            </div>
            <div
                class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-950/30">
                <p class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Pendientes</p>
                <p class="mt-1 text-2xl font-black text-amber-900 dark:text-amber-100">
                    {{ $estadisticas['pendientes'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-100 bg-rose-50 p-4 dark:border-rose-900/40 dark:bg-rose-950/30">
                <p class="text-xs font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">Reprobadas</p>
                <p class="mt-1 text-2xl font-black text-rose-900 dark:text-rose-100">{{ $estadisticas['reprobadas'] }}
                </p>
            </div>
            <div
                class="rounded-2xl border border-violet-100 bg-violet-50 p-4 dark:border-violet-900/40 dark:bg-violet-950/30">
                <p class="text-xs font-bold uppercase tracking-wide text-violet-700 dark:text-violet-300">Especiales
                </p>
                <p class="mt-1 text-2xl font-black text-violet-900 dark:text-violet-100">
                    {{ $estadisticas['especiales'] }}</p>
            </div>
            <div
                class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/30">
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Captura</p>
                <p class="mt-1 text-2xl font-black text-indigo-900 dark:text-indigo-100">
                    {{ $estadisticas['porcentaje_captura'] }}%</p>
            </div>
        </div>
    @endif

    {{-- Tabla --}}
    <div
        class="mt-6 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="relative">
            <div wire:loading.flex
                wire:target="nivel_id,generacion_id,grado_id,grupo_id,semestre_id,parcial_bachillerato_id,periodo_basica_id,busqueda,filtro_estado,limpiarFiltros,guardarCalificaciones,abrirRevisionGuardado"
                class="absolute inset-0 z-30 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-950/60">
                <div
                    class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white px-5 py-4 shadow-lg dark:border-neutral-800 dark:bg-neutral-950">
                    <div
                        class="h-5 w-5 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-white">
                    </div>
                    <div class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">Cargando…</div>
                </div>
            </div>

            <div class="border-b border-neutral-200 p-4 dark:border-neutral-800">
                <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-3">
                    <div>
                        <label class="text-xs font-medium text-neutral-600 dark:text-neutral-300">Buscar alumno o
                            matrícula</label>
                        <input type="text" wire:model.live.debounce.300ms="busqueda"
                            placeholder="Escribe nombre o matrícula..."
                            class="mt-1 w-full rounded-2xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-100">
                    </div>

                    <div>
                        <flux:select label="Vista rápida" wire:model.live="filtro_estado">
                            <flux:select.option value="">Todos</flux:select.option>
                            <flux:select.option value="pendientes">Pendientes</flux:select.option>
                            <flux:select.option value="aprobados">Aprobados</flux:select.option>
                            <flux:select.option value="reprobados">Reprobados</flux:select.option>
                            <flux:select.option value="especiales">Valores especiales</flux:select.option>
                            <flux:select.option value="cambios">Con cambios</flux:select.option>
                        </flux:select>
                    </div>
                </div>
                <div
                    class="mt-3 rounded-2xl border border-sky-100 bg-sky-50/70 p-3 dark:border-sky-900/40 dark:bg-sky-950/20">
                    <div class="flex items-start gap-3">
                        <div
                            class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-sky-600 shadow-sm dark:bg-neutral-900 dark:text-sky-300">
                            <flux:icon.document-text class="h-5 w-5" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">

                                {{-- Boleta por alumno --}}
                                <div
                                    class="rounded-2xl border border-white/70 bg-white/90 p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-300">
                                            <flux:icon.document-arrow-down class="h-5 w-5" />
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                                Descargar boleta
                                            </h4>

                                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                                Selecciona un alumno para descargar su boleta del
                                                {{ $this->esBachillerato ? 'parcial' : 'periodo' }} seleccionado.
                                            </p>

                                            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                                                <div class="flex-1">
                                                    <flux:select
                                                        label="{{ $this->esBachillerato ? 'Boleta parcial por alumno' : 'Boleta de periodo por alumno' }}"
                                                        wire:model.live="boleta_inscripcion_id"
                                                        :disabled="count($inscripciones) === 0 || !$periodo_id">

                                                        <flux:select.option value="">
                                                            -- Selecciona un alumno --
                                                        </flux:select.option>

                                                        @foreach ($inscripciones as $alumnoBoleta)
                                                            <flux:select.option
                                                                value="{{ $alumnoBoleta['inscripcion_id'] }}">
                                                                {{ $alumnoBoleta['matricula'] }} -
                                                                {{ $alumnoBoleta['alumno'] }}
                                                            </flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </div>

                                                <button type="button"
                                                    @if (!$this->puedeExportarBoleta) disabled @endif
                                                    x-on:click="window.open('{{ route(
                                                        'misrutas.boleta.calificaciones.pdf',
                                                        array_filter([
                                                            'slug_nivel' => $slug_nivel,
                                                            'generacion_id' => $generacion_id,
                                                            'grado_id' => $grado_id,
                                                            'grupo_id' => $grupo_id,
                                                            'periodo_id' => $periodo_id,
                                                            'inscripcion_id' => $boleta_inscripcion_id,
                                                            'semestre_id' => $this->esBachillerato ? $semestre_id : null,
                                                            'parcial_bachillerato_id' => $this->esBachillerato ? $parcial_bachillerato_id : null,
                                                            'periodo_basica_id' => !$this->esBachillerato ? $periodo_basica_id : null,
                                                        ]),
                                                    ) }}', '_blank')"
                                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-sky-500/20 transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-50">
                                                    <flux:icon.document-arrow-down class="h-4 w-4" />
                                                    Descargar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Diploma por alumno --}}
                                <div
                                    class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 via-yellow-50 to-orange-50 p-4 shadow-sm dark:border-amber-900/40 dark:from-amber-950/20 dark:via-neutral-900 dark:to-orange-950/20">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm dark:bg-neutral-900 dark:text-amber-300">
                                            <flux:icon.trophy class="h-5 w-5" />
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">
                                                Descargar diploma
                                            </h4>

                                            <p class="mt-1 text-xs text-neutral-600 dark:text-neutral-400">
                                                Selecciona un alumno para descargar su diploma del
                                                {{ $this->esBachillerato ? 'parcial' : 'periodo' }} seleccionado.
                                            </p>

                                            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                                                <div class="flex-1">
                                                    <flux:select
                                                        label="{{ $this->esBachillerato ? 'Diploma por parcial' : 'Diploma por periodo' }}"
                                                        wire:model.live="diploma_inscripcion_id"
                                                        :disabled="count($inscripciones) === 0 || !$periodo_id">

                                                        <flux:select.option value="">
                                                            -- Selecciona un alumno --
                                                        </flux:select.option>

                                                        @foreach ($inscripciones as $alumnoDiploma)
                                                            <flux:select.option
                                                                value="{{ $alumnoDiploma['inscripcion_id'] }}">
                                                                {{ $alumnoDiploma['matricula'] }} -
                                                                {{ $alumnoDiploma['alumno'] }}
                                                            </flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </div>

                                                <button type="button"
                                                    @if (!$this->puedeExportarDiploma) disabled @endif
                                                    x-on:click="window.open('{{ route(
                                                        'misrutas.diploma.calificaciones.pdf',
                                                        array_filter([
                                                            'slug_nivel' => $slug_nivel,
                                                            'generacion_id' => $generacion_id,
                                                            'grado_id' => $grado_id,
                                                            'grupo_id' => $grupo_id,
                                                            'periodo_id' => $periodo_id,
                                                            'inscripcion_id' => $diploma_inscripcion_id,
                                                            'semestre_id' => $this->esBachillerato ? $semestre_id : null,
                                                            'parcial_bachillerato_id' => $this->esBachillerato ? $parcial_bachillerato_id : null,
                                                            'periodo_basica_id' => !$this->esBachillerato ? $periodo_basica_id : null,
                                                        ]),
                                                    ) }}', '_blank')"
                                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-amber-400 via-yellow-500 to-orange-500 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-amber-500/20 transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-50">
                                                    <flux:icon.trophy class="h-4 w-4" />
                                                    Diploma
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            @if (!$periodo_id)
                                <div
                                    class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                                    Primero selecciona {{ $this->esBachillerato ? 'un parcial' : 'un periodo' }}
                                    para habilitar las descargas por alumno.
                                </div>
                            @endif
                        </div>
                    </div>
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
                                <th class="min-w-[190px] px-4 py-2 text-center font-semibold text-white">
                                    <div class="text-white">{{ mb_strtoupper($m['materia']) }}</div>
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

                            <tr class="hover:bg-neutral-50/70 dark:hover:bg-neutral-950/40">
                                <td class="px-4 py-3 text-neutral-700 dark:text-neutral-200">{{ $index + 1 }}</td>
                                <td
                                    class="sticky left-0 z-10 min-w-[140px] bg-white px-4 py-3 font-medium text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100">
                                    {{ $fila['matricula'] }}</td>
                                <td
                                    class="sticky left-[140px] z-10 min-w-[260px] bg-white px-4 py-3 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 uppercase">
                                    {{ $fila['alumno'] }}</td>

                                @foreach ($materias as $m)
                                    @php($asigId = (int) $m['id'])
                                    <td class="px-4 py-3 text-center">
                                        <div class="mx-auto w-28">
                                            <input id="cal-{{ $insId }}-{{ $asigId }}" type="text"
                                                maxlength="5" inputmode="text"
                                                wire:model.lazy="calificaciones.{{ $insId }}.{{ $asigId }}"
                                                @focus="$event.target.select()"
                                                @keydown.enter.prevent="move({{ $insId }}, {{ $asigId }}, $event.shiftKey ? 'up' : 'down')"
                                                @keydown.tab.prevent="move({{ $insId }}, {{ $asigId }}, $event.shiftKey ? 'left' : 'right')"
                                                @keydown.arrow-down.prevent="move({{ $insId }}, {{ $asigId }}, 'down')"
                                                @keydown.arrow-up.prevent="move({{ $insId }}, {{ $asigId }}, 'up')"
                                                @keydown.arrow-right.prevent="move({{ $insId }}, {{ $asigId }}, 'right')"
                                                @keydown.arrow-left.prevent="move({{ $insId }}, {{ $asigId }}, 'left')"
                                                class="{{ $this->claseInputCalificacion($insId, $asigId) }}"
                                                placeholder="0-10 / AC" />

                                            @error('calificaciones.' . $insId . '.' . $asigId)
                                                <div class="mt-1 text-[11px] leading-tight text-red-600 dark:text-red-300">
                                                    {{ $message }}</div>
                                            @enderror


                                        </div>
                                    </td>
                                @endforeach

                                <td class="px-4 py-3 text-center font-semibold text-neutral-900 dark:text-neutral-100">
                                    {{ $promedios[$insId] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 4 + count($materias) }}" class="px-6 py-10">
                                    <div
                                        class="rounded-2xl border border-dashed border-neutral-200 p-6 text-center dark:border-neutral-800">
                                        <div class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">No
                                            hay datos para mostrar</div>
                                        <div class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $this->esBachillerato
                                                ? 'Selecciona generación, grado, semestre, grupo y parcial para cargar alumnos y materias.'
                                                : 'Selecciona generación, grado, grupo y periodo para cargar alumnos y materias.' }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-neutral-200 p-5 dark:border-neutral-800">
                @error('calificaciones')
                    <div
                        class="mb-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
                        {{ $message }}</div>
                @enderror

                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="w-full md:w-2/3">
                        <div class="flex items-center justify-between text-xs text-neutral-600 dark:text-neutral-300">
                            <span>Calificaciones introducidas: {{ $this->celdasCapturadas }} de
                                {{ $this->totalCeldas }} ({{ $this->porcentajeCaptura }}%)</span>
                        </div>
                        <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-950">
                            <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-indigo-500"
                                style="width: {{ $this->porcentajeCaptura }}%"></div>
                        </div>
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
                            @if ($this->mostrarBotonBitacora)
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

                            <button type="button" wire:click="abrirRevisionGuardado"
                                @if (!$this->puedeGuardar) disabled @endif class="{{ $this->claseGuardar }}">
                                Revisar y guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Gráficas --}}
    @if (count($inscripciones) > 0 && count($materias) > 0)
        <div wire:key="graficas-calificaciones-{{ md5(json_encode($graficasCalificaciones)) }}"
            x-data="graficasCalificacionesPro(@js($graficasCalificaciones))" x-init="iniciar()"
            class="mt-6 overflow-hidden rounded-[28px] border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
            <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-600"></div>
            <div class="p-5 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                            <span class="h-2 w-2 rounded-full bg-sky-500"></span>Análisis visual
                        </div>
                        <h3 class="mt-3 text-2xl font-black tracking-tight text-neutral-900 dark:text-white">Gráficas
                            de calificaciones</h3>
                        <p class="mt-1 max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">Visualización
                            automática de promedios por alumno, por materia y rendimiento global del grupo.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div
                                class="text-[11px] font-bold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                Promedio global</div>
                            <div class="mt-1 text-2xl font-black text-neutral-900 dark:text-white">
                                {{ number_format($graficasCalificaciones['global']['promedio'], 1) }}</div>
                        </div>
                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div
                                class="text-[11px] font-bold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                Aprobación</div>
                            <div class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-300">
                                {{ $graficasCalificaciones['global']['porcentaje_aprobacion'] }}%</div>
                        </div>
                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div
                                class="text-[11px] font-bold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                Aprobadas</div>
                            <div class="mt-1 text-2xl font-black text-sky-600 dark:text-sky-300">
                                {{ $graficasCalificaciones['global']['aprobadas'] }}</div>
                        </div>
                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div
                                class="text-[11px] font-bold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                Reprobadas</div>
                            <div class="mt-1 text-2xl font-black text-rose-600 dark:text-rose-300">
                                {{ $graficasCalificaciones['global']['reprobadas'] }}</div>
                        </div>
                    </div>
                </div>

                @if ($graficasCalificaciones['global']['total_numericas'] > 0)
                    <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-3">
                        <div
                            class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/40">
                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">Promedio por alumno</h4>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Promedio individual de cada
                                estudiante.</p>
                            <div id="graficaCalificacionesAlumnos" class="min-h-[330px]"></div>
                        </div>
                        <div
                            class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/40">
                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">Promedio por materia</h4>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Comparativo del rendimiento por
                                asignatura.</p>
                            <div id="graficaCalificacionesMaterias" class="min-h-[330px]"></div>
                        </div>
                        <div
                            class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/40">
                            <h4 class="text-sm font-black text-neutral-900 dark:text-white">Rendimiento global</h4>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Promedio general del grupo
                                seleccionado.</p>
                            <div id="graficaCalificacionesGlobal" class="min-h-[330px]"></div>
                        </div>
                    </div>
                @else
                    <div
                        class="mt-6 rounded-2xl border border-dashed border-neutral-300 p-6 text-center dark:border-neutral-700">
                        <div class="text-sm font-bold text-neutral-800 dark:text-neutral-100">Todavía no hay
                            calificaciones numéricas para graficar</div>
                        <div class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Captura calificaciones de 0 a
                            10 para generar las gráficas.</div>
                    </div>
                @endif
            </div>
        </div>
    @endif



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
                            <h3 class="text-xl font-black text-neutral-900 dark:text-white">Revisión antes de guardar
                            </h3>
                            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Verifica los cambios
                                detectados antes de guardar las calificaciones.</p>
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

                    <div class="mt-5">
                        <label
                            class="text-xs font-bold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Motivo
                            del guardado</label>
                        <textarea wire:model.live="motivo_guardado" rows="3"
                            class="mt-1 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 outline-none focus:ring-2 focus:ring-sky-300 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white"
                            placeholder="Ejemplo: Captura de calificaciones del periodo, corrección administrativa, revisión final..."></textarea>
                    </div>

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
                            wire:target="guardarCalificaciones"
                            class="rounded-2xl bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-sky-500/20 hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="guardarCalificaciones">Confirmar y guardar</span>
                            <span wire:loading wire:target="guardarCalificaciones">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                            <livewire:accion.bitacora-calificaciones :nivel_id="$nivel_id" :grado_id="$grado_id"
                                :grupo_id="$grupo_id" :semestre_id="$semestre_id" :generacion_id="$generacion_id" :periodo_id="$periodo_id"
                                :esBachillerato="$this->esBachillerato" :key="'bitacora-calificaciones-' .
                                    md5(
                                        json_encode([
                                            'nivel' => $nivel_id,
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

    @once
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @endonce

    <script>
        function graficasCalificacionesPro(datos) {
            return {
                datos: datos,
                graficaAlumnos: null,
                graficaMaterias: null,
                graficaGlobal: null,
                iniciar() {
                    this.esperarApexCharts(() => {
                        this.destruirGraficas();
                        this.crearGraficaAlumnos();
                        this.crearGraficaMaterias();
                        this.crearGraficaGlobal();
                    });
                },
                esperarApexCharts(callback) {
                    if (typeof ApexCharts !== 'undefined') {
                        callback();
                        return;
                    }
                    setTimeout(() => this.esperarApexCharts(callback), 100);
                },
                destruirGraficas() {
                    if (this.graficaAlumnos) this.graficaAlumnos.destroy();
                    if (this.graficaMaterias) this.graficaMaterias.destroy();
                    if (this.graficaGlobal) this.graficaGlobal.destroy();
                    this.graficaAlumnos = null;
                    this.graficaMaterias = null;
                    this.graficaGlobal = null;
                },
                crearGraficaAlumnos() {
                    const elemento = document.querySelector('#graficaCalificacionesAlumnos');
                    if (!elemento || !this.datos.alumnos || this.datos.alumnos.series.length === 0) return;
                    this.graficaAlumnos = new ApexCharts(elemento, this.opcionesBarra(this.datos.alumnos.labels, this.datos
                        .alumnos.series));
                    this.graficaAlumnos.render();
                },
                crearGraficaMaterias() {
                    const elemento = document.querySelector('#graficaCalificacionesMaterias');
                    if (!elemento || !this.datos.materias || this.datos.materias.series.length === 0) return;
                    this.graficaMaterias = new ApexCharts(elemento, this.opcionesBarra(this.datos.materias.labels, this
                        .datos.materias.series));
                    this.graficaMaterias.render();
                },
                crearGraficaGlobal() {
                    const elemento = document.querySelector('#graficaCalificacionesGlobal');
                    if (!elemento || !this.datos.global) return;
                    this.graficaGlobal = new ApexCharts(elemento, {
                        chart: {
                            type: 'radialBar',
                            height: 330,
                            toolbar: {
                                show: false
                            },
                            fontFamily: 'Inter, ui-sans-serif, system-ui'
                        },
                        series: [this.datos.global.porcentaje],
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
                                        formatter: () => Number(this.datos.global.promedio).toFixed(1)
                                    }
                                }
                            }
                        },
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
                            tickAmount: 5
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
