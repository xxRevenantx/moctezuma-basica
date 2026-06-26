<div class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
    <div class="border-b border-slate-200 p-5 dark:border-neutral-700">
        <h2 class="text-xl font-black text-slate-900 dark:text-white">Crear ciclo escolar</h2>
        <p class="mt-1 text-sm text-slate-500">Al marcarlo como actual, el ciclo anterior puede cerrarse automáticamente sin perder su historial.</p>
    </div>

    <form wire:submit="guardar" class="space-y-5 p-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Año de inicio</span>
                <input wire:model.live.blur="inicio_anio" type="number" min="2000" max="{{ now()->addYears(5)->year }}" placeholder="2026"
                    class="w-full rounded-xl border-slate-300 bg-white dark:border-neutral-700 dark:bg-neutral-800" />
                @error('inicio_anio') <p class="text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
            </label>
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Año final</span>
                <input wire:model="fin_anio" type="number" min="2001" max="{{ now()->addYears(6)->year }}" placeholder="2027"
                    class="w-full rounded-xl border-slate-300 bg-white dark:border-neutral-700 dark:bg-neutral-800" />
                @error('fin_anio') <p class="text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
            </label>
        </div>

        <div class="rounded-2xl bg-sky-50 p-4 text-center dark:bg-sky-950/20">
            <p class="text-xs font-bold uppercase tracking-wide text-sky-600">Vista previa</p>
            <p class="mt-1 text-2xl font-black text-sky-900 dark:text-sky-100">{{ $inicio_anio ?: '----' }}-{{ $fin_anio ?: '----' }}</p>
        </div>

        <div class="space-y-3 rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" wire:model.live="marcar_como_actual" class="mt-1 rounded border-slate-300 text-sky-600" />
                <span>
                    <b class="block text-sm text-slate-900 dark:text-white">Marcar como ciclo actual</b>
                    <span class="text-xs text-slate-500">Será el ciclo seleccionado de forma predeterminada en matrícula, bajas y reportes.</span>
                </span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 {{ !$marcar_como_actual ? 'opacity-50' : '' }}">
                <input type="checkbox" wire:model="cerrar_anterior" @disabled(!$marcar_como_actual) class="mt-1 rounded border-slate-300 text-amber-600" />
                <span>
                    <b class="block text-sm text-slate-900 dark:text-white">Cerrar automáticamente el ciclo anterior</b>
                    <span class="text-xs text-slate-500">El historial seguirá disponible y el administrador podrá reabrirlo para correcciones.</span>
                </span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 {{ !$marcar_como_actual ? 'opacity-50' : '' }}">
                <input type="checkbox" wire:model="preparar_trayectorias" @disabled(!$marcar_como_actual) class="mt-1 rounded border-slate-300 text-emerald-600" />
                <span>
                    <b class="block text-sm text-slate-900 dark:text-white">Preparar trayectorias del nuevo ciclo</b>
                    <span class="text-xs text-slate-500">Promueve automáticamente conservando generación y grupo; los casos sin grupo destino quedarán para revisión manual.</span>
                </span>
            </label>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-600 to-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-sky-600/20 disabled:opacity-50">
            <span wire:loading.remove wire:target="guardar" class="inline-flex items-center gap-2"><flux:icon.plus class="h-4 w-4" /> Crear ciclo escolar</span>
            <span wire:loading wire:target="guardar">Guardando…</span>
        </button>
    </form>
</div>
