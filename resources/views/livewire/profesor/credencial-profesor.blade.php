<div x-data="{
    abierto: localStorage.getItem('collapse_credenciales_profesor') ?? 'credenciales_profesor',
    cambiar() {
        this.abierto = this.abierto === 'credenciales_profesor' ? null : 'credenciales_profesor';

        if (this.abierto) {
            localStorage.setItem('collapse_credenciales_profesor', this.abierto);
        } else {
            localStorage.removeItem('collapse_credenciales_profesor');
        }
    }
}" class="space-y-6">

    <section class="space-y-4">
        <article
            class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm transition-all duration-300 dark:border-neutral-800 dark:bg-neutral-900">

            <button type="button" x-on:click.prevent="cambiar()"
                class="group flex w-full items-center justify-between gap-4 px-5 py-5 text-left transition hover:bg-slate-50 dark:hover:bg-neutral-800/60 sm:px-6">

                <div class="flex items-center gap-4">
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-slate-900 text-white shadow-lg shadow-blue-500/20 transition group-hover:scale-105">
                        <flux:icon.identification class="h-5 w-5" />
                    </span>

                    <span>
                        <span class="block text-base font-black text-slate-900 dark:text-white">
                            Descargar credenciales de profesores
                        </span>

                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">
                            Selecciona el nivel académico, busca profesores y genera sus credenciales en PDF.
                        </span>
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    @if ($this->nivelSeleccionado)
                        <span
                            class="hidden rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950/30 dark:text-blue-300 dark:ring-blue-900/60 sm:inline-flex">
                            {{ $this->nivelSeleccionado->nombre }}
                        </span>
                    @endif

                    <span
                        class="hidden rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/60 sm:inline-flex">
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

                    <div class="h-1.5 w-full bg-gradient-to-r from-blue-600 via-indigo-600 to-slate-900"></div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                    Filtros de credencial
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    El nivel seleccionado define el C.C.T., logo y director que aparecerán en la
                                    credencial.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="limpiarSeleccion"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                    <flux:icon.x-mark class="h-4 w-4" />
                                    Limpiar selección
                                </button>

                                <button type="button" wire:click="limpiarFiltros"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                    <flux:icon.arrow-path class="h-4 w-4" />
                                    Limpiar filtros
                                </button>
                            </div>
                        </div>

                        <div
                            class="mb-5 rounded-2xl border border-blue-200 bg-blue-50/80 p-4 text-sm text-blue-800 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-200">
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200">
                                    <flux:icon.information-circle class="h-5 w-5" />
                                </div>

                                <div>
                                    <p class="font-black">
                                        Selección por nivel académico
                                    </p>

                                    <p class="mt-1 text-sm">
                                        Si un profesor está asignado a varios niveles, primero elige el nivel de la
                                        credencial. Solo se mostrarán profesores relacionados con ese nivel.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <flux:field>
                                <flux:label>Nivel académico</flux:label>

                                <flux:select wire:model.live="nivel_id">
                                    <flux:select.option value="">
                                        Selecciona el nivel
                                    </flux:select.option>

                                    @foreach ($this->niveles as $nivel)
                                        <flux:select.option value="{{ $nivel->id }}">
                                            {{ $nivel->nombre }}
                                            @if ($nivel->cct)
                                                - C.C.T. {{ $nivel->cct }}
                                            @endif
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="nivel_id" />

                                @if ($this->nivelSeleccionado)
                                    <p class="mt-2 text-xs font-bold text-blue-600 dark:text-blue-300">
                                        Se usará el logo, C.C.T. y director de {{ $this->nivelSeleccionado->nombre }}.
                                    </p>
                                @endif
                            </flux:field>

                            <flux:field>
                                <flux:label>Modo de descarga</flux:label>

                                <flux:select wire:model.live="modo_descarga">
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

                                <flux:input type="search" wire:model.live.debounce.600ms="buscar_persona"
                                    :disabled="!$nivel_id"
                                    placeholder="{{ $nivel_id ? 'Nombre, CURP, RFC o correo...' : 'Primero selecciona un nivel' }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Vigencia</flux:label>

                                <flux:input type="text" wire:model.live.debounce.400ms="vigencia"
                                    placeholder="Ej. Ciclo escolar 2026 - 2027" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Cargo general</flux:label>

                                <flux:input type="text" wire:model.live.debounce.400ms="cargo"
                                    placeholder="Ej. Profesor" />
                            </flux:field>

                            @if ($modo_descarga === 'individual')
                                <flux:field class="xl:col-span-2">
                                    <flux:label>Profesor individual</flux:label>

                                    <flux:select wire:model.live="persona_individual_id" :disabled="!$nivel_id">
                                        <flux:select.option value="">
                                            {{ $nivel_id ? 'Selecciona un profesor' : 'Primero selecciona un nivel' }}
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

                            @if ($this->nivelSeleccionado)
                                <span
                                    class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300">
                                    Nivel: {{ $this->nivelSeleccionado->nombre }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700 dark:border-cyan-900/40 dark:bg-cyan-950/30 dark:text-cyan-300">
                                    C.C.T. {{ $this->nivelSeleccionado->cct ?? 'No especificado' }}
                                </span>
                            @endif

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
                                            :disabled="!$nivel_id"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-slate-900">
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
                                                @if (!$nivel_id)
                                                    <tr>
                                                        <td colspan="4" class="px-4 py-10 text-center">
                                                            <p class="font-black text-slate-700 dark:text-slate-200">
                                                                Selecciona un nivel académico.
                                                            </p>

                                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                                Después podrás buscar profesores asignados a ese nivel.
                                                            </p>
                                                        </td>
                                                    </tr>
                                                @else
                                                    @forelse ($this->personas as $persona)
                                                        <tr wire:key="resultado-persona-{{ $persona->id }}"
                                                            class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/60">

                                                            <td class="px-4 py-3">
                                                                <input type="checkbox" value="{{ $persona->id }}"
                                                                    wire:model.live="personas_seleccionadas"
                                                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:border-neutral-700">
                                                            </td>

                                                            <td class="px-4 py-3">
                                                                <p class="font-black text-slate-900 dark:text-white">
                                                                    {{ $this->nombrePersona($persona) }}
                                                                </p>

                                                                <div class="mt-1 flex flex-wrap gap-1.5">
                                                                    <span
                                                                        class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                                                        {{ $this->rolPrincipal($persona) }}
                                                                    </span>

                                                                    @if ($this->nivelSeleccionado)
                                                                        <span
                                                                            class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-black text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950/30 dark:text-blue-300 dark:ring-blue-900/40">
                                                                            {{ $this->nivelSeleccionado->nombre }}
                                                                        </span>
                                                                    @endif
                                                                </div>

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
                                                                <p
                                                                    class="font-black text-slate-700 dark:text-slate-200">
                                                                    No hay profesores para mostrar.
                                                                </p>

                                                                <p
                                                                    class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                                    Escribe en el buscador o revisa que existan
                                                                    profesores asignados al nivel.
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                @endif
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
                                                Nivel seleccionado:
                                                {{ $this->nivelSeleccionado?->nombre ?? 'No especificado' }}.
                                            </p>

                                            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                                Si alguna persona no tiene fotografía, se generará con el espacio “FOTO + SELLO” y el ZIP incluirá una advertencia.
                                            </p>
                                        @else
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                Selecciona un nivel académico y completa la selección del personal.
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if ($this->puedeDescargar)
                                    <div x-data="{ formatos: false, previa: false }" class="relative flex flex-wrap items-center justify-end gap-2">
                                        <button type="button" x-on:click="previa = true"
                                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            <flux:icon.eye class="h-5 w-5" />
                                            Vista previa
                                        </button>

                                        <div class="relative">
                                            <button type="button" x-on:click="formatos = !formatos"
                                                x-on:click.outside="formatos = false"
                                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-slate-900 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:shadow-xl">
                                                <flux:icon.document-arrow-down class="h-5 w-5" />
                                                Descargar
                                                <flux:icon.chevron-down class="h-4 w-4" />
                                            </button>

                                            <div x-cloak x-show="formatos" x-transition
                                                class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">
                                                <a href="{{ $this->urlDescarga }}" target="_blank"
                                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                    <flux:icon.document-text class="h-5 w-5 text-rose-500" />
                                                    Formato PDF
                                                </a>
                                                <a href="{{ $this->urlDescargaPng }}"
                                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                    <flux:icon.photo class="h-5 w-5 text-emerald-500" />
                                                    Imagen PNG
                                                </a>
                                                <a href="{{ $this->urlDescargaJpg }}"
                                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                    <flux:icon.photo class="h-5 w-5 text-sky-500" />
                                                    Imagen JPG · 100%
                                                </a>
                                            </div>
                                        </div>

                                        <template x-teleport="body">
                                            <div x-cloak x-show="previa" x-transition.opacity
                                                x-on:keydown.escape.window="previa = false"
                                                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm">
                                                <div x-on:click.outside="previa = false"
                                                    class="w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
                                                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-neutral-800">
                                                        <div>
                                                            <p class="font-black text-slate-900 dark:text-white">Vista previa de credencial del profesor</p>
                                                            <p class="text-xs text-slate-500">Se muestra la primera credencial del alcance seleccionado.</p>
                                                        </div>
                                                        <button type="button" x-on:click="previa = false"
                                                            class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-neutral-800">
                                                            <flux:icon.x-mark class="h-5 w-5" />
                                                        </button>
                                                    </div>
                                                    <div class="bg-slate-100 p-4 dark:bg-neutral-950">
                                                        <img x-bind:src="previa ? @js($this->urlVistaPrevia) : ''" alt="Vista previa de la credencial"
                                                            class="mx-auto h-auto max-h-[75vh] w-full rounded-xl object-contain shadow-lg">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                @else
                                    <button type="button" disabled
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
