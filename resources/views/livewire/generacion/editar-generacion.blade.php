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
}" x-cloak x-show="show" x-on:abrir-modal-editar.window="abrir()"
    x-on:editar-cargado.window="cargado()"
    x-on:cerrar-modal-editar.window="
        show = false;
        loading = false;
    "
    x-on:keydown.escape.window="cerrar()" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog"
    aria-modal="true">
    {{-- Fondo --}}
    <div x-show="show" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" x-on:click="cerrar()"
        wire:loading.class="pointer-events-none" wire:target="actualizarGeneracion"></div>

    {{-- Modal --}}
    <form wire:submit="actualizarGeneracion" x-show="show" x-transition:enter="transition duration-250 ease-out"
        x-transition:enter-start="translate-y-5 scale-95 opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
        x-transition:leave-end="translate-y-4 scale-95 opacity-0"
        class="relative max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
        {{-- Barra de guardado --}}
        <div wire:loading wire:target="actualizarGeneracion"
            class="absolute inset-x-0 top-0 z-40 h-1 overflow-hidden bg-slate-100 dark:bg-slate-800">
            <div class="h-full w-full animate-pulse bg-gradient-to-r from-[#006492] via-[#88AC2E] to-[#006492]"></div>
        </div>

        {{-- Loader del guardado --}}
        <div wire:loading wire:target="actualizarGeneracion"
            class="absolute inset-0 z-30 flex items-center justify-center bg-white/80 backdrop-blur-[2px] dark:bg-slate-900/85">
            <div
                class="rounded-3xl border border-slate-200 bg-white px-8 py-6 text-center shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div
                    class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-[#006492]/10 text-[#006492]">
                    <svg class="size-7 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>

                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z">
                        </path>
                    </svg>
                </div>

                <p class="mt-4 font-black text-slate-900 dark:text-white">
                    Guardando generación
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Estamos actualizando la información.
                </p>
            </div>
        </div>

        {{-- Encabezado --}}
        <header
            class="relative overflow-hidden border-b border-slate-100 bg-gradient-to-r from-[#006492]/10 via-white to-[#88AC2E]/10 px-6 py-5 dark:border-slate-800 dark:via-slate-900 sm:px-7">
            <div class="absolute -right-12 -top-16 size-40 rounded-full bg-[#88AC2E]/10 blur-2xl"></div>

            <div class="relative flex items-start gap-4">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-lg shadow-[#006492]/20">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14.25v4.125c0 1.036-.84 1.875-1.875 1.875H5.625A1.875 1.875 0 0 1 3.75 18.375V7.875C3.75 6.839 4.59 6 5.625 6H9.75" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-black text-slate-950 dark:text-white sm:text-2xl">
                            Editar generación
                        </h2>

                        <span x-show="!loading"
                            class="rounded-full bg-[#88AC2E]/15 px-3 py-1 text-xs font-bold text-[#628314] dark:text-[#b6dd5f]">
                            {{ $nombre !== '' ? $nombre : 'Generación' }}
                        </span>
                    </div>

                    <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Actualiza los datos académicos y el periodo oficial.
                        Los alumnos permanecerán vinculados.
                    </p>
                </div>

                <button type="button" x-on:click="cerrar()" wire:loading.attr="disabled"
                    wire:target="actualizarGeneracion"
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-white hover:text-slate-700 hover:shadow-sm dark:hover:bg-slate-800 dark:hover:text-white">
                    <span class="sr-only">Cerrar</span>
                    <span class="text-2xl leading-none">&times;</span>
                </button>
            </div>
        </header>

        {{-- Carga inicial --}}
        <div x-show="loading" class="flex min-h-[430px] items-center justify-center px-6 py-12">
            <div class="w-full max-w-md text-center">
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
                    Cargando información
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Consultando los datos de la generación seleccionada...
                </p>

                <div class="mt-8 space-y-3">
                    <div class="h-11 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800"></div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="h-11 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800"></div>

                        <div class="h-11 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800"></div>
                    </div>

                    <div class="h-11 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800"></div>
                </div>
            </div>
        </div>

        {{-- Formulario --}}
        <div x-show="!loading" class="max-h-[calc(92vh-170px)] overflow-y-auto">
            <fieldset wire:loading.attr="disabled" wire:target="actualizarGeneracion"
                class="space-y-6 px-6 py-6 sm:px-7">
                {{-- Aviso --}}
                <div
                    class="flex items-start gap-3 rounded-2xl border border-[#006492]/15 bg-[#006492]/5 p-4 dark:bg-[#006492]/10">
                    <div
                        class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl bg-[#006492]/10 text-[#006492]">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                    </div>

                    <div>
                        <p class="text-sm font-bold text-[#005477] dark:text-sky-200">
                            Vinculación protegida
                        </p>

                        <p class="mt-0.5 text-xs leading-5 text-slate-600 dark:text-slate-400">
                            Editar estos datos no elimina inscripciones,
                            calificaciones ni documentos asociados.
                        </p>
                    </div>
                </div>

                {{-- Datos generales --}}
                <section>
                    <div class="mb-4 flex items-center gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 14.25 3.75 9.75 12 5.25l8.25 4.5L12 14.25Zm0 0v5.25m-6-7.5v4.125A2.625 2.625 0 0 0 8.625 18.75h6.75A2.625 2.625 0 0 0 18 16.125V12" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">
                                Datos generales
                            </h3>

                            <p class="text-xs text-slate-500">
                                Identificación y nivel educativo.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:select wire:model.live="nivel_id" label="Nivel educativo" :disabled="$tieneGrupos"
                            description="Nivel al que pertenece la generación.">
                            <flux:select.option value="">
                                Selecciona un nivel
                            </flux:select.option>

                            @foreach ($niveles as $nivel)
                                <flux:select.option value="{{ $nivel->id }}">
                                    {{ $nivel->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:input wire:model="nombre" label="Nombre de la generación"
                            placeholder="Ejemplo: 2025-2028" icon="tag" readonly />

                        <flux:input wire:model.live="anio_ingreso" type="number" min="1900" max="2200"
                            step="1" label="Año de ingreso" placeholder="2025" icon="calendar-days" :disabled="$tieneGrupos" />

                        <flux:input wire:model="anio_egreso" type="number" min="1900" max="2200"
                            step="1" label="Año de egreso" placeholder="2028" icon="calendar-days" readonly />
                    </div>

                    @if ($detalleDuracion || $tieneGrupos)
                        <div class="mt-4 rounded-xl border px-4 py-3 text-sm
                            {{ $tieneGrupos
                                ? 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200'
                                : 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-200' }}">
                            @if ($detalleDuracion)
                                <span class="font-semibold">{{ $detalleDuracion }}</span>
                            @endif
                            @if ($tieneGrupos)
                                Esta generación ya tiene grupos relacionados; el nivel y los años están protegidos para no alterar sus asignaciones.
                            @endif
                        </div>
                    @endif
                </section>

                <div class="h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent dark:via-slate-700">
                </div>

                {{-- Ciclos escolares --}}
                <section>
                    <div class="mb-4 flex items-center gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-xl bg-[#88AC2E]/15 text-[#668318] dark:text-[#b6dd5f]">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 6.75h7.5M8.25 10.5h7.5m-7.5 3.75h3M6.75 3.75h10.5A2.25 2.25 0 0 1 19.5 6v12A2.25 2.25 0 0 1 17.25 20.25H6.75A2.25 2.25 0 0 1 4.5 18V6a2.25 2.25 0 0 1 2.25-2.25Z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">
                                Vinculación con ciclos escolares
                            </h3>

                            <p class="text-xs text-slate-500">
                                Relaciona el inicio y la conclusión académica.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:select wire:model="ciclo_escolar_inicio_id" label="Ciclo escolar inicial" disabled>
                            <flux:select.option value="">
                                Sin asignar
                            </flux:select.option>

                            @foreach ($ciclosEscolares as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="ciclo_escolar_fin_id" label="Ciclo escolar final" disabled>
                            <flux:select.option value="">
                                Sin asignar
                            </flux:select.option>

                            @foreach ($ciclosEscolares as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </section>

                <div class="h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent dark:via-slate-700">
                </div>

                {{-- Fechas --}}
                <section>
                    <div class="mb-4 flex items-center gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3.75 9.75h16.5M5.25 5.25h13.5A1.5 1.5 0 0 1 20.25 6.75v12a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-12a1.5 1.5 0 0 1 1.5-1.5Z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">
                                Periodo oficial
                            </h3>

                            <p class="text-xs text-slate-500">
                                Fechas completas de vigencia de la generación.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:input wire:model="fecha_inicio" type="date" label="Fecha de inicio" readonly />

                        <flux:input wire:model="fecha_termino" type="date" label="Fecha de término" readonly />
                    </div>
                </section>
            </fieldset>

            {{-- Pie del modal --}}
            <footer
                class="sticky bottom-0 flex flex-col-reverse gap-3 border-t border-slate-100 bg-white/95 px-6 py-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div class="min-h-5">
                    <span wire:dirty
                        wire:target="nivel_id,nombre,anio_ingreso,anio_egreso,ciclo_escolar_inicio_id,ciclo_escolar_fin_id,fecha_inicio,fecha_termino"
                        class="inline-flex items-center gap-2 text-xs font-semibold text-amber-600 dark:text-amber-300">
                        <span class="size-2 rounded-full bg-amber-500"></span>
                        Hay cambios pendientes por guardar
                    </span>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" x-on:click="cerrar()" wire:loading.attr="disabled"
                        wire:target="actualizarGeneracion">
                        Cancelar
                    </flux:button>

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                        wire:target="actualizarGeneracion" class="min-w-44">
                        <span wire:loading.remove wire:target="actualizarGeneracion"
                            class="inline-flex items-center gap-2">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.5 12.75 10.5 18.75 19.5 5.25" />
                            </svg>

                            Guardar cambios
                        </span>

                        <span wire:loading wire:target="actualizarGeneracion" class="inline-flex items-center gap-2">
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
        </div>
    </form>
</div>
