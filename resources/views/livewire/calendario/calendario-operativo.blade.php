<div class="space-y-6" x-data>
    <section class="relative overflow-hidden rounded-[30px] border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-sky-100/70 blur-3xl dark:bg-sky-950/20"></div>
        <div class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 flex flex-wrap gap-2">
                    <flux:badge color="blue" size="sm">Planeación institucional</flux:badge>
                    <flux:badge color="green" size="sm">Fechas académicas automáticas</flux:badge>
                </div>
                <flux:heading size="xl">Calendario operativo institucional</flux:heading>
                <flux:text variant="subtle" class="mt-2">
                    Centraliza periodos, evaluaciones, inscripciones, reuniones, documentación, cierres y tareas administrativas.
                </flux:text>
            </div>

            @if ($puedeGestionar)
                <flux:button wire:click="abrirCrear" icon="plus" variant="primary">
                    Nuevo evento
                </flux:button>
            @endif
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['titulo' => 'Eventos del mes', 'valor' => $metricas['total'], 'icono' => 'calendar-days', 'clase' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-300'],
            ['titulo' => 'Prioridad crítica', 'valor' => $metricas['criticos'], 'icono' => 'exclamation-triangle', 'clase' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300'],
            ['titulo' => 'En curso', 'valor' => $metricas['en_curso'], 'icono' => 'clock', 'clase' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300'],
            ['titulo' => 'Próximos 30 días', 'valor' => $metricas['proximos'], 'icono' => 'arrow-trending-up', 'clase' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300'],
        ] as $tarjeta)
            <article class="rounded-3xl border p-5 {{ $tarjeta['clase'] }}">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide">{{ $tarjeta['titulo'] }}</p>
                        <p class="mt-1 text-3xl font-black text-slate-900 dark:text-white">{{ $tarjeta['valor'] }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/80 shadow-sm dark:bg-neutral-900/70">
                        <flux:icon :name="$tarjeta['icono']" class="h-5 w-5" />
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <flux:heading size="lg">Filtros</flux:heading>
                <flux:text variant="subtle">Combina búsqueda, ciclo, nivel, tipo y estado.</flux:text>
            </div>
            <flux:button wire:click="limpiarFiltros" variant="ghost" icon="x-mark">Limpiar</flux:button>
        </div>

        <div class="grid gap-4 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <flux:input wire:model.live.debounce.350ms="buscar" label="Buscar evento" icon="magnifying-glass"
                    placeholder="Título, descripción o ubicación" clearable />
            </div>
            <div class="lg:col-span-2">
                <flux:select wire:model.live="filtroCicloId" label="Ciclo escolar">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($ciclos as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="lg:col-span-2">
                <flux:select wire:model.live="filtroNivelId" label="Nivel">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($niveles as $nivel)
                        <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="lg:col-span-2">
                <flux:select wire:model.live="filtroTipo" label="Tipo">
                    <flux:select.option value="todos">Todos</flux:select.option>
                    <flux:select.option value="academico">Académico</flux:select.option>
                    <flux:select.option value="evaluacion">Evaluación</flux:select.option>
                    <flux:select.option value="inscripcion">Inscripción</flux:select.option>
                    <flux:select.option value="reinscripcion">Reinscripción</flux:select.option>
                    <flux:select.option value="boletas">Entrega de boletas</flux:select.option>
                    <flux:select.option value="cierre">Cierre académico</flux:select.option>
                    <flux:select.option value="horario">Publicación de horarios</flux:select.option>
                    <flux:select.option value="documentacion">Documentación</flux:select.option>
                    <flux:select.option value="reunion">Reunión</flux:select.option>
                    <flux:select.option value="administrativo">Administrativo</flux:select.option>
                    <flux:select.option value="respaldo">Respaldo</flux:select.option>
                    <flux:select.option value="otro">Otro</flux:select.option>
                </flux:select>
            </div>
            <div class="lg:col-span-2">
                <flux:select wire:model.live="filtroEstado" label="Estado">
                    <flux:select.option value="todos">Todos</flux:select.option>
                    <flux:select.option value="programado">Programado</flux:select.option>
                    <flux:select.option value="en_curso">En curso</flux:select.option>
                    <flux:select.option value="completado">Completado</flux:select.option>
                    <flux:select.option value="cancelado">Cancelado</flux:select.option>
                </flux:select>
            </div>
        </div>

        <label class="mt-4 inline-flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 dark:border-neutral-800 dark:bg-neutral-950/60 dark:text-slate-200">
            <input type="checkbox" wire:model.live="mostrarSistema"
                class="h-5 w-5 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
            Mostrar fechas automáticas tomadas de periodos, evaluaciones y captura de calificaciones
        </label>
    </section>

    <section class="overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 dark:border-neutral-800 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-2">
                <flux:button wire:click="mesAnterior" variant="ghost" icon="chevron-left" aria-label="Mes anterior" />
                <flux:button wire:click="irHoy" variant="ghost">Hoy</flux:button>
                <flux:button wire:click="mesSiguiente" variant="ghost" icon="chevron-right" aria-label="Mes siguiente" />
                <div class="ml-2">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ $etiquetaMes }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $eventos->count() }} ocurrencia(s) visible(s)</p>
                </div>
            </div>

            <div class="inline-flex w-fit rounded-2xl border border-slate-200 bg-slate-50 p-1 dark:border-neutral-800 dark:bg-neutral-950">
                <button type="button" wire:click="$set('vista', 'mes')"
                    class="rounded-xl px-4 py-2 text-sm font-black transition {{ $vista === 'mes' ? 'bg-white text-[#006492] shadow-sm dark:bg-neutral-800 dark:text-sky-300' : 'text-slate-500 dark:text-slate-400' }}">
                    Mes
                </button>
                <button type="button" wire:click="$set('vista', 'agenda')"
                    class="rounded-xl px-4 py-2 text-sm font-black transition {{ $vista === 'agenda' ? 'bg-white text-[#006492] shadow-sm dark:bg-neutral-800 dark:text-sky-300' : 'text-slate-500 dark:text-slate-400' }}">
                    Agenda
                </button>
            </div>
        </div>

        @if ($vista === 'mes')
            <div class="hidden grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-black uppercase tracking-wider text-slate-500 dark:border-neutral-800 dark:bg-neutral-950/60 dark:text-slate-400 md:grid">
                @foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $diaSemana)
                    <div class="px-3 py-3">{{ $diaSemana }}</div>
                @endforeach
            </div>

            <div class="hidden grid-cols-7 md:grid">
                @foreach ($dias as $dia)
                    <article class="group min-h-[150px] border-b border-r border-slate-200 p-2.5 transition hover:bg-sky-50/40 dark:border-neutral-800 dark:hover:bg-sky-950/10 {{ $dia['mes_actual'] ? '' : 'bg-slate-50/70 dark:bg-neutral-950/40' }}">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl text-sm font-black {{ $dia['hoy'] ? 'bg-[#006492] text-white shadow-sm' : ($dia['mes_actual'] ? 'text-slate-800 dark:text-white' : 'text-slate-400') }}">
                                {{ $dia['numero'] }}
                            </span>
                            @if ($puedeGestionar)
                                <button type="button" wire:click="abrirCrear('{{ $dia['fecha'] }}')"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-300 opacity-0 transition hover:bg-white hover:text-[#006492] group-hover:opacity-100 dark:hover:bg-neutral-800"
                                    title="Agregar evento">
                                    <flux:icon.plus class="h-4 w-4" />
                                </button>
                            @endif
                        </div>

                        <div class="space-y-1.5">
                            @foreach ($dia['eventos']->take(3) as $evento)
                                <button type="button" wire:click="verEvento(@js($evento['key']))"
                                    class="relative block w-full overflow-hidden rounded-xl border px-2.5 py-2 text-left text-[11px] font-bold leading-tight transition hover:-translate-y-0.5 hover:shadow-sm {{ $this->claseTipo($evento['tipo']) }}">
                                    <span class="absolute bottom-0 left-0 top-0 w-1 {{ $this->clasePrioridad($evento['prioridad']) }}"></span>
                                    <span class="block truncate pl-1">{{ $evento['titulo'] }}</span>
                                    <span class="mt-1 block truncate pl-1 text-[10px] font-medium opacity-70">
                                        {{ $evento['todo_el_dia'] ? 'Todo el día' : $evento['inicia_at']->format('H:i') }}
                                        @if ($evento['origen'] === 'sistema') · Automático @endif
                                    </span>
                                </button>
                            @endforeach

                            @if ($dia['eventos']->count() > 3)
                                <button type="button" wire:click="$set('vista', 'agenda')"
                                    class="px-2 text-[11px] font-black text-[#006492] dark:text-sky-300">
                                    + {{ $dia['eventos']->count() - 3 }} evento(s)
                                </button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="divide-y divide-slate-200 dark:divide-neutral-800 md:hidden">
                @foreach ($dias->where('mes_actual', true) as $dia)
                    @if ($dia['eventos']->isNotEmpty() || $dia['hoy'])
                        <article class="p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">
                                        {{ \Carbon\CarbonImmutable::parse($dia['fecha'])->translatedFormat('l') }}
                                    </p>
                                    <p class="text-lg font-black text-slate-900 dark:text-white">
                                        {{ \Carbon\CarbonImmutable::parse($dia['fecha'])->translatedFormat('d \d\e F') }}
                                    </p>
                                </div>
                                @if ($puedeGestionar)
                                    <flux:button wire:click="abrirCrear('{{ $dia['fecha'] }}')" variant="ghost" icon="plus" size="sm" />
                                @endif
                            </div>
                            <div class="space-y-2">
                                @forelse ($dia['eventos'] as $evento)
                                    <button type="button" wire:click="verEvento(@js($evento['key']))"
                                        class="relative block w-full overflow-hidden rounded-2xl border p-3 text-left {{ $this->claseTipo($evento['tipo']) }}">
                                        <span class="absolute bottom-0 left-0 top-0 w-1.5 {{ $this->clasePrioridad($evento['prioridad']) }}"></span>
                                        <p class="pl-2 text-sm font-black">{{ $evento['titulo'] }}</p>
                                        <p class="mt-1 pl-2 text-xs opacity-70">{{ $evento['tipo_etiqueta'] }} · {{ $evento['estado_etiqueta'] }}</p>
                                    </button>
                                @empty
                                    <p class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400 dark:border-neutral-800">Sin eventos para hoy.</p>
                                @endforelse
                            </div>
                        </article>
                    @endif
                @endforeach
            </div>
        @else
            @php
                $agendaAgrupada = $eventos->groupBy(fn ($evento) => $evento['inicia_at']->toDateString());
            @endphp
            <div class="divide-y divide-slate-200 dark:divide-neutral-800">
                @forelse ($agendaAgrupada as $fecha => $eventosDia)
                    <article class="grid gap-4 p-5 md:grid-cols-[150px_1fr]">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-400">{{ \Carbon\CarbonImmutable::parse($fecha)->translatedFormat('l') }}</p>
                            <p class="mt-1 text-lg font-black text-slate-900 dark:text-white">{{ \Carbon\CarbonImmutable::parse($fecha)->translatedFormat('d \d\e F') }}</p>
                        </div>
                        <div class="space-y-3">
                            @foreach ($eventosDia as $evento)
                                <button type="button" wire:click="verEvento(@js($evento['key']))"
                                    class="relative flex w-full flex-col gap-3 overflow-hidden rounded-2xl border p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md sm:flex-row sm:items-center sm:justify-between {{ $this->claseTipo($evento['tipo']) }}">
                                    <span class="absolute bottom-0 left-0 top-0 w-1.5 {{ $this->clasePrioridad($evento['prioridad']) }}"></span>
                                    <div class="pl-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-black">{{ $evento['titulo'] }}</p>
                                            @if ($evento['origen'] === 'sistema')
                                                <span class="rounded-full bg-white/70 px-2 py-0.5 text-[10px] font-black uppercase dark:bg-neutral-900/50">Automático</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs opacity-75">{{ $evento['descripcion'] ?: $evento['tipo_etiqueta'] }}</p>
                                    </div>
                                    <div class="shrink-0 pl-2 text-xs font-black sm:text-right">
                                        <p>{{ $evento['todo_el_dia'] ? 'Todo el día' : $evento['inicia_at']->format('H:i') }}</p>
                                        <p class="mt-1 font-medium opacity-70">{{ $evento['estado_etiqueta'] }}</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="p-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                            <flux:icon.calendar-days class="h-7 w-7" />
                        </div>
                        <h3 class="mt-4 font-black text-slate-900 dark:text-white">No hay eventos en este mes</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajusta los filtros o registra una nueva actividad institucional.</p>
                    </div>
                @endforelse
            </div>
        @endif
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <flux:heading size="lg">Próximos compromisos</flux:heading>
                    <flux:text variant="subtle">Ventana operativa de los siguientes 30 días.</flux:text>
                </div>
                <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">{{ $metricas['proximos'] }}</span>
            </div>
            <div class="space-y-3">
                @forelse ($proximos as $evento)
                    <button type="button" wire:click="verEvento(@js($evento['key']))"
                        class="flex w-full items-center gap-4 rounded-2xl border border-slate-200 p-3 text-left transition hover:border-sky-300 hover:bg-sky-50/50 dark:border-neutral-800 dark:hover:border-sky-900 dark:hover:bg-sky-950/10">
                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-2xl bg-slate-100 dark:bg-neutral-800">
                            <span class="text-[10px] font-black uppercase text-slate-400">{{ $evento['inicia_at']->translatedFormat('M') }}</span>
                            <span class="text-lg font-black text-slate-900 dark:text-white">{{ $evento['inicia_at']->format('d') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ $evento['titulo'] }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ $evento['nivel'] ?: 'Institucional' }} · {{ $evento['estado_etiqueta'] }}
                            </p>
                        </div>
                        <span class="h-3 w-3 shrink-0 rounded-full {{ $this->clasePrioridad($evento['prioridad']) }}"></span>
                    </button>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400 dark:border-neutral-800">No hay compromisos próximos con los filtros actuales.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <flux:heading size="lg">Lectura rápida</flux:heading>
            <flux:text variant="subtle">Colores por tipo y prioridad.</flux:text>
            <div class="mt-5 space-y-3">
                @foreach ([
                    ['tipo' => 'academico', 'texto' => 'Académico'],
                    ['tipo' => 'evaluacion', 'texto' => 'Evaluación y calificaciones'],
                    ['tipo' => 'inscripcion', 'texto' => 'Inscripción y reinscripción'],
                    ['tipo' => 'documentacion', 'texto' => 'Documentación'],
                    ['tipo' => 'reunion', 'texto' => 'Reuniones'],
                    ['tipo' => 'administrativo', 'texto' => 'Administrativo'],
                ] as $leyenda)
                    <div class="rounded-xl border px-3 py-2 text-xs font-black {{ $this->claseTipo($leyenda['tipo']) }}">{{ $leyenda['texto'] }}</div>
                @endforeach
            </div>
            <div class="mt-5 grid grid-cols-3 gap-2 text-center text-[11px] font-black text-slate-600 dark:text-slate-300">
                <div class="rounded-xl bg-emerald-50 p-2 dark:bg-emerald-950/30"><span class="mx-auto mb-1 block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Normal</div>
                <div class="rounded-xl bg-amber-50 p-2 dark:bg-amber-950/30"><span class="mx-auto mb-1 block h-2.5 w-2.5 rounded-full bg-amber-500"></span>Alta</div>
                <div class="rounded-xl bg-rose-50 p-2 dark:bg-rose-950/30"><span class="mx-auto mb-1 block h-2.5 w-2.5 rounded-full bg-rose-500"></span>Crítica</div>
            </div>
        </div>
    </section>

    @if ($modalFormulario)
        <div class="fixed inset-0 z-[10020] overflow-y-auto bg-slate-950/60 px-4 py-8 backdrop-blur-sm" wire:keydown.escape="cerrarFormulario">
            <div class="flex min-h-full items-center justify-center">
                <div class="w-full max-w-5xl overflow-hidden rounded-[30px] border border-white/20 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="relative overflow-hidden bg-gradient-to-r from-[#006492] via-sky-600 to-[#88AC2E] px-6 py-5 text-white sm:px-8">
                        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/10 blur-2xl"></div>
                        <div class="relative flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-wider text-white/70">Planeación institucional</p>
                                <h2 class="mt-1 text-2xl font-black">{{ $eventoEditandoId ? 'Editar evento' : 'Nuevo evento' }}</h2>
                                <p class="mt-1 text-sm text-white/80">Define fecha, alcance, responsable y prioridad.</p>
                            </div>
                            <button type="button" wire:click="cerrarFormulario" class="rounded-xl bg-white/15 p-2 transition hover:bg-white/25">
                                <flux:icon.x-mark class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <form wire:submit="guardar">
                        <div class="max-h-[72vh] overflow-y-auto p-6 sm:p-8">
                            <div class="grid gap-5 lg:grid-cols-12">
                                <div class="lg:col-span-8">
                                    <flux:input wire:model="titulo" label="Nombre del evento" placeholder="Ej. Entrega de boletas del primer periodo" />
                                </div>
                                <div class="lg:col-span-4">
                                    <flux:select wire:model="tipo" label="Tipo">
                                        <flux:select.option value="academico">Académico</flux:select.option>
                                        <flux:select.option value="evaluacion">Evaluación</flux:select.option>
                                        <flux:select.option value="inscripcion">Inscripción</flux:select.option>
                                        <flux:select.option value="reinscripcion">Reinscripción</flux:select.option>
                                        <flux:select.option value="boletas">Entrega de boletas</flux:select.option>
                                        <flux:select.option value="cierre">Cierre académico</flux:select.option>
                                        <flux:select.option value="horario">Publicación de horarios</flux:select.option>
                                        <flux:select.option value="documentacion">Documentación</flux:select.option>
                                        <flux:select.option value="reunion">Reunión</flux:select.option>
                                        <flux:select.option value="administrativo">Administrativo</flux:select.option>
                                        <flux:select.option value="respaldo">Respaldo</flux:select.option>
                                        <flux:select.option value="otro">Otro</flux:select.option>
                                    </flux:select>
                                </div>

                                <div class="lg:col-span-12">
                                    <label class="mb-1.5 block text-sm font-bold text-slate-800 dark:text-white">Descripción</label>
                                    <textarea wire:model="descripcion" rows="3" maxlength="5000" placeholder="Objetivo, instrucciones o información relevante..."
                                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-[#006492] focus:ring-4 focus:ring-sky-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-sky-950"></textarea>
                                    @error('descripcion') <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="lg:col-span-3">
                                    <flux:input wire:model="inicia_at" type="datetime-local" label="Inicia" />
                                </div>
                                <div class="lg:col-span-3">
                                    <flux:input wire:model="termina_at" type="datetime-local" label="Termina" />
                                </div>
                                <div class="lg:col-span-3">
                                    <flux:select wire:model="prioridad" label="Prioridad">
                                        <flux:select.option value="normal">Normal</flux:select.option>
                                        <flux:select.option value="alta">Alta</flux:select.option>
                                        <flux:select.option value="critica">Crítica</flux:select.option>
                                    </flux:select>
                                </div>
                                <div class="lg:col-span-3">
                                    <flux:select wire:model="estado" label="Estado">
                                        <flux:select.option value="programado">Programado</flux:select.option>
                                        <flux:select.option value="en_curso">En curso</flux:select.option>
                                        <flux:select.option value="completado">Completado</flux:select.option>
                                        <flux:select.option value="cancelado">Cancelado</flux:select.option>
                                    </flux:select>
                                </div>

                                <div class="lg:col-span-4">
                                    <flux:select wire:model="audiencia" label="Visible para">
                                        <flux:select.option value="todos">Todos los usuarios</flux:select.option>
                                        <flux:select.option value="administrativos">Administrativos</flux:select.option>
                                        <flux:select.option value="docentes">Docentes</flux:select.option>
                                    </flux:select>
                                </div>
                                <div class="lg:col-span-4">
                                    <flux:select wire:model="responsable_id" label="Responsable">
                                        <flux:select.option value="">Sin responsable asignado</flux:select.option>
                                        @foreach ($usuarios as $usuario)
                                            <flux:select.option value="{{ $usuario->id }}">{{ $usuario->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <div class="lg:col-span-4">
                                    <flux:input wire:model="recordatorio_dias" type="number" min="0" max="365" label="Avisar con días de anticipación" />
                                </div>

                                <div class="lg:col-span-4">
                                    <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar">
                                        <flux:select.option value="">No especificado</flux:select.option>
                                        @foreach ($ciclos as $ciclo)
                                            <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <div class="lg:col-span-4">
                                    <flux:select wire:model.live="nivel_id" label="Nivel">
                                        <flux:select.option value="">Todos los niveles</flux:select.option>
                                        @foreach ($niveles as $nivel)
                                            <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <div class="lg:col-span-4">
                                    <flux:select wire:model.live="grado_id" label="Grado">
                                        <flux:select.option value="">Todos los grados</flux:select.option>
                                        @foreach ($gradosDisponibles as $grado)
                                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <div class="lg:col-span-4">
                                    <flux:select wire:model="grupo_id" label="Grupo">
                                        <flux:select.option value="">Todos los grupos</flux:select.option>
                                        @foreach ($gruposDisponibles as $grupo)
                                            <flux:select.option value="{{ $grupo->id }}">{{ $grupo->clave ?: 'Grupo '.$grupo->id }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <div class="lg:col-span-4">
                                    <flux:input wire:model="ubicacion" label="Ubicación" icon="map-pin" placeholder="Aula, oficina o plataforma" />
                                </div>
                                <div class="lg:col-span-4">
                                    <flux:input wire:model="enlace" label="Enlace" icon="link" placeholder="https://..." />
                                </div>

                                <div class="lg:col-span-4">
                                    <flux:select wire:model.live="recurrencia" label="Repetición">
                                        <flux:select.option value="ninguna">No repetir</flux:select.option>
                                        <flux:select.option value="diaria">Diariamente</flux:select.option>
                                        <flux:select.option value="semanal">Semanalmente</flux:select.option>
                                        <flux:select.option value="mensual">Mensualmente</flux:select.option>
                                        <flux:select.option value="anual">Anualmente</flux:select.option>
                                    </flux:select>
                                </div>
                                @if ($recurrencia !== 'ninguna')
                                    <div class="lg:col-span-4">
                                        <flux:input wire:model="recurrencia_hasta" type="date" label="Repetir hasta" />
                                    </div>
                                @endif
                                <div class="flex items-end lg:col-span-4">
                                    <label class="flex w-full cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 dark:border-neutral-800 dark:bg-neutral-950/60 dark:text-slate-200">
                                        <input type="checkbox" wire:model="todo_el_dia" class="h-5 w-5 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                                        Mostrar como evento de todo el día
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-neutral-800 dark:bg-neutral-950/60 sm:flex-row sm:justify-end">
                            <flux:button type="button" wire:click="cerrarFormulario" variant="ghost">Cancelar</flux:button>
                            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="guardar">
                                <span wire:loading.remove wire:target="guardar">Guardar evento</span>
                                <span wire:loading wire:target="guardar">Guardando...</span>
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($modalDetalle && $detalleEvento)
        <div class="fixed inset-0 z-[10025] overflow-y-auto bg-slate-950/60 px-4 py-8 backdrop-blur-sm" wire:keydown.escape="cerrarDetalle">
            <div class="flex min-h-full items-center justify-center">
                <div class="w-full max-w-2xl overflow-hidden rounded-[30px] border border-white/20 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-[#006492] to-sky-600 px-6 py-6 text-white">
                        <span class="absolute bottom-0 left-0 top-0 w-2 {{ $this->clasePrioridad($detalleEvento['prioridad']) }}"></span>
                        <div class="flex items-start justify-between gap-4 pl-2">
                            <div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black">{{ $detalleEvento['tipo_etiqueta'] }}</span>
                                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black">{{ $detalleEvento['estado_etiqueta'] }}</span>
                                    @if ($detalleEvento['origen'] === 'sistema')
                                        <span class="rounded-full bg-[#88AC2E] px-3 py-1 text-xs font-black">Fecha automática</span>
                                    @endif
                                </div>
                                <h2 class="mt-4 text-2xl font-black leading-tight">{{ $detalleEvento['titulo'] }}</h2>
                                <p class="mt-2 text-sm text-white/75">{{ $detalleEvento['rango'] }}</p>
                            </div>
                            <button type="button" wire:click="cerrarDetalle" class="rounded-xl bg-white/15 p-2 transition hover:bg-white/25">
                                <flux:icon.x-mark class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-5 p-6">
                        @if ($detalleEvento['descripcion'])
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700 dark:border-neutral-800 dark:bg-neutral-950/60 dark:text-slate-300">
                                {{ $detalleEvento['descripcion'] }}
                            </div>
                        @endif

                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                ['icono' => 'academic-cap', 'titulo' => 'Alcance académico', 'valor' => collect([$detalleEvento['ciclo'], $detalleEvento['nivel'], $detalleEvento['grado'], $detalleEvento['grupo']])->filter()->implode(' · ') ?: 'Institucional'],
                                ['icono' => 'user', 'titulo' => 'Responsable', 'valor' => $detalleEvento['responsable'] ?: 'Sin responsable asignado'],
                                ['icono' => 'map-pin', 'titulo' => 'Ubicación', 'valor' => $detalleEvento['ubicacion'] ?: 'No especificada'],
                                ['icono' => 'arrow-path', 'titulo' => 'Recurrencia', 'valor' => ucfirst($detalleEvento['recurrencia'])],
                            ] as $dato)
                                <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-800">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-[#006492] dark:bg-sky-950/40 dark:text-sky-300">
                                            <flux:icon :name="$dato['icono']" class="h-5 w-5" />
                                        </span>
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-wide text-slate-400">{{ $dato['titulo'] }}</p>
                                            <p class="mt-1 text-sm font-bold text-slate-800 dark:text-white">{{ $dato['valor'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($detalleEvento['enlace'])
                            <a href="{{ $detalleEvento['enlace'] }}" target="_blank" rel="noopener noreferrer"
                                class="flex items-center justify-between rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-black text-[#006492] dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-300">
                                Abrir enlace relacionado
                                <flux:icon.arrow-top-right-on-square class="h-4 w-4" />
                            </a>
                        @endif
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-neutral-800 dark:bg-neutral-950/60 sm:flex-row sm:justify-between">
                        <flux:button type="button" wire:click="cerrarDetalle" variant="ghost">Cerrar</flux:button>
                        @if ($puedeGestionar && $detalleEvento['editable'])
                            <div class="flex flex-wrap justify-end gap-2">
                                @if ($detalleEvento['estado'] !== 'completado')
                                    <flux:button wire:click="completarEvento({{ $detalleEvento['id'] }})" variant="filled" icon="check-circle">Completar</flux:button>
                                @endif
                                <flux:button wire:click="editarEvento({{ $detalleEvento['id'] }})" variant="primary" icon="pencil-square">Editar</flux:button>
                                <flux:button wire:click="eliminarEvento({{ $detalleEvento['id'] }})"
                                    wire:confirm="¿Deseas enviar este evento al historial?"
                                    variant="danger" icon="trash">Eliminar</flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
