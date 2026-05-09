<div x-data="{ show: false, loading: false }" x-cloak x-trap.noscroll="show" x-show="show"
    @abrir-modal-editar.window="show = true; loading = true" @editar-cargado.window="loading = false"
    @cerrar-modal-editar.window="show = false; loading = false; $wire.cerrarModal()"
    @keydown.escape.window="show = false; $wire.cerrarModal()" class="fixed inset-0 z-50 flex items-center justify-center"
    aria-live="polite">
    <div class="absolute inset-0 bg-neutral-900/70 backdrop-blur-sm" x-show="show" x-transition.opacity
        @click.self="show = false; $wire.cerrarModal()"></div>

    <div class="relative mx-4 flex max-h-[85vh] w-[92vw] max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-neutral-900 dark:ring-white/10 sm:mx-6 sm:w-[88vw] md:w-[90vw]"
        role="dialog" aria-modal="true" aria-labelledby="titulo-modal-personal" x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2" wire:ignore.self>
        <div x-show="loading" x-transition.opacity
            class="absolute inset-0 z-20 flex items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-neutral-900/80">
            <div class="flex flex-col items-center gap-2">
                <svg class="h-6 w-6 animate-spin text-indigo-600 dark:text-indigo-400"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                    Cargando datos del personal...
                </p>
            </div>
        </div>

        <div class="h-1.5 w-full shrink-0 bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>

        <div
            class="sticky top-0 z-10 flex items-start justify-between gap-3 bg-white/95 px-5 pb-3 pt-4 backdrop-blur dark:bg-neutral-900/95 sm:px-6">
            <div class="min-w-0">
                <h2 id="titulo-modal-personal" class="text-xl font-bold text-neutral-900 dark:text-white sm:text-2xl">
                    Editar Asignación de Nivel
                </h2>

                <div class="mt-2 space-y-1.5">
                    <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                        Información actual:
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <flux:badge color="indigo" size="sm">
                            {{ $nombrePersona ?? '—' }}
                        </flux:badge>

                        <flux:badge color="violet" size="sm">
                            {{ $nombreNivel ?? '—' }}
                        </flux:badge>

                        @if (!$this->esBachillerato())
                            <flux:badge color="purple" size="sm">
                                Grado: {{ $nombreGrado ?? '—' }}
                            </flux:badge>

                            <flux:badge color="fuchsia" size="sm">
                                Grupo: {{ $nombreGrupo ?? '—' }}
                            </flux:badge>
                        @else
                            <flux:badge color="amber" size="sm">
                                Bachillerato sin grado y grupo
                            </flux:badge>
                        @endif
                    </div>
                </div>
            </div>

            <button @click="show = false; $wire.cerrarModal()" type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-zinc-400 dark:hover:bg-neutral-800 dark:hover:text-zinc-200"
                aria-label="Cerrar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <form wire:submit.prevent="actualizarPersonal" class="relative">
                <flux:field class="p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:select label="Persona" wire:model.live="persona_id">
                            <flux:select.option value="">
                                -- Selecciona una persona --
                            </flux:select.option>

                            @foreach ($personas as $p)
                                <flux:select.option value="{{ $p->id }}">
                                    {{ $p->titulo ?? '—' }} - {{ $p->nombre ?? '' }} {{ $p->apellido_paterno ?? '' }}
                                    {{ $p->apellido_materno ?? '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="nivel_id" label="Seleccionar Nivel">
                            <flux:select.option value="">
                                -- Seleccionar Nivel --
                            </flux:select.option>

                            @foreach ($niveles as $nivel)
                                <flux:select.option value="{{ $nivel->id }}">
                                    {{ $nivel->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                Función / Rol
                            </label>

                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse($rolesPersona as $r)
                                    @php
                                        $isActive = (int) $persona_role_id === (int) $r->id;
                                        $nombreRol = $r->rolePersona?->nombre ?? '—';
                                    @endphp

                                    <button type="button" wire:click="seleccionarRol({{ (int) $r->id }})"
                                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold
                                            {{ $isActive
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700' }}">
                                        {{ $nombreRol }}
                                    </button>
                                @empty
                                    <div
                                        class="w-full rounded-2xl border border-dashed border-zinc-300/70 bg-white/60 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                                        <p class="text-sm text-zinc-700 dark:text-zinc-200">
                                            Selecciona una persona para ver sus funciones disponibles.
                                        </p>
                                    </div>
                                @endforelse
                            </div>

                            @error('persona_role_id')
                                <p class="mt-2 text-xs text-rose-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        @if ($this->esBachillerato())
                            <div
                                class="md:col-span-2 rounded-2xl border border-dashed border-amber-300/80 bg-amber-50/80 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                                <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                    En Bachillerato no se permite seleccionar grado ni grupo. La asignación se guardará
                                    solo con persona, nivel y rol.
                                </p>
                            </div>
                        @elseif ($this->debeMostrarGradoGrupo())
                            <flux:select wire:model.live="grado_id" label="Seleccionar Grado"
                                :disabled="!$nivel_id || $grados->isEmpty()">
                                <flux:select.option value="">
                                    -- Seleccionar Grado --
                                </flux:select.option>

                                @foreach ($grados as $grado)
                                    <flux:select.option value="{{ $grado->id }}">
                                        {{ $grado->nombre }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model.live="grupo_id" label="Seleccionar Grupo"
                                :disabled="!$grado_id || $grupos->isEmpty()">
                                <flux:select.option value="">
                                    -- Seleccionar Grupo --
                                </flux:select.option>

                                @foreach ($grupos as $grupo)
                                    <flux:select.option value="{{ $grupo->id }}">
                                        {{ $this->nombreGrupo($grupo) }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <div
                                class="md:col-span-2 rounded-2xl border border-dashed border-zinc-300/70 bg-white/60 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                                <p class="text-sm text-zinc-700 dark:text-zinc-200">
                                    Este rol no permite o no requiere grado/grupo. La asignación se guardará solo con
                                    nivel.
                                </p>
                            </div>
                        @endif

                        @error('grado_id')
                            <p class="md:col-span-2 -mt-2 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror

                        @error('grupo_id')
                            <p class="md:col-span-2 -mt-2 text-xs text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror

                        @if ($this->esBachillerato())
                            <div
                                class="md:col-span-2 rounded-2xl border border-dashed border-sky-300/80 bg-sky-50/80 p-4 dark:border-sky-800 dark:bg-sky-900/20">
                                <p class="text-sm font-semibold text-sky-800 dark:text-sky-200">
                                    En Bachillerato no se captura Ingreso SEG ni Ingreso SEP.
                                </p>
                            </div>

                            <flux:input type="date" wire:model="ingreso_ct" label="Fecha de Ingreso C.T." />
                        @elseif (!$this->esSecundaria())
                            <flux:input type="date" wire:model="ingreso_seg" label="Fecha de Ingreso SEG" />
                            <flux:input type="date" wire:model="ingreso_sep" label="Fecha de Ingreso SEP" />
                            <flux:input type="date" wire:model="ingreso_ct" label="Fecha de Ingreso C.T." />
                        @else
                            <div
                                class="md:col-span-2 rounded-2xl border border-dashed border-amber-300/80 bg-amber-50/80 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                                <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                    En Secundaria las fechas se mantienen desde la cabecera existente.
                                </p>
                            </div>
                        @endif
                    </div>

                    <div
                        class="mt-6 flex flex-col-reverse items-stretch justify-end gap-2 sm:flex-row sm:items-center">
                        <button @click="show = false; $wire.cerrarModal()" type="button"
                            class="inline-flex justify-center rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700">
                            Cancelar
                        </button>

                        <flux:button variant="primary" type="submit" class="w-full cursor-pointer sm:w-auto"
                            wire:loading.attr="disabled" wire:target="actualizarPersonal">
                            Guardar
                        </flux:button>
                    </div>
                </flux:field>

                <div wire:loading.flex wire:target="actualizarPersonal"
                    class="absolute inset-0 z-20 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                    <div
                        class="flex items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-xl ring-1 ring-slate-200 dark:bg-neutral-900 dark:ring-neutral-700">
                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent">
                        </div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Guardando cambios...
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
