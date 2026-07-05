<div x-data="{
    open: @js($errors->any()),

    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `La materia ${nombre} se eliminará permanentemente.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((resultado) => {
            if (resultado.isConfirmed) {
                @this.call('eliminar', id)
            }
        })
    },

    irAlFormulario() {
        open = true

        setTimeout(() => {
            const panel = document.getElementById('panel-materia')

            if (!panel) return

            panel.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            })
        }, 250)
    }
}" x-on:abrir-formulario-materia.window="open = true"
    x-on:scroll-panel-materia.window="irAlFormulario()" x-on:cerrar-formulario-materia.window="open = false"
    class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Catálogo de materias
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Administra las materias generales por nivel, grado y semestre.
            </p>
        </div>



        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <form wire:submit.prevent="importarMaterias" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label
                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-bold text-emerald-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">

                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 9.707a1 1 0 011.414 0L9 11V3a1 1 0 112 0v8l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </span>

                    <span>Seleccionar Excel</span>

                    <input type="file" wire:model="archivo_materias" accept=".xlsx,.xls" class="hidden">
                </label>

                @if ($archivo_materias)
                    <button type="submit" wire:loading.attr="disabled" wire:target="importarMaterias,archivo_materias"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 transition hover:-translate-y-0.5 hover:shadow-xl disabled:opacity-60">

                        <span wire:loading.remove wire:target="importarMaterias">
                            Importar materias
                        </span>

                        <span wire:loading wire:target="importarMaterias">
                            Importando…
                        </span>
                    </button>
                @endif
            </form>

            <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="panel-materia"
                class="group inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-sky-500/20 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-sky-500/30 focus:outline-none focus:ring-2 focus:ring-sky-300 dark:focus:ring-offset-neutral-900">

                <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-white/15">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition" :class="{ 'rotate-45': open }"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                </span>

                <span x-text="open ? 'Ocultar formulario' : 'Nueva materia'"></span>

                <span class="transition-transform duration-200" :class="open ? 'rotate-180' : 'rotate-0'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 15.5l-6-6h12l-6 6z" />
                    </svg>
                </span>
            </button>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
            class="rounded-3xl border border-sky-100 bg-sky-50 p-5 shadow-sm dark:border-sky-900/40 dark:bg-sky-950/30">
            <p class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">Total materias</p>
            <p class="mt-2 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->totalMaterias }}</p>
        </div>

        <div
            class="rounded-3xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/30">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Calificables
            </p>
            <p class="mt-2 text-3xl font-black text-emerald-900 dark:text-emerald-100">
                {{ $this->totalCalificables }}</p>
        </div>

        <div
            class="rounded-3xl border border-violet-100 bg-violet-50 p-5 shadow-sm dark:border-violet-900/40 dark:bg-violet-950/30">
            <p class="text-xs font-bold uppercase tracking-wide text-violet-700 dark:text-violet-300">Extras</p>
            <p class="mt-2 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->totalExtras }}</p>
        </div>

        <div
            class="rounded-3xl border border-amber-100 bg-amber-50 p-5 shadow-sm dark:border-amber-900/40 dark:bg-amber-950/30">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Recesos</p>
            <p class="mt-2 text-3xl font-black text-amber-900 dark:text-amber-100">{{ $this->totalRecesos }}</p>
        </div>
    </div>

    {{-- Collapse formulario --}}
    <div id="panel-materia" x-show="open" x-cloak x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative">

        <form wire:submit.prevent="guardarMateria" class="group">
            <div
                class="relative overflow-hidden rounded-[28px] border border-white/70 bg-white shadow-xl shadow-slate-200/50 dark:border-white/10 dark:bg-neutral-900 dark:shadow-black/20">

                <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

                <div class="border-b border-slate-200/70 px-5 py-4 dark:border-white/10 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M9 4.804A7.968 7.968 0 005.5 4C3.57 4 2 4.672 2 5.5v8c0 .828 1.57 1.5 3.5 1.5 1.307 0 2.45-.308 3.05-.766A7.97 7.97 0 0112.5 15c1.93 0 3.5-.672 3.5-1.5v-8c0-.828-1.57-1.5-3.5-1.5A7.968 7.968 0 009 4.804z" />
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-lg font-black text-slate-900 dark:text-white">
                                    {{ $editandoId ? 'Editar materia' : 'Nueva materia' }}
                                </h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Completa la información del catálogo general.
                                </p>
                            </div>
                        </div>

                        @if ($editandoId)
                            <span
                                class="inline-flex w-fit items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                                Modo edición
                            </span>
                        @endif
                    </div>
                </div>

                <div class="space-y-6 p-5 sm:p-6 lg:p-8">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <flux:field>
                            <flux:label badge="Requerido">Nivel</flux:label>
                            <flux:select wire:model.live="nivel_id">
                                <option value="">Selecciona un nivel</option>
                                @foreach ($niveles as $nivel)
                                    <option value="{{ $nivel->id }}">{{ $nivel->nombre }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="nivel_id" />
                        </flux:field>

                        <flux:select badge="Requerido" label="Grado" wire:model.live="grado_id"
                            wire:key="grado-formulario-{{ $nivel_id ?? 'sin-nivel' }}-{{ $editandoId ?? 'nuevo' }}"
                            :disabled="blank($nivel_id)">
                            <option value="">Selecciona un grado</option>

                            @foreach ($gradosFormulario as $grado)
                                <option value="{{ $grado->id }}">
                                    {{ $grado->nombre }}
                                </option>
                            @endforeach
                        </flux:select>

                        @if ($this->esBachilleratoFormulario)
                            <flux:select label="Semestre" wire:model.live="semestre_id"
                                wire:key="semestre-formulario-{{ $grado_id ?? 'sin-grado' }}-{{ $editandoId ?? 'nuevo' }}"
                                :disabled="blank($grado_id)">
                                <option value="">Selecciona un semestre</option>

                                @foreach ($semestresFormulario as $semestre)
                                    <option value="{{ $semestre->id }}">
                                        {{ $semestre->numero }}° semestre
                                    </option>
                                @endforeach
                            </flux:select>
                        @endif
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <flux:field class="{{ $this->esBachilleratoFormulario ? 'xl:col-span-2' : 'xl:col-span-2' }}">
                            <flux:label badge="Requerido">Materia</flux:label>
                            <flux:input wire:model.live.debounce.400ms="materia" placeholder="Nombre de la materia" />
                            <flux:error name="materia" />
                        </flux:field>

                        @if ($this->esBachilleratoFormulario)
                            <flux:field>
                                <flux:label badge="Opcional">Clave</flux:label>
                                <flux:input wire:model.live.debounce.400ms="clave" maxlength="50" class="uppercase"
                                    placeholder="Ej. BG101" />
                                <flux:error name="clave" />
                            </flux:field>
                        @endif

                        <flux:field>
                            <flux:label badge="Requerido">Slug</flux:label>
                            <flux:input variant="filled" wire:model.live.debounce.400ms="slug"
                                placeholder="slug-de-la-materia" />
                            <flux:error name="slug" />
                        </flux:field>

                        <flux:select wire:model.live="campo_formativo_id" label="Campo formativo">
                            <option value="">Sin clasificar</option>
                            @foreach ($camposFormativos as $campo)
                                <option value="{{ $campo->id }}">{{ $campo->nombre }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div
                        class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-neutral-950/40">
                        <div class="mb-4">
                            <h3 class="text-sm font-black text-slate-800 dark:text-white">
                                Configuración académica
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Define si la materia se califica, si es extra o si corresponde a un receso.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border border-white bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-neutral-900">
                                <input type="checkbox" wire:model.live="calificable"
                                    @disabled($this->esBachilleratoFormulario && ($extra || $receso))
                                    class="mt-1 rounded border-slate-300 text-sky-600 focus:ring-sky-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700">
                                <span>
                                    <span class="block text-sm font-bold text-slate-800 dark:text-white">
                                        Calificable
                                    </span>
                                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                                        Se podrá capturar calificación para esta materia.
                                    </span>
                                </span>
                            </label>

                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border border-white bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-neutral-900">
                                <input type="checkbox" wire:model.live="extra"
                                    class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500 dark:border-neutral-700">
                                <span>
                                    <span class="block text-sm font-bold text-slate-800 dark:text-white">
                                        Extra
                                    </span>
                                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                                        {{ $this->esBachilleratoFormulario
                                            ? 'Permite captura y aparece en boleta, pero nunca interviene en promedios, lugares o reconocimientos.'
                                            : 'Cuenta como asignada, pero puede omitirse del promedio.' }}
                                    </span>
                                </span>
                            </label>

                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border border-white bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-neutral-900">
                                <input type="checkbox" wire:model.live="receso"
                                    class="mt-1 rounded border-slate-300 text-amber-600 focus:ring-amber-500 dark:border-neutral-700">
                                <span>
                                    <span class="block text-sm font-bold text-slate-800 dark:text-white">
                                        Receso
                                    </span>
                                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                                        Se marca como espacio especial; no será calificable.
                                    </span>
                                </span>
                            </label>


                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border border-white bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-neutral-900">
                                <input type="checkbox" wire:model.live="participa_en_calificacion_oficial"
                                    @disabled($this->esBachilleratoFormulario && ($extra || $receso))
                                    class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700">
                                <span>
                                    <span class="block text-sm font-bold text-slate-800 dark:text-white">
                                        Participa en calificación oficial
                                    </span>
                                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                                        En primaria participa en la sugerencia del campo formativo; en secundaria participa en el promedio acreditable.
                                    </span>
                                </span>
                            </label>
                        </div>

                        @if ($this->esBachilleratoFormulario)
                            <div class="mt-4 flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 text-sm text-sky-900 dark:border-sky-900/40 dark:bg-sky-950/20 dark:text-sky-200">
                                <flux:icon.information-circle class="mt-0.5 h-5 w-5 shrink-0" />
                                <div>
                                    <p class="font-black">Regla exclusiva de bachillerato</p>
                                    <p class="mt-1 text-xs leading-5">
                                        Una materia oficial debe ser calificable, no extra y no receso. Una materia extra se captura y se muestra por separado en boletas, PDF y Excel, pero queda excluida de todos los promedios y reconocimientos.
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-4">
                            <flux:error name="calificable" />
                            <flux:error name="extra" />
                            <flux:error name="receso" />
                            <flux:error name="participa_en_calificacion_oficial" />
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col-reverse gap-3 border-t border-slate-200/70 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    <button type="button" wire:click="limpiarFormulario"
                        class="inline-flex justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-neutral-950 dark:text-slate-200 dark:hover:bg-white/5">
                        Cancelar
                    </button>

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                        wire:target="guardarMateria">
                        {{ $editandoId ? 'Actualizar materia' : 'Guardar materia' }}
                    </flux:button>
                </div>

                <div wire:loading.delay wire:target="guardarMateria"
                    class="pointer-events-none absolute inset-0 z-20 grid place-items-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                    <div
                        class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-lg dark:border-white/10 dark:bg-neutral-950">
                        <span
                            class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-slate-900 dark:border-neutral-700 dark:border-t-white"></span>
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200">
                            Guardando materia…
                        </span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Filtros --}}
    <section
        class="overflow-hidden rounded-[28px] border border-white/70 bg-white shadow-lg shadow-slate-200/40 dark:border-white/10 dark:bg-neutral-900 dark:shadow-black/20">
        <div class="h-1.5 w-full bg-gradient-to-r from-fuchsia-500 via-violet-500 to-sky-500"></div>

        <div class="space-y-4 p-5 sm:p-6">
            <div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white">
                    Materias registradas
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Consulta, filtra, edita, elimina u ordena materias del catálogo general.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <flux:field>
                    <flux:label>Búsqueda</flux:label>
                    <flux:input wire:model.live.debounce.350ms="buscar"
                        placeholder="Materia, clave, slug, grado o nivel..." />
                </flux:field>

                <flux:field>
                    <flux:label>Nivel</flux:label>
                    <flux:select wire:model.live="filtro_nivel_id">
                        <option value="">Todos los niveles</option>
                        @foreach ($niveles as $nivel)
                            <option value="{{ $nivel->id }}">{{ $nivel->nombre }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Grado</flux:label>
                    <flux:select wire:model.live="filtro_grado_id" :disabled="blank($filtro_nivel_id)">
                        <option value="">Todos los grados</option>
                        @foreach ($gradosFiltro as $grado)
                            <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                @if ($this->esBachilleratoFiltro)
                    <flux:field>
                        <flux:label>Semestre</flux:label>
                        <flux:select wire:model.live="filtro_semestre_id" :disabled="blank($filtro_grado_id)">
                            <option value="">Todos los semestres</option>
                            @foreach ($semestresFiltro as $semestre)
                                <option value="{{ $semestre->id }}">{{ $semestre->numero }}° semestre</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Tipo</flux:label>
                    <flux:select wire:model.live="filtro_tipo">
                        <option value="">Todas</option>
                        <option value="calificables">Calificables</option>
                        <option value="extras">Extras</option>
                        <option value="recesos">Recesos</option>
                    </flux:select>
                </flux:field>
            </div>
        </div>
    </section>

    {{-- Listado agrupado --}}
    {{-- Listado agrupado por nivel con collapse --}}
    <section class="space-y-6">
        @php
            $materiasPorNivel = $materiasAgrupadas->groupBy(function ($materiasGrupo) {
                return $materiasGrupo->first()?->nivel_id ?? 'sin-nivel';
            });
        @endphp

        @forelse ($materiasPorNivel as $nivelKey => $gruposDelNivel)
            @php
                $primeraMateriaNivel = $gruposDelNivel->flatten(1)->first();
                $nombreNivel = $primeraMateriaNivel?->nivel?->nombre ?? 'Sin nivel';
                $nivelCollapseKey = 'nivel-' . $nivelKey;
                $totalMateriasNivel = $gruposDelNivel->sum(fn($grupo) => $grupo->count());
            @endphp

            <div x-data="{ abierto: false }"
                class="overflow-hidden rounded-[28px] border border-white/70 bg-white shadow-xl shadow-slate-200/50 dark:border-white/10 dark:bg-neutral-900 dark:shadow-black/20">

                <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

                {{-- Encabezado del nivel --}}
                <button type="button" @click="abierto = !abierto" :aria-expanded="abierto"
                    aria-controls="{{ $nivelCollapseKey }}"
                    class="flex w-full flex-col gap-4 border-b border-slate-200 bg-slate-50/80 px-5 py-5 text-left transition hover:bg-slate-100/80 dark:border-white/10 dark:bg-neutral-950/40 dark:hover:bg-white/[0.04] sm:flex-row sm:items-center sm:justify-between">

                    <div class="flex items-center gap-4">
                        <div
                            class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.786 0l7-3a1 1 0 000-1.838l-7-3z" />
                                <path
                                    d="M3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762z" />
                                <path
                                    d="M9.21 10.929a3 3 0 002.58 0l3.96-1.697a11.083 11.083 0 01.19 3.927 1 1 0 01-.89.89 8.968 8.968 0 00-5.55 2.27 8.968 8.968 0 00-5.55-2.27 1 1 0 01-.89-.89 11.083 11.083 0 01.19-3.927l3.96 1.697z" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-sky-600 dark:text-sky-300">
                                Nivel académico
                            </p>

                            <h2 class="text-xl font-black text-slate-900 dark:text-white">
                                {{ $nombreNivel }}
                            </h2>

                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{ $gruposDelNivel->count() }} contexto(s) académico(s) registrado(s)
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex w-fit rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                            {{ $totalMateriasNivel }} materias
                        </span>

                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition dark:border-white/10 dark:bg-neutral-900 dark:text-slate-300"
                            :class="{ 'rotate-180': abierto }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24"
                                fill="currentColor">
                                <path d="M12 15.5l-6-6h12l-6 6z" />
                            </svg>
                        </span>
                    </div>
                </button>

                {{-- Contenido del nivel --}}
                <div id="{{ $nivelCollapseKey }}" x-show="abierto" x-cloak
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2" class="space-y-5 p-4 sm:p-5">

                    @foreach ($gruposDelNivel as $contexto => $materiasGrupo)
                        @php
                            $primera = $materiasGrupo->first();
                            $contextoKey = str_replace('|', '-', $contexto);
                        @endphp

                        <div
                            class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-neutral-950/40">

                            <div
                                class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4 dark:border-white/10 dark:bg-neutral-950/60 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                        Contexto académico
                                    </p>

                                    <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                        {{ $primera->grado?->nombre ?? 'Sin grado' }}

                                        @if ($primera->semestre)
                                            · {{ $primera->semestre?->numero }}° semestre
                                        @endif
                                    </h3>
                                </div>

                                <span
                                    class="inline-flex w-fit rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                    {{ $materiasGrupo->count() }} materias
                                </span>
                            </div>

                            {{-- Tabla desktop --}}
                            <div class="hidden overflow-x-auto lg:block">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-100 text-slate-600 dark:bg-neutral-950 dark:text-slate-300">
                                        <tr>
                                            <th
                                                class="w-20 px-4 py-4 text-center text-xs font-black uppercase tracking-wider">
                                                Orden
                                            </th>

                                            <th
                                                class="px-4 py-4 text-left text-xs font-black uppercase tracking-wider">
                                                Materia
                                            </th>

                                            <th
                                                class="px-4 py-4 text-left text-xs font-black uppercase tracking-wider">
                                                Clave
                                            </th>

                                            <th
                                                class="px-4 py-4 text-center text-xs font-black uppercase tracking-wider">
                                                Estados
                                            </th>

                                            <th
                                                class="px-4 py-4 text-center text-xs font-black uppercase tracking-wider">
                                                Asignaciones
                                            </th>

                                            <th
                                                class="px-4 py-4 text-center text-xs font-black uppercase tracking-wider">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody data-sortable="materias" data-contexto="{{ $contextoKey }}"
                                        class="divide-y divide-slate-100 dark:divide-white/10">
                                        @foreach ($materiasGrupo as $item)
                                            <tr data-id="{{ $item->id }}"
                                                class="transition hover:bg-slate-50 dark:hover:bg-white/[0.03]">
                                                <td class="px-4 py-4 text-center">
                                                    <button type="button" data-handle
                                                        class="inline-flex h-9 w-9 cursor-move items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 transition hover:border-sky-200 hover:text-sky-600 dark:border-white/10 dark:bg-neutral-900 dark:text-slate-500 dark:hover:border-sky-500/20 dark:hover:text-sky-300"
                                                        title="Arrastrar para ordenar">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            viewBox="0 0 20 20" fill="currentColor">
                                                            <path
                                                                d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 4.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM13 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 4.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM13 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                                        </svg>
                                                    </button>
                                                </td>

                                                <td class="px-4 py-4">
                                                    <div class="font-black text-slate-900 dark:text-white">
                                                        {{ $item->materia }}
                                                    </div>

                                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                        {{ $item->slug }} · Orden {{ $item->orden }}
                                                    </div>
                                                </td>

                                                <td class="px-4 py-4 font-semibold text-slate-700 dark:text-slate-200">
                                                    {{ $item->clave ?: '—' }}
                                                </td>

                                                <td class="px-4 py-4">
                                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                                        @if ($item->calificable)
                                                            <span
                                                                class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                                Calificable
                                                            </span>
                                                        @endif

                                                        @if ($item->extra)
                                                            <span
                                                                class="rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                                                Extra
                                                            </span>
                                                        @endif

                                                        @if ($item->receso)
                                                            <span
                                                                class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                                                Receso
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td class="px-4 py-4 text-center">
                                                    <span
                                                        class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                                        {{ $item->asignaciones_count }}
                                                    </span>
                                                </td>

                                                <td class="px-4 py-4">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button type="button"
                                                            wire:click="editar({{ $item->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="editar({{ $item->id }})"
                                                            class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 transition hover:bg-sky-100 disabled:opacity-60 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                                            Editar
                                                        </button>

                                                        <button type="button"
                                                            @click="eliminar('{{ $item->id }}', '{{ addslashes($item->materia) }}')"
                                                            class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Móvil --}}
                            <div class="space-y-4 p-4 lg:hidden">
                                @foreach ($materiasGrupo as $item)
                                    <article
                                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-neutral-950/40">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h3 class="text-base font-black text-slate-900 dark:text-white">
                                                    {{ $item->materia }}
                                                </h3>

                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    {{ $item->slug }}
                                                </p>
                                            </div>

                                            <span
                                                class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                                Orden {{ $item->orden }}
                                            </span>
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                            <div>
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                    Clave
                                                </p>

                                                <p class="font-semibold text-slate-700 dark:text-slate-200">
                                                    {{ $item->clave ?: '—' }}
                                                </p>
                                            </div>

                                            <div>
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                    Asignaciones
                                                </p>

                                                <p class="font-semibold text-slate-700 dark:text-slate-200">
                                                    {{ $item->asignaciones_count }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @if ($item->calificable)
                                                <span
                                                    class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                    Calificable
                                                </span>
                                            @endif

                                            @if ($item->extra)
                                                <span
                                                    class="rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                                    Extra
                                                </span>
                                            @endif

                                            @if ($item->receso)
                                                <span
                                                    class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                                    Receso
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-5 flex justify-end gap-2">
                                            <button type="button" wire:click="editar({{ $item->id }})"
                                                class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 transition hover:bg-sky-100 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                                Editar
                                            </button>

                                            <button type="button"
                                                @click="eliminar('{{ $item->id }}', '{{ addslashes($item->materia) }}')"
                                                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                                Eliminar
                                            </button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div
                class="rounded-3xl border border-dashed border-slate-300 bg-slate-50/70 px-6 py-12 text-center dark:border-white/10 dark:bg-white/[0.02]">
                <h3 class="text-base font-black text-slate-800 dark:text-white">
                    No hay materias registradas
                </h3>

                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Agrega una nueva materia para comenzar.
                </p>
            </div>
        @endforelse
    </section>

    {{-- SORTABLE --}}
    @once
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <script>
            (function() {
                function getLivewireComponentFrom(el) {
                    const root = el.closest('[wire\\:id]');
                    if (!root) return null;

                    const componentId = root.getAttribute('wire:id');

                    return componentId ? Livewire.find(componentId) : null;
                }

                function initSortableMateriasPorContexto() {
                    if (typeof Sortable === 'undefined') return;

                    document.querySelectorAll('tbody[data-sortable="materias"]').forEach((el) => {
                        if (el._sortable) return;

                        const contexto = el.dataset.contexto || '';

                        el._sortable = new Sortable(el, {
                            animation: 150,
                            handle: '[data-handle]',
                            draggable: 'tr[data-id]',
                            dataIdAttr: 'data-id',
                            forceFallback: true,
                            fallbackOnBody: true,
                            fallbackTolerance: 5,

                            onEnd: () => {
                                const ids = el._sortable.toArray()
                                    .map(v => parseInt(v, 10))
                                    .filter(Boolean);

                                if (!ids.length) return;

                                const component = getLivewireComponentFrom(el);
                                if (!component) return;

                                component.call('ordenarMateriasPorContextoJs', contexto, ids);
                            },
                        });
                    });
                }

                document.addEventListener('DOMContentLoaded', () => initSortableMateriasPorContexto());

                document.addEventListener('livewire:init', () => {
                    initSortableMateriasPorContexto();

                    Livewire.hook('message.processed', () => {
                        initSortableMateriasPorContexto();
                    });
                });

                const t = setInterval(() => {
                    if (typeof Sortable !== 'undefined') {
                        clearInterval(t);
                        initSortableMateriasPorContexto();
                    }
                }, 120);
            })
            ();
        </script>
    @endonce
</div>
