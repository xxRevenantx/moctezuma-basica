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

                    {{-- Persona + Nivel + Grado + Grupo --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <flux:select wire:model.live="persona_id" label="Seleccionar Personal">
                            <flux:select.option value="">-- Seleccionar Personal --</flux:select.option>
                            @foreach ($personas as $p)
                                <flux:select.option value="{{ $p->id }}">
                                    {{ trim($p->nombre . ' ' . $p->apellido_paterno . ' ' . $p->apellido_materno) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

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
                            :disabled="!$grado_id || $grupos->isEmpty()">
                            <flux:select.option value="">-- Seleccionar Grupo (opcional) --</flux:select.option>
                            @foreach ($grupos as $grupo)
                                <flux:select.option value="{{ $grupo->id }}">{{ $grupo->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Chips de roles --}}
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

                    {{-- Fechas --}}
                    <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:input type="date" wire:model="ingreso_seg" label="Fecha de Ingreso SEG" />
                        <flux:input type="date" wire:model="ingreso_sep" label="Fecha de Ingreso SEP" />
                        <flux:input type="date" wire:model="ingreso_ct" label="Fecha de Ingreso C.T." />
                    </div>

                    <div class="mt-6 border-t border-gray-200 dark:border-neutral-800"></div>

                    <div class="mt-6 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2">
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

                <!-- Loader overlay -->
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
