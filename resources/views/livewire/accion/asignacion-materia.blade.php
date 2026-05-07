<div x-data="{
    openForm: @js($errors->any()),
    openPromediar: false,
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `La asignación ${nombre} se eliminará de forma permanente`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminar', id))
    }
}" x-on:abrir-formulario-materia.window="openForm = true; openPromediar = false"
    x-on:cerrar-formulario-materia.window="openForm = false"
    x-on:scroll-editar-materia.window="$nextTick(() => document.getElementById('panel-editar-asignacion')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
    class="space-y-6">

    {{-- NIVELES --}}
    <div class="overflow-hidden">
        <div class="-mx-1 overflow-x-auto pb-1">
            <div class="flex min-w-max items-center justify-center gap-2 px-1">
                @foreach ($niveles as $item)
                    @php
                        $activo = $slug_nivel === $item->slug;
                    @endphp

                    <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'asignacion-de-materias']) }}"
                        wire:navigate aria-current="{{ $activo ? 'page' : 'false' }}"
                        class="group relative inline-flex items-center gap-2 whitespace-nowrap rounded-2xl border px-4 py-3 text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5
                        {{ $activo
                            ? 'border-sky-200 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20 dark:border-sky-700/50'
                            : 'border-slate-200 bg-white text-slate-700 shadow-sm hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:border-sky-800 dark:hover:bg-neutral-800 dark:hover:text-sky-300' }}">

                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-xl
                            {{ $activo
                                ? 'bg-white/15 text-white'
                                : 'bg-slate-100 text-slate-500 group-hover:bg-sky-100 group-hover:text-sky-700 dark:bg-neutral-700 dark:text-slate-300 dark:group-hover:bg-sky-950/40 dark:group-hover:text-sky-300' }}">
                            <flux:icon.rectangle-stack class="h-4 w-4" />
                        </span>

                        <span>{{ $item->nombre }}</span>

                        @if ($activo)
                            <span class="rounded-full bg-white/15 px-2 py-0.5 text-[11px] font-bold text-white">
                                Activo
                            </span>
                            <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-white/80"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ENCABEZADO --}}
    <section>
        <div class="flex flex-col gap-4 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-800 dark:text-white">
                    Asignación de materias
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Asigna materias del catálogo a un grupo y profesor.
                </p>
            </div>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="w-full sm:w-80">
                    <flux:input wire:model.live.debounce.300ms="buscar"
                        placeholder="Buscar por materia, profesor, grado o grupo..." />
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="button" @click="openForm = !openForm; if(openForm){ openPromediar = false }"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-sky-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition"
                            :class="{ 'rotate-45': openForm }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span x-text="openForm ? 'Ocultar asignación' : 'Nueva asignación'"></span>
                    </button>

                    <button type="button"
                        @click="openPromediar = !openPromediar; if(openPromediar){ openForm = false }"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 via-fuchsia-500 to-pink-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-fuchsia-500/20 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-fuchsia-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition"
                            :class="{ 'rotate-180': openPromediar }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                        <span x-text="openPromediar ? 'Ocultar promediar' : 'Materias a promediar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- FORMULARIO --}}
    <section id="panel-editar-asignacion" x-show="openForm" x-collapse x-cloak
        class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">

        <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

        <form wire:submit.prevent="guardarMateria" class="relative">
            <div class="border-b border-slate-200/70 px-5 py-4 dark:border-white/10 sm:px-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white">
                            {{ $editandoId ? 'Editar asignación de materia' : 'Nueva asignación de materia' }}
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Selecciona una materia ya registrada en el catálogo.
                        </p>
                    </div>

                    @if ($editandoId)
                        <span
                            class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                            Editando
                        </span>
                    @endif
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                            Nivel actual
                        </p>
                        <h4 class="text-base font-bold text-slate-800 dark:text-white">
                            {{ $nivel->nombre ?? 'Sin nivel' }}
                        </h4>
                    </div>

                    @if ($nivel)
                        <span
                            class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                            {{ $nivel->nombre }}
                        </span>
                    @endif
                </div>

                <div
                    class="grid grid-cols-1 gap-5 md:grid-cols-2 {{ $this->esBachillerato ? 'xl:grid-cols-3' : 'xl:grid-cols-2' }}">

                    {{-- GENERACIÓN --}}
                    <flux:field>
                        <flux:label>Generación</flux:label>

                        <flux:select wire:model.live="generacion_id">
                            <option value="">Selecciona una generación</option>

                            @foreach ($generaciones as $item)
                                <option value="{{ $item['id'] }}">
                                    {{ $item['nombre'] }}
                                </option>
                            @endforeach
                        </flux:select>

                        <flux:error name="generacion_id" />
                    </flux:field>

                    {{-- GRADO --}}
                    <flux:field>
                        <flux:label>Grado</flux:label>

                        <flux:select wire:model.live="grado_id" :disabled="blank($generacion_id)">
                            <option value="">Selecciona un grado</option>

                            @foreach ($grados as $item)
                                <option value="{{ $item['id'] }}">
                                    {{ $item['nombre'] }}
                                </option>
                            @endforeach
                        </flux:select>

                        <flux:error name="grado_id" />
                    </flux:field>

                    {{-- SEMESTRE --}}
                    @if ($this->esBachillerato)
                        <flux:field>
                            <flux:label>Semestre</flux:label>

                            <flux:select wire:model.live="semestre"
                                :disabled="blank($generacion_id) || blank($grado_id)">
                                <option value="">Selecciona un semestre</option>

                                @foreach ($semestres as $item)
                                    <option value="{{ $item['id'] }}">
                                        {{ $item['numero'] }}° semestre
                                    </option>
                                @endforeach
                            </flux:select>

                            <flux:error name="semestre" />
                        </flux:field>
                    @endif

                    {{-- GRUPO --}}
                    <flux:field>
                        <flux:label>Grupo</flux:label>

                        <flux:select wire:model.live="grupo_id"
                            :disabled="$this->esBachillerato
                                                                                        ? (blank($generacion_id) || blank($grado_id) || blank($semestre))
                                                                                        : (blank($generacion_id) || blank($grado_id))">
                            <option value="">Selecciona un grupo</option>

                            @foreach ($grupos as $item)
                                <option value="{{ $item['id'] }}">
                                    {{ $item['nombre'] }}
                                </option>
                            @endforeach
                        </flux:select>

                        <flux:error name="grupo_id" />
                    </flux:field>

                    {{-- MATERIA --}}
                    <flux:field class="{{ $this->esBachillerato ? 'md:col-span-2' : '' }}">
                        <flux:label>Materia</flux:label>

                        <flux:select wire:model.live="materia_id"
                            :disabled="$this->esBachillerato
                                                                                        ? (blank($generacion_id) || blank($grado_id) || blank($semestre))
                                                                                        : (blank($generacion_id) || blank($grado_id))">
                            <option value="">Selecciona una materia</option>

                            @foreach ($this->materiasDisponibles as $item)
                                <option value="{{ $item->id }}">
                                    {{ $item->materia }}
                                    @if ($item->clave)
                                        - {{ $item->clave }}
                                    @endif
                                    @if ($item->extra)
                                        - Extra
                                    @endif
                                    @if ($item->receso)
                                        - Receso
                                    @endif
                                </option>
                            @endforeach
                        </flux:select>

                        <flux:error name="materia_id" />
                    </flux:field>

                    {{-- PROFESOR --}}
                    {{-- PROFESOR --}}
                    <div
                        class="space-y-2 md:col-span-2 {{ $this->esBachillerato ? 'xl:col-span-2' : 'xl:col-span-1' }}">
                        <div x-data="{
                            abierto: false,
                            seleccionar(id, nombre) {
                                @this.set('profesor_id', id);
                                @this.set('buscarProfesor', nombre);
                                this.abierto = false;
                            }
                        }" class="relative">

                            <flux:field>
                                <flux:label>Profesor</flux:label>

                                <div class="relative">
                                    <flux:input wire:model.live.debounce.250ms="buscarProfesor" @focus="abierto = true"
                                        @click="abierto = true" placeholder="Buscar profesor por nombre o apellido..."
                                        autocomplete="off" />

                                    @if ($profesor_id)
                                        <button type="button" wire:click="$set('profesor_id', null)"
                                            @click="@this.set('buscarProfesor', ''); abierto = true"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-rose-500 dark:hover:bg-white/10">
                                            ✕
                                        </button>
                                    @endif
                                </div>

                                <flux:error name="profesor_id" />
                            </flux:field>

                            <div x-show="abierto" x-cloak @click.outside="abierto = false"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1 scale-[0.98]"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
                                class="absolute z-50 mt-2 max-h-72 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl shadow-slate-900/10 dark:border-white/10 dark:bg-neutral-950">

                                @forelse ($this->profesoresFiltrados as $item)
                                    <button type="button"
                                        @click="seleccionar({{ $item['id'] }}, @js($item['nombre']))"
                                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold text-slate-700 transition hover:bg-sky-50 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-sky-500/10 dark:hover:text-sky-300">

                                        <span
                                            class="grid h-9 w-9 place-items-center rounded-xl bg-sky-50 text-xs font-black text-sky-700 dark:bg-sky-500/10 dark:text-sky-300">
                                            {{ mb_substr($item['nombre'], 0, 1) }}
                                        </span>

                                        <span class="min-w-0">
                                            <span class="block truncate">
                                                {{ $item['nombre'] }}
                                            </span>

                                            <span class="block text-xs font-medium text-slate-400">
                                                Profesor asignado a {{ $nivel->nombre ?? 'este nivel' }}
                                            </span>
                                        </span>
                                    </button>
                                @empty
                                    <div
                                        class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center dark:border-white/10">
                                        <p class="text-sm font-bold text-slate-600 dark:text-slate-300">
                                            No se encontraron profesores
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Revisa que el profesor esté relacionado con este nivel.
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                @if ($materia_id)
                    @php
                        $materiaSeleccionada = $this->materiasDisponibles->firstWhere('id', (int) $materia_id);
                    @endphp

                    @if ($materiaSeleccionada)
                        <div
                            class="mt-6 rounded-3xl border border-slate-200 bg-slate-50/80 p-5 dark:border-white/10 dark:bg-neutral-950/40">
                            <h4 class="text-sm font-black text-slate-800 dark:text-white">
                                Datos de la materia seleccionada
                            </h4>

                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Slug</p>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $materiaSeleccionada->slug }}
                                    </p>
                                </div>

                                @if ($this->esBachillerato)
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Clave</p>
                                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                            {{ $materiaSeleccionada->clave ?: '—' }}
                                        </p>
                                    </div>
                                @endif

                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Calificable</p>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $materiaSeleccionada->calificable ? 'Sí' : 'No' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Extra</p>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $materiaSeleccionada->extra ? 'Sí' : 'No' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <div
                class="flex flex-col-reverse gap-3 border-t border-slate-200/70 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                <button type="button" wire:click="limpiarFormulario"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-neutral-950/50 dark:text-slate-200 dark:hover:bg-white/5">
                    Cancelar
                </button>

                <flux:button type="submit" variant="primary">
                    {{ $editandoId ? 'Actualizar asignación' : 'Guardar asignación' }}
                </flux:button>
            </div>

            <div wire:loading.flex wire:target="guardarMateria"
                class="absolute inset-0 z-20 items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-neutral-900/75">
                <div class="flex flex-col items-center gap-3">
                    <div
                        class="h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-sky-500 dark:border-white/10 dark:border-t-sky-400">
                    </div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Guardando asignación...
                    </p>
                </div>
            </div>
        </form>
    </section>

    {{-- MATERIAS A PROMEDIAR --}}
    <section x-show="openPromediar" x-collapse x-cloak
        class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
        <div class="h-1.5 w-full bg-gradient-to-r from-violet-500 via-fuchsia-500 to-pink-500"></div>

        <livewire:materia-promediar :slug_nivel="$slug_nivel" />
    </section>

    {{-- RESUMEN --}}
    <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div
            class="rounded-3xl border border-white/60 bg-white/80 p-5 shadow-lg shadow-slate-200/40 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                Total asignaciones
            </p>
            <h2 class="mt-2 text-3xl font-black text-slate-800 dark:text-white">
                {{ $this->asignacionesFiltradas->count() }}
            </h2>
        </div>

        <div
            class="rounded-3xl border border-white/60 bg-white/80 p-5 shadow-lg shadow-slate-200/40 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                Profesores cargados
            </p>
            <h2 class="mt-2 text-3xl font-black text-slate-800 dark:text-white">
                {{ count($profesores) }}
            </h2>
        </div>

        <div
            class="rounded-3xl border border-white/60 bg-white/80 p-5 shadow-lg shadow-slate-200/40 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                Nivel actual
            </p>
            <h2 class="mt-2 text-lg font-black text-slate-800 dark:text-white">
                {{ $nivel->nombre ?? 'Sin nivel' }}
            </h2>
        </div>
    </section>

    {{-- LISTADO --}}
    <section
        class="relative overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
        <div class="h-1.5 w-full bg-gradient-to-r from-fuchsia-500 via-violet-500 to-sky-500"></div>

        <div class="p-5 sm:p-6">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">
                        Materias asignadas
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Consulta rápida de asignaciones, agrupadas por grado.
                    </p>
                </div>

                @if ($ultimoRegistroId)
                    <span
                        class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                        Último registro {{ $ultimoMovimiento }}
                    </span>
                @endif
            </div>

            @php
                $asignacionesAgrupadas = $this->asignacionesFiltradas->groupBy(function ($item) {
                    return $item->materia?->grado?->nombre ?? 'Sin grado';
                });
            @endphp

            @forelse ($asignacionesAgrupadas as $nombreGrado => $materias)
                @php
                    $gradoId = $materias->first()?->materia?->grado_id;
                    $abrirGrado = $materias->contains('id', $ultimoRegistroId);
                @endphp

                <div x-data="{ open: @js($abrirGrado) }"
                    class="mb-6 overflow-hidden rounded-3xl border border-slate-200/70 bg-white/70 shadow-sm dark:border-white/10 dark:bg-neutral-950/40">

                    <button type="button" @click="open = !open"
                        class="flex w-full items-center justify-between gap-3 border-b border-slate-200/70 bg-gradient-to-r from-sky-50 via-indigo-50 to-fuchsia-50 px-5 py-4 text-left transition hover:from-sky-100 hover:via-indigo-100 hover:to-fuchsia-100 dark:border-white/10 dark:from-sky-500/10 dark:via-indigo-500/10 dark:to-fuchsia-500/10">
                        <div class="flex items-center gap-4">
                            <span
                                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-sky-200 bg-white text-sky-600 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition duration-300"
                                    :class="{ 'rotate-90': open }" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M7.21 14.77a.75.75 0 01.02-1.06L10.94 10 7.23 6.29a.75.75 0 111.06-1.06l4.24 4.24a.75.75 0 010 1.06l-4.24 4.24a.75.75 0 01-1.08 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </span>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">
                                    Grado
                                </p>
                                <h4 class="text-lg font-black text-slate-800 dark:text-white">
                                    {{ $nombreGrado }}
                                </h4>
                            </div>
                        </div>

                        <span
                            class="inline-flex items-center rounded-full border border-sky-200 bg-white px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                            {{ $materias->count() }} {{ $materias->count() === 1 ? 'materia' : 'materias' }}
                        </span>
                    </button>

                    <div x-show="open" x-collapse>
                        {{-- TABLA DESKTOP --}}
                        <div class="hidden overflow-x-auto lg:block">
                            <table class="min-w-full">
                                <thead class="sticky top-0 z-10 bg-slate-100/95 backdrop-blur dark:bg-neutral-900">
                                    <tr class="text-left">
                                        <th
                                            class="w-14 px-4 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Orden
                                        </th>

                                        <th
                                            class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Materia
                                        </th>

                                        @if ($this->esBachillerato)
                                            <th
                                                class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Clave
                                            </th>
                                        @endif

                                        <th
                                            class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Grupo
                                        </th>

                                        @if ($this->esBachillerato)
                                            <th
                                                class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Semestre
                                            </th>
                                        @endif

                                        <th
                                            class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Profesor
                                        </th>

                                        <th
                                            class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Calificable
                                        </th>

                                        <th
                                            class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Extra
                                        </th>

                                        <th
                                            class="px-4 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Receso
                                        </th>

                                        <th
                                            class="px-4 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>

                                <tbody data-sortable="grado" data-grado-id="{{ $gradoId }}"
                                    class="divide-y divide-slate-200/70 dark:divide-white/10">
                                    @foreach ($materias as $item)
                                        @php
                                            $esUltimo = $ultimoRegistroId === $item->id;
                                            $nombreMateria = $item->materia?->materia ?? 'Sin materia';
                                        @endphp

                                        <tr data-id="{{ $item->id }}"
                                            class="transition {{ $esUltimo ? 'bg-gradient-to-r from-emerald-50 via-white to-sky-50 dark:from-emerald-500/10 dark:via-white/[0.02] dark:to-sky-500/10' : 'hover:bg-slate-50/80 dark:hover:bg-white/[0.03]' }}">
                                            <td class="px-4 py-4 align-top text-center">
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

                                            <td class="px-4 py-4 align-top">
                                                <div class="font-bold text-slate-800 dark:text-white">
                                                    {{ $nombreMateria }}
                                                </div>
                                                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    {{ $item->materia?->slug ?? 'sin-slug' }}
                                                </div>
                                            </td>

                                            @if ($this->esBachillerato)
                                                <td
                                                    class="px-4 py-4 align-top text-sm font-medium text-slate-700 dark:text-slate-200">
                                                    {{ $item->materia?->clave ?: '—' }}
                                                </td>
                                            @endif

                                            <td
                                                class="px-4 py-4 align-top text-sm font-medium text-slate-700 dark:text-slate-200">
                                                {{ $item->grupo?->nombre ?? '—' }}
                                            </td>

                                            @if ($this->esBachillerato)
                                                <td
                                                    class="px-4 py-4 align-top text-sm font-medium text-slate-700 dark:text-slate-200">
                                                    {{ $item->materia?->semestre?->numero ?? $item->materia?->semestre_id }}°
                                                    semestre
                                                </td>
                                            @endif

                                            <td
                                                class="px-4 py-4 align-top text-sm font-semibold text-slate-700 dark:text-slate-200">
                                                {{ trim(($item->profesor?->nombre ?? '') . ' ' . ($item->profesor?->apellido_paterno ?? '') . ' ' . ($item->profesor?->apellido_materno ?? '')) ?: '—' }}
                                            </td>

                                            <td class="px-4 py-4 align-top">
                                                @if ($item->materia?->calificable == 1)
                                                    <span
                                                        class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                                        Sí
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                                                        No
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-4 align-top">
                                                @if ($item->materia?->extra == 1)
                                                    <span
                                                        class="inline-flex rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:border-violet-500/20 dark:bg-violet-500/10 dark:text-violet-300">
                                                        Sí
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                                        No
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-4 align-top">
                                                @if ($item->materia?->receso == 1)
                                                    <span
                                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                                                        Sí
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                                        No
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-4 align-top text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button type="button" wire:click="editar({{ $item->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="editar({{ $item->id }})"
                                                        class="inline-flex items-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 transition hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                                                        Editar
                                                    </button>

                                                    <button type="button"
                                                        @click="eliminar('{{ $item->id }}', '{{ addslashes($nombreMateria) }}')"
                                                        class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                                                        Eliminar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- MÓVIL --}}
                        <div class="space-y-4 p-4 lg:hidden">
                            @foreach ($materias as $item)
                                @php
                                    $nombreMateria = $item->materia?->materia ?? 'Sin materia';
                                @endphp

                                <article
                                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-neutral-950/50">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h4 class="text-base font-black text-slate-800 dark:text-white">
                                                {{ $nombreMateria }}
                                            </h4>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                {{ $item->materia?->slug ?? 'sin-slug' }}
                                            </p>
                                        </div>

                                        @if ($item->materia?->receso)
                                            <span
                                                class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                                                Receso
                                            </span>
                                        @elseif ($item->materia?->extra)
                                            <span
                                                class="rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700">
                                                Extra
                                            </span>
                                        @elseif ($item->materia?->calificable)
                                            <span
                                                class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                                Calificable
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Grupo
                                            </p>
                                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                                {{ $item->grupo?->nombre ?? '—' }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                Profesor</p>
                                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                                {{ trim(($item->profesor?->nombre ?? '') . ' ' . ($item->profesor?->apellido_paterno ?? '') . ' ' . ($item->profesor?->apellido_materno ?? '')) ?: '—' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex justify-end gap-2">
                                        <button type="button" wire:click="editar({{ $item->id }})"
                                            class="inline-flex items-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 transition hover:bg-sky-100 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                                            Editar
                                        </button>

                                        <button type="button"
                                            @click="eliminar('{{ $item->id }}', '{{ addslashes($nombreMateria) }}')"
                                            class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                                            Eliminar
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div
                    class="rounded-3xl border border-dashed border-slate-300 bg-slate-50/70 px-6 py-12 text-center dark:border-white/10 dark:bg-white/[0.02]">
                    <h4 class="text-base font-black text-slate-700 dark:text-slate-200">
                        No hay materias asignadas
                    </h4>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Agrega una nueva asignación para comenzar.
                    </p>
                </div>
            @endforelse
        </div>

        <div wire:loading.flex wire:target="editar"
            class="absolute inset-0 z-20 items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-neutral-900/75">
            <div class="flex flex-col items-center gap-3">
                <div
                    class="h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-sky-500 dark:border-white/10 dark:border-t-sky-400">
                </div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Cargando datos para editar...
                </p>
            </div>
        </div>
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

                function initSortableMateriasPorGrado() {
                    if (typeof Sortable === 'undefined') return;

                    document.querySelectorAll('tbody[data-sortable="grado"]').forEach((el) => {
                        if (el._sortable) return;

                        const gradoId = parseInt(el.dataset.gradoId || '0', 10);

                        if (!gradoId) return;

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

                                component.call('ordenarMateriasPorGradoJs', gradoId, ids);
                            },
                        });
                    });
                }

                document.addEventListener('DOMContentLoaded', () => initSortableMateriasPorGrado());

                document.addEventListener('livewire:init', () => {
                    initSortableMateriasPorGrado();

                    Livewire.hook('message.processed', () => {
                        initSortableMateriasPorGrado();
                    });
                });

                const intervalo = setInterval(() => {
                    if (typeof Sortable !== 'undefined') {
                        clearInterval(intervalo);
                        initSortableMateriasPorGrado();
                    }
                }, 120);
            })
            ();
        </script>
    @endonce
</div>
