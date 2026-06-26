<div
    x-data="{
        confirmarReingreso(id, nombre) {
            Swal.fire({
                title: 'Registrar reingreso',
                html: `Se creará una nueva estancia para <b>${nombre}</b> y la baja anterior permanecerá en el historial.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Registrar reingreso',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#7c3aed'
            }).then((r) => r.isConfirmed && this.$wire.reactivarAlumno(id));
        }
    }"
    x-on:abrir-constancia-baja.window="window.open($event.detail.url, '_blank')"
    class="space-y-5"
>
    <div class="overflow-x-auto pb-1">
        <div class="flex min-w-max justify-center gap-2">
            @foreach ($niveles as $item)
                @php($activo = $slug_nivel === $item->slug)
                <a wire:navigate
                    href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'bajas']) }}"
                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition
                    {{ $activo ? 'border-red-600 bg-red-600 text-white shadow-lg shadow-red-600/20' : 'border-slate-200 bg-white text-slate-700 hover:border-red-300 hover:text-red-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200' }}">
                    <flux:icon.user-minus class="h-4 w-4" /> {{ $item->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="bg-gradient-to-r from-red-700 via-rose-700 to-fuchsia-800 p-5 text-white sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black">Estados, bajas, traslados y reingresos</h1>
                        @if ($cicloSeleccionado?->es_actual)
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold ring-1 ring-white/25">Ciclo actual</span>
                        @elseif ($cicloSeleccionado?->cerrado_at)
                            <span class="rounded-full bg-amber-300/20 px-3 py-1 text-xs font-bold ring-1 ring-amber-200/30">Histórico cerrado</span>
                        @else
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold ring-1 ring-white/25">Histórico</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-rose-100">Cada movimiento queda asociado al ciclo y corte elegidos; ninguna etapa anterior se elimina.</p>
                </div>
                <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/20">
                    <p class="text-xs font-bold uppercase tracking-wide text-rose-100">Contexto</p>
                    <p class="mt-1 font-black">{{ $cicloSeleccionado?->nombre ?? '—' }} · {{ $corteSeleccionado?->ciclo ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-4 dark:bg-neutral-700">
            @foreach ([['Activos', $total], ['Hombres', $hombres], ['Mujeres', $mujeres], ['No activos', $totalBajas]] as [$label, $value])
                <div class="bg-white p-4 dark:bg-neutral-900">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($value) }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="grid gap-3 md:grid-cols-3">
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Ciclo escolar</span>
                <select wire:model.live="ciclo_escolar_id" class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-800">
                    @foreach ($cicloEscolares as $item)
                        <option value="{{ $item->id }}">{{ $item->nombre }}{{ $item->es_actual ? ' · Actual' : ($item->cerrado_at ? ' · Cerrado' : '') }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Corte</span>
                <select wire:model.live="ciclo_id" class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-800">
                    @foreach ($ciclos as $item)
                        <option value="{{ $item->id }}">{{ $item->ciclo }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Buscar alumno</span>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input wire:model.live.debounce.350ms="search" type="search" placeholder="Nombre, matrícula o CURP"
                            class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 text-sm dark:border-neutral-700 dark:bg-neutral-800" />
                    </div>
                    @if ($search !== '')
                        <button type="button" wire:click="clearSearch" class="rounded-xl border border-slate-200 px-3 text-slate-500 hover:bg-slate-50 dark:border-neutral-700 dark:hover:bg-neutral-800">
                            <flux:icon.x-mark class="h-4 w-4" />
                        </button>
                    @endif
                </div>
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-red-200 bg-red-50/60 p-5 shadow-sm dark:border-red-900/50 dark:bg-red-950/20">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-lg font-black text-red-950 dark:text-red-100">Aplicar movimiento a alumnos activos</h2>
                <p class="text-sm text-red-800/80 dark:text-red-200/70">Se generará una nueva estancia no activa en el ciclo y corte seleccionados.</p>
            </div>
            <span class="rounded-full bg-red-200 px-3 py-1 text-xs font-black text-red-900 dark:bg-red-900/50 dark:text-red-100">{{ $this->selectedCount }} seleccionado(s)</span>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-red-700 dark:text-red-300">Tipo</span>
                <select wire:model="tipo_movimiento" class="w-full rounded-xl border-red-300 bg-white text-sm dark:border-red-800 dark:bg-neutral-900">
                    <option value="baja_definitiva">Baja definitiva</option>
                    <option value="baja_temporal">Baja temporal</option>
                    <option value="traslado">Traslado / cambio de escuela</option>
                    <option value="inactivo">Inactivo</option>
                    <option value="suspendido">Suspendido</option>
                </select>
            </label>
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-red-700 dark:text-red-300">Fecha</span>
                <input wire:model="fecha_baja" type="date" class="w-full rounded-xl border-red-300 bg-white text-sm dark:border-red-800 dark:bg-neutral-900" />
            </label>
            <label class="space-y-1.5 md:col-span-2">
                <span class="text-xs font-bold uppercase tracking-wide text-red-700 dark:text-red-300">Motivo <span class="normal-case font-medium">(opcional)</span></span>
                <input wire:model="motivo_baja" type="text" placeholder="Escribe el motivo cuando corresponda"
                    class="w-full rounded-xl border-red-300 bg-white text-sm dark:border-red-800 dark:bg-neutral-900" />
            </label>
            <label class="space-y-1.5 md:col-span-2 xl:col-span-3">
                <span class="text-xs font-bold uppercase tracking-wide text-red-700 dark:text-red-300">Observaciones</span>
                <textarea wire:model="observaciones_baja" rows="2" placeholder="Información adicional (opcional)"
                    class="w-full rounded-xl border-red-300 bg-white text-sm dark:border-red-800 dark:bg-neutral-900"></textarea>
            </label>
            <div class="flex items-end">
                <button type="button" wire:click="aplicarBaja" wire:loading.attr="disabled"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-700 px-4 py-3 text-sm font-black text-white transition hover:bg-red-800 disabled:opacity-50">
                    <flux:icon.user-minus class="h-4 w-4" /> Aplicar movimiento
                </button>
            </div>
        </div>
        @error('selected') <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
        @error('motivo_baja') <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="border-b border-slate-200 p-4 dark:border-neutral-700">
            <h2 class="font-black text-slate-900 dark:text-white">Alumnos activos disponibles</h2>
            <p class="text-sm text-slate-500">Marca los alumnos a los que se aplicará el estado no activo.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[1050px] w-full text-left text-sm">
                <thead class="bg-slate-900 text-xs uppercase text-white">
                    <tr>
                        <th class="px-4 py-3 text-center"><input type="checkbox" wire:model.live="selectPage" class="rounded text-red-600" /></th>
                        <th class="px-4 py-3">Matrícula</th>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">CURP</th>
                        <th class="px-4 py-3">Generación</th>
                        <th class="px-4 py-3">Grado / grupo</th>
                        <th class="px-4 py-3">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($rows as $row)
                        @php
                            $alumno = $row->inscripcion;
                            $matricula = $alumno?->matriculasAlumno?->firstWhere('nivel_id', $row->nivel_id)?->matricula ?: $alumno?->matricula;
                            $nombre = trim(($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '') . ' ' . ($alumno?->nombre ?? ''));
                        @endphp
                        <tr wire:key="baja-activo-{{ $row->id }}" class="hover:bg-red-50/40 dark:hover:bg-red-950/10">
                            <td class="px-4 py-4 text-center"><input type="checkbox" wire:model.live="selected" value="{{ $row->id }}" class="rounded border-slate-300 text-red-600" /></td>
                            <td class="px-4 py-4 font-black text-slate-900 dark:text-white">{{ $matricula ?: '—' }}</td>
                            <td class="px-4 py-4 font-bold text-slate-800 dark:text-slate-100">{{ $nombre }}</td>
                            <td class="px-4 py-4 text-slate-500">{{ $alumno?->curp ?: '—' }}</td>
                            <td class="px-4 py-4">{{ $row->generacion ? $row->generacion->anio_ingreso . '-' . $row->generacion->anio_egreso : '—' }}</td>
                            <td class="px-4 py-4">{{ $row->grado?->nombre ?? '—' }} · {{ $this->textoGrupo($row->grupo) }} @if($row->semestre)<span class="text-xs text-slate-500">/ Sem. {{ $row->semestre->numero }}</span>@endif</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">{{ $this->etiquetaEstatus($row->estatus) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-14 text-center text-slate-500">No hay alumnos activos en este ciclo y corte.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-neutral-700">{{ $rows->links(data: ['scrollTo' => false]) }}</div>
        @endif
    </section>

    <section class="rounded-3xl border border-violet-200 bg-violet-50/50 p-5 shadow-sm dark:border-violet-900/50 dark:bg-violet-950/15">
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <h2 class="font-black text-violet-950 dark:text-violet-100">Configuración de reingreso</h2>
                <p class="mt-1 text-sm text-violet-800/75 dark:text-violet-200/70">Estos datos se usarán al pulsar “Reingresar”.</p>
            </div>
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-violet-700 dark:text-violet-300">Fecha de reingreso</span>
                <input wire:model="fecha_reingreso" type="date" class="w-full rounded-xl border-violet-300 bg-white text-sm dark:border-violet-800 dark:bg-neutral-900" />
            </label>
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-violet-700 dark:text-violet-300">Motivo / nota</span>
                <input wire:model="motivo_reingreso" type="text" placeholder="Opcional" class="w-full rounded-xl border-violet-300 bg-white text-sm dark:border-violet-800 dark:bg-neutral-900" />
            </label>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="border-b border-slate-200 p-4 dark:border-neutral-700">
            <h2 class="font-black text-slate-900 dark:text-white">Estados no activos del contexto</h2>
            <p class="text-sm text-slate-500">El reingreso crea una estancia nueva y conserva el estado anterior como antecedente.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[1250px] w-full text-left text-sm">
                <thead class="bg-slate-900 text-xs uppercase text-white">
                    <tr>
                        <th class="px-4 py-3">Matrícula</th>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">Ubicación histórica</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Motivo / observaciones</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($bajasRows as $row)
                        @php
                            $alumno = $row->inscripcion;
                            $matricula = $alumno?->matriculasAlumno?->firstWhere('nivel_id', $row->nivel_id)?->matricula ?: $alumno?->matricula;
                            $nombre = trim(($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '') . ' ' . ($alumno?->nombre ?? ''));
                        @endphp
                        <tr wire:key="baja-registrada-{{ $row->id }}" class="align-top hover:bg-slate-50 dark:hover:bg-neutral-800/50">
                            <td class="px-4 py-4 font-black text-slate-900 dark:text-white">{{ $matricula ?: '—' }}</td>
                            <td class="px-4 py-4"><p class="font-bold text-slate-800 dark:text-slate-100">{{ $nombre }}</p><p class="mt-1 text-xs text-slate-500">{{ $alumno?->curp ?: '—' }}</p></td>
                            <td class="px-4 py-4">{{ $row->grado?->nombre ?? '—' }} · {{ $this->textoGrupo($row->grupo) }} @if($row->semestre)<br><span class="text-xs text-slate-500">Semestre {{ $row->semestre->numero }}</span>@endif</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-black text-red-700 dark:bg-red-900/30 dark:text-red-200">{{ $this->etiquetaEstatus($row->estatus) }}</span></td>
                            <td class="px-4 py-4">{{ optional($row->fecha_baja)->format('d/m/Y') ?: '—' }}<br><span class="text-xs text-slate-500">Estancia {{ $row->numero_estancia }}</span></td>
                            <td class="max-w-sm px-4 py-4"><p class="font-semibold text-slate-700 dark:text-slate-200">{{ $row->motivo_baja ?: '—' }}</p>@if($row->observaciones_baja)<p class="mt-1 text-xs text-slate-500">{{ $row->observaciones_baja }}</p>@endif</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="generarConstanciaBaja({{ $row->id }})" title="Generar constancia"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100 dark:bg-sky-950/30 dark:text-sky-300">
                                        <flux:icon.document-text class="h-4 w-4" />
                                    </button>
                                    <button type="button" x-on:click="confirmarReingreso({{ $row->id }}, @js($nombre))"
                                        class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-3 py-2 text-xs font-black text-white hover:bg-violet-700">
                                        <flux:icon.arrow-path class="h-4 w-4" /> Reingresar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-14 text-center text-slate-500">No hay bajas ni traslados en este ciclo y corte.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bajasRows->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-neutral-700">{{ $bajasRows->links(data: ['scrollTo' => false]) }}</div>
        @endif
    </section>
</div>
