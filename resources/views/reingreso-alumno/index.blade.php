<x-layouts.app :title="__('Reingreso o reincorporación')">
    <div class="mx-auto max-w-7xl space-y-5 p-4 sm:p-6">
        <div class="flex flex-col gap-3 rounded-3xl border border-cyan-200 bg-gradient-to-r from-cyan-50 to-sky-50 p-5 shadow-sm dark:border-cyan-900/40 dark:from-cyan-950/20 dark:to-sky-950/20 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-cyan-700 dark:text-cyan-300">Proceso formal</p>
                <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">Reingreso o reincorporación</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $nivel->nombre }} · el historial anterior se conserva y se crea una nueva ubicación académica válida.</p>
            </div>
            <a href="{{ route('submodulos.accion', ['slug_nivel' => $slug_nivel, 'accion' => 'alumnos-no-vigentes']) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-black text-cyan-800 shadow-sm ring-1 ring-cyan-200 transition hover:bg-cyan-50 dark:bg-neutral-900 dark:text-cyan-200 dark:ring-cyan-900/50">
                <flux:icon.arrow-left class="h-4 w-4" /> Volver a no vigentes
            </a>
        </div>

        <livewire:accion.reingreso-alumno :slug_nivel="$slug_nivel" />
    </div>
</x-layouts.app>
