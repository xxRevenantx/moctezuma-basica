<div class="w-full flex-1" x-data="{ nav: false }" x-init="window.addEventListener('livewire:navigate:start', () => nav = true);
window.addEventListener('livewire:navigate:finish', () => nav = false);">
    {{-- ✅ WRAPPER CENTRADO --}}
    <div class="mx-auto w-full px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6">

            @php
                // Config visual por slug (icono + estilos)
                $ui = [
                    'matricula' => [
                        'iconWrap' => 'bg-indigo-50 ring-indigo-200 dark:bg-indigo-500/15 dark:ring-indigo-500/30',
                        'icon' =>
                            'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 6a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 12c-2.5 0-4.7-1.2-6.1-3.1 1.2-2.1 3.5-3.4 6.1-3.4s4.9 1.3 6.1 3.4C16.7 18.8 14.5 20 12 20Z',
                    ],
                    'asignacion-de-materias' => [
                        'iconWrap' => 'bg-sky-50 ring-sky-200 dark:bg-sky-500/15 dark:ring-sky-500/30',
                        'icon' =>
                            'M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 4h10V5H7v2Zm0 4h10V9H7v2Zm0 4h10v-2H7v2Z',
                    ],
                    'horarios' => [
                        'iconWrap' => 'bg-amber-50 ring-amber-200 dark:bg-amber-500/15 dark:ring-amber-500/30',
                        'icon' => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z',
                    ],
                    'calificaciones' => [
                        'iconWrap' => 'bg-violet-50 ring-violet-200 dark:bg-violet-500/15 dark:ring-violet-500/30',
                        'icon' =>
                            'M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2ZM7 17l3-4 2 3 3-5 2 6H7Z',
                    ],
                    'bajas' => [
                        'iconWrap' => 'bg-rose-50 ring-rose-200 dark:bg-rose-500/15 dark:ring-rose-500/30',
                        'icon' =>
                            'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.3 13.7L12 11.4 7.7 15.7 6.3 14.3 10.6 10 6.3 5.7 7.7 4.3 12 8.6l4.3-4.3 1.4 1.4L13.4 10l4.3 4.3-1.4 1.4Z',
                    ],
                ];

                $fallback = [
                    'iconWrap' => 'bg-neutral-50 ring-neutral-200 dark:bg-white/10 dark:ring-white/10',
                    'icon' => 'M6 4h12v2H6V4Zm0 5h12v2H6V9Zm0 5h12v2H6v-2Z',
                ];

                $accionActual = $accionActual ?? (request()->route('accion') ?? request('accion'));
                $badges = $badges ?? ['bajas' => 0];
            @endphp

            {{-- BARRA TIPO TABS (sin recarga) --}}
            <div class="relative mx-auto w-full">
                {{-- borde degradado --}}
                <div
                    class="absolute -inset-[2px] rounded-[26px] bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500 opacity-90 blur-[0.5px]">
                </div>

                {{-- loader superior tipo Flux --}}
                <div class="absolute left-0 right-0 top-0 z-10 h-[3px] overflow-hidden rounded-t-[24px]"
                    aria-hidden="true">
                    <div class="h-full w-full origin-left scale-x-0 bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500 transition-transform duration-300"
                        :class="nav ? 'scale-x-100' : 'scale-x-0'"></div>
                </div>

                <div
                    class="relative rounded-[24px] border border-white/30 bg-white/80 p-3 shadow-lg backdrop-blur
                            dark:border-neutral-800/70 dark:bg-neutral-900/70">

                    {{-- ✅ CENTRADO: en vez de que arranque a la izquierda, lo centramos --}}
                    <div class="flex justify-center">
                        <div
                            class="flex gap-3 overflow-x-auto py-1 px-1
                                    [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            @foreach ($acciones as $a)
                                @php
                                    $cfg = $ui[$a->slug] ?? $fallback;
                                    $isActive = $accionActual === $a->slug;
                                    $badge = $badges[$a->slug] ?? null;
                                @endphp

                                <button x-cloak type="button"
                                    class="group shrink-0 text-left disabled:cursor-wait disabled:opacity-80"
                                    wire:click="ir('{{ $a->slug }}')" wire:navigate :disabled="nav"
                                    aria-current="{{ $isActive ? 'page' : 'false' }}">
                                    <div
                                        class="relative flex items-center gap-3 rounded-2xl px-3.5 py-2.5 ring-1 transition
                                        {{ $isActive
                                            ? 'bg-indigo-50/80 ring-indigo-200 shadow-sm dark:bg-indigo-500/15 dark:ring-indigo-500/35'
                                            : 'bg-white/60 ring-neutral-200 hover:bg-white hover:ring-neutral-300 dark:bg-neutral-950/40 dark:ring-white/10 dark:hover:bg-neutral-950/60' }}
                                    ">

                                        {{-- icono --}}
                                        <div
                                            class="grid h-9 w-9 place-items-center rounded-2xl ring-1 {{ $cfg['iconWrap'] }}">
                                            <svg viewBox="0 0 24 24"
                                                class="h-5 w-5 {{ $isActive ? 'text-indigo-700 dark:text-indigo-200' : 'text-neutral-700 dark:text-neutral-200' }}"
                                                fill="currentColor" aria-hidden="true">
                                                <path d="{{ $cfg['icon'] }}"></path>
                                            </svg>
                                        </div>

                                        {{-- texto + badge --}}
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm font-semibold tracking-tight
                                                {{ $isActive ? 'text-indigo-700 dark:text-indigo-100' : 'text-neutral-800 dark:text-neutral-100' }}">
                                                {{ $a->accion }}
                                            </span>

                                            @if (!is_null($badge))
                                                <span
                                                    class="inline-flex min-w-6 items-center justify-center rounded-xl px-2 py-0.5 text-xs font-bold
                                                    {{ $badge > 0
                                                        ? 'bg-rose-100 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-200 dark:ring-rose-500/30'
                                                        : 'bg-neutral-100 text-neutral-700 ring-1 ring-neutral-200 dark:bg-white/10 dark:text-neutral-200 dark:ring-white/10' }}">
                                                    {{ $badge }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- indicador activo inferior --}}
                                        @if ($isActive)
                                            <div
                                                class="absolute -bottom-[7px] left-5 right-5 h-1 rounded-full bg-gradient-to-r from-indigo-500 to-sky-500">
                                            </div>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>

            {{-- panel inferior (opcional) --}}
            <div
                class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">



                <div>


                    @switch($accionActual)
                        @case('matricula')
                            <livewire:accion.matricula :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
                        @break

                        @case('asignacion-de-materias')
                            {{-- <livewire:accion.matricula :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" /> --}}
                            <h1>Desde asignación materias</h1>
                            {{-- <livewire:accion.asignacion-de-materias :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" /> --}}
                        @break

                        @default
                    @endswitch


                </div>


            </div>

        </div>
    </div>
</div>
