<div x-data="{
    guardarScroll() {
            localStorage.setItem('scroll_credenciales_{{ $slug_nivel }}', window.scrollY || 0);
        },

        restaurarScroll() {
            const posicion = localStorage.getItem('scroll_credenciales_{{ $slug_nivel }}');

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

            <div class="bg-slate-50/70 p-4 dark:bg-neutral-950/30 sm:p-6">

                <div
                    class="relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

                    <div wire:loading.delay.flex
                        wire:target="generacion_id,grado_id,semestre_id,grupo_id,modo_descarga,buscar_alumno,alumno_individual_id,alumnos_seleccionados,copias_por_alumno,limpiarFiltros,seleccionarTodosVisibles,seleccionarTodosAlcance,limpiarSeleccion,quitarAlumnoSeleccionado,incrementarCopiasAlumno,decrementarCopiasAlumno,aplicarCopiasATodosSeleccionados"
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

                    <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-600 to-indigo-600"></div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                    Filtros de credenciales
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Selecciona el alcance de descarga y después aplica los filtros necesarios.
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

                        @if ($modo_descarga === 'nivel')
                            <div
                                class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="font-black">
                                            Descarga por nivel activo
                                        </p>

                                        <p class="mt-1 text-sm">
                                            Se generarán las credenciales de todos los alumnos registrados en el nivel
                                            {{ $nivel?->nombre ?? 'seleccionado' }}.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($this->esBachillerato())
                            <div
                                class="mb-5 rounded-2xl border border-violet-200 bg-violet-50/80 p-4 text-sm text-violet-800 dark:border-violet-900/60 dark:bg-violet-950/30 dark:text-violet-200">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-200">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="font-black">
                                            Modo bachillerato activo
                                        </p>

                                        <p class="mt-1 text-sm">
                                            Para descargar por semestre o grupo, primero selecciona generación, grado y
                                            semestre.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <flux:field>
                                <flux:label>Nivel</flux:label>
                                <flux:input readonly variant="filled" value="{{ $nivel?->nombre ?? '—' }}" disabled />
                            </flux:field>

                            <flux:field>
                                <flux:label>Modo de descarga</flux:label>

                                <flux:select id="modo_descarga" wire:model.live="modo_descarga"
                                    x-on:change="guardarScroll()">
                                    @foreach ($this->modosDescarga() as $valor => $texto)
                                        <flux:select.option value="{{ $valor }}">
                                            {{ $texto }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="modo_descarga" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ $modo_descarga === 'individual' ? 'Copias del alumno' : 'Copias predeterminadas' }}</flux:label>

                                <flux:select id="copias_por_alumno" wire:model.live="copias_por_alumno"
                                    x-on:change="guardarScroll()">
                                    @for ($cantidad = 1; $cantidad <= $this->maxCopiasPorAlumno(); $cantidad++)
                                        <flux:select.option value="{{ $cantidad }}">
                                            {{ $cantidad }} {{ $cantidad === 1 ? 'copia' : 'copias' }}
                                        </flux:select.option>
                                    @endfor
                                </flux:select>

                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    @if ($modo_descarga === 'individual')
                                        Cantidad de credenciales que se generarán para este alumno. Máximo {{ $this->maxCopiasPorAlumno() }}.
                                    @else
                                        Es la cantidad inicial. En la lista inferior puedes incluir o excluir alumnos y ajustar sus copias de forma individual.
                                    @endif
                                </p>
                            </flux:field>

                            <flux:field>
                                <flux:label>Generación</flux:label>

                                <flux:select id="generacion_id" wire:model.live="generacion_id"
                                    x-on:change="guardarScroll()" :disabled="$modo_descarga === 'nivel'">
                                    <flux:select.option value="">
                                        Selecciona una generación
                                    </flux:select.option>

                                    @foreach ($generaciones as $generacion)
                                        <flux:select.option value="{{ $generacion->id }}">
                                            {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="generacion_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Grado</flux:label>

                                <flux:select id="grado_id" wire:model.live="grado_id" x-on:change="guardarScroll()"
                                    :disabled="$modo_descarga === 'nivel' || !$generacion_id">
                                    <flux:select.option value="">
                                        Selecciona un grado
                                    </flux:select.option>

                                    @foreach ($grados as $grado)
                                        <flux:select.option value="{{ $grado->id }}">
                                            {{ $grado->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="grado_id" />
                            </flux:field>

                            @if ($this->esBachillerato())
                                <flux:field>
                                    <flux:label>Semestre</flux:label>

                                    <flux:select id="semestre_id" wire:model.live="semestre_id"
                                        x-on:change="guardarScroll()"
                                        :disabled="$modo_descarga === 'nivel' || !$generacion_id || !$grado_id || $semestres->isEmpty()">
                                        <flux:select.option value="">
                                            Selecciona un semestre
                                        </flux:select.option>

                                        @foreach ($semestres as $semestre)
                                            <flux:select.option value="{{ $semestre->id }}">
                                                {{ $this->textoSemestre($semestre) }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="semestre_id" />
                                </flux:field>
                            @endif

                            <flux:field>
                                <flux:label>Grupo</flux:label>

                                <flux:select id="grupo_id" wire:model.live="grupo_id" x-on:change="guardarScroll()"
                                    wire:key="credenciales-grupo-select-{{ $slug_nivel }}-{{ $generacion_id ?? 'null' }}-{{ $grado_id ?? 'null' }}-{{ $semestre_id ?? 'null' }}-{{ $grupos->count() }}"
                                    :disabled="$modo_descarga === 'nivel' || (
                                                                                                                $this->esBachillerato()
                                                                                                                    ? (!$generacion_id || !$grado_id || !$semestre_id || $grupos->isEmpty())
                                                                                                                    : (!$generacion_id || !$grado_id || $grupos->isEmpty())
                                                                                                            )">

                                    <flux:select.option value="">
                                        Selecciona un grupo
                                    </flux:select.option>

                                    @foreach ($grupos as $grupo)
                                        <flux:select.option value="{{ $grupo->id }}">
                                            {{ $this->textoGrupo($grupo) }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="grupo_id" />

                                @if ($modo_descarga !== 'nivel' && !$this->esBachillerato() && $generacion_id && $grado_id && $grupos->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        No hay grupos registrados para la generación y grado seleccionados.
                                    </p>
                                @endif

                                @if (
                                    $modo_descarga !== 'nivel' &&
                                        $this->esBachillerato() &&
                                        $generacion_id &&
                                        $grado_id &&
                                        $semestre_id &&
                                        $grupos->isEmpty())
                                    <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        No hay grupos registrados para la generación, grado y semestre seleccionados.
                                    </p>
                                @endif
                            </flux:field>

                            <flux:field>
                                <flux:label>Buscar alumno</flux:label>
                                <flux:input type="search" wire:model.live.debounce.400ms="buscar_alumno"
                                    x-on:input="guardarScroll()" placeholder="Nombre, apellidos o matrícula..." />
                            </flux:field>

                            @if ($modo_descarga === 'individual')
                                <flux:field>
                                    <flux:label>Alumno individual</flux:label>

                                    <flux:select id="alumno_individual_id" wire:model.live="alumno_individual_id"
                                        x-on:change="guardarScroll()">
                                        <flux:select.option value="">
                                            Selecciona un alumno
                                        </flux:select.option>

                                        @foreach ($this->alumnos as $alumno)
                                            <flux:select.option value="{{ $alumno->id }}">
                                                {{ $alumno->matricula ?? 'S/M' }} — {{ $this->nombreAlumno($alumno) }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="alumno_individual_id" />
                                </flux:field>
                            @endif
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                Modo: {{ $this->textoModoDescarga }}
                            </span>

                            <span
                                class="inline-flex items-center rounded-full border border-fuchsia-200 bg-fuchsia-50 px-3 py-1 text-xs font-bold text-fuchsia-700 dark:border-fuchsia-900/40 dark:bg-fuchsia-950/30 dark:text-fuchsia-300">
                                Copias por alumno: {{ $copias_por_alumno }}
                            </span>

                            @if ($this->tieneCopiasPersonalizadas)
                                <span
                                    class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Con cantidades individuales
                                </span>
                            @endif

                            @if ($modo_descarga === 'nivel')
                                <span
                                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    Nivel completo: {{ $nivel?->nombre ?? '—' }}
                                </span>
                            @endif

                            @if ($this->generacionSeleccionada && $modo_descarga !== 'nivel')
                                <span
                                    class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300">
                                    Generación:
                                    {{ $this->generacionSeleccionada->anio_ingreso }} -
                                    {{ $this->generacionSeleccionada->anio_egreso }}
                                </span>
                            @endif

                            @if ($this->gradoSeleccionado && $modo_descarga !== 'nivel')
                                <span
                                    class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                    Grado: {{ $this->gradoSeleccionado->nombre }}
                                </span>
                            @endif

                            @if ($this->esBachillerato() && $this->semestreSeleccionado && $modo_descarga !== 'nivel')
                                <span
                                    class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                    {{ $this->textoSemestre($this->semestreSeleccionado) }}
                                </span>
                            @endif

                            @if ($this->grupoSeleccionado && $modo_descarga !== 'nivel')
                                <span
                                    class="inline-flex items-center rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700 dark:border-cyan-900/40 dark:bg-cyan-950/30 dark:text-cyan-300">
                                    Grupo: {{ $this->textoGrupo($this->grupoSeleccionado) }}
                                </span>
                            @endif

                            @if ($modo_descarga === 'seleccionados')
                                <span
                                    class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Alumnos específicos: {{ count($alumnos_seleccionados) }}
                                </span>
                            @endif
                            @if (in_array($modo_descarga, ['nivel', 'generacion', 'grado', 'semestre', 'grupo'], true) && $this->alcanceConfigurado)
                                <span
                                    class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Seleccionados: {{ count($alumnos_seleccionados) }} / {{ $this->cantidadAlumnosAlcance }}
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

                        @if (in_array($modo_descarga, ['nivel', 'generacion', 'grado', 'semestre', 'grupo'], true) && $this->alcanceConfigurado)
                            <div class="mt-6 overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-emerald-50/60 p-4 dark:border-neutral-800 dark:from-neutral-950 dark:to-emerald-950/20 sm:p-5">
                                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                        <div class="flex items-start gap-3">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                <flux:icon.users class="h-5 w-5" />
                                            </div>

                                            <div>
                                                <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                                    Alumnos incluidos en las credenciales
                                                </h4>
                                                <p class="mt-1 text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">
                                                    Se encontraron {{ $this->cantidadAlumnosAlcance }} alumno(s) en el alcance.
                                                    Marca quiénes deben generar credencial y define las copias de cada alumno.
                                                    Un alumno desmarcado no se incluirá en el PDF ni en el ZIP.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" wire:click="seleccionarTodosAlcance"
                                                x-on:click="guardarScroll()"
                                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-black text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                <flux:icon.check class="h-4 w-4" />
                                                Seleccionar todos
                                            </button>

                                            <button type="button" wire:click="limpiarSeleccion"
                                                x-on:click="guardarScroll()"
                                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-black text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
                                                <flux:icon.x-mark class="h-4 w-4" />
                                                Quitar todos
                                            </button>

                                            <button type="button" wire:click="aplicarCopiasATodosSeleccionados"
                                                x-on:click="guardarScroll()"
                                                @disabled(count($alumnos_seleccionados) === 0)
                                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-slate-900">
                                                <flux:icon.squares-2x2 class="h-4 w-4" />
                                                Aplicar {{ $copias_por_alumno }} a seleccionados
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            {{ count($alumnos_seleccionados) }} seleccionado(s)
                                        </span>

                                        <span class="inline-flex items-center rounded-full border border-fuchsia-200 bg-fuchsia-50 px-3 py-1 text-xs font-black text-fuchsia-700 dark:border-fuchsia-900/50 dark:bg-fuchsia-950/30 dark:text-fuchsia-300">
                                            {{ $this->totalCredenciales }} credencial(es)
                                        </span>

                                        @if (trim($buscar_alumno) !== '')
                                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                                                Mostrando coincidencias de “{{ $buscar_alumno }}”
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="max-h-[520px] overflow-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                                        <thead class="sticky top-0 z-10 bg-slate-100/95 backdrop-blur dark:bg-neutral-950/95">
                                            <tr>
                                                <th class="w-20 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                    Generar
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                    Alumno
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                    Matrícula
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                    Ubicación
                                                </th>
                                                <th class="w-44 px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                    Copias
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                                            @forelse ($this->alumnosAlcance as $alumno)
                                                @php($incluido = $this->alumnoSeleccionado((int) $alumno->id))
                                                <tr wire:key="alcance-credencial-alumno-{{ $alumno->id }}"
                                                    class="transition {{ $incluido ? 'hover:bg-emerald-50/50 dark:hover:bg-neutral-800/70' : 'bg-slate-50/70 opacity-70 dark:bg-neutral-950/40' }}">
                                                    <td class="px-4 py-3 text-center">
                                                        <input type="checkbox" value="{{ $alumno->id }}"
                                                            wire:model.live="alumnos_seleccionados"
                                                            x-on:change="guardarScroll()"
                                                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 dark:border-neutral-700">
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        <p class="font-black text-slate-900 dark:text-white">
                                                            {{ $this->nombreAlumno($alumno) }}
                                                        </p>
                                                        @if ($alumno->generacion)
                                                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                                Generación {{ $alumno->generacion->anio_ingreso }} - {{ $alumno->generacion->anio_egreso }}
                                                            </p>
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-3 font-bold text-slate-700 dark:text-slate-200">
                                                        {{ $alumno->matricula ?? 'S/M' }}
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        <div class="flex flex-wrap gap-1.5">
                                                            <span class="rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-black text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/40">
                                                                {{ $alumno->grado?->nombre ?? 'Sin grado' }}
                                                            </span>
                                                            <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-[11px] font-black text-cyan-700 ring-1 ring-cyan-100 dark:bg-cyan-950/30 dark:text-cyan-300 dark:ring-cyan-900/40">
                                                                Grupo {{ $alumno->grupo?->asignacionGrupo?->nombre ?? '—' }}
                                                            </span>
                                                            @if ($this->esBachillerato() && $alumno->semestre)
                                                                <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-black text-violet-700 ring-1 ring-violet-100 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/40">
                                                                    {{ $this->textoSemestre($alumno->semestre) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        @if ($incluido)
                                                            <div class="flex items-center justify-center gap-1.5">
                                                                <button type="button"
                                                                    wire:click="decrementarCopiasAlumno({{ $alumno->id }})"
                                                                    x-on:click="guardarScroll()"
                                                                    @disabled($this->copiasAlumno((int) $alumno->id) <= 1)
                                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                                    <flux:icon.minus class="h-4 w-4" />
                                                                </button>

                                                                <span class="inline-flex min-w-11 items-center justify-center rounded-xl bg-emerald-100 px-3 py-2 text-sm font-black text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                                    {{ $this->copiasAlumno((int) $alumno->id) }}
                                                                </span>

                                                                <button type="button"
                                                                    wire:click="incrementarCopiasAlumno({{ $alumno->id }})"
                                                                    x-on:click="guardarScroll()"
                                                                    @disabled($this->copiasAlumno((int) $alumno->id) >= $this->maxCopiasPorAlumno())
                                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                                    <flux:icon.plus class="h-4 w-4" />
                                                                </button>
                                                            </div>
                                                        @else
                                                            <div class="text-center">
                                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-400">
                                                                    No generar
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-4 py-12 text-center">
                                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-neutral-800 dark:text-slate-300">
                                                            <flux:icon.magnifying-glass class="h-5 w-5" />
                                                        </div>
                                                        <p class="mt-3 font-black text-slate-700 dark:text-slate-200">
                                                            No hay alumnos que coincidan con la búsqueda.
                                                        </p>
                                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                            Limpia el buscador para volver a mostrar todos los alumnos del alcance.
                                                        </p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if ($modo_descarga === 'seleccionados')
                            <div class="mt-6 rounded-2xl border border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                        <flux:icon.users class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">
                                            Generar solo para alumnos específicos
                                        </p>
                                        <p class="mt-1 text-xs font-semibold leading-5 text-slate-600 dark:text-slate-300">
                                            Filtra por nivel, generación, grado o grupo y marca únicamente a los alumnos que necesites.
                                            Los alumnos que no estén marcados no generarán ninguna credencial. A cada seleccionado puedes asignarle
                                            una cantidad distinta de copias.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-12">
                                <div
                                    class="overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900 xl:col-span-7">
                                    <div
                                        class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 class="text-sm font-black text-slate-900 dark:text-white">
                                                Resultados de búsqueda
                                            </h4>

                                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                Marca alumnos desde la búsqueda. Los seleccionados se conservarán aunque
                                                busques otro alumno.
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
                                                        Matrícula
                                                    </th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                        Alumno
                                                    </th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                        Grupo
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                                @forelse ($this->alumnos as $alumno)
                                                    <tr wire:key="resultado-alumno-{{ $alumno->id }}"
                                                        class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/60">
                                                        <td class="px-4 py-3">
                                                            <input type="checkbox" value="{{ $alumno->id }}"
                                                                wire:model.live="alumnos_seleccionados"
                                                                x-on:change="guardarScroll()"
                                                                class="h-4 w-4 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 dark:border-neutral-700">
                                                        </td>

                                                        <td
                                                            class="px-4 py-3 font-bold text-slate-700 dark:text-slate-200">
                                                            {{ $alumno->matricula ?? 'S/M' }}
                                                        </td>

                                                        <td class="px-4 py-3">
                                                            <p class="font-black text-slate-900 dark:text-white">
                                                                {{ $this->nombreAlumno($alumno) }}
                                                            </p>

                                                            <div class="mt-1 flex flex-wrap gap-1.5">
                                                                @if ($alumno->generacion)
                                                                    <span
                                                                        class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-black text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900/40">
                                                                        {{ $alumno->generacion->anio_ingreso }} -
                                                                        {{ $alumno->generacion->anio_egreso }}
                                                                    </span>
                                                                @endif

                                                                @if ($alumno->grado)
                                                                    <span
                                                                        class="rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-black text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/40">
                                                                        {{ $alumno->grado->nombre }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </td>

                                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                            {{ $alumno->grupo?->asignacionGrupo?->nombre ?? '—' }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="px-4 py-10 text-center">
                                                            <p class="font-black text-slate-700 dark:text-slate-200">
                                                                No hay alumnos para mostrar.
                                                            </p>

                                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                                Ajusta los filtros o escribe en el buscador.
                                                            </p>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div
                                    class="overflow-hidden rounded-[1.4rem] border border-emerald-200 bg-emerald-50/50 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/10 xl:col-span-5">
                                    <div
                                        class="border-b border-emerald-200 bg-gradient-to-r from-emerald-500 via-sky-600 to-indigo-600 p-4 text-white dark:border-emerald-900/50">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <h4 class="text-sm font-black">
                                                    Alumnos específicos a generar
                                                </h4>

                                                <p class="mt-1 text-xs font-semibold text-white/80">
                                                    {{ count($alumnos_seleccionados) }} alumno(s) seleccionado(s). Los demás no se incluirán.
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap items-center justify-end gap-2">
                                                <button type="button" wire:click="aplicarCopiasATodosSeleccionados"
                                                    x-on:click="guardarScroll()"
                                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-black text-white ring-1 ring-white/20 transition hover:bg-white/25">
                                                    <flux:icon.squares-plus class="h-4 w-4" />
                                                    Aplicar {{ $copias_por_alumno }} a seleccionados
                                                </button>

                                                <button type="button" wire:click="limpiarSeleccion"
                                                    x-on:click="guardarScroll()"
                                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-black text-white ring-1 ring-white/20 transition hover:bg-white/25">
                                                    <flux:icon.x-mark class="h-4 w-4" />
                                                    Limpiar
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="max-h-[430px] overflow-y-auto">
                                        <table
                                            class="min-w-full divide-y divide-emerald-100 text-sm dark:divide-neutral-800">
                                            <thead class="sticky top-0 z-10 bg-emerald-50 dark:bg-neutral-950">
                                                <tr>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                                        Alumno
                                                    </th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                                        Matrícula
                                                    </th>
                                                    <th
                                                        class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                                        Copias
                                                    </th>
                                                    <th class="w-12 px-4 py-3 text-right"></th>
                                                </tr>
                                            </thead>

                                            <tbody
                                                class="divide-y divide-emerald-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                                                @forelse ($this->alumnosSeleccionadosLista as $alumno)
                                                    <tr wire:key="seleccionado-alumno-{{ $alumno->id }}"
                                                        class="transition hover:bg-emerald-50/70 dark:hover:bg-neutral-800/60">
                                                        <td class="px-4 py-3">
                                                            <p class="font-black text-slate-900 dark:text-white">
                                                                {{ $this->nombreAlumno($alumno) }}
                                                            </p>

                                                            <p
                                                                class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                                {{ $alumno->grado?->nombre ?? 'Sin grado' }}
                                                                ·
                                                                {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo' }}
                                                            </p>
                                                        </td>

                                                        <td
                                                            class="px-4 py-3 font-bold text-slate-700 dark:text-slate-200">
                                                            {{ $alumno->matricula ?? 'S/M' }}
                                                        </td>

                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center justify-center gap-1.5">
                                                                <button type="button"
                                                                    wire:click="decrementarCopiasAlumno({{ $alumno->id }})"
                                                                    x-on:click="guardarScroll()"
                                                                    @disabled($this->copiasAlumno((int) $alumno->id) <= 1)
                                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                                    <flux:icon.minus class="h-3.5 w-3.5" />
                                                                </button>

                                                                <span
                                                                    class="inline-flex min-w-9 items-center justify-center rounded-lg bg-emerald-100 px-2 py-1.5 text-xs font-black text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                                    {{ $this->copiasAlumno((int) $alumno->id) }}
                                                                </span>

                                                                <button type="button"
                                                                    wire:click="incrementarCopiasAlumno({{ $alumno->id }})"
                                                                    x-on:click="guardarScroll()"
                                                                    @disabled($this->copiasAlumno((int) $alumno->id) >= $this->maxCopiasPorAlumno())
                                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                                    <flux:icon.plus class="h-3.5 w-3.5" />
                                                                </button>
                                                            </div>
                                                        </td>

                                                        <td class="px-4 py-3 text-right">
                                                            <button type="button"
                                                                wire:click="quitarAlumnoSeleccionado({{ $alumno->id }})"
                                                                x-on:click="guardarScroll()"
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
                                                                <flux:icon.trash class="h-4 w-4" />
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="px-4 py-10 text-center">
                                                            <div
                                                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                                <flux:icon.user-plus class="h-5 w-5" />
                                                            </div>

                                                            <p
                                                                class="mt-3 font-black text-slate-700 dark:text-slate-200">
                                                                Todavía no hay alumnos seleccionados.
                                                            </p>

                                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                                Marca alumnos desde la tabla de resultados.
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
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        <flux:icon.information-circle class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">
                                            Estado de la descarga
                                        </p>

                                        @if ($this->puedeDescargar)
                                            <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">
                                                Listo para generar: <strong>{{ $this->cantidadAlumnosDescarga }}</strong> alumno(s) ·
                                                <strong>{{ $this->totalCredenciales }}</strong> credencial(es).
                                            </p>

                                            @if (! $this->tieneCopiasPersonalizadas)
                                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                    {{ $this->cantidadAlumnosDescarga }} alumno(s) × {{ $copias_por_alumno }}
                                                    {{ $copias_por_alumno === 1 ? 'copia' : 'copias' }} =
                                                    {{ $this->totalCredenciales }} credencial(es).
                                                    Alcance: {{ $this->textoModoDescarga }}.
                                                </p>
                                            @else
                                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                    Alcance: {{ $this->textoModoDescarga }}. Hay cantidades personalizadas por alumno.
                                                </p>
                                            @endif

                                            @if ($this->totalCredenciales > $this->umbralConfirmacionCopias())
                                                <p class="mt-2 text-xs font-bold text-rose-600 dark:text-rose-400">
                                                    Descarga grande: se generarán {{ $this->totalCredenciales }} credenciales.
                                                    El sistema pedirá confirmación antes de continuar.
                                                </p>
                                            @endif

                                            @if ($this->totalCredenciales > $this->maxImagenesPorZip())
                                                <p class="mt-2 text-xs font-bold text-amber-700 dark:text-amber-300">
                                                    Para PNG/JPG el máximo por ZIP es {{ $this->maxImagenesPorZip() }} credenciales.
                                                    Puedes generar el PDF completo o dividir la descarga de imágenes.
                                                </p>
                                            @endif

                                            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                                Si algún alumno no tiene fotografía, se generará con el espacio “FOTO + SELLO” y el ZIP incluirá una advertencia.
                                            </p>
                                        @else
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                Completa los campos necesarios según el modo de descarga.
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if ($this->puedeDescargar)
                                    <div x-data="{ formatos: false, previa: false }" class="relative flex flex-wrap items-center justify-end gap-2">
                                        <button type="button" x-on:click="previa = true"
                                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            <flux:icon.eye class="h-5 w-5" />
                                            Vista previa
                                        </button>

                                        <div class="relative">
                                            <button type="button" x-on:click="formatos = !formatos"
                                                x-on:click.outside="formatos = false"
                                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 via-sky-600 to-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-500/20 transition hover:-translate-y-0.5 hover:shadow-xl">
                                                <flux:icon.document-arrow-down class="h-5 w-5" />
                                                Descargar {{ $this->totalCredenciales }}
                                                <flux:icon.chevron-down class="h-4 w-4" />
                                            </button>

                                            <div x-cloak x-show="formatos" x-transition
                                                class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">
                                                <a href="{{ $this->urlDescarga }}" target="_blank"
                                                    x-on:click="if ({{ $this->totalCredenciales }} > {{ $this->umbralConfirmacionCopias() }} && !window.confirm('Se generarán {{ $this->totalCredenciales }} credenciales para {{ $this->cantidadAlumnosDescarga }} alumno(s). ¿Deseas continuar?')) { $event.preventDefault(); }"
                                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                    <flux:icon.document-text class="h-5 w-5 text-rose-500" />
                                                    Formato PDF
                                                </a>
                                                @if ($this->totalCredenciales <= $this->maxImagenesPorZip())
                                                    <a href="{{ $this->urlDescargaPng }}"
                                                        x-on:click="if ({{ $this->totalCredenciales }} > {{ $this->umbralConfirmacionCopias() }} && !window.confirm('Se generarán {{ $this->totalCredenciales }} credenciales para {{ $this->cantidadAlumnosDescarga }} alumno(s). ¿Deseas continuar?')) { $event.preventDefault(); }"
                                                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                        <flux:icon.photo class="h-5 w-5 text-emerald-500" />
                                                        Imagen PNG
                                                    </a>
                                                    <a href="{{ $this->urlDescargaJpg }}"
                                                        x-on:click="if ({{ $this->totalCredenciales }} > {{ $this->umbralConfirmacionCopias() }} && !window.confirm('Se generarán {{ $this->totalCredenciales }} credenciales para {{ $this->cantidadAlumnosDescarga }} alumno(s). ¿Deseas continuar?')) { $event.preventDefault(); }"
                                                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                        <flux:icon.photo class="h-5 w-5 text-sky-500" />
                                                        Imagen JPG · 100%
                                                    </a>
                                                @else
                                                    <div class="rounded-xl px-3 py-2.5 text-xs font-bold text-amber-700 dark:text-amber-300">
                                                        PNG/JPG no disponible: supera {{ $this->maxImagenesPorZip() }} imágenes.
                                                    </div>
                                                @endif
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
                                                            <p class="font-black text-slate-900 dark:text-white">Vista previa de credencial</p>
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
