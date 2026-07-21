<div class="space-y-4">
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-black">Ciclos escolares registrados</h2>
                <p class="text-sm text-slate-500">
                    Cada grupo y cada inscripción quedan ligados a un ciclo escolar. La preparación se controla por nivel.
                </p>
            </div>
            <flux:input wire:model.live.debounce.350ms="search" placeholder="Buscar 2026-2027" />
        </div>
        @error('eliminar')
            <p class="mt-3 text-sm font-bold text-rose-600">{{ $message }}</p>
        @enderror
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="p-3 text-left">Ciclo</th>
                        <th class="p-3 text-left">Estado</th>
                        <th class="p-3 text-left">Preparación por nivel</th>
                        <th class="p-3 text-left">Uso académico</th>
                        <th class="p-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-neutral-800">
                    @forelse ($ciclos as $ciclo)
                        @php
                            $listos = $ciclo->nivelesPreparacion->where('estado', 'listo')->count();
                            $totalNiveles = max(4, $ciclo->nivelesPreparacion->count());
                        @endphp
                        <tr>
                            <td class="p-4">
                                <b class="text-lg">{{ $ciclo->nombre }}</b>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ number_format($ciclo->grupos_count) }} grupos · {{ number_format($ciclo->inscripciones_count) }} alumnos ligados
                                </div>
                            </td>
                            <td class="p-4">
                                @if ($ciclo->es_actual)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-black text-emerald-700">Actual</span>
                                @elseif ($ciclo->cerrado_at)
                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-black text-slate-700">Cerrado</span>
                                @else
                                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-black text-sky-700">Histórico abierto</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-slate-800 dark:text-slate-100">{{ $listos }} de {{ $totalNiveles }} niveles listos</div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @forelse ($ciclo->nivelesPreparacion as $estadoNivel)
                                        <span class="rounded-full px-2 py-1 text-[11px] font-bold {{ $estadoNivel->estado === 'listo' ? 'bg-emerald-100 text-emerald-700' : ($estadoNivel->estado === 'en_preparacion' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                            {{ $estadoNivel->nivel?->nombre }}: {{ str_replace('_', ' ', $estadoNivel->estado) }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-500">Diagnóstico pendiente.</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="p-4 text-xs text-slate-500">
                                {{ $ciclo->periodos_count }} periodos ·
                                {{ $ciclo->asignacion_materias_count }} cargas ·
                                {{ $ciclo->horarios_count }} horarios ·
                                {{ $ciclo->calificaciones_count }} calificaciones
                            </td>
                            <td class="p-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <flux:button size="sm" wire:click="abrirPreparacion({{ $ciclo->id }})">
                                        Revisar preparación
                                    </flux:button>
                                    @unless ($ciclo->es_actual)
                                        <flux:button size="sm" wire:click="marcarActual({{ $ciclo->id }})">Hacer actual</flux:button>
                                    @endunless
                                    <flux:button size="sm" variant="ghost" wire:click="alternarCierre({{ $ciclo->id }})">
                                        {{ $ciclo->cerrado_at ? 'Reabrir' : 'Cerrar' }}
                                    </flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="eliminar({{ $ciclo->id }})">Eliminar</flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center text-slate-500">Sin ciclos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $ciclos->links(data: ['scrollTo' => false]) }}</div>
    </section>

    @if ($modalPreparacion)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" wire:key="preparacion-ciclo-modal">
            <div class="max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
                <div class="sticky top-0 z-10 flex items-start justify-between border-b border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Preparar ciclo {{ $cicloPrepararNombre }}</h3>
                        <p class="text-sm text-slate-500">
                            Vista previa por nivel. No mueve alumnos ni elimina información; crea generaciones y grupos faltantes con cupo ilimitado.
                        </p>
                    </div>
                    <button type="button" wire:click="cerrarPreparacion" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-neutral-800">
                        <flux:icon.x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($diagnosticoPreparacion as $diagnostico)
                            <article class="rounded-2xl border p-4 {{ $diagnostico['estado'] === 'listo' ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900/50 dark:bg-emerald-950/20' : 'border-amber-200 bg-amber-50/70 dark:border-amber-900/50 dark:bg-amber-950/20' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h4 class="font-black text-slate-900 dark:text-white">{{ $diagnostico['nivel'] }}</h4>
                                        <p class="text-sm text-slate-600 dark:text-slate-300">Generación de ingreso: {{ $diagnostico['generacion_esperada'] }}</p>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $diagnostico['estado'] === 'listo' ? 'bg-emerald-200 text-emerald-800' : 'bg-amber-200 text-amber-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $diagnostico['estado'])) }}
                                    </span>
                                </div>

                                <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                    <div class="rounded-xl bg-white/70 p-2 dark:bg-neutral-900/50"><dt class="text-xs text-slate-500">Grupos</dt><dd class="font-black">{{ $diagnostico['grupos'] }}</dd></div>
                                    <div class="rounded-xl bg-white/70 p-2 dark:bg-neutral-900/50"><dt class="text-xs text-slate-500">Nuevo ingreso</dt><dd class="font-black">{{ $diagnostico['grupos_nuevo_ingreso'] }}</dd></div>
                                    <div class="rounded-xl bg-white/70 p-2 dark:bg-neutral-900/50"><dt class="text-xs text-slate-500">Periodos</dt><dd class="font-black">{{ $diagnostico['periodos'] }}</dd></div>
                                    <div class="rounded-xl bg-white/70 p-2 dark:bg-neutral-900/50"><dt class="text-xs text-slate-500">Cargas / horarios</dt><dd class="font-black">{{ $diagnostico['asignaciones'] }} / {{ $diagnostico['horarios'] }}</dd></div>
                                </dl>

                                @if (!empty($diagnostico['faltantes']))
                                    <div class="mt-3 text-xs font-semibold text-amber-800 dark:text-amber-200">
                                        Pendiente: {{ implode(', ', $diagnostico['faltantes']) }}.
                                    </div>
                                @else
                                    <div class="mt-3 text-xs font-semibold text-emerald-800 dark:text-emerald-200">
                                        La estructura académica y la plantilla publicada están disponibles.
                                    </div>
                                @endif

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <flux:button
                                        size="sm"
                                        variant="primary"
                                        wire:click="prepararNivel({{ $diagnostico['nivel_id'] }})"
                                        spinner="prepararNivel({{ $diagnostico['nivel_id'] }})"
                                    >
                                        Preparar solo {{ $diagnostico['nivel'] }}
                                    </flux:button>
                                    <span class="self-center text-[11px] text-slate-500">
                                        No mueve alumnos ni modifica otros niveles.
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-200">
                        La preparación copia las secciones del ciclo anterior, avanza sus grupos al siguiente grado o semestre, crea la generación y grupos de nuevo ingreso y conserva estructuras especiales para alumnos no promovidos. No mueve alumnos automáticamente.
                    </div>
                </div>

                <div class="sticky bottom-0 flex flex-wrap justify-end gap-3 border-t border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <flux:button variant="ghost" wire:click="cerrarPreparacion">Cerrar</flux:button>
                    <flux:button variant="primary" wire:click="confirmarPreparacion" spinner="confirmarPreparacion">
                        Preparar todos los niveles faltantes
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <livewire:ciclo-escolar.editar-ciclo-escolar />
</div>
