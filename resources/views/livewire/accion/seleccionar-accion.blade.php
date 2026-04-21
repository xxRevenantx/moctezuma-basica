<div x-data="{ nav: false }" x-init="window.addEventListener('livewire:navigate:start', () => nav = true);
window.addEventListener('livewire:navigate:finish', () => nav = false);" class="relative">

    @php
        $ui = [
            'generales' => [
                'icon' =>
                    'M4 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3H4V5Zm0 5h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9Zm3 3v2h4v-2H7Z',
                'gradient' => 'from-blue-500 to-cyan-500',
            ],
            'matricula' => [
                'icon' =>
                    'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 6a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 12c-2.5 0-4.7-1.2-6.1-3.1 1.2-2.1 3.5-3.4 6.1-3.4s4.9 1.3 6.1 3.4C16.7 18.8 14.5 20 12 20Z',
                'gradient' => 'from-indigo-500 to-sky-500',
            ],
            'asignacion-de-materias' => [
                'icon' =>
                    'M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 4h10V5H7v2Zm0 4h10V9H7v2Zm0 4h10v-2H7v2Z',
                'gradient' => 'from-sky-500 to-cyan-500',
            ],
            'horarios' => [
                'icon' => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z',
                'gradient' => 'from-amber-500 to-orange-500',
            ],
            'calificaciones' => [
                'icon' =>
                    'M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2ZM7 17l3-4 2 3 3-5 2 6H7Z',
                'gradient' => 'from-violet-500 to-fuchsia-500',
            ],
            'bajas' => [
                'icon' =>
                    'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.3 13.7L12 11.4 7.7 15.7 6.3 14.3 10.6 10 6.3 5.7 7.7 4.3 12 8.6l4.3-4.3 1.4 1.4L13.4 10l4.3 4.3-1.4 1.4Z',
                'gradient' => 'from-rose-500 to-red-500',
            ],
        ];

        $fallback = [
            'icon' => 'M6 4h12v2H6V4Zm0 5h12v2H6V9Zm0 5h12v2H6v-2Z',
            'gradient' => 'from-slate-500 to-slate-700',
        ];

        $cardThemes = [
            'generales' => [
                'bg' => 'from-blue-50 to-cyan-50 dark:from-blue-500/10 dark:to-cyan-500/10',
                'iconBox' => 'from-blue-500 to-cyan-500',
                'accent' => 'text-blue-700 dark:text-blue-300',
                'ring' => 'ring-blue-400/30',
            ],
            'matricula' => [
                'bg' => 'from-sky-50 to-indigo-50 dark:from-sky-500/10 dark:to-indigo-500/10',
                'iconBox' => 'from-sky-500 to-indigo-500',
                'accent' => 'text-sky-700 dark:text-sky-300',
                'ring' => 'ring-indigo-400/30',
            ],
            'asignacion-de-materias' => [
                'bg' => 'from-cyan-50 to-sky-50 dark:from-cyan-500/10 dark:to-sky-500/10',
                'iconBox' => 'from-cyan-500 to-sky-500',
                'accent' => 'text-cyan-700 dark:text-cyan-300',
                'ring' => 'ring-cyan-400/30',
            ],
            'horarios' => [
                'bg' => 'from-amber-50 to-orange-50 dark:from-amber-500/10 dark:to-orange-500/10',
                'iconBox' => 'from-amber-500 to-orange-500',
                'accent' => 'text-amber-700 dark:text-amber-300',
                'ring' => 'ring-amber-400/30',
            ],
            'calificaciones' => [
                'bg' => 'from-violet-50 to-fuchsia-50 dark:from-violet-500/10 dark:to-fuchsia-500/10',
                'iconBox' => 'from-violet-500 to-fuchsia-500',
                'accent' => 'text-violet-700 dark:text-violet-300',
                'ring' => 'ring-violet-400/30',
            ],
            'bajas' => [
                'bg' => 'from-rose-50 to-pink-50 dark:from-rose-500/10 dark:to-pink-500/10',
                'iconBox' => 'from-rose-500 to-pink-500',
                'accent' => 'text-rose-700 dark:text-rose-300',
                'ring' => 'ring-rose-400/30',
            ],
        ];

        $accionActual = $accionActual ?? (request()->route('accion') ?? request('accion'));
        $badges = $badges ?? ['bajas' => 0];
    @endphp

    {{-- MENÚ HORIZONTAL PREMIUM --}}
    <div class="relative mx-auto w-full ">
        <div
            class="absolute -inset-[1px] rounded-[30px] bg-gradient-to-r from-sky-500/35 via-indigo-500/35 to-fuchsia-500/35 blur-[2px]">
        </div>

        <div class="absolute left-0 right-0 top-0 z-20 h-[3px] overflow-hidden rounded-t-[30px]" aria-hidden="true">
            <div class="h-full w-full origin-left scale-x-0 bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500 transition-transform duration-500"
                :class="nav ? 'scale-x-100' : 'scale-x-0'"></div>
        </div>

        <div
            class="relative overflow-hidden rounded-[30px] border border-white/50 bg-white/80 p-3 shadow-[0_18px_45px_-20px_rgba(15,23,42,0.28)] backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80">
            <div
                class="overflow-x-auto overflow-y-hidden [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <div class="flex min-w-max gap-3">
                    @foreach ($acciones as $a)
                        @php
                            $cfg = $ui[$a->slug] ?? $fallback;
                            $theme = $cardThemes[$a->slug] ?? [
                                'bg' => 'from-slate-50 to-neutral-50 dark:from-slate-500/10 dark:to-neutral-500/10',
                                'iconBox' => 'from-slate-500 to-neutral-600',
                                'accent' => 'text-slate-700 dark:text-slate-300',
                                'ring' => 'ring-slate-400/30',
                            ];
                            $isActive = $accionActual === $a->slug;
                            $badge = $badges[$a->slug] ?? null;
                        @endphp

                        <button type="button"
                            class="group relative w-[250px] shrink-0 text-left disabled:cursor-wait disabled:opacity-80"
                            wire:click="ir('{{ $a->slug }}')" wire:navigate :disabled="nav"
                            aria-current="{{ $isActive ? 'page' : 'false' }}">

                            <div @class([
                                'relative overflow-hidden rounded-[24px] border p-4 transition-all duration-300',
                                'bg-gradient-to-br ' .
                                $theme['bg'] .
                                ' border-white/70 shadow-[0_14px_34px_-18px_rgba(59,130,246,0.45)] ring-2 ' .
                                $theme['ring'] .
                                ' dark:border-white/10' => $isActive,
                                'bg-gradient-to-br ' .
                                $theme['bg'] .
                                ' border-white/60 hover:-translate-y-1 hover:shadow-[0_18px_40px_-20px_rgba(15,23,42,0.28)] dark:border-white/10' => !$isActive,
                            ])>
                                <div
                                    class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-white/40 blur-2xl dark:bg-white/5">
                                </div>
                                <div
                                    class="pointer-events-none absolute bottom-0 left-0 h-16 w-16 rounded-full bg-white/20 blur-2xl dark:bg-white/5">
                                </div>

                                <div class="relative flex items-start justify-between gap-3">
                                    <div
                                        class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br {{ $theme['iconBox'] }} text-white shadow-lg shadow-black/10">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                                            <path d="{{ $cfg['icon'] }}"></path>
                                        </svg>
                                    </div>

                                    @if (!is_null($badge))
                                        <span
                                            class="inline-flex min-w-7 items-center justify-center rounded-full px-2 py-1 text-[11px] font-bold
                                            {{ $badge > 0
                                                ? 'bg-white/90 text-rose-600 shadow-sm ring-1 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-200 dark:ring-rose-500/30'
                                                : 'bg-white/80 text-neutral-500 shadow-sm ring-1 ring-neutral-200 dark:bg-white/10 dark:text-neutral-300 dark:ring-white/10' }}">
                                            {{ $badge }}
                                        </span>
                                    @endif
                                </div>

                                <div class="relative">
                                    <h3
                                        class="mt-1 line-clamp-2 min-h-[30px] text-[15px] font-bold tracking-tight text-neutral-800 dark:text-white">
                                        {{ $a->accion }}
                                    </h3>

                                    <div class=" flex items-end justify-between">
                                        <span class="text-3xl font-extrabold leading-none {{ $theme['accent'] }}">
                                            {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                        </span>

                                        <span
                                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold
                                            {{ $isActive
                                                ? 'bg-white/80 text-indigo-600 ring-1 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/20'
                                                : 'bg-white/70 text-neutral-500 ring-1 ring-neutral-200 dark:bg-white/10 dark:text-neutral-400 dark:ring-white/10' }}">
                                            {{ $isActive ? 'Activo' : 'Entrar' }}
                                        </span>
                                    </div>
                                </div>

                                @if ($isActive)
                                    <div
                                        class="absolute inset-x-4 bottom-0 h-1 rounded-full bg-gradient-to-r {{ $cfg['gradient'] }}">
                                    </div>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- PANEL PRINCIPAL --}}
    <div
        class="relative mt-5 overflow-hidden rounded-[28px] border border-white/40 bg-white/85 shadow-[0_20px_60px_-25px_rgba(15,23,42,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/85">
        <div class="h-1 w-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>

        <div x-show="nav" x-transition.opacity
            class="absolute inset-0 z-20 flex items-center justify-center bg-white/55 backdrop-blur-sm dark:bg-neutral-950/55">
            <div
                class="flex items-center gap-3 rounded-2xl border border-white/50 bg-white/80 px-4 py-2.5 shadow-xl backdrop-blur dark:border-white/10 dark:bg-neutral-900/80">
                <svg class="h-5 w-5 animate-spin text-indigo-600 dark:text-indigo-300" viewBox="0 0 24 24"
                    fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".2"
                        stroke-width="4"></circle>
                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" stroke-linecap="round">
                    </path>
                </svg>
                <span class="text-sm font-semibold text-neutral-700 dark:text-neutral-200">
                    Cargando módulo...
                </span>
            </div>
        </div>

        <div class="p-4 sm:p-5 lg:p-6">
            @switch($accionActual)
                @case('generales')
                    <div
                        class="rounded-2xl border border-dashed border-neutral-300 bg-neutral-50/70 p-8 text-center dark:border-neutral-700 dark:bg-neutral-800/40">
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                            Aquí puedes mostrar el contenido del módulo generales.
                        </p>
                    </div>
                @break

                @case('matricula')
                    <livewire:accion.matricula :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
                @break

                @case('asignacion-de-materias')
                    <livewire:accion.asignacion-materia :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
                @break

                @case('horarios')
                    <livewire:accion.horario :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
                @break

                @case('calificaciones')
                    <livewire:accion.calificacion :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
                @break

                @case('bajas')
                    <div
                        class="rounded-2xl border border-dashed border-neutral-300 bg-neutral-50/70 p-8 text-center dark:border-neutral-700 dark:bg-neutral-800/40">
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                            Aquí puedes mostrar el contenido del módulo bajas.
                        </p>
                    </div>
                @break

                @default
                    <div
                        class="rounded-2xl border border-dashed bg-neutral-50/70 p-8 text-center dark:border-neutral-700 dark:bg-neutral-800/40">
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                            Selecciona una acción para visualizar su contenido.
                        </p>
                    </div>
            @endswitch
        </div>
    </div>
</div>
