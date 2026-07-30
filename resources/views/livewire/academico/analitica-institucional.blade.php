<div class="space-y-6" x-data="{ tab: 'resumen' }">
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>
        <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-[#006492]/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <flux:badge color="blue">Inteligencia institucional</flux:badge>
                    <flux:badge color="green">Histórico por ciclo</flux:badge>
                    @if ($ultimo_snapshot_at)
                        <flux:badge color="zinc">Última instantánea: {{ $ultimo_snapshot_at }}</flux:badge>
                    @endif
                </div>
                <flux:heading size="xl">Analítica institucional avanzada</flux:heading>
                <flux:text variant="subtle" class="mt-2">
                    Compara matrícula, permanencia, rendimiento, riesgo, integridad, documentación y horarios usando el historial académico por ciclo.
                </flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('misrutas.analitica.reporte', array_merge(['formato' => 'pdf'], $this->parametrosReporte))" target="_blank" icon="document-arrow-down">
                    PDF ejecutivo
                </flux:button>
                <flux:button :href="route('misrutas.analitica.reporte', array_merge(['formato' => 'excel'], $this->parametrosReporte))" icon="table-cells">
                    Excel detallado
                </flux:button>
                @if (auth()->user()?->canAccess('analitica.gestionar'))
                    <flux:button wire:click="guardarSnapshot" wire:loading.attr="disabled" wire:target="guardarSnapshot" variant="filled" icon="camera">
                        Guardar instantánea
                    </flux:button>
                @endif
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <flux:heading size="lg">Contexto del análisis</flux:heading>
                <flux:text variant="subtle">Los indicadores se recalculan únicamente al presionar Aplicar filtros.</flux:text>
            </div>
            <flux:button wire:click="limpiarFiltros" variant="ghost" icon="x-mark">Restablecer</flux:button>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo analizado">
                @foreach ($ciclos as $ciclo)
                    <flux:select.option value="{{ $ciclo['id'] }}">{{ $ciclo['nombre'] }}{{ $ciclo['actual'] ? ' · Actual' : '' }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="ciclo_comparacion_id" label="Comparar contra">
                <flux:select.option value="">Sin comparación</flux:select.option>
                @foreach ($ciclos as $ciclo)
                    @if ((string) $ciclo['id'] !== $ciclo_escolar_id)
                        <flux:select.option value="{{ $ciclo['id'] }}">{{ $ciclo['nombre'] }}</flux:select.option>
                    @endif
                @endforeach
            </flux:select>
            <flux:select wire:model.live="nivel_id" label="Nivel">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($niveles as $nivel)
                    <flux:select.option value="{{ $nivel['id'] }}">{{ $nivel['nombre'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="generacion_id" label="Generación">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach ($generaciones as $generacion)
                    <flux:select.option value="{{ $generacion['id'] }}">{{ $generacion['nombre'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="grado_id" label="Grado">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($grados as $grado)
                    <flux:select.option value="{{ $grado['id'] }}">{{ $grado['nombre'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="grupo_id" label="Grupo">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($grupos as $grupo)
                    <flux:select.option value="{{ $grupo['id'] }}">{{ $grupo['nombre'] }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="mt-4 flex justify-end">
            <flux:button wire:click="aplicarFiltros" wire:loading.attr="disabled" wire:target="aplicarFiltros" variant="primary" icon="arrow-path">
                <span wire:loading.remove wire:target="aplicarFiltros">Aplicar filtros</span>
                <span wire:loading wire:target="aplicarFiltros">Calculando indicadores...</span>
            </flux:button>
        </div>
    </section>

    @php($resumen = $datos['resumen'] ?? [])
    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        @foreach ([
            ['titulo' => 'Matrícula', 'valor' => $resumen['matricula'] ?? 0, 'detalle' => ($resumen['variacion_matricula'] ?? 0).'% vs. comparación', 'clase' => 'border-sky-200 bg-sky-50 dark:border-sky-900 dark:bg-sky-950/25', 'texto' => 'text-sky-700 dark:text-sky-300'],
            ['titulo' => 'Permanencia', 'valor' => ($resumen['permanencia'] ?? 0).'%', 'detalle' => ($datos['matricula']['salidas'] ?? 0).' salidas', 'clase' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/25', 'texto' => 'text-emerald-700 dark:text-emerald-300'],
            ['titulo' => 'Promoción', 'valor' => ($resumen['promocion'] ?? 0).'%', 'detalle' => ($datos['matricula']['egresados'] ?? 0).' egresados', 'clase' => 'border-lime-200 bg-lime-50 dark:border-lime-900 dark:bg-lime-950/25', 'texto' => 'text-lime-700 dark:text-lime-300'],
            ['titulo' => 'Promedio', 'valor' => number_format((float) ($resumen['promedio'] ?? 0), 2), 'detalle' => ($resumen['reprobacion'] ?? 0).'% reprobación', 'clase' => 'border-violet-200 bg-violet-50 dark:border-violet-900 dark:bg-violet-950/25', 'texto' => 'text-violet-700 dark:text-violet-300'],
            ['titulo' => 'Riesgo alto/crítico', 'valor' => ($resumen['riesgo_alto_critico'] ?? 0).'%', 'detalle' => ($datos['riesgo']['alto_critico'] ?? 0).' alumnos', 'clase' => 'border-orange-200 bg-orange-50 dark:border-orange-900 dark:bg-orange-950/25', 'texto' => 'text-orange-700 dark:text-orange-300'],
            ['titulo' => 'Documentación', 'valor' => ($resumen['documentacion'] ?? 0).'%', 'detalle' => ($datos['documentacion']['pendientes'] ?? 0).' pendientes', 'clase' => 'border-cyan-200 bg-cyan-50 dark:border-cyan-900 dark:bg-cyan-950/25', 'texto' => 'text-cyan-700 dark:text-cyan-300'],
        ] as $tarjeta)
            <article class="rounded-2xl border p-4 {{ $tarjeta['clase'] }}">
                <p class="text-xs font-black uppercase tracking-wide {{ $tarjeta['texto'] }}">{{ $tarjeta['titulo'] }}</p>
                <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $tarjeta['valor'] }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-neutral-400">{{ $tarjeta['detalle'] }}</p>
            </article>
        @endforeach
    </section>

    @if (count($datos['alertas'] ?? []) > 0)
        <section class="rounded-3xl border border-rose-200 bg-rose-50/50 p-5 dark:border-rose-900 dark:bg-rose-950/20">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">Alertas directivas</flux:heading>
                    <flux:text variant="subtle">Condiciones que merecen atención según los umbrales institucionales.</flux:text>
                </div>
                <flux:badge color="red">{{ count($datos['alertas']) }}</flux:badge>
            </div>
            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                @foreach ($datos['alertas'] as $indice => $alerta)
                    <article wire:key="alerta-analitica-{{ $indice }}" class="rounded-2xl border border-white/70 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-black text-slate-900 dark:text-white">{{ $alerta['titulo'] }}</p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">{{ $alerta['mensaje'] }}</p>
                            </div>
                            <flux:badge :color="$alerta['severidad'] === 'critico' ? 'red' : ($alerta['severidad'] === 'advertencia' ? 'amber' : 'blue')">
                                {{ ucfirst($alerta['severidad']) }}
                            </flux:badge>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <nav class="flex flex-wrap gap-2 rounded-2xl bg-slate-100 p-2 dark:bg-neutral-900">
        @foreach ([
            'resumen' => 'Resumen y tendencias',
            'rendimiento' => 'Rendimiento',
            'permanencia' => 'Permanencia y control',
            'riesgo' => 'Riesgo y seguimiento',
            'horarios' => 'Docentes y horarios',
        ] as $clave => $etiqueta)
            <button type="button" x-on:click="tab = '{{ $clave }}'" :class="tab === '{{ $clave }}' ? 'bg-white text-[#006492] shadow-sm dark:bg-neutral-800 dark:text-sky-300' : 'text-slate-600 dark:text-neutral-400'" class="rounded-xl px-4 py-2 text-sm font-black transition">
                {{ $etiqueta }}
            </button>
        @endforeach
    </nav>

    <div x-show="tab === 'resumen'" x-transition.opacity class="space-y-5">
        <section class="grid gap-5 xl:grid-cols-2">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div><flux:heading>Tendencia entre ciclos</flux:heading><flux:text variant="subtle">Matrícula, permanencia y promedio.</flux:text></div>
                <div id="analitica-tendencia" wire:ignore class="mt-4 min-h-80"></div>
            </article>
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div><flux:heading>Distribución por nivel</flux:heading><flux:text variant="subtle">Alumnos históricos del ciclo seleccionado.</flux:text></div>
                <div id="analitica-niveles" wire:ignore class="mt-4 min-h-80"></div>
            </article>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-slate-100 p-5 dark:border-neutral-800"><flux:heading>Indicadores por grupo</flux:heading><flux:text variant="subtle">Matrícula, promedio y concentración de riesgo.</flux:text></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-950/60"><tr><th class="px-5 py-3 text-left">Grado/Semestre</th><th class="px-4 py-3 text-left">Grupo</th><th class="px-4 py-3 text-center">Alumnos</th><th class="px-4 py-3 text-center">Promedio</th><th class="px-4 py-3 text-center">Riesgo</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @forelse ($datos['grupos'] ?? [] as $fila)
                            <tr><td class="px-5 py-4 font-bold">{{ $fila['grado'] }}</td><td class="px-4 py-4">{{ $fila['grupo'] }}</td><td class="px-4 py-4 text-center font-black">{{ $fila['alumnos'] }}</td><td class="px-4 py-4 text-center">{{ number_format($fila['promedio'], 2) }}</td><td class="px-4 py-4 text-center"><flux:badge :color="$fila['riesgo_porcentaje'] >= 20 ? 'red' : ($fila['riesgo_porcentaje'] >= 10 ? 'amber' : 'green')">{{ $fila['riesgo'] }} · {{ $fila['riesgo_porcentaje'] }}%</flux:badge></td></tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No hay grupos para el contexto seleccionado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div x-show="tab === 'rendimiento'" x-cloak x-transition.opacity class="grid gap-5 xl:grid-cols-[1.2fr_1.8fr]">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
            @foreach ([
                ['Evaluaciones numéricas', $datos['rendimiento']['evaluaciones'] ?? 0, 'blue'],
                ['Aprobadas', $datos['rendimiento']['aprobadas'] ?? 0, 'green'],
                ['Reprobadas', $datos['rendimiento']['reprobadas'] ?? 0, 'red'],
                ['Registros pendientes', $datos['rendimiento']['pendientes'] ?? 0, 'amber'],
            ] as $item)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"><flux:badge :color="$item[2]">{{ $item[0] }}</flux:badge><p class="mt-3 text-3xl font-black">{{ $item[1] }}</p></article>
            @endforeach
        </section>
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <flux:heading>Materias con mayor reprobación</flux:heading>
            <flux:text variant="subtle">Porcentaje calculado sobre evaluaciones numéricas registradas.</flux:text>
            <div id="analitica-rendimiento" wire:ignore class="mt-4 min-h-96"></div>
        </section>
    </div>

    <div x-show="tab === 'permanencia'" x-cloak x-transition.opacity class="grid gap-5 xl:grid-cols-3">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900 xl:col-span-2">
            <flux:heading>Trayectoria del ciclo</flux:heading>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['En curso', $datos['matricula']['en_curso'] ?? 0], ['Promovidos', $datos['matricula']['promovidos'] ?? 0], ['Egresados', $datos['matricula']['egresados'] ?? 0], ['No promovidos', $datos['matricula']['no_promovidos'] ?? 0],
                    ['Traslados', $datos['matricula']['traslados'] ?? 0], ['Bajas', $datos['matricula']['bajas'] ?? 0], ['No reinscritos', $datos['matricula']['no_reinscritos'] ?? 0], ['Reingresos', $datos['matricula']['reingresos'] ?? 0],
                ] as $item)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60"><p class="text-xs font-bold text-slate-500">{{ $item[0] }}</p><p class="mt-1 text-2xl font-black">{{ $item[1] }}</p></div>
                @endforeach
            </div>
        </section>
        <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:heading>Control escolar</flux:heading>
            <div class="mt-4 space-y-3">
                <div class="flex justify-between rounded-xl bg-slate-50 p-3 dark:bg-neutral-950/60"><span>Casos de integridad abiertos</span><strong>{{ $datos['integridad']['abiertos'] ?? 0 }}</strong></div>
                <div class="flex justify-between rounded-xl bg-slate-50 p-3 dark:bg-neutral-950/60"><span>Casos críticos</span><strong class="text-rose-600">{{ $datos['integridad']['criticos'] ?? 0 }}</strong></div>
                <div class="flex justify-between rounded-xl bg-slate-50 p-3 dark:bg-neutral-950/60"><span>Documentos esperados</span><strong>{{ $datos['documentacion']['esperados'] ?? 0 }}</strong></div>
                <div class="flex justify-between rounded-xl bg-slate-50 p-3 dark:bg-neutral-950/60"><span>Documentos pendientes</span><strong>{{ $datos['documentacion']['pendientes'] ?? 0 }}</strong></div>
            </div>
        </section>
    </div>

    <div x-show="tab === 'riesgo'" x-cloak x-transition.opacity class="grid gap-5 xl:grid-cols-2">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:heading>Distribución del semáforo</flux:heading>
            <div id="analitica-riesgo" wire:ignore class="mt-4 min-h-80"></div>
        </section>
        <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:heading>Seguimiento e intervención</flux:heading>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['Casos activos', $datos['seguimiento']['activos'] ?? 0], ['Casos cerrados', $datos['seguimiento']['cerrados'] ?? 0], ['Sin responsable', $datos['seguimiento']['sin_responsable'] ?? 0], ['Revisiones vencidas', $datos['seguimiento']['revisiones_vencidas'] ?? 0], ['Acciones vencidas', $datos['seguimiento']['acciones_vencidas'] ?? 0], ['Acciones completadas', $datos['seguimiento']['acciones_completadas'] ?? 0],
                ] as $item)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60"><p class="text-xs font-bold text-slate-500">{{ $item[0] }}</p><p class="mt-1 text-2xl font-black">{{ $item[1] }}</p></div>
                @endforeach
            </div>
            <p class="mt-4 text-sm text-slate-500">Tiempo promedio de atención de casos cerrados: <strong>{{ $datos['seguimiento']['tiempo_atencion_dias'] ?? 0 }} días</strong>.</p>
        </section>
    </div>

    <div x-show="tab === 'horarios'" x-cloak x-transition.opacity class="grid gap-5 xl:grid-cols-[1fr_2fr]">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:heading>Versiones y publicación</flux:heading>
            <div class="mt-4 space-y-3">
                @foreach ([
                    ['Versiones', $datos['horarios']['versiones'] ?? 0], ['Publicadas', $datos['horarios']['publicadas'] ?? 0], ['Borradores', $datos['horarios']['borradores'] ?? 0], ['Puntaje promedio', $datos['horarios']['puntaje_promedio'] ?? 0], ['Conflictos críticos', $datos['horarios']['conflictos_criticos'] ?? 0], ['Traslapes excepcionales', $datos['horarios']['traslapes_excepcionales'] ?? 0],
                ] as $item)
                    <div class="flex justify-between rounded-xl bg-slate-50 p-3 dark:bg-neutral-950/60"><span>{{ $item[0] }}</span><strong>{{ $item[1] }}</strong></div>
                @endforeach
            </div>
        </section>
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-slate-100 p-5 dark:border-neutral-800"><flux:heading>Carga docente publicada</flux:heading><flux:text variant="subtle">Bloques, grupos y simultaneidades del horario vigente.</flux:text></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-950/60"><tr><th class="px-5 py-3 text-left">Docente</th><th class="px-4 py-3 text-center">Bloques</th><th class="px-4 py-3 text-center">Grupos</th><th class="px-4 py-3 text-center">Compartidas</th><th class="px-4 py-3 text-center">Excepcionales</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @forelse ($datos['carga_docente'] ?? [] as $docente)
                            <tr><td class="px-5 py-4 font-bold">{{ $docente['docente'] }}</td><td class="px-4 py-4 text-center">{{ $docente['bloques'] }}</td><td class="px-4 py-4 text-center">{{ $docente['grupos'] }}</td><td class="px-4 py-4 text-center">{{ $docente['compartidas'] }}</td><td class="px-4 py-4 text-center">{{ $docente['excepcionales'] }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No hay una versión publicada para el contexto.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        (() => {
            const initialData = @js([
                'tendencia' => $datos['tendencia_ciclos'] ?? [],
                'niveles' => $datos['distribucion_niveles'] ?? [],
                'riesgo' => $datos['riesgo'] ?? [],
                'rendimiento' => $datos['rendimiento']['materias_reprobacion'] ?? [],
            ]);
            window.__analiticaCharts = window.__analiticaCharts || {};

            function destroy(name) {
                if (window.__analiticaCharts[name]) {
                    window.__analiticaCharts[name].destroy();
                    delete window.__analiticaCharts[name];
                }
            }

            function render(data) {
                if (typeof ApexCharts === 'undefined') return;
                const textColor = document.documentElement.classList.contains('dark') ? '#d4d4d8' : '#475569';

                const tendencia = document.querySelector('#analitica-tendencia');
                if (tendencia) {
                    destroy('tendencia');
                    window.__analiticaCharts.tendencia = new ApexCharts(tendencia, {
                        chart: { type: 'line', height: 315, toolbar: { show: false } },
                        series: [
                            { name: 'Matrícula', type: 'column', data: (data.tendencia || []).map(x => x.matricula) },
                            { name: 'Permanencia %', type: 'line', data: (data.tendencia || []).map(x => x.permanencia) },
                            { name: 'Promedio', type: 'line', data: (data.tendencia || []).map(x => x.promedio) },
                        ],
                        xaxis: { categories: (data.tendencia || []).map(x => x.ciclo), labels: { style: { colors: textColor } } },
                        yaxis: [{ title: { text: 'Alumnos' } }, { opposite: true, min: 0, max: 100, title: { text: '% / promedio' } }],
                        stroke: { width: [0, 3, 3], curve: 'smooth' },
                        dataLabels: { enabled: false },
                        colors: ['#006492', '#88AC2E', '#7c3aed'],
                        legend: { labels: { colors: textColor } },
                        grid: { borderColor: '#e2e8f0' },
                    });
                    window.__analiticaCharts.tendencia.render();
                }

                const niveles = document.querySelector('#analitica-niveles');
                if (niveles) {
                    destroy('niveles');
                    window.__analiticaCharts.niveles = new ApexCharts(niveles, {
                        chart: { type: 'bar', height: 315, toolbar: { show: false } },
                        series: [{ name: 'Alumnos', data: (data.niveles || []).map(x => x.total) }],
                        xaxis: { categories: (data.niveles || []).map(x => x.nivel), labels: { style: { colors: textColor } } },
                        plotOptions: { bar: { borderRadius: 8, distributed: true } },
                        dataLabels: { enabled: true },
                        colors: ['#006492', '#88AC2E', '#7c3aed', '#f59e0b'],
                        legend: { show: false },
                    });
                    window.__analiticaCharts.niveles.render();
                }

                const riesgo = document.querySelector('#analitica-riesgo');
                if (riesgo) {
                    destroy('riesgo');
                    const r = data.riesgo || {};
                    window.__analiticaCharts.riesgo = new ApexCharts(riesgo, {
                        chart: { type: 'donut', height: 315 },
                        labels: ['Bajo', 'Moderado', 'Alto', 'Crítico'],
                        series: [r.bajo || 0, r.moderado || 0, r.alto || 0, r.critico || 0],
                        colors: ['#22c55e', '#f59e0b', '#f97316', '#e11d48'],
                        legend: { position: 'bottom', labels: { colors: textColor } },
                        dataLabels: { enabled: true },
                    });
                    window.__analiticaCharts.riesgo.render();
                }

                const rendimiento = document.querySelector('#analitica-rendimiento');
                if (rendimiento) {
                    destroy('rendimiento');
                    window.__analiticaCharts.rendimiento = new ApexCharts(rendimiento, {
                        chart: { type: 'bar', height: 380, toolbar: { show: false } },
                        series: [{ name: 'Reprobación %', data: (data.rendimiento || []).map(x => x.porcentaje) }],
                        xaxis: { categories: (data.rendimiento || []).map(x => x.materia), max: 100, labels: { style: { colors: textColor } } },
                        plotOptions: { bar: { horizontal: true, borderRadius: 6 } },
                        colors: ['#e11d48'],
                        dataLabels: { enabled: true, formatter: val => `${val}%` },
                    });
                    window.__analiticaCharts.rendimiento.render();
                }
            }

            const boot = () => setTimeout(() => render(initialData), 120);
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true }); else boot();
            document.addEventListener('livewire:navigated', boot);
            window.addEventListener('analitica-actualizada', event => {
                const data = event.detail?.graficas || event.detail?.[0]?.graficas || initialData;
                setTimeout(() => render(data), 120);
            });
        })();
    </script>
</div>
