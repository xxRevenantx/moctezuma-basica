<div x-data="{ open: true }" class="my-4">
    <button type="button" @click="open = !open" :aria-expanded="open"
        class="group inline-flex items-center gap-2 rounded-2xl px-4 py-2.5
               bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow
               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400
               dark:focus:ring-offset-neutral-900">
        <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-white/15">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 6v12m6-6H6" />
            </svg>
        </span>
        <span class="font-medium">Nuevo Personal por Nivel</span>
        <span class="ml-1 transition-transform duration-200" :class="open ? 'rotate-180' : 'rotate-0'">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 15.5l-6-6h12l-6 6z" />
            </svg>
        </span>
    </button>

    <div x-show="open" x-cloak class="relative mt-4">
        <form wire:submit.prevent="asignarPersonalNivel">
            <div
                class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-lg overflow-hidden">
                <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>

                <div class="p-5 sm:p-6 lg:p-8">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 grid place-items-center">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M12 6v12m6-6H6" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Asignación</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Grado y Grupo están disponibles para todos los roles, pero son opcionales.
                                <span class="font-semibold">Si eliges uno, el otro también.</span>
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div x-data="{
                            abierto: false,
                            cerrar() {
                                setTimeout(() => {
                                    this.abierto = false;
                                }, 150);
                            }
                        }" class="relative">

                            <flux:field>
                                <flux:label>Seleccionar Personal</flux:label>

                                <div class="relative">
                                    <flux:input wire:model.live.debounce.300ms="buscar_persona" @focus="abierto = true"
                                        @click="abierto = true" placeholder="Buscar persona por nombre o apellidos..."
                                        autocomplete="off" />

                                    @if ($persona_id)
                                        <button type="button" wire:click="limpiarPersona" @click="abierto = true"
                                            class="absolute right-3 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10"
                                            title="Quitar selección">
                                            <span class="text-sm font-black">×</span>
                                        </button>
                                    @endif
                                </div>

                                <flux:error name="persona_id" />
                            </flux:field>

                            <div x-show="abierto" x-cloak @click.outside="abierto = false"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1 scale-[0.98]"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
                                class="absolute z-50 mt-2 max-h-80 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl shadow-slate-900/10 dark:border-white/10 dark:bg-neutral-950">

                                @forelse ($this->personalFiltrado as $p)
                                    @php
                                        $nombreCompleto = trim(
                                            ($p->titulo ?? '') .
                                                ' ' .
                                                ($p->nombre ?? '') .
                                                ' ' .
                                                ($p->apellido_paterno ?? '') .
                                                ' ' .
                                                ($p->apellido_materno ?? ''),
                                        );

                                        $iniciales =
                                            mb_substr($p->nombre ?? 'P', 0, 1) .
                                            mb_substr($p->apellido_paterno ?? '', 0, 1);
                                    @endphp

                                    <button type="button" wire:click="seleccionarPersona({{ $p->id }})"
                                        @click="abierto = false"
                                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-indigo-50 dark:hover:bg-indigo-500/10
                    {{ (int) $persona_id === (int) $p->id ? 'bg-indigo-50 dark:bg-indigo-500/10' : '' }}">

                                        <span
                                            class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-black uppercase text-white shadow-sm">
                                            {{ $iniciales }}
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span
                                                class="block truncate text-sm font-bold text-slate-800 dark:text-slate-100">
                                                {{ $nombreCompleto }}
                                            </span>

                                            <span class="mt-0.5 block text-xs font-medium text-slate-400">
                                                ID: {{ $p->id }}
                                            </span>
                                        </span>

                                        @if ((int) $persona_id === (int) $p->id)
                                            <span
                                                class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                                Seleccionado
                                            </span>
                                        @endif
                                    </button>
                                @empty
                                    <div
                                        class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center dark:border-white/10">
                                        <p class="text-sm font-bold text-slate-600 dark:text-slate-300">
                                            No se encontraron personas
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Intenta buscar por nombre o apellido.
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <flux:select wire:model.live="nivel_id" label="Seleccionar Nivel" required>
                            <flux:select.option value="">-- Seleccionar Nivel --</flux:select.option>
                            @foreach ($niveles as $nivel)
                                <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="grado_id" label="Seleccionar Grado"
                            :disabled="!$nivel_id || $grados->isEmpty()">
                            <flux:select.option value="">-- Seleccionar Grado (opcional) --</flux:select.option>
                            @foreach ($grados as $grado)
                                <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="grupo_id" label="Seleccionar Grupo"
                            :disabled="!$grado_id || $grupos->isEmpty() || (int) $nivel_id === 4">
                            <flux:select.option value="">
                                {{ (int) $nivel_id === 4 ? '-- No aplica para Bachillerato --' : '-- Seleccionar Grupo (opcional) --' }}
                            </flux:select.option>

                            @foreach ($grupos as $grupo)
                                <flux:select.option value="{{ $grupo->id }}">{{ $grupo->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="mt-5">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Función / Rol</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Selecciona una función.</p>
                            </div>

                            @if ($persona_role_id)
                                <button type="button" wire:click="$set('persona_role_id', null)"
                                    class="text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                    Limpiar
                                </button>
                            @endif
                        </div>

                        <input type="hidden" wire:model="persona_role_id">

                        <div
                            class="mt-3 rounded-2xl border border-gray-200 dark:border-neutral-800 bg-gray-50/60 dark:bg-neutral-800/40 p-4">
                            @if (!$persona_id)
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Primero selecciona una persona para ver sus funciones.
                                </p>
                            @elseif($rolesPersona->isEmpty())
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Esta persona no tiene roles asignados.
                                </p>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($rolesPersona as $rp)
                                        @php
                                            $selected = (int) $persona_role_id === (int) $rp->id;
                                            $label = $rp->rolePersona?->nombre ?? '—';
                                        @endphp

                                        <button type="button" wire:click="seleccionarRol({{ $rp->id }})"
                                            class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold
                                                   ring-1 transition
                                                   {{ $selected
                                                       ? 'bg-indigo-600 text-white ring-indigo-600 shadow-sm'
                                                       : 'bg-white dark:bg-neutral-900 text-gray-700 dark:text-gray-200 ring-gray-200 dark:ring-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-800' }}">
                                            <span
                                                class="h-1.5 w-1.5 rounded-full {{ $selected ? 'bg-white' : 'bg-indigo-500' }}"></span>
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            @error('persona_role_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror

                            @error('grado_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror

                            @error('grupo_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Fechas de ingreso</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    En secundaria, si el personal ya tiene registro, estas fechas quedan bloqueadas.
                                </p>
                            </div>

                            @if ($bloquearFechasSecundaria)
                                <span
                                    class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold
                       bg-amber-50 text-amber-700 ring-1 ring-amber-200
                       dark:bg-amber-900/20 dark:text-amber-200 dark:ring-amber-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    Fechas bloqueadas (Secundaria)
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:input type="date" wire:model="ingreso_seg" label="Fecha de Ingreso SEG"
                                :disabled="$bloquearFechasSecundaria" />
                            <flux:input type="date" wire:model="ingreso_sep" label="Fecha de Ingreso SEP"
                                :disabled="$bloquearFechasSecundaria" />
                            <flux:input type="date" wire:model="ingreso_ct" label="Fecha de Ingreso C.T."
                                :disabled="$bloquearFechasSecundaria" />
                        </div>

                        @if ($bloquearFechasSecundaria)
                            <div
                                class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800
                    dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                                Este personal ya tiene registro en <b>Secundaria</b>. Para evitar inconsistencias, las
                                fechas solo se editan desde la cabecera existente.
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 border-t border-gray-200 dark:border-neutral-800"></div>

                    <div
                        class="mt-6 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2">
                        <button type="button" @click="open = false"
                            class="inline-flex justify-center rounded-xl px-4 py-2.5 border border-neutral-200 dark:border-neutral-700
                                   bg-white dark:bg-neutral-900 text-neutral-700 dark:text-neutral-100
                                   hover:bg-neutral-50 dark:hover:bg-neutral-800
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-300 dark:focus:ring-offset-neutral-900">
                            Cancelar
                        </button>

                        <flux:button variant="primary" type="submit"
                            class="w-full sm:w-auto cursor-pointer btn-gradient" wire:loading.attr="disabled"
                            wire:target="asignarPersonalNivel">
                            Guardar
                        </flux:button>
                    </div>
                </div>

                <div wire:loading.delay wire:target="asignarPersonalNivel"
                    class="pointer-events-none absolute inset-0 grid place-items-center bg-white/60 dark:bg-neutral-900/60">
                    <div
                        class="flex items-center gap-3 rounded-xl bg-white/90 dark:bg-neutral-900/90 px-4 py-3 ring-1 ring-gray-200 dark:ring-neutral-700 shadow">
                        <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Guardando…</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
