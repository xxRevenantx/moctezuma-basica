@php
    $tonos = [
        'emerald' =>
            'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
        'amber' =>
            'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
        'violet' =>
            'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/20',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20',
        'slate' =>
            'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-500/20',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
        'indigo' =>
            'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/20',
    ];
@endphp

<div x-data="{
    cerrando: false,
    solicitarApertura() {
        window.dispatchEvent(new CustomEvent('buscador-global-iniciando'));
        window.Livewire.dispatch('abrir-buscador-global');
    },
    enfocar() {
        this.$nextTick(() => setTimeout(() => this.$refs.campoBusqueda?.focus(), 60));
    },
    desplazar(indice) {
        this.$nextTick(() => {
            document.querySelector(`[data-resultado-global='${indice}']`)
                ?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        });
    },
    solicitarCierre() {
        if (this.cerrando) return;

        this.cerrando = true;
        window.Livewire.dispatch('cerrar-buscador-global');

        // Evita que el estado visual quede bloqueado si la petición falla.
        setTimeout(() => this.cerrando = false, 5000);
    }
}" x-on:keydown.window.ctrl.k.prevent="solicitarApertura()"
    x-on:keydown.window.meta.k.prevent="solicitarApertura()"
    x-on:keydown.escape.window="if (document.querySelector('[data-buscador-global-modal]')) solicitarCierre()"
    x-on:buscador-global-cerrado.window="cerrando = false" x-on:enfocar-buscador-global.window="enfocar()"
    x-on:resultado-buscador-activo.window="desplazar($event.detail.indice)">
    @if (auth()->user()?->is_admin && $modalAbierto)
        <div x-ref="modalBusquedaGlobal" x-init="enfocar()" data-buscador-global-modal class="fixed inset-0 z-[9999]"
            role="dialog" aria-modal="true" aria-label="Búsqueda global">
            <div x-transition.opacity.duration.150ms class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm"
                x-on:click="solicitarCierre()"></div>

            <div
                class="relative mx-auto flex min-h-full w-full items-start justify-center px-3 pb-8 pt-[5vh] sm:px-6 sm:pt-[9vh]">
                <section x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]" x-on:click.stop
                    class="flex max-h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-[28px] border border-white/60 bg-white shadow-[0_35px_100px_-20px_rgba(15,23,42,0.65)] dark:border-white/10 dark:bg-neutral-950">
                    <div class="h-1.5 shrink-0 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>

                    <div class="border-b border-neutral-200 p-3 dark:border-neutral-800 sm:p-4">
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 px-4 shadow-inner focus-within:border-[#006492]/50 focus-within:ring-4 focus-within:ring-[#006492]/10 dark:border-neutral-700 dark:bg-neutral-900">
                            <flux:icon.magnifying-glass class="size-5 shrink-0 text-[#006492] dark:text-sky-300" />

                            <input x-ref="campoBusqueda" type="search" wire:model.live.debounce.280ms="consulta"
                                wire:keydown.arrow-down.prevent="siguiente" wire:keydown.arrow-up.prevent="anterior"
                                wire:keydown.enter.prevent="seleccionarActivo" autocomplete="off" spellcheck="false"
                                placeholder="Nombre, matrícula, CURP, materia, folio, grupo, horario..."
                                class="h-14 min-w-0 flex-1 border-0 bg-transparent px-0 text-base font-semibold text-neutral-900 outline-none ring-0 placeholder:font-normal placeholder:text-neutral-400 focus:border-0 focus:ring-0 dark:text-white dark:placeholder:text-neutral-500">

                            <div wire:loading.flex wire:target="consulta" class="shrink-0 items-center">
                                <svg class="size-5 animate-spin text-[#006492]" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor"
                                        stroke-opacity=".2" stroke-width="3"></circle>
                                    <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="3"
                                        stroke-linecap="round"></path>
                                </svg>
                            </div>

                            @if ($consulta !== '')
                                <button type="button" wire:click="limpiar"
                                    class="grid size-8 shrink-0 place-items-center rounded-xl text-neutral-400 transition hover:bg-neutral-200 hover:text-neutral-700 dark:hover:bg-neutral-800 dark:hover:text-neutral-200"
                                    aria-label="Limpiar búsqueda">
                                    <flux:icon.x-mark class="size-4" />
                                </button>
                            @endif

                            <button type="button" x-on:click="solicitarCierre()" x-bind:disabled="cerrando"
                                class="hidden shrink-0 rounded-lg border border-neutral-200 bg-white px-2 py-1 text-[10px] font-black text-neutral-500 shadow-sm transition disabled:cursor-wait disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 sm:block">
                                ESC
                            </button>
                        </div>

                        <div
                            class="mt-3 flex flex-wrap items-center justify-between gap-2 px-1 text-[11px] text-neutral-500 dark:text-neutral-400">
                            <p>
                                Busca también por <strong
                                    class="font-black text-neutral-700 dark:text-neutral-200">cal:8</strong>,
                                <strong class="font-black text-neutral-700 dark:text-neutral-200">hor:nombre</strong>,
                                materia, ciclo escolar o nombre del alumno o profesor.
                            </p>

                            @if ($totalResultados > 0)
                                <span
                                    class="rounded-full bg-neutral-100 px-2.5 py-1 font-black text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                                    {{ $totalResultados }} resultado{{ $totalResultados === 1 ? '' : 's' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-3 sm:p-4">
                        @if ($mensajeError)
                            <div
                                class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-center text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200">
                                {{ $mensajeError }}
                            </div>
                        @elseif (mb_strlen(trim($consulta)) < 2)
                            <div class="grid min-h-[300px] place-items-center px-6 py-10 text-center">
                                <div>
                                    <div
                                        class="mx-auto grid size-16 place-items-center rounded-[22px] bg-gradient-to-br from-sky-100 to-lime-100 text-[#006492] ring-1 ring-sky-200 dark:from-sky-500/10 dark:to-lime-500/10 dark:text-sky-300 dark:ring-sky-500/20">
                                        <flux:icon.command-line class="size-8" />
                                    </div>
                                    <h2 class="mt-5 text-lg font-black text-neutral-900 dark:text-white">
                                        Encuentra cualquier registro
                                    </h2>
                                    <p
                                        class="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                        Escribe al menos dos caracteres. Puedes localizar alumnos, personal, tutores,
                                        constancias, grupos, generaciones, calificaciones y horarios de alumnos o
                                        profesores.
                                    </p>
                                </div>
                            </div>
                        @elseif ($busquedaEjecutada && $totalResultados === 0)
                            <div class="grid min-h-[300px] place-items-center px-6 py-10 text-center">
                                <div>
                                    <div
                                        class="mx-auto grid size-16 place-items-center rounded-[22px] bg-neutral-100 text-neutral-400 dark:bg-neutral-900 dark:text-neutral-500">
                                        <flux:icon.magnifying-glass class="size-8" />
                                    </div>
                                    <h2 class="mt-5 text-lg font-black text-neutral-900 dark:text-white">
                                        No se encontraron coincidencias
                                    </h2>
                                    <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                                        Prueba con la matrícula, CURP, folio, materia o un nombre más corto.
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="space-y-5">
                                @foreach ($categorias as $categoria)
                                    <section wire:key="categoria-global-{{ $categoria['clave'] }}">
                                        <div class="mb-2 flex items-center gap-2 px-2">
                                            <span
                                                class="grid size-7 place-items-center rounded-lg bg-neutral-100 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                                                @switch($categoria['icono'])
                                                    @case('users')
                                                        <flux:icon.users class="size-4" />
                                                    @break

                                                    @case('academic-cap')
                                                        <flux:icon.academic-cap class="size-4" />
                                                    @break

                                                    @case('briefcase')
                                                        <flux:icon.briefcase class="size-4" />
                                                    @break

                                                    @case('user-group')
                                                        <flux:icon.user-group class="size-4" />
                                                    @break

                                                    @case('document-text')
                                                        <flux:icon.document-text class="size-4" />
                                                    @break

                                                    @case('rectangle-group')
                                                        <flux:icon.rectangle-group class="size-4" />
                                                    @break

                                                    @case('clock')
                                                        <flux:icon.clock class="size-4" />
                                                    @break

                                                    @default
                                                        <flux:icon.calendar-days class="size-4" />
                                                @endswitch
                                            </span>

                                            <h3
                                                class="text-xs font-black uppercase tracking-[0.13em] text-neutral-500 dark:text-neutral-400">
                                                {{ $categoria['titulo'] }}
                                            </h3>

                                            <span
                                                class="rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-black text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                                                {{ count($categoria['resultados']) }}
                                            </span>
                                        </div>

                                        <div class="space-y-1.5">
                                            @foreach ($categoria['resultados'] as $resultado)
                                                @php
                                                    $activo = (int) $indiceActivo === (int) $resultado['indice'];
                                                    $tono = $tonos[$resultado['tono'] ?? 'slate'] ?? $tonos['slate'];
                                                @endphp

                                                <button type="button"
                                                    data-resultado-global="{{ $resultado['indice'] }}"
                                                    wire:click="seleccionar({{ $resultado['indice'] }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="seleccionar({{ $resultado['indice'] }})"
                                                    @class([
                                                        'group flex w-full items-start gap-3 rounded-2xl border p-3 text-left outline-none transition disabled:cursor-wait disabled:opacity-70 sm:p-4',
                                                        'border-[#006492]/40 bg-sky-50/80 shadow-sm ring-2 ring-[#006492]/10 dark:border-sky-500/40 dark:bg-sky-500/10' => $activo,
                                                        'border-transparent hover:border-neutral-200 hover:bg-neutral-50 dark:hover:border-neutral-800 dark:hover:bg-neutral-900/70' => !$activo,
                                                    ])>
                                                    <span @class([
                                                        'grid size-11 shrink-0 place-items-center rounded-2xl text-xs font-black ring-1 transition sm:size-12',
                                                        $tono,
                                                        'scale-105' => $activo,
                                                    ])>
                                                        {{ $resultado['iniciales'] ?: '—' }}
                                                    </span>

                                                    <span class="min-w-0 flex-1">
                                                        <span
                                                            class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                                                            <span class="min-w-0">
                                                                <span
                                                                    class="block truncate text-sm font-black text-neutral-900 dark:text-white">
                                                                    {{ $resultado['titulo'] }}
                                                                </span>

                                                                @if (filled($resultado['subtitulo'] ?? null))
                                                                    <span
                                                                        class="mt-0.5 block truncate text-xs font-semibold text-neutral-600 dark:text-neutral-300">
                                                                        {{ $resultado['subtitulo'] }}
                                                                    </span>
                                                                @endif
                                                            </span>

                                                            @if (filled($resultado['estado'] ?? null))
                                                                <span
                                                                    class="w-fit shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black ring-1 {{ $tono }}">
                                                                    {{ $resultado['estado'] }}
                                                                </span>
                                                            @endif
                                                        </span>

                                                        @if (filled($resultado['detalle'] ?? null))
                                                            <span
                                                                class="mt-1.5 block line-clamp-2 text-[11px] leading-5 text-neutral-500 dark:text-neutral-400">
                                                                {{ $resultado['detalle'] }}
                                                            </span>
                                                        @endif
                                                    </span>

                                                    <span class="mt-2.5 grid size-5 shrink-0 place-items-center">
                                                        <span wire:loading.remove
                                                            wire:target="seleccionar({{ $resultado['indice'] }})">
                                                            <flux:icon.arrow-right
                                                                class="size-4 text-neutral-300 transition group-hover:translate-x-0.5 group-hover:text-[#006492] dark:text-neutral-700 dark:group-hover:text-sky-300" />
                                                        </span>

                                                        <span wire:loading.flex
                                                            wire:target="seleccionar({{ $resultado['indice'] }})"
                                                            class="items-center justify-center"
                                                            aria-label="Abriendo resultado">
                                                            <svg class="size-5 animate-spin text-[#006492] dark:text-sky-300"
                                                                viewBox="0 0 24 24" fill="none">
                                                                <circle cx="12" cy="12" r="9"
                                                                    stroke="currentColor" stroke-opacity=".2"
                                                                    stroke-width="3"></circle>
                                                                <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor"
                                                                    stroke-width="3" stroke-linecap="round"></path>
                                                            </svg>
                                                        </span>
                                                    </span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div wire:loading.flex wire:target="seleccionarActivo"
                        class="absolute inset-0 z-40 items-center justify-center bg-white/75 backdrop-blur-[2px] dark:bg-neutral-950/75">
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white px-5 py-3 text-sm font-black text-neutral-700 shadow-xl dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100">
                            <svg class="size-5 animate-spin text-[#006492] dark:text-sky-300" viewBox="0 0 24 24"
                                fill="none">
                                <circle cx="12" cy="12" r="9" stroke="currentColor"
                                    stroke-opacity=".2" stroke-width="3"></circle>
                                <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="3"
                                    stroke-linecap="round"></path>
                            </svg>
                            Abriendo resultado...
                        </div>
                    </div>

                    <footer
                        class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-t border-neutral-200 bg-neutral-50 px-4 py-3 text-[10px] font-bold text-neutral-500 dark:border-neutral-800 dark:bg-neutral-900/70 dark:text-neutral-400">
                        <div class="flex items-center gap-3">
                            <span><kbd
                                    class="rounded border border-neutral-300 bg-white px-1.5 py-0.5 dark:border-neutral-700 dark:bg-neutral-800">↑</kbd>
                                <kbd
                                    class="rounded border border-neutral-300 bg-white px-1.5 py-0.5 dark:border-neutral-700 dark:bg-neutral-800">↓</kbd>
                                Navegar</span>
                            <span><kbd
                                    class="rounded border border-neutral-300 bg-white px-1.5 py-0.5 dark:border-neutral-700 dark:bg-neutral-800">Enter</kbd>
                                Abrir</span>
                        </div>

                        <span class="text-[#006492] dark:text-sky-300">Centro Universitario Moctezuma</span>
                    </footer>
                </section>
            </div>

            <div x-cloak x-show="cerrando" x-transition.opacity.duration.150ms
                class="absolute inset-0 z-50 flex items-center justify-center bg-slate-950/35 backdrop-blur-[2px]">
                <div
                    class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white px-5 py-3 text-sm font-black text-neutral-700 shadow-2xl dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100">
                    <svg class="size-5 animate-spin text-[#006492] dark:text-sky-300" viewBox="0 0 24 24"
                        fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".2"
                            stroke-width="3"></circle>
                        <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                        </path>
                    </svg>
                    Cerrando buscador...
                </div>
            </div>

        </div>
    @endif
</div>
