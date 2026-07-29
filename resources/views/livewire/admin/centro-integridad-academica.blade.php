<div class="min-h-screen bg-slate-50 pb-14 dark:bg-neutral-950">
    @php
        $severidadClases = [
            'critico' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-300',
            'advertencia' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-300',
            'informativo' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/70 dark:bg-sky-950/30 dark:text-sky-300',
        ];
        $estadoClases = [
            'pendiente' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
            'en_revision' => 'bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300',
            'resuelto' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
            'ignorado' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/50 dark:text-orange-300',
        ];
    @endphp

    <header class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-br from-[#006492] via-sky-700 to-[#88AC2E] text-white dark:border-neutral-800">
        <div class="absolute -right-20 -top-24 h-80 w-80 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-20 left-1/3 h-64 w-64 rounded-full bg-lime-300/15 blur-3xl"></div>
        <div class="absolute inset-0 opacity-[0.08]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 24px 24px;"></div>

        <div class="relative mx-auto max-w-[1700px] px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/15 shadow-xl backdrop-blur">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3 4 7v5c0 4.8 3.2 8.2 8 9 4.8-.8 8-4.2 8-9V7l-8-4Z" />
                            <path d="m8.5 12 2.2 2.2 4.8-5" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">Centro de integridad académica</h1>
                            <span class="rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-bold backdrop-blur">Auditoría permanente</span>
                        </div>
                        <p class="mt-2 max-w-3xl text-sm text-sky-50 sm:text-base">
                            Detecta contradicciones entre inscripciones, ciclos, ubicaciones, calificaciones, matrículas y cierres. Las correcciones delicadas nunca se aplican sin confirmación humana.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                    @if ($ultimoAnalisis)
                        <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-sm backdrop-blur">
                            <p class="text-xs font-bold uppercase tracking-wider text-sky-100">Último análisis</p>
                            <p class="mt-1 font-semibold">{{ optional($ultimoAnalisis->finalizado_at ?? $ultimoAnalisis->iniciado_at)->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-sky-100">{{ number_format($ultimoAnalisis->total_detectados) }} hallazgos · {{ ucfirst($ultimoAnalisis->origen) }}</p>
                        </div>
                    @endif

                    @if ($puedeGestionar)
                        <button type="button" wire:click="analizar" wire:loading.attr="disabled" wire:target="analizar"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 font-black text-[#006492] shadow-xl transition hover:-translate-y-0.5 hover:bg-sky-50 disabled:cursor-wait disabled:opacity-70">
                            <svg wire:loading.remove wire:target="analizar" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5" />
                                <path d="M4 13a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5" />
                            </svg>
                            <span wire:loading.remove wire:target="analizar">Ejecutar análisis</span>
                            <span wire:loading wire:target="analizar">Analizando...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-[1700px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
            @foreach ([
                ['label' => 'Casos abiertos', 'value' => $resumen['abiertos'] ?? 0, 'tone' => 'slate', 'icon' => 'M4 6h16M4 12h16M4 18h10'],
                ['label' => 'Críticos', 'value' => $resumen['criticos'] ?? 0, 'tone' => 'rose', 'icon' => 'M12 9v4m0 4h.01M10.3 3.7 2-1.1 2 1.1 7.5 13a2 2 0 0 1-1.7 3H4a2 2 0 0 1-1.7-3l8-13Z'],
                ['label' => 'Advertencias', 'value' => $resumen['advertencias'] ?? 0, 'tone' => 'amber', 'icon' => 'M12 9v4m0 4h.01M10.3 3.7 2-1.1 2 1.1 7.5 13a2 2 0 0 1-1.7 3H4a2 2 0 0 1-1.7-3l8-13Z'],
                ['label' => 'Informativos', 'value' => $resumen['informativos'] ?? 0, 'tone' => 'sky', 'icon' => 'M12 16v-4m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['label' => 'En revisión', 'value' => $resumen['en_revision'] ?? 0, 'tone' => 'violet', 'icon' => 'M11 4H4v16h16v-7M9 15l11-11 2 2-11 11H9v-2Z'],
                ['label' => 'Resueltos', 'value' => $resumen['resueltos'] ?? 0, 'tone' => 'emerald', 'icon' => 'm5 12 4 4L19 6'],
                ['label' => 'Excepciones', 'value' => $resumen['ignorados'] ?? 0, 'tone' => 'orange', 'icon' => 'M12 3v18M3 12h18'],
            ] as $tarjeta)
                @php
                    $tone = [
                        'slate' => 'border-slate-200 bg-white text-slate-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-slate-200',
                        'rose' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/25 dark:text-rose-300',
                        'amber' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/25 dark:text-amber-300',
                        'sky' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/25 dark:text-sky-300',
                        'violet' => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/60 dark:bg-violet-950/25 dark:text-violet-300',
                        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/25 dark:text-emerald-300',
                        'orange' => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900/60 dark:bg-orange-950/25 dark:text-orange-300',
                    ][$tarjeta['tone']];
                @endphp
                <article class="rounded-2xl border p-4 shadow-sm {{ $tone }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider opacity-75">{{ $tarjeta['label'] }}</p>
                            <p class="mt-2 text-3xl font-black">{{ number_format($tarjeta['value']) }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-black/5 dark:bg-white/10">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $tarjeta['icon'] }}" /></svg>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
                <label class="flex-1">
                    <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">Buscar caso o alumno</span>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input wire:model.live.debounce.350ms="buscar" type="search" placeholder="Folio, matrícula, CURP, nombre o descripción..."
                            class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 outline-none transition focus:border-[#006492] focus:ring-2 focus:ring-sky-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-sky-950" />
                    </div>
                </label>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">Estado</span>
                        <select wire:model.live="estado" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                            <option value="abiertos">Abiertos</option>
                            <option value="todos">Todos</option>
                            <option value="pendiente">Pendientes</option>
                            <option value="en_revision">En revisión</option>
                            <option value="resuelto">Resueltos</option>
                            <option value="ignorado">Ignorados</option>
                        </select>
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">Severidad</span>
                        <select wire:model.live="severidad" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                            <option value="">Todas</option>
                            <option value="critico">Crítico</option>
                            <option value="advertencia">Advertencia</option>
                            <option value="informativo">Informativo</option>
                        </select>
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">Categoría</span>
                        <select wire:model.live="categoria" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                            <option value="">Todas</option>
                            @foreach ($categorias as $opcion)
                                <option value="{{ $opcion }}">{{ ucfirst(str_replace('_', ' ', $opcion)) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">Orden</span>
                        <select wire:model.live="orden" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                            <option value="prioridad">Prioridad</option>
                            <option value="recientes">Más recientes</option>
                            <option value="antiguos">Más antiguos</option>
                            <option value="alumno">Por alumno</option>
                        </select>
                    </label>
                </div>

                <button type="button" wire:click="limpiarFiltros" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">Limpiar</button>
            </div>
        </section>

        <section class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Bandeja de inconsistencias</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Cada hallazgo conserva evidencia, responsable, decisiones y correcciones realizadas.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">{{ number_format($casos->total()) }} casos filtrados</span>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-neutral-800">
                @forelse ($casos as $caso)
                    <button type="button" wire:click="seleccionarCaso({{ $caso->id }})"
                        class="group grid w-full gap-4 px-5 py-4 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/70 lg:grid-cols-[170px_minmax(0,1fr)_190px_160px] lg:items-center">
                        <div class="flex flex-wrap items-center gap-2 lg:block">
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-black uppercase tracking-wide {{ $severidadClases[$caso->severidad] ?? $severidadClases['informativo'] }}">{{ $caso->etiqueta_severidad }}</span>
                            <p class="mt-2 font-mono text-xs font-bold text-slate-500 dark:text-slate-400 lg:block">{{ $caso->folio }}</p>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate font-black text-slate-900 group-hover:text-[#006492] dark:text-white">{{ $caso->titulo }}</h3>
                                @if ($caso->tiene_correccion_sugerida)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Corrección disponible</span>
                                @endif
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm text-slate-500 dark:text-slate-400">{{ $caso->descripcion }}</p>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $caso->nombre_alumno }}</span>
                                @if ($caso->inscripcion?->matricula)<span>Matrícula {{ $caso->inscripcion->matricula }}</span>@endif
                                <span>{{ ucfirst(str_replace('_', ' ', $caso->categoria)) }}</span>
                            </div>
                        </div>

                        <div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $estadoClases[$caso->estado] ?? $estadoClases['pendiente'] }}">{{ $caso->etiqueta_estado }}</span>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $caso->asignado?->name ? 'Responsable: '.$caso->asignado->name : 'Sin responsable asignado' }}</p>
                        </div>

                        <div class="flex items-center justify-between gap-3 lg:justify-end">
                            <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                                <p>{{ optional($caso->ultima_deteccion_at)->diffForHumans() }}</p>
                                <p>{{ $caso->ocurrencias }} detección(es)</p>
                            </div>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition group-hover:border-[#006492] group-hover:bg-sky-50 group-hover:text-[#006492] dark:border-neutral-700 dark:group-hover:bg-sky-950/30">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                            </span>
                        </div>
                    </button>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300">
                            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6"/></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-black text-slate-900 dark:text-white">No hay casos con estos filtros</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ejecuta un análisis o cambia los filtros de la bandeja.</p>
                    </div>
                @endforelse
            </div>

            @if ($casos->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-neutral-800">{{ $casos->links() }}</div>
            @endif
        </section>
    </main>

    @if ($seleccionado)
        <div x-data class="fixed inset-0 z-[10010] overflow-y-auto bg-slate-950/70 px-2 py-3 backdrop-blur-sm sm:px-5 sm:py-6" x-on:keydown.escape.window="$wire.cerrarDetalle()">
            <div class="mx-auto max-w-[1450px]">
                <section class="overflow-hidden rounded-[28px] border border-white/20 bg-slate-50 shadow-2xl dark:border-neutral-700 dark:bg-neutral-950">
                    <header class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-[#006492] to-sky-700 px-5 py-6 text-white sm:px-8">
                        <div class="absolute -right-12 -top-20 h-64 w-64 rounded-full bg-[#88AC2E]/25 blur-3xl"></div>
                        <div class="relative flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 font-mono text-xs font-black">{{ $seleccionado->folio }}</span>
                                    <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-black uppercase">{{ $seleccionado->etiqueta_severidad }}</span>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-[#006492]">{{ $seleccionado->etiqueta_estado }}</span>
                                </div>
                                <h2 class="mt-4 max-w-5xl text-2xl font-black tracking-tight sm:text-3xl">{{ $seleccionado->titulo }}</h2>
                                <p class="mt-2 max-w-5xl text-sm text-sky-100 sm:text-base">{{ $seleccionado->descripcion }}</p>
                            </div>
                            <button type="button" wire:click="cerrarDetalle" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 transition hover:bg-white/20" aria-label="Cerrar detalle">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
                            </button>
                        </div>
                    </header>

                    <div class="grid gap-6 p-4 sm:p-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(360px,0.8fr)]">
                        <div class="space-y-6">
                            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-widest text-[#006492]">Persona o contexto afectado</p>
                                        <h3 class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ $seleccionado->nombre_alumno }}</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            {{ $seleccionado->inscripcion?->matricula ? 'Matrícula '.$seleccionado->inscripcion->matricula : 'Caso general del sistema' }}
                                            @if ($seleccionado->nivel) · {{ $seleccionado->nivel->nombre }} @endif
                                            @if ($seleccionado->cicloEscolar) · {{ $seleccionado->cicloEscolar->inicio_anio }}-{{ $seleccionado->cicloEscolar->fin_anio }} @endif
                                        </p>
                                    </div>
                                    @if ($seleccionado->inscripcion_id && auth()->user()?->canAccess('alumnos.consultar'))
                                        <button type="button" wire:click="verTrayectoria({{ $seleccionado->inscripcion_id }})" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#006492] to-[#88AC2E] px-4 py-2.5 text-sm font-black text-white shadow-lg">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg>
                                            Ver trayectoria
                                        </button>
                                    @endif
                                </div>
                            </section>

                            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">Evidencia encontrada</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Datos utilizados por la regla <span class="font-mono">{{ $seleccionado->regla }}</span>.</p>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    @foreach (($seleccionado->evidencia ?? []) as $clave => $valor)
                                        @continue($clave === 'alumno')
                                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-700 dark:bg-neutral-950">
                                            <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $clave)) }}</p>
                                            @if (is_array($valor))
                                                <pre class="mt-2 max-h-72 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{ json_encode($valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            @elseif (is_bool($valor))
                                                <p class="mt-2 font-bold text-slate-900 dark:text-white">{{ $valor ? 'Sí' : 'No' }}</p>
                                            @else
                                                <p class="mt-2 break-words font-bold text-slate-900 dark:text-white">{{ filled($valor) ? $valor : '—' }}</p>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </section>

                            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">Historial del caso</h3>
                                <div class="relative mt-5 space-y-5 pl-7 before:absolute before:bottom-2 before:left-[9px] before:top-2 before:w-px before:bg-slate-200 dark:before:bg-neutral-700">
                                    @forelse ($seleccionado->eventos as $evento)
                                        <article class="relative">
                                            <span class="absolute -left-7 top-1 h-[18px] w-[18px] rounded-full border-4 border-white bg-[#006492] shadow dark:border-neutral-900"></span>
                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-700 dark:bg-neutral-950">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <p class="font-black text-slate-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $evento->tipo)) }}</p>
                                                    <time class="text-xs text-slate-500 dark:text-slate-400">{{ optional($evento->created_at)->format('d/m/Y H:i') }}</time>
                                                </div>
                                                @if ($evento->descripcion)<p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $evento->descripcion }}</p>@endif
                                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $evento->usuario?->name ?? 'Proceso automático' }}</p>
                                            </div>
                                        </article>
                                    @empty
                                        <p class="text-sm text-slate-500">Sin eventos registrados.</p>
                                    @endforelse
                                </div>
                            </section>
                        </div>

                        <aside class="space-y-5">
                            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                                <h3 class="font-black text-slate-900 dark:text-white">Gestión del caso</h3>
                                <div class="mt-4 space-y-4">
                                    <label>
                                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">Responsable</span>
                                        <select wire:model="asignadoA" @disabled(! $puedeGestionar) class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                                            <option value="">Sin asignar</option>
                                            @foreach ($usuarios as $usuario)<option value="{{ $usuario->id }}">{{ $usuario->name }}</option>@endforeach
                                        </select>
                                    </label>
                                    @if ($puedeGestionar)
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <button type="button" wire:click="guardarAsignacion" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">Guardar responsable</button>
                                            @if ($seleccionado->estado === 'pendiente')
                                                <button type="button" wire:click="iniciarRevision" class="rounded-xl bg-violet-600 px-3 py-2.5 text-sm font-black text-white hover:bg-violet-700">Tomar para revisión</button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </section>

                            @if ($seleccionado->tiene_correccion_sugerida)
                                <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900/60 dark:bg-emerald-950/25">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 14 6-6 4 4 6-6M14 6h6v6"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Corrección asistida</p>
                                            <h3 class="mt-1 font-black text-emerald-950 dark:text-emerald-100">{{ data_get($seleccionado->correccion_sugerida, 'etiqueta') }}</h3>
                                            <p class="mt-2 text-sm text-emerald-800 dark:text-emerald-200">{{ data_get($seleccionado->correccion_sugerida, 'descripcion') }}</p>
                                        </div>
                                    </div>

                                    @if ($puedeGestionar && in_array($seleccionado->estado, ['pendiente', 'en_revision']))
                                        <div class="mt-5 space-y-3 border-t border-emerald-200 pt-4 dark:border-emerald-900/60">
                                            <label>
                                                <span class="mb-1.5 block text-sm font-bold text-emerald-950 dark:text-emerald-100">Motivo administrativo</span>
                                                <textarea wire:model="motivo" rows="3" class="w-full rounded-xl border border-emerald-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-emerald-800 dark:bg-neutral-950 dark:text-white" placeholder="Explica por qué se autoriza esta corrección..."></textarea>
                                                @error('motivo')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                                            </label>
                                            <label>
                                                <span class="mb-1.5 block text-sm font-bold text-emerald-950 dark:text-emerald-100">Confirmación</span>
                                                <input wire:model="confirmacionCorreccion" type="text" class="w-full rounded-xl border border-emerald-300 bg-white px-3 py-2.5 text-sm uppercase dark:border-emerald-800 dark:bg-neutral-950 dark:text-white" placeholder="Escribe CORREGIR" />
                                                @error('confirmacionCorreccion')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                                            </label>
                                            <button type="button" wire:click="aplicarCorreccion" wire:loading.attr="disabled" wire:target="aplicarCorreccion" class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white shadow-lg hover:bg-emerald-700 disabled:opacity-60">
                                                <span wire:loading.remove wire:target="aplicarCorreccion">Aplicar con respaldo reversible</span>
                                                <span wire:loading wire:target="aplicarCorreccion">Aplicando...</span>
                                            </button>
                                        </div>
                                    @endif
                                </section>
                            @endif

                            @if ($puedeGestionar && in_array($seleccionado->estado, ['pendiente', 'en_revision']))
                                <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                                    <h3 class="font-black text-slate-900 dark:text-white">Cerrar sin corrección automática</h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <textarea wire:model="motivoResolucion" rows="3" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white" placeholder="Describe la corrección manual realizada..."></textarea>
                                            @error('motivoResolucion')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                                            <button type="button" wire:click="marcarResuelto" class="mt-2 w-full rounded-xl bg-[#006492] px-4 py-2.5 text-sm font-black text-white">Marcar como resuelto</button>
                                        </div>
                                        <div class="border-t border-slate-200 pt-4 dark:border-neutral-800">
                                            <textarea wire:model="motivoIgnorar" rows="3" class="w-full rounded-xl border border-orange-300 bg-orange-50 px-3 py-2.5 text-sm dark:border-orange-900 dark:bg-orange-950/20 dark:text-white" placeholder="Justifica por qué este caso es una excepción válida..."></textarea>
                                            @error('motivoIgnorar')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                                            <button type="button" wire:click="ignorar" class="mt-2 w-full rounded-xl border border-orange-300 px-4 py-2.5 text-sm font-black text-orange-700 hover:bg-orange-50 dark:border-orange-900 dark:text-orange-300 dark:hover:bg-orange-950/30">Ignorar justificadamente</button>
                                        </div>
                                    </div>
                                </section>
                            @elseif ($puedeGestionar)
                                <button type="button" wire:click="reabrir" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-black text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">Reabrir caso</button>
                            @endif

                            @if ($seleccionado->correcciones->isNotEmpty())
                                <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                                    <h3 class="font-black text-slate-900 dark:text-white">Correcciones y respaldos</h3>
                                    <div class="mt-4 space-y-3">
                                        @foreach ($seleccionado->correcciones as $correccion)
                                            <article class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-700 dark:bg-neutral-950">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="font-mono text-xs font-black text-slate-600 dark:text-slate-300">#{{ $correccion->id }} · {{ $correccion->clave }}</p>
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase {{ $correccion->estado === 'aplicada' ? 'bg-emerald-100 text-emerald-700' : 'bg-violet-100 text-violet-700' }}">{{ $correccion->estado }}</span>
                                                </div>
                                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Aplicada por {{ $correccion->usuarioAplico?->name ?? 'sistema' }} · {{ optional($correccion->aplicada_at)->format('d/m/Y H:i') }}</p>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Firma {{ substr($correccion->firma, 0, 14) }}…</p>

                                                @if ($puedeGestionar && $correccion->puede_revertirse)
                                                    <div class="mt-3 border-t border-slate-200 pt-3 dark:border-neutral-700">
                                                        <textarea wire:model="motivoReversion" rows="2" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs dark:border-neutral-700 dark:bg-neutral-900 dark:text-white" placeholder="Motivo de reversión..."></textarea>
                                                        <input wire:model="confirmacionReversion" type="text" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs uppercase dark:border-neutral-700 dark:bg-neutral-900 dark:text-white" placeholder="Escribe REVERTIR" />
                                                        @error('motivoReversion')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                                                        @error('confirmacionReversion')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                                                        <button type="button" wire:click="revertirCorreccion({{ $correccion->id }})" class="mt-2 w-full rounded-lg bg-rose-600 px-3 py-2 text-xs font-black text-white">Revertir desde respaldo</button>
                                                    </div>
                                                @elseif ($correccion->bloqueo_reversion)
                                                    <p class="mt-2 rounded-lg bg-rose-50 p-2 text-xs font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">{{ $correccion->bloqueo_reversion }}</p>
                                                @endif
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        </aside>
                    </div>
                </section>
            </div>
        </div>
    @endif

    <livewire:alumno.linea-tiempo-academica :key="'linea-tiempo-integridad'" />
</div>
