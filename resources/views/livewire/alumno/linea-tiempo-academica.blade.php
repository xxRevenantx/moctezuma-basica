<div>
    <div wire:loading.flex wire:target="abrir"
        class="fixed inset-0 z-[10030] items-center justify-center bg-slate-950/65 px-4 backdrop-blur-sm">
        <div class="flex items-center gap-4 rounded-[24px] border border-white/20 bg-white px-6 py-5 shadow-2xl dark:bg-neutral-900">
            <span class="h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-[#006492]"></span>
            <div>
                <p class="font-black text-slate-900 dark:text-white">Construyendo trayectoria</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ordenando ciclos, evaluaciones y movimientos...</p>
            </div>
        </div>
    </div>

    @if ($abierto && $trayectoria)
        @php
            $alumno = $trayectoria['alumno'];
            $resumen = $trayectoria['resumen'];
            $integridad = $trayectoria['integridad'];
            $ciclos = collect($trayectoria['ciclos']);
            $cicloActual = $ciclos->firstWhere('es_actual', true) ?? $ciclos->last();

            $tonos = [
                'sky' => [
                    'borde' => 'border-sky-200 dark:border-sky-900/60',
                    'fondo' => 'bg-sky-50 dark:bg-sky-950/25',
                    'texto' => 'text-sky-700 dark:text-sky-300',
                    'punto' => 'bg-sky-500 ring-sky-100 dark:ring-sky-950',
                    'gradiente' => 'from-sky-500 to-blue-600',
                ],
                'blue' => [
                    'borde' => 'border-blue-200 dark:border-blue-900/60',
                    'fondo' => 'bg-blue-50 dark:bg-blue-950/25',
                    'texto' => 'text-blue-700 dark:text-blue-300',
                    'punto' => 'bg-blue-500 ring-blue-100 dark:ring-blue-950',
                    'gradiente' => 'from-blue-500 to-indigo-600',
                ],
                'emerald' => [
                    'borde' => 'border-emerald-200 dark:border-emerald-900/60',
                    'fondo' => 'bg-emerald-50 dark:bg-emerald-950/25',
                    'texto' => 'text-emerald-700 dark:text-emerald-300',
                    'punto' => 'bg-emerald-500 ring-emerald-100 dark:ring-emerald-950',
                    'gradiente' => 'from-emerald-500 to-green-600',
                ],
                'violet' => [
                    'borde' => 'border-violet-200 dark:border-violet-900/60',
                    'fondo' => 'bg-violet-50 dark:bg-violet-950/25',
                    'texto' => 'text-violet-700 dark:text-violet-300',
                    'punto' => 'bg-violet-500 ring-violet-100 dark:ring-violet-950',
                    'gradiente' => 'from-violet-500 to-purple-600',
                ],
                'orange' => [
                    'borde' => 'border-orange-200 dark:border-orange-900/60',
                    'fondo' => 'bg-orange-50 dark:bg-orange-950/25',
                    'texto' => 'text-orange-700 dark:text-orange-300',
                    'punto' => 'bg-orange-500 ring-orange-100 dark:ring-orange-950',
                    'gradiente' => 'from-orange-500 to-amber-600',
                ],
                'amber' => [
                    'borde' => 'border-amber-200 dark:border-amber-900/60',
                    'fondo' => 'bg-amber-50 dark:bg-amber-950/25',
                    'texto' => 'text-amber-700 dark:text-amber-300',
                    'punto' => 'bg-amber-500 ring-amber-100 dark:ring-amber-950',
                    'gradiente' => 'from-amber-500 to-yellow-600',
                ],
                'rose' => [
                    'borde' => 'border-rose-200 dark:border-rose-900/60',
                    'fondo' => 'bg-rose-50 dark:bg-rose-950/25',
                    'texto' => 'text-rose-700 dark:text-rose-300',
                    'punto' => 'bg-rose-500 ring-rose-100 dark:ring-rose-950',
                    'gradiente' => 'from-rose-500 to-red-600',
                ],
                'indigo' => [
                    'borde' => 'border-indigo-200 dark:border-indigo-900/60',
                    'fondo' => 'bg-indigo-50 dark:bg-indigo-950/25',
                    'texto' => 'text-indigo-700 dark:text-indigo-300',
                    'punto' => 'bg-indigo-500 ring-indigo-100 dark:ring-indigo-950',
                    'gradiente' => 'from-indigo-500 to-violet-600',
                ],
                'slate' => [
                    'borde' => 'border-slate-200 dark:border-slate-700',
                    'fondo' => 'bg-slate-50 dark:bg-slate-900/60',
                    'texto' => 'text-slate-700 dark:text-slate-300',
                    'punto' => 'bg-slate-500 ring-slate-100 dark:ring-slate-800',
                    'gradiente' => 'from-slate-500 to-slate-700',
                ],
            ];
        @endphp

        <div x-data="{
                filtro: 'todos',
                cicloAbierto: @js((string) ($cicloActual['id'] ?? '')),
                panelIntegridad: false,
                visible(tipo) {
                    if (this.filtro === 'todos') return true;

                    const grupos = {
                        academico: ['preinscripcion', 'ingreso', 'ubicacion', 'matricula', 'cierre', 'proyeccion'],
                        evaluacion: ['calificaciones', 'correccion'],
                        documentos: ['documento'],
                        movimientos: ['movimiento', 'cambio'],
                    };

                    return (grupos[this.filtro] || []).includes(tipo);
                },
                alternarCiclo(id) {
                    this.cicloAbierto = this.cicloAbierto === String(id) ? '' : String(id);
                }
            }"
            x-on:keydown.escape.window="$wire.cerrar()"
            class="fixed inset-0 z-[10020] overflow-y-auto bg-slate-950/70 px-2 py-3 backdrop-blur-md sm:px-5 sm:py-6">
            <div class="mx-auto min-h-full max-w-[1500px]">
                <section class="overflow-hidden rounded-[28px] border border-white/20 bg-slate-50 shadow-2xl shadow-slate-950/40 dark:border-neutral-700 dark:bg-neutral-950 sm:rounded-[36px]">
                    {{-- Encabezado institucional --}}
                    <header class="relative overflow-hidden bg-gradient-to-br from-[#006492] via-sky-700 to-[#88AC2E] px-5 py-6 text-white sm:px-8 sm:py-8 lg:px-10">
                        <div class="absolute -right-16 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
                        <div class="absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-lime-300/15 blur-3xl"></div>
                        <div class="absolute inset-0 opacity-[0.08]"
                            style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 24px 24px;"></div>

                        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
                            <div class="flex min-w-0 items-start gap-4 sm:gap-5">
                                <div class="relative shrink-0">
                                    @if ($alumno['foto_url'])
                                        <img src="{{ $alumno['foto_url'] }}" alt="Foto de {{ $alumno['nombre'] }}"
                                            class="h-20 w-20 rounded-[24px] object-cover ring-4 ring-white/25 shadow-2xl sm:h-24 sm:w-24 sm:rounded-[28px]">
                                    @else
                                        <div class="flex h-20 w-20 items-center justify-center rounded-[24px] bg-white/15 text-2xl font-black ring-4 ring-white/20 backdrop-blur sm:h-24 sm:w-24 sm:rounded-[28px] sm:text-3xl">
                                            {{ $alumno['iniciales'] }}
                                        </div>
                                    @endif

                                    <span class="absolute -bottom-2 -right-2 flex h-8 w-8 items-center justify-center rounded-xl bg-white text-[#006492] shadow-lg ring-2 ring-white/40">
                                        <flux:icon.academic-cap class="h-5 w-5" />
                                    </span>
                                </div>

                                <div class="min-w-0 pt-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-black uppercase tracking-[0.15em] ring-1 ring-white/20 backdrop-blur">
                                            <flux:icon.history class="h-3.5 w-3.5" />
                                            Línea del tiempo académica
                                        </span>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-[#006492] shadow-sm">
                                            {{ $alumno['estatus_etiqueta'] }}
                                        </span>
                                    </div>

                                    <h2 class="mt-3 truncate text-2xl font-black uppercase tracking-tight sm:text-3xl lg:text-4xl">
                                        {{ $alumno['nombre'] }}
                                    </h2>

                                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-white/85">
                                        <span class="inline-flex items-center gap-2">
                                            <flux:icon.identification class="h-4 w-4" />
                                            {{ $alumno['matricula'] }}
                                        </span>
                                        <span class="inline-flex items-center gap-2">
                                            <flux:icon.map-pin class="h-4 w-4" />
                                            {{ $alumno['ubicacion_actual'] }}
                                        </span>
                                        <span class="inline-flex items-center gap-2">
                                            <flux:icon.calendar-days class="h-4 w-4" />
                                            Ciclo actual: {{ $alumno['ciclo_actual'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2 self-end xl:self-center">
                                <button type="button" x-on:click="panelIntegridad = !panelIntegridad"
                                    class="inline-flex items-center gap-2 rounded-2xl bg-white/15 px-4 py-2.5 text-xs font-black ring-1 ring-white/20 backdrop-blur transition hover:bg-white/25">
                                    @if ($integridad['estado'] === 'correcto')
                                        <flux:icon.shield-check class="h-4 w-4" />
                                    @else
                                        <flux:icon.shield-exclamation class="h-4 w-4" />
                                    @endif
                                    {{ $integridad['etiqueta'] }}
                                </button>

                                <button type="button" wire:click="cerrar"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950/20 text-white ring-1 ring-white/20 transition hover:rotate-90 hover:bg-slate-950/35"
                                    aria-label="Cerrar trayectoria">
                                    <flux:icon.x-mark class="h-6 w-6" />
                                </button>
                            </div>
                        </div>
                    </header>

                    {{-- Panel de integridad --}}
                    <div x-cloak x-show="panelIntegridad" x-collapse
                        class="border-b border-slate-200 bg-white px-5 py-5 dark:border-neutral-800 dark:bg-neutral-900 sm:px-8 lg:px-10">
                        <div class="grid gap-4 lg:grid-cols-[0.8fr_2.2fr]">
                            <div class="rounded-[22px] border {{ $integridad['estado'] === 'correcto' ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/60 dark:bg-emerald-950/30' : 'border-amber-200 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/30' }} p-5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $integridad['estado'] === 'correcto' ? 'bg-emerald-500' : 'bg-amber-500' }} text-white shadow-lg">
                                        @if ($integridad['estado'] === 'correcto')
                                            <flux:icon.shield-check class="h-6 w-6" />
                                        @else
                                            <flux:icon.shield-exclamation class="h-6 w-6" />
                                        @endif
                                    </span>
                                    <div>
                                        <h3 class="font-black text-slate-900 dark:text-white">{{ $integridad['etiqueta'] }}</h3>
                                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ $integridad['mensaje'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @forelse ($integridad['alertas'] as $alerta)
                                    <article class="rounded-[20px] border border-slate-200 bg-slate-50 p-4 dark:border-neutral-700 dark:bg-neutral-950/70">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $alerta['nivel'] === 'critico' ? 'bg-rose-500' : ($alerta['nivel'] === 'advertencia' ? 'bg-amber-500' : 'bg-sky-500') }}"></span>
                                            <div>
                                                <p class="text-sm font-black text-slate-800 dark:text-white">{{ $alerta['titulo'] }}</p>
                                                <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $alerta['detalle'] }}</p>
                                            </div>
                                        </div>
                                    </article>
                                @empty
                                    <div class="sm:col-span-2 xl:col-span-3 rounded-[20px] border border-dashed border-emerald-300 bg-emerald-50 p-5 text-sm font-bold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
                                        No se detectaron inconsistencias en los registros mostrados.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-5 sm:px-7 sm:py-7 lg:px-10">
                        {{-- Resumen visual --}}
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 xl:grid-cols-8">
                            @php
                                $tarjetas = [
                                    ['icono' => 'calendar-days', 'valor' => $resumen['ciclos'], 'etiqueta' => 'Ciclos', 'clase' => 'from-sky-500 to-blue-600'],
                                    ['icono' => 'clock', 'valor' => $resumen['anos_trayectoria'], 'etiqueta' => 'Años', 'clase' => 'from-indigo-500 to-violet-600'],
                                    ['icono' => 'academic-cap', 'valor' => $resumen['niveles'], 'etiqueta' => 'Niveles', 'clase' => 'from-[#006492] to-cyan-500'],
                                    ['icono' => 'arrow-up-tray', 'valor' => $resumen['promociones'], 'etiqueta' => 'Promociones', 'clase' => 'from-[#88AC2E] to-emerald-500'],
                                    ['icono' => 'chart-bar-square', 'valor' => $resumen['promedio_global'], 'etiqueta' => 'Promedio', 'clase' => 'from-blue-500 to-cyan-500'],
                                    ['icono' => 'document-text', 'valor' => $resumen['documentos'], 'etiqueta' => 'Documentos', 'clase' => 'from-emerald-500 to-teal-600'],
                                    ['icono' => 'arrows-right-left', 'valor' => $resumen['movimientos'], 'etiqueta' => 'Movimientos', 'clase' => 'from-violet-500 to-fuchsia-600'],
                                    ['icono' => 'history', 'valor' => $resumen['correcciones'], 'etiqueta' => 'Correcciones', 'clase' => 'from-orange-500 to-amber-500'],
                                ];
                            @endphp

                            @foreach ($tarjetas as $tarjeta)
                                <article class="group relative overflow-hidden rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-xl dark:border-neutral-800 dark:bg-neutral-900">
                                    <div class="absolute -right-5 -top-5 h-16 w-16 rounded-full bg-slate-100 transition group-hover:scale-125 dark:bg-neutral-800"></div>
                                    <span class="relative flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br {{ $tarjeta['clase'] }} text-white shadow-lg">
                                        <x-linea-tiempo-icono :icono="$tarjeta['icono']" class="h-5 w-5" />
                                    </span>
                                    <p class="relative mt-3 text-2xl font-black text-slate-900 dark:text-white">{{ $tarjeta['valor'] }}</p>
                                    <p class="relative text-[11px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $tarjeta['etiqueta'] }}</p>
                                </article>
                            @endforeach
                        </div>

                        {{-- Filtros --}}
                        <div class="mt-6 flex flex-col gap-4 rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="flex items-center gap-2 text-base font-black text-slate-900 dark:text-white">
                                    <flux:icon.history class="h-5 w-5 text-[#006492]" />
                                    Recorrido cronológico
                                </h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Desde el primer registro hasta su situación académica más reciente.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @foreach ([
                                    'todos' => ['Todos', 'rectangle-stack'],
                                    'academico' => ['Vida académica', 'academic-cap'],
                                    'evaluacion' => ['Evaluaciones', 'chart-bar-square'],
                                    'documentos' => ['Documentos', 'document-text'],
                                    'movimientos' => ['Movimientos', 'arrows-right-left'],
                                ] as $valorFiltro => [$textoFiltro, $iconoFiltro])
                                    <button type="button" x-on:click="filtro = '{{ $valorFiltro }}'"
                                        x-bind:class="filtro === '{{ $valorFiltro }}'
                                            ? 'bg-[#006492] text-white shadow-lg shadow-sky-900/20'
                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-neutral-800 dark:text-slate-300 dark:hover:bg-neutral-700'"
                                        class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-black transition">
                                        <x-linea-tiempo-icono :icono="$iconoFiltro" class="h-4 w-4" />
                                        {{ $textoFiltro }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Línea de tiempo --}}
                        <div class="relative mt-7 pb-3 pl-10 sm:pl-16 lg:pl-24">
                            <div class="absolute bottom-6 left-[19px] top-6 w-0.5 bg-gradient-to-b from-[#006492] via-sky-300 to-[#88AC2E] sm:left-[31px] lg:left-[47px]"></div>

                            @foreach ($ciclos as $ciclo)
                                @php
                                    $tono = $tonos[$ciclo['tono']] ?? $tonos['blue'];
                                @endphp

                                <article class="relative pb-8 last:pb-0">
                                    <div class="absolute -left-10 top-5 sm:-left-16 lg:-left-24">
                                        <div class="relative flex h-10 w-10 items-center justify-center rounded-2xl bg-white shadow-xl ring-1 ring-slate-200 dark:bg-neutral-900 dark:ring-neutral-700 sm:h-14 sm:w-14 lg:h-16 lg:w-16">
                                            <span class="absolute inset-2 rounded-xl {{ $tono['punto'] }} ring-4 sm:inset-3"></span>
                                            @if ($ciclo['es_actual'])
                                                <span class="absolute -right-1 -top-1 h-3 w-3 animate-pulse rounded-full bg-[#88AC2E] ring-4 ring-lime-100 dark:ring-lime-950"></span>
                                            @endif
                                        </div>
                                    </div>

                                    <section class="overflow-hidden rounded-[26px] border {{ $tono['borde'] }} bg-white shadow-lg shadow-slate-200/50 transition hover:shadow-xl dark:bg-neutral-900 dark:shadow-none sm:rounded-[30px]">
                                        <button type="button" x-on:click="alternarCiclo(@js((string) $ciclo['id']))"
                                            class="w-full text-left">
                                            <div class="relative overflow-hidden bg-gradient-to-r {{ $tono['gradiente'] }} px-5 py-5 text-white sm:px-7">
                                                <div class="absolute -right-12 -top-16 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                                                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                                    <div>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-black uppercase tracking-widest ring-1 ring-white/20">
                                                                Ciclo {{ $ciclo['ciclo'] }}
                                                            </span>
                                                            @if ($ciclo['es_actual'])
                                                                <span class="rounded-full bg-[#88AC2E] px-3 py-1 text-[11px] font-black uppercase tracking-wide shadow-sm">
                                                                    Actual
                                                                </span>
                                                            @endif
                                                            @if ($ciclo['reconstruido'] || $ciclo['nivel_confianza'] !== 'exacto')
                                                                <span class="rounded-full bg-amber-400 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-amber-950">
                                                                    Contexto reconstruido
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <h4 class="mt-3 text-xl font-black sm:text-2xl">
                                                            {{ $ciclo['ubicacion'] }}
                                                        </h4>

                                                        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1.5 text-xs text-white/85 sm:text-sm">
                                                            <span>Generación {{ $ciclo['generacion'] ?? '—' }}</span>
                                                            <span>Matrícula {{ $ciclo['matricula'] ?: $alumno['matricula'] }}</span>
                                                            <span>{{ $ciclo['fecha_ingreso'] ?? 'Sin fecha de ingreso' }}@if ($ciclo['fecha_salida']) — {{ $ciclo['fecha_salida'] }} @endif</span>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-3 self-end lg:self-center">
                                                        <div class="text-right">
                                                            <p class="text-[10px] font-black uppercase tracking-widest text-white/65">Resultado</p>
                                                            <p class="mt-0.5 text-sm font-black">{{ $ciclo['resultado_etiqueta'] }}</p>
                                                        </div>
                                                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20">
                                                            <flux:icon.chevron-down class="h-5 w-5 transition-transform duration-300"
                                                                x-bind:class="cicloAbierto === @js((string) $ciclo['id']) ? 'rotate-180' : ''" />
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </button>

                                        <div x-cloak x-show="cicloAbierto === @js((string) $ciclo['id'])" x-collapse>
                                            <div class="grid grid-cols-2 gap-3 border-b border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/60 sm:grid-cols-3 lg:grid-cols-6 sm:p-5">
                                                @foreach ([
                                                    ['Calificaciones', $ciclo['estadisticas']['calificaciones'], 'chart-bar-square'],
                                                    ['Periodos', $ciclo['estadisticas']['periodos'], 'calendar-days'],
                                                    ['Promedio', $ciclo['estadisticas']['promedio'], 'trophy'],
                                                    ['Documentos', $ciclo['estadisticas']['documentos'], 'document-text'],
                                                    ['Movimientos', $ciclo['estadisticas']['movimientos'], 'arrows-right-left'],
                                                    ['Correcciones', $ciclo['estadisticas']['correcciones'], 'history'],
                                                ] as [$etiquetaEstadistica, $valorEstadistica, $iconoEstadistica])
                                                    <div class="rounded-2xl border border-slate-200 bg-white p-3.5 dark:border-neutral-800 dark:bg-neutral-900">
                                                        <div class="flex items-center gap-2 text-slate-400">
                                                            <x-linea-tiempo-icono :icono="$iconoEstadistica" class="h-4 w-4" />
                                                            <span class="text-[10px] font-black uppercase tracking-wide">{{ $etiquetaEstadistica }}</span>
                                                        </div>
                                                        <p class="mt-2 text-xl font-black text-slate-900 dark:text-white">{{ $valorEstadistica }}</p>
                                                    </div>
                                                @endforeach
                                            </div>

                                            @if (count($ciclo['asignaciones']) > 1)
                                                <div class="border-b border-slate-200 px-5 py-5 dark:border-neutral-800 sm:px-7">
                                                    <div class="flex items-center gap-2 text-sm font-black text-slate-800 dark:text-white">
                                                        <flux:icon.map-pin class="h-4 w-4 text-violet-500" />
                                                        Ubicaciones dentro del ciclo
                                                    </div>
                                                    <div class="mt-3 flex gap-3 overflow-x-auto pb-2">
                                                        @foreach ($ciclo['asignaciones'] as $asignacion)
                                                            <div class="min-w-[240px] rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900/50 dark:bg-violet-950/25">
                                                                <div class="flex items-start justify-between gap-3">
                                                                    <div>
                                                                        <p class="text-sm font-black text-violet-800 dark:text-violet-200">{{ $asignacion['ubicacion'] }}</p>
                                                                        <p class="mt-1 text-xs text-violet-600 dark:text-violet-300">{{ $asignacion['tipo'] }}</p>
                                                                    </div>
                                                                    @if ($asignacion['es_actual'])
                                                                        <span class="rounded-full bg-violet-600 px-2 py-1 text-[9px] font-black uppercase text-white">Vigente</span>
                                                                    @endif
                                                                </div>
                                                                <p class="mt-3 text-[11px] text-violet-700/80 dark:text-violet-300/80">
                                                                    {{ $asignacion['inicio'] ?? '—' }} @if ($asignacion['fin']) · {{ $asignacion['fin'] }} @endif
                                                                </p>
                                                                @if ($asignacion['motivo'])
                                                                    <p class="mt-2 text-xs leading-relaxed text-violet-700 dark:text-violet-300">{{ $asignacion['motivo'] }}</p>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="p-5 sm:p-7">
                                                <div class="mb-5 flex items-center justify-between gap-4">
                                                    <div>
                                                        <h5 class="font-black text-slate-900 dark:text-white">Actividad del ciclo</h5>
                                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Los acontecimientos se ordenan por fecha de forma ascendente.</p>
                                                    </div>
                                                    <span class="rounded-full {{ $tono['fondo'] }} px-3 py-1 text-xs font-black {{ $tono['texto'] }}">
                                                        {{ count($ciclo['eventos']) }} hitos
                                                    </span>
                                                </div>

                                                <div class="relative ml-3 space-y-4 border-l-2 border-slate-200 pl-7 dark:border-neutral-700">
                                                    @forelse ($ciclo['eventos'] as $evento)
                                                        @php
                                                            $tonoEvento = $tonos[$evento['tono']] ?? $tonos['slate'];
                                                        @endphp
                                                        <article x-cloak x-show="visible(@js($evento['tipo']))" x-transition.opacity
                                                            class="relative rounded-[20px] border {{ $tonoEvento['borde'] }} {{ $tonoEvento['fondo'] }} p-4 sm:p-5">
                                                            <span class="absolute -left-[39px] top-5 flex h-6 w-6 items-center justify-center rounded-lg {{ $tonoEvento['punto'] }} text-white ring-4">
                                                                <x-linea-tiempo-icono :icono="$evento['icono']" class="h-3.5 w-3.5" />
                                                            </span>

                                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                                <div class="min-w-0">
                                                                    <div class="flex flex-wrap items-center gap-2">
                                                                        <span class="text-[10px] font-black uppercase tracking-widest {{ $tonoEvento['texto'] }}">{{ $evento['fecha'] }}</span>
                                                                        <span class="rounded-full bg-white/70 px-2 py-0.5 text-[9px] font-black uppercase tracking-wide text-slate-500 dark:bg-neutral-900/70 dark:text-slate-400">
                                                                            {{ str($evento['tipo'])->replace('_', ' ')->title() }}
                                                                        </span>
                                                                    </div>
                                                                    <h6 class="mt-1.5 text-sm font-black text-slate-900 dark:text-white sm:text-base">{{ $evento['titulo'] }}</h6>
                                                                    <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $evento['descripcion'] }}</p>
                                                                    @if ($evento['detalle'])
                                                                        <p class="mt-2 rounded-xl bg-white/70 px-3 py-2 text-xs leading-relaxed text-slate-500 dark:bg-neutral-900/70 dark:text-slate-400">{{ $evento['detalle'] }}</p>
                                                                    @endif
                                                                </div>

                                                                @if ($evento['actor'])
                                                                    <div class="shrink-0 rounded-xl bg-white/70 px-3 py-2 text-right dark:bg-neutral-900/70">
                                                                        <p class="text-[9px] font-black uppercase tracking-wide text-slate-400">Responsable</p>
                                                                        <p class="mt-0.5 max-w-[190px] truncate text-xs font-bold text-slate-700 dark:text-slate-300">{{ $evento['actor'] }}</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </article>
                                                    @empty
                                                        <div class="rounded-[20px] border border-dashed border-slate-300 bg-slate-50 p-8 text-center dark:border-neutral-700 dark:bg-neutral-950/60">
                                                            <flux:icon.clock class="mx-auto h-8 w-8 text-slate-300" />
                                                            <p class="mt-3 text-sm font-black text-slate-700 dark:text-slate-300">Sin acontecimientos detallados</p>
                                                        </div>
                                                    @endforelse
                                                </div>

                                                @if ($ciclo['eventos_ocultos'] > 0)
                                                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/25 dark:text-amber-300">
                                                        Se resumieron {{ $ciclo['eventos_ocultos'] }} registros repetitivos para mantener clara la línea del tiempo. Los totales del ciclo sí los incluyen.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </section>
                                </article>
                            @endforeach
                        </div>
                    </div>

                    <footer class="flex flex-col gap-3 border-t border-slate-200 bg-white px-5 py-4 text-xs text-slate-500 dark:border-neutral-800 dark:bg-neutral-900 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
                        <span class="inline-flex items-center gap-2">
                            <flux:icon.shield-check class="h-4 w-4 text-[#88AC2E]" />
                            La ubicación actual no reemplaza la evidencia de ciclos anteriores.
                        </span>
                        <div class="flex items-center justify-between gap-4 sm:justify-end">
                            <span>Actualizado: {{ $trayectoria['generado_at'] }}</span>
                            <button type="button" wire:click="cerrar"
                                class="rounded-xl bg-slate-900 px-4 py-2 font-black text-white transition hover:bg-[#006492] dark:bg-white dark:text-slate-900 dark:hover:bg-sky-200">
                                Cerrar
                            </button>
                        </div>
                    </footer>
                </section>
            </div>
        </div>
    @endif
</div>
