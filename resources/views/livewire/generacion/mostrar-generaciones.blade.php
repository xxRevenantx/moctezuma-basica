<div class="space-y-5">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">
                Control escolar
            </p>
            <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">Generaciones</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Consulta, cierra o reabre una generación sin perder su historial académico.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:input wire:model.live.debounce.350ms="search" placeholder="Buscar generación o nivel"
                icon="magnifying-glass" />
            <flux:checkbox wire:model.live="incluir_inactivas" label="Mostrar inactivas" />
        </div>
    </div>

    <div
        class="rounded-2xl border border-sky-100 bg-gradient-to-r from-sky-50 to-emerald-50 p-4 dark:border-sky-900/40 dark:from-sky-950/20 dark:to-emerald-950/20">
        <div class="flex items-start gap-3">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#006492] text-white shadow-sm">
                <flux:icon.information-circle class="h-5 w-5" />
            </div>
            <div>
                <p class="font-black text-slate-900 dark:text-white">Reapertura para correcciones</p>
                <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Al reabrir una generación puedes reactivar temporalmente a sus egresados para corregir
                    calificaciones, boletas o documentos. Al finalizar, vuelve a desactivarla y marca nuevamente
                    a los alumnos activos como egresados.
                </p>
            </div>
        </div>
    </div>

    <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead
                    class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Generación</th>
                        <th class="px-4 py-3">Nivel</th>
                        <th class="px-4 py-3">Periodo</th>
                        <th class="px-4 py-3">Alumnos</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($generaciones as $g)
                        <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <p class="font-black text-slate-900 dark:text-white">{{ $g->etiqueta }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $g->nombre ?: 'Generación escolar' }}
                                </p>
                            </td>

                            <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">
                                {{ $g->nivel?->nombre ?: '—' }}
                            </td>

                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                {{ optional($g->fecha_inicio)->format('d/m/Y') ?: '—' }}
                                <span class="mx-1 text-slate-400">—</span>
                                {{ optional($g->fecha_termino)->format('d/m/Y') ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <flux:badge color="blue">Total {{ $g->alumnos_total_count }}</flux:badge>
                                    <flux:badge color="green">Activos {{ $g->alumnos_activos_count }}</flux:badge>
                                    <flux:badge color="amber">Bajas {{ $g->alumnos_bajas_count }}</flux:badge>
                                    <flux:badge color="purple">Egresados {{ $g->alumnos_egresados_count }}</flux:badge>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                @if ($g->status)
                                    <flux:badge color="green" icon="check-circle">Activa</flux:badge>
                                @else
                                    <flux:badge color="zinc" icon="lock-closed">Cerrada</flux:badge>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <flux:button size="sm" icon="pencil-square"
                                        @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $g->id }} })">
                                        Editar
                                    </flux:button>

                                    @if ($g->status)
                                        <flux:button size="sm" variant="danger" icon="lock-closed"
                                            wire:click="prepararDesactivacion({{ $g->id }})">
                                            Cerrar
                                        </flux:button>
                                    @else
                                        <flux:button size="sm" variant="primary" icon="lock-open"
                                            wire:click="prepararReactivacion({{ $g->id }})">
                                            Reabrir para correcciones
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                                No hay generaciones que coincidan con la búsqueda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-4 dark:border-slate-800">
            {{ $generaciones->links() }}
        </div>
    </div>

    @if ($modalDesactivar)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                <div
                    class="border-b border-slate-200 bg-gradient-to-r from-rose-50 to-amber-50 px-6 py-5 dark:border-slate-800 dark:from-rose-950/30 dark:to-amber-950/20">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-600 text-white shadow-lg">
                            <flux:icon.lock-closed class="h-6 w-6" />
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-slate-900 dark:text-white">Cerrar generación</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                La generación seguirá disponible en consultas y reportes históricos.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 p-6">
                    <flux:textarea wire:model="motivo" label="Motivo obligatorio"
                        placeholder="Ejemplo: conclusión oficial de la generación" rows="3" />

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-rose-300 hover:bg-rose-50/60 dark:border-slate-700 dark:hover:border-rose-900/70 dark:hover:bg-rose-950/20">
                        <input type="checkbox" wire:model="egresar_activos"
                            class="mt-1 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                        <span>
                            <b class="block text-slate-900 dark:text-white">Marcar como egresados a los alumnos
                                activos</b>
                            <small class="mt-1 block leading-5 text-slate-500 dark:text-slate-400">
                                Úsalo también después de terminar una reapertura por correcciones.
                            </small>
                        </span>
                    </label>
                </div>

                <div
                    class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/30">
                    <flux:button wire:click="$set('modalDesactivar', false)">Cancelar</flux:button>
                    <flux:button variant="danger" icon="lock-closed" wire:click="desactivar" spinner="desactivar">
                        Confirmar cierre
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    @if ($modalReactivar)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm">
            <div class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                <div
                    class="border-b border-slate-200 bg-gradient-to-r from-sky-50 to-emerald-50 px-6 py-5 dark:border-slate-800 dark:from-sky-950/30 dark:to-emerald-950/20">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] text-white shadow-lg">
                            <flux:icon.lock-open class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700 dark:text-sky-300">
                                Reapertura administrativa
                            </p>
                            <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">
                                {{ $generacionReactivar?->etiqueta ?: 'Generación seleccionada' }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                {{ $generacionReactivar?->nivel?->nombre ?: 'Nivel educativo' }}
                                · {{ $generacionReactivar?->egresados_count ?? 0 }} egresado(s)
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 p-6">
                    <div
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                        Esta acción no crea otra inscripción ni un reingreso. Conserva la misma matrícula,
                        generación, grupo, calificaciones e historial del alumno.
                    </div>

                    <flux:textarea wire:model="motivo_reactivacion" label="Motivo de la reapertura"
                        placeholder="Ejemplo: corrección de calificaciones del tercer periodo" rows="3" />

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50/60 p-4 transition hover:border-sky-300 dark:border-sky-900/50 dark:bg-sky-950/20">
                        <input type="checkbox" wire:model="reactivar_egresados"
                            class="mt-1 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                        <span>
                            <b class="block text-slate-900 dark:text-white">
                                Reactivar temporalmente a los alumnos egresados
                            </b>
                            <small class="mt-1 block leading-5 text-slate-600 dark:text-slate-400">
                                Los alumnos volverán a aparecer en Calificaciones y en los módulos que muestran
                                matrícula activa. Bajas, traslados, suspendidos e inactivos no serán modificados.
                            </small>
                        </span>
                    </label>

                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                        <p class="font-black text-emerald-900 dark:text-emerald-100">Cuando termines las correcciones
                        </p>
                        <p class="mt-1 text-sm leading-6 text-emerald-800 dark:text-emerald-200">
                            Regresa a esta sección, cierra la generación y deja marcada la opción
                            “Marcar como egresados a los alumnos activos”.
                        </p>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/30">
                    <flux:button wire:click="$set('modalReactivar', false)">Cancelar</flux:button>
                    <flux:button variant="primary" icon="lock-open" wire:click="reactivar" spinner="reactivar">
                        Reabrir generación
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <livewire:generacion.editar-generacion />
</div>
