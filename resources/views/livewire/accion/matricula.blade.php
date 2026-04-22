<div x-data="{
    openRow: null,
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `Esta inscripción se eliminará de forma permanente`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminar', id))
    }
}" class="space-y-5">

    {{-- ITERA NIVELES --}}
    <div class="overflow-hidden ">
        <div>
            <div class="-mx-1 overflow-x-auto pb-1">
                <div class="flex min-w-max items-center gap-2 px-1 justify-center">
                    @foreach ($niveles as $item)
                        @php
                            $activo = $slug_nivel === $item->slug;
                        @endphp

                        <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'matricula']) }}"
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
    </div>

    {{-- Encabezado --}}
    <div>
        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                        Matrícula
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Consulta de alumnos y personal por nivel, grado y grupo.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div
                        class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 dark:border-sky-900/40 dark:bg-sky-950/30">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">
                            Total
                        </p>
                        <p class="mt-1 text-2xl font-bold text-sky-900 dark:text-sky-100">
                            {{ $total }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                            Hombres
                        </p>
                        <p class="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                            {{ $hombres }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-pink-100 bg-pink-50 px-4 py-3 dark:border-pink-900/40 dark:bg-pink-950/30">
                        <p class="text-xs font-semibold uppercase tracking-wide text-pink-700 dark:text-pink-300">
                            Mujeres
                        </p>
                        <p class="mt-1 text-2xl font-bold text-pink-900 dark:text-pink-100">
                            {{ $mujeres }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div
        class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
        <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-500"></div>

        <div class="p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div>
                    <flux:label>Nivel</flux:label>
                    <flux:input readonly variant="filled" value="{{ $nivel?->nombre ?? '—' }}" disabled />
                </div>

                <div>
                    <flux:label>Grado</flux:label>
                    <flux:select id="grado_id" wire:model.live="grado_id">
                        <flux:select.option value="">Selecciona un grado</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">
                                {{ $grado->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                @if ($esBachillerato)
                    <div>
                        <flux:label>Semestre</flux:label>
                        <flux:select id="semestre_id" wire:model.live="semestre_id"
                            :disabled="!$grado_id || $semestres->isEmpty()">
                            <flux:select.option value="">Selecciona un semestre</flux:select.option>
                            @foreach ($semestres as $semestre)
                                <flux:select.option value="{{ $semestre->id }}">
                                    Semestre {{ $semestre->numero }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @endif

                <div>
                    <flux:label>Grupo</flux:label>
                    <flux:select id="grupo_id" wire:model.live="grupo_id"
                        :disabled="!$grado_id || ($esBachillerato && !$semestre_id) || $grupos->isEmpty()">
                        <flux:select.option value="">Selecciona un grupo</flux:select.option>
                        @foreach ($grupos as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">
                                {{ $grupo->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:label>Buscar</flux:label>
                    <x-input wire:model.live.debounce.400ms="search" placeholder="Matrícula, CURP o nombre..." />
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($generacionGrupoLabel)
                        <span
                            class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300">
                            Generación del grupo: {{ $generacionGrupoLabel }}
                        </span>
                    @endif

                    @if ((!$esBachillerato && $grado_id && $grupo_id) || ($esBachillerato && $grado_id && $semestre_id && $grupo_id))
                        <span
                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                            Filtros aplicados
                        </span>
                    @endif
                </div>

                <button type="button" wire:click="clearFilters"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:bg-neutral-700">
                    Limpiar filtros
                </button>
            </div>
        </div>
    </div>

    @if (
        (!$esBachillerato && (!$grado_id || !$grupo_id)) ||
            ($esBachillerato && (!$grado_id || !$semestre_id || !$grupo_id)))
        <div
            class="rounded-[28px] border border-dashed border-slate-300 bg-white/70 p-10 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-900/60">
            <div class="mx-auto max-w-2xl">
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">
                    @if ($esBachillerato)
                        Selecciona un grado, un semestre y un grupo
                    @else
                        Selecciona un grado y un grupo
                    @endif
                </h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    @if ($esBachillerato)
                        Primero elige esos tres filtros para mostrar la matrícula y el personal relacionado.
                    @else
                        Primero elige ambos filtros para mostrar la matrícula y el personal relacionado.
                    @endif
                </p>
            </div>
        </div>
    @else
        <div
            class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
            <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-emerald-500 to-fuchsia-500"></div>

            <div class="p-5 sm:p-6">
                {{-- Sección personal asignado --}}
                <section class="mb-3">
                    <div class="relative">
                        <div class="transition-opacity duration-300" wire:loading.class="opacity-50"
                            wire:target="grado_id,semestre_id,grupo_id,search">
                            @if ($personal->isEmpty())
                                <div
                                    class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-400">
                                    No hay personal asignado para este grupo.
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($personal as $p)
                                        @php
                                            $per = $p->persona;
                                            $detalle = $p->detalles->first();

                                            $nombre = trim(
                                                ($per?->titulo ? $per->titulo . ' ' : '') .
                                                    ($per?->nombre ?? '') .
                                                    ' ' .
                                                    ($per?->apellido_paterno ?? '') .
                                                    ' ' .
                                                    ($per?->apellido_materno ?? ''),
                                            );

                                            $gen = $per?->genero;
                                        @endphp

                                        <div
                                            class="w-full rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                                            <div
                                                class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                                <div class="min-w-0 flex-1">
                                                    <div
                                                        class="flex flex-col gap-2 lg:flex-row lg:flex-wrap lg:items-center">
                                                        <h3
                                                            class="truncate text-base font-bold text-slate-800 dark:text-white">
                                                            {{ $nombre !== '' ? $nombre : 'Sin nombre' }}
                                                        </h3>

                                                        <div class="flex flex-wrap items-center gap-2">
                                                            @if ($gen)
                                                                <span
                                                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $gen === 'H'
                                                                        ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300'
                                                                        : 'bg-pink-100 text-pink-700 dark:bg-pink-950/40 dark:text-pink-300' }}">
                                                                    {{ $gen === 'H' ? 'Hombre' : 'Mujer' }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="flex flex-col gap-2 text-sm text-slate-600 dark:text-slate-300 xl:flex-row xl:items-center xl:gap-6">
                                                    <p><span class="font-semibold">Nivel:</span>
                                                        {{ $p->nivel?->nombre ?? '—' }}</p>
                                                    <p><span class="font-semibold">Grado:</span>
                                                        {{ $detalle?->grado?->nombre ?? '—' }}</p>
                                                    @if ($esBachillerato)
                                                        <p><span class="font-semibold">Semestre:</span>
                                                            {{ $semestres->firstWhere('id', $semestre_id)?->numero ?? '—' }}
                                                        </p>
                                                    @endif
                                                    <p><span class="font-semibold">Grupo:</span>
                                                        {{ $detalle?->grupo?->nombre ?? '—' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                {{-- Sección lista de alumnos --}}
                <section>
                    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                Lista de alumnos
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Registros filtrados por nivel, grado y grupo.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <label
                                class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                                <input type="checkbox" wire:model.live="selectPage"
                                    class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                Seleccionar página
                            </label>

                            <div
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                                Seleccionados: <span class="font-bold">{{ $this->selectedCount }}</span>
                            </div>
                        </div>
                    </div>

                    @if (!$esBachillerato)
                        <div class="mb-5 grid grid-cols-1 gap-3 lg:grid-cols-[1fr_auto]">
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <div class="w-full sm:max-w-xs">
                                    <label for="nuevo_grado_id"
                                        class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        Cambiar grado a seleccionados
                                    </label>
                                    <select id="nuevo_grado_id" wire:model="nuevo_grado_id" @disabled($this->selectedCount === 0)
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-100 dark:focus:ring-sky-900/40">
                                        <option value="">Selecciona un grado</option>
                                        @foreach ($grados as $grado)
                                            <option value="{{ $grado->id }}">
                                                {{ $grado->nombre }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('nuevo_grado_id')
                                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex items-end">
                                <button type="button" wire:click="exportarMatricula" wire:loading.attr="disabled"
                                    wire:target="exportarMatricula"
                                    class="mr-3 inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-green-500 to-green-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-green-500/20 transition hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-60">
                                    <span wire:loading.remove wire:target="exportarMatricula">
                                        <div class="flex justify-between gap-2">
                                            <flux:icon.download class="h-4 w-4" />
                                            Exportar Excel
                                        </div>
                                    </span>

                                    <span wire:loading wire:target="exportarMatricula">
                                        Descargando...
                                    </span>
                                </button>

                                <button type="button" wire:click="aplicarCambiarGrado" wire:loading.attr="disabled"
                                    wire:target="aplicarCambiarGrado"
                                    class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-blue-500 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-60">
                                    <span wire:loading.remove wire:target="aplicarCambiarGrado">
                                        Aplicar cambio
                                    </span>

                                    <span wire:loading wire:target="aplicarCambiarGrado">
                                        Aplicando...
                                    </span>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="mb-5 flex justify-end">
                            <button type="button" wire:click="exportarMatricula" wire:loading.attr="disabled"
                                wire:target="exportarMatricula"
                                class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-green-500 to-green-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-green-500/20 transition hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-60">
                                <span wire:loading.remove wire:target="exportarMatricula">
                                    <div class="flex justify-between gap-2">
                                        <flux:icon.download class="h-4 w-4" />
                                        Exportar Excel
                                    </div>
                                </span>

                                <span wire:loading wire:target="exportarMatricula">
                                    Descargando...
                                </span>
                            </button>
                        </div>
                    @endif

                    <div class="relative transition-opacity duration-300" wire:loading.class="opacity-60"
                        wire:target="grado_id,semestre_id,grupo_id,search">
                        <div wire:loading.flex wire:target="grado_id,semestre_id,grupo_id,search"
                            class="absolute inset-0 z-30 hidden items-center justify-center rounded-3xl border border-white/60 bg-white/75 backdrop-blur-md dark:border-white/10 dark:bg-neutral-900/75">
                            <div
                                class="flex min-w-[260px] flex-col items-center rounded-3xl border border-sky-100 bg-white/90 px-8 py-7 shadow-2xl shadow-sky-500/10 dark:border-sky-900/40 dark:bg-neutral-950/90">
                                <div class="relative mb-4 flex h-16 w-16 items-center justify-center">
                                    <div
                                        class="absolute inset-0 rounded-full border-4 border-sky-200 dark:border-sky-900/40">
                                    </div>
                                    <div
                                        class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-sky-500 border-r-indigo-500">
                                    </div>
                                    <div
                                        class="h-8 w-8 rounded-full bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 shadow-lg shadow-sky-500/30">
                                    </div>
                                </div>

                                <h3 class="text-base font-bold text-slate-800 dark:text-white">
                                    Cargando registros
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Actualizando alumnos y personal...
                                </p>

                                <div class="mt-4 flex items-center gap-1.5">
                                    <span
                                        class="h-2.5 w-2.5 animate-bounce rounded-full bg-sky-500 [animation-delay:-0.3s]"></span>
                                    <span
                                        class="h-2.5 w-2.5 animate-bounce rounded-full bg-blue-500 [animation-delay:-0.15s]"></span>
                                    <span class="h-2.5 w-2.5 animate-bounce rounded-full bg-indigo-500"></span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="hidden overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-800 xl:block">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                                    <thead class="bg-slate-50/90 dark:bg-neutral-800/80">
                                        <tr>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Sel.
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                #
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Foto
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Matrícula
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Folio
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Apellido Paterno
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Apellido Materno
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Nombre(s)
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                CURP
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Género
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Grado
                                            </th>
                                            @if ($esBachillerato)
                                                <th
                                                    class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                    Semestre
                                                </th>
                                            @endif
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Grupo
                                            </th>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody
                                        class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                                        @forelse ($rows as $row)
                                            <tr class="transition hover:bg-slate-50 dark:hover:bg-neutral-800/60">
                                                <td class="px-4 py-4 align-top">
                                                    <input type="checkbox" wire:model.live="selected"
                                                        value="{{ $row->id }}"
                                                        class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                                </td>

                                                <td
                                                    class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                    {{ $loop->iteration + ($rows->currentPage() - 1) * $rows->perPage() }}
                                                </td>

                                                <td class="px-4 py-4 align-top">
                                                    @if ($row->foto_path)
                                                        <img src="{{ asset('storage/' . $row->foto_path) }}"
                                                            alt="Foto de {{ $row->nombre }}"
                                                            class="h-10 w-10 rounded-full object-cover">
                                                    @else
                                                        <div
                                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-sm font-medium text-slate-500 dark:bg-neutral-700 dark:text-slate-400">
                                                        </div>
                                                    @endif
                                                </td>

                                                <td
                                                    class="px-4 py-4 align-top font-semibold text-slate-800 dark:text-slate-100">
                                                    {{ $row->matricula ?: '—' }}
                                                </td>

                                                <td
                                                    class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                    {{ $row->folio ?: '—' }}
                                                </td>

                                                <td
                                                    class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                    {{ $row->apellido_paterno ?: '—' }}
                                                </td>

                                                <td
                                                    class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                    {{ $row->apellido_materno ?: '—' }}
                                                </td>

                                                <td
                                                    class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                    {{ trim($row->nombre) }}
                                                </td>

                                                <td
                                                    class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                    {{ $row->curp ?: '—' }}
                                                </td>

                                                <td class="px-4 py-4 align-top">
                                                    <span
                                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                        {{ $row->genero === 'H'
                                                            ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300'
                                                            : 'bg-pink-100 text-pink-700 dark:bg-pink-950/40 dark:text-pink-300' }}">
                                                        {{ $row->genero ?: '—' }}
                                                    </span>
                                                </td>

                                                <td
                                                    class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                    {{ $row->grado?->nombre ?? '—' }}
                                                </td>

                                                @if ($esBachillerato)
                                                    <td
                                                        class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                        {{ $row->semestre?->numero ?? '—' }}
                                                    </td>
                                                @endif

                                                <td
                                                    class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                    {{ $row->grupo?->nombre ?? '—' }}
                                                </td>

                                                <td class="px-4 py-4 align-top">
                                                    <div class="flex items-center gap-2">
                                                        <flux:button variant="primary"
                                                            class="cursor-pointer bg-amber-500 px-3 py-1.5 text-xs text-white shadow-sm hover:bg-amber-600 hover:shadow-md"
                                                            x-on:click="window.open('{{ route('misrutas.matricula.editar', ['slug_nivel' => $slug_nivel, 'inscripcion' => $row->id]) }}', '_blank')">
                                                            <flux:icon.square-pen class="h-3.5 w-3.5" />
                                                        </flux:button>

                                                        <flux:button variant="danger"
                                                            class="cursor-pointer bg-rose-600 px-3 py-1.5 text-xs text-white shadow-sm hover:bg-rose-700 hover:shadow-md"
                                                            @click="eliminar({{ $row->id }}, '{{ $row->nombre }}')">
                                                            <flux:icon.trash-2 class="h-3.5 w-3.5" />
                                                        </flux:button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ $esBachillerato ? 14 : 13 }}"
                                                    class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                                    No se encontraron alumnos con los filtros actuales.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="space-y-4 xl:hidden">
                            @forelse ($rows as $row)
                                <div
                                    class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-base font-bold text-slate-800 dark:text-white">
                                                {{ trim($row->nombre . ' ' . $row->apellido_paterno . ' ' . $row->apellido_materno) }}
                                            </p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                Matrícula: {{ $row->matricula ?: '—' }}
                                            </p>
                                        </div>

                                        <input type="checkbox" wire:model.live="selected"
                                            value="{{ $row->id }}"
                                            class="mt-1 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    </div>

                                    <div
                                        class="mt-4 grid grid-cols-1 gap-2 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                                        <div><span class="font-semibold">CURP:</span> {{ $row->curp ?: '—' }}
                                        </div>
                                        <div><span class="font-semibold">Género:</span>
                                            {{ $row->genero ?: '—' }}
                                        </div>
                                        <div><span class="font-semibold">Grado:</span>
                                            {{ $row->grado?->nombre ?? '—' }}
                                        </div>
                                        @if ($esBachillerato)
                                            <div><span class="font-semibold">Semestre:</span>
                                                {{ $row->semestre?->numero ?? '—' }}
                                            </div>
                                        @endif
                                        <div><span class="font-semibold">Grupo:</span>
                                            {{ $row->grupo?->nombre ?? '—' }}
                                        </div>
                                        <div><span class="font-semibold">Folio:</span>
                                            {{ $row->folio ?: '—' }}</div>
                                    </div>
                                </div>
                            @empty
                                <div
                                    class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-400">
                                    No se encontraron alumnos con los filtros actuales.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-5">
                        {{ $rows->links() }}
                    </div>
                </section>

                <div
                    class="my-8 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>
            </div>
        </div>
    @endif
</div>
