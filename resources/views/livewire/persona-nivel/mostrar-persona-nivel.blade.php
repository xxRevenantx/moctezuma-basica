<div x-data="{
        abiertos: {},
        archivoModal: false,
        init() {
            @foreach ($niveles as $nivel)
                this.abiertos[{{ $nivel->id }}] = true;
            @endforeach
        },
        abrirTodos() { Object.keys(this.abiertos).forEach(k => this.abiertos[k] = true) },
        cerrarTodos() { Object.keys(this.abiertos).forEach(k => this.abiertos[k] = false) },
    }"
    x-on:abrir-modal-archivar-persona-nivel.window="archivoModal = true"
    x-on:cerrar-modal-archivar-persona-nivel.window="archivoModal = false"
    class="space-y-5">

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-cyan-500 via-blue-600 to-violet-600"></div>
        <div class="flex flex-col gap-4 p-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-start gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-900">
                    <flux:icon.user-group class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">Personal asignado por nivel</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Plantilla del ciclo <b class="text-slate-700 dark:text-slate-200">{{ $ciclo?->nombre ?? 'sin ciclo' }}</b>. El orden es independiente por ciclo y nivel.
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <flux:input wire:model.live.debounce.350ms="search" icon="magnifying-glass"
                    placeholder="Buscar nombre, función, grado o grupo..." class="min-w-72" />
                <flux:button type="button" variant="outline" wire:click="$refresh">Actualizar</flux:button>
                <flux:button type="button" variant="outline" @click="abrirTodos()">Abrir todo</flux:button>
                <flux:button type="button" variant="outline" @click="cerrarTodos()">Cerrar todo</flux:button>
            </div>
        </div>
    </section>

    @forelse ($niveles as $nivel)
        @php
            $plantilla = $plantillas->get($nivel->id);
            $diagnostico = $plantilla?->diagnostico ?? [];
            $esSecundaria = $secundaria && (int) $nivel->id === (int) $secundaria->id;
            $items = $porNivel->get($nivel->nombre, collect());
            $personasNivel = $items->pluck('cabecera.persona_id')->filter()->unique()->count();
            $pendientes = $items->where('confirmado', false)->count();
            $editable = $plantilla?->esEditable() ?? false;
            $estadoClass = match($plantilla?->estado) {
                'publicada' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900',
                'en_revision' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900',
                'cerrada' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700',
                default => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900',
            };
        @endphp

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <button type="button" @click="abiertos[{{ $nivel->id }}] = !abiertos[{{ $nivel->id }}]"
                class="flex w-full items-center justify-between gap-4 p-5 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/50">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-blue-50 to-violet-50 text-blue-700 ring-1 ring-blue-100 dark:from-blue-950/30 dark:to-violet-950/30 dark:text-blue-300 dark:ring-blue-900">
                        <flux:icon.academic-cap class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-black text-slate-950 dark:text-white">{{ $nivel->nombre }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $estadoClass }}">
                                {{ $plantilla?->etiqueta_estado ?? 'Sin preparar' }}
                            </span>
                            @if ($pendientes)
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-black text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900">
                                    {{ $pendientes }} pendiente(s)
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $personasNivel }} persona(s) · {{ $items->count() }} asignación(es) ·
                            {{ $diagnostico['criticos'] ?? 0 }} error(es) crítico(s) · {{ $diagnostico['advertencias'] ?? 0 }} advertencia(s)
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">{{ $items->count() }}</span>
                    <flux:icon.chevron-down class="h-4 w-4 text-slate-400 transition" ::class="abiertos[{{ $nivel->id }}] ? 'rotate-180' : ''" />
                </div>
            </button>

            <div x-show="abiertos[{{ $nivel->id }}]" x-collapse class="border-t border-slate-200 dark:border-neutral-800">
                <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-3 dark:border-neutral-800 dark:bg-neutral-950/30 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full bg-white px-3 py-1 font-bold text-slate-600 ring-1 ring-slate-200 dark:bg-neutral-900 dark:text-slate-300 dark:ring-neutral-700">
                            Grupos del ciclo: {{ $diagnostico['grupos_activos'] ?? 0 }}
                        </span>
                        @if (!$editable)
                            <span class="rounded-full bg-rose-50 px-3 py-1 font-bold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900">
                                Solo consulta
                            </span>
                        @endif
                    </div>
                    <flux:button type="button" size="sm" icon="arrow-down-tray" wire:click="exportarPlantilla({{ $nivel->id }})">
                        Exportar Excel
                    </flux:button>
                </div>

                @if (($diagnostico['criticos'] ?? 0) > 0 || ($diagnostico['advertencias'] ?? 0) > 0)
                    <div class="grid gap-3 border-b border-slate-100 p-4 dark:border-neutral-800 lg:grid-cols-2">
                        @if (($diagnostico['criticos'] ?? 0) > 0)
                            <details class="rounded-2xl border border-rose-200 bg-rose-50 p-3 dark:border-rose-900 dark:bg-rose-950/20">
                                <summary class="cursor-pointer text-xs font-black text-rose-700 dark:text-rose-300">Errores críticos ({{ $diagnostico['criticos'] }})</summary>
                                <ul class="mt-2 space-y-1 text-xs text-rose-700 dark:text-rose-200">
                                    @foreach (array_slice($diagnostico['errores'] ?? [], 0, 12) as $error)
                                        <li>• {{ $error }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                        @if (($diagnostico['advertencias'] ?? 0) > 0)
                            <details class="rounded-2xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/20">
                                <summary class="cursor-pointer text-xs font-black text-amber-700 dark:text-amber-300">Advertencias ({{ $diagnostico['advertencias'] }})</summary>
                                <ul class="mt-2 space-y-1 text-xs text-amber-700 dark:text-amber-200">
                                    @foreach (array_slice($diagnostico['avisos'] ?? [], 0, 12) as $aviso)
                                        <li>• {{ $aviso }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                @endif

                @if ($esSecundaria)
                    <div data-sortable="personas" data-nivel-id="{{ $nivel->id }}" class="grid gap-4 p-4 xl:grid-cols-2">
                        @forelse ($profesoresSec as $profesor)
                            <article data-id="{{ $profesor['membresia_id'] }}" class="persona-card overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                                <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-4 dark:border-neutral-800">
                                    <div class="flex items-start gap-3">
                                        <button type="button" data-handle-card @disabled(!$editable)
                                            class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-400 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:hover:bg-neutral-800">
                                            <flux:icon.bars-3 class="h-4 w-4" />
                                        </button>
                                        <div>
                                            <h4 class="font-black text-slate-950 dark:text-white">{{ $profesor['nombre'] }}</h4>
                                            <p class="mt-1 text-xs text-slate-500">{{ $profesor['especialidad'] ?: 'Sin especialidad registrada' }}</p>
                                        </div>
                                    </div>
                                    @if ($profesor['pendientes'])
                                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-black text-rose-700 ring-1 ring-rose-200">{{ $profesor['pendientes'] }} pendiente(s)</span>
                                    @endif
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50 text-[10px] uppercase text-slate-500 dark:bg-neutral-950/40">
                                            <tr><th class="px-3 py-2 text-left">Orden</th><th class="px-3 py-2 text-left">Función</th><th class="px-3 py-2 text-left">Grupo</th><th class="px-3 py-2 text-right">Acciones</th></tr>
                                        </thead>
                                        <tbody data-sortable="sec" data-nivel-id="{{ $nivel->id }}" data-cabecera-id="{{ $profesor['membresia_id'] }}" class="divide-y divide-slate-100 dark:divide-neutral-800">
                                            @foreach ($profesor['detalles'] as $detalle)
                                                <tr data-id="{{ $detalle->id }}" class="{{ !$detalle->confirmado ? 'bg-rose-50/60 dark:bg-rose-950/10' : '' }}">
                                                    <td class="px-3 py-3"><button data-handle type="button" @disabled(!$editable) class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 disabled:opacity-40"><flux:icon.bars-3 class="h-4 w-4" />{{ $detalle->orden }}</button></td>
                                                    <td class="px-3 py-3"><b class="text-slate-800 dark:text-slate-200">{{ $detalle->personaRole?->rolePersona?->nombre }}</b>@if(!$detalle->confirmado)<span class="mt-1 block text-[10px] font-black uppercase text-rose-600">Pendiente de confirmar</span>@endif</td>
                                                    <td class="px-3 py-3 text-xs text-slate-600 dark:text-slate-300">{{ $detalle->grado?->nombre ?: 'General' }} · {{ $detalle->grupo?->asignacionGrupo?->nombre ?: 'Sin grupo' }}</td>
                                                    <td class="px-3 py-3"><div class="flex justify-end gap-1"><button type="button" wire:click="$dispatch('editarPersonaNivel', { id: {{ $detalle->id }} })" @disabled(!$editable) class="rounded-xl bg-amber-500 p-2 text-white disabled:opacity-40"><flux:icon.pencil-square class="h-4 w-4" /></button><button type="button" wire:click="solicitarArchivar({{ $detalle->id }})" @disabled(!$editable) class="rounded-xl bg-rose-600 p-2 text-white disabled:opacity-40"><flux:icon.archive-box class="h-4 w-4" /></button></div></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                        @empty
                            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500 dark:border-neutral-700">No hay personal de Secundaria en este ciclo.</div>
                        @endforelse
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-500 dark:bg-neutral-950/40">
                                <tr>
                                    <th class="px-4 py-3 text-left">Orden</th>
                                    <th class="px-4 py-3 text-left">Personal</th>
                                    <th class="px-4 py-3 text-left">Función</th>
                                    <th class="px-4 py-3 text-left">Grado / grupo</th>
                                    <th class="px-4 py-3 text-left">Generación</th>
                                    <th class="px-4 py-3 text-left">SEG · SEP · C.T.</th>
                                    <th class="px-4 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody data-sortable="nivel" data-nivel-id="{{ $nivel->id }}" class="divide-y divide-slate-100 dark:divide-neutral-800">
                                @forelse ($items as $detalle)
                                    @php($persona = $detalle->cabecera?->persona)
                                    <tr data-id="{{ $detalle->id }}" class="transition hover:bg-slate-50/70 dark:hover:bg-neutral-800/40 {{ !$detalle->confirmado ? 'bg-rose-50/60 dark:bg-rose-950/10' : '' }}">
                                        <td class="px-4 py-3">
                                            <button type="button" data-handle @disabled(!$editable) class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-2 py-1.5 text-xs font-black text-slate-500 disabled:opacity-40 dark:border-neutral-700">
                                                <flux:icon.bars-3 class="h-4 w-4" /> {{ $detalle->orden }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-3">
                                            <b class="block text-slate-950 dark:text-white">{{ trim(collect([$persona?->titulo, $persona?->nombre, $persona?->apellido_paterno, $persona?->apellido_materno])->filter()->implode(' ')) }}</b>
                                            <span class="text-[11px] text-slate-500">{{ $persona?->especialidad ?: 'Sin especialidad' }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-bold text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">{{ $detalle->personaRole?->rolePersona?->nombre }}</span>
                                            @if (!$detalle->confirmado)
                                                <span class="mt-1 block text-[10px] font-black uppercase text-rose-600">Pendiente de confirmar</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-200">
                                            <b>{{ $detalle->grado?->nombre ?: 'General' }}</b>
                                            <span class="block text-xs text-slate-500">{{ $detalle->grupo?->asignacionGrupo?->nombre ?: 'Sin grupo específico' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">{{ $detalle->grupo?->generacion?->etiqueta ?: 'Automática al elegir grupo' }}</td>
                                        <td class="px-4 py-3 text-[11px] text-slate-500">
                                            <div>SEG: {{ optional($detalle->cabecera?->ingreso_seg)->format('d/m/Y') ?: '—' }}</div>
                                            <div>SEP: {{ optional($detalle->cabecera?->ingreso_sep)->format('d/m/Y') ?: '—' }}</div>
                                            <div>C.T.: {{ optional($detalle->cabecera?->ingreso_ct)->format('d/m/Y') ?: '—' }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" wire:click="$dispatch('editarPersonaNivel', { id: {{ $detalle->id }} })" @disabled(!$editable)
                                                    class="rounded-xl bg-amber-500 p-2.5 text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-40"><flux:icon.pencil-square class="h-4 w-4" /></button>
                                                <button type="button" wire:click="solicitarArchivar({{ $detalle->id }})" @disabled(!$editable)
                                                    class="rounded-xl bg-rose-600 p-2.5 text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-40"><flux:icon.archive-box class="h-4 w-4" /></button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">No hay asignaciones para {{ $nivel->nombre }} en este ciclo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @empty
        <div class="rounded-3xl border border-dashed border-slate-300 p-10 text-center text-slate-500 dark:border-neutral-700">No hay niveles configurados.</div>
    @endforelse

    <div x-show="archivoModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
        <div @click.outside="archivoModal = false" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
            <div class="flex items-start gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-2xl bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"><flux:icon.archive-box class="h-5 w-5" /></div>
                <div><h3 class="text-lg font-black text-slate-950 dark:text-white">Archivar asignación</h3><p class="mt-1 text-sm text-slate-500">No se eliminará el historial. El motivo quedará registrado.</p></div>
            </div>
            <div class="mt-5"><flux:textarea wire:model="motivoArchivo" label="Motivo obligatorio" rows="4" placeholder="Describe por qué concluye o se corrige esta asignación..." /><flux:error name="motivoArchivo" /></div>
            <div class="mt-5 flex justify-end gap-2"><flux:button type="button" variant="ghost" @click="archivoModal=false">Cancelar</flux:button><flux:button type="button" variant="danger" wire:click="confirmarArchivo">Archivar y conservar historial</flux:button></div>
        </div>
    </div>

    <livewire:persona-nivel.editar-persona-nivel />
    <livewire:persona-nivel.editar-persona-nivel-cabecera />

    @once
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <script>
            (() => {
                const componentFrom = el => {
                    const root = el.closest('[wire\\:id]');
                    return root ? Livewire.find(root.getAttribute('wire:id')) : null;
                };
                const init = () => {
                    if (typeof Sortable === 'undefined') return;
                    document.querySelectorAll('tbody[data-sortable="nivel"], tbody[data-sortable="sec"], [data-sortable="personas"]').forEach(el => {
                        if (el._sortable) return;
                        const tipo = el.dataset.sortable;
                        const nivelId = parseInt(el.dataset.nivelId || '0');
                        if (!nivelId) return;
                        el._sortable = new Sortable(el, {
                            animation: 160,
                            handle: tipo === 'personas' ? '[data-handle-card]' : '[data-handle]',
                            draggable: tipo === 'personas' ? '.persona-card' : 'tr[data-id]',
                            dataIdAttr: 'data-id',
                            forceFallback: true,
                            onEnd: () => {
                                const ids = el._sortable.toArray().map(Number).filter(Boolean);
                                const component = componentFrom(el);
                                if (!component || !ids.length) return;
                                if (tipo === 'personas') return component.call('ordenarPersonasJs', nivelId, ids);
                                if (tipo === 'sec') return component.call('ordenarSecJs', nivelId, parseInt(el.dataset.cabeceraId || '0'), ids);
                                component.call('ordenarJs', nivelId, ids);
                            }
                        });
                    });
                };
                document.addEventListener('DOMContentLoaded', init);
                document.addEventListener('livewire:init', () => {
                    init();
                    Livewire.hook('message.processed', init);
                });
                setInterval(init, 500);
            })();
        </script>
    @endonce
</div>
