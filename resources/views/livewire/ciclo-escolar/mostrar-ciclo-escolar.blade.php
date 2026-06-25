<div x-data="{
    eliminar(id, nombre) {
        Swal.fire({
            title: 'Eliminar ciclo ' + nombre,
            text: 'Solo podrá eliminarse si no contiene trayectorias, periodos ni calificaciones.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626'
        }).then((r) => r.isConfirmed && this.$wire.eliminar(id));
    },
    actual(id, nombre) {
        Swal.fire({
            title: 'Marcar ' + nombre + ' como ciclo actual',
            text: 'El ciclo actual anterior quedará cerrado, pero su historial no se modificará.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, marcar actual',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#006492'
        }).then((r) => r.isConfirmed && this.$wire.marcarActual(id));
    }
}" class="space-y-4">
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Ciclos escolares registrados</h2>
                <p class="text-sm text-slate-500">El historial bloquea la eliminación física; usa “Cerrar” para conservarlo.</p>
            </div>
            <div class="relative w-full sm:max-w-xs">
                <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input wire:model.live.debounce.350ms="search" type="search" placeholder="Buscar 2025-2026"
                    class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 text-sm dark:border-neutral-700 dark:bg-neutral-800" />
            </div>
        </div>
        @error('eliminar')
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-200">{{ $message }}</div>
        @enderror
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="overflow-x-auto">
            <table class="min-w-[950px] w-full text-left text-sm">
                <thead class="bg-slate-900 text-xs uppercase text-white">
                    <tr>
                        <th class="px-4 py-3">Ciclo escolar</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Trayectorias</th>
                        <th class="px-4 py-3">Creado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($ciclos as $ciclo)
                        <tr wire:key="ciclo-{{ $ciclo->id }}" class="hover:bg-sky-50/40 dark:hover:bg-sky-950/10">
                            <td class="px-4 py-4">
                                <p class="text-lg font-black text-slate-900 dark:text-white">{{ $ciclo->nombre }}</p>
                                @if ($ciclo->cerrado_at)
                                    <p class="mt-1 text-xs text-slate-500">Cerrado el {{ $ciclo->cerrado_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if ($ciclo->es_actual)
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">Ciclo actual</span>
                                @elseif ($ciclo->cerrado_at)
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">Cerrado</span>
                                @else
                                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-700 dark:bg-sky-900/30 dark:text-sky-200">Histórico abierto</span>
                                @endif
                            </td>
                            <td class="px-4 py-4"><b class="text-slate-900 dark:text-white">{{ number_format($ciclo->trayectorias_count) }}</b><br><span class="text-xs text-slate-500">registros históricos</span></td>
                            <td class="px-4 py-4 text-slate-500">{{ optional($ciclo->created_at)->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    @unless ($ciclo->es_actual)
                                        <button type="button" x-on:click="actual({{ $ciclo->id }}, @js($ciclo->nombre))"
                                            class="inline-flex items-center gap-2 rounded-xl bg-sky-50 px-3 py-2 text-xs font-black text-sky-700 hover:bg-sky-100 dark:bg-sky-950/30 dark:text-sky-300">
                                            <flux:icon.check-circle class="h-4 w-4" /> Hacer actual
                                        </button>
                                    @endunless
                                    <button type="button" wire:click="alternarCierre({{ $ciclo->id }})"
                                        class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-black {{ $ciclo->cerrado_at ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/30 dark:text-amber-300' }}">
                                        <flux:icon.lock-closed class="h-4 w-4" /> {{ $ciclo->cerrado_at ? 'Reabrir' : 'Cerrar' }}
                                    </button>
                                    <button type="button" x-on:click="eliminar({{ $ciclo->id }}, @js($ciclo->nombre))"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-950/30 dark:text-red-300" title="Eliminar">
                                        <flux:icon.trash-2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-14 text-center text-slate-500">No hay ciclos escolares registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($ciclos->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-neutral-700">{{ $ciclos->links(data: ['scrollTo' => false]) }}</div>
        @endif
    </section>

    <livewire:ciclo-escolar.editar-ciclo-escolar />
</div>
