{{-- resources/views/livewire/accion/matricula.blade.php --}}
<div class="space-y-6">
    {{-- Encabezado --}}
    <div
        class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
        <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-fuchsia-500"></div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                        Matrícula
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Consulta de alumnos y personal por nivel, generación y grupo.
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
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Nivel
                    </label>
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                        {{ $nivel?->nombre ?? '—' }}
                    </div>
                </div>

                <div>
                    <label for="generacion_id"
                        class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Generación
                    </label>
                    <select id="generacion_id" wire:model.live="generacion_id"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-100 dark:focus:ring-sky-900/40">
                        <option value="">Selecciona una generación</option>
                        @foreach ($generaciones as $generacion)
                            <option value="{{ $generacion->id }}">
                                {{ $generacion->label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="grupo_id" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Grupo
                    </label>
                    <select id="grupo_id" wire:model.live="grupo_id" @disabled(!$generacion_id)
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-100 dark:focus:ring-sky-900/40">
                        <option value="">Selecciona un grupo</option>
                        @foreach ($grupos as $grupo)
                            <option value="{{ $grupo->id }}">
                                {{ $grupo->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="search" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Buscar
                    </label>
                    <input id="search" type="text" wire:model.live.debounce.400ms="search"
                        placeholder="Matrícula, CURP o nombre..." @disabled(!$generacion_id || !$grupo_id)
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-100 dark:focus:ring-sky-900/40">
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($gradoGeneracionLabel)
                        <span
                            class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300">
                            Grado(s) de la generación: {{ $gradoGeneracionLabel }}
                        </span>
                    @endif

                    @if ($generacion_id && $grupo_id)
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

    @if (!$generacion_id || !$grupo_id)
        <div
            class="rounded-[28px] border border-dashed border-slate-300 bg-white/70 p-10 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-900/60">
            <div class="mx-auto max-w-2xl">
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">
                    Selecciona una generación y un grupo
                </h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Primero elige ambos filtros para mostrar la matrícula y el personal relacionado.
                </p>
            </div>
        </div>
    @else
        {{-- CARD ÚNICO --}}
        <div
            class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
            <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-emerald-500 to-fuchsia-500"></div>




            <div class="p-5 sm:p-6">

                {{-- Sección personal asignado --}}
                <section class="mb-3">
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
                                    class="rounded-3xl border w-full border-slate-200 bg-white px-5 py-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900">
                                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:flex-wrap">
                                                <h3 class="truncate text-base font-bold text-slate-800 dark:text-white">
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
                                            <p><span class="font-semibold">Grupo:</span>
                                                {{ $detalle?->grupo?->nombre ?? '—' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- Sección lista de alumnos --}}
                <section>
                    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                Lista de alumnos
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Registros filtrados por nivel, generación y grupo.
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
                                class="inline-flex items-center mr-3 justify-center rounded-2xl bg-gradient-to-r from-green-500 to-green-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-green-500/20 transition hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-60">
                                <span wire:loading.remove wire:target="exportarMatricula">
                                    <div class="flex justify-between gap-2">
                                        <flux:icon.download class="w-4 h-4" />
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

                    <div
                        class="hidden overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-800 xl:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                                <thead class="bg-slate-50/90 dark:bg-neutral-800/80">
                                    <tr>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Sel.</th>
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
                                            Matrícula</th>

                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Folio</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Apellido Paterno</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Apellido Materno</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Nombre(s)</th>

                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            CURP</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Género</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Grado</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Grupo</th>

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

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
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

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->folio ?: '—' }}
                                            </td>

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->apellido_paterno ?: '—' }}
                                            </td>
                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->apellido_materno ?: '—' }}
                                            </td>
                                            <td
                                                class="px-4 py-4 align-top  text-sm  text-slate-600 dark:text-slate-300">
                                                {{ trim($row->nombre) }}
                                            </td>




                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
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

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->grado?->nombre ?? '—' }}
                                            </td>

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->grupo?->nombre ?? '—' }}
                                            </td>


                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8"
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

                                    <input type="checkbox" wire:model.live="selected" value="{{ $row->id }}"
                                        class="mt-1 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                </div>

                                <div
                                    class="mt-4 grid grid-cols-1 gap-2 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                                    <div><span class="font-semibold">CURP:</span> {{ $row->curp ?: '—' }}</div>
                                    <div><span class="font-semibold">Género:</span> {{ $row->genero ?: '—' }}</div>
                                    <div><span class="font-semibold">Grado:</span> {{ $row->grado?->nombre ?? '—' }}
                                    </div>
                                    <div><span class="font-semibold">Grupo:</span> {{ $row->grupo?->nombre ?? '—' }}
                                    </div>
                                    <div><span class="font-semibold">Folio:</span> {{ $row->folio ?: '—' }}</div>
                                </div>
                            </div>
                        @empty
                            <div
                                class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-400">
                                No se encontraron alumnos con los filtros actuales.
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-5">
                        {{ $rows->links() }}
                    </div>
                </section>

                {{-- Separador interno --}}
                <div
                    class="my-8 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>


            </div>
        </div>
    @endif
</div>
