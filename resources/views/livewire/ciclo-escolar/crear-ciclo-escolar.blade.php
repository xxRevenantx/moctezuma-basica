<div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <form wire:submit="guardar" class="space-y-5">
        <div><h2 class="text-xl font-black">Crear ciclo escolar</h2><p class="text-sm text-slate-500">El ciclo organiza calificaciones, materias y horarios. Los alumnos se administran por generación.</p></div>
        <div class="grid gap-4 sm:grid-cols-2"><flux:input wire:model.live="inicio_anio" type="number" label="Año inicial" /><flux:input wire:model="fin_anio" type="number" label="Año final" /></div>
        <div class="space-y-3 rounded-2xl border p-4 dark:border-neutral-700">
            <label class="flex gap-3"><input type="checkbox" wire:model.live="marcar_como_actual"><span><b class="block">Marcar como ciclo actual</b><small class="text-slate-500">Se usará de forma predeterminada en calificaciones, materias y horarios.</small></span></label>
            <label class="flex gap-3 {{ !$marcar_como_actual ? 'opacity-50' : '' }}"><input type="checkbox" wire:model="cerrar_anterior" @disabled(!$marcar_como_actual)><span><b class="block">Cerrar el ciclo anterior</b><small class="text-slate-500">No mueve ni modifica alumnos.</small></span></label>
        </div>
        <flux:button type="submit" variant="primary" class="w-full" spinner="guardar">Crear ciclo escolar</flux:button>
    </form>
</div>
