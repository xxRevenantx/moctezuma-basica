<div x-data="{ nav: false }" x-init="window.addEventListener('livewire:navigate:start', () => nav = true);
window.addEventListener('livewire:navigate:finish', () => nav = false);" class="relative">
    {{-- Fondo decorativo suave --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-20 left-[-3rem] h-56 w-56 rounded-full bg-sky-500/10 blur-3xl"></div>
        <div class="absolute right-[-4rem] top-10 h-64 w-64 rounded-full bg-fuchsia-500/10 blur-3xl"></div>
        <div class="absolute bottom-[-4rem] left-1/3 h-56 w-56 rounded-full bg-indigo-500/10 blur-3xl"></div>
    </div>

    {{-- Wrapper --}}
    <div class="relative mx-auto w-full ">
        <div class="flex flex-col gap-5">

            @php
                // Configuración visual por slug
                $ui = [
                    'matricula' => [
                        'iconWrap' => 'bg-indigo-500/10 ring-indigo-400/30 text-indigo-600 dark:text-indigo-300',
                        'icon' =>
                            'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 6a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 12c-2.5 0-4.7-1.2-6.1-3.1 1.2-2.1 3.5-3.4 6.1-3.4s4.9 1.3 6.1 3.4C16.7 18.8 14.5 20 12 20Z',
                        'gradient' => 'from-indigo-500 to-sky-500',
                    ],
                    'asignacion-de-materias' => [
                        'iconWrap' => 'bg-sky-500/10 ring-sky-400/30 text-sky-600 dark:text-sky-300',
                        'icon' =>
                            'M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 4h10V5H7v2Zm0 4h10V9H7v2Zm0 4h10v-2H7v2Z',
                        'gradient' => 'from-sky-500 to-cyan-500',
                    ],
                    'horarios' => [
                        'iconWrap' => 'bg-amber-500/10 ring-amber-400/30 text-amber-600 dark:text-amber-300',
                        'icon' => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z',
                        'gradient' => 'from-amber-500 to-orange-500',
                    ],
                    'calificaciones' => [
                        'iconWrap' => 'bg-violet-500/10 ring-violet-400/30 text-violet-600 dark:text-violet-300',
                        'icon' =>
                            'M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2ZM7 17l3-4 2 3 3-5 2 6H7Z',
                        'gradient' => 'from-violet-500 to-fuchsia-500',
                    ],
                    'bajas' => [
                        'iconWrap' => 'bg-rose-500/10 ring-rose-400/30 text-rose-600 dark:text-rose-300',
                        'icon' =>
                            'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.3 13.7L12 11.4 7.7 15.7 6.3 14.3 10.6 10 6.3 5.7 7.7 4.3 12 8.6l4.3-4.3 1.4 1.4L13.4 10l4.3 4.3-1.4 1.4Z',
                        'gradient' => 'from-rose-500 to-red-500',
                    ],
                ];

                $fallback = [
                    'iconWrap' => 'bg-neutral-500/10 ring-neutral-400/20 text-neutral-700 dark:text-neutral-200',
                    'icon' => 'M6 4h12v2H6V4Zm0 5h12v2H6V9Zm0 5h12v2H6v-2Z',
                    'gradient' => 'from-slate-500 to-slate-700',
                ];

                $accionActual = $accionActual ?? (request()->route('accion') ?? request('accion'));
                $badges = $badges ?? ['bajas' => 0];
            @endphp


            {{-- Tabs compactas premium --}}
            <div class="relative mx-auto w-full max-w-6xl">
                <div
                    class="absolute -inset-[1px] rounded-[24px] bg-gradient-to-r from-sky-500/60 via-indigo-500/60 to-fuchsia-500/60 blur-[1px]">
                </div>

                {{-- Loader superior --}}
                <div class="absolute left-0 right-0 top-0 z-20 h-[2px] overflow-hidden rounded-t-[22px]"
                    aria-hidden="true">
                    <div class="h-full w-full origin-left scale-x-0 bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500 transition-transform duration-500"
                        :class="nav ? 'scale-x-100' : 'scale-x-0'"></div>
                </div>

                <div
                    class="relative overflow-hidden rounded-[22px] border border-white/40 bg-white/75 p-2 shadow-[0_14px_35px_-20px_rgba(15,23,42,0.25)] backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/75">
                    <div class="flex justify-center">
                        <div
                            class="flex gap-2 overflow-x-auto px-1 py-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            @foreach ($acciones as $a)
                                @php
                                    $cfg = $ui[$a->slug] ?? $fallback;
                                    $isActive = $accionActual === $a->slug;
                                    $badge = $badges[$a->slug] ?? null;
                                @endphp

                                <button x-cloak type="button"
                                    class="group relative shrink-0 text-left disabled:cursor-wait disabled:opacity-80"
                                    wire:click="ir('{{ $a->slug }}')" wire:navigate :disabled="nav"
                                    aria-current="{{ $isActive ? 'page' : 'false' }}">
                                    <div class="relative overflow-hidden rounded-[18px] px-3 py-2 ring-1 transition-all duration-300"
                                        @class([
                                            'bg-white/90 shadow-[0_10px_25px_-15px_rgba(79,70,229,0.40)] ring-indigo-300/50 dark:bg-indigo-500/10 dark:ring-indigo-400/30' => $isActive,
                                            'bg-white/60 ring-neutral-200/80 hover:bg-white dark:bg-neutral-950/40 dark:ring-white/10 dark:hover:bg-neutral-950/60' => !$isActive,
                                        ])>
                                        {{-- brillo suave --}}
                                        <div
                                            class="pointer-events-none absolute inset-0 opacity-0 transition duration-300 group-hover:opacity-100">
                                            <div
                                                class="absolute inset-y-0 left-0 w-16 bg-white/30 blur-2xl dark:bg-white/5">
                                            </div>
                                        </div>

                                        <div class="relative flex items-center gap-2.5">
                                            {{-- Icono --}}
                                            <div
                                                class="grid h-9 w-9 place-items-center rounded-xl ring-1 {{ $cfg['iconWrap'] }}">
                                                <svg viewBox="0 0 24 24"
                                                    class="h-4 w-4 {{ $isActive ? 'text-indigo-700 dark:text-indigo-200' : '' }}"
                                                    fill="currentColor" aria-hidden="true">
                                                    <path d="{{ $cfg['icon'] }}"></path>
                                                </svg>
                                            </div>

                                            {{-- Texto --}}
                                            <div class="flex flex-col leading-tight">
                                                <span
                                                    class="text-[10px] font-medium uppercase tracking-[0.16em] text-neutral-400 dark:text-neutral-500">
                                                    módulo
                                                </span>

                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="text-[13px] font-bold tracking-tight {{ $isActive ? 'text-indigo-700 dark:text-indigo-100' : 'text-neutral-800 dark:text-neutral-100' }}">
                                                        {{ $a->accion }}
                                                    </span>

                                                    @if (!is_null($badge))
                                                        <span
                                                            class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold ring-1
                                                            {{ $badge > 0
                                                                ? 'bg-rose-100 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-200 dark:ring-rose-500/30'
                                                                : 'bg-neutral-100 text-neutral-700 ring-neutral-200 dark:bg-white/10 dark:text-neutral-200 dark:ring-white/10' }}">
                                                            {{ $badge }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Línea activa --}}
                                        @if ($isActive)
                                            <div
                                                class="absolute inset-x-3 bottom-0 h-[3px] rounded-full bg-gradient-to-r {{ $cfg['gradient'] }}">
                                            </div>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Panel principal --}}
            <div
                class="relative overflow-hidden rounded-[26px] border border-white/40 bg-white/85 shadow-[0_20px_60px_-25px_rgba(15,23,42,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/85">
                <div class="h-1 w-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>

                {{-- Overlay loader --}}
                <div x-show="nav" x-transition.opacity
                    class="absolute inset-0 z-20 flex items-center justify-center bg-white/55 backdrop-blur-sm dark:bg-neutral-950/55">
                    <div
                        class="flex items-center gap-3 rounded-2xl border border-white/50 bg-white/80 px-4 py-2.5 shadow-xl backdrop-blur dark:border-white/10 dark:bg-neutral-900/80">
                        <svg class="h-5 w-5 animate-spin text-indigo-600 dark:text-indigo-300" viewBox="0 0 24 24"
                            fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".2"
                                stroke-width="4"></circle>
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4"
                                stroke-linecap="round"></path>
                        </svg>
                        <span class="text-sm font-semibold text-neutral-700 dark:text-neutral-200">
                            Cargando módulo...
                        </span>
                    </div>
                </div>

                <div class="p-4 sm:p-5 lg:p-6">
                    @switch($accionActual)
                        @case('matricula')
                            <livewire:accion.matricula :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
                        @break

                        @case('asignacion-de-materias')
                            <livewire:accion.asignacion-materia :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
                        @break

                        @case('horarios')
                            <livewire:accion.horario :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
                        @break


                    @break

                    @default
                        <div
                            class="rounded-2xl border border-dashed border-neutral-300 bg-neutral-50/70 p-8 text-center dark:border-neutral-700 dark:bg-neutral-800/40">
                            <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                Selecciona una acción para visualizar su contenido.
                            </p>
                        </div>
                @endswitch
            </div>
        </div>

    </div>
</div>
</div>
