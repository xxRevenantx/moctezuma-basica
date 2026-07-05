<div class="space-y-5">
    <div
        class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
        Cerrar una generación la conserva para consultas y reportes. También puedes reabrirla temporalmente
        para corregir calificaciones, boletas o documentación de alumnos egresados.
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <flux:select wire:model="generacion_id" label="Generación a cerrar">
            <flux:select.option value="">Selecciona</flux:select.option>
            @foreach ($generaciones->where('status', true) as $g)
                <flux:select.option value="{{ $g->id }}">
                    {{ $g->etiqueta }} · {{ $g->inscripciones_count }} alumnos
                </flux:select.option>
            @endforeach
        </flux:select>

        <div class="lg:col-span-2">
            <flux:input wire:model="motivo" label="Motivo obligatorio"
                placeholder="Ejemplo: finalización oficial de la generación" />
        </div>
    </div>

    <label
        class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-rose-300 hover:bg-rose-50/50 dark:border-neutral-800 dark:hover:border-rose-900/60 dark:hover:bg-rose-950/20">
        <input type="checkbox" wire:model="egresar_activos"
            class="mt-1 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
        <span>
            <b class="block text-slate-900 dark:text-white">Marcar como egresados a los alumnos activos</b>
            <small class="mt-1 block text-slate-500 dark:text-slate-400">
                Las bajas, traslados, suspendidos e inactivos conservarán su estatus actual.
            </small>
        </span>
    </label>

    <div class="flex justify-end">
        <flux:button wire:click="desactivar" variant="primary" icon="lock-closed" spinner="desactivar">
            Cerrar generación
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead
                    class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="p-3 text-left">Generación</th>
                        <th class="p-3">Total</th>
                        <th class="p-3">Activos</th>
                        <th class="p-3">Egresados</th>
                        <th class="p-3">Estado</th>
                        <th class="p-3 text-right">Acción</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @foreach ($generaciones as $g)
                        <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                            <td class="p-3 font-black text-slate-900 dark:text-white">{{ $g->etiqueta }}</td>
                            <td class="p-3 text-center">{{ $g->inscripciones_count }}</td>
                            <td class="p-3 text-center">
                                <flux:badge color="green">{{ $g->activos_count }}</flux:badge>
                            </td>
                            <td class="p-3 text-center">
                                <flux:badge color="purple">{{ $g->egresados_count }}</flux:badge>
                            </td>
                            <td class="p-3 text-center">
                                @if ($g->status)
                                    <flux:badge color="green" icon="check-circle">Activa</flux:badge>
                                @else
                                    <flux:badge color="zinc" icon="lock-closed">Cerrada</flux:badge>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                @unless ($g->status)
                                    <flux:button size="sm" variant="primary" icon="lock-open"
                                        wire:click="prepararReactivacion({{ $g->id }})">
                                        Reabrir para correcciones
                                    </flux:button>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

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
                                {{ $generacionReactivar?->egresados_count ?? 0 }} alumno(s) egresado(s)
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 p-6">
                    <div
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                        Esta reapertura conserva la matrícula, grupo, generación, calificaciones e historial del alumno.
                        No genera una inscripción nueva ni lo registra como reingreso.
                    </div>

                    <flux:textarea wire:model="motivo_reactivacion" label="Motivo de la reapertura"
                        placeholder="Ejemplo: corrección de calificaciones del tercer periodo" rows="3" />

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50/60 p-4 transition hover:border-sky-300 dark:border-sky-900/50 dark:bg-sky-950/20">
                        <input type="checkbox" wire:model="reactivar_egresados"
                            class="mt-1 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                        <span>
                            <b class="block text-slate-900 dark:text-white">
                                Reactivar temporalmente a los egresados
                            </b>
                            <small class="mt-1 block leading-5 text-slate-600 dark:text-slate-400">
                                Volverán a mostrarse en Calificaciones y en los apartados de matrícula activa.
                                Los demás estados no serán modificados.
                            </small>
                        </span>
                    </label>

                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                        <p class="font-black text-emerald-900 dark:text-emerald-100">Al terminar</p>
                        <p class="mt-1 text-sm leading-6 text-emerald-800 dark:text-emerald-200">
                            Cierra nuevamente la generación y conserva marcada la opción para egresar a los alumnos
                            activos.
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
</div>
