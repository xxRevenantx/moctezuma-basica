<div class="space-y-5" wire:key="reingreso-alumno-{{ $slug_nivel }}">
    <section
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-violet-600 via-[#006492] to-[#88AC2E]"></div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.22em] text-violet-600 dark:text-violet-300">
                        Retorno de alumnos
                    </p>

                    <h3 class="mt-1 text-xl font-black text-slate-900 dark:text-white">
                        Reingreso de exalumno y reincorporación
                    </h3>

                    <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
                        Reutiliza el mismo alumno, conserva sus etapas anteriores y crea una trayectoria nueva.
                        Reingreso corresponde a un egresado; reincorporación, a quien se trasladó o causó baja
                        antes de concluir.
                    </p>
                </div>

                <span
                    class="rounded-full bg-violet-100 px-3 py-1.5 text-xs font-black text-violet-700 dark:bg-violet-900/30 dark:text-violet-200">
                    Solo administrador
                </span>
            </div>
        </div>
    </section>

    @includeWhen(!$alumno_id, 'livewire.accion.partials.reingreso-buscador')
    @includeWhen((bool) $alumno_id, 'livewire.accion.partials.reingreso-formulario')
</div>
