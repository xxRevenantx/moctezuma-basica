<div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-violet-600 via-sky-500 to-emerald-500"></div>
        <div class="flex flex-col gap-4 p-6 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.2em] text-violet-700">Control de cambios históricos</p>
                <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">Correcciones de calificaciones</h1>
                <p class="mt-2 text-sm text-slate-500">Los ciclos cerrados no se modifican directamente: primero se solicita, luego se autoriza y finalmente se aplica.</p>
            </div>
            <div class="flex flex-wrap items-end gap-3">
                <flux:select wire:model.live="estado" label="Estado">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="solicitada">Solicitadas</flux:select.option>
                    <flux:select.option value="autorizada">Autorizadas</flux:select.option>
                    <flux:select.option value="aplicada">Aplicadas</flux:select.option>
                    <flux:select.option value="rechazada">Rechazadas</flux:select.option>
                </flux:select>
                <a href="{{ route('misrutas.centro-control') }}" class="inline-flex rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">Centro de control</a>
            </div>
        </div>
    </section>

    <section class="space-y-4">
        @forelse ($correcciones as $correccion)
            @php
                $alumno = $correccion->inscripcion;
                $propuesto = $correccion->valor_propuesto ?? [];
                $anterior = $correccion->valor_anterior ?? [];
            @endphp
            <article wire:key="correccion-calificacion-{{ $correccion->id }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="grid gap-5 xl:grid-cols-[1fr_1fr_.8fr]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-lg bg-slate-900 px-2.5 py-1 text-xs font-black text-white">#{{ $correccion->id }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $correccion->estado === 'solicitada' ? 'bg-amber-100 text-amber-700' : ($correccion->estado === 'autorizada' ? 'bg-sky-100 text-sky-700' : ($correccion->estado === 'aplicada' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700')) }}">{{ ucfirst($correccion->estado) }}</span>
                        </div>
                        <h2 class="mt-3 text-lg font-black text-slate-900 dark:text-white">{{ trim(($alumno?->nombre ?? '') . ' ' . ($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '')) ?: 'Alumno no disponible' }}</h2>
                        <p class="text-sm text-slate-500">{{ $alumno?->matricula ?: 'Sin matrícula' }} · {{ $correccion->periodo?->nivel?->nombre }} · {{ $correccion->periodo?->cicloEscolar?->nombre }}</p>
                        <p class="mt-3 rounded-2xl bg-slate-50 p-3 text-sm text-slate-600 dark:bg-neutral-800 dark:text-slate-300"><strong>Motivo:</strong> {{ $correccion->motivo }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/40 dark:bg-rose-950/20">
                            <p class="text-xs font-black uppercase text-rose-700">Valor anterior</p>
                            <p class="mt-2 text-2xl font-black text-rose-900 dark:text-rose-100">{{ data_get($anterior, 'calificacion', '—') }}</p>
                            <p class="mt-1 text-xs text-rose-700/80">{{ data_get($anterior, 'observacion', 'Sin observación') ?: 'Sin observación' }}</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                            <p class="text-xs font-black uppercase text-emerald-700">Valor propuesto</p>
                            <p class="mt-2 text-2xl font-black text-emerald-900 dark:text-emerald-100">{{ data_get($propuesto, 'accion') === 'eliminar' ? 'Eliminar' : data_get($propuesto, 'calificacion', '—') }}</p>
                            <p class="mt-1 text-xs text-emerald-700/80">{{ data_get($propuesto, 'observacion', 'Sin observación') ?: 'Sin observación' }}</p>
                        </div>
                    </div>

                    <div>
                        <flux:textarea wire:model="observaciones.{{ $correccion->id }}" label="Observación de autorización" placeholder="Justifica la autorización o el rechazo..." />
                        <flux:error name="observaciones.{{ $correccion->id }}" />
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($correccion->estado === 'solicitada')
                                <flux:button size="sm" variant="primary" wire:click="autorizar({{ $correccion->id }})" spinner="autorizar({{ $correccion->id }})">Autorizar</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="rechazar({{ $correccion->id }})" spinner="rechazar({{ $correccion->id }})">Rechazar</flux:button>
                            @elseif ($correccion->estado === 'autorizada')
                                <flux:button size="sm" variant="primary" wire:click="aplicar({{ $correccion->id }})" spinner="aplicar({{ $correccion->id }})">Aplicar corrección</flux:button>
                            @else
                                <span class="text-xs font-semibold text-slate-500">Proceso finalizado.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-10 text-center text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200">No existen correcciones con el estado seleccionado.</div>
        @endforelse
    </section>

    @if ($correcciones->hasPages())
        <div>{{ $correcciones->links(data: ['scrollTo' => false]) }}</div>
    @endif
</div>
