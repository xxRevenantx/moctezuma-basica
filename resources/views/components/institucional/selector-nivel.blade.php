@props(['niveles', 'slugNivel'])

<section class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white p-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <div class="-mx-1 overflow-x-auto px-1 py-1">
        <div class="flex min-w-max items-center gap-2">
            @foreach ($niveles as $item)
                @php($activo = $slugNivel === $item->slug)
                <button type="button" wire:click="seleccionarNivel('{{ $item->slug }}')"
                    wire:key="selector-nivel-global-{{ $item->id }}"
                    class="group relative inline-flex items-center gap-2 whitespace-nowrap rounded-2xl border px-4 py-2.5 text-sm font-bold transition-all duration-200 hover:-translate-y-0.5
                        {{ $activo
                            ? 'border-sky-200 bg-gradient-to-r from-[#006492] to-sky-600 text-white shadow-md shadow-sky-700/15'
                            : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:border-sky-800 dark:hover:bg-sky-950/20 dark:hover:text-sky-300' }}">
                    <flux:icon.rectangle-stack class="h-4 w-4" />
                    <span>{{ $item->nombre }}</span>
                    @if ($activo)
                        <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide">Activo</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</section>
