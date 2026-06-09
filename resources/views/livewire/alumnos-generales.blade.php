<div x-data="{
    mostrarFiltros: true,
    editando: false,

    abrirEdicion(url) {
        this.editando = true;

        setTimeout(() => {
            window.location.href = url;
        }, 250);
    }
}" class="space-y-6">

    {{-- Loader para edición --}}
    <div x-cloak x-show="editando" x-transition.opacity
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/80 backdrop-blur-md dark:bg-neutral-950/80">
        <div
            class="mx-4 w-full max-w-sm rounded-[28px] border border-sky-100 bg-white p-7 text-center shadow-2xl shadow-sky-500/20 dark:border-sky-900/40 dark:bg-neutral-900">

            <div class="relative mx-auto mb-5 flex h-20 w-20 items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-sky-100 dark:border-sky-900/40"></div>
                <div
                    class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-sky-500 border-r-indigo-500">
                </div>

                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/30">
                    <flux:icon.square-pen class="h-5 w-5" />
                </div>
            </div>

            <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                Abriendo edición
            </h3>

            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Preparando la información del alumno...
            </p>
        </div>
    </div>

    {{-- Encabezado --}}
    <section
        class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

        <div
            class="relative overflow-hidden bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-700 px-6 py-7 text-white sm:px-8">
            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-16 -left-10 h-44 w-44 rounded-full bg-white/10 blur-2xl"></div>

            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-bold">
                        <flux:icon.users class="h-4 w-4" />
                        Consulta general
                    </div>

                    <h1 class="mt-3 text-2xl font-black tracking-tight sm:text-3xl">
                        Alumnos generales
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm text-white/80">
                        Consulta todos los alumnos de todos los niveles con filtros ligeros, paginación y carga
                        optimizada.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5 lg:min-w-[620px]">
                    <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-white/70">Total</p>
                        <p class="mt-1 text-2xl font-black">{{ $total }}</p>
                    </div>

                    <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-white/70">Hombres</p>
                        <p class="mt-1 text-2xl font-black">{{ $hombres }}</p>
                    </div>

                    <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-white/70">Mujeres</p>
                        <p class="mt-1 text-2xl font-black">{{ $mujeres }}</p>
                    </div>

                    <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-white/70">Activos</p>
                        <p class="mt-1 text-2xl font-black">{{ $activos }}</p>
                    </div>

                    <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-white/70">Bajas</p>
                        <p class="mt-1 text-2xl font-black">{{ $bajas }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-900/60">
            <button type="button" x-on:click="mostrarFiltros = !mostrarFiltros"
                class="flex w-full items-center justify-between gap-3 text-left">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-sky-600 shadow-sm ring-1 ring-slate-200 dark:bg-neutral-800 dark:ring-neutral-700">
                        <flux:icon.funnel class="h-5 w-5" />
                    </span>

                    <div>
                        <h2 class="text-sm font-black text-slate-800 dark:text-white">
                            Filtros de búsqueda
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Filtra sin cargar datos innecesarios.
                        </p>
                    </div>
                </div>

                <span
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300"
                    x-bind:class="mostrarFiltros ? 'rotate-180 text-sky-600' : ''">
                    <flux:icon.chevron-down class="h-5 w-5" />
                </span>
            </button>

            <div x-show="mostrarFiltros" x-collapse class="mt-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

                    <flux:field>
                        <flux:label>Buscar alumno</flux:label>
                        <flux:input wire:model.live.debounce.500ms="buscar"
                            placeholder="Nombre, matrícula, CURP o folio..." />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nivel</flux:label>
                        <flux:select wire:model.live="nivel_id">
                            <flux:select.option value="">Todos los niveles</flux:select.option>

                            @foreach ($niveles as $nivel)
                                <flux:select.option value="{{ $nivel->id }}">
                                    {{ $nivel->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Grado</flux:label>
                        <flux:select wire:model.live="grado_id" :disabled="!$nivel_id">
                            <flux:select.option value="">
                                {{ $nivel_id ? 'Todos los grados' : 'Primero selecciona un nivel' }}
                            </flux:select.option>

                            @foreach ($grados as $grado)
                                <flux:select.option value="{{ $grado->id }}">
                                    {{ $grado->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Generación</flux:label>
                        <flux:select wire:model.live="generacion_id">
                            <flux:select.option value="">Todas las generaciones</flux:select.option>

                            @foreach ($generaciones as $generacion)
                                <flux:select.option value="{{ $generacion->id }}">
                                    {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                    @if (!$nivel_id)
                                        / {{ $generacion->nivel?->nombre }}
                                    @endif
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    @if ($this->esBachillerato())
                        <flux:field>
                            <flux:label>Semestre</flux:label>
                            <flux:select wire:model.live="semestre_id" :disabled="!$grado_id">
                                <flux:select.option value="">
                                    {{ $grado_id ? 'Todos los semestres' : 'Primero selecciona un grado' }}
                                </flux:select.option>

                                @foreach ($semestres as $semestre)
                                    <flux:select.option value="{{ $semestre->id }}">
                                        {{ $semestre->numero }}° semestre
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:label>Grupo</flux:label>
                        <flux:select wire:model.live="grupo_id" :disabled="!$nivel_id || !$grado_id || !$generacion_id">
                            <flux:select.option value="">
                                {{ $nivel_id && $grado_id && $generacion_id ? 'Todos los grupos' : 'Selecciona nivel, grado y generación' }}
                            </flux:select.option>

                            @foreach ($grupos as $grupo)
                                <flux:select.option value="{{ $grupo->id }}">
                                    Grupo {{ $this->textoGrupo($grupo) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Ciclo</flux:label>
                        <flux:select wire:model.live="ciclo_id">
                            <flux:select.option value="">Todos los ciclos</flux:select.option>

                            @foreach ($ciclos as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->ciclo }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Género</flux:label>
                        <flux:select wire:model.live="genero">
                            <flux:select.option value="">Todos</flux:select.option>
                            <flux:select.option value="H">Hombres</flux:select.option>
                            <flux:select.option value="M">Mujeres</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Estatus</flux:label>
                        <flux:select wire:model.live="estatus">
                            <flux:select.option value="todos">Todos</flux:select.option>
                            <flux:select.option value="activos">Activos</flux:select.option>
                            <flux:select.option value="bajas">Bajas</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Ordenar por</flux:label>
                        <flux:select wire:model.live="orden">
                            <flux:select.option value="apellidos">Apellidos</flux:select.option>
                            <flux:select.option value="matricula">Matrícula</flux:select.option>
                            <flux:select.option value="nivel">Nivel</flux:select.option>
                            <flux:select.option value="recientes">Más recientes</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Mostrar</flux:label>
                        <flux:select wire:model.live="perPage">
                            <flux:select.option value="10">10 registros</flux:select.option>
                            <flux:select.option value="25">25 registros</flux:select.option>
                            <flux:select.option value="50">50 registros</flux:select.option>
                            <flux:select.option value="100">100 registros</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Mostrando resultados paginados para evitar cargas pesadas.
                    </p>

                    <flux:button type="button" variant="ghost" wire:click="limpiarFiltros" class="cursor-pointer">
                        <flux:icon.x-mark class="h-4 w-4" />
                        Limpiar filtros
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Loader Livewire --}}
        <div wire:loading.delay
            class="border-b border-slate-200 bg-sky-50 px-5 py-3 text-sm font-semibold text-sky-700 dark:border-neutral-800 dark:bg-sky-950/30 dark:text-sky-300">
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-sky-500"></span>
                Actualizando alumnos...
            </div>
        </div>

        {{-- Tabla escritorio --}}
        <div class="hidden xl:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                    <thead
                        class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-neutral-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-4 text-left font-black">#</th>
                            <th class="px-4 py-4 text-left font-black">Foto</th>
                            <th class="px-4 py-4 text-left font-black">Alumno</th>
                            <th class="px-4 py-4 text-left font-black">Matrícula</th>
                            <th class="px-4 py-4 text-left font-black">CURP</th>
                            <th class="px-4 py-4 text-left font-black">Nivel</th>
                            <th class="px-4 py-4 text-left font-black">Grado</th>
                            <th class="px-4 py-4 text-left font-black">Semestre</th>
                            <th class="px-4 py-4 text-left font-black">Grupo</th>
                            <th class="px-4 py-4 text-left font-black">Generación</th>
                            <th class="px-4 py-4 text-left font-black">Ciclo</th>
                            <th class="px-4 py-4 text-left font-black">Estatus</th>
                            <th class="px-4 py-4 text-right font-black">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                        @forelse ($alumnos as $alumno)
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/60">
                                <td class="px-4 py-4 text-sm text-slate-500 dark:text-slate-400">
                                    {{ $loop->iteration + ($alumnos->currentPage() - 1) * $alumnos->perPage() }}
                                </td>

                                <td class="px-4 py-4">
                                    @if ($alumno->foto_path)
                                        <img src="{{ asset('storage/' . $alumno->foto_path) }}"
                                            alt="Foto de {{ $alumno->nombre }}"
                                            class="h-11 w-11 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-neutral-700">
                                    @else
                                        <div
                                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 ring-1 ring-slate-200 dark:bg-neutral-800 dark:ring-neutral-700">
                                            <flux:icon.user class="h-5 w-5" />
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <p class="font-black text-slate-800 dark:text-white">
                                        {{ $this->nombreCompleto($alumno) }}
                                    </p>

                                    <div class="mt-1 flex flex-wrap items-center gap-2">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[11px] font-bold
                                            {{ $alumno->genero === 'H'
                                                ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300'
                                                : 'bg-pink-100 text-pink-700 dark:bg-pink-950/40 dark:text-pink-300' }}">
                                            {{ $alumno->genero === 'H' ? 'Hombre' : 'Mujer' }}
                                        </span>

                                        @if ($alumno->folio)
                                            <span
                                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                                Folio: {{ $alumno->folio }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-sm font-bold text-slate-700 dark:text-slate-200">
                                    {{ $alumno->matricula ?: '—' }}
                                </td>

                                <td class="px-4 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    {{ $alumno->curp ?: '—' }}
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $alumno->nivel?->nombre ?? '—' }}
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $alumno->grado?->nombre ?? '—' }}
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $alumno->semestre ? $alumno->semestre->numero . '°' : '—' }}
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $this->textoGrupo($alumno->grupo) }}
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $this->textoGeneracion($alumno->generacion) }}
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $alumno->ciclo?->ciclo ?? '—' }}
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-black
                                        {{ $alumno->activo
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                            : 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' }}">
                                        {{ $alumno->activo ? 'Activo' : 'Baja' }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    @if ($alumno->nivel?->slug)
                                        <flux:button type="button" variant="primary"
                                            class="cursor-pointer bg-amber-500 text-white hover:bg-amber-600"
                                            x-on:click="abrirEdicion('{{ route('misrutas.matricula.editar', ['slug_nivel' => $alumno->nivel->slug, 'inscripcion' => $alumno->id]) }}')">
                                            <flux:icon.square-pen class="h-4 w-4" />
                                        </flux:button>
                                    @else
                                        <span class="text-xs text-slate-400">Sin ruta</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-6 py-12 text-center">
                                    <div class="mx-auto max-w-sm">
                                        <div
                                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                                            <flux:icon.inbox class="h-7 w-7" />
                                        </div>

                                        <h3 class="mt-4 text-base font-black text-slate-800 dark:text-white">
                                            No se encontraron alumnos
                                        </h3>

                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            Ajusta los filtros o limpia la búsqueda.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tarjetas móvil/tablet --}}
        <div class="space-y-4 p-4 xl:hidden">
            @forelse ($alumnos as $alumno)
                <article
                    class="rounded-[1.7rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

                    <div class="flex items-start gap-3">
                        @if ($alumno->foto_path)
                            <img src="{{ asset('storage/' . $alumno->foto_path) }}"
                                alt="Foto de {{ $alumno->nombre }}"
                                class="h-14 w-14 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-neutral-700">
                        @else
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 ring-1 ring-slate-200 dark:bg-neutral-800 dark:ring-neutral-700">
                                <flux:icon.user class="h-6 w-6" />
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-black text-slate-800 dark:text-white">
                                {{ $this->nombreCompleto($alumno) }}
                            </h3>

                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Matrícula: {{ $alumno->matricula ?: '—' }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold
                                    {{ $alumno->activo
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                        : 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' }}">
                                    {{ $alumno->activo ? 'Activo' : 'Baja' }}
                                </span>

                                <span
                                    class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                    {{ $alumno->genero === 'H' ? 'Hombre' : 'Mujer' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                        <p><span class="font-bold">CURP:</span> {{ $alumno->curp ?: '—' }}</p>
                        <p><span class="font-bold">Nivel:</span> {{ $alumno->nivel?->nombre ?? '—' }}</p>
                        <p><span class="font-bold">Grado:</span> {{ $alumno->grado?->nombre ?? '—' }}</p>
                        <p><span class="font-bold">Semestre:</span>
                            {{ $alumno->semestre ? $alumno->semestre->numero . '°' : '—' }}</p>
                        <p><span class="font-bold">Grupo:</span> {{ $this->textoGrupo($alumno->grupo) }}</p>
                        <p><span class="font-bold">Generación:</span>
                            {{ $this->textoGeneracion($alumno->generacion) }}</p>
                        <p><span class="font-bold">Ciclo:</span> {{ $alumno->ciclo?->ciclo ?? '—' }}</p>
                        <p><span class="font-bold">Folio:</span> {{ $alumno->folio ?: '—' }}</p>
                    </div>

                    <div class="mt-4 flex justify-end">
                        @if ($alumno->nivel?->slug)
                            <flux:button type="button" variant="primary"
                                class="cursor-pointer bg-amber-500 text-white hover:bg-amber-600"
                                x-on:click="abrirEdicion('{{ route('misrutas.matricula.editar', ['slug_nivel' => $alumno->nivel->slug, 'inscripcion' => $alumno->id]) }}')">
                                <flux:icon.square-pen class="h-4 w-4" />
                                Editar
                            </flux:button>
                        @endif
                    </div>
                </article>
            @empty
                <div
                    class="rounded-[1.7rem] border border-dashed border-slate-300 bg-white p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                        <flux:icon.inbox class="h-7 w-7" />
                    </div>

                    <h3 class="mt-4 text-base font-black text-slate-800 dark:text-white">
                        No se encontraron alumnos
                    </h3>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Ajusta los filtros o limpia la búsqueda.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Paginación --}}
        <div class="border-t border-slate-200 px-5 py-4 dark:border-neutral-800">
            {{ $alumnos->links() }}
        </div>
    </section>
</div>
