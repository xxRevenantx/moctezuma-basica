<div x-data="{
    abierto: localStorage.getItem('collapse_credenciales_profesor') ?? 'credenciales_profesor',

    cambiar(seccion) {
        this.abierto = this.abierto === seccion ? null : seccion;

        if (this.abierto) {
            localStorage.setItem('collapse_credenciales_profesor', this.abierto);
        } else {
            localStorage.removeItem('collapse_credenciales_profesor');
        }
    },

    guardarScroll() {
        localStorage.setItem('scroll_credenciales_profesor', window.scrollY || 0);
    },

    restaurarScroll() {
        const posicion = localStorage.getItem('scroll_credenciales_profesor');

        if (posicion !== null) {
            requestAnimationFrame(() => {
                window.scrollTo(0, Number(posicion));
            });
        }
    }
}" x-init="document.addEventListener('livewire:init', () => {
    Livewire.hook('commit', ({ succeed }) => {
        guardarScroll();

        succeed(() => {
            setTimeout(() => restaurarScroll(), 30);
        });
    });
});" class="space-y-6">

    <section class="space-y-4">
        <article
            class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm transition-all duration-300 dark:border-neutral-800 dark:bg-neutral-900">

            <button type="button" x-on:click="cambiar('credenciales_profesor')"
                class="group flex w-full items-center justify-between gap-4 px-5 py-5 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/60 sm:px-6">

                <div class="flex items-center gap-4">
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white shadow-lg shadow-blue-500/20 transition group-hover:scale-105">
                        <flux:icon.identification class="h-5 w-5" />
                    </span>

                    <span>
                        <span class="block text-base font-black text-slate-900 dark:text-white">
                            Descargar credenciales de profesores
                        </span>

                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">
                            Busca personal docente, selecciónalo y descarga sus credenciales en PDF.
                        </span>
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        class="hidden rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950/30 dark:text-blue-300 dark:ring-blue-900/60 sm:inline-flex">
                        PDF
                    </span>

                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300"
                        x-bind:class="abierto === 'credenciales_profesor'
                            ?
                            'rotate-180 border-blue-200 text-blue-600 dark:border-blue-900 dark:text-blue-300' :
                            ''">
                        <flux:icon.chevron-down class="h-5 w-5" />
                    </span>
                </div>
            </button>

            <div x-cloak x-show="abierto === 'credenciales_profesor'"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                class="border-t border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/30 sm:p-6">

                <div
                    class="relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

                    <div wire:loading.delay.flex
                        wire:target="modo_descarga,buscar_persona,persona_individual_id,personas_seleccionadas,cct,vigencia,cargo,limpiarFiltros,limpiarSeleccion,seleccionarTodosVisibles,quitarPersonaSeleccionada"
                        class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                        <div
                            class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-lg dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                </path>
                            </svg>
                            Actualizando información...
                        </div>
                    </div>

                    <div class="h-1.5 w-full bg-gradient-to-r from-blue-600 via-indigo-600 to-slate-900"></div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                    Filtros de credencial
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Configura los datos generales y selecciona al personal que aparecerá en el PDF.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="limpiarSeleccion" x-on:click="guardarScroll()"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                    <flux:icon.x-mark class="h-4 w-4" />
                                    Limpiar selección
                                </button>

                                <button type="button" wire:click="limpiarFiltros" x-on:click="guardarScroll()"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                    <flux:icon.arrow-path class="h-4 w-4" />
                                    Limpiar filtros
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <flux:field>
                                <flux:label>Modo de descarga</flux:label>

                                <flux:select wire:model.live="modo_descarga" x-on:change="guardarScroll()">
                                    @foreach ($this->modosDescarga() as $valor => $texto)
                                        <flux:select.option value="{{ $valor }}">
                                            {{ $texto }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="modo_descarga" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Buscar profesor</flux:label>

                                <flux:input type="search" wire:model.live.debounce.400ms="buscar_persona"
                                    x-on:input="guardarScroll()" placeholder="Nombre, CURP, RFC o correo..." />
                            </flux:field>

                            <flux:field>
                                <flux:label>C.C.T.</flux:label>

                                <flux:input type="text" wire:model.live.debounce.400ms="cct"
                                    placeholder="Ej. 12PES0105U" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Vigencia</flux:label>

                                <flux:input type="text" wire:model.live.debounce.400ms="vigencia"
                                    placeholder="Ej. Ciclo escolar 2026 - 2027" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Cargo</flux:label>

                                <flux:input type="text" wire:model.live.debounce.400ms="cargo"
                                    placeholder="Ej. Profesor" />
                            </flux:field>

                            @if ($modo_descarga === 'individual')
                                <flux:field class="xl:col-span-2">
                                    <flux:label>Profesor individual</flux:label>

                                    <flux:select wire:model.live="persona_individual_id" x-on:change="guardarScroll()">
                                        <flux:select.option value="">
                                            Selecciona un profesor
                                        </flux:select.option>

                                        @foreach ($this->personas as $persona)
                                            <flux:select.option value="{{ $persona->id }}">
                                                {{ $this->nombrePersona($persona) }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="persona_individual_id" />
                                </flux:field>
                            @endif
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 dark:border-blue-900/40 dark:bg-blue-950/30 dark:text-blue-300">
                                Modo: {{ $this->textoModoDescarga }}
                            </span>

                            @if ($modo_descarga === 'seleccionados')
                                <span
                                    class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Seleccionados: {{ count($personas_seleccionadas) }}
                                </span>
                            @endif

                            @if ($this->puedeDescargar)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    <flux:icon.check-circle class="h-4 w-4" />
                                    Listo para descargar
                                </span>
                            @endif
                        </div>

                        @if ($modo_descarga === 'seleccionados')
                            <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-12">
                                <div
                                    class="overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900 xl:col-span-7">
                                    <div
                                        class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                                Resultados de búsqueda
                                            </h4>

                                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                Busca y marca profesores. La selección se conserva aunque cambies la
                                                búsqueda.
                                            </p>
                                        </div>

                                        <button type="button" wire:click="seleccionarTodosVisibles"
                                            x-on:click="guardarScroll()"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 dark:bg-white dark:text-slate-900">
                                            <flux:icon.check class="h-4 w-4" />
                                            Seleccionar visibles
                                        </button>
                                    </div>

                                    <div class="max-h-[430px] overflow-y-auto">
                                        <table
                                            class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                                            <thead class="sticky top-0 z-10 bg-slate-100 dark:bg-neutral-950">
                                                <tr>
                                                    <th class="w-12 px-4 py-3 text-left"></th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                        Profesor
                                                    </th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                        RFC
                                                    </th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                        Teléfono
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                                @forelse ($this->personas as $persona)
                                                    <tr wire:key="resultado-persona-{{ $persona->id }}"
                                                        class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/60">
                                                        <td class="px-4 py-3">
                                                            <input type="checkbox" value="{{ $persona->id }}"
                                                                wire:model.live="personas_seleccionadas"
                                                                x-on:change="guardarScroll()"
                                                                class="h-4 w-4 rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:border-neutral-700">
                                                        </td>

                                                        <td class="px-4 py-3">
                                                            <p class="font-black text-slate-900 dark:text-white">
                                                                {{ $this->nombrePersona($persona) }}
                                                            </p>

                                                            <p
                                                                class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                                {{ $persona->correo ?? 'Sin correo' }}
                                                            </p>
                                                        </td>

                                                        <td
                                                            class="px-4 py-3 font-bold text-slate-700 dark:text-slate-200">
                                                            {{ $persona->rfc ?? 'No especificado' }}
                                                        </td>

                                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                            {{ $persona->telefono_movil ?? ($persona->telefono_fijo ?? 'No especificado') }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="px-4 py-10 text-center">
                                                            <p class="font-black text-slate-700 dark:text-slate-200">
                                                                No hay profesores para mostrar.
                                                            </p>

                                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                                Escribe en el buscador para consultar personal.
                                                            </p>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div
                                    class="overflow-hidden rounded-[1.4rem] border border-blue-200 bg-blue-50/50 shadow-sm dark:border-blue-900/50 dark:bg-blue-950/10 xl:col-span-5">
                                    <div
                                        class="border-b border-blue-200 bg-gradient-to-r from-blue-600 via-indigo-600 to-slate-900 p-4 text-white dark:border-blue-900/50">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <h4 class="text-sm font-black">
                                                    Profesores agregados
                                                </h4>

                                                <p class="mt-1 text-xs font-semibold text-white/80">
                                                    {{ count($personas_seleccionadas) }} profesor(es) seleccionado(s).
                                                </p>
                                            </div>

                                            <button type="button" wire:click="limpiarSeleccion"
                                                x-on:click="guardarScroll()"
                                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-black text-white ring-1 ring-white/20 transition hover:bg-white/25">
                                                <flux:icon.x-mark class="h-4 w-4" />
                                                Limpiar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="max-h-[430px] overflow-y-auto">
                                        <table
                                            class="min-w-full divide-y divide-blue-100 text-sm dark:divide-neutral-800">
                                            <thead class="sticky top-0 z-10 bg-blue-50 dark:bg-neutral-950">
                                                <tr>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                                        Profesor
                                                    </th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                                        RFC
                                                    </th>
                                                    <th class="w-12 px-4 py-3 text-right"></th>
                                                </tr>
                                            </thead>

                                            <tbody
                                                class="divide-y divide-blue-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                                                @forelse ($this->personasSeleccionadasLista as $persona)
                                                    <tr wire:key="seleccionado-persona-{{ $persona->id }}"
                                                        class="transition hover:bg-blue-50/70 dark:hover:bg-neutral-800/60">
                                                        <td class="px-4 py-3">
                                                            <p class="font-black text-slate-900 dark:text-white">
                                                                {{ $this->nombrePersona($persona) }}
                                                            </p>

                                                            <p
                                                                class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                                {{ $persona->correo ?? 'Sin correo' }}
                                                            </p>
                                                        </td>

                                                        <td
                                                            class="px-4 py-3 font-bold text-slate-700 dark:text-slate-200">
                                                            {{ $persona->rfc ?? 'N/A' }}
                                                        </td>

                                                        <td class="px-4 py-3 text-right">
                                                            <button type="button"
                                                                wire:click="quitarPersonaSeleccionada({{ $persona->id }})"
                                                                x-on:click="guardarScroll()"
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
                                                                <flux:icon.trash class="h-4 w-4" />
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="px-4 py-10 text-center">
                                                            <div
                                                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                                                <flux:icon.user-plus class="h-5 w-5" />
                                                            </div>

                                                            <p
                                                                class="mt-3 font-black text-slate-700 dark:text-slate-200">
                                                                Todavía no hay profesores seleccionados.
                                                            </p>

                                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                                Marca profesores desde la tabla de resultados.
                                                            </p>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div
                            class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">
                                            Estado de descarga
                                        </p>

                                        @if ($this->puedeDescargar)
                                            <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">
                                                Ya puedes descargar las credenciales.
                                            </p>

                                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                Alcance seleccionado: {{ $this->textoModoDescarga }}.
                                            </p>
                                        @else
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                Completa la selección del personal para habilitar la descarga.
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if ($this->puedeDescargar)
                                    <a href="{{ $this->urlDescarga }}" target="_blank"
                                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-slate-900 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:shadow-xl">
                                        <flux:icon.document-arrow-down class="h-5 w-5" />
                                        Descargar credenciales
                                    </a>
                                @else
                                    <button type="button" :disabled="true"
                                        class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-200 px-5 py-3 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-neutral-500">
                                        <flux:icon.lock-closed class="h-5 w-5" />
                                        Descargar credenciales
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </section>
</div>
