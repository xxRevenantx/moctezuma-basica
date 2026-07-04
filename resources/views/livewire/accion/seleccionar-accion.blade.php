<div x-data="{ nav: false }" x-on:livewire:navigate.document="nav = true" x-on:livewire:navigated.document="nav = false"
    class="relative space-y-5">

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


    {{-- MENÚ CURVO COMPACTO --}}
    <nav class="relative mx-auto w-full" aria-label="Módulos del nivel">
        <div
            class="pointer-events-none absolute inset-x-4 bottom-0 top-8 rounded-[32px] bg-gradient-to-r from-sky-500/25 via-violet-500/25 to-fuchsia-500/25 blur-md dark:from-sky-500/10 dark:via-violet-500/10 dark:to-fuchsia-500/10">
        </div>

        <div class="absolute inset-x-0 top-8 z-30 h-[3px] overflow-hidden rounded-t-[30px]" aria-hidden="true">
            <div class="h-full w-full origin-left scale-x-0 bg-gradient-to-r from-[#006492] via-violet-500 to-[#88AC2E] transition-transform duration-500"
                :class="nav ? 'scale-x-100' : 'scale-x-0'"></div>
        </div>

        {{-- El padding superior permite que el botón activo sobresalga sin ser recortado al desplazar el menú. --}}
        <div
            class="relative overflow-x-auto overflow-y-hidden pt-8 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <div
                class="relative flex h-[82px] min-w-[720px] items-stretch rounded-[30px] border border-white/80 bg-white/95 px-2 shadow-[0_18px_42px_-24px_rgba(67,56,202,0.50)] backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/95 sm:min-w-full sm:px-3">

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

                        $parametrosAccion = [
                            'slug_nivel' => $slug_nivel,
                            'accion' => $a->slug,
                        ];

                        if (filled($slug_grado)) {
                            $parametrosAccion['slug_grado'] = $slug_grado;
                        }

                        $urlAccion = route('submodulos.accion', $parametrosAccion);
                    @endphp

                    <a href="{{ $urlAccion }}" @if (!$isActive) wire:navigate.hover @endif
                        @if ($isActive) x-on:click.prevent
                        @else
                            x-on:click="if (nav) { $event.preventDefault() } else { nav = true }" @endif
                        aria-current="{{ $isActive ? 'page' : 'false' }}"
                        class="group relative flex min-w-[118px] flex-1 flex-col items-center justify-end px-2 pb-3 text-center outline-none transition">

                        @if ($isActive)
                            {{-- Semicírculo blanco que integra el botón elevado con la barra. --}}
                            <span
                                class="pointer-events-none absolute -top-[26px] left-1/2 z-10 h-[62px] w-[94px] -translate-x-1/2 rounded-t-[999px] bg-white dark:bg-neutral-900"
                                aria-hidden="true"></span>
                        @endif

                        <span @class([
                            'grid place-items-center transition-all duration-300',
                            'absolute -top-[28px] left-1/2 z-20 h-[62px] w-[62px] -translate-x-1/2 rounded-full border-[6px] border-white bg-gradient-to-br text-white shadow-[0_12px_26px_-10px_rgba(79,70,229,0.80)] dark:border-neutral-900 ' .
                            $theme['iconBox'] => $isActive,
                            'relative mb-1.5 h-8 w-8 rounded-xl text-slate-400 group-hover:-translate-y-0.5 group-hover:text-violet-500 dark:text-slate-500 dark:group-hover:text-violet-300' => !$isActive,
                        ])>
                            <svg viewBox="0 0 24 24" @class(['h-6 w-6' => $isActive, 'h-5 w-5' => !$isActive]) fill="currentColor"
                                aria-hidden="true">
                                <path d="{{ $cfg['icon'] }}"></path>
                            </svg>

                            @if (!is_null($badge) && $badge > 0)
                                <span
                                    class="absolute -right-1.5 -top-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[9px] font-black leading-none text-white shadow-sm ring-2 ring-white dark:ring-neutral-900">
                                    {{ $badge }}
                                </span>
                            @endif
                        </span>

                        <span @class([
                            'relative z-20 max-w-[115px] truncate text-[11px] font-black transition-colors duration-300 sm:text-xs',
                            $theme['accent'] => $isActive,
                            'text-slate-400 group-hover:text-violet-600 dark:text-slate-500 dark:group-hover:text-violet-300' => !$isActive,
                        ])>
                            {{ $a->accion }}
                        </span>

                        @if ($isActive)
                            <span
                                class="absolute bottom-1.5 left-1/2 z-20 h-1 w-8 -translate-x-1/2 rounded-full bg-gradient-to-r {{ $cfg['gradient'] }} shadow-sm"
                                aria-hidden="true"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </nav>

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
