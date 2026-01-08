<div class="w-full mx-auto p-4 sm:p-6">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
            Crear ciclo escolar
        </h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
            Captura manualmente los años (ej. 2025 – 2026).
        </p>
    </div>

    {{-- Card --}}
    <div
        class="relative overflow-hidden rounded-2xl border border-zinc-200/70 dark:border-zinc-800 bg-white dark:bg-zinc-950 shadow-sm">
        <div class="h-1.5 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

        {{-- Loader overlay --}}
        <div wire:loading.flex wire:target="guardar"
            class="absolute inset-0 z-10 items-center justify-center bg-white/60 dark:bg-zinc-950/60 backdrop-blur-sm">
            <div
                class="flex items-center gap-3 rounded-2xl border border-zinc-200/70 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/60 px-4 py-3 shadow-sm">
                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="text-sm text-zinc-700 dark:text-zinc-200">Guardando…</span>
            </div>
        </div>

        <div class="p-5 sm:p-7">
            {{-- Preview --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div class="flex items-center gap-3">
                    <div
                        class="h-10 w-10 rounded-2xl bg-gradient-to-br from-sky-500/15 via-blue-600/15 to-indigo-600/15 border border-zinc-200/60 dark:border-zinc-800 flex items-center justify-center">
                        <svg class="h-5 w-5 text-zinc-800 dark:text-zinc-100" viewBox="0 0 24 24" fill="none">
                            <path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" />
                            <path d="M6 7h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V9a2 2 0 012-2Z"
                                stroke="currentColor" stroke-width="2" />
                            <path d="M8 11h8M8 15h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            Vista previa del ciclo
                        </div>
                        <div class="text-xs text-zinc-600 dark:text-zinc-300">
                            {{ $inicio_anio ?: '----' }} — {{ $fin_anio ?: '----' }}
                        </div>
                    </div>
                </div>

                <div
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs
                            border border-zinc-200/70 dark:border-zinc-800
                            bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Formato recomendado: 4 dígitos
                </div>
            </div>

            <form wire:submit.prevent="guardar" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    {{-- Inicio --}}
                    <div>
                        <flux:field>
                            <flux:label>Año de inicio</flux:label>

                            <div class="mt-2">
                                <flux:input type="number" inputmode="numeric" min="2000"
                                    max="{{ now()->addYears(5)->format('Y') }}" step="1" placeholder="Ej. 2025"
                                    wire:model="inicio_anio" />
                            </div>

                            <div class="mt-2 text-xs text-zinc-600 dark:text-zinc-300">
                                Escribe el año con 4 dígitos.
                            </div>

                            <flux:error name="inicio_anio" />
                        </flux:field>
                    </div>

                    {{-- Fin --}}
                    <div>
                        <flux:field>
                            <flux:label>Año de fin</flux:label>

                            <div class="mt-2">
                                <flux:input type="number" inputmode="numeric" min="2000"
                                    max="{{ now()->addYears(5)->format('Y') }}" step="1" placeholder="Ej. 2026"
                                    wire:model="fin_anio" />
                            </div>

                            <div class="mt-2 text-xs text-zinc-600 dark:text-zinc-300">
                                Debe ser igual o mayor al año de inicio.
                            </div>

                            <flux:error name="fin_anio" />
                        </flux:field>
                    </div>
                </div>

                {{-- Tip box --}}
                <div
                    class="rounded-2xl border border-zinc-200/70 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/40 p-4">
                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                        Reglas rápidas
                    </div>
                    <ul class="mt-2 text-sm text-zinc-700 dark:text-zinc-200 list-disc pl-5 space-y-1">
                        <li>Ambos años deben tener 4 dígitos.</li>
                        <li>El año final debe ser mayor o igual al año de inicio.</li>
                        <li>Evita guardar ciclos duplicados.</li>
                    </ul>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-end">
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-zinc-200/70 dark:border-zinc-800 px-4 py-2 text-sm
                               bg-white dark:bg-zinc-950 hover:bg-zinc-50 dark:hover:bg-zinc-900 transition"
                        wire:click="$reset" :disabled="false">
                        Limpiar
                    </button>

                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium text-white
                               bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600
                               hover:opacity-95 transition shadow-sm">
                        Guardar ciclo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
