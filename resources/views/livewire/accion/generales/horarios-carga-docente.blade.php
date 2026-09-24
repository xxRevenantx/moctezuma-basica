<div class="space-y-6">
    @php
        $reporte = $this->reporte;
        $resumen = $reporte['resumen'] ?? [];
        $alertas = $reporte['alertas'] ?? [];
        $dias = collect($reporte['dias'] ?? []);
    @endphp

    <section class="relative overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 w-full bg-gradient-to-r from-[#006492] via-cyan-600 to-[#88AC2E]"></div>

        <div wire:loading.delay.flex
            wire:target="ciclo_escolar_id,grado_id,grupo_id,profesor_id,limpiarFiltros,descargarExcel,descargarPdfActual,descargarPdfCompleto"
            class="absolute inset-0 z-30 hidden items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-neutral-900/80">
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                Procesando horarios y carga docente...
            </div>
        </div>

        <div class="space-y-6 p-5 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-cyan-50 px-3 py-1 text-xs font-black uppercase tracking-[0.16em] text-cyan-700 dark:bg-cyan-950/30 dark:text-cyan-300">
                        Secundaria
                    </span>
                    <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">
                        Horarios y carga docente
                    </h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Genera los formatos ASIG, FOR-HORGEN, FORM-HOR y HOR-COMP. desde los horarios reales del sistema.
                        Las sesiones de talleres conjuntos se contabilizan una sola vez aunque atiendan varios grupos.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="descargarExcel" @disabled(!$this->puedeExportar)
                        @class([
                            'inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-black transition',
                            'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20 hover:-translate-y-0.5 hover:bg-emerald-700' => $this->puedeExportar,
                            'cursor-not-allowed bg-slate-200 text-slate-400 dark:bg-neutral-800' => !$this->puedeExportar,
                        ])>
                        <flux:icon.document-arrow-down class="h-4 w-4" />
                        Excel · 4 hojas
                    </button>

                    <button type="button" wire:click="descargarPdfActual" @disabled(!$this->puedeExportar)
                        @class([
                            'inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-black transition',
                            'bg-[#006492] text-white shadow-lg shadow-cyan-700/20 hover:-translate-y-0.5' => $this->puedeExportar,
                            'cursor-not-allowed bg-slate-200 text-slate-400 dark:bg-neutral-800' => !$this->puedeExportar,
                        ])>
                        PDF visible
                    </button>

                    <button type="button" wire:click="descargarPdfCompleto" @disabled(!$this->puedeExportar)
                        @class([
                            'inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-black transition',
                            'bg-[#88AC2E] text-white shadow-lg shadow-lime-700/20 hover:-translate-y-0.5' => $this->puedeExportar,
                            'cursor-not-allowed bg-slate-200 text-slate-400 dark:bg-neutral-800' => !$this->puedeExportar,
                        ])>
                        PDF completo
                    </button>
                </div>
            </div>

            @if ($mensaje)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-300">
                    {{ $mensaje }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <flux:field>
                    <flux:label>Ciclo escolar</flux:label>
                    <flux:select wire:model.change="ciclo_escolar_id">
                        @foreach ($ciclosEscolares as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">
                                {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                                {{ $ciclo->es_actual ? '· Actual' : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Grado</flux:label>
                    <flux:select wire:model.change="grado_id">
                        <flux:select.option value="">Todos los grados</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Grupo</flux:label>
                    <flux:select wire:model.change="grupo_id">
                        <flux:select.option value="">Todos los grupos</flux:select.option>
                        @foreach ($this->gruposDisponibles as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">{{ $this->etiquetaGrupo($grupo) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Profesor</flux:label>
                    <flux:select wire:model.change="profesor_id">
                        <flux:select.option value="">Todos los profesores</flux:select.option>
                        @foreach ($profesores as $profesor)
                            <flux:select.option value="{{ $profesor->id }}">{{ $this->nombrePersona($profesor) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <div class="flex justify-end">
                <button type="button" wire:click="limpiarFiltros"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300 dark:hover:bg-neutral-800">
                    <flux:icon.funnel class="h-4 w-4" />
                    Limpiar filtros
                </button>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
        @foreach ([
            ['label' => 'Grupos', 'value' => $resumen['grupos'] ?? 0],
            ['label' => 'Docentes', 'value' => $resumen['docentes'] ?? 0],
            ['label' => 'Sesiones', 'value' => $resumen['sesiones'] ?? 0],
            ['label' => 'Horas reloj', 'value' => number_format((float) ($resumen['horas_reloj'] ?? 0), 2)],
            ['label' => 'Asignaturas', 'value' => $resumen['materias'] ?? 0],
            ['label' => 'Alertas', 'value' => $resumen['alertas'] ?? 0],
        ] as $indicador)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <p class="text-[11px] font-black uppercase tracking-[0.12em] text-slate-400">{{ $indicador['label'] }}</p>
                <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $indicador['value'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <details class="group rounded-[1.5rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <summary class="cursor-pointer list-none p-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-black text-slate-900 dark:text-white">Encabezados institucionales</p>
                        <p class="mt-1 text-xs text-slate-500">Se guardan por ciclo escolar y solo afectan estos reportes.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500 dark:bg-neutral-800">Configurar</span>
                </div>
            </summary>

            <div class="border-t border-slate-100 p-5 dark:border-neutral-800">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field class="sm:col-span-2">
                        <flux:label>Escuela</flux:label>
                        <flux:input wire:model.blur="configuracion.escuela" />
                    </flux:field>
                    <flux:field>
                        <flux:label>CCT</flux:label>
                        <flux:input wire:model.blur="configuracion.cct" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Zona escolar</flux:label>
                        <flux:input wire:model.blur="configuracion.zona_escolar" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Turno</flux:label>
                        <flux:input wire:model.blur="configuracion.turno" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Director(a)</flux:label>
                        <flux:input wire:model.blur="configuracion.director" />
                    </flux:field>
                    <flux:field class="sm:col-span-2">
                        <flux:label>Supervisor(a)</flux:label>
                        <flux:input wire:model.blur="configuracion.supervisor" />
                    </flux:field>
                </div>

                <button type="button" wire:click="guardarConfiguracion"
                    class="mt-4 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900">
                    Guardar encabezados
                </button>
            </div>
        </details>

        <details class="group rounded-[1.5rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <summary class="cursor-pointer list-none p-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-black text-slate-900 dark:text-white">Datos laborales de docentes</p>
                        <p class="mt-1 text-xs text-slate-500">Nombramiento y clave presupuestal. Vacío = S/C.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500 dark:bg-neutral-800">{{ $profesores->count() }} docentes</span>
                </div>
            </summary>

            <div class="max-h-[32rem] overflow-auto border-t border-slate-100 p-5 dark:border-neutral-800">
                <div class="space-y-3">
                    @forelse ($profesores as $profesor)
                        <div class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 p-3 dark:border-neutral-800 lg:grid-cols-[1.2fr_1fr_1fr] lg:items-end">
                            <div>
                                <p class="text-xs font-black uppercase tracking-wide text-slate-400">Profesor(a)</p>
                                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $this->nombrePersona($profesor) }}</p>
                            </div>
                            <flux:field>
                                <flux:label>Nombramiento</flux:label>
                                <flux:input wire:model.defer="datosLaborales.{{ $profesor->id }}.nombramiento" placeholder="S/C" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Clave presupuestal</flux:label>
                                <flux:input wire:model.defer="datosLaborales.{{ $profesor->id }}.clave_presupuestal" placeholder="S/C" />
                            </flux:field>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No hay docentes con horario en este ciclo.</p>
                    @endforelse
                </div>

                @if ($profesores->isNotEmpty())
                    <button type="button" wire:click="guardarDatosLaborales"
                        class="mt-4 rounded-xl bg-[#006492] px-4 py-2.5 text-sm font-black text-white transition hover:opacity-90">
                        Guardar datos laborales
                    </button>
                @endif
            </div>
        </details>
    </section>

    @if ($reporte)
        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Validaciones automáticas</h3>
                    <p class="mt-1 text-xs text-slate-500">Detecta huecos, materias sin docente y traslapes antes de imprimir.</p>
                </div>
                @if (($alertas['total'] ?? 0) === 0)
                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                        <flux:icon.check-circle class="h-4 w-4" /> Sin incidencias
                    </span>
                @else
                    <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                        {{ $alertas['total'] }} incidencia(s)
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
                <div class="rounded-xl bg-rose-50 p-3 dark:bg-rose-950/20">
                    <p class="text-xs font-bold text-rose-600">Sin docente</p>
                    <p class="mt-1 text-xl font-black text-rose-800 dark:text-rose-300">{{ collect($alertas['sin_docente'] ?? [])->count() }}</p>
                </div>
                <div class="rounded-xl bg-amber-50 p-3 dark:bg-amber-950/20">
                    <p class="text-xs font-bold text-amber-600">Espacios vacíos</p>
                    <p class="mt-1 text-xl font-black text-amber-800 dark:text-amber-300">{{ collect($alertas['espacios_vacios'] ?? [])->count() }}</p>
                </div>
                <div class="rounded-xl bg-violet-50 p-3 dark:bg-violet-950/20">
                    <p class="text-xs font-bold text-violet-600">Traslapes docente</p>
                    <p class="mt-1 text-xl font-black text-violet-800 dark:text-violet-300">{{ collect($alertas['traslapes_docente'] ?? [])->count() }}</p>
                </div>
                <div class="rounded-xl bg-blue-50 p-3 dark:bg-blue-950/20">
                    <p class="text-xs font-bold text-blue-600">Traslapes grupo</p>
                    <p class="mt-1 text-xl font-black text-blue-800 dark:text-blue-300">{{ collect($alertas['traslapes_grupo'] ?? [])->count() }}</p>
                </div>
                <div class="rounded-xl bg-lime-50 p-3 dark:bg-lime-950/20">
                    <p class="text-xs font-bold text-lime-700">Receso inconsistente</p>
                    <p class="mt-1 text-xl font-black text-lime-900 dark:text-lime-300">{{ !empty($alertas['receso_inconsistente']) ? 'Sí' : 'No' }}</p>
                </div>
            </div>

            @if (($alertas['total'] ?? 0) > 0)
                <details class="mt-4 rounded-xl border border-slate-200 dark:border-neutral-800">
                    <summary class="cursor-pointer p-3 text-sm font-black text-slate-700 dark:text-slate-200">Ver primeras incidencias</summary>
                    <div class="border-t border-slate-100 p-4 text-xs text-slate-600 dark:border-neutral-800 dark:text-slate-300">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div>
                                <p class="mb-2 font-black text-slate-900 dark:text-white">Materias / talleres sin docente</p>
                                @forelse (collect($alertas['sin_docente'] ?? [])->take(8) as $item)
                                    <p class="mb-1">• {{ $item['materia'] }} · {{ implode(', ', $item['grupos']) }} · {{ $item['dia'] }} {{ $item['hora'] }}</p>
                                @empty
                                    <p class="text-slate-400">Sin incidencias.</p>
                                @endforelse
                            </div>
                            <div>
                                <p class="mb-2 font-black text-slate-900 dark:text-white">Espacios vacíos</p>
                                @forelse (collect($alertas['espacios_vacios'] ?? [])->take(8) as $item)
                                    <p class="mb-1">• {{ $item['grupo'] }} · {{ $item['dia'] }} · {{ $item['hora'] }}</p>
                                @empty
                                    <p class="text-slate-400">Sin incidencias.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </details>
            @endif
        </section>
    @endif

    <section class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-slate-100 p-5 dark:border-neutral-800">
            <div class="flex flex-wrap gap-2">
                @foreach ([
                    'asig' => 'ASIG · Carga docente',
                    'general' => 'FOR-HORGEN · Horario general',
                    'formatos' => 'FORM-HOR · Formatos',
                    'complementarias' => 'HOR-COMP. · Complementarias',
                ] as $clave => $texto)
                    <button type="button" wire:click="cambiarFormato('{{ $clave }}')"
                        @class([
                            'rounded-xl px-3.5 py-2 text-xs font-black transition',
                            'bg-[#006492] text-white shadow-sm' => $formato === $clave,
                            'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300 dark:hover:bg-neutral-800' => $formato !== $clave,
                        ])>
                        {{ $texto }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="p-5">
            @if (!$reporte || ($resumen['sesiones'] ?? 0) === 0)
                <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-14 text-center dark:border-neutral-700">
                    <p class="text-sm font-black text-slate-700 dark:text-slate-200">No hay horarios para los filtros seleccionados.</p>
                    <p class="mt-1 text-xs text-slate-500">Cambia el ciclo, grado, grupo o profesor para mostrar la vista previa.</p>
                </div>
            @elseif ($formato === 'asig')
                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-neutral-800">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                        <thead class="bg-slate-50 dark:bg-neutral-950/50">
                            <tr class="text-left text-[11px] font-black uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3">Docente</th>
                                <th class="px-4 py-3">Asignatura / taller</th>
                                <th class="px-4 py-3">Grupos</th>
                                <th class="px-4 py-3 text-center">Sesiones</th>
                                <th class="px-4 py-3 text-center">Horas reloj</th>
                                <th class="px-4 py-3">Nombramiento</th>
                                <th class="px-4 py-3">Clave</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                            @foreach ($reporte['carga_docente'] as $fila)
                                @php $laboral = $datosLaborales[$fila['profesor_id']] ?? []; @endphp
                                <tr>
                                    <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">{{ $fila['docente'] }}</td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                        {{ $fila['materia'] }}
                                        @if ($fila['tipo'] === 'taller')
                                            <span class="ml-1 rounded bg-lime-100 px-1.5 py-0.5 text-[10px] font-black text-lime-700">Taller</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500">{{ implode(', ', $fila['grupos']) }}</td>
                                    <td class="px-4 py-3 text-center font-black">{{ $fila['sesiones_semanales'] }}</td>
                                    <td class="px-4 py-3 text-center font-black">{{ number_format($fila['horas_reloj'], 2) }}</td>
                                    <td class="px-4 py-3 text-xs">{{ filled($laboral['nombramiento'] ?? null) ? $laboral['nombramiento'] : 'S/C' }}</td>
                                    <td class="px-4 py-3 text-xs">{{ filled($laboral['clave_presupuestal'] ?? null) ? $laboral['clave_presupuestal'] : 'S/C' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif ($formato === 'general')
                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-neutral-800">
                    <table class="min-w-[1050px] w-full border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#006492] text-white">
                                <th class="border border-white/20 px-3 py-2.5 text-center font-black">HORA</th>
                                @foreach ($reporte['tabla_general']['dias'] as $dia)
                                    <th class="border border-white/20 px-3 py-2.5 text-center font-black">{{ mb_strtoupper($dia->dia) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reporte['tabla_general']['filas'] as $fila)
                                @if ($fila['es_receso'])
                                    <tr class="bg-lime-50 text-lime-800 dark:bg-lime-950/20 dark:text-lime-300">
                                        <td class="border border-slate-200 px-3 py-2 text-center font-black dark:border-neutral-800">{{ $fila['hora'] }}</td>
                                        <td colspan="{{ max(1, $dias->count()) }}" class="border border-slate-200 px-3 py-3 text-center font-black tracking-[0.18em] dark:border-neutral-800">RECESO</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td class="border border-slate-200 bg-slate-50 px-3 py-2 text-center font-black dark:border-neutral-800 dark:bg-neutral-950/50">{{ $fila['hora'] }}</td>
                                        @foreach ($reporte['tabla_general']['dias'] as $dia)
                                            <td class="min-w-48 border border-slate-200 p-2 align-top dark:border-neutral-800">
                                                @forelse ($fila['celdas'][$dia->id] ?? [] as $item)
                                                    <div class="mb-1.5 rounded-lg bg-slate-50 p-2 dark:bg-neutral-950/50">
                                                        <p class="font-black text-slate-900 dark:text-white">{{ $item['grupo'] }} · {{ $item['nombre'] }}</p>
                                                        <p class="mt-0.5 text-[10px] text-slate-500">{{ $item['profesor'] }}</p>
                                                    </div>
                                                @empty
                                                    <span class="text-slate-300">—</span>
                                                @endforelse
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif ($formato === 'formatos')
                <div class="space-y-6">
                    <div>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h4 class="text-sm font-black text-slate-900 dark:text-white">Horarios individuales por docente</h4>
                            @if (!$profesor_id && collect($reporte['formatos_docentes'])->count() > 2)
                                <span class="text-xs text-slate-400">Vista previa: primeros 2. El PDF/Excel incluye todos.</span>
                            @endif
                        </div>
                        @foreach (collect($reporte['formatos_docentes'])->take($profesor_id ? 20 : 2) as $matriz)
                            @include('livewire.accion.generales.partials.horario-carga-matriz', ['matriz' => $matriz, 'dias' => $dias, 'tipoMatriz' => 'Docente'])
                        @endforeach
                    </div>

                    <div>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h4 class="text-sm font-black text-slate-900 dark:text-white">Horarios por grupo</h4>
                            @if (!$grupo_id && collect($reporte['formatos_grupos'])->count() > 2)
                                <span class="text-xs text-slate-400">Vista previa: primeros 2. El PDF/Excel incluye todos.</span>
                            @endif
                        </div>
                        @foreach (collect($reporte['formatos_grupos'])->take($grupo_id ? 20 : 2) as $matriz)
                            @include('livewire.accion.generales.partials.horario-carga-matriz', ['matriz' => $matriz, 'dias' => $dias, 'tipoMatriz' => 'Grupo'])
                        @endforeach
                    </div>
                </div>
            @else
                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-neutral-800">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                        <thead class="bg-slate-50 dark:bg-neutral-950/50">
                            <tr class="text-left text-[11px] font-black uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Actividad</th>
                                <th class="px-4 py-3">Docente</th>
                                <th class="px-4 py-3">Grupos</th>
                                <th class="px-4 py-3 text-center">Sesiones</th>
                                <th class="px-4 py-3 text-center">Horas reloj</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                            @forelse ($reporte['complementarias'] as $fila)
                                <tr>
                                    <td class="px-4 py-3 text-xs font-black text-lime-700">{{ $fila['tipo'] }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">{{ $fila['nombre'] }}</td>
                                    <td class="px-4 py-3">{{ $fila['docente'] }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-500">{{ implode(', ', $fila['grupos']) }}</td>
                                    <td class="px-4 py-3 text-center font-black">{{ $fila['sesiones_semanales'] }}</td>
                                    <td class="px-4 py-3 text-center font-black">{{ number_format($fila['horas_reloj'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No hay materias complementarias o talleres en los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>
