<div x-data="{
    tooltip: {
        visible: false,
        fijo: false,
        x: 0,
        y: 0,
        titulo: '',
        total: 0,
        alumnos: [],
    },

    mostrarTooltip(titulo, total, alumnos, event) {
        if (this.tooltip.fijo) return;

        this.tooltip.visible = true;
        this.tooltip.titulo = titulo;
        this.tooltip.total = total || 0;
        this.tooltip.alumnos = alumnos || [];

        this.posicionarTooltip(event);
    },

    moverTooltip(event) {
        if (this.tooltip.fijo) return;

        this.posicionarTooltip(event);
    },

    posicionarTooltip(event) {
        const boton = event.currentTarget;
        const rect = boton.getBoundingClientRect();

        const anchoTooltip = 320;
        const altoEstimado = 360;
        const separacion = -5;

        let x = rect.left + (rect.width / 2) - (anchoTooltip / 2) - 580;
        let y = rect.bottom + separacion;

        if (x < 12) {
            x = 12;
        }

        if ((x + anchoTooltip) > window.innerWidth - 12) {
            x = window.innerWidth - anchoTooltip - 12;
        }

        if ((y + altoEstimado) > window.innerHeight - 12) {
            y = rect.top - altoEstimado - separacion;
        }

        if (y < 12) {
            y = 12;
        }

        this.tooltip.x = x;
        this.tooltip.y = y;
    },

    fijarTooltip(titulo, total, alumnos, event) {
        event.stopPropagation();

        this.tooltip.visible = true;
        this.tooltip.fijo = true;
        this.tooltip.titulo = titulo;
        this.tooltip.total = total || 0;
        this.tooltip.alumnos = alumnos || [];

        this.posicionarTooltip(event);
    },

    ocultarTooltip() {
        if (this.tooltip.fijo) return;

        this.tooltip.visible = false;
    },

    cerrarTooltipFijo() {
        if (!this.tooltip.fijo) return;

        this.tooltip.visible = false;
        this.tooltip.fijo = false;
    },
}" x-on:click.window="cerrarTooltipFijo()" x-on:keydown.escape.window="cerrarTooltipFijo()"
    class="space-y-6">
    @once
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    @endonce

    {{-- TOOLTIP FLOTANTE --}}
    <div x-cloak x-show="tooltip.visible" x-transition.opacity.duration.150ms x-on:click.stop
        class="fixed z-[9999] w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-black/5 dark:border-neutral-700 dark:bg-neutral-900 dark:shadow-black/40"
        :class="tooltip.fijo ? 'pointer-events-auto' : 'pointer-events-none'"
        :style="`left: ${tooltip.x}px; top: ${tooltip.y}px;`">
        <div class="bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 px-4 py-3 text-white">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-white/80">
                        Detalle de alumnos
                    </p>

                    <p class="mt-1 text-sm font-black" x-text="tooltip.titulo"></p>
                </div>

                <button x-show="tooltip.fijo" type="button" x-on:click="cerrarTooltipFijo()"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white/15 text-lg font-black leading-none text-white transition hover:bg-white/25"
                    title="Cerrar">
                    ×
                </button>
            </div>
        </div>

        <div class="space-y-3 p-4">
            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-neutral-800">
                <span class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Total
                </span>

                <span
                    class="rounded-full bg-sky-100 px-3 py-1 text-sm font-black text-sky-700 dark:bg-sky-950/40 dark:text-sky-300"
                    x-text="tooltip.total"></span>
            </div>

            <template x-if="tooltip.alumnos.length > 0">
                <div class="max-h-64 space-y-1 overflow-y-auto pr-1">
                    <template x-for="(alumno, index) in tooltip.alumnos" :key="index">
                        <div
                            class="flex items-start gap-2 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-neutral-800">
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-sky-50 text-[10px] font-black text-sky-700 dark:bg-sky-950/40 dark:text-sky-300"
                                x-text="index + 1"></span>

                            <span x-text="alumno"></span>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="tooltip.alumnos.length === 0">
                <div
                    class="rounded-xl border border-dashed border-slate-200 p-3 text-center text-xs font-bold text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                    No hay alumnos en esta categoría.
                </div>
            </template>

            <div x-show="tooltip.fijo"
                class="rounded-xl bg-amber-50 px-3 py-2 text-[11px] font-bold text-amber-700 dark:bg-amber-950/20 dark:text-amber-300">
                Tooltip fijado. Da click fuera del recuadro o presiona Escape para cerrarlo.
            </div>
        </div>
    </div>

    {{-- ITERA NIVELES --}}
    <div class="overflow-hidden">
        <div>
            <div class="-mx-1 overflow-x-auto pb-1">
                <div class="flex min-w-max items-center justify-center gap-2 px-1">
                    @foreach ($niveles as $item)
                        @php
                            $activo = $slug_nivel === $item->slug;
                        @endphp

                        <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'generales']) }}"
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
                                <span class="rounded-full bg-white/15 px-2 py-0.5 text-[11px] font-bold text-white">
                                    Activo
                                </span>

                                <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-white/80"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA ESTADÍSTICA GENERAL --}}
    <section
        class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

        <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

        <div class="space-y-5 p-5 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                        <flux:icon.chart-bar-square class="h-6 w-6" />
                    </div>

                    <div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">
                            Estadística general de {{ $nivel->nombre }}
                        </h2>

                        <p class="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                            Concentrado por grado con inscripción inicial, altas, inscripción total, bajas y existencia
                            actual.
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50">
                                Nivel: {{ $nivel->nombre }}
                            </span>

                            <span
                                class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900/50">
                                Generación: {{ $this->textoGeneracion($generacion_id ? (int) $generacion_id : null) }}
                            </span>

                            <span
                                class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50">
                                Existencia actual: {{ $this->totalesEstadistica['existencia_actual']['t'] }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 xl:w-[420px]">
                    <flux:field>
                        <flux:label>Generación</flux:label>

                        <flux:select wire:model.live="generacion_id">
                            <flux:select.option value="">
                                Todas las generaciones
                            </flux:select.option>

                            @foreach ($generaciones as $generacion)
                                <flux:select.option value="{{ $generacion->id }}">
                                    {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <div class="flex items-end">
                        <button type="button" wire:click="limpiarFiltroEstadistica"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                            <flux:icon.arrow-path class="h-4 w-4" />
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            {{-- TARJETAS RESUMEN --}}
            @php
                $tarjetasResumen = [
                    [
                        'titulo' => 'Inscripción inicial',
                        'grupo' => 'inicial',
                        'card' => 'border-slate-200 bg-slate-50 dark:border-neutral-800 dark:bg-neutral-950/40',
                        'texto' => 'text-slate-500 dark:text-slate-400',
                        'numero' => 'text-slate-900 dark:text-white',
                    ],
                    [
                        'titulo' => 'Altas',
                        'grupo' => 'altas',
                        'card' => 'border-blue-200 bg-blue-50 dark:border-blue-900/50 dark:bg-blue-950/20',
                        'texto' => 'text-blue-700 dark:text-blue-300',
                        'numero' => 'text-slate-900 dark:text-white',
                    ],
                    [
                        'titulo' => 'Inscripción total',
                        'grupo' => 'inscripcion_total',
                        'card' => 'border-indigo-200 bg-indigo-50 dark:border-indigo-900/50 dark:bg-indigo-950/20',
                        'texto' => 'text-indigo-700 dark:text-indigo-300',
                        'numero' => 'text-slate-900 dark:text-white',
                    ],
                    [
                        'titulo' => 'Bajas',
                        'grupo' => 'bajas',
                        'card' => 'border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/20',
                        'texto' => 'text-rose-700 dark:text-rose-300',
                        'numero' => 'text-slate-900 dark:text-white',
                    ],
                    [
                        'titulo' => 'Existencia actual',
                        'grupo' => 'existencia_actual',
                        'card' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/20',
                        'texto' => 'text-emerald-700 dark:text-emerald-300',
                        'numero' => 'text-slate-900 dark:text-white',
                    ],
                ];
            @endphp

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach ($tarjetasResumen as $tarjeta)
                    @php
                        $grupoTarjeta = $tarjeta['grupo'];
                        $totalTarjeta = $this->totalesEstadistica[$grupoTarjeta]['t'];
                        $hombresTarjeta = $this->totalesEstadistica[$grupoTarjeta]['h'];
                        $mujeresTarjeta = $this->totalesEstadistica[$grupoTarjeta]['m'];
                    @endphp

                    <div class="rounded-2xl border p-4 {{ $tarjeta['card'] }}">
                        <p class="text-xs font-black uppercase tracking-wide {{ $tarjeta['texto'] }}">
                            {{ $tarjeta['titulo'] }}
                        </p>

                        <p class="mt-2 text-3xl font-black {{ $tarjeta['numero'] }}">
                            <button type="button"
                                class="rounded-xl px-1 transition hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm dark:hover:bg-neutral-900/60"
                                x-on:mouseenter="mostrarTooltip(@js($tarjeta['titulo'] . ' · Total general'), {{ $totalTarjeta }}, @js($this->totalesEstadistica[$grupoTarjeta]['nombres_t']), $event)"
                                x-on:mousemove="moverTooltip($event)" x-on:mouseleave="ocultarTooltip()"
                                x-on:click="fijarTooltip(@js($tarjeta['titulo'] . ' · Total general'), {{ $totalTarjeta }}, @js($this->totalesEstadistica[$grupoTarjeta]['nombres_t']), $event)">
                                {{ $totalTarjeta }}
                            </button>
                        </p>

                        <p class="mt-1 text-xs font-bold {{ $tarjeta['texto'] }}">
                            H:
                            <button type="button"
                                class="rounded-lg px-1 transition hover:bg-white/70 dark:hover:bg-neutral-900/60"
                                x-on:mouseenter="mostrarTooltip(@js($tarjeta['titulo'] . ' · Hombres'), {{ $hombresTarjeta }}, @js($this->totalesEstadistica[$grupoTarjeta]['nombres_h']), $event)"
                                x-on:mousemove="moverTooltip($event)" x-on:mouseleave="ocultarTooltip()"
                                x-on:click="fijarTooltip(@js($tarjeta['titulo'] . ' · Hombres'), {{ $hombresTarjeta }}, @js($this->totalesEstadistica[$grupoTarjeta]['nombres_h']), $event)">
                                {{ $hombresTarjeta }}
                            </button>

                            · M:
                            <button type="button"
                                class="rounded-lg px-1 transition hover:bg-white/70 dark:hover:bg-neutral-900/60"
                                x-on:mouseenter="mostrarTooltip(@js($tarjeta['titulo'] . ' · Mujeres'), {{ $mujeresTarjeta }}, @js($this->totalesEstadistica[$grupoTarjeta]['nombres_m']), $event)"
                                x-on:mousemove="moverTooltip($event)" x-on:mouseleave="ocultarTooltip()"
                                x-on:click="fijarTooltip(@js($tarjeta['titulo'] . ' · Mujeres'), {{ $mujeresTarjeta }}, @js($this->totalesEstadistica[$grupoTarjeta]['nombres_m']), $event)">
                                {{ $mujeresTarjeta }}
                            </button>
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- TABLA --}}
            <div
                class="overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="overflow-x-auto">
                    <table class="min-w-[1120px] w-full border-collapse text-center text-sm">
                        <thead>
                            <tr>
                                <th rowspan="3"
                                    class="sticky left-0 z-20 border border-slate-300 bg-slate-900 px-4 py-3 text-xs font-black uppercase tracking-wide text-white dark:border-neutral-700">
                                    Grado
                                </th>

                                <th colspan="3"
                                    class="border border-slate-300 bg-slate-800 px-4 py-2 text-xs font-black uppercase tracking-wide text-white dark:border-neutral-700">
                                    Inscripción inicial
                                </th>

                                <th colspan="12"
                                    class="border border-slate-300 bg-gradient-to-r from-sky-600 via-blue-700 to-indigo-700 px-4 py-2 text-sm font-black uppercase tracking-[0.18em] text-white dark:border-neutral-700">
                                    Medio curso
                                </th>
                            </tr>

                            <tr>
                                <th colspan="3"
                                    class="border border-slate-300 bg-slate-100 px-3 py-2 text-xs font-black uppercase tracking-wide text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                                    Inicial
                                </th>

                                <th colspan="3"
                                    class="border border-slate-300 bg-blue-50 px-3 py-2 text-xs font-black uppercase tracking-wide text-blue-700 dark:border-neutral-700 dark:bg-blue-950/30 dark:text-blue-300">
                                    Altas
                                </th>

                                <th colspan="3"
                                    class="border border-slate-300 bg-indigo-50 px-3 py-2 text-xs font-black uppercase tracking-wide text-indigo-700 dark:border-neutral-700 dark:bg-indigo-950/30 dark:text-indigo-300">
                                    Inscripción total
                                </th>

                                <th colspan="3"
                                    class="border border-slate-300 bg-rose-50 px-3 py-2 text-xs font-black uppercase tracking-wide text-rose-700 dark:border-neutral-700 dark:bg-rose-950/30 dark:text-rose-300">
                                    Bajas
                                </th>

                                <th colspan="3"
                                    class="border border-slate-300 bg-emerald-50 px-3 py-2 text-xs font-black uppercase tracking-wide text-emerald-700 dark:border-neutral-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    Existencia actual
                                </th>
                            </tr>

                            <tr>
                                @foreach (['H', 'M', 'T'] as $sexo)
                                    <th
                                        class="border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                        {{ $sexo }}
                                    </th>
                                @endforeach

                                @foreach (['altas', 'inscripcion_total', 'bajas', 'existencia_actual'] as $grupoTabla)
                                    @foreach (['H', 'M', 'T'] as $sexo)
                                        <th
                                            class="border border-slate-300 px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200
                                            {{ $grupoTabla === 'altas' ? 'bg-blue-50/60 dark:bg-blue-950/10' : '' }}
                                            {{ $grupoTabla === 'inscripcion_total' ? 'bg-indigo-50/60 dark:bg-indigo-950/10' : '' }}
                                            {{ $grupoTabla === 'bajas' ? 'bg-rose-50/60 dark:bg-rose-950/10' : '' }}
                                            {{ $grupoTabla === 'existencia_actual' ? 'bg-emerald-50/60 dark:bg-emerald-950/10' : '' }}">
                                            {{ $sexo }}
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                $bloquesTabla = [
                                    [
                                        'grupo' => 'inicial',
                                        'titulo' => 'Inicial',
                                        'celdas' => [
                                            'h' =>
                                                'border border-slate-300 px-3 py-3 font-bold text-slate-700 dark:border-neutral-700 dark:text-slate-200',
                                            'm' =>
                                                'border border-slate-300 px-3 py-3 font-bold text-slate-700 dark:border-neutral-700 dark:text-slate-200',
                                            't' =>
                                                'border border-slate-300 bg-slate-50 px-3 py-3 font-black text-slate-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white',
                                        ],
                                        'hover' => 'hover:bg-slate-100 dark:hover:bg-neutral-800',
                                    ],
                                    [
                                        'grupo' => 'altas',
                                        'titulo' => 'Altas',
                                        'celdas' => [
                                            'h' =>
                                                'border border-slate-300 bg-blue-50/50 px-3 py-3 font-bold text-blue-800 dark:border-neutral-700 dark:bg-blue-950/10 dark:text-blue-300',
                                            'm' =>
                                                'border border-slate-300 bg-blue-50/50 px-3 py-3 font-bold text-blue-800 dark:border-neutral-700 dark:bg-blue-950/10 dark:text-blue-300',
                                            't' =>
                                                'border border-slate-300 bg-blue-100/70 px-3 py-3 font-black text-blue-900 dark:border-neutral-700 dark:bg-blue-950/20 dark:text-blue-200',
                                        ],
                                        'hover' => 'hover:bg-white dark:hover:bg-neutral-900',
                                    ],
                                    [
                                        'grupo' => 'inscripcion_total',
                                        'titulo' => 'Inscripción total',
                                        'celdas' => [
                                            'h' =>
                                                'border border-slate-300 bg-indigo-50/50 px-3 py-3 font-bold text-indigo-800 dark:border-neutral-700 dark:bg-indigo-950/10 dark:text-indigo-300',
                                            'm' =>
                                                'border border-slate-300 bg-indigo-50/50 px-3 py-3 font-bold text-indigo-800 dark:border-neutral-700 dark:bg-indigo-950/10 dark:text-indigo-300',
                                            't' =>
                                                'border border-slate-300 bg-indigo-100/70 px-3 py-3 font-black text-indigo-900 dark:border-neutral-700 dark:bg-indigo-950/20 dark:text-indigo-200',
                                        ],
                                        'hover' => 'hover:bg-white dark:hover:bg-neutral-900',
                                    ],
                                    [
                                        'grupo' => 'bajas',
                                        'titulo' => 'Bajas',
                                        'celdas' => [
                                            'h' =>
                                                'border border-slate-300 bg-rose-50/50 px-3 py-3 font-bold text-rose-800 dark:border-neutral-700 dark:bg-rose-950/10 dark:text-rose-300',
                                            'm' =>
                                                'border border-slate-300 bg-rose-50/50 px-3 py-3 font-bold text-rose-800 dark:border-neutral-700 dark:bg-rose-950/10 dark:text-rose-300',
                                            't' =>
                                                'border border-slate-300 bg-rose-100/70 px-3 py-3 font-black text-rose-900 dark:border-neutral-700 dark:bg-rose-950/20 dark:text-rose-200',
                                        ],
                                        'hover' => 'hover:bg-white dark:hover:bg-neutral-900',
                                    ],
                                    [
                                        'grupo' => 'existencia_actual',
                                        'titulo' => 'Existencia actual',
                                        'celdas' => [
                                            'h' =>
                                                'border border-slate-300 bg-emerald-50/50 px-3 py-3 font-bold text-emerald-800 dark:border-neutral-700 dark:bg-emerald-950/10 dark:text-emerald-300',
                                            'm' =>
                                                'border border-slate-300 bg-emerald-50/50 px-3 py-3 font-bold text-emerald-800 dark:border-neutral-700 dark:bg-emerald-950/10 dark:text-emerald-300',
                                            't' =>
                                                'border border-slate-300 bg-emerald-100/70 px-3 py-3 font-black text-emerald-900 dark:border-neutral-700 dark:bg-emerald-950/20 dark:text-emerald-200',
                                        ],
                                        'hover' => 'hover:bg-white dark:hover:bg-neutral-900',
                                    ],
                                ];

                                $sexosTabla = [
                                    'h' => 'Hombres',
                                    'm' => 'Mujeres',
                                    't' => 'Total',
                                ];
                            @endphp

                            @forelse ($this->estadisticaGeneral as $fila)
                                <tr class="transition hover:bg-sky-50/60 dark:hover:bg-neutral-800/70">
                                    <td
                                        class="sticky left-0 z-10 border border-slate-300 bg-white px-4 py-3 text-base font-black text-slate-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white">
                                        {{ $fila['grado'] }}°
                                    </td>

                                    @foreach ($bloquesTabla as $bloque)
                                        @foreach ($sexosTabla as $claveSexo => $textoSexo)
                                            @php
                                                $grupo = $bloque['grupo'];
                                                $valor = $fila[$grupo][$claveSexo] ?? 0;
                                                $nombres = $fila[$grupo]['nombres_' . $claveSexo] ?? [];
                                                $tituloTooltip =
                                                    $bloque['titulo'] . ' · ' . $fila['grado'] . '° · ' . $textoSexo;
                                            @endphp

                                            <td class="{{ $bloque['celdas'][$claveSexo] }}">
                                                @if ($valor)
                                                    <button type="button"
                                                        class="rounded-xl px-2 py-1 transition hover:-translate-y-0.5 hover:shadow-sm {{ $bloque['hover'] }}"
                                                        x-on:mouseenter="mostrarTooltip(@js($tituloTooltip), {{ $valor }}, @js($nombres), $event)"
                                                        x-on:mousemove="moverTooltip($event)"
                                                        x-on:mouseleave="ocultarTooltip()"
                                                        x-on:click="fijarTooltip(@js($tituloTooltip), {{ $valor }}, @js($nombres), $event)">
                                                        {{ $valor }}
                                                    </button>
                                                @endif
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="16"
                                        class="border border-slate-300 px-4 py-10 text-center dark:border-neutral-700">
                                        <p class="font-black text-slate-700 dark:text-slate-200">
                                            No hay grados registrados para este nivel.
                                        </p>

                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            Cuando existan grados y alumnos inscritos, se mostrará la estadística.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <tfoot>
                            @php
                                $bloquesTotales = [
                                    [
                                        'grupo' => 'inicial',
                                        'titulo' => 'Inicial',
                                        'clases' => [
                                            'h' => 'bg-slate-800',
                                            'm' => 'bg-slate-800',
                                            't' => 'bg-slate-950',
                                        ],
                                    ],
                                    [
                                        'grupo' => 'altas',
                                        'titulo' => 'Altas',
                                        'clases' => [
                                            'h' => 'bg-blue-700',
                                            'm' => 'bg-blue-700',
                                            't' => 'bg-blue-800',
                                        ],
                                    ],
                                    [
                                        'grupo' => 'inscripcion_total',
                                        'titulo' => 'Inscripción total',
                                        'clases' => [
                                            'h' => 'bg-indigo-700',
                                            'm' => 'bg-indigo-700',
                                            't' => 'bg-indigo-800',
                                        ],
                                    ],
                                    [
                                        'grupo' => 'bajas',
                                        'titulo' => 'Bajas',
                                        'clases' => [
                                            'h' => 'bg-rose-700',
                                            'm' => 'bg-rose-700',
                                            't' => 'bg-rose-800',
                                        ],
                                    ],
                                    [
                                        'grupo' => 'existencia_actual',
                                        'titulo' => 'Existencia actual',
                                        'clases' => [
                                            'h' => 'bg-emerald-700',
                                            'm' => 'bg-emerald-700',
                                            't' => 'bg-emerald-800',
                                        ],
                                    ],
                                ];
                            @endphp

                            <tr>
                                <td
                                    class="sticky left-0 z-10 border border-slate-300 bg-slate-900 px-4 py-3 text-sm font-black uppercase tracking-wide text-white dark:border-neutral-700">
                                    Total
                                </td>

                                @foreach ($bloquesTotales as $bloque)
                                    @foreach ($sexosTabla as $claveSexo => $textoSexo)
                                        @php
                                            $grupo = $bloque['grupo'];
                                            $valor = $this->totalesEstadistica[$grupo][$claveSexo] ?? 0;
                                            $nombres = $this->totalesEstadistica[$grupo]['nombres_' . $claveSexo] ?? [];
                                            $tituloTooltip = $bloque['titulo'] . ' · Total general · ' . $textoSexo;
                                        @endphp

                                        <td
                                            class="border border-slate-300 {{ $bloque['clases'][$claveSexo] }} px-3 py-3 font-black text-white dark:border-neutral-700">
                                            @if ($valor)
                                                <button type="button"
                                                    class="rounded-xl px-2 py-1 transition hover:-translate-y-0.5 hover:bg-white/15 hover:shadow-sm"
                                                    x-on:mouseenter="mostrarTooltip(@js($tituloTooltip), {{ $valor }}, @js($nombres), $event)"
                                                    x-on:mousemove="moverTooltip($event)"
                                                    x-on:mouseleave="ocultarTooltip()"
                                                    x-on:click="fijarTooltip(@js($tituloTooltip), {{ $valor }}, @js($nombres), $event)">
                                                    {{ $valor }}
                                                </button>
                                            @endif
                                        </td>
                                    @endforeach
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div
                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                <p class="font-black">
                    Nota de cálculo
                </p>

                <p class="mt-1">
                    H = hombres, M = mujeres, T = total. La inscripción inicial toma alumnos de ciclo
                    <b>Inicio de ciclo</b>, las altas toman alumnos de <b>Medio Ciclo</b>, las bajas se detectan por
                    <b>fecha_baja</b> o <b>activo = false</b>, y la existencia actual considera alumnos activos sin
                    baja.
                </p>
            </div>
        </div>
    </section>

    {{-- LISTAS GENERALES --}}
    <livewire:accion.generales.listas :slug_nivel="$slug_nivel" :key="'listas-generales-' . $slug_nivel" />

    {{-- CREDENCIALES --}}
    <livewire:generales.credenciales :slug_nivel="$slug_nivel" :key="'credenciales-generales-' . $slug_nivel" />
</div>
