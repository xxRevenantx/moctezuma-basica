@props([
    'show' => 'show',
    'loading' => 'loading',
    'titulo' => 'Modal',
    'subtitulo' => null,
    'modalId' => 'titulo-modal',
    'eventoAbrir' => 'abrir-modal',
    'eventoCerrar' => 'cerrar-modal',
    'eventoCargado' => 'modal-cargado',
    'wireCerrar' => 'cerrarModal',
    'textoCargando' => 'Cargando información...',
    'maxWidth' => 'max-w-2xl',
])

<div x-data="{ {{ $show }}: false, {{ $loading }}: false }" x-cloak x-trap.noscroll="{{ $show }}" x-show="{{ $show }}"
    @{{ $eventoAbrir }}.window="{{ $show }} = true; {{ $loading }} = true"
    @{{ $eventoCargado }}.window="{{ $loading }} = false"
    @{{ $eventoCerrar }}.window="
        {{ $show }} = false;
        {{ $loading }} = false;
        $wire.{{ $wireCerrar }}()
    "
    @keydown.escape.window="{{ $show }} = false; $wire.{{ $wireCerrar }}()"
    class="fixed inset-0 z-50 flex items-center justify-center" aria-live="polite">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-neutral-900/70 backdrop-blur-sm" x-show="{{ $show }}" x-transition.opacity
        @click.self="{{ $show }} = false; $wire.{{ $wireCerrar }}()"></div>

    <!-- Modal -->
    <div class="relative w-[92vw] sm:w-[88vw] md:w-[90vw] {{ $maxWidth }} mx-4 sm:mx-6
               bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl ring-1 ring-black/5 dark:ring-white/10
               overflow-hidden flex flex-col max-h-[85vh]"
        role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}" x-show="{{ $show }}"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2" wire:ignore.self>
        <!-- Loader -->
        <div x-show="{{ $loading }}" x-transition.opacity
            class="absolute inset-0 z-20 flex items-center justify-center
                   bg-white/80 dark:bg-neutral-900/80 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-2">
                <svg class="w-6 h-6 animate-spin text-indigo-600 dark:text-indigo-400"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                    {{ $textoCargando }}
                </p>
            </div>
        </div>

        <!-- Barra superior -->
        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500 shrink-0"></div>

        <!-- Header -->
        <div
            class="px-5 sm:px-6 pt-4 pb-3 flex items-start justify-between gap-3
                    sticky top-0 bg-white/95 dark:bg-neutral-900/95 backdrop-blur z-10">
            <div class="min-w-0">
                <h2 id="{{ $modalId }}" class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white">
                    {{ $titulo }}
                </h2>

                @if ($subtitulo)
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        {!! $subtitulo !!}
                    </p>
                @endif
            </div>

            <button @click="{{ $show }} = false; $wire.{{ $wireCerrar }}()" type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full
                       text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100
                       dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-neutral-800
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                aria-label="Cerrar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Contenido -->
        <div class="flex-1 overflow-y-auto">
            {{ $slot }}
        </div>
    </div>
</div>
