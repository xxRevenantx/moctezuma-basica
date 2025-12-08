<div class="flex items-center gap-2">
    @auth
        @php($user = auth()->user())

        <span class="relative flex h-9 w-9 shrink-0 overflow-hidden rounded-lg">
            @if ($user->photo)
                <img src="{{ asset('storage/profile-photos/' . $user->photo) }}" alt="{{ $user->name }}"
                    class="h-full w-full object-cover">
            @else
                <span
                    class="flex h-full w-full items-center justify-center rounded-lg bg-zinc-800 text-sm font-semibold text-zinc-50">
                    {{ $user->initials() }}
                </span>
            @endif
        </span>

        <div class="flex flex-col leading-tight">
            <span class="text-sm font-semibold truncate">
                {{ $user->name }}
            </span>
            <span class="text-[11px] text-zinc-400">
                Administrador
            </span>
        </div>
    @endauth

    @guest
        {{-- Opcional: qué mostrar si no hay usuario autenticado --}}
        <span class="relative flex h-9 w-9 shrink-0 overflow-hidden rounded-lg">
            <span
                class="flex h-full w-full items-center justify-center rounded-lg bg-zinc-800 text-sm font-semibold text-zinc-50">
                ?
            </span>
        </span>
        <div class="flex flex-col leading-tight">
            <span class="text-sm font-semibold truncate">
                Invitado
            </span>
            <span class="text-[11px] text-zinc-400">
                Sin sesión
            </span>
        </div>
    @endguest
</div>
