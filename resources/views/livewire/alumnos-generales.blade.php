<div x-data="{
    editando: false,

    abrirEdicion(url) {
        this.editando = true;

        setTimeout(() => {
            window.location.href = url;
        }, 250);
    }
}" class="space-y-6">
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- Loader de edición --}}
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
            class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-sky-600 to-indigo-700 px-6 py-7 text-white sm:px-8">
            <div class="absolute -right-10 -top-10 h-44 w-44 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-16 -left-10 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>

            <div class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-black uppercase tracking-wide">
                        <flux:icon.users class="h-4 w-4" />
                        Consulta general
                    </div>

                    <h1 class="mt-3 text-2xl font-black tracking-tight sm:text-3xl">
                        Alumnos generales
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm text-white/80">
                        Consulta alumnos de todos los niveles con filtros ligeros, paginación y detalles desplegables.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5 xl:min-w-[620px]">
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
        <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-5 dark:border-neutral-800 dark:bg-neutral-900/70">
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
                                {{ $generacion->anio_ingreso }}-{{ $generacion->anio_egreso }}
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
                    La consulta usa paginación y relaciones ligeras para evitar que se vuelva pesada.
                </p>

                <flux:button type="button" variant="ghost" wire:click="limpiarFiltros" class="cursor-pointer">
                    <flux:icon.x-mark class="h-4 w-4" />
                    Limpiar filtros
                </flux:button>
            </div>
        </div>

        {{-- Loader Livewire --}}
        <div wire:loading.delay
            class="border-b border-slate-200 bg-sky-50 px-5 py-3 text-sm font-bold text-sky-700 dark:border-neutral-800 dark:bg-sky-950/30 dark:text-sky-300">
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-sky-500"></span>
                Actualizando alumnos...
            </div>
        </div>

        {{-- Tabla escritorio --}}
        <div class="hidden overflow-x-auto xl:block">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                <thead
                    class="bg-white text-xs uppercase tracking-wide text-slate-500 dark:bg-neutral-900 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-4 text-left font-black">#</th>
                        <th class="px-4 py-4 text-left font-black">Foto</th>
                        <th class="px-4 py-4 text-left font-black">Matrícula</th>
                        <th class="px-4 py-4 text-left font-black">Folio</th>
                        <th class="px-4 py-4 text-left font-black">CURP</th>
                        <th class="px-4 py-4 text-left font-black">Alumno</th>
                        <th class="px-4 py-4 text-center font-black">Sexo</th>
                        <th class="px-4 py-4 text-left font-black">Grado / Semestre</th>
                        <th class="px-4 py-4 text-left font-black">Generación</th>
                        <th class="px-4 py-4 text-center font-black">Estatus</th>
                        <th class="px-4 py-4 text-right font-black">Acciones</th>
                    </tr>
                </thead>

                <tbody x-data="{ detalleAbierto: null }"
                    class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                    @forelse ($alumnos as $alumno)
                        <tr class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/60">
                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-400">
                                {{ $loop->iteration + ($alumnos->currentPage() - 1) * $alumnos->perPage() }}
                            </td>

                            <td class="px-4 py-4">
                                @if ($alumno->foto_path)
                                    <img src="{{ asset('storage/' . $alumno->foto_path) }}"
                                        alt="Foto de {{ $alumno->nombre }}"
                                        class="h-10 w-10 rounded-full object-cover ring-2 ring-slate-200 dark:ring-neutral-700">
                                @else
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 ring-2 ring-slate-200 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700">
                                        <flux:icon.user class="h-5 w-5" />
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4 text-sm font-bold text-slate-800 dark:text-white">
                                {{ $alumno->matricula ?: '—' }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-500 dark:text-slate-400">
                                {{ $alumno->folio ?: '—' }}
                            </td>

                            <td class="px-4 py-4 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ $alumno->curp ?: '—' }}
                            </td>

                            <td class="px-4 py-4">
                                <p class="max-w-[280px] text-sm font-black uppercase text-slate-900 dark:text-white">
                                    {{ $this->nombreCompleto($alumno) }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $alumno->nivel?->nombre ?? 'Sin nivel' }}
                                    @if ($alumno->grupo)
                                        · Grupo {{ $this->textoGrupo($alumno->grupo) }}
                                    @endif
                                </p>
                            </td>

                            <td class="px-4 py-4 text-center text-sm font-bold text-slate-800 dark:text-white">
                                {{ $alumno->genero ?: '—' }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">
                                @if ($alumno->semestre)
                                    {{ $alumno->semestre->numero }}° semestre
                                @else
                                    {{ $alumno->grado?->nombre ?? '—' }}
                                @endif
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">
                                {{ $this->textoGeneracion($alumno->generacion) }}
                            </td>

                            <td class="px-4 py-4 text-center">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-black
                                    {{ $alumno->activo
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                        : 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' }}">
                                    {{ $alumno->activo ? 'Activo' : 'Baja' }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button"
                                        x-on:click="detalleAbierto = detalleAbierto === {{ $alumno->id }} ? null : {{ $alumno->id }}"
                                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 px-4 py-2 text-xs font-black text-white shadow-sm transition hover:from-sky-600 hover:to-indigo-700">
                                        <flux:icon.chevron-down class="h-4 w-4 transition-transform duration-300"
                                            x-bind:class="detalleAbierto === {{ $alumno->id }} ? 'rotate-180' : ''" />

                                        <span
                                            x-text="detalleAbierto === {{ $alumno->id }} ? 'Ocultar' : 'Detalles'"></span>
                                    </button>

                                    @if ($alumno->nivel?->slug)
                                        <button type="button"
                                            x-on:click="abrirEdicion('{{ route('misrutas.matricula.editar', ['slug_nivel' => $alumno->nivel->slug, 'inscripcion' => $alumno->id]) }}')"
                                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-white shadow-sm transition hover:bg-blue-700"
                                            title="Editar">
                                            <flux:icon.square-pen class="h-4 w-4" />
                                        </button>
                                    @endif

                                    <button type="button" wire:click="eliminarAlumno({{ $alumno->id }})"
                                        wire:confirm="¿Seguro que deseas eliminar este alumno?"
                                        class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-white shadow-sm transition hover:bg-rose-700"
                                        title="Eliminar">
                                        <flux:icon.trash class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- Desplegable detalles --}}
                        <tr x-cloak x-show="detalleAbierto === {{ $alumno->id }}" x-transition>
                            <td colspan="11" class="bg-slate-50 px-4 pb-5 dark:bg-neutral-950/70">
                                <div
                                    class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-lg shadow-slate-200/70 dark:border-neutral-800 dark:bg-neutral-900 dark:shadow-none">
                                    <div
                                        class="bg-gradient-to-r from-indigo-600 via-blue-600 to-sky-500 px-5 py-4 text-white">
                                        <div
                                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20">
                                                    <flux:icon.user class="h-6 w-6" />
                                                </div>

                                                <div>
                                                    <p class="text-xs font-bold uppercase tracking-wide text-white/75">
                                                        Detalles del alumno
                                                    </p>

                                                    <h3
                                                        class="text-base font-black uppercase leading-tight sm:text-lg">
                                                        {{ $this->nombreCompleto($alumno) }}
                                                    </h3>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <span
                                                    class="rounded-full bg-white/15 px-3 py-1 text-xs font-black ring-1 ring-white/20">
                                                    Matrícula: {{ $alumno->matricula ?: '—' }}
                                                </span>

                                                <span
                                                    class="rounded-full bg-white/15 px-3 py-1 text-xs font-black ring-1 ring-white/20">
                                                    {{ $alumno->activo ? 'Activo' : 'Baja' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-3">
                                        {{-- Datos personales --}}
                                        <div
                                            class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                            <div class="flex items-start gap-3">
                                                @if ($alumno->foto_path)
                                                    <img src="{{ asset('storage/' . $alumno->foto_path) }}"
                                                        alt="Foto de {{ $alumno->nombre }}"
                                                        class="h-16 w-16 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-neutral-700">
                                                @else
                                                    <div
                                                        class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 ring-1 ring-slate-200 dark:bg-neutral-800 dark:ring-neutral-700">
                                                        <flux:icon.user class="h-7 w-7" />
                                                    </div>
                                                @endif

                                                <div class="min-w-0">
                                                    <p
                                                        class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">
                                                        CURP
                                                    </p>

                                                    <p
                                                        class="break-all text-sm font-black text-slate-800 dark:text-white">
                                                        {{ $alumno->curp ?: '—' }}
                                                    </p>

                                                    <div class="mt-3 flex flex-wrap gap-2">
                                                        <span
                                                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-neutral-800 dark:text-slate-300">
                                                            Sexo: {{ $alumno->genero ?: '—' }}
                                                        </span>

                                                        @if ($alumno->fecha_nacimiento)
                                                            <span
                                                                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-neutral-800 dark:text-slate-300">
                                                                Edad: {{ $alumno->fecha_nacimiento->age }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4 flex flex-wrap gap-2">
                                                @if ($alumno->nivel?->slug)
                                                    <a href="{{ route('misrutas.matricula.editar', ['slug_nivel' => $alumno->nivel->slug, 'inscripcion' => $alumno->id]) }}"
                                                        class="inline-flex items-center rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 px-4 py-2 text-xs font-black text-white transition hover:from-sky-600 hover:to-indigo-700">
                                                        Ver expediente completo
                                                    </a>
                                                @endif

                                                @if (auth()->user()?->is_admin)
                                                    <a href="{{ route('misrutas.expedientes.show', $alumno) }}"
                                                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-4 py-2 text-xs font-black text-white transition hover:from-emerald-600 hover:to-teal-700"
                                                        wire:navigate>
                                                        <flux:icon name="folder-lock" class="size-4" />
                                                        Expediente digital
                                                    </a>
                                                @endif
                                            </div>

                                            @if (auth()->user()?->is_admin)
                                                @php($resumenDocumental = $alumno->resumen_documental ?? ['items' => [], 'completados' => 0, 'total' => 0, 'completo' => false])
                                                <div
                                                    class="mt-4 rounded-2xl border border-slate-200 p-3 dark:border-neutral-800">
                                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                                        @foreach ($resumenDocumental['items'] as $documentoEsperado)
                                                            <span
                                                                class="inline-flex items-center gap-1 rounded-full px-2 py-1 font-bold {{ $documentoEsperado['presente'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-300' }}">
                                                                <flux:icon :name="$documentoEsperado['presente'] ? 'check' : 'x'" class="size-3.5" />
                                                                {{ $documentoEsperado['etiqueta'] }}
                                                            </span>
                                                        @endforeach

                                                        <span
                                                            class="ml-auto rounded-full px-2.5 py-1 text-xs font-black {{ $resumenDocumental['completo'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' }}">
                                                            {{ $resumenDocumental['completados'] }}/{{ $resumenDocumental['total'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Datos escolares --}}
                                        <div
                                            class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                            <h4
                                                class="mb-4 text-xs font-black uppercase text-slate-500 dark:text-slate-400">
                                                Datos escolares
                                            </h4>

                                            <div class="space-y-3 text-sm">
                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Matrícula</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        {{ $alumno->matricula ?: '—' }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Folio</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        {{ $alumno->folio ?: '—' }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Nivel</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        {{ $alumno->nivel?->nombre ?? '—' }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Grado</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        {{ $alumno->grado?->nombre ?? '—' }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Semestre</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        {{ $alumno->semestre ? $alumno->semestre->numero . '°' : '—' }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Grupo</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        {{ $this->textoGrupo($alumno->grupo) }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Generación</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        {{ $this->textoGeneracion($alumno->generacion) }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Ciclo</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        {{ $alumno->ciclo?->ciclo ?? '—' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Cuenta y extra --}}
                                        <div
                                            class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                            <h4
                                                class="mb-4 text-xs font-black uppercase text-slate-500 dark:text-slate-400">
                                                Cuenta y extra
                                            </h4>

                                            <div class="space-y-3 text-sm">
                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Fecha nac.</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        {{ $alumno->fecha_nacimiento ? $alumno->fecha_nacimiento->format('d/m/Y') : '—' }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Fecha
                                                        inscripción</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        {{ $alumno->fecha_inscripcion ? \Carbon\Carbon::parse($alumno->fecha_inscripcion)->format('d/m/Y') : '—' }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">Estatus</span>
                                                    <span
                                                        class="rounded-full px-3 py-1 text-xs font-black
                                                        {{ $alumno->activo
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                            : 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' }}">
                                                        {{ $alumno->activo ? 'Activo' : 'Baja' }}
                                                    </span>
                                                </div>

                                                @if (!$alumno->activo && $alumno->fecha_baja)
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-slate-500 dark:text-slate-400">Fecha
                                                            baja</span>
                                                        <span
                                                            class="text-right font-black text-rose-600 dark:text-rose-300">
                                                            {{ \Carbon\Carbon::parse($alumno->fecha_baja)->format('d/m/Y') }}
                                                        </span>
                                                    </div>
                                                @endif

                                                @if ($alumno->motivo_baja)
                                                    <div class="flex items-start justify-between gap-4">
                                                        <span class="text-slate-500 dark:text-slate-400">Motivo
                                                            baja</span>
                                                        <span
                                                            class="text-right font-black text-rose-600 dark:text-rose-300">
                                                            {{ $alumno->motivo_baja }}
                                                        </span>
                                                    </div>
                                                @endif

                                                <div class="flex items-center justify-between gap-4">
                                                    <span class="text-slate-500 dark:text-slate-400">ID alumno</span>
                                                    <span class="text-right font-black text-slate-800 dark:text-white">
                                                        #{{ $alumno->id }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="flex justify-end border-t border-slate-200 px-5 py-4 dark:border-neutral-800">
                                        <button type="button" x-on:click="detalleAbierto = null"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:bg-neutral-700">
                                            <flux:icon.x-mark class="h-4 w-4" />
                                            Cerrar
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-14 text-center">
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

        {{-- Tarjetas móvil/tablet --}}
        <div class="space-y-4 p-4 xl:hidden">
            @forelse ($alumnos as $alumno)
                <article x-data="{ abierto: false }"
                    class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="p-4">
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
                                <h3 class="text-base font-black uppercase text-slate-800 dark:text-white">
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
                                        {{ $alumno->genero ?: '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" x-on:click="abierto = !abierto"
                                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 px-4 py-2 text-xs font-black text-white">
                                <flux:icon.chevron-down class="h-4 w-4 transition-transform"
                                    x-bind:class="abierto ? 'rotate-180' : ''" />
                                <span x-text="abierto ? 'Ocultar' : 'Detalles'"></span>
                            </button>

                            @if ($alumno->nivel?->slug)
                                <button type="button"
                                    x-on:click="abrirEdicion('{{ route('misrutas.matricula.editar', ['slug_nivel' => $alumno->nivel->slug, 'inscripcion' => $alumno->id]) }}')"
                                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-white">
                                    <flux:icon.square-pen class="h-4 w-4" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div x-cloak x-show="abierto" x-transition
                        class="border-t border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/60">
                        <div class="grid grid-cols-1 gap-2 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                            <p><span class="font-bold">CURP:</span> {{ $alumno->curp ?: '—' }}</p>
                            <p><span class="font-bold">Folio:</span> {{ $alumno->folio ?: '—' }}</p>
                            <p><span class="font-bold">Nivel:</span> {{ $alumno->nivel?->nombre ?? '—' }}</p>
                            <p><span class="font-bold">Grado:</span> {{ $alumno->grado?->nombre ?? '—' }}</p>
                            <p><span class="font-bold">Semestre:</span>
                                {{ $alumno->semestre ? $alumno->semestre->numero . '°' : '—' }}</p>
                            <p><span class="font-bold">Grupo:</span> {{ $this->textoGrupo($alumno->grupo) }}</p>
                            <p><span class="font-bold">Generación:</span>
                                {{ $this->textoGeneracion($alumno->generacion) }}</p>
                            <p><span class="font-bold">Ciclo:</span> {{ $alumno->ciclo?->ciclo ?? '—' }}</p>
                            <p><span class="font-bold">Fecha nac.:</span>
                                {{ $alumno->fecha_nacimiento ? $alumno->fecha_nacimiento->format('d/m/Y') : '—' }}</p>
                            <p><span class="font-bold">Edad:</span>
                                {{ $alumno->fecha_nacimiento ? $alumno->fecha_nacimiento->age : '—' }}</p>
                        </div>
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
