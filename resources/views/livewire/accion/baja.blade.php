<div
    x-data="{
        confirmarReincorporacion(id, nombre) {
            Swal.fire({
                title: 'Registrar reincorporación',
                html: `Se reactivará a <b>${nombre}</b> y conservará su generación original.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Reincorporar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#7c3aed'
            }).then((resultado) => {
                if (resultado.isConfirmed) {
                    this.$wire.reactivarAlumno(id);
                }
            });
        }
    }"
    class="space-y-5"
>
    {{-- Loader general --}}
    <div
        wire:loading.delay.longer
        wire:target="generacion_id,filtro_estatus,search,clearSearch,aplicarMovimiento,reactivarAlumno,gotoPage,nextPage,previousPage"
        class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm"
    >
        <div class="mx-4 w-full max-w-sm rounded-[28px] border border-red-100 bg-white/95 p-7 text-center shadow-2xl shadow-red-500/20 dark:border-red-900/40 dark:bg-neutral-900/95">
            <div class="relative mx-auto mb-5 flex h-16 w-16 items-center justify-center">
                <div class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-r-fuchsia-600 border-t-red-600"></div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-red-600 to-fuchsia-700 text-white">
                    <flux:icon.user-minus class="h-5 w-5" />
                </div>
            </div>
            <h3 class="font-bold text-slate-900 dark:text-white">Actualizando bajas</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Procesando la información de los alumnos…</p>
        </div>
    </div>

    {{-- Navegación por nivel --}}
    <div class="overflow-x-auto pb-1">
        <div class="flex min-w-max justify-center gap-2">
            @foreach ($niveles as $item)
                @php($activoNivel = $slug_nivel === $item->slug)
                <a
                    wire:navigate
                    href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'bajas']) }}"
                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition
                        {{ $activoNivel
                            ? 'border-red-600 bg-red-600 text-white shadow-lg shadow-red-600/20'
                            : 'border-slate-200 bg-white text-slate-700 hover:border-red-300 hover:text-red-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200' }}"
                >
                    <flux:icon.user-minus class="h-4 w-4" />
                    {{ $item->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Encabezado y resumen --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="bg-gradient-to-r from-red-700 via-rose-700 to-fuchsia-800 p-5 text-white sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black">Estados, bajas, traslados y reincorporaciones</h1>
                        @if ($generacionSeleccionada?->status)
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold ring-1 ring-white/25">Generación activa</span>
                        @else
                            <span class="rounded-full bg-amber-300/20 px-3 py-1 text-xs font-bold ring-1 ring-amber-200/30">Generación inactiva</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-rose-100">Cada movimiento modifica el estatus actual del alumno sin cambiar ni eliminar su generación.</p>
                </div>

                <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/20">
                    <p class="text-xs font-bold uppercase tracking-wide text-rose-100">Contexto</p>
                    <p class="mt-1 font-black">
                        {{ $nivel?->nombre ?? '—' }} · {{ $generacionSeleccionada?->etiqueta ?? 'Todas las generaciones' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-4 dark:bg-neutral-700">
            @foreach ([['Activos', $total], ['Hombres', $hombres], ['Mujeres', $mujeres], ['No activos', $totalBajas]] as [$etiqueta, $valor])
                <div class="bg-white p-4 dark:bg-neutral-900">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $etiqueta }}</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($valor) }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Filtros --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="grid gap-3 md:grid-cols-3">
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Generación</span>
                <select wire:model.live="generacion_id" class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-800">
                    @forelse ($generaciones as $generacion)
                        <option value="{{ $generacion->id }}">
                            {{ $generacion->etiqueta }}{{ $generacion->status ? '' : ' · Inactiva' }}
                        </option>
                    @empty
                        <option value="">Sin generaciones registradas</option>
                    @endforelse
                </select>
            </label>

            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Estatus de matrícula activa</span>
                <select wire:model.live="filtro_estatus" class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-800">
                    <option value="">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="reingreso">Reingreso</option>
                    <option value="no_promovido">No promovido</option>
                </select>
            </label>

            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Buscar alumno</span>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            wire:model.live.debounce.350ms="search"
                            type="search"
                            placeholder="Nombre, matrícula o CURP"
                            class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 text-sm dark:border-neutral-700 dark:bg-neutral-800"
                        />
                    </div>

                    @if ($search !== '')
                        <button
                            type="button"
                            wire:click="clearSearch"
                            title="Limpiar búsqueda"
                            class="rounded-xl border border-slate-200 px-3 text-slate-500 transition hover:bg-slate-50 dark:border-neutral-700 dark:hover:bg-neutral-800"
                        >
                            <flux:icon.x-mark class="h-4 w-4" />
                        </button>
                    @endif
                </div>
            </label>
        </div>
    </section>

    {{-- Aplicar movimiento --}}
    <section class="rounded-3xl border border-red-200 bg-red-50/60 p-5 shadow-sm dark:border-red-900/50 dark:bg-red-950/20">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-lg font-black text-red-950 dark:text-red-100">Aplicar movimiento a alumnos activos</h2>
                <p class="text-sm text-red-800/80 dark:text-red-200/70">El alumno conservará su generación y pasará a la lista de estados no activos.</p>
            </div>

            <span class="rounded-full bg-red-200 px-3 py-1 text-xs font-black text-red-900 dark:bg-red-900/50 dark:text-red-100">
                {{ $this->selectedCount }} seleccionado(s)
            </span>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-red-700 dark:text-red-300">Tipo</span>
                <select wire:model="tipo_movimiento" class="w-full rounded-xl border-red-300 bg-white text-sm dark:border-red-800 dark:bg-neutral-900">
                    <option value="baja_definitiva">Baja definitiva</option>
                    <option value="baja_temporal">Baja temporal</option>
                    <option value="trasladado">Traslado / cambio de escuela</option>
                    <option value="inactivo">Inactivo</option>
                    <option value="suspendido">Suspendido</option>
                </select>
            </label>

            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-red-700 dark:text-red-300">Fecha</span>
                <input wire:model="fecha_movimiento" type="date" class="w-full rounded-xl border-red-300 bg-white text-sm dark:border-red-800 dark:bg-neutral-900" />
            </label>

            <label class="space-y-1.5 md:col-span-2">
                <span class="text-xs font-bold uppercase tracking-wide text-red-700 dark:text-red-300">Motivo <span class="normal-case font-medium">(obligatorio)</span></span>
                <input
                    wire:model="motivo"
                    type="text"
                    placeholder="Escribe el motivo del movimiento"
                    class="w-full rounded-xl border-red-300 bg-white text-sm dark:border-red-800 dark:bg-neutral-900"
                />
            </label>

            <label class="space-y-1.5 md:col-span-2 xl:col-span-3">
                <span class="text-xs font-bold uppercase tracking-wide text-red-700 dark:text-red-300">Observaciones</span>
                <textarea
                    wire:model="observaciones"
                    rows="2"
                    placeholder="Información adicional (opcional)"
                    class="w-full rounded-xl border-red-300 bg-white text-sm dark:border-red-800 dark:bg-neutral-900"
                ></textarea>
            </label>

            <div class="flex items-end">
                <button
                    type="button"
                    wire:click="aplicarMovimiento"
                    wire:loading.attr="disabled"
                    wire:target="aplicarMovimiento"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-700 px-4 py-3 text-sm font-black text-white transition hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="aplicarMovimiento" class="inline-flex items-center gap-2">
                        <flux:icon.user-minus class="h-4 w-4" /> Aplicar movimiento
                    </span>
                    <span wire:loading.flex wire:target="aplicarMovimiento" class="items-center gap-2">
                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        Procesando…
                    </span>
                </button>
            </div>
        </div>

        @error('generacion_id') <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
        @error('selected') <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
        @error('motivo') <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
        @error('fecha_movimiento') <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
        @error('observaciones') <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
    </section>

    {{-- Alumnos activos --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-col gap-2 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-700">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white">Alumnos activos disponibles</h2>
                <p class="text-sm text-slate-500">Marca los alumnos a los que se aplicará el estado no activo.</p>
            </div>

            <div wire:loading.delay wire:target="generacion_id,filtro_estatus,search,clearSearch" class="text-sm font-semibold text-red-600">
                Actualizando información…
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1050px] w-full text-left text-sm">
                <thead class="bg-slate-900 text-xs uppercase tracking-wide text-white dark:bg-black">
                    <tr>
                        <th class="px-4 py-3 text-center">
                            <input type="checkbox" wire:model.live="selectPage" class="rounded border-slate-300 text-red-600 focus:ring-red-500" />
                        </th>
                        <th class="px-4 py-3">Matrícula</th>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">CURP</th>
                        <th class="px-4 py-3">Generación</th>
                        <th class="px-4 py-3">Grado / grupo</th>
                        <th class="px-4 py-3">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @if ($activos->isNotEmpty())
                        @foreach ($activos as $alumnoActivo)
                            @php
                                $nombreCompletoActivo = trim(
                                    ($alumnoActivo->apellido_paterno ?? '') . ' ' .
                                    ($alumnoActivo->apellido_materno ?? '') . ' ' .
                                    ($alumnoActivo->nombre ?? '')
                                );
                            @endphp

                            <tr wire:key="baja-activo-{{ $alumnoActivo->id }}" class="hover:bg-red-50/40 dark:hover:bg-red-950/10">
                                <td class="px-4 py-4 text-center">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selected"
                                        value="{{ $alumnoActivo->id }}"
                                        class="rounded border-slate-300 text-red-600 focus:ring-red-500"
                                    />
                                </td>
                                <td class="px-4 py-4 font-black text-slate-900 dark:text-white">{{ $alumnoActivo->matricula ?: '—' }}</td>
                                <td class="px-4 py-4 font-bold text-slate-800 dark:text-slate-100">{{ $nombreCompletoActivo ?: '—' }}</td>
                                <td class="px-4 py-4 text-slate-500">{{ $alumnoActivo->curp ?: '—' }}</td>
                                <td class="px-4 py-4">{{ $alumnoActivo->generacion?->etiqueta ?? '—' }}</td>
                                <td class="px-4 py-4">
                                    {{ $alumnoActivo->grado?->nombre ?? '—' }} · {{ $this->textoGrupo($alumnoActivo->grupo) }}
                                    @if ($alumnoActivo->semestre)
                                        <span class="text-xs text-slate-500">/ Sem. {{ $alumnoActivo->semestre->numero }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                                        {{ $this->etiquetaEstatus($alumnoActivo->estatus) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center text-slate-500">No hay alumnos activos en esta generación.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($activos->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-neutral-700">
                {{ $activos->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </section>

    {{-- Configuración de reincorporación --}}
    <section class="rounded-3xl border border-violet-200 bg-violet-50/50 p-5 shadow-sm dark:border-violet-900/50 dark:bg-violet-950/15">
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <h2 class="font-black text-violet-950 dark:text-violet-100">Configuración de reincorporación rápida</h2>
                <p class="mt-1 text-sm text-violet-800/75 dark:text-violet-200/70">Estos datos se usarán al pulsar “Reincorporar”. El alumno conservará su generación y ubicación académica actual.</p>
            </div>

            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-violet-700 dark:text-violet-300">Fecha de reincorporación</span>
                <input wire:model="fecha_reingreso" type="date" class="w-full rounded-xl border-violet-300 bg-white text-sm dark:border-violet-800 dark:bg-neutral-900" />
                @error('fecha_reingreso') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-violet-700 dark:text-violet-300">Motivo / nota</span>
                <input wire:model="motivo_reingreso" type="text" placeholder="Motivo obligatorio" class="w-full rounded-xl border-violet-300 bg-white text-sm dark:border-violet-800 dark:bg-neutral-900" />
                @error('motivo_reingreso') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    {{-- Estados no activos --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-col gap-2 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-700">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white">Estados no activos de la generación</h2>
                <p class="text-sm text-slate-500">Las bajas, traslados e inactivos permanecen vinculados a su generación original.</p>
            </div>

            <div wire:loading.delay wire:target="reactivarAlumno" class="text-sm font-semibold text-violet-600">
                Registrando reincorporación…
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1250px] w-full text-left text-sm">
                <thead class="bg-slate-900 text-xs uppercase tracking-wide text-white dark:bg-black">
                    <tr>
                        <th class="px-4 py-3">Matrícula</th>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">Ubicación actual</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Motivo / observaciones</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @if ($inactivos->isNotEmpty())
                        @foreach ($inactivos as $alumnoInactivo)
                            @php
                                $nombreCompletoInactivo = trim(
                                    ($alumnoInactivo->apellido_paterno ?? '') . ' ' .
                                    ($alumnoInactivo->apellido_materno ?? '') . ' ' .
                                    ($alumnoInactivo->nombre ?? '')
                                );
                                $estatusClase = match ($alumnoInactivo->estatus) {
                                    'egresado' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-200',
                                    'trasladado' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200',
                                    'suspendido' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-200',
                                    default => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-200',
                                };
                            @endphp

                            <tr wire:key="baja-registrada-{{ $alumnoInactivo->id }}" class="align-top hover:bg-slate-50 dark:hover:bg-neutral-800/50">
                                <td class="px-4 py-4 font-black text-slate-900 dark:text-white">{{ $alumnoInactivo->matricula ?: '—' }}</td>
                                <td class="px-4 py-4">
                                    <p class="font-bold text-slate-800 dark:text-slate-100">{{ $nombreCompletoInactivo ?: '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $alumnoInactivo->curp ?: '—' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    {{ $alumnoInactivo->grado?->nombre ?? '—' }} · {{ $this->textoGrupo($alumnoInactivo->grupo) }}
                                    @if ($alumnoInactivo->semestre)
                                        <br><span class="text-xs text-slate-500">Semestre {{ $alumnoInactivo->semestre->numero }}</span>
                                    @endif
                                    <br><span class="text-xs font-semibold text-slate-500">Generación {{ $alumnoInactivo->generacion?->etiqueta ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $estatusClase }}">
                                        {{ $this->etiquetaEstatus($alumnoInactivo->estatus) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    {{ optional($alumnoInactivo->fecha_estatus)->format('d/m/Y') ?: optional($alumnoInactivo->fecha_baja)->format('d/m/Y') ?: '—' }}
                                </td>
                                <td class="max-w-sm px-4 py-4">
                                    <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $alumnoInactivo->motivo_estatus ?: $alumnoInactivo->motivo_baja ?: '—' }}</p>
                                    @if ($alumnoInactivo->observaciones_baja)
                                        <p class="mt-1 text-xs text-slate-500">{{ $alumnoInactivo->observaciones_baja }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end">
                                        @if ($alumnoInactivo->estatus !== 'egresado')
                                            <button
                                                type="button"
                                                x-on:click="confirmarReincorporacion({{ $alumnoInactivo->id }}, @js($nombreCompletoInactivo))"
                                                wire:loading.attr="disabled"
                                                wire:target="reactivarAlumno"
                                                class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-3 py-2 text-xs font-black text-white transition hover:bg-violet-700 disabled:opacity-50"
                                            >
                                                <flux:icon.arrow-path class="h-4 w-4" />
                                                Reincorporar
                                            </button>
                                        @else
                                            <span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-500 dark:bg-neutral-800">Egreso confirmado</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center text-slate-500">No hay bajas, traslados ni alumnos inactivos en esta generación.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($inactivos->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-neutral-700">
                {{ $inactivos->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </section>
</div>
