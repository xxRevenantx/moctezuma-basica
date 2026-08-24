@props([
    'eyebrow' => 'Administración institucional',
    'title',
    'description',
])

<section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>
    <div class="relative flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] text-white shadow-lg shadow-sky-900/15">
                <flux:icon.squares-2x2 class="h-7 w-7" />
            </div>
            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-[#006492] dark:text-sky-300">{{ $eyebrow }}</p>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">{{ $title }}</h1>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $description }}</p>
            </div>
        </div>
        <div class="shrink-0 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-xs font-bold text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/25 dark:text-sky-200">
            Vista global · Todos los niveles
        </div>
    </div>
</section>
