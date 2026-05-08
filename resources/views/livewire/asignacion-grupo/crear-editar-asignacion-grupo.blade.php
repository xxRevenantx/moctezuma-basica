<div x-data="{ show: false }" x-on:abrir-modal-asignacion-grupo.window="show = true"
    x-on:cerrar-modal-asignacion-grupo.window="show = false" x-on:keydown.escape.window="show = false">
    <div x-show="show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog"
        aria-modal="true">
        {{-- Fondo --}}
        <div x-show="show" x-transition.opacity.duration.200ms class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"
            @click="show = false"></div>

        {{-- Modal --}}
        <div x-show="show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 blur-sm"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100 blur-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100 blur-0"
            x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 blur-sm"
            class="relative w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 dark:bg-neutral-900 dark:ring-white/10">
            {{-- Barra superior --}}
            <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

            <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 dark:border-neutral-800">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        Crear o editar grupos
                    </h2>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Administra los nombres de grupo como A, B, C o D.
                    </p>
                </div>

                <button type="button"
                    class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-neutral-800 dark:hover:text-white"
                    @click="show = false">
                    ✕
                </button>
            </div>

            <div class="max-h-[75vh] overflow-y-auto p-5">
                {{-- Formulario --}}
                <form wire:submit.prevent="guardarAsignacionGrupo" class="space-y-4">
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
                        <flux:input label="Nombre del grupo" placeholder="Ejemplo: A, B, C, D..." wire:model="nombre"
                            maxlength="20" />

                        <flux:error name="nombre" />

                        <div class="mt-4 flex items-center justify-end gap-2">
                            @if ($modoEdicion)
                                <button type="button" wire:click="resetFormulario"
                                    class="inline-flex rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-100 dark:hover:bg-neutral-700">
                                    Cancelar edición
                                </button>
                            @endif

                            <flux:button type="submit" variant="primary" class="btn-gradient"
                                spinner="guardarAsignacionGrupo">
                                {{ $modoEdicion ? 'Actualizar grupo' : 'Guardar grupo' }}
                            </flux:button>
                        </div>
                    </div>
                </form>

                {{-- Lista --}}
                <div
                    class="mt-5 rounded-2xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="border-b border-slate-200 px-4 py-3 dark:border-neutral-800">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                            Grupos registrados
                        </h3>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @forelse ($asignacionGrupos as $grupo)
                            <div class="flex items-center justify-between gap-3 px-4 py-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                        {{ $grupo->nombre }}
                                    </p>

                                    <p class="text-xs text-slate-400">
                                        ID: {{ $grupo->id }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="editarAsignacionGrupo({{ $grupo->id }})"
                                        class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/20">
                                        Editar
                                    </button>

                                    <button type="button" wire:click="eliminarAsignacionGrupo({{ $grupo->id }})"
                                        wire:confirm="¿Seguro que deseas eliminar este grupo?"
                                        class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20">
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    No hay grupos registrados.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Loader local --}}
            <div wire:loading.flex wire:target="guardarAsignacionGrupo,editarAsignacionGrupo,eliminarAsignacionGrupo"
                class="absolute inset-0 z-20 items-center justify-center bg-white/60 backdrop-blur-sm dark:bg-neutral-900/60">
                <div
                    class="flex items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-xl ring-1 ring-slate-200 dark:bg-neutral-900 dark:ring-neutral-700">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>

                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Procesando...
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
