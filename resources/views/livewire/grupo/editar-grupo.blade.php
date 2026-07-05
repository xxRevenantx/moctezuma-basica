<div x-data="{
    show: false,
    loading: false,

    abrir() {
        this.show = true;
        this.loading = true;
    },

    cargado() {
        this.loading = false;
    },

    cerrar() {
        if (this.loading) {
            return;
        }

        this.show = false;
        this.loading = false;

        $wire.cerrarModal();
    }
}" x-cloak x-trap.noscroll="show" x-show="show" x-on:abrir-modal-editar.window="abrir()"
    x-on:editar-cargado.window="cargado()"
    x-on:cerrar-modal-editar.window="
        show = false;
        loading = false;
    "
    x-on:keydown.escape.window="cerrar()" class="fixed inset-0 z-50 flex items-center justify-center p-4"
    aria-live="polite">
    {{-- Fondo --}}
    <div x-show="show" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" x-on:click.self="cerrar()"
        wire:loading.class="pointer-events-none" wire:target="actualizarGrupo"></div>

    {{-- Modal --}}
    <div x-show="show" x-transition:enter="transition duration-250 ease-out"
        x-transition:enter-start="translate-y-5 scale-95 opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
        x-transition:leave-end="translate-y-4 scale-95 opacity-0" wire:ignore.self role="dialog" aria-modal="true"
        aria-labelledby="titulo-modal-grupo"
        class="relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-slate-900 dark:ring-white/10">
        {{-- Acento institucional --}}
        <div class="h-1.5 w-full shrink-0 bg-gradient-to-r from-[#006492] via-[#368b75] to-[#88AC2E]"></div>

        {{-- Loader inicial --}}
        <div x-show="loading" x-transition.opacity
            class="absolute inset-0 z-40 flex items-center justify-center bg-white/90 px-6 backdrop-blur-sm dark:bg-slate-900/90">
            <div class="w-full max-w-sm text-center">
                <div class="relative mx-auto flex size-20 items-center justify-center">
                    <div class="absolute inset-0 animate-ping rounded-full bg-[#006492]/10"></div>

                    <div
                        class="relative flex size-16 items-center justify-center rounded-2xl bg-[#006492]/10 text-[#006492]">
                        <svg class="size-8 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>

                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                        </svg>
                    </div>
                </div>

                <h3 class="mt-5 text-lg font-black text-slate-900 dark:text-white">
                    Cargando grupo
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Consultando nivel, generación y periodo académico...
                </p>

                <div class="mt-7 space-y-3">
                    <div class="h-12 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800"></div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="h-12 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800"></div>

                        <div class="h-12 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Encabezado --}}
        <header
            class="relative shrink-0 overflow-hidden border-b border-slate-100 bg-gradient-to-r from-[#006492]/10 via-white to-[#88AC2E]/10 px-6 py-5 dark:border-slate-800 dark:via-slate-900 sm:px-7">
            <div class="absolute -right-16 -top-20 size-48 rounded-full bg-[#88AC2E]/10 blur-3xl"></div>

            <div class="relative flex items-start gap-4">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-lg shadow-[#006492]/20">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487 18.55 2.8a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14.25v4.125c0 1.036-.84 1.875-1.875 1.875H5.625A1.875 1.875 0 0 1 3.75 18.375V7.875C3.75 6.839 4.59 6 5.625 6H9.75" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <h2 id="titulo-modal-grupo"
                        class="text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                        Editar grupo
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Actualiza la organización académica del grupo seleccionado.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center rounded-full bg-[#006492]/10 px-3 py-1.5 text-xs font-bold text-[#006492] dark:bg-[#006492]/20 dark:text-sky-300">
                            {{ $nivel_nombre ?: 'Nivel no seleccionado' }}
                        </span>

                        @if ($esBachillerato)
                            <span
                                class="inline-flex items-center rounded-full bg-violet-100 px-3 py-1.5 text-xs font-bold text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                                Organización por semestre
                            </span>
                        @else
                            <span
                                class="inline-flex items-center rounded-full bg-[#88AC2E]/15 px-3 py-1.5 text-xs font-bold text-[#648315] dark:text-[#b8dc69]">
                                {{ $grado_nombre ?: 'Grado no seleccionado' }}
                            </span>
                        @endif

                        <span
                            class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            Grupo {{ $grupo_nombre ?: 'sin definir' }}
                        </span>
                    </div>
                </div>

                <button type="button" x-on:click="cerrar()" wire:loading.attr="disabled" wire:target="actualizarGrupo"
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-white hover:text-slate-800 hover:shadow-sm dark:hover:bg-slate-800 dark:hover:text-white"
                    aria-label="Cerrar">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </header>

        <form wire:submit="actualizarGrupo" class="relative flex min-h-0 flex-1 flex-col">
            {{-- Loader al guardar --}}
            <div wire:loading.flex wire:target="actualizarGrupo"
                class="absolute inset-0 z-30 items-center justify-center bg-white/85 px-6 backdrop-blur-sm dark:bg-slate-900/85">
                <div
                    class="rounded-3xl border border-slate-200 bg-white px-8 py-6 text-center shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                    <div
                        class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-[#006492]/10 text-[#006492]">
                        <svg class="size-7 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>

                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                        </svg>
                    </div>

                    <p class="mt-4 font-black text-slate-900 dark:text-white">
                        Guardando cambios
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Actualizando la información del grupo...
                    </p>
                </div>
            </div>

            {{-- Contenido --}}
            <div class="min-h-0 flex-1 overflow-y-auto">
                <fieldset wire:loading.attr="disabled" wire:target="actualizarGrupo"
                    class="space-y-6 px-6 py-6 sm:px-7">
                    {{-- Errores generales --}}
                    @if ($errors->any())
                        <div
                            class="rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-500/20 dark:bg-red-500/10">
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                    </svg>
                                </div>

                                <div>
                                    <p class="text-sm font-black text-red-800 dark:text-red-200">
                                        Revisa la información
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-red-700 dark:text-red-300">
                                        Algunos campos están incompletos o contienen
                                        información que no corresponde al nivel.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Identificación --}}
                    <section
                        class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 dark:border-slate-800 dark:bg-slate-950/20">
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex size-10 items-center justify-center rounded-xl bg-[#006492]/10 text-[#006492]">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3.75 6.75c0-1.036.84-1.875 1.875-1.875h12.75c1.036 0 1.875.84 1.875 1.875v10.5c0 1.036-.84 1.875-1.875 1.875H5.625A1.875 1.875 0 0 1 3.75 17.25V6.75ZM8.25 9h7.5m-7.5 3h7.5m-7.5 3H12" />
                                    </svg>
                                </div>

                                <div>
                                    <h3 class="font-black text-slate-900 dark:text-white">
                                        Identificación del grupo
                                    </h3>

                                    <p class="text-xs text-slate-500">
                                        Selecciona la letra o nombre con el que se identifica.
                                    </p>
                                </div>
                            </div>

                            <flux:button type="button" variant="primary" size="sm" icon="plus"
                                x-on:click="
                                    $dispatch('abrir-modal-asignacion-grupo');
                                    Livewire.dispatch('editarModalAsignacionGrupo');
                                ">
                                Crear o administrar
                            </flux:button>
                        </div>

                        <flux:select wire:model.live="asignacion_grupo_id" label="Grupo"
                            description="Ejemplo: A, B, Único o Mixto.">
                            <flux:select.option value="">
                                Selecciona un grupo
                            </flux:select.option>

                            @foreach ($asignacionGrupos as $grupo)
                                <flux:select.option value="{{ $grupo->id }}">
                                    {{ $grupo->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:error name="asignacion_grupo_id" />
                    </section>

                    {{-- Organización académica --}}
                    <section>
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-xl bg-[#88AC2E]/15 text-[#668318] dark:text-[#b6dd5f]">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 14.25 3.75 9.75 12 5.25l8.25 4.5L12 14.25Zm0 0v5.25m-6-7.5v4.125A2.625 2.625 0 0 0 8.625 18.75h6.75A2.625 2.625 0 0 0 18 16.125V12" />
                                </svg>
                            </div>

                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white">
                                    Organización académica
                                </h3>

                                <p class="text-xs text-slate-500">
                                    El formulario se adapta automáticamente al nivel.
                                </p>
                            </div>
                        </div>

                        <div class="relative grid grid-cols-1 gap-5 md:grid-cols-2">
                            {{-- Loader al cambiar nivel --}}
                            <div wire:loading.flex wire:target="nivel_id"
                                class="absolute inset-0 z-20 items-center justify-center rounded-2xl bg-white/80 backdrop-blur-sm dark:bg-slate-900/80">
                                <div
                                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                                    <svg class="size-5 animate-spin text-[#006492]" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>

                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                                    </svg>

                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-white">
                                            Cargando opciones
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            Consultando el nivel seleccionado...
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <flux:select wire:model.live="nivel_id" label="Nivel educativo"
                                description="Define los grados, generaciones y semestres.">
                                <flux:select.option value="">
                                    Selecciona un nivel
                                </flux:select.option>

                                @foreach ($niveles as $nivel)
                                    <flux:select.option value="{{ $nivel->id }}">
                                        {{ $nivel->nombre }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            {{-- Generación --}}
                            <flux:select wire:model="generacion_id" label="Generación" :disabled="!$nivel_id">
                                @if (!$nivel_id)
                                    <flux:select.option value="">
                                        Primero selecciona un nivel
                                    </flux:select.option>
                                @else
                                    <flux:select.option value="">
                                        Selecciona una generación
                                    </flux:select.option>

                                    @forelse ($generaciones as $generacion)
                                        <flux:select.option value="{{ $generacion->id }}">
                                            {{ $generacion->anio_ingreso }}-{{ $generacion->anio_egreso }}
                                            · {{ $generacion->nivel?->nombre }}
                                            @if (!$generacion->status)
                                                · Inactiva
                                            @endif
                                        </flux:select.option>
                                    @empty
                                        <flux:select.option value="" disabled>
                                            No hay generaciones disponibles
                                        </flux:select.option>
                                    @endforelse
                                @endif
                            </flux:select>

                            {{-- Grado o aviso --}}
                            @if (!$esBachillerato)
                                <flux:select wire:model.live="grado_id" label="Grado" :disabled="!$nivel_id">
                                    @if (!$nivel_id)
                                        <flux:select.option value="">
                                            Primero selecciona un nivel
                                        </flux:select.option>
                                    @else
                                        <flux:select.option value="">
                                            Selecciona un grado
                                        </flux:select.option>

                                        @forelse ($grados as $grado)
                                            <flux:select.option value="{{ $grado->id }}">
                                                {{ $grado->nombre }}° grado
                                            </flux:select.option>
                                        @empty
                                            <flux:select.option value="" disabled>
                                                No hay grados disponibles
                                            </flux:select.option>
                                        @endforelse
                                    @endif
                                </flux:select>
                            @else
                                <div
                                    class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-500/20 dark:bg-sky-500/10">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300">
                                            <svg class="size-5" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                            </svg>
                                        </div>

                                        <div>
                                            <p class="text-sm font-black text-sky-800 dark:text-sky-200">
                                                Grado no requerido
                                            </p>

                                            <p class="mt-1 text-xs leading-5 text-sky-700 dark:text-sky-300">
                                                Bachillerato se organiza mediante semestres,
                                                por lo que el grado quedará vacío.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Semestre o aviso --}}
                            @if ($esBachillerato)
                                <flux:select wire:model="semestre_id" label="Semestre"
                                    description="Periodo actual del grupo de Bachillerato.">
                                    <flux:select.option value="">
                                        Selecciona un semestre
                                    </flux:select.option>

                                    @forelse ($semestres as $semestre)
                                        <flux:select.option value="{{ $semestre->id }}">
                                            {{ $semestre->numero }}° semestre
                                        </flux:select.option>
                                    @empty
                                        <flux:select.option value="" disabled>
                                            No hay semestres registrados
                                        </flux:select.option>
                                    @endforelse
                                </flux:select>
                            @else
                                <div
                                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                                            <svg class="size-5" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6.75 3v2.25M17.25 3v2.25M3.75 9.75h16.5M5.25 5.25h13.5A1.5 1.5 0 0 1 20.25 6.75v12a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-12a1.5 1.5 0 0 1 1.5-1.5Z" />
                                            </svg>
                                        </div>

                                        <div>
                                            <p class="text-sm font-black text-slate-700 dark:text-slate-200">
                                                Semestre no aplicable
                                            </p>

                                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                                Este campo solamente se utiliza para
                                                grupos de Bachillerato.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- Aviso de integridad --}}
                    <div
                        class="flex items-start gap-3 rounded-2xl border border-[#006492]/15 bg-[#006492]/5 p-4 dark:bg-[#006492]/10">
                        <div
                            class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl bg-[#006492]/10 text-[#006492]">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-bold text-[#005477] dark:text-sky-200">
                                Información protegida
                            </p>

                            <p class="mt-0.5 text-xs leading-5 text-slate-600 dark:text-slate-400">
                                Los alumnos vinculados no se eliminan al modificar
                                la organización del grupo.
                            </p>
                        </div>
                    </div>
                </fieldset>
            </div>

            {{-- Pie --}}
            <footer
                class="flex shrink-0 flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/90 px-6 py-4 backdrop-blur dark:border-slate-800 dark:bg-slate-950/40 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div class="min-h-5">
                    <span wire:dirty wire:target="asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id"
                        class="inline-flex items-center gap-2 text-xs font-semibold text-amber-600 dark:text-amber-300">
                        <span class="size-2 rounded-full bg-amber-500"></span>
                        Hay cambios pendientes por guardar
                    </span>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" x-on:click="cerrar()" wire:loading.attr="disabled"
                        wire:target="actualizarGrupo">
                        Cancelar
                    </flux:button>

                    <flux:button type="submit" variant="primary" class="min-w-44" wire:loading.attr="disabled"
                        wire:target="actualizarGrupo">
                        <span wire:loading.remove wire:target="actualizarGrupo"
                            class="inline-flex items-center gap-2">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.5 12.75 10.5 18.75 19.5 5.25" />
                            </svg>

                            Guardar cambios
                        </span>

                        <span wire:loading wire:target="actualizarGrupo" class="inline-flex items-center gap-2">
                            <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>

                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                            </svg>

                            Guardando...
                        </span>
                    </flux:button>
                </div>
            </footer>
        </form>
    </div>

    <livewire:asignacion-grupo.crear-editar-asignacion-grupo />
</div>
