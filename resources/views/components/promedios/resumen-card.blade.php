@props([
    'titulo',
    'valor',
    'icono' => 'chart-bar',
    'destacado' => false,
    'advertencia' => false,
])

<div {{ $attributes->class([
    'rounded-[1.4rem] border p-4 shadow-sm',
    'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/20' => $destacado,
    'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/20' => $advertencia,
    'border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900' => ! $destacado && ! $advertencia,
]) }}>
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $titulo }}</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $valor }}</p>
        </div>
        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/80 text-[#006492] shadow-sm dark:bg-neutral-800 dark:text-sky-300">
            <flux:icon.chart-bar-square class="h-5 w-5" />
        </div>
    </div>
</div>
