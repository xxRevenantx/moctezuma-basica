@if ($documento->es_organizado)
    <span
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-2 text-[10px] font-black uppercase text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50">
        <flux:icon name="squares-2x2" class="size-3.5" />
        {{ max((int) $documento->paginas_total, 1) }} pág. · Confirmado
    </span>

    <a href="{{ route('misrutas.expedientes.originals', $documento) }}"
        class="inline-flex items-center gap-1.5 rounded-xl border border-violet-200 bg-white px-3 py-2 text-xs font-black text-violet-700 transition hover:bg-violet-50 dark:border-violet-900 dark:bg-neutral-900 dark:text-violet-300">
        <flux:icon name="archive-box-arrow-down" class="size-3.5" />
        Original{{ count($documento->organizacion?->fuentes_ids ?? []) === 1 ? '' : 'es' }}
    </a>
@endif

@if (!$soloHistorico && $documento->es_organizable)
    <button type="button" wire:click="abrirOrganizador"
        class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-black text-indigo-700 transition hover:bg-indigo-50 dark:border-indigo-900 dark:bg-neutral-900 dark:text-indigo-300">
        <flux:icon name="squares-plus" class="size-3.5" />
        Organizar páginas
    </button>
@endif
