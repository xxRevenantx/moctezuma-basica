<div class="space-y-6" x-data="{ tab: @entangle('tab').live, gestionModal: false }"
    x-on:abrir-modal-gestion-persona-nivel.window="gestionModal = true"
    x-on:cerrar-modal-gestion-persona-nivel.window="gestionModal = false">

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] via-blue-600 to-[#88AC2E]"></div>
        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#006492] dark:text-sky-300">Gestión integral</p>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Plantilla de personal por nivel</h1>
                    <p class="mt-1 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
                        Asignaciones generales, carga laboral, titulares, expediente, movimientos y reportes. Los PDF existentes no fueron modificados.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('misrutas.plantilla-personal.reporte', ['tipo' => 'plantilla', 'formato' => 'pdf']) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-2xl bg-[#006492] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-sky-800">
                        <flux:icon.document-arrow-down class="h-4 w-4" /> Nueva plantilla PDF
                    </a>
                    <a href="{{ route('misrutas.plantilla-personal.reporte', ['tipo' => 'carga', 'formato' => 'excel']) }}"
                        class="inline-flex items-center gap-2 rounded-2xl border border-[#88AC2E]/40 bg-[#88AC2E]/10 px-4 py-2.5 text-sm font-bold text-[#557015] transition hover:bg-[#88AC2E]/20 dark:text-lime-300">
                        <flux:icon.table-cells class="h-4 w-4" /> Carga Excel
                    </a>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
                @php
                    $cards = [
                        ['Personal', $resumen['personas'], 'users', 'text-blue-700 bg-blue-50 dark:bg-blue-950/40 dark:text-blue-300'],
                        ['Activas', $resumen['asignaciones_activas'], 'check-circle', 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300'],
                        ['Bajas', $resumen['asignaciones_baja'], 'x-circle', 'text-rose-700 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-300'],
                        ['Sobrecarga', $resumen['sobrecargas'], 'exclamation-triangle', 'text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300'],
                        ['Exp. incompleto', $resumen['expedientes_incompletos'], 'folder-open', 'text-violet-700 bg-violet-50 dark:bg-violet-950/40 dark:text-violet-300'],
                        ['Duplicados', $resumen['duplicados'], 'square-2-stack', 'text-orange-700 bg-orange-50 dark:bg-orange-950/40 dark:text-orange-300'],
                        ['Sin titular', $resumen['grupos_sin_titular'], 'academic-cap', 'text-slate-700 bg-slate-100 dark:bg-neutral-800 dark:text-slate-200'],
                    ];
                @endphp

                @foreach ($cards as [$label, $value, $icon, $class])
                    <div class="rounded-2xl border border-slate-200 p-3 dark:border-neutral-800 {{ $class }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wide opacity-75">{{ $label }}</p>
                                <p class="mt-1 text-2xl font-black">{{ number_format($value) }}</p>
                            </div>
                            <flux:icon :name="$icon" class="h-5 w-5 opacity-70" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <nav class="flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        @foreach ([
            'plantilla' => ['Plantilla actual', 'users'],
            'carga' => ['Carga laboral', 'clock'],
            'expedientes' => ['Expedientes', 'folder-open'],
            'historial' => ['Movimientos e historial', 'clock'],
            'reportes' => ['Reportes', 'document-chart-bar'],
        ] as $key => [$label, $icon])
            <button type="button" @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'bg-gradient-to-r from-[#006492] to-blue-700 text-white shadow' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-neutral-800'"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition">
                <flux:icon :name="$icon" class="h-4 w-4" /> {{ $label }}
            </button>
        @endforeach
    </nav>

    <section x-show="tab === 'plantilla'" x-cloak class="space-y-5">
        <livewire:persona-nivel.crear-persona-nivel />
        <livewire:persona-nivel.mostrar-persona-nivel />
    </section>

    <section x-show="tab === 'carga'" x-cloak class="space-y-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="grid gap-3 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <label class="mb-1 block text-xs font-bold text-slate-600 dark:text-slate-300">Buscar</label>
                    <input wire:model.live.debounce.350ms="search" type="search"
                        placeholder="Nombre, función, especialidad o materia..."
                        class="w-full rounded-2xl border-slate-200 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-950" />
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-1 block text-xs font-bold text-slate-600 dark:text-slate-300">Nivel</label>
                    <select wire:model.live="nivelFiltro" class="w-full rounded-2xl border-slate-200 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                        <option value="">Todos</option>
                        @foreach ($niveles as $nivel)
                            <option value="{{ $nivel->id }}">{{ $nivel->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-600 dark:text-slate-300">Estado</label>
                    <select wire:model.live="estadoFiltro" class="w-full rounded-2xl border-slate-200 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                        <option value="todos">Todos</option>
                        <option value="activo">Activo</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
                <div class="flex items-end lg:col-span-2">
                    <button type="button" wire:click='seleccionarVisibles(@json($filas->pluck("modelo.id")->values()))'
                        class="w-full rounded-2xl border border-blue-200 bg-blue-50 px-3 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300">
                        Seleccionar visibles
                    </button>
                </div>
            </div>
        </div>

        @if (count($seleccionados))
            <div class="rounded-3xl border border-blue-200 bg-blue-50/70 p-4 shadow-sm dark:border-blue-900 dark:bg-blue-950/30">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                    <div class="min-w-44">
                        <p class="text-xs font-black uppercase text-blue-700 dark:text-blue-300">{{ count($seleccionados) }} seleccionadas</p>
                        <button wire:click="limpiarSeleccion" class="mt-1 text-xs font-bold text-slate-500 underline">Limpiar selección</button>
                    </div>
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-bold">Acción</label>
                        <select wire:model.live="accionMasiva" class="w-full rounded-xl border-blue-200 bg-white text-sm dark:border-blue-900 dark:bg-neutral-950">
                            <option value="">Selecciona...</option>
                            <option value="baja">Dar de baja</option>
                            <option value="mover">Mover a grado / grupo</option>
                            <option value="duplicar">Duplicar a otro nivel / grado / grupo</option>
                            <option value="eliminar">Eliminar definitivamente</option>
                        </select>
                    </div>

                    @if (in_array($accionMasiva, ['mover', 'duplicar'], true))
                        <div class="flex-1">
                            <label class="mb-1 block text-xs font-bold">Nivel destino</label>
                            <select wire:model.live="nivelDestinoId" class="w-full rounded-xl border-blue-200 bg-white text-sm dark:border-blue-900 dark:bg-neutral-950">
                                <option value="">Selecciona...</option>
                                @foreach ($niveles as $nivel)
                                    <option value="{{ $nivel->id }}">{{ $nivel->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if (in_array($accionMasiva, ['mover', 'duplicar'], true))
                        <div class="flex-1">
                            <label class="mb-1 block text-xs font-bold">Grado destino</label>
                            <select wire:model.live="gradoDestinoId" class="w-full rounded-xl border-blue-200 bg-white text-sm dark:border-blue-900 dark:bg-neutral-950">
                                <option value="">Conservar / sin grado</option>
                                @foreach ($gradosDestino as $grado)
                                    <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="mb-1 block text-xs font-bold">Grupo destino</label>
                            <select wire:model.live="grupoDestinoId" class="w-full rounded-xl border-blue-200 bg-white text-sm dark:border-blue-900 dark:bg-neutral-950">
                                <option value="">Conservar / sin grupo</option>
                                @foreach ($gruposDestino as $grupo)
                                    <option value="{{ $grupo->id }}">{{ $grupo->asignacionGrupo?->nombre ?? 'Grupo' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($accionMasiva === 'baja')
                        <div class="flex-[2]">
                            <label class="mb-1 block text-xs font-bold">Motivo</label>
                            <input wire:model="motivoMasivo" class="w-full rounded-xl border-blue-200 bg-white text-sm dark:border-blue-900 dark:bg-neutral-950" placeholder="Motivo de la baja" />
                        </div>
                    @endif

                    <button type="button"
                        @click="if ('{{ $accionMasiva }}' === 'eliminar') { Swal.fire({title:'¿Eliminar asignaciones?',text:'Esta acción es definitiva.',icon:'warning',showCancelButton:true,confirmButtonText:'Sí, eliminar',cancelButtonText:'Cancelar'}).then(r => r.isConfirmed && $wire.ejecutarAccionMasiva()) } else { $wire.ejecutarAccionMasiva() }"
                        class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-black text-white hover:bg-blue-800">
                        Aplicar
                    </button>
                </div>
            </div>
        @endif

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="overflow-x-auto">
                <table class="min-w-[1350px] w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-black uppercase tracking-wide text-slate-500 dark:bg-neutral-950 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3"></th>
                            <th class="px-4 py-3">Personal</th>
                            <th class="px-4 py-3">Nivel / función</th>
                            <th class="px-4 py-3">Grado / grupo</th>
                            <th class="px-4 py-3">Materia</th>
                            <th class="px-4 py-3 text-center">Frente a grupo</th>
                            <th class="px-4 py-3 text-center">Administrativas</th>
                            <th class="px-4 py-3 text-center">Total / límite</th>
                            <th class="px-4 py-3">Expediente</th>
                            <th class="px-4 py-3">Vigencia</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @forelse ($filas as $fila)
                            @php
                                $d = $fila['modelo'];
                                $p = $d->cabecera?->persona;
                                $c = $fila['carga'];
                                $cg = $fila['carga_global'];
                                $e = $fila['expediente'];
                                $nombre = trim(($p?->titulo ? $p->titulo.' ' : '').($p?->nombre ?? '').' '.($p?->apellido_paterno ?? '').' '.($p?->apellido_materno ?? ''));
                            @endphp
                            <tr wire:key="gestion-pn-{{ $d->id }}" class="align-top hover:bg-slate-50/70 dark:hover:bg-neutral-800/40">
                                <td class="px-4 py-4">
                                    <input type="checkbox" wire:model.live="seleccionados" value="{{ $d->id }}" class="rounded border-slate-300 text-blue-700" />
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-black text-slate-900 dark:text-white">{{ $nombre ?: 'Sin nombre' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $p?->grado_estudios ?: 'Sin grado de estudios' }} · {{ $p?->especialidad ?: 'Sin especialidad' }}</p>
                                    <details class="mt-2 text-xs">
                                        <summary class="cursor-pointer font-bold text-blue-700 dark:text-blue-300">Datos profesionales</summary>
                                        <div class="mt-2 space-y-1 rounded-xl bg-slate-50 p-2 dark:bg-neutral-950">
                                            <p><b>Correo:</b> {{ $p?->correo ?: '—' }}</p>
                                            <p><b>Teléfono:</b> {{ $p?->telefono_movil ?: '—' }}</p>
                                            <p><b>Cédula:</b> {{ implode(', ', $fila['cedulas']) ?: '—' }}</p>
                                            <p><b>Ingreso SEG:</b> {{ optional($d->cabecera?->ingreso_seg)->format('d/m/Y') ?: '—' }}</p>
                                            <p><b>Ingreso SEP:</b> {{ optional($d->cabecera?->ingreso_sep)->format('d/m/Y') ?: '—' }}</p>
                                            <p><b>Ingreso C.T.:</b> {{ optional($d->cabecera?->ingreso_ct)->format('d/m/Y') ?: '—' }}</p>
                                        </div>
                                    </details>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-bold">{{ $d->cabecera?->nivel?->nombre ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $d->personaRole?->rolePersona?->nombre ?? 'Sin función' }}</p>
                                    @if ($d->es_titular)
                                        <span class="mt-2 inline-flex rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-black text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                                            {{ $d->es_titular_principal ? 'Titular principal' : 'Titular auxiliar' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-bold">{{ $d->grado?->nombre ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $d->grupo?->asignacionGrupo?->nombre ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-bold">{{ $d->nombreMateria() ?: 'Sin materia vinculada' }}</p>
                                    <p class="text-xs text-slate-500">{{ $d->asignacion_materia_id ? 'Vinculada al horario' : ($d->materia_manual ? 'Registro manual' : 'Función administrativa') }}</p>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <p class="text-lg font-black">{{ number_format($c['horas_frente_grupo'], 2) }} h</p>
                                    <p class="text-[10px] text-slate-500">Auto {{ number_format($c['horas_automaticas'], 2) }} · ajuste {{ number_format($c['ajuste'], 2) }}</p>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <p class="text-lg font-black">{{ number_format($cg['horas_administrativas'], 2) }} h</p>
                                    <p class="text-[10px] text-slate-500">General {{ number_format($cg['horas_administrativas_generales'], 2) }} · funciones {{ number_format($cg['horas_administrativas_detalle'], 2) }}</p>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded-xl px-3 py-2 text-sm font-black {{ $cg['sobrecarga'] ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                                        {{ number_format($cg['total'], 2) }} / {{ number_format($cg['limite'], 2) }} h
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-200 dark:bg-neutral-700">
                                            <div class="h-full rounded-full {{ $e['porcentaje'] === 100 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $e['porcentaje'] }}%"></div>
                                        </div>
                                        <b>{{ $e['porcentaje'] }}%</b>
                                    </div>
                                    @if ($e['faltantes'])
                                        <details class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                                            <summary class="cursor-pointer font-bold">{{ count($e['faltantes']) }} faltantes</summary>
                                            <ul class="mt-1 list-disc pl-4">
                                                @foreach ($e['faltantes'] as $faltante)<li>{{ $faltante }}</li>@endforeach
                                            </ul>
                                        </details>
                                    @else
                                        <p class="mt-1 text-xs font-bold text-emerald-600">Completo</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2 py-1 text-[10px] font-black uppercase {{ $d->estado === 'activo' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300' }}">{{ $d->estado }}</span>
                                    <p class="mt-2 text-xs text-slate-500">{{ optional($d->fecha_inicio)->format('d/m/Y') ?: '—' }} a {{ optional($d->fecha_fin)->format('d/m/Y') ?: 'vigente' }}</p>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <button wire:click="editarGestion({{ $d->id }})" class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300">
                                        Gestionar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="px-6 py-16 text-center text-slate-500">No se encontraron asignaciones.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section x-show="tab === 'expedientes'" x-cloak>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($filas->unique(fn($fila) => $fila['modelo']->cabecera?->persona_id) as $fila)
                @php $d=$fila['modelo']; $p=$d->cabecera?->persona; $e=$fila['expediente']; @endphp
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-black text-slate-950 dark:text-white">{{ trim(($p?->nombre ?? '').' '.($p?->apellido_paterno ?? '').' '.($p?->apellido_materno ?? '')) }}</p>
                            <p class="text-xs text-slate-500">{{ $p?->grado_estudios ?: 'Sin grado registrado' }} · {{ $p?->especialidad ?: 'Sin especialidad' }}</p>
                        </div>
                        <span class="rounded-2xl px-3 py-2 text-lg font-black {{ $e['porcentaje'] === 100 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $e['porcentaje'] }}%</span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-neutral-700">
                        <div class="h-full {{ $e['porcentaje'] === 100 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width:{{ $e['porcentaje'] }}%"></div>
                    </div>
                    <div class="mt-4 rounded-2xl bg-slate-50 p-3 text-xs dark:bg-neutral-950">
                        @if ($e['faltantes'])
                            <p class="font-black text-amber-700 dark:text-amber-300">Documentos faltantes</p>
                            <ul class="mt-2 list-disc space-y-1 pl-4 text-slate-600 dark:text-slate-300">
                                @foreach ($e['faltantes'] as $faltante)<li>{{ $faltante }}</li>@endforeach
                            </ul>
                        @else
                            <p class="font-black text-emerald-700 dark:text-emerald-300">Expediente completo</p>
                        @endif
                    </div>
                    <a href="{{ route('misrutas.expedientes-personal.show', $p) }}" target="_blank" class="mt-4 inline-flex text-xs font-black text-blue-700 underline dark:text-blue-300">Abrir expediente completo</a>
                </article>
            @endforeach
        </div>
    </section>

    <section x-show="tab === 'historial'" x-cloak>
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-slate-200 p-5 dark:border-neutral-800">
                <h2 class="text-lg font-black">Últimos movimientos</h2>
                <p class="text-sm text-slate-500">Registro automático de altas, cambios, bajas, duplicaciones y eliminaciones.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[950px] w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-black uppercase text-slate-500 dark:bg-neutral-950">
                        <tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Persona</th><th class="px-5 py-3">Nivel</th><th class="px-5 py-3">Acción</th><th class="px-5 py-3">Descripción</th><th class="px-5 py-3">Usuario</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @forelse ($historial as $mov)
                            <tr>
                                <td class="px-5 py-3 whitespace-nowrap">{{ optional($mov->fecha)->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3 font-bold">{{ trim(($mov->persona?->nombre ?? '').' '.($mov->persona?->apellido_paterno ?? '').' '.($mov->persona?->apellido_materno ?? '')) ?: 'Registro eliminado' }}</td>
                                <td class="px-5 py-3">{{ $mov->nivel?->nombre ?? '—' }}</td>
                                <td class="px-5 py-3"><span class="rounded-full bg-blue-50 px-2 py-1 text-[10px] font-black uppercase text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">{{ str_replace('_', ' ', $mov->accion) }}</span></td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $mov->descripcion ?: '—' }}</td>
                                <td class="px-5 py-3">{{ $mov->usuario?->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">Aún no hay movimientos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section x-show="tab === 'reportes'" x-cloak class="space-y-4">
        @php
            $filtrosReporte = array_filter([
                'nivel_id' => $reporteNivelId,
                'grado_id' => $reporteGradoId,
                'grupo_id' => $reporteGrupoId,
                'persona_id' => $reportePersonaId,
            ], fn ($valor) => filled($valor));
        @endphp

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="mb-4">
                <h3 class="font-black">Filtros de los nuevos reportes</h3>
                <p class="text-sm text-slate-500">Permiten generar listas por nivel, grado, grupo o una persona específica.</p>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="mb-1 block text-xs font-bold">Nivel</label><select wire:model.live="reporteNivelId" class="w-full rounded-xl border-slate-200 text-sm dark:border-neutral-700 dark:bg-neutral-950"><option value="">Todos</option>@foreach($niveles as $nivel)<option value="{{ $nivel->id }}">{{ $nivel->nombre }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-bold">Grado</label><select wire:model.live="reporteGradoId" class="w-full rounded-xl border-slate-200 text-sm dark:border-neutral-700 dark:bg-neutral-950"><option value="">Todos</option>@foreach($gradosReporte as $grado)<option value="{{ $grado->id }}">{{ $grado->nombre }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-bold">Grupo</label><select wire:model.live="reporteGrupoId" class="w-full rounded-xl border-slate-200 text-sm dark:border-neutral-700 dark:bg-neutral-950"><option value="">Todos</option>@foreach($gruposReporte as $grupo)<option value="{{ $grupo->id }}">{{ $grupo->asignacionGrupo?->nombre ?? 'Grupo' }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-bold">Persona</label><select wire:model.live="reportePersonaId" class="w-full rounded-xl border-slate-200 text-sm dark:border-neutral-700 dark:bg-neutral-950"><option value="">Todas</option>@foreach($personasReporte as $persona)<option value="{{ $persona->id }}">{{ trim(($persona->titulo ? $persona->titulo.' ' : '').$persona->nombre.' '.$persona->apellido_paterno.' '.$persona->apellido_materno) }}</option>@endforeach</select></div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['plantilla','Plantilla completa','Personal, funciones, niveles, grados, grupos, estado y expediente.'],
                ['carga','Carga laboral','Horas de horario, ajustes, horas administrativas, total y alertas.'],
                ['historial','Historial laboral','Movimientos y cambios registrados en la plantilla.'],
            ] as [$tipo,$titulo,$descripcion])
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300"><flux:icon.document-chart-bar class="h-5 w-5" /></div>
                    <h3 class="mt-4 text-lg font-black">{{ $titulo }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $descripcion }}</p>
                    <div class="mt-5 flex gap-2">
                        <a target="_blank" href="{{ route('misrutas.plantilla-personal.reporte', array_merge(['tipo'=>$tipo,'formato'=>'pdf'], $filtrosReporte)) }}" class="rounded-xl bg-[#006492] px-4 py-2 text-xs font-black text-white">PDF nuevo</a>
                        <a href="{{ route('misrutas.plantilla-personal.reporte', array_merge(['tipo'=>$tipo,'formato'=>'excel'], $filtrosReporte)) }}" class="rounded-xl bg-[#88AC2E] px-4 py-2 text-xs font-black text-white">Excel</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <div x-show="gestionModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-sm" @keydown.escape.window="gestionModal=false">
        <div class="mx-auto my-6 max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-neutral-900" @click.outside="gestionModal=false">
            <div class="h-1.5 bg-gradient-to-r from-[#006492] to-[#88AC2E]"></div>
            <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-neutral-800">
                <div><h3 class="text-xl font-black">Gestionar asignación</h3><p class="text-sm text-slate-500">Vigencia, titular, materia, carga laboral y estado.</p></div>
                <button type="button" @click="gestionModal=false; $wire.cerrarGestion()" class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-xl dark:bg-neutral-800">×</button>
            </div>

            <form wire:submit="guardarGestion" class="space-y-5 p-5">
                <section class="rounded-2xl border border-sky-200 bg-sky-50/60 p-4 dark:border-sky-900 dark:bg-sky-950/20">
                    <div class="mb-4">
                        <h4 class="font-black text-sky-900 dark:text-sky-200">Asignación general de la persona al nivel</h4>
                        <p class="text-xs text-sky-700/80 dark:text-sky-300/80">Esta vigencia y carga administrativa se aplican a la cabecera persona-nivel.</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div><label class="mb-1 block text-xs font-bold">Inicio general *</label><input type="date" wire:model="editCabFechaInicio" class="w-full rounded-xl border-sky-200 dark:border-sky-900 dark:bg-neutral-950" />@error('editCabFechaInicio')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="mb-1 block text-xs font-bold">Término general</label><input type="date" wire:model="editCabFechaFin" class="w-full rounded-xl border-sky-200 dark:border-sky-900 dark:bg-neutral-950" />@error('editCabFechaFin')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="mb-1 block text-xs font-bold">Estado general</label><select wire:model="editCabEstado" class="w-full rounded-xl border-sky-200 dark:border-sky-900 dark:bg-neutral-950" @disabled($editCabEstadoOriginal === 'baja')><option value="activo">Activo</option><option value="baja">Baja</option></select>@error('editCabEstado')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <div><label class="mb-1 block text-xs font-bold">Horas administrativas generales</label><input type="number" min="0" step="0.25" wire:model="editCabHorasAdministrativas" class="w-full rounded-xl border-sky-200 dark:border-sky-900 dark:bg-neutral-950" /></div>
                        <div><label class="mb-1 block text-xs font-bold">Límite general semanal</label><input type="number" min="1" step="0.5" wire:model="editCabLimiteHoras" class="w-full rounded-xl border-sky-200 dark:border-sky-900 dark:bg-neutral-950" /></div>
                        <div><label class="mb-1 block text-xs font-bold">Actividad administrativa general</label><input wire:model="editCabActividadAdministrativa" class="w-full rounded-xl border-sky-200 dark:border-sky-900 dark:bg-neutral-950" placeholder="Ej. Coordinación del nivel" /></div>
                    </div>
                    @if ($editCabEstado === 'baja')
                        <div class="mt-4 grid gap-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900 dark:bg-rose-950/20 md:grid-cols-3">
                            <div><label class="mb-1 block text-xs font-bold">Fecha de baja general</label><input type="date" wire:model="editCabFechaBaja" class="w-full rounded-xl border-rose-200 dark:border-rose-900 dark:bg-neutral-950" /></div>
                            <div class="md:col-span-2"><label class="mb-1 block text-xs font-bold">Motivo de baja general</label><input wire:model="editCabMotivoBaja" class="w-full rounded-xl border-rose-200 dark:border-rose-900 dark:bg-neutral-950" placeholder="Motivo de conclusión de la asignación al nivel" /></div>
                        </div>
                    @endif
                    <div class="mt-4"><label class="mb-1 block text-xs font-bold">Observaciones generales</label><textarea wire:model="editCabObservaciones" rows="2" class="w-full rounded-xl border-sky-200 dark:border-sky-900 dark:bg-neutral-950"></textarea></div>
                </section>

                <div>
                    <h4 class="font-black text-slate-900 dark:text-white">Función o asignación individual</h4>
                    <p class="text-xs text-slate-500">Los siguientes datos pertenecen únicamente a esta función, grupo o materia.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div><label class="mb-1 block text-xs font-bold">Fecha de inicio *</label><input type="date" wire:model="editFechaInicio" class="w-full rounded-xl border-slate-200 dark:border-neutral-700 dark:bg-neutral-950" />@error('editFechaInicio')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-xs font-bold">Fecha de término</label><input type="date" wire:model="editFechaFin" class="w-full rounded-xl border-slate-200 dark:border-neutral-700 dark:bg-neutral-950" />@error('editFechaFin')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-xs font-bold">Estado</label><select wire:model.live="editEstado" class="w-full rounded-xl border-slate-200 dark:border-neutral-700 dark:bg-neutral-950" @disabled($editEstadoOriginal === 'baja')><option value="activo">Activo</option><option value="baja">Baja</option></select>@error('editEstado')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 dark:border-neutral-700"><input type="checkbox" wire:model.live="editEsTitular" class="rounded text-blue-700" /><span><b class="block">Titular de grupo</b><small class="text-slate-500">Puede ser principal o auxiliar.</small></span></label>
                    <label class="flex items-center gap-3 rounded-2xl border border-indigo-200 bg-indigo-50/50 p-4 dark:border-indigo-900 dark:bg-indigo-950/20"><input type="checkbox" wire:model.live="editEsTitularPrincipal" class="rounded text-indigo-700" /><span><b class="block">Titular principal</b><small class="text-slate-500">Si ya existe otro, se advertirá pero permitirá guardar.</small></span></label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-bold">Materia vinculada al horario</label><select wire:model="editAsignacionMateriaId" class="w-full rounded-xl border-slate-200 text-sm dark:border-neutral-700 dark:bg-neutral-950"><option value="">Sin vínculo</option>@foreach($asignacionesMateriaDisponibles as $a)<option value="{{ $a->id }}">{{ $a->materia?->materia ?? 'Materia' }} · {{ $a->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo' }}</option>@endforeach</select>@error('editAsignacionMateriaId')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-xs font-bold">Materia o actividad manual</label><input wire:model="editMateriaManual" class="w-full rounded-xl border-slate-200 dark:border-neutral-700 dark:bg-neutral-950" placeholder="Solo cuando no exista una asignación vinculable" /></div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div><label class="mb-1 block text-xs font-bold">Ajuste horas frente a grupo</label><input type="number" step="0.25" wire:model="editAjusteHoras" class="w-full rounded-xl border-slate-200 dark:border-neutral-700 dark:bg-neutral-950" /><p class="mt-1 text-[10px] text-slate-500">Se suma o resta al cálculo automático del horario.</p></div>
                    <div><label class="mb-1 block text-xs font-bold">Horas administrativas</label><input type="number" min="0" step="0.25" wire:model="editHorasAdministrativas" class="w-full rounded-xl border-slate-200 dark:border-neutral-700 dark:bg-neutral-950" /></div>
                    <div><label class="mb-1 block text-xs font-bold">Límite semanal</label><input type="number" min="1" step="0.5" wire:model="editLimiteHoras" class="w-full rounded-xl border-slate-200 dark:border-neutral-700 dark:bg-neutral-950" /></div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-bold">Actividad administrativa</label><select wire:model="editActividadAdministrativaId" class="w-full rounded-xl border-slate-200 dark:border-neutral-700 dark:bg-neutral-950"><option value="">Sin catálogo</option>@foreach($actividades as $actividad)<option value="{{ $actividad->id }}">{{ $actividad->nombre }} ({{ number_format($actividad->horas_sugeridas,2) }} h sugeridas)</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-xs font-bold">Otra actividad administrativa</label><input wire:model="editActividadAdministrativaManual" class="w-full rounded-xl border-slate-200 dark:border-neutral-700 dark:bg-neutral-950" placeholder="Descripción libre" /></div>
                </div>

                @if ($editEstado === 'baja')
                    <div class="grid gap-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900 dark:bg-rose-950/20 md:grid-cols-3">
                        <div><label class="mb-1 block text-xs font-bold">Fecha de baja</label><input type="date" wire:model="editFechaBaja" class="w-full rounded-xl border-rose-200 dark:border-rose-900 dark:bg-neutral-950" /></div>
                        <div class="md:col-span-2"><label class="mb-1 block text-xs font-bold">Motivo de baja</label><input wire:model="editMotivoBaja" class="w-full rounded-xl border-rose-200 dark:border-rose-900 dark:bg-neutral-950" /></div>
                    </div>
                @endif

                <div><label class="mb-1 block text-xs font-bold">Observaciones</label><textarea wire:model="editObservaciones" rows="3" class="w-full rounded-xl border-slate-200 dark:border-neutral-700 dark:bg-neutral-950"></textarea></div>

                <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-neutral-800">
                    <button type="button" @click="gestionModal=false; $wire.cerrarGestion()" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold dark:border-neutral-700">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-gradient-to-r from-[#006492] to-blue-700 px-5 py-2 text-sm font-black text-white disabled:opacity-50">
                        <span wire:loading.remove wire:target="guardarGestion">Guardar cambios</span><span wire:loading wire:target="guardarGestion">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
