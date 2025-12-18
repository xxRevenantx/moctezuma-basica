<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Asignación de personal</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Selecciona un integrante y asígnale un rol dentro del sistema.
        </p>
    </div>

    <!-- Card principal -->
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow">
        <!-- Accent -->
        <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

        <form wire:submit.prevent="asignarRol" class="p-5 sm:p-6 lg:p-7">
            {{-- =========================
                 SOLO Personal + Rol (con overlay local)
                 ========================= --}}
            <div class="relative">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                    <!-- Selector Personal -->
                    <div class="lg:col-span-6">
                        <div
                            class="rounded-2xl border border-gray-200 dark:border-neutral-800 bg-gray-50/60 dark:bg-neutral-800/40 p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="grid place-items-center h-11 w-11 rounded-xl bg-white dark:bg-neutral-900 ring-1 ring-black/5 dark:ring-white/10">
                                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7a4 4 0 108 0 4 4 0 00-8 0z" />
                                        </svg>
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Personal</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                            Elige a quién vas a asignar.
                                        </p>
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    @if ($persona_id)
                                        <span
                                            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold
                                            bg-emerald-50 text-emerald-700 dark:bg-emerald-900/25 dark:text-emerald-200
                                            ring-1 ring-emerald-600/15">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            Seleccionado
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold
                                            bg-white/80 dark:bg-neutral-900/60 text-gray-700 dark:text-gray-200
                                            ring-1 ring-black/5 dark:ring-white/10">
                                            <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                                            Pendiente
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4">
                                <flux:field>
                                    <flux:label class="sr-only">Seleccionar personal</flux:label>

                                    <flux:select wire:model.live="persona_id" placeholder="Selecciona personal…">
                                        <flux:select.option value="">-- Ninguno --</flux:select.option>
                                        @foreach ($personal as $p)
                                            <flux:select.option value="{{ $p->id }}"> {{ $p->nombre }}
                                                {{ $p->apellido_paterno }}
                                                {{ $p->apellido_materno }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="persona_id" />
                                </flux:field>

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <p class="text-xs text-gray-600 dark:text-gray-300">
                                        Tip: escribe para buscar más rápido.
                                    </p>

                                    @if ($persona_id)
                                        <div
                                            class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-neutral-900 px-3 py-2
                                            ring-1 ring-black/5 dark:ring-white/10">
                                            <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span
                                                class="text-xs font-semibold text-gray-800 dark:text-gray-100">Listo</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selector Rol -->
                    <div class="lg:col-span-6">
                        <div
                            class="rounded-2xl border border-gray-200 dark:border-neutral-800 bg-gray-50/60 dark:bg-neutral-800/40 p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="grid place-items-center h-11 w-11 rounded-xl bg-white dark:bg-neutral-900 ring-1 ring-black/5 dark:ring-white/10">
                                        <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21v-1a7 7 0 00-14 0v1" />
                                        </svg>
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Rol</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                            Define permisos y funciones.
                                        </p>
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    @if ($role_persona_id)
                                        <span
                                            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold
                                            bg-violet-50 text-violet-700 dark:bg-violet-900/25 dark:text-violet-200
                                            ring-1 ring-violet-600/15">
                                            <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                                            Elegido
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold
                                            bg-white/80 dark:bg-neutral-900/60 text-gray-700 dark:text-gray-200
                                            ring-1 ring-black/5 dark:ring-white/10">
                                            <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                                            Pendiente
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4">
                                <flux:field>
                                    <flux:label class="sr-only">Seleccionar rol</flux:label>

                                    <flux:select wire:model.live="role_persona_id" placeholder="Selecciona un rol…">
                                        <flux:select.option value="">-- Ninguno --</flux:select.option>
                                        @foreach ($personaRoles as $r)
                                            <flux:select.option value="{{ $r->id }}"> {{ $r->nombre }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="role_persona_id" />
                                </flux:field>

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <p class="text-xs text-gray-600 dark:text-gray-300">
                                        Consejo: asigna el rol más específico posible.
                                    </p>

                                    @if ($role_persona_id)
                                        <div
                                            class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-neutral-900 px-3 py-2
                                            ring-1 ring-black/5 dark:ring-white/10">
                                            <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span
                                                class="text-xs font-semibold text-gray-800 dark:text-gray-100">Listo</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Loader overlay SOLO para esta sección -->
                <div wire:loading.delay wire:target="asignarRol"
                    class="absolute inset-0 z-20 grid place-items-center rounded-2xl
                           bg-white/60 dark:bg-neutral-900/60 backdrop-blur-sm">
                    <div
                        class="flex items-center gap-3 rounded-2xl bg-white/90 dark:bg-neutral-900/90 px-5 py-4 ring-1 ring-gray-200 dark:ring-neutral-700 shadow-xl">
                        <svg class="h-5 w-5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24"
                            fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Asignando…</span>
                    </div>
                </div>
            </div>

            <!-- Resumen + Acciones -->
            <div
                class="mt-5 rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 sm:p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold
                            bg-blue-50 text-blue-700 dark:bg-blue-900/25 dark:text-blue-200 ring-1 ring-blue-600/15">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" />
                            </svg>
                            Resumen
                        </span>

                        <p class="text-sm text-gray-700 dark:text-gray-200">
                            @if ($persona_id && $role_persona_id)
                                Todo listo para asignar.
                            @else
                                Selecciona personal y rol para continuar.
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="resetSeleccion"
                            class="inline-flex justify-center rounded-xl px-4 py-2.5 border border-neutral-200 dark:border-neutral-700
                                   bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-100
                                   hover:bg-neutral-50 dark:hover:bg-neutral-700 transition
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-300 dark:focus:ring-offset-neutral-900">
                            Limpiar
                        </button>

                        <flux:button variant="primary" type="submit" class="cursor-pointer btn-gradient"
                            wire:loading.attr="disabled" wire:target="asignarRol"
                            :disabled="!($persona_id && $role_persona_id)">
                            Asignar
                        </flux:button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
