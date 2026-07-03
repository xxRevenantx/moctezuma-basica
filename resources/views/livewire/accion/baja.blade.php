<div x-data="{
    tipoMovimiento: $wire.entangle('tipo_movimiento'),

    confirmarMovimiento(cantidad) {
        const etiquetas = {
            baja_definitiva: 'Baja definitiva',
            baja_temporal: 'Baja temporal',
            trasladado: 'Traslado / cambio de escuela',
            inactivo: 'Inactivo',
            suspendido: 'Suspendido'
        };

        const tipo = etiquetas[this.tipoMovimiento] ?? 'el movimiento seleccionado';
        if (cantidad < 1) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona alumnos',
                text: 'Marca al menos un alumno antes de aplicar el movimiento.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#e11d48'
            });

            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Confirmar movimiento',
            html: `
                    <div style='text-align:left'>
                        <p>
                            Se aplicará <b>${tipo}</b> a
                            <b>${cantidad}</b> alumno(s).
                        </p>

                        <p style='margin-top:8px;color:#64748b;font-size:13px'>
                            La generación y la ubicación académica se conservarán.
                        </p>
                    </div>
                `,
            showCancelButton: true,
            confirmButtonText: 'Sí, aplicar movimiento',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
            focusCancel: true
        }).then((resultado) => {
            if (resultado.isConfirmed) {
                this.$wire.call('aplicarMovimiento');
            }
        });
    },

    confirmarReincorporacion(id, nombre) {
        Swal.fire({
            icon: 'question',
            title: 'Registrar reincorporación',
            html: `
                    Se reincorporará a <b>${nombre}</b>.
                    <br>
                    <span style='color:#64748b;font-size:13px'>
                        Conservará su generación y ubicación académica actual.
                    </span>
                `,
            showCancelButton: true,
            confirmButtonText: 'Reincorporar alumno',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#7c3aed',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        }).then((resultado) => {
            if (resultado.isConfirmed) {
                this.$wire.call('reactivarAlumno', id);
            }
        });
    }
}" class="space-y-6">
    {{-- Loader general --}}
    <div wire:loading.flex
        wire:target="generacion_id,filtro_estatus,search,clearSearch,limpiarFiltros,aplicarMovimiento,reactivarAlumno,gotoPage,nextPage,previousPage"
        class="fixed inset-0 z-[9998] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div
            class="w-full max-w-sm overflow-hidden rounded-[30px] border border-white/20 bg-white/95 shadow-2xl shadow-rose-950/20 dark:bg-neutral-900/95">
            <div class="h-1.5 bg-gradient-to-r from-rose-500 via-red-600 to-violet-600"></div>

            <div class="p-7 text-center">
                <div class="relative mx-auto mb-5 h-16 w-16">
                    <div
                        class="absolute inset-0 animate-spin rounded-full border-4 border-rose-100 border-r-violet-600 border-t-rose-600 dark:border-rose-950">
                    </div>
                    <div
                        class="absolute inset-[10px] flex items-center justify-center rounded-2xl bg-gradient-to-br from-rose-600 to-violet-700 text-white shadow-lg shadow-rose-500/20">
                        <flux:icon.user-minus class="h-6 w-6" />
                    </div>
                </div>

                <h3 class="text-base font-black text-slate-900 dark:text-white">
                    Actualizando información
                </h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Procesando movimientos y datos de los alumnos…
                </p>
            </div>
        </div>
    </div>

    {{-- Navegación por nivel --}}
    <div class="overflow-x-auto pb-1">
        <div class="flex min-w-max justify-center gap-2">
            @foreach ($niveles as $item)
                @php($activoNivel = $slug_nivel === $item->slug)

                <a wire:navigate
                    href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'bajas']) }}"
                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-bold transition duration-200
                        {{ $activoNivel
                            ? 'border-rose-600 bg-gradient-to-r from-rose-600 to-red-600 text-white shadow-lg shadow-rose-600/20'
                            : 'border-slate-200 bg-white text-slate-600 hover:-translate-y-0.5 hover:border-rose-300 hover:text-rose-700 hover:shadow-md dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300 dark:hover:border-rose-800' }}">
                    <flux:icon.user-minus class="h-4 w-4" />
                    {{ $item->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Encabezado principal --}}
    <section
        class="relative overflow-hidden rounded-[30px] border border-white/60 bg-white/90 shadow-xl shadow-slate-200/60 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/90 dark:shadow-black/20">
        <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
        <div class="absolute -bottom-28 left-1/3 h-56 w-56 rounded-full bg-rose-400/20 blur-3xl"></div>

        <div
            class="relative bg-gradient-to-r from-slate-950 via-rose-950 to-violet-950 px-5 py-6 text-white sm:px-7 sm:py-7">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-rose-200 ring-1 ring-white/15 backdrop-blur">
                        <flux:icon.user-minus class="h-7 w-7" />
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">
                                Bajas y control de estatus
                            </h1>

                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-white ring-1 ring-white/15">
                                <span
                                    class="h-2 w-2 rounded-full {{ $generacionSeleccionada?->status ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                {{ $generacionSeleccionada?->status ? 'Generación activa' : 'Generación inactiva' }}
                            </span>
                        </div>

                        <p class="mt-2 max-w-3xl text-sm leading-6 text-rose-100/80">
                            Registra bajas, traslados, suspensiones e inactividad sin eliminar al alumno ni cambiar su
                            generación. También puedes reincorporarlo conservando su información académica.
                        </p>
                    </div>
                </div>

                <div class="min-w-[240px] rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/15 backdrop-blur">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-rose-200">
                        Contexto seleccionado
                    </p>
                    <p class="mt-1 text-base font-black">
                        {{ $nivel?->nombre ?? '—' }}
                    </p>
                    <p class="mt-0.5 text-sm text-white/75">
                        Generación {{ $generacionSeleccionada?->etiqueta ?? 'sin seleccionar' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="relative grid grid-cols-2 gap-px bg-slate-200 lg:grid-cols-4 dark:bg-neutral-700">
            <div class="bg-white p-4 sm:p-5 dark:bg-neutral-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Alumnos activos</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($total) }}
                        </p>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300">
                        <flux:icon.check-circle class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-5 dark:bg-neutral-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Hombres</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($hombres) }}
                        </p>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 dark:bg-sky-950/30 dark:text-sky-300">
                        <flux:icon.user class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-5 dark:bg-neutral-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Mujeres</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($mujeres) }}
                        </p>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-pink-50 text-pink-600 dark:bg-pink-950/30 dark:text-pink-300">
                        <flux:icon.user class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-5 dark:bg-neutral-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">No activos</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                            {{ number_format($totalBajas) }}</p>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-300">
                        <flux:icon.user-minus class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Filtros --}}
    <section
        class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div
            class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                    <flux:icon.funnel class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="font-black text-slate-900 dark:text-white">Filtros de consulta</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Localiza rápidamente a los alumnos de la
                        generación.</p>
                </div>
            </div>

            @if ($search !== '' || $filtro_estatus !== '')
                <flux:button type="button" variant="ghost" wire:click="limpiarFiltros"
                    class="cursor-pointer rounded-xl">
                    <flux:icon.x-mark class="h-4 w-4" />
                    Limpiar filtros
                </flux:button>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <div class="mb-1.5 flex items-center gap-2">
                    <flux:label>Generación</flux:label>
                    <span
                        class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-black uppercase text-rose-600 dark:bg-rose-950/30 dark:text-rose-300">Obligatorio</span>
                </div>

                <flux:select wire:model.live="generacion_id">
                    @if ($generaciones->isEmpty())
                        <flux:select.option value="">Sin generaciones registradas</flux:select.option>
                    @else
                        @foreach ($generaciones as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">
                                {{ $generacion->etiqueta }}{{ $generacion->status ? '' : ' · Inactiva' }}
                            </flux:select.option>
                        @endforeach
                    @endif
                </flux:select>
            </div>

            <div>
                <div class="mb-1.5 flex items-center gap-2">
                    <flux:label>Estatus activo</flux:label>
                </div>

                <flux:select wire:model.live="filtro_estatus">
                    <flux:select.option value="">Todos los activos</flux:select.option>
                    <flux:select.option value="activo">Activo</flux:select.option>
                    <flux:select.option value="reingreso">Reingreso</flux:select.option>
                    <flux:select.option value="no_promovido">No promovido</flux:select.option>
                </flux:select>
            </div>

            <div class="md:col-span-2">
                <div class="mb-1.5 flex items-center gap-2">
                    <flux:label>Buscar alumno</flux:label>
                </div>

                <div class="relative">
                    <flux:icon.magnifying-glass
                        class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <flux:input wire:model.live.debounce.350ms="search" type="search"
                        placeholder="Nombre, matrícula o CURP" class="pl-10" />
                </div>
            </div>
        </div>
    </section>

    {{-- Aplicación de movimiento --}}
    <section
        class="relative overflow-hidden rounded-[28px] border border-rose-200 bg-gradient-to-br from-rose-50 via-white to-orange-50 shadow-lg shadow-rose-100/40 dark:border-rose-900/50 dark:from-rose-950/25 dark:via-neutral-900 dark:to-orange-950/20 dark:shadow-none">
        <div class="absolute -right-16 -top-16 h-44 w-44 rounded-full bg-rose-300/25 blur-3xl dark:bg-rose-900/20">
        </div>

        <div class="relative p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-600 text-white shadow-lg shadow-rose-600/20">
                        <flux:icon.exclamation-triangle class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Registrar movimiento
                            administrativo</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            Selecciona alumnos activos, define el movimiento y confirma. Ningún registro será eliminado.
                        </p>
                    </div>
                </div>

                <div
                    class="inline-flex items-center gap-2 self-start rounded-full border border-rose-200 bg-white px-3 py-1.5 text-xs font-black text-rose-700 shadow-sm dark:border-rose-900/50 dark:bg-neutral-900 dark:text-rose-300">
                    <span
                        class="flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 text-[10px] text-white">
                        {{ $this->selectedCount }}
                    </span>
                    alumno(s) seleccionado(s)
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <div class="mb-1.5 flex items-center gap-2">
                        <flux:label>Tipo de movimiento</flux:label>
                        <span
                            class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-black uppercase text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">Obligatorio</span>
                    </div>

                    <flux:select wire:model.live="tipo_movimiento">
                        <flux:select.option value="baja_definitiva">Baja definitiva</flux:select.option>
                        <flux:select.option value="baja_temporal">Baja temporal</flux:select.option>
                        <flux:select.option value="trasladado">Traslado / cambio de escuela</flux:select.option>
                        <flux:select.option value="inactivo">Inactivo</flux:select.option>
                        <flux:select.option value="suspendido">Suspendido</flux:select.option>
                    </flux:select>
                </div>

                <div>
                    <div class="mb-1.5 flex items-center gap-2">
                        <flux:label>Fecha del movimiento</flux:label>
                        <span
                            class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-black uppercase text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">Obligatorio</span>
                    </div>

                    <flux:input wire:model="fecha_movimiento" type="date" />
                    @error('fecha_movimiento')
                        <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <div class="mb-1.5 flex items-center gap-2">
                        <flux:label>Motivo</flux:label>
                        <span
                            class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-black uppercase text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">Obligatorio</span>
                    </div>

                    <flux:input wire:model="motivo" placeholder="Describe el motivo del movimiento" />
                    @error('motivo')
                        <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2 xl:col-span-3">
                    <div class="mb-1.5 flex items-center gap-2">
                        <flux:label>Observaciones adicionales</flux:label>
                        <span
                            class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black uppercase text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">Opcional</span>
                    </div>

                    <flux:textarea wire:model="observaciones" rows="2"
                        placeholder="Agrega información complementaria" />
                    @error('observaciones')
                        <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-end">
                    <flux:button type="button" variant="primary"
                        x-on:click.prevent="confirmarMovimiento({{ $this->selectedCount }})"
                        wire:loading.attr="disabled" wire:target="aplicarMovimiento"
                        class="w-full cursor-pointer rounded-2xl bg-gradient-to-r from-rose-600 to-red-700">
                        <span wire:loading.remove wire:target="aplicarMovimiento"
                            class="inline-flex items-center gap-2">
                            <flux:icon.user-minus class="h-4 w-4" />
                            Aplicar movimiento
                        </span>
                        <span wire:loading wire:target="aplicarMovimiento" class="inline-flex items-center gap-2">
                            <span
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            Procesando…
                        </span>
                    </flux:button>
                </div>
            </div>

            @error('generacion_id')
                <p
                    class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
                    {{ $message }}</p>
            @enderror
            @error('selected')
                <p
                    class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
                    {{ $message }}</p>
            @enderror
        </div>
    </section>

    {{-- Tabla de activos --}}
    <section
        class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-lg shadow-slate-200/40 dark:border-neutral-700 dark:bg-neutral-900 dark:shadow-black/10">
        <div
            class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300">
                    <flux:icon.check-circle class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="font-black text-slate-900 dark:text-white">Alumnos activos disponibles</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Selecciona uno o varios registros para
                        aplicar el movimiento.</p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs font-bold text-slate-500 dark:text-slate-400">
                <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-neutral-800">Página:
                    {{ $activos->count() }}</span>
                <span
                    class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">Total:
                    {{ $activos->total() }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead
                    class="bg-slate-950 text-[10px] font-black uppercase tracking-[0.12em] text-slate-300 dark:bg-black">
                    <tr>
                        <th class="w-16 px-4 py-3 text-center">
                            <input type="checkbox" wire:model.live="selectPage"
                                aria-label="Seleccionar alumnos de la página"
                                class="rounded border-slate-500 bg-slate-800 text-rose-600 focus:ring-rose-500" />
                        </th>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">Matrícula / CURP</th>
                        <th class="px-4 py-3">Generación</th>
                        <th class="px-4 py-3">Ubicación académica</th>
                        <th class="px-4 py-3">Estatus</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @if ($activos->count() > 0)
                        @foreach ($activos as $alumnoActivo)
                            <tr wire:key="baja-activo-{{ $alumnoActivo->id }}"
                                class="transition hover:bg-rose-50/40 dark:hover:bg-rose-950/10">
                                <td class="px-4 py-4 text-center">
                                    <input type="checkbox" wire:model.live="selected"
                                        value="{{ $alumnoActivo->id }}"
                                        aria-label="Seleccionar {{ $this->nombreCompleto($alumnoActivo) }}"
                                        class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 dark:border-neutral-600 dark:bg-neutral-800" />
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-xs font-black text-slate-700 shadow-sm dark:from-neutral-700 dark:to-neutral-800 dark:text-slate-200">
                                            {{ $this->iniciales($alumnoActivo) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 dark:text-white">
                                                {{ $this->nombreCompleto($alumnoActivo) }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                {{ in_array($alumnoActivo->genero, ['H', 'Hombre'], true) ? 'Hombre' : 'Mujer' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <p class="font-black text-slate-900 dark:text-white">
                                        {{ $alumnoActivo->matricula ?: '—' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $alumnoActivo->curp ?: '—' }}</p>
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300">
                                        {{ $alumnoActivo->generacion?->etiqueta ?? '—' }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <p class="font-bold text-slate-800 dark:text-slate-200">
                                        {{ $alumnoActivo->grado?->nombre ?? '—' }} · Grupo
                                        {{ $this->textoGrupo($alumnoActivo->grupo) }}
                                    </p>
                                    @if ($alumnoActivo->semestre)
                                        <p class="mt-0.5 text-xs text-slate-500">Semestre
                                            {{ $alumnoActivo->semestre->numero }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black {{ $this->claseEstatus($alumnoActivo->estatus ?: 'activo') }}">
                                        {{ $this->etiquetaEstatus($alumnoActivo->estatus) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                                    <flux:icon.magnifying-glass class="h-6 w-6" />
                                </div>
                                <p class="mt-3 font-black text-slate-700 dark:text-slate-200">No se encontraron alumnos
                                    activos</p>
                                <p class="mt-1 text-sm text-slate-500">Cambia la generación o ajusta los filtros de
                                    búsqueda.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($activos->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-neutral-800">
                {{ $activos->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </section>

    {{-- Reincorporación --}}
    <section
        class="overflow-hidden rounded-[28px] border border-violet-200 bg-gradient-to-br from-violet-50 via-white to-indigo-50 shadow-lg shadow-violet-100/40 dark:border-violet-900/50 dark:from-violet-950/25 dark:via-neutral-900 dark:to-indigo-950/20 dark:shadow-none">
        <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-[1.1fr_1fr_1.4fr] lg:items-end sm:p-6">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-lg shadow-violet-600/20">
                    <flux:icon.arrow-path class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="font-black text-slate-900 dark:text-white">Datos para reincorporación</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Se utilizarán al presionar “Reincorporar” en la tabla inferior.
                    </p>
                </div>
            </div>

            <div>
                <div class="mb-1.5 flex items-center gap-2">
                    <flux:label>Fecha de reincorporación</flux:label>
                    <span
                        class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-black uppercase text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">Obligatorio</span>
                </div>
                <flux:input wire:model="fecha_reingreso" type="date" />
                @error('fecha_reingreso')
                    <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="mb-1.5 flex items-center gap-2">
                    <flux:label>Motivo o nota</flux:label>
                    <span
                        class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-black uppercase text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">Obligatorio</span>
                </div>
                <flux:input wire:model="motivo_reingreso" placeholder="Escribe el motivo de la reincorporación" />
                @error('motivo_reingreso')
                    <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    {{-- Tabla de estados no activos --}}
    <section
        class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-lg shadow-slate-200/40 dark:border-neutral-700 dark:bg-neutral-900 dark:shadow-black/10">
        <div
            class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-300">
                    <flux:icon.user-minus class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="font-black text-slate-900 dark:text-white">Bajas y estados no activos</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Los alumnos permanecen vinculados a su
                        generación original.</p>
                </div>
            </div>

            <span
                class="inline-flex self-start rounded-full bg-rose-50 px-3 py-1 text-xs font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">
                {{ $inactivos->total() }} registro(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1250px] text-left text-sm">
                <thead
                    class="bg-slate-950 text-[10px] font-black uppercase tracking-[0.12em] text-slate-300 dark:bg-black">
                    <tr>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">Matrícula / CURP</th>
                        <th class="px-4 py-3">Ubicación</th>
                        <th class="px-4 py-3">Estatus</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Motivo / observaciones</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @if ($inactivos->count() > 0)
                        @foreach ($inactivos as $alumnoInactivo)
                            <tr wire:key="baja-inactivo-{{ $alumnoInactivo->id }}"
                                class="align-top transition hover:bg-slate-50 dark:hover:bg-neutral-800/50">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-rose-100 to-orange-100 text-xs font-black text-rose-700 dark:from-rose-950/40 dark:to-orange-950/30 dark:text-rose-300">
                                            {{ $this->iniciales($alumnoInactivo) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 dark:text-white">
                                                {{ $this->nombreCompleto($alumnoInactivo) }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">Generación
                                                {{ $alumnoInactivo->generacion?->etiqueta ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <p class="font-black text-slate-900 dark:text-white">
                                        {{ $alumnoInactivo->matricula ?: '—' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $alumnoInactivo->curp ?: '—' }}</p>
                                </td>

                                <td class="px-4 py-4">
                                    <p class="font-bold text-slate-800 dark:text-slate-200">
                                        {{ $alumnoInactivo->grado?->nombre ?? '—' }} · Grupo
                                        {{ $this->textoGrupo($alumnoInactivo->grupo) }}
                                    </p>
                                    @if ($alumnoInactivo->semestre)
                                        <p class="mt-0.5 text-xs text-slate-500">Semestre
                                            {{ $alumnoInactivo->semestre->numero }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black {{ $this->claseEstatus($alumnoInactivo->estatus) }}">
                                        {{ $this->etiquetaEstatus($alumnoInactivo->estatus) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 font-bold text-slate-700 dark:text-slate-300">
                                        <flux:icon.calendar-days class="h-4 w-4 text-slate-400" />
                                        {{ $this->fechaMovimientoTexto($alumnoInactivo) }}
                                    </span>
                                </td>

                                <td class="max-w-sm px-4 py-4">
                                    <p class="font-semibold leading-5 text-slate-700 dark:text-slate-200">
                                        {{ $alumnoInactivo->motivo_estatus ?: $alumnoInactivo->motivo_baja ?: 'Sin motivo registrado' }}
                                    </p>

                                    @if ($alumnoInactivo->observaciones_baja)
                                        <p
                                            class="mt-1.5 rounded-xl bg-slate-50 px-2.5 py-2 text-xs leading-5 text-slate-500 dark:bg-neutral-800 dark:text-slate-400">
                                            {{ $alumnoInactivo->observaciones_baja }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex justify-end">
                                        @if ($alumnoInactivo->estatus !== 'egresado')
                                            <button type="button"
                                                x-on:click.prevent="confirmarReincorporacion(
                                                    {{ $alumnoInactivo->id }},
                                                    @js($this->nombreCompleto($alumnoInactivo))
                                                )"
                                                wire:loading.attr="disabled"
                                                wire:target="reactivarAlumno({{ $alumnoInactivo->id }})"
                                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-violet-600/20 transition hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-neutral-900">
                                                <flux:icon.arrow-path wire:loading.remove
                                                    wire:target="reactivarAlumno({{ $alumnoInactivo->id }})"
                                                    class="h-4 w-4" />
                                                <flux:icon.arrow-path wire:loading
                                                    wire:target="reactivarAlumno({{ $alumnoInactivo->id }})"
                                                    class="h-4 w-4 animate-spin" />
                                                <span wire:loading.remove
                                                    wire:target="reactivarAlumno({{ $alumnoInactivo->id }})">Reincorporar</span>
                                                <span wire:loading
                                                    wire:target="reactivarAlumno({{ $alumnoInactivo->id }})">Procesando…</span>
                                            </button>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-black text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                                <flux:icon.check-circle class="h-4 w-4" />
                                                Egreso confirmado
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    <flux:icon.check-circle class="h-6 w-6" />
                                </div>
                                <p class="mt-3 font-black text-slate-700 dark:text-slate-200">No hay movimientos
                                    registrados</p>
                                <p class="mt-1 text-sm text-slate-500">Esta generación no tiene bajas, traslados ni
                                    alumnos inactivos.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($inactivos->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-neutral-800">
                {{ $inactivos->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </section>
</div>
