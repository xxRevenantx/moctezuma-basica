<div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>
        <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.2em] text-sky-700">Revisión controlada</p>
                <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">Ciclos académicos pendientes</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Esta pantalla no corrige automáticamente datos históricos. Permite revisar cargas académicas y grupos archivados antes de decidir si se justifican, corrigen o reactivan.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:checkbox wire:model.live="mostrarResueltas" label="Mostrar revisadas" />
                <a href="{{ route('misrutas.centro-control') }}" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">Volver al centro de control</a>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-amber-200 bg-white shadow-sm dark:border-amber-900/50 dark:bg-neutral-900">
        <div class="border-b border-amber-100 bg-amber-50/70 p-5 dark:border-amber-900/40 dark:bg-amber-950/20">
            <h2 class="text-lg font-black text-amber-950 dark:text-amber-100">Asignaciones de materias con ciclos distintos</h2>
            <p class="mt-1 text-sm text-amber-800/80 dark:text-amber-300">Las asignaciones 283–288 aparecerán aquí si continúan pendientes. Ninguna se cambia sin una decisión explícita.</p>
        </div>

        <div class="space-y-4 p-5">
            @forelse ($asignaciones as $asignacion)
                <article wire:key="revision-asignacion-{{ $asignacion->id }}" class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                    <div class="grid gap-4 xl:grid-cols-[1.4fr_.8fr_.8fr]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-lg bg-slate-900 px-2.5 py-1 text-xs font-black text-white">#{{ $asignacion->id }}</span>
                                <h3 class="font-black text-slate-900 dark:text-white">{{ $asignacion->materia?->nombre ?: 'Materia no disponible' }}</h3>
                                @if ($asignacion->revision_ciclo_estado)
                                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-black text-sky-700">{{ ucfirst($asignacion->revision_ciclo_estado) }}</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                {{ $asignacion->grupo?->nivel?->nombre }} · {{ $asignacion->grupo?->grado?->nombre }} ·
                                Grupo {{ $asignacion->grupo?->asignacionGrupo?->nombre ?? $asignacion->grupo_id }} ·
                                Generación {{ $asignacion->grupo?->generacion?->etiqueta ?? '—' }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold">
                                <span class="rounded-full bg-rose-100 px-3 py-1 text-rose-700">Carga: {{ $asignacion->cicloEscolar?->nombre ?? 'sin ciclo' }}</span>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Grupo: {{ $asignacion->grupo?->cicloEscolar?->nombre ?? 'sin ciclo' }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">{{ $asignacion->calificaciones_count }} calif. · {{ $asignacion->horarios_count }} horarios · {{ $asignacion->bitacora_calificaciones_count }} bitácoras</span>
                            </div>
                        </div>

                        <div class="xl:col-span-2">
                            <flux:textarea wire:model="motivoAsignacion.{{ $asignacion->id }}" label="Motivo de la decisión" placeholder="Explica por qué debe conservarse o corregirse el ciclo..." />
                            <flux:error name="motivoAsignacion.{{ $asignacion->id }}" />
                            <div class="mt-3 flex flex-wrap gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="justificarAsignacion({{ $asignacion->id }})" spinner="justificarAsignacion({{ $asignacion->id }})">
                                    Mantener y justificar
                                </flux:button>
                                <flux:button size="sm" variant="primary" wire:click="aplicarCicloDelGrupo({{ $asignacion->id }})" spinner="aplicarCicloDelGrupo({{ $asignacion->id }})">
                                    Usar ciclo del grupo
                                </flux:button>
                            </div>
                            @if ($asignacion->calificaciones_count || $asignacion->horarios_count || $asignacion->bitacora_calificaciones_count)
                                <p class="mt-2 text-xs font-semibold text-amber-700">Por seguridad, no se cambiará el ciclo mientras exista historial; en ese caso solo podrás justificarla o corregir primero los módulos vinculados.</p>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200">
                    No hay asignaciones pendientes con ciclos distintos.
                </div>
            @endforelse
        </div>
        @if ($asignaciones->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-neutral-800">{{ $asignaciones->links(data: ['scrollTo' => false]) }}</div>
        @endif
    </section>

    <section class="overflow-hidden rounded-3xl border border-sky-200 bg-white shadow-sm dark:border-sky-900/50 dark:bg-neutral-900">
        <div class="border-b border-sky-100 bg-sky-50/70 p-5 dark:border-sky-900/40 dark:bg-sky-950/20">
            <h2 class="text-lg font-black text-sky-950 dark:text-sky-100">Grupos archivados o sin ciclo</h2>
            <p class="mt-1 text-sm text-sky-800/80 dark:text-sky-300">El grupo 28 permanece archivado y editable. Puedes asignarle el ciclo correcto y reactivarlo cuando confirmes que debe utilizarse.</p>
        </div>
        <div class="grid gap-4 p-5 lg:grid-cols-2">
            @forelse ($gruposArchivados as $grupo)
                <article wire:key="revision-grupo-{{ $grupo->id }}" class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">Grupo #{{ $grupo->id }} · {{ $grupo->nivel?->nombre }} {{ $grupo->grado?->nombre }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $grupo->asignacionGrupo?->nombre ?? 'Sin sección' }} · Generación {{ $grupo->generacion?->etiqueta ?? '—' }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $grupo->estado === 'archivado' ? 'bg-slate-200 text-slate-700' : 'bg-amber-100 text-amber-700' }}">{{ $grupo->estado ?: 'sin estado' }}</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Relaciones: {{ $grupo->inscripciones_count }} alumnos · {{ $grupo->asignacion_materias_count }} cargas · {{ $grupo->horarios_count }} horarios · {{ $grupo->calificaciones_count }} calificaciones · {{ $grupo->persona_nivel_detalles_count }} asignaciones de personal.</p>
                    <div class="mt-4 space-y-3">
                        <flux:select wire:model="cicloGrupo.{{ $grupo->id }}" label="Ciclo escolar correcto">
                            <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                            @foreach ($ciclos as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}{{ $ciclo->es_actual ? ' · actual' : '' }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="cicloGrupo.{{ $grupo->id }}" />
                        <flux:textarea wire:model="motivoGrupo.{{ $grupo->id }}" label="Motivo" placeholder="Indica por qué se asigna este ciclo o se reactiva..." />
                        <flux:error name="motivoGrupo.{{ $grupo->id }}" />
                        <div class="flex flex-wrap gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="actualizarGrupoArchivado({{ $grupo->id }}, false)">Guardar archivado</flux:button>
                            <flux:button size="sm" variant="primary" wire:click="actualizarGrupoArchivado({{ $grupo->id }}, true)">Corregir y reactivar</flux:button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center text-emerald-800">No hay grupos archivados o sin ciclo pendientes de revisión.</div>
            @endforelse
        </div>
    </section>
</div>
