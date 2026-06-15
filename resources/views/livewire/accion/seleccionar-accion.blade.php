<div x-data="{ nav: false }" x-init="window.addEventListener('livewire:navigate:start', () => nav = true);
window.addEventListener('livewire:navigate:finish', () => nav = false);" class="relative space-y-5">

    @php
        $ui = [
            'generales' => [
                'icon' =>
                    'M4 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3H4V5Zm0 5h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9Zm3 3v2h4v-2H7Z',
                'gradient' => 'from-blue-500 to-cyan-500',
                'descripcion' => 'Información general del nivel',
            ],
            'matricula' => [
                'icon' =>
                    'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 6a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 12c-2.5 0-4.7-1.2-6.1-3.1 1.2-2.1 3.5-3.4 6.1-3.4s4.9 1.3 6.1 3.4C16.7 18.8 14.5 20 12 20Z',
                'gradient' => 'from-indigo-500 to-sky-500',
                'descripcion' => 'Registro y control de alumnos',
            ],
            'asignacion-de-materias' => [
                'icon' =>
                    'M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 4h10V5H7v2Zm0 4h10V9H7v2Zm0 4h10v-2H7v2Z',
                'gradient' => 'from-sky-500 to-cyan-500',
                'descripcion' => 'Materias por grupo y nivel',
            ],
            'horarios' => [
                'icon' => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z',
                'gradient' => 'from-amber-500 to-orange-500',
                'descripcion' => 'Distribución de clases',
            ],
            'calificaciones' => [
                'icon' =>
                    'M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2ZM7 17l3-4 2 3 3-5 2 6H7Z',
                'gradient' => 'from-violet-500 to-fuchsia-500',
                'descripcion' => 'Evaluación y promedios',
            ],
            'bajas' => [
                'icon' =>
                    'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.3 13.7L12 11.4 7.7 15.7 6.3 14.3 10.6 10 6.3 5.7 7.7 4.3 12 8.6l4.3-4.3 1.4 1.4L13.4 10l4.3 4.3-1.4 1.4Z',
                'gradient' => 'from-rose-500 to-red-500',
                'descripcion' => 'Control de bajas escolares',
            ],
            'fichas' => [
                'icon' =>
                    'M6 2h9l5 5v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm8 1.5V8h4.5L14 3.5ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h4v2H8V8Z',
                'gradient' => 'from-pink-500 to-rose-500',
                'descripcion' => 'Fichas exclusivas de preescolar',
            ],
        ];

        $fallback = [
            'icon' => 'M6 4h12v2H6V4Zm0 5h12v2H6V9Zm0 5h12v2H6v-2Z',
            'gradient' => 'from-slate-500 to-slate-700',
            'descripcion' => 'Módulo del sistema escolar',
        ];

        $cardThemes = [
            'generales' => [
                'bg' =>
                    'from-blue-50 via-white to-cyan-50 dark:from-blue-500/10 dark:via-neutral-900 dark:to-cyan-500/10',
                'iconBox' => 'from-blue-500 to-cyan-500',
                'accent' => 'text-blue-700 dark:text-blue-300',
                'ring' => 'ring-blue-400/30',
                'soft' => 'bg-blue-500/10 text-blue-700 ring-blue-500/20 dark:text-blue-300',
            ],
            'matricula' => [
                'bg' =>
                    'from-sky-50 via-white to-indigo-50 dark:from-sky-500/10 dark:via-neutral-900 dark:to-indigo-500/10',
                'iconBox' => 'from-sky-500 to-indigo-500',
                'accent' => 'text-sky-700 dark:text-sky-300',
                'ring' => 'ring-indigo-400/30',
                'soft' => 'bg-sky-500/10 text-sky-700 ring-sky-500/20 dark:text-sky-300',
            ],
            'asignacion-de-materias' => [
                'bg' =>
                    'from-cyan-50 via-white to-sky-50 dark:from-cyan-500/10 dark:via-neutral-900 dark:to-sky-500/10',
                'iconBox' => 'from-cyan-500 to-sky-500',
                'accent' => 'text-cyan-700 dark:text-cyan-300',
                'ring' => 'ring-cyan-400/30',
                'soft' => 'bg-cyan-500/10 text-cyan-700 ring-cyan-500/20 dark:text-cyan-300',
            ],
            'horarios' => [
                'bg' =>
                    'from-amber-50 via-white to-orange-50 dark:from-amber-500/10 dark:via-neutral-900 dark:to-orange-500/10',
                'iconBox' => 'from-amber-500 to-orange-500',
                'accent' => 'text-amber-700 dark:text-amber-300',
                'ring' => 'ring-amber-400/30',
                'soft' => 'bg-amber-500/10 text-amber-700 ring-amber-500/20 dark:text-amber-300',
            ],
            'calificaciones' => [
                'bg' =>
                    'from-violet-50 via-white to-fuchsia-50 dark:from-violet-500/10 dark:via-neutral-900 dark:to-fuchsia-500/10',
                'iconBox' => 'from-violet-500 to-fuchsia-500',
                'accent' => 'text-violet-700 dark:text-violet-300',
                'ring' => 'ring-violet-400/30',
                'soft' => 'bg-violet-500/10 text-violet-700 ring-violet-500/20 dark:text-violet-300',
            ],
            'bajas' => [
                'bg' =>
                    'from-rose-50 via-white to-pink-50 dark:from-rose-500/10 dark:via-neutral-900 dark:to-pink-500/10',
                'iconBox' => 'from-rose-500 to-pink-500',
                'accent' => 'text-rose-700 dark:text-rose-300',
                'ring' => 'ring-rose-400/30',
                'soft' => 'bg-rose-500/10 text-rose-700 ring-rose-500/20 dark:text-rose-300',
            ],
            'fichas' => [
                'bg' =>
                    'from-pink-50 via-white to-rose-50 dark:from-pink-500/10 dark:via-neutral-900 dark:to-rose-500/10',
                'iconBox' => 'from-pink-500 to-rose-500',
                'accent' => 'text-pink-700 dark:text-pink-300',
                'ring' => 'ring-pink-400/30',
                'soft' => 'bg-pink-500/10 text-pink-700 ring-pink-500/20 dark:text-pink-300',
            ],
        ];

        $accionActual = $accionActual ?? (request()->route('accion') ?? request('accion'));
        $badges = $badges ?? ['bajas' => 0];

        $accionesVisibles = collect($acciones)->filter(function ($accion) use ($slug_nivel) {
            if ($slug_nivel === 'preescolar' && $accion->slug === 'calificaciones') {
                return false;
            }

            if ($slug_nivel !== 'preescolar' && $accion->slug === 'fichas') {
                return false;
            }

            return true;
        });

        $accionActiva = $accionesVisibles->firstWhere('slug', $accionActual);
        $cfgActiva = $ui[$accionActual] ?? $fallback;
        $themeActiva = $cardThemes[$accionActual] ?? [
            'bg' =>
                'from-slate-50 via-white to-neutral-50 dark:from-slate-500/10 dark:via-neutral-900 dark:to-neutral-500/10',
            'iconBox' => 'from-slate-500 to-neutral-600',
            'accent' => 'text-slate-700 dark:text-slate-300',
            'ring' => 'ring-slate-400/30',
            'soft' => 'bg-slate-500/10 text-slate-700 ring-slate-500/20 dark:text-slate-300',
        ];
    @endphp


    {{-- MENÚ HORIZONTAL PREMIUM --}}
    <div class="relative mx-auto w-full">
        <div
            class="absolute -inset-[1px] rounded-[32px] bg-gradient-to-r from-sky-500/35 via-indigo-500/35 to-fuchsia-500/35 blur-[2px]">
        </div>

        <div class="absolute left-0 right-0 top-0 z-20 h-[3px] overflow-hidden rounded-t-[32px]" aria-hidden="true">
            <div class="h-full w-full origin-left scale-x-0 bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500 transition-transform duration-500"
                :class="nav ? 'scale-x-100' : 'scale-x-0'"></div>
        </div>

        <div
            class="relative overflow-hidden rounded-[32px] border border-white/50 bg-white/80 p-3 shadow-[0_18px_45px_-20px_rgba(15,23,42,0.28)] backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80">

            <div
                class="overflow-x-auto overflow-y-hidden pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <div class="flex min-w-max gap-3">
                    @foreach ($accionesVisibles as $a)
                        @php
                            $cfg = $ui[$a->slug] ?? $fallback;

                            $theme = $cardThemes[$a->slug] ?? [
                                'bg' =>
                                    'from-slate-50 via-white to-neutral-50 dark:from-slate-500/10 dark:via-neutral-900 dark:to-neutral-500/10',
                                'iconBox' => 'from-slate-500 to-neutral-600',
                                'accent' => 'text-slate-700 dark:text-slate-300',
                                'ring' => 'ring-slate-400/30',
                                'soft' => 'bg-slate-500/10 text-slate-700 ring-slate-500/20 dark:text-slate-300',
                            ];

                            $isActive = $accionActual === $a->slug;
                            $badge = $badges[$a->slug] ?? null;
                        @endphp

                        <button type="button"
                            class="group relative w-[265px] shrink-0 text-left outline-none disabled:cursor-wait disabled:opacity-80"
                            wire:click="ir('{{ $a->slug }}')" wire:navigate :disabled="nav"
                            aria-current="{{ $isActive ? 'page' : 'false' }}">

                            <div @class([
                                'relative min-h-[155px] overflow-hidden rounded-[26px] border p-4 transition-all duration-300',
                                'bg-gradient-to-br ' .
                                $theme['bg'] .
                                ' border-white/80 shadow-[0_18px_42px_-20px_rgba(59,130,246,0.55)] ring-2 ' .
                                $theme['ring'] .
                                ' dark:border-white/10' => $isActive,
                                'bg-gradient-to-br ' .
                                $theme['bg'] .
                                ' border-white/60 hover:-translate-y-1 hover:shadow-[0_18px_40px_-20px_rgba(15,23,42,0.32)] dark:border-white/10' => !$isActive,
                            ])>

                                <div
                                    class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full bg-white/50 blur-2xl dark:bg-white/5">
                                </div>

                                <div
                                    class="pointer-events-none absolute -bottom-10 -left-10 h-24 w-24 rounded-full bg-white/30 blur-2xl dark:bg-white/5">
                                </div>

                                <div class="relative flex items-start justify-between gap-3">
                                    <div
                                        class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br {{ $theme['iconBox'] }} text-white shadow-lg shadow-black/10 transition-transform duration-300 group-hover:scale-105">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                                            <path d="{{ $cfg['icon'] }}"></path>
                                        </svg>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        @if ($a->slug === 'fichas')
                                            <span
                                                class="rounded-full bg-pink-500/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-pink-700 ring-1 ring-pink-500/20 dark:text-pink-300">
                                                Preescolar
                                            </span>
                                        @endif

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
                                </div>

                                <div class="relative mt-4">
                                    <h3
                                        class="line-clamp-2 min-h-[38px] text-[15px] font-black tracking-tight text-neutral-800 dark:text-white">
                                        {{ $a->accion }}
                                    </h3>

                                    <p
                                        class="mt-1 line-clamp-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                        {{ $cfg['descripcion'] ?? 'Módulo del sistema escolar' }}
                                    </p>

                                    <div class="mt-4 flex items-end justify-between">
                                        <span class="text-3xl font-extrabold leading-none {{ $theme['accent'] }}">
                                            {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                        </span>

                                        <span
                                            class="rounded-full px-3 py-1.5 text-[11px] font-black transition-all duration-300
                                            {{ $isActive
                                                ? 'bg-white/90 text-indigo-600 ring-1 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/20'
                                                : 'bg-white/70 text-neutral-500 ring-1 ring-neutral-200 group-hover:text-indigo-600 dark:bg-white/10 dark:text-neutral-400 dark:ring-white/10 dark:group-hover:text-indigo-300' }}">
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
        class="relative overflow-hidden rounded-[30px] border border-white/40 bg-white/85 shadow-[0_20px_60px_-25px_rgba(15,23,42,0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/85">

        <div class="h-1 w-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>

        <div x-show="nav" x-transition.opacity
            class="absolute inset-0 z-20 flex items-center justify-center bg-white/60 backdrop-blur-sm dark:bg-neutral-950/60">
            <div
                class="flex items-center gap-3 rounded-2xl border border-white/50 bg-white/85 px-5 py-3 shadow-xl backdrop-blur dark:border-white/10 dark:bg-neutral-900/85">
                <svg class="h-5 w-5 animate-spin text-indigo-600 dark:text-indigo-300" viewBox="0 0 24 24"
                    fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".2"
                        stroke-width="4"></circle>
                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" stroke-linecap="round">
                    </path>
                </svg>

                <span class="text-sm font-bold text-neutral-700 dark:text-neutral-200">
                    Cargando módulo...
                </span>
            </div>
        </div>

        @if ($accionActiva)
            <div
                class="border-b border-neutral-100 bg-gradient-to-r from-neutral-50/90 via-white/80 to-neutral-50/90 px-4 py-3 dark:border-white/10 dark:from-neutral-900/90 dark:via-neutral-900/80 dark:to-neutral-900/90 sm:px-5 lg:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br {{ $themeActiva['iconBox'] }} text-white shadow-md shadow-black/10">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                                <path d="{{ $cfgActiva['icon'] }}"></path>
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-black text-neutral-800 dark:text-white">
                                {{ $accionActiva->accion }}
                            </p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $cfgActiva['descripcion'] ?? 'Módulo del sistema escolar' }}
                            </p>
                        </div>
                    </div>

                    <span class="w-fit rounded-full px-3 py-1 text-xs font-black ring-1 {{ $themeActiva['soft'] }}">
                        Módulo activo
                    </span>
                </div>
            </div>
        @endif

        <div class="p-4 sm:p-5 lg:p-6">
            @switch($accionActual)
                @case('generales')
                    <livewire:accion.generales :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
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
                    <livewire:accion.baja :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
                @break

                @case('fichas')
                    @if ($slug_nivel === 'preescolar')
                        <livewire:accion.ficha :slug_nivel="$slug_nivel" :slug_grado="$slug_grado" />
                    @else
                        <div
                            class="relative overflow-hidden rounded-3xl border border-rose-200 bg-rose-50/80 p-8 text-center shadow-sm dark:border-rose-500/20 dark:bg-rose-500/10">
                            <div
                                class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-rose-500 to-pink-500 text-white shadow-lg shadow-rose-500/20">
                                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="currentColor" aria-hidden="true">
                                    <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5v6h-2V7h2Zm0 8v2h-2v-2h2Z">
                                    </path>
                                </svg>
                            </div>

                            <h3 class="mt-4 text-base font-black text-rose-700 dark:text-rose-200">
                                Acción no disponible
                            </h3>

                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-rose-600/80 dark:text-rose-200/80">
                                La sección de fichas solo está disponible para el nivel preescolar.
                            </p>
                        </div>
                    @endif
                @break

                @default
                    <div
                        class="relative overflow-hidden rounded-3xl border border-dashed border-neutral-300 bg-neutral-50/70 p-10 text-center dark:border-neutral-700 dark:bg-neutral-800/40">

                        <div
                            class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-gradient-to-br from-slate-500 to-slate-700 text-white shadow-lg shadow-slate-500/20">
                            <svg viewBox="0 0 24 24" class="h-7 w-7" fill="currentColor" aria-hidden="true">
                                <path d="M6 4h12v2H6V4Zm0 5h12v2H6V9Zm0 5h12v2H6v-2Z"></path>
                            </svg>
                        </div>

                        <h3 class="mt-4 text-base font-black text-neutral-800 dark:text-white">
                            Selecciona una acción
                        </h3>

                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                            Elige una tarjeta del menú superior para visualizar el contenido correspondiente.
                        </p>
                    </div>
            @endswitch
        </div>
    </div>
</div>
