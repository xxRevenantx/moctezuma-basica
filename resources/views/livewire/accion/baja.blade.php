<div x-data="{
    filtrosRestaurados: false,

    init() {
        this.$nextTick(() => {
            this.restaurarFiltros();
        });

        document.addEventListener('livewire:navigated', () => {
            this.$nextTick(() => {
                this.restaurarFiltros();
            });
        });
    },

    llaveFiltros() {
        return 'bajas_filtros_' + @js($slug_nivel);
    },

    guardarFiltros() {
        const filtros = {
            slug_nivel: @js($slug_nivel),
            generacion_id: this.$wire.get('generacion_id'),
            grado_id: this.$wire.get('grado_id'),
            semestre_id: this.$wire.get('semestre_id'),
            grupo_id: this.$wire.get('grupo_id'),
            search: this.$wire.get('search'),
        };

        localStorage.setItem(this.llaveFiltros(), JSON.stringify(filtros));
    },

    restaurarFiltros() {
        if (this.filtrosRestaurados) {
            return;
        }

        const raw = localStorage.getItem(this.llaveFiltros());

        if (!raw) {
            return;
        }

        let filtros = null;

        try {
            filtros = JSON.parse(raw);
        } catch (e) {
            localStorage.removeItem(this.llaveFiltros());
            return;
        }

        if (!filtros || filtros.slug_nivel !== @js($slug_nivel)) {
            return;
        }

        this.filtrosRestaurados = true;

        @this.call('restaurarFiltrosBajas', filtros);
    },

    limpiarFiltrosGuardados() {
        localStorage.removeItem(this.llaveFiltros());
    },

    confirmarBaja() {
        this.guardarFiltros();

        Swal.fire({
            title: '¿Aplicar baja?',
            text: 'Los alumnos seleccionados quedarán marcados como baja y ya no aparecerán como activos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, aplicar baja'
        }).then((resultado) => {
            if (resultado.isConfirmed) {
                @this.call('aplicarBaja');
            }
        });
    },

    confirmarReactivacion(id) {
        this.guardarFiltros();

        Swal.fire({
            title: '¿Reactivar alumno?',
            text: 'El alumno volverá a estar activo y se limpiarán los datos de baja.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, reactivar'
        }).then((resultado) => {
            if (resultado.isConfirmed) {
                @this.call('reactivarAlumno', id);
            }
        });
    }
}" class="space-y-6">

    {{-- ITERA NIVELES --}}
    <div class="overflow-hidden">
        <div class="-mx-1 overflow-x-auto pb-1">
            <div class="flex min-w-max items-center justify-center gap-2 px-1">
                @foreach ($niveles as $item)
                    @php
                        $activo = $slug_nivel === $item->slug;
                    @endphp

                    <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'bajas']) }}"
                        wire:navigate aria-current="{{ $activo ? 'page' : 'false' }}"
                        class="group relative inline-flex items-center gap-2 whitespace-nowrap rounded-2xl border px-4 py-3 text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5
                        {{ $activo
                            ? 'border-rose-200 bg-gradient-to-r from-rose-500 via-red-600 to-orange-500 text-white shadow-lg shadow-rose-500/20 dark:border-rose-700/50'
                            : 'border-slate-200 bg-white text-slate-700 shadow-sm hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:border-rose-800 dark:hover:bg-neutral-800 dark:hover:text-rose-300' }}">

                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-xl
                            {{ $activo
                                ? 'bg-white/15 text-white'
                                : 'bg-slate-100 text-slate-500 group-hover:bg-rose-100 group-hover:text-rose-700 dark:bg-neutral-700 dark:text-slate-300 dark:group-hover:bg-rose-950/40 dark:group-hover:text-rose-300' }}">
                            <flux:icon.user-minus class="h-4 w-4" />
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
    <section
        class="relative overflow-hidden rounded-[32px] border border-white/60 bg-white/85 p-6 shadow-xl shadow-slate-200/60 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/85 dark:shadow-black/20">

        <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full bg-rose-400/20 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-20 h-64 w-64 rounded-full bg-orange-400/20 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div
                    class="mb-3 inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                    <flux:icon.user-minus class="h-3.5 w-3.5" />
                    Gestión de bajas
                </div>

                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                    Bajas escolares
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Consulta alumnos activos, aplica bajas y reactiva alumnos desde la misma tabla de inscripciones.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div
                    class="rounded-3xl border border-sky-100 bg-sky-50/90 px-5 py-4 shadow-sm dark:border-sky-900/40 dark:bg-sky-950/30">
                    <p class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">
                        Total activo
                    </p>
                    <p class="mt-1 text-3xl font-black text-sky-900 dark:text-sky-100">
                        {{ $total }}
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-zinc-200 bg-zinc-50/90 px-5 py-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/30">
                    <p class="text-xs font-bold uppercase tracking-wide text-zinc-700 dark:text-zinc-300">
                        Bajas
                    </p>
                    <p class="mt-1 text-3xl font-black text-zinc-900 dark:text-zinc-100">
                        {{ $totalBajas }}
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-emerald-100 bg-emerald-50/90 px-5 py-4 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/30">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                        Hombres
                    </p>
                    <p class="mt-1 text-3xl font-black text-emerald-900 dark:text-emerald-100">
                        {{ $hombres }}
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-pink-100 bg-pink-50/90 px-5 py-4 shadow-sm dark:border-pink-900/40 dark:bg-pink-950/30">
                    <p class="text-xs font-bold uppercase tracking-wide text-pink-700 dark:text-pink-300">
                        Mujeres
                    </p>
                    <p class="mt-1 text-3xl font-black text-pink-900 dark:text-pink-100">
                        {{ $mujeres }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- FILTROS --}}
    <section
        class="overflow-hidden rounded-[28px] border border-white/60 bg-white/85 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/85 dark:shadow-black/20">

        <div class="h-1.5 w-full bg-gradient-to-r from-rose-500 via-orange-500 to-amber-500"></div>

        <div class="p-5 sm:p-6">
            <div class="mb-5 flex flex-col gap-1">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    Filtros de búsqueda
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Selecciona la estructura escolar para cargar los alumnos activos y dados de baja.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div>
                    <flux:label>Nivel</flux:label>
                    <flux:input readonly variant="filled" value="{{ $nivel?->nombre ?? '—' }}" disabled />
                </div>

                <div>
                    <flux:label>Generación</flux:label>
                    <flux:select id="generacion_id" wire:model.live="generacion_id">
                        <flux:select.option value="">Selecciona una generación</flux:select.option>

                        @foreach ($generaciones as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">
                                {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:label>Grado</flux:label>
                    <flux:select id="grado_id" wire:model.live="grado_id" :disabled="!$generacion_id">
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
                        wire:key="bajas-grupo-select-{{ $slug_nivel }}-{{ $generacion_id ?? 'null' }}-{{ $grado_id ?? 'null' }}-{{ $semestre_id ?? 'null' }}-{{ $grupos->count() }}"
                        :disabled="$esBachillerato
                                                                                                    ? (!$generacion_id || !$grado_id || !$semestre_id || $grupos->isEmpty())
                                                                                                    : (!$generacion_id || !$grado_id || $grupos->isEmpty())">

                        <flux:select.option value="">Selecciona un grupo</flux:select.option>

                        @foreach ($grupos as $grupo)
                            <flux:select.option value="{{ (string) $grupo->id }}">
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

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($generacionGrupoLabel)
                        <span
                            class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300">
                            Generación: {{ $generacionGrupoLabel }}
                        </span>
                    @endif

                    @if ($this->filtrosListos)
                        <span
                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                            Filtros aplicados
                        </span>
                    @else
                        <span
                            class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                            Filtros pendientes
                        </span>
                    @endif
                </div>

                <button type="button" x-on:click="limpiarFiltrosGuardados(); @this.call('clearFilters')"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:bg-neutral-700">
                    Limpiar filtros
                </button>
            </div>
        </div>
    </section>

    @if (!$this->filtrosListos)
        <section
            class="rounded-[28px] border border-dashed border-slate-300 bg-white/70 p-10 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-900/60">
            <div class="mx-auto max-w-2xl">
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-300">
                    <flux:icon.adjustments-horizontal class="h-7 w-7" />
                </div>

                <h2 class="text-xl font-bold text-slate-800 dark:text-white">
                    @if ($esBachillerato)
                        Selecciona una generación, grado, semestre y grupo
                    @else
                        Selecciona una generación, grado y grupo
                    @endif
                </h2>

                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Cuando completes los filtros, se mostrarán los alumnos activos y dados de baja.
                </p>
            </div>
        </section>
    @else
        {{-- ALUMNOS ACTIVOS --}}
        <section
            class="overflow-hidden rounded-[28px] border border-white/60 bg-white/85 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/85 dark:shadow-black/20">

            <div class="h-1.5 w-full bg-gradient-to-r from-red-600 via-rose-500 to-orange-500"></div>

            <div class="p-5 sm:p-6">
                <div class="mb-6 grid grid-cols-1 gap-5">
                    {{-- FORMULARIO DE BAJA --}}
                    <div
                        class="rounded-[28px] border border-rose-100 bg-gradient-to-br from-rose-50 via-white to-orange-50 p-5 shadow-sm dark:border-rose-900/40 dark:from-rose-950/20 dark:via-neutral-900 dark:to-orange-950/20">

                        <div class="mb-5 flex items-start gap-3">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-lg shadow-rose-500/25">
                                <flux:icon.user-minus class="h-5 w-5" />
                            </div>

                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">
                                    Datos de la baja
                                </h3>
                                <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400">
                                    Esta información se aplicará a los alumnos seleccionados.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <flux:label>Fecha de baja</flux:label>
                                <flux:input type="date" wire:model.live="fecha_baja" />

                                @error('fecha_baja')
                                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <flux:label>Motivo de baja</flux:label>
                                <flux:select wire:model.live="motivo_baja">
                                    <flux:select.option value="">Selecciona un motivo</flux:select.option>
                                    <flux:select.option value="Cambio de escuela">Cambio de escuela</flux:select.option>
                                    <flux:select.option value="Baja voluntaria">Baja voluntaria</flux:select.option>
                                    <flux:select.option value="Problemas económicos">Problemas económicos
                                    </flux:select.option>
                                    <flux:select.option value="Cambio de domicilio">Cambio de domicilio
                                    </flux:select.option>
                                    <flux:select.option value="Inasistencia">Inasistencia</flux:select.option>
                                    <flux:select.option value="Otro">Otro</flux:select.option>
                                </flux:select>

                                @error('motivo_baja')
                                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    Observaciones
                                </label>

                                <textarea wire:model.live.debounce.400ms="observaciones_baja" rows="5"
                                    placeholder="Agrega observaciones internas de la baja..."
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-4 focus:ring-rose-100 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-100 dark:focus:ring-rose-900/40"></textarea>

                                @error('observaciones_baja')
                                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @error('selected')
                                <p
                                    class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
                                    {{ $message }}
                                </p>
                            @enderror

                            <button type="button" x-on:click="confirmarBaja()" wire:loading.attr="disabled"
                                wire:target="aplicarBaja"
                                :disabled="{{ $this->selectedCount === 0 ? 'true' : 'false' }}"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-rose-600 via-red-600 to-orange-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-rose-500/25 transition hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-60">

                                <span wire:loading.remove wire:target="aplicarBaja"
                                    class="inline-flex items-center gap-2">
                                    <flux:icon.user-minus class="h-4 w-4" />
                                    Aplicar baja
                                </span>

                                <span wire:loading wire:target="aplicarBaja">
                                    Aplicando baja...
                                </span>
                            </button>
                        </div>
                    </div>


                </div>

                <div class="relative transition-opacity duration-300" wire:loading.class="opacity-60"
                    wire:target="generacion_id,grado_id,semestre_id,grupo_id,search,aplicarBaja">

                    <div wire:loading.flex wire:target="generacion_id,grado_id,semestre_id,grupo_id,search,aplicarBaja"
                        class="absolute inset-0 z-30 hidden items-center justify-center rounded-3xl border border-white/60 bg-white/75 backdrop-blur-md dark:border-white/10 dark:bg-neutral-900/75">

                        <div
                            class="flex min-w-[260px] flex-col items-center rounded-3xl border border-rose-100 bg-white/90 px-8 py-7 shadow-2xl shadow-rose-500/10 dark:border-rose-900/40 dark:bg-neutral-950/90">
                            <div class="relative mb-4 flex h-16 w-16 items-center justify-center">
                                <div
                                    class="absolute inset-0 rounded-full border-4 border-rose-200 dark:border-rose-900/40">
                                </div>
                                <div
                                    class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-rose-500 border-r-orange-500">
                                </div>
                                <div
                                    class="h-8 w-8 rounded-full bg-gradient-to-br from-rose-500 via-red-600 to-orange-500 shadow-lg shadow-rose-500/30">
                                </div>
                            </div>

                            <h3 class="text-base font-bold text-slate-800 dark:text-white">
                                Actualizando registros
                            </h3>

                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Procesando información...
                            </p>
                        </div>
                    </div>

                    {{-- TABLA ESCRITORIO ACTIVOS --}}
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
                                            #</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Foto</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Matrícula</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Folio</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Alumno</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            CURP</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Género</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Generación</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Grado</th>

                                        @if ($esBachillerato)
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                Semestre</th>
                                        @endif

                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Grupo</th>
                                    </tr>
                                </thead>

                                <tbody
                                    class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                                    @forelse ($rows as $row)
                                        <tr class="transition hover:bg-rose-50/60 dark:hover:bg-neutral-800/60">
                                            <td class="px-4 py-4 align-top">
                                                <input type="checkbox" wire:model.live="selected"
                                                    value="{{ $row->id }}"
                                                    class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                            </td>

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $loop->iteration + ($rows->currentPage() - 1) * $rows->perPage() }}
                                            </td>

                                            <td class="px-4 py-4 align-top">
                                                @if ($row->foto_path)
                                                    <img src="{{ asset('storage/' . $row->foto_path) }}"
                                                        alt="Foto de {{ $row->nombre }}"
                                                        class="h-11 w-11 rounded-2xl object-cover ring-2 ring-white shadow-sm dark:ring-neutral-800">
                                                @else
                                                    <div
                                                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-200 text-sm font-bold text-slate-500 dark:bg-neutral-700 dark:text-slate-400">
                                                        {{ mb_substr($row->nombre ?? 'A', 0, 1) }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td
                                                class="px-4 py-4 align-top font-bold text-slate-800 dark:text-slate-100">
                                                {{ $row->matricula ?: '—' }}
                                            </td>

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->folio ?: '—' }}
                                            </td>

                                            <td class="px-4 py-4 align-top">
                                                <p class="font-bold text-slate-800 dark:text-slate-100">
                                                    {{ trim($row->apellido_paterno . ' ' . $row->apellido_materno . ' ' . $row->nombre) }}
                                                </p>
                                            </td>

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->curp ?: '—' }}
                                            </td>

                                            <td class="px-4 py-4 align-top">
                                                <span
                                                    class="inline-flex rounded-full px-3 py-1 text-xs font-bold
                                                    {{ $row->genero === 'H'
                                                        ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300'
                                                        : 'bg-pink-100 text-pink-700 dark:bg-pink-950/40 dark:text-pink-300' }}">
                                                    {{ $row->genero ?: '—' }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->generacion ? $row->generacion->anio_ingreso . ' - ' . $row->generacion->anio_egreso : '—' }}
                                            </td>

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->grado?->nombre ?? '—' }}
                                            </td>

                                            @if ($esBachillerato)
                                                <td
                                                    class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                    {{ $row->semestre?->numero ?? '—' }}
                                                </td>
                                            @endif

                                            <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->grupo?->nombre ?? '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $esBachillerato ? 12 : 11 }}"
                                                class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                                                No se encontraron alumnos activos con los filtros actuales.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TARJETAS MÓVIL ACTIVOS --}}
                    <div class="space-y-4 xl:hidden">
                        @forelse ($rows as $row)
                            <div
                                class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-rose-200 hover:bg-rose-50/40 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800">

                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-start gap-3">
                                        @if ($row->foto_path)
                                            <img src="{{ asset('storage/' . $row->foto_path) }}"
                                                alt="Foto de {{ $row->nombre }}"
                                                class="h-12 w-12 rounded-2xl object-cover">
                                        @else
                                            <div
                                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-200 text-sm font-bold text-slate-500 dark:bg-neutral-700 dark:text-slate-400">
                                                {{ mb_substr($row->nombre ?? 'A', 0, 1) }}
                                            </div>
                                        @endif

                                        <div class="min-w-0">
                                            <p class="truncate text-base font-black text-slate-800 dark:text-white">
                                                {{ trim($row->apellido_paterno . ' ' . $row->apellido_materno . ' ' . $row->nombre) }}
                                            </p>

                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                Matrícula: {{ $row->matricula ?: '—' }}
                                            </p>
                                        </div>
                                    </div>

                                    <input type="checkbox" wire:model.live="selected" value="{{ $row->id }}"
                                        class="mt-1 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                </div>

                                <div
                                    class="mt-4 grid grid-cols-1 gap-2 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                                    <div><span class="font-semibold">Folio:</span> {{ $row->folio ?: '—' }}</div>
                                    <div><span class="font-semibold">CURP:</span> {{ $row->curp ?: '—' }}</div>
                                    <div><span class="font-semibold">Género:</span> {{ $row->genero ?: '—' }}</div>
                                    <div>
                                        <span class="font-semibold">Generación:</span>
                                        {{ $row->generacion ? $row->generacion->anio_ingreso . ' - ' . $row->generacion->anio_egreso : '—' }}
                                    </div>
                                    <div><span class="font-semibold">Grado:</span> {{ $row->grado?->nombre ?? '—' }}
                                    </div>

                                    @if ($esBachillerato)
                                        <div><span class="font-semibold">Semestre:</span>
                                            {{ $row->semestre?->numero ?? '—' }}</div>
                                    @endif

                                    <div><span class="font-semibold">Grupo:</span> {{ $row->grupo?->nombre ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div
                                class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-400">
                                No se encontraron alumnos activos con los filtros actuales.
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-5">
                        {{ $rows->links() }}
                    </div>
                </div>
            </div>
        </section>

        {{-- ALUMNOS DADOS DE BAJA --}}
        <section
            class="overflow-hidden rounded-[28px] border border-white/60 bg-white/85 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/85 dark:shadow-black/20">

            <div class="h-1.5 w-full bg-gradient-to-r from-zinc-700 via-slate-600 to-emerald-600"></div>

            <div class="p-5 sm:p-6">
                <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div
                            class="mb-2 inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950/30 dark:text-zinc-300">
                            <flux:icon.archive-box class="h-3.5 w-3.5" />
                            Historial de bajas
                        </div>

                        <h2 class="text-xl font-black text-slate-900 dark:text-white">
                            Alumnos dados de baja
                        </h2>

                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Al reactivar, el campo activo cambia a 1 y se limpian fecha, motivo y observaciones de baja.
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-bold text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950/30 dark:text-zinc-300">
                        Total bajas:
                        <span class="font-black">{{ $totalBajas }}</span>
                    </div>
                </div>

                <div class="relative transition-opacity duration-300" wire:loading.class="opacity-60"
                    wire:target="generacion_id,grado_id,semestre_id,grupo_id,search,reactivarAlumno">

                    <div wire:loading.flex wire:target="reactivarAlumno"
                        class="absolute inset-0 z-30 hidden items-center justify-center rounded-3xl bg-white/75 backdrop-blur-md dark:bg-neutral-900/75">
                        <div
                            class="rounded-3xl border border-emerald-100 bg-white px-8 py-6 text-center shadow-2xl dark:border-emerald-900/40 dark:bg-neutral-950">
                            <div
                                class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600">
                            </div>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                Reactivando alumno...
                            </p>
                        </div>
                    </div>

                    {{-- TABLA ESCRITORIO BAJAS --}}
                    <div
                        class="hidden overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-800 xl:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                                <thead class="bg-slate-50/90 dark:bg-neutral-800/80">
                                    <tr>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            #</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Alumno</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Matrícula</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            CURP</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Fecha baja</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Motivo</th>
                                        <th
                                            class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Observaciones</th>
                                        <th
                                            class="px-4 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Acción</th>
                                    </tr>
                                </thead>

                                <tbody
                                    class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                                    @forelse ($bajasRows as $row)
                                        <tr class="transition hover:bg-emerald-50/60 dark:hover:bg-neutral-800/60">
                                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                                {{ $loop->iteration + ($bajasRows->currentPage() - 1) * $bajasRows->perPage() }}
                                            </td>

                                            <td class="px-4 py-4">
                                                <p class="font-bold text-slate-800 dark:text-slate-100">
                                                    {{ trim($row->apellido_paterno . ' ' . $row->apellido_materno . ' ' . $row->nombre) }}
                                                </p>
                                            </td>

                                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->matricula ?: '—' }}
                                            </td>

                                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->curp ?: '—' }}
                                            </td>

                                            <td
                                                class="px-4 py-4 text-sm font-semibold text-rose-700 dark:text-rose-300">
                                                {{ $row->fecha_baja ? \Carbon\Carbon::parse($row->fecha_baja)->format('d/m/Y') : '—' }}
                                            </td>

                                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->motivo_baja ?: '—' }}
                                            </td>

                                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                                {{ $row->observaciones_baja ?: '—' }}
                                            </td>

                                            <td class="px-4 py-4 text-right">
                                                <button type="button"
                                                    x-on:click="confirmarReactivacion({{ $row->id }})"
                                                    wire:loading.attr="disabled" wire:target="reactivarAlumno"
                                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-500 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 transition hover:scale-[1.02] disabled:cursor-not-allowed disabled:opacity-60">

                                                    <span wire:loading.remove wire:target="reactivarAlumno"
                                                        class="inline-flex items-center gap-2">
                                                        <flux:icon.arrow-path class="h-4 w-4" />
                                                        Reactivar
                                                    </span>

                                                    <span wire:loading wire:target="reactivarAlumno">
                                                        Reactivando...
                                                    </span>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8"
                                                class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                                                No hay alumnos dados de baja con los filtros actuales.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TARJETAS MÓVIL BAJAS --}}
                    <div class="space-y-4 xl:hidden">
                        @forelse ($bajasRows as $row)
                            <div
                                class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-base font-black text-slate-800 dark:text-white">
                                            {{ trim($row->apellido_paterno . ' ' . $row->apellido_materno . ' ' . $row->nombre) }}
                                        </p>

                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            Matrícula: {{ $row->matricula ?: '—' }}
                                        </p>
                                    </div>

                                    <span
                                        class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                                        Baja
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-2 text-sm text-slate-600 dark:text-slate-300">
                                    <div>
                                        <span class="font-semibold">CURP:</span>
                                        {{ $row->curp ?: '—' }}
                                    </div>

                                    <div>
                                        <span class="font-semibold">Fecha baja:</span>
                                        {{ $row->fecha_baja ? \Carbon\Carbon::parse($row->fecha_baja)->format('d/m/Y') : '—' }}
                                    </div>

                                    <div>
                                        <span class="font-semibold">Motivo:</span>
                                        {{ $row->motivo_baja ?: '—' }}
                                    </div>

                                    <div>
                                        <span class="font-semibold">Observaciones:</span>
                                        {{ $row->observaciones_baja ?: '—' }}
                                    </div>
                                </div>

                                <button type="button" x-on:click="confirmarReactivacion({{ $row->id }})"
                                    wire:loading.attr="disabled" wire:target="reactivarAlumno"
                                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 transition hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-60">

                                    <span wire:loading.remove wire:target="reactivarAlumno">
                                        Reactivar alumno
                                    </span>

                                    <span wire:loading wire:target="reactivarAlumno">
                                        Reactivando...
                                    </span>
                                </button>
                            </div>
                        @empty
                            <div
                                class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-400">
                                No hay alumnos dados de baja con los filtros actuales.
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-5">
                        {{ $bajasRows->links() }}
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
