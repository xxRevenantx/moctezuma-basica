<div class="flex items-center gap-2">
    <span class="relative flex h-9 w-9 shrink-0 overflow-hidden rounded-lg">
        @if (auth()->user()->photo)
            <img src="{{ asset('storage/profile-photos/' . auth()->user()->photo) }}" alt="{{ auth()->user()->name }}"
                class="h-full w-full object-cover">
        @else
            <span
                class="flex h-full w-full items-center justify-center rounded-lg bg-zinc-800 text-sm font-semibold text-zinc-50">
                {{ auth()->user()->initials() }}
            </span>
        @endif
    </span>
    <div class="flex flex-col leading-tight">
        <span class="text-sm font-semibold truncate">
            {{ auth()->user()->name }}
        </span>
        <span class="text-[11px] text-zinc-400">
            Administrador
        </span>
    </div>
</div>
