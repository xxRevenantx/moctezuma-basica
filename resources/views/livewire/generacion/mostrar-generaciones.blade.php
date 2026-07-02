<div class="space-y-5">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div><h1 class="text-2xl font-bold">Generaciones</h1><p class="text-sm text-slate-500">Activa, desactiva y consulta el padrón permanente de cada generación.</p></div>
        <div class="flex gap-3"><flux:input wire:model.live.debounce.350ms="search" placeholder="Buscar generación o nivel" icon="magnifying-glass" /><flux:checkbox wire:model.live="incluir_inactivas" label="Mostrar inactivas" /></div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto"><table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800"><tr><th class="px-4 py-3">Generación</th><th class="px-4 py-3">Nivel</th><th class="px-4 py-3">Periodo</th><th class="px-4 py-3">Alumnos</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Acciones</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($generaciones as $g)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $g->etiqueta }}</td>
                    <td class="px-4 py-3">{{ $g->nivel?->nombre }}</td>
                    <td class="px-4 py-3 text-xs">{{ optional($g->fecha_inicio)->format('d/m/Y') ?: '—' }} — {{ optional($g->fecha_termino)->format('d/m/Y') ?: '—' }}</td>
                    <td class="px-4 py-3"><div class="flex flex-wrap gap-1"><flux:badge color="blue">Total {{ $g->alumnos_total_count }}</flux:badge><flux:badge color="green">Activos {{ $g->alumnos_activos_count }}</flux:badge><flux:badge color="amber">Bajas {{ $g->alumnos_bajas_count }}</flux:badge><flux:badge color="purple">Egresados {{ $g->alumnos_egresados_count }}</flux:badge></div></td>
                    <td class="px-4 py-3">@if($g->status)<flux:badge color="green">Activa</flux:badge>@else<flux:badge color="zinc">Inactiva</flux:badge>@endif</td>
                    <td class="px-4 py-3"><div class="flex justify-end gap-2">
                        <flux:button size="sm" @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $g->id }} })" icon="pencil-square">Editar</flux:button>
                        @if($g->status)<flux:button size="sm" variant="danger" wire:click="prepararDesactivacion({{ $g->id }})">Desactivar</flux:button>@else<flux:button size="sm" variant="primary" wire:click="reactivar({{ $g->id }})">Reactivar</flux:button>@endif
                    </div></td>
                </tr>
            @empty<tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No hay generaciones.</td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="border-t p-4 dark:border-slate-800">{{ $generaciones->links() }}</div>
    </div>

    @if($modalDesactivar)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
        <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
            <h2 class="text-xl font-bold">Desactivar generación</h2>
            <p class="mt-1 text-sm text-slate-500">La generación seguirá disponible en filtros históricos y sus alumnos conservarán su vínculo.</p>
            <div class="mt-5 space-y-4"><flux:textarea wire:model="motivo" label="Motivo obligatorio" rows="3" /><flux:checkbox wire:model="egresar_activos" label="Marcar como egresados a los alumnos activos después de confirmar" /></div>
            <div class="mt-6 flex justify-end gap-2"><flux:button wire:click="$set('modalDesactivar', false)">Cancelar</flux:button><flux:button variant="danger" wire:click="desactivar">Confirmar desactivación</flux:button></div>
        </div>
    </div>
    @endif
    <livewire:generacion.editar-generacion />
</div>
