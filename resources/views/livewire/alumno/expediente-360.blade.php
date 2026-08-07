@php
    $alumno = $expediente['alumno'];
    $resumen = $expediente['resumen'];
    $integridad = $expediente['integridad'];
    $trayectoria = $expediente['trayectoria'];
    $tabs = [
        'resumen' => ['Resumen', 'squares-2x2'],
        'personales' => ['Datos personales', 'identification'],
        'responsables' => ['Responsables', 'users'],
        'academico' => ['Trayectoria académica', 'academic-cap'],
        'calificaciones' => ['Calificaciones', 'chart-bar-square'],
        'documentos' => ['Documentos', 'folder-open'],
        'movimientos' => ['Movimientos', 'arrows-right-left'],
        'seguimiento' => ['Seguimiento', 'shield-check'],
    ];
@endphp

<div class="space-y-6" x-data>
    <section class="relative overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>
        <div class="absolute -right-28 -top-28 h-80 w-80 rounded-full bg-sky-100/70 blur-3xl dark:bg-sky-950/20"></div>
        <div class="relative p-6 sm:p-8">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                <div class="flex min-w-0 flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="relative shrink-0">
                        @if ($alumno['foto_url'])
                            <img src="{{ $alumno['foto_url'] }}" alt="{{ $alumno['nombre'] }}"
                                class="h-24 w-24 rounded-[28px] object-cover shadow-lg ring-4 ring-white dark:ring-neutral-800 sm:h-28 sm:w-28">
                        @else
                            <div class="flex h-24 w-24 items-center justify-center rounded-[28px] bg-gradient-to-br from-[#006492] to-[#88AC2E] text-3xl font-black text-white shadow-lg ring-4 ring-white dark:ring-neutral-800 sm:h-28 sm:w-28">
                                {{ $alumno['iniciales'] }}
                            </div>
                        @endif
                        <span class="absolute -bottom-1 -right-1 h-5 w-5 rounded-full border-4 border-white {{ in_array($alumno['estatus'], ['activo', 'reingreso'], true) ? 'bg-emerald-500' : 'bg-slate-400' }} dark:border-neutral-900"></span>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $this->claseEstatus($alumno['estatus']) }}">
                                {{ $alumno['estatus_etiqueta'] }}
                            </span>
                            @if ($alumno['archivado'])
                                <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-black text-white dark:bg-slate-100 dark:text-slate-900">Solo lectura</span>
                            @endif
                            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">Expediente 360°</span>
                        </div>
                        <h1 class="mt-3 truncate text-2xl font-black uppercase leading-tight text-slate-900 dark:text-white sm:text-3xl">
                            {{ $alumno['nombre'] }}
                        </h1>
                        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600 dark:text-slate-300">
                            <span><strong class="text-slate-900 dark:text-white">Matrícula:</strong> {{ $alumno['matricula'] }}</span>
                            <span><strong class="text-slate-900 dark:text-white">CURP:</strong> {{ $alumno['curp'] }}</span>
                            <span><strong class="text-slate-900 dark:text-white">Folio:</strong> {{ $alumno['folio'] }}</span>
                        </div>
                        <p class="mt-2 text-sm font-semibold text-[#006492] dark:text-sky-300">
                            {{ collect([$alumno['nivel'], $alumno['grado'], $alumno['grupo'], $alumno['ciclo']])->filter()->implode(' · ') ?: 'Sin ubicación académica actual' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 xl:max-w-md xl:justify-end">
                    <a href="{{ route('misrutas.alumnos') }}" wire:navigate
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:hover:bg-neutral-700">
                        <flux:icon.arrow-left class="h-4 w-4" /> Alumnos
                    </a>
                    <flux:button wire:click="actualizarExpediente" wire:loading.attr="disabled" wire:target="actualizarExpediente" variant="ghost" icon="arrow-path">
                        Actualizar
                    </flux:button>
                    <flux:button wire:click="abrirTrayectoria" variant="filled" icon="history">Línea del tiempo</flux:button>

                    @if ($puedeEditar && ! $alumno['archivado'] && $alumno['nivel_slug'])
                        <a href="{{ route('misrutas.matricula.editar', ['slug_nivel' => $alumno['nivel_slug'], 'inscripcion' => $alumno['id']]) }}" wire:navigate
                            class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-blue-700">
                            <flux:icon.pencil-square class="h-4 w-4" /> Editar matrícula
                        </a>
                    @endif

                    @if ($puedeDocumentos)
                        <a href="{{ route('misrutas.expedientes.show', $alumno['id']) }}"
                            class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#006492] to-[#88AC2E] px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:brightness-110">
                            <flux:icon.folder-open class="h-4 w-4" /> Expediente digital
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @foreach ([
            ['titulo' => 'Ciclos cursados', 'valor' => $resumen['ciclos'], 'icono' => 'calendar-days', 'clase' => 'text-sky-700 bg-sky-50 border-sky-200 dark:text-sky-300 dark:bg-sky-950/30 dark:border-sky-900/60'],
            ['titulo' => 'Promedio global', 'valor' => $resumen['promedio_global'], 'icono' => 'chart-bar-square', 'clase' => 'text-violet-700 bg-violet-50 border-violet-200 dark:text-violet-300 dark:bg-violet-950/30 dark:border-violet-900/60'],
            ['titulo' => 'Documentos', 'valor' => $resumen['documentos'], 'icono' => 'folder-open', 'clase' => 'text-amber-700 bg-amber-50 border-amber-200 dark:text-amber-300 dark:bg-amber-950/30 dark:border-amber-900/60'],
            ['titulo' => 'Responsables', 'valor' => $resumen['responsables'], 'icono' => 'users', 'clase' => 'text-emerald-700 bg-emerald-50 border-emerald-200 dark:text-emerald-300 dark:bg-emerald-950/30 dark:border-emerald-900/60'],
            ['titulo' => 'Movimientos', 'valor' => $resumen['movimientos'], 'icono' => 'arrows-right-left', 'clase' => 'text-indigo-700 bg-indigo-50 border-indigo-200 dark:text-indigo-300 dark:bg-indigo-950/30 dark:border-indigo-900/60'],
            ['titulo' => 'Seguimientos', 'valor' => $resumen['seguimientos_activos'], 'icono' => 'shield-check', 'clase' => 'text-rose-700 bg-rose-50 border-rose-200 dark:text-rose-300 dark:bg-rose-950/30 dark:border-rose-900/60'],
        ] as $tarjeta)
            <article class="rounded-3xl border p-4 {{ $tarjeta['clase'] }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-wide">{{ $tarjeta['titulo'] }}</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $tarjeta['valor'] }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/80 shadow-sm dark:bg-neutral-900/70">
                        <flux:icon :name="$tarjeta['icono']" class="h-5 w-5" />
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="overflow-x-auto border-b border-slate-200 dark:border-neutral-800">
            <nav class="flex min-w-max gap-1 p-2" aria-label="Secciones del expediente">
                @foreach ($tabs as $clave => [$texto, $icono])
                    <button type="button" wire:click="cambiarSeccion('{{ $clave }}')"
                        class="inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition {{ $seccion === $clave ? 'bg-[#006492] text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-neutral-800 dark:hover:text-white' }}">
                        <flux:icon :name="$icono" class="h-4 w-4" />
                        {{ $texto }}
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="p-5 sm:p-6">
            @if ($seccion === 'resumen')
                <div class="grid gap-6 xl:grid-cols-12">
                    <div class="space-y-6 xl:col-span-8">
                        <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <flux:heading size="lg">Situación actual</flux:heading>
                                    <flux:text variant="subtle">Identidad, ubicación y vigencia escolar.</flux:text>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $this->claseEstatus($alumno['estatus']) }}">{{ $alumno['estatus_etiqueta'] }}</span>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ([
                                    ['Nivel', $alumno['nivel'] ?: '—', 'academic-cap'],
                                    ['Grado / semestre', $alumno['grado'] ?: '—', 'list-bullet'],
                                    ['Grupo', $alumno['grupo'] ?: '—', 'users'],
                                    ['Generación', $alumno['generacion'] ?: '—', 'calendar-days'],
                                ] as [$titulo, $valor, $icono])
                                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60">
                                        <div class="flex items-center gap-2 text-slate-400">
                                            <flux:icon :name="$icono" class="h-4 w-4" />
                                            <p class="text-[11px] font-black uppercase tracking-wide">{{ $titulo }}</p>
                                        </div>
                                        <p class="mt-2 text-sm font-black text-slate-900 dark:text-white">{{ $valor }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <flux:heading size="lg">Trayectoria por ciclo</flux:heading>
                                    <flux:text variant="subtle">Resumen histórico consolidado.</flux:text>
                                </div>
                                <flux:button wire:click="abrirTrayectoria" variant="ghost" icon="history" size="sm">Ver detalle cronológico</flux:button>
                            </div>

                            <div class="mt-5 grid gap-3 md:grid-cols-2">
                                @foreach (collect($trayectoria['ciclos'])->take(4) as $ciclo)
                                    <article class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-800">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-black text-slate-900 dark:text-white">{{ $ciclo['ciclo'] }}</p>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $ciclo['ubicacion'] ?: 'Sin ubicación registrada' }}</p>
                                            </div>
                                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[10px] font-black text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">{{ $ciclo['resultado_etiqueta'] }}</span>
                                        </div>
                                        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                                            <div class="rounded-xl bg-slate-50 p-2 dark:bg-neutral-950/60">
                                                <p class="text-[10px] font-black uppercase text-slate-400">Promedio</p>
                                                <p class="mt-1 font-black text-slate-900 dark:text-white">{{ $ciclo['estadisticas']['promedio'] }}</p>
                                            </div>
                                            <div class="rounded-xl bg-slate-50 p-2 dark:bg-neutral-950/60">
                                                <p class="text-[10px] font-black uppercase text-slate-400">Docs.</p>
                                                <p class="mt-1 font-black text-slate-900 dark:text-white">{{ $ciclo['estadisticas']['documentos'] }}</p>
                                            </div>
                                            <div class="rounded-xl bg-slate-50 p-2 dark:bg-neutral-950/60">
                                                <p class="text-[10px] font-black uppercase text-slate-400">Movs.</p>
                                                <p class="mt-1 font-black text-slate-900 dark:text-white">{{ $ciclo['estadisticas']['movimientos'] }}</p>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>

                        <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <flux:heading size="lg">Documentación reciente</flux:heading>
                                    <flux:text variant="subtle">Últimos archivos vinculados al alumno.</flux:text>
                                </div>
                                <button type="button" wire:click="cambiarSeccion('documentos')" class="text-xs font-black text-[#006492] dark:text-sky-300">Ver todos</button>
                            </div>
                            <div class="mt-4 space-y-2">
                                @forelse (collect($expediente['documentos'])->take(5) as $documento)
                                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 p-3 dark:border-neutral-800">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300">
                                            <flux:icon.document-text class="h-5 w-5" />
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ $documento['nombre'] }}</p>
                                            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $documento['fecha'] }} · {{ $documento['tamano'] }}</p>
                                        </div>
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $this->claseDocumento($documento['estado']) }}">{{ str($documento['estado'])->replace('_', ' ')->title() }}</span>
                                    </div>
                                @empty
                                    <p class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400 dark:border-neutral-800">No hay documentos registrados.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6 xl:col-span-4">
                        <section class="rounded-[26px] border p-5 {{ $integridad['estado'] === 'correcto' ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/60 dark:bg-emerald-950/25' : 'border-amber-200 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/25' }}">
                            <div class="flex items-start gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/80 shadow-sm dark:bg-neutral-900/70">
                                    <flux:icon :name="$integridad['estado'] === 'correcto' ? 'check-circle' : 'exclamation-triangle'" class="h-6 w-6 {{ $integridad['estado'] === 'correcto' ? 'text-emerald-600' : 'text-amber-600' }}" />
                                </span>
                                <div>
                                    <p class="font-black text-slate-900 dark:text-white">{{ $integridad['etiqueta'] }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $integridad['mensaje'] }}</p>
                                </div>
                            </div>
                            @if (!empty($integridad['alertas']))
                                <div class="mt-4 space-y-2">
                                    @foreach (collect($integridad['alertas'])->take(4) as $alerta)
                                        <div class="rounded-xl bg-white/70 p-3 dark:bg-neutral-900/60">
                                            <p class="text-xs font-black text-slate-800 dark:text-white">{{ $alerta['titulo'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $alerta['detalle'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </section>

                        <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:heading size="lg">Responsable principal</flux:heading>
                                    <flux:text variant="subtle">Contacto vigente.</flux:text>
                                </div>
                                <button type="button" wire:click="cambiarSeccion('responsables')" class="text-xs font-black text-[#006492] dark:text-sky-300">Todos</button>
                            </div>
                            @php $principal = collect($expediente['responsables'])->firstWhere('principal', true) ?? collect($expediente['responsables'])->first(); @endphp
                            @if ($principal)
                                <div class="mt-4 rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60">
                                    <p class="font-black uppercase text-slate-900 dark:text-white">{{ $principal['nombre'] }}</p>
                                    <p class="mt-1 text-xs font-bold text-[#006492] dark:text-sky-300">{{ $principal['parentesco'] }}</p>
                                    <div class="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                        <p class="flex items-center gap-2"><flux:icon.phone class="h-4 w-4 text-slate-400" /> {{ $principal['telefono'] }}</p>
                                        <p class="flex items-center gap-2"><flux:icon.envelope class="h-4 w-4 text-slate-400" /> {{ $principal['correo'] }}</p>
                                    </div>
                                </div>
                            @else
                                <p class="mt-4 rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400 dark:border-neutral-800">No hay responsables vinculados.</p>
                            @endif
                        </section>

                        <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800">
                            <flux:heading size="lg">Últimas observaciones</flux:heading>
                            <div class="mt-4 space-y-3">
                                @forelse (collect($expediente['observaciones'])->take(3) as $observacion)
                                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-xs font-black text-[#006492] dark:text-sky-300">{{ $observacion['ciclo'] }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $observacion['fecha'] }}</span>
                                        </div>
                                        <div class="prose prose-sm mt-2 max-w-none text-slate-600 dark:prose-invert dark:text-slate-300">{!! $observacion['contenido'] !!}</div>
                                    </div>
                                @empty
                                    <p class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400 dark:border-neutral-800">Sin observaciones registradas.</p>
                                @endforelse
                            </div>
                        </section>
                    </aside>
                </div>
            @elseif ($seccion === 'personales')
                <div class="grid gap-6 xl:grid-cols-12">
                    <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800 xl:col-span-7">
                        <flux:heading size="lg">Identidad del alumno</flux:heading>
                        <flux:text variant="subtle">Datos personales y de nacimiento capturados en la inscripción.</flux:text>
                        <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                ['Nombre completo', $alumno['nombre']],
                                ['CURP', $alumno['curp']],
                                ['Matrícula actual', $alumno['matricula']],
                                ['Folio', $alumno['folio']],
                                ['Género', $alumno['genero']],
                                ['Fecha de nacimiento', $alumno['fecha_nacimiento']],
                                ['Edad', $alumno['edad'] !== null ? $alumno['edad'].' años' : '—'],
                                ['Lugar de nacimiento', $alumno['nacimiento']],
                                ['Fecha de inscripción', $alumno['fecha_inscripcion']],
                                ['Ciclo actual', $alumno['ciclo'] ?: '—'],
                            ] as [$titulo, $valor])
                                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60">
                                    <dt class="text-[11px] font-black uppercase tracking-wide text-slate-400">{{ $titulo }}</dt>
                                    <dd class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $valor }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    <div class="space-y-6 xl:col-span-5">
                        <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800">
                            <flux:heading size="lg">Domicilio</flux:heading>
                            <div class="mt-4 flex items-start gap-3 rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-[#006492] dark:bg-sky-950/50 dark:text-sky-300">
                                    <flux:icon.home class="h-5 w-5" />
                                </span>
                                <p class="text-sm font-semibold leading-6 text-slate-700 dark:text-slate-300">{{ $alumno['direccion'] }}</p>
                            </div>
                        </section>

                        <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800">
                            <div class="flex items-center justify-between">
                                <flux:heading size="lg">Estado de la matrícula</flux:heading>
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $this->claseEstatus($alumno['estatus']) }}">{{ $alumno['estatus_etiqueta'] }}</span>
                            </div>
                            <div class="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                <p><span class="font-black text-slate-900 dark:text-white">Fecha del estado:</span> {{ $alumno['fecha_estatus'] ?: '—' }}</p>
                                <p><span class="font-black text-slate-900 dark:text-white">Motivo:</span> {{ $alumno['motivo_estatus'] ?: 'Sin motivo capturado' }}</p>
                            </div>
                        </section>
                    </div>

                    <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800 xl:col-span-12">
                        <flux:heading size="lg">Historial de matrículas</flux:heading>
                        <div class="mt-5 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                                <thead>
                                    <tr class="text-left text-[11px] font-black uppercase tracking-wide text-slate-400">
                                        <th class="px-3 py-3">Matrícula</th><th class="px-3 py-3">Nivel</th><th class="px-3 py-3">Asignación</th><th class="px-3 py-3">Fin</th><th class="px-3 py-3">Origen</th><th class="px-3 py-3">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                    @forelse ($expediente['matriculas'] as $matricula)
                                        <tr class="text-sm text-slate-700 dark:text-slate-300">
                                            <td class="px-3 py-3 font-black text-slate-900 dark:text-white">{{ $matricula['matricula'] }}</td>
                                            <td class="px-3 py-3">{{ $matricula['nivel'] ?: '—' }}</td>
                                            <td class="px-3 py-3">{{ $matricula['fecha_asignacion'] ?: '—' }}</td>
                                            <td class="px-3 py-3">{{ $matricula['fecha_fin'] ?: '—' }}</td>
                                            <td class="px-3 py-3">{{ $matricula['origen'] ?: '—' }}</td>
                                            <td class="px-3 py-3"><span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $matricula['vigente'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-slate-300' }}">{{ $matricula['vigente'] ? 'Vigente' : 'Histórica' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-3 py-8 text-center text-sm text-slate-400">No existe historial de matrículas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            @elseif ($seccion === 'responsables')
                <div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <flux:heading size="lg">Responsables vinculados</flux:heading>
                            <flux:text variant="subtle">Tutela, contacto, avisos, recogida y responsabilidad económica.</flux:text>
                        </div>
                        @if ($puedeEditar)
                            <a href="{{ route('misrutas.tutores') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-[#006492] px-4 py-2.5 text-sm font-black text-white">
                                <flux:icon.users class="h-4 w-4" /> Gestionar responsables
                            </a>
                        @endif
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-2">
                        @forelse ($expediente['responsables'] as $responsable)
                            <article class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800 {{ !$responsable['activo'] ? 'opacity-65' : '' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white">
                                            <flux:icon.user class="h-6 w-6" />
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="truncate font-black uppercase text-slate-900 dark:text-white">{{ $responsable['nombre'] }}</h3>
                                            <p class="mt-1 text-xs font-bold text-[#006492] dark:text-sky-300">{{ $responsable['parentesco'] }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap justify-end gap-1.5">
                                        @if ($responsable['principal']) <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[10px] font-black text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">Principal</span> @endif
                                        @if ($responsable['tutor_legal']) <span class="rounded-full bg-violet-100 px-2.5 py-1 text-[10px] font-black text-violet-700 dark:bg-violet-950/50 dark:text-violet-300">Tutor legal</span> @endif
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $responsable['activo'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-neutral-800 dark:text-slate-400' }}">{{ $responsable['activo'] ? 'Activo' : 'Histórico' }}</span>
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-neutral-950/60"><p class="text-[10px] font-black uppercase text-slate-400">Teléfono</p><p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $responsable['telefono'] }}</p></div>
                                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-neutral-950/60"><p class="text-[10px] font-black uppercase text-slate-400">Correo</p><p class="mt-1 truncate text-sm font-bold text-slate-900 dark:text-white">{{ $responsable['correo'] }}</p></div>
                                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-neutral-950/60 sm:col-span-2"><p class="text-[10px] font-black uppercase text-slate-400">Identificación protegida</p><p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $responsable['identidad'] }}</p></div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach ([
                                        ['vive_con_alumno', 'Vive con alumno'], ['recibe_avisos', 'Recibe avisos'], ['recibe_calificaciones', 'Recibe calificaciones'],
                                        ['contacto_emergencia', 'Emergencia'], ['autorizado_recoger', 'Puede recoger'], ['responsable_economico', 'Responsable económico'],
                                    ] as [$clave, $texto])
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $responsable[$clave] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-400 dark:bg-neutral-800 dark:text-slate-500' }}">{{ $texto }}</span>
                                    @endforeach
                                </div>
                                @if ($responsable['observaciones'])
                                    <p class="mt-4 rounded-2xl bg-amber-50 p-3 text-xs leading-5 text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">{{ $responsable['observaciones'] }}</p>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-[26px] border border-dashed border-slate-300 p-12 text-center dark:border-neutral-700 lg:col-span-2">
                                <flux:icon.users class="mx-auto h-10 w-10 text-slate-300" />
                                <h3 class="mt-4 font-black text-slate-900 dark:text-white">Sin responsables vinculados</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Relaciona al menos un responsable principal para completar el expediente.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @elseif ($seccion === 'academico')
                <div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <flux:heading size="lg">Trayectoria académica completa</flux:heading>
                            <flux:text variant="subtle">Ciclos, ubicaciones, resultados, promedios y actividad registrada.</flux:text>
                        </div>
                        <flux:button wire:click="abrirTrayectoria" variant="primary" icon="history">Abrir línea del tiempo</flux:button>
                    </div>

                    <div class="mt-6 space-y-4">
                        @foreach ($trayectoria['ciclos'] as $ciclo)
                            <article class="overflow-hidden rounded-[26px] border border-slate-200 dark:border-neutral-800">
                                <div class="flex flex-col gap-4 bg-slate-50 p-5 dark:bg-neutral-950/60 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ $ciclo['ciclo'] }}</h3>
                                            @if ($ciclo['es_actual']) <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Ciclo actual</span> @endif
                                            @if ($ciclo['reconstruido']) <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">Reconstruido</span> @endif
                                        </div>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $ciclo['ubicacion'] ?: 'Sin ubicación registrada' }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">{{ $ciclo['estado_etiqueta'] }}</span>
                                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">{{ $ciclo['resultado_etiqueta'] }}</span>
                                    </div>
                                </div>

                                <div class="grid gap-3 p-5 sm:grid-cols-3 lg:grid-cols-7">
                                    @foreach ([
                                        ['Ingreso', $ciclo['fecha_ingreso']], ['Salida', $ciclo['fecha_salida']], ['Matrícula', $ciclo['matricula'] ?: '—'],
                                        ['Promedio', $ciclo['estadisticas']['promedio']], ['Calificaciones', $ciclo['estadisticas']['calificaciones']],
                                        ['Documentos', $ciclo['estadisticas']['documentos']], ['Movimientos', $ciclo['estadisticas']['movimientos']],
                                    ] as [$titulo, $valor])
                                        <div class="rounded-2xl bg-slate-50 p-3 text-center dark:bg-neutral-950/60">
                                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">{{ $titulo }}</p>
                                            <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">{{ $valor ?: '—' }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                @if (!empty($ciclo['asignaciones']))
                                    <div class="border-t border-slate-200 px-5 py-4 dark:border-neutral-800">
                                        <p class="mb-3 text-xs font-black uppercase tracking-wide text-slate-400">Asignaciones dentro del ciclo</p>
                                        <div class="space-y-2">
                                            @foreach ($ciclo['asignaciones'] as $asignacion)
                                                <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 p-3 text-sm dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
                                                    <div><p class="font-black text-slate-900 dark:text-white">{{ $asignacion['ubicacion'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $asignacion['tipo'] }}{{ $asignacion['motivo'] ? ' · '.$asignacion['motivo'] : '' }}</p></div>
                                                    <p class="text-xs font-bold text-slate-500">{{ $asignacion['inicio'] ?: '—' }} → {{ $asignacion['fin'] ?: 'Actual' }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @elseif ($seccion === 'calificaciones')
                <div>
                    <div>
                        <flux:heading size="lg">Calificaciones por ciclo</flux:heading>
                        <flux:text variant="subtle">Vista consolidada de registros numéricos y especiales.</flux:text>
                    </div>
                    <div class="mt-6 space-y-5">
                        @forelse ($expediente['calificaciones'] as $ciclo)
                            <article class="overflow-hidden rounded-[26px] border border-slate-200 dark:border-neutral-800">
                                <div class="flex flex-col gap-3 bg-slate-50 p-5 dark:bg-neutral-950/60 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ $ciclo['ciclo'] }}</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ collect([$ciclo['nivel'], $ciclo['grado']])->filter()->implode(' · ') }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">{{ $ciclo['total'] }} registros</span>
                                        <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-700 dark:bg-violet-950/50 dark:text-violet-300">Promedio {{ $ciclo['promedio'] }}</span>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                                        <thead><tr class="text-left text-[11px] font-black uppercase tracking-wide text-slate-400"><th class="px-5 py-3">Materia</th><th class="px-5 py-3">Periodo</th><th class="px-5 py-3 text-center">Calificación</th><th class="px-5 py-3">Captura</th><th class="px-5 py-3">Observación</th></tr></thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                            @foreach ($ciclo['registros'] as $registro)
                                                <tr class="text-sm text-slate-700 dark:text-slate-300">
                                                    <td class="px-5 py-3 font-black text-slate-900 dark:text-white">{{ $registro['materia'] }}</td>
                                                    <td class="px-5 py-3">{{ $registro['periodo'] }}</td>
                                                    <td class="px-5 py-3 text-center"><span class="inline-flex min-w-10 justify-center rounded-xl bg-slate-100 px-3 py-1.5 font-black text-slate-900 dark:bg-neutral-800 dark:text-white">{{ $registro['calificacion'] }}</span></td>
                                                    <td class="px-5 py-3"><p>{{ $registro['capturado_at'] ?: '—' }}</p><p class="mt-1 text-xs text-slate-400">{{ $registro['capturado_por'] ?: 'Sistema' }}</p></td>
                                                    <td class="max-w-xs px-5 py-3">{{ $registro['observacion'] ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[26px] border border-dashed border-slate-300 p-12 text-center dark:border-neutral-700">
                                <flux:icon.chart-bar-square class="mx-auto h-10 w-10 text-slate-300" />
                                <h3 class="mt-4 font-black text-slate-900 dark:text-white">Sin calificaciones</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">No hay registros académicos vinculados al alumno.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @elseif ($seccion === 'documentos')
                <div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <flux:heading size="lg">Expediente documental</flux:heading>
                            <flux:text variant="subtle">Versiones, validación, archivos actuales y documentos emitidos.</flux:text>
                        </div>
                        @if ($puedeDocumentos)
                            <a href="{{ route('misrutas.expedientes.show', $alumno['id']) }}" class="inline-flex items-center gap-2 rounded-2xl bg-[#006492] px-4 py-2.5 text-sm font-black text-white">
                                <flux:icon.folder-open class="h-4 w-4" /> Administrar expediente
                            </a>
                        @endif
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        @foreach ([
                            ['Total histórico', $expediente['documentos_resumen']['total']], ['Actuales', $expediente['documentos_resumen']['actuales']],
                            ['Validados', $expediente['documentos_resumen']['validados']], ['Pendientes', $expediente['documentos_resumen']['pendientes']],
                            ['Rechazados', $expediente['documentos_resumen']['rechazados']],
                        ] as [$titulo, $valor])
                            <div class="rounded-2xl bg-slate-50 p-4 text-center dark:bg-neutral-950/60"><p class="text-[10px] font-black uppercase text-slate-400">{{ $titulo }}</p><p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $valor }}</p></div>
                        @endforeach
                    </div>

                    <div class="mt-6 overflow-hidden rounded-[26px] border border-slate-200 dark:border-neutral-800">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                                <thead class="bg-slate-50 dark:bg-neutral-950/60"><tr class="text-left text-[11px] font-black uppercase tracking-wide text-slate-400"><th class="px-5 py-3">Documento</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Versión</th><th class="px-5 py-3">Ciclo</th><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Validación</th><th class="px-5 py-3 text-right">Acción</th></tr></thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                    @forelse ($expediente['documentos'] as $documento)
                                        <tr class="text-sm text-slate-700 dark:text-slate-300 {{ !$documento['actual'] ? 'opacity-60' : '' }}">
                                            <td class="px-5 py-3"><p class="font-black text-slate-900 dark:text-white">{{ $documento['nombre'] }}</p><p class="mt-1 text-xs text-slate-400">{{ $documento['archivo'] ?: 'Sin nombre de archivo' }} · {{ $documento['tamano'] }}</p></td>
                                            <td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $this->claseDocumento($documento['estado']) }}">{{ str($documento['estado'])->replace('_', ' ')->title() }}</span></td>
                                            <td class="px-5 py-3">v{{ $documento['version'] ?: 1 }}{{ $documento['actual'] ? ' · Actual' : '' }}</td>
                                            <td class="px-5 py-3">{{ $documento['ciclo'] ?: '—' }}</td>
                                            <td class="px-5 py-3">{{ $documento['fecha'] ?: '—' }}</td>
                                            <td class="px-5 py-3"><p>{{ $documento['validado_por'] ?: 'Pendiente' }}</p><p class="mt-1 text-xs text-slate-400">{{ $documento['validado_at'] ?: '' }}</p></td>
                                            <td class="px-5 py-3 text-right">
                                                @if ($puedeDocumentos)
                                                    <a href="{{ route('misrutas.expedientes.preview', $documento['id']) }}" target="_blank" class="inline-flex items-center justify-center rounded-xl bg-sky-100 p-2 text-[#006492] transition hover:bg-sky-200 dark:bg-sky-950/50 dark:text-sky-300" title="Vista previa"><flux:icon.eye class="h-4 w-4" /></a>
                                                @else
                                                    <span class="text-xs text-slate-400">Consulta restringida</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-slate-400">No existen documentos en el expediente.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <section class="mt-6 rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800">
                        <flux:heading size="lg">Documentos institucionales emitidos</flux:heading>
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @forelse ($expediente['documentos_emitidos'] as $emitido)
                                <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300"><flux:icon.document-check class="h-5 w-5" /></span>
                                    <div class="min-w-0 flex-1"><p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ $emitido['nombre'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $emitido['tipo'] }} · Folio {{ $emitido['folio'] }} · {{ $emitido['fecha'] }}</p></div>
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">{{ str($emitido['estado'])->title() }}</span>
                                </div>
                            @empty
                                <p class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400 dark:border-neutral-800 md:col-span-2">No hay constancias u oficios vinculados.</p>
                            @endforelse
                        </div>
                    </section>
                </div>
            @elseif ($seccion === 'movimientos')
                <div>
                    <div>
                        <flux:heading size="lg">Movimientos y cambios académicos</flux:heading>
                        <flux:text variant="subtle">Bajas, reingresos, promociones, cambios de grupo y correcciones de ubicación.</flux:text>
                    </div>
                    <div class="relative mt-6 space-y-4 before:absolute before:bottom-4 before:left-[19px] before:top-4 before:w-px before:bg-slate-200 dark:before:bg-neutral-800">
                        @forelse ($expediente['movimientos'] as $movimiento)
                            <article class="relative pl-12">
                                <span class="absolute left-0 top-1 flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-indigo-600 text-white shadow-sm"><flux:icon.arrows-right-left class="h-5 w-5" /></span>
                                <div class="rounded-[24px] border border-slate-200 p-5 dark:border-neutral-800">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div><h3 class="font-black text-slate-900 dark:text-white">{{ $movimiento['tipo'] }}</h3><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $movimiento['motivo'] }}</p></div>
                                        <span class="text-xs font-bold text-slate-400">{{ $movimiento['fecha'] }}</span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                        @if ($movimiento['ciclo']) <span class="rounded-full bg-sky-100 px-2.5 py-1 font-black text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">{{ $movimiento['ciclo'] }}</span> @endif
                                        @if ($movimiento['cambio_nivel']) <span class="rounded-full bg-indigo-100 px-2.5 py-1 font-black text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">{{ $movimiento['cambio_nivel'] }}</span> @endif
                                        @if ($movimiento['usuario']) <span class="rounded-full bg-slate-100 px-2.5 py-1 font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">Por {{ $movimiento['usuario'] }}</span> @endif
                                    </div>
                                    @if ($movimiento['observaciones']) <p class="mt-3 rounded-2xl bg-slate-50 p-3 text-xs leading-5 text-slate-600 dark:bg-neutral-950/60 dark:text-slate-300">{{ $movimiento['observaciones'] }}</p> @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[26px] border border-dashed border-slate-300 p-12 text-center dark:border-neutral-700"><flux:icon.arrows-right-left class="mx-auto h-10 w-10 text-slate-300" /><h3 class="mt-4 font-black text-slate-900 dark:text-white">Sin movimientos</h3><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">No existen cambios académicos registrados.</p></div>
                        @endforelse
                    </div>
                </div>
            @elseif ($seccion === 'seguimiento')
                <div class="grid gap-6 xl:grid-cols-12">
                    <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800 xl:col-span-5">
                        <flux:heading size="lg">Semáforo de riesgo</flux:heading>
                        @if ($expediente['riesgo'])
                            <div class="mt-5 rounded-[24px] border p-5 {{ $this->claseRiesgo($expediente['riesgo']['nivel']) }}">
                                <div class="flex items-center justify-between gap-3">
                                    <div><p class="text-xs font-black uppercase tracking-wide">Riesgo actual</p><p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $expediente['riesgo']['etiqueta'] }}</p></div>
                                    <div class="text-right"><p class="text-xs font-black uppercase tracking-wide">Puntaje</p><p class="mt-1 text-3xl font-black text-slate-900 dark:text-white">{{ $expediente['riesgo']['puntaje'] }}</p></div>
                                </div>
                                <p class="mt-3 text-xs">Evaluado: {{ $expediente['riesgo']['evaluado_at'] ?: '—' }}</p>
                            </div>
                            @if (!empty($expediente['riesgo']['factores']))
                                <div class="mt-4 space-y-2">
                                    @foreach ($expediente['riesgo']['factores'] as $factor)
                                        <div class="rounded-2xl bg-slate-50 p-3 text-xs text-slate-600 dark:bg-neutral-950/60 dark:text-slate-300">{{ is_array($factor) ? ($factor['detalle'] ?? $factor['nombre'] ?? json_encode($factor, JSON_UNESCAPED_UNICODE)) : $factor }}</div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="mt-5 rounded-[24px] border border-dashed border-slate-300 p-8 text-center dark:border-neutral-700"><flux:icon.shield-check class="mx-auto h-10 w-10 text-slate-300" /><p class="mt-3 font-black text-slate-900 dark:text-white">Sin evaluación actual</p><p class="mt-1 text-sm text-slate-500">El alumno aún no tiene un semáforo de riesgo vigente.</p></div>
                        @endif
                    </section>

                    <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800 xl:col-span-7">
                        <div class="flex items-center justify-between gap-3">
                            <div><flux:heading size="lg">Casos de seguimiento</flux:heading><flux:text variant="subtle">Responsables, prioridad y próximas revisiones.</flux:text></div>
                            @if ($puedeSeguimiento)
                                <a href="{{ route('misrutas.seguimiento-academico') }}" wire:navigate class="text-xs font-black text-[#006492] dark:text-sky-300">Abrir centro</a>
                            @endif
                        </div>
                        <div class="mt-5 space-y-3">
                            @forelse ($expediente['seguimientos'] as $caso)
                                <article class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-800">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div><div class="flex flex-wrap items-center gap-2"><h3 class="font-black text-slate-900 dark:text-white">{{ $caso['folio'] ?: 'Caso #'.$caso['id'] }}</h3><span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $caso['activo'] ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' }}">{{ $caso['estado'] }}</span></div><p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $caso['resumen'] ?: 'Sin resumen capturado.' }}</p></div>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600 dark:bg-neutral-800 dark:text-slate-300">{{ $caso['prioridad'] ?: 'Sin prioridad' }}</span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500"><span>Responsable: <strong>{{ $caso['responsable'] ?: 'Sin asignar' }}</strong></span><span>Próxima revisión: <strong>{{ $caso['proxima_revision'] ?: 'Sin fecha' }}</strong></span></div>
                                </article>
                            @empty
                                <p class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400 dark:border-neutral-800">No existen casos de seguimiento.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-[26px] border border-slate-200 p-5 dark:border-neutral-800 xl:col-span-12">
                        <flux:heading size="lg">Observaciones por ciclo</flux:heading>
                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                            @forelse ($expediente['observaciones'] as $observacion)
                                <article class="rounded-2xl bg-slate-50 p-4 dark:bg-neutral-950/60">
                                    <div class="flex items-center justify-between gap-3"><span class="text-xs font-black text-[#006492] dark:text-sky-300">{{ $observacion['ciclo'] }}</span><span class="text-[10px] text-slate-400">{{ $observacion['fecha'] }}</span></div>
                                    <div class="prose prose-sm mt-3 max-w-none text-slate-600 dark:prose-invert dark:text-slate-300">{!! $observacion['contenido'] !!}</div>
                                    <p class="mt-3 text-[10px] font-bold text-slate-400">Actualizado por {{ $observacion['usuario'] }}</p>
                                </article>
                            @empty
                                <p class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400 dark:border-neutral-800 lg:col-span-2">No hay observaciones registradas.</p>
                            @endforelse
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </section>

    <p class="text-center text-xs text-slate-400">Expediente consolidado el {{ $expediente['generado_at'] }}. La información se obtiene de los módulos originales y no crea copias independientes.</p>

    <livewire:alumno.linea-tiempo-academica />
</div>
