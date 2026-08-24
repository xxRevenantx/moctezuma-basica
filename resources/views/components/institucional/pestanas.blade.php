@props(['tabs', 'active'])

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <div class="flex min-w-max items-center gap-2">
        @foreach ($tabs as $value => $label)
            @php($activo = $active === $value)
            <button type="button" wire:click="seleccionarTab('{{ $value }}')"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition
                    {{ $activo
                        ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-neutral-800 dark:hover:text-white' }}">
                @if ($activo)
                    <span class="h-2 w-2 rounded-full bg-[#88AC2E]"></span>
                @endif
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>
