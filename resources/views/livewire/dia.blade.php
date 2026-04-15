<div x-data="{
    openRow: null,
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `Este día (${nombre}) se eliminará de forma permanente`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminarDia', id))
    }
}">
    <section x-data="{ abiertoDias: @js((bool) $dia_id) }"
        class="overflow-hidden rounded-[28px] border border-white/60 bg-white/85 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
        <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500"></div>

        <button type="button" @click="abiertoDias = !abiertoDias"
            class="flex w-full items-start justify-between gap-4 p-5 text-left sm:p-6">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                    <flux:icon.calendar-days class="h-5 w-5" />
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                        Gestión de días
                    </h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Define los días disponibles por nivel.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if ($dia_id)
                    <span
                        class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                        Editando
                    </span>
                @endif

                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 transition-transform duration-300 dark:bg-neutral-800 dark:text-slate-300"
                    :class="{ 'rotate-180': abiertoDias }">
                    <flux:icon.chevron-down class="h-5 w-5" />
                </div>
            </div>
        </button>

        <div x-show="abiertoDias" x-collapse x-cloak
            class="space-y-5 border-t border-slate-200/70 p-5 sm:p-6 dark:border-neutral-800">

            @if (session()->has('success_dia'))
                <div
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-950/30 dark:text-emerald-300">
                    {{ session('success_dia') }}
                </div>
            @endif

            <form wire:submit="guardarDia" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-1">
                    <flux:field>
                        <flux:label>Día</flux:label>
                        <flux:input wire:model="dia" type="text" placeholder="Ejemplo: Lunes" />
                        <flux:error name="dia" />
                    </flux:field>


                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    @if ($dia_id)
                        <flux:button type="button" variant="ghost" wire:click="cancelarDia"
                            @click="abiertoDias = false">
                            Cancelar
                        </flux:button>
                    @endif

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="guardarDia">
                        <span wire:loading.remove wire:target="guardarDia">
                            {{ $dia_id ? 'Actualizar día' : 'Guardar día' }}
                        </span>
                        <span wire:loading wire:target="guardarDia">
                            Guardando...
                        </span>
                    </flux:button>
                </div>
            </form>

            <div class="overflow-hidden rounded-3xl border border-slate-200/80 dark:border-neutral-800">
                <div class="max-h-[360px] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                        <thead class="bg-slate-50 dark:bg-neutral-900/70">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Orden
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Día
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Acciones
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-950/40">
                            @forelse ($dias as $item)
                                <tr class="transition hover:bg-emerald-50/70 dark:hover:bg-neutral-800/60">
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $item->orden }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $item->dia }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <flux:button size="sm" variant="ghost"
                                                wire:click="editarDia({{ $item->id }})" @click="abiertoDias = true">
                                                Editar
                                            </flux:button>

                                            <flux:button size="sm" variant="danger"
                                                @click="eliminar({{ $item->id }}, '{{ $item->dia }}')">
                                                Eliminar
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3"
                                        class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                        No hay días registrados todavía.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
