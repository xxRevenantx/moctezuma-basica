<div class="space-y-5">
    <div class="relative overflow-hidden rounded-[1.6rem] border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-lime-50 p-5 shadow-sm dark:border-sky-900/50 dark:from-sky-950/30 dark:via-neutral-900 dark:to-lime-950/20 sm:p-6">
        <div class="absolute -right-14 -top-14 h-40 w-40 rounded-full bg-[#006492]/10 blur-2xl"></div>
        <div class="absolute -bottom-16 left-1/3 h-36 w-36 rounded-full bg-[#88AC2E]/10 blur-2xl"></div>

        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] text-white shadow-lg shadow-sky-600/20">
                    <flux:icon.archive-box class="h-7 w-7" />
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-sky-200 bg-white/80 px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-[#006492] dark:border-sky-900/50 dark:bg-neutral-900/70 dark:text-sky-300">
                            Historial escolar
                        </span>
                        <span class="rounded-full border border-lime-200 bg-lime-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-lime-700 dark:border-lime-900/50 dark:bg-lime-950/30 dark:text-lime-300">
                            PDF + Word
                        </span>
                    </div>

                    <h3 class="mt-3 text-xl font-black text-slate-900 dark:text-white">
                        Listas históricas de generaciones
                    </h3>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Selecciona una o varias generaciones de {{ $nivel->nombre }}, filtra por estatus o grupo y genera un padrón institucional en PDF o Word editable.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 sm:min-w-[330px]">
                <div class="rounded-2xl border border-white/80 bg-white/85 p-3 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-800/80">
                    <p class="text-2xl font-black text-[#006492] dark:text-sky-300">{{ $generacionesEgresadas->count() }}</p>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Egresadas</p>
                </div>
                <div class="rounded-2xl border border-white/80 bg-white/85 p-3 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-800/80">
                    <p class="text-2xl font-black text-[#88AC2E] dark:text-lime-300">{{ $generacionesActivas->count() }}</p>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Activas</p>
                </div>
                <div class="rounded-2xl border border-white/80 bg-white/85 p-3 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-800/80">
                    <p class="text-2xl font-black text-violet-600 dark:text-violet-300">{{ count($generacionesSeleccionadas) }}</p>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Elegidas</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1.15fr_.85fr]">
        <section class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-950/40 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-[#006492] dark:text-sky-300">Paso 1</p>
                    <h4 class="mt-1 text-base font-black text-slate-900 dark:text-white">Selecciona las generaciones</h4>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="seleccionarEgresadas"
                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 transition hover:-translate-y-0.5 hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                        <flux:icon.check-circle class="h-4 w-4" />
                        Todas las egresadas
                    </button>
                    <button type="button" wire:click="seleccionarTodas"
                        class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-black text-sky-700 transition hover:-translate-y-0.5 hover:bg-sky-100 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300">
                        <flux:icon.squares-plus class="h-4 w-4" />
                        Seleccionar todas
                    </button>
                    <button type="button" wire:click="limpiarSeleccion"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-100 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300">
                        <flux:icon.x-mark class="h-4 w-4" />
                        Limpiar
                    </button>
                </div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-white dark:border-emerald-900/50 dark:bg-neutral-900">
                    <div class="flex items-center justify-between border-b border-emerald-100 bg-emerald-50/80 px-4 py-3 dark:border-emerald-900/40 dark:bg-emerald-950/25">
                        <div>
                            <p class="text-sm font-black text-emerald-800 dark:text-emerald-300">Egresadas / cerradas</p>
                            <p class="text-xs text-emerald-700/70 dark:text-emerald-300/70">Generaciones con status inactivo</p>
                        </div>
                        <span class="rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-black text-white">{{ $generacionesEgresadas->count() }}</span>
                    </div>

                    <div class="max-h-72 space-y-2 overflow-y-auto p-3">
                        @forelse ($generacionesEgresadas as $generacion)
                            <label wire:key="generacion-historica-egresada-{{ $generacion->id }}"
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3 transition hover:border-emerald-300 hover:bg-emerald-50/60 dark:border-neutral-700 dark:bg-neutral-800/60 dark:hover:border-emerald-800 dark:hover:bg-emerald-950/20">
                                <input type="checkbox" value="{{ $generacion->id }}" wire:model.live="generacionesSeleccionadas"
                                    class="mt-0.5 h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-neutral-600 dark:bg-neutral-900">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-black text-slate-800 dark:text-white">Generación {{ $this->etiquetaGeneracion($generacion) }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400">
                                        {{ $generacion->motivo_desactivacion ?: 'Generación cerrada o histórica' }}
                                    </span>
                                </span>
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-black uppercase text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Egresada</span>
                            </label>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500 dark:border-neutral-700">
                                No hay generaciones egresadas registradas en este nivel.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-sky-200 bg-white dark:border-sky-900/50 dark:bg-neutral-900">
                    <div class="flex items-center justify-between border-b border-sky-100 bg-sky-50/80 px-4 py-3 dark:border-sky-900/40 dark:bg-sky-950/25">
                        <div>
                            <p class="text-sm font-black text-sky-800 dark:text-sky-300">Generaciones activas</p>
                            <p class="text-xs text-sky-700/70 dark:text-sky-300/70">Disponibles para consultas actuales</p>
                        </div>
                        <span class="rounded-full bg-[#006492] px-2.5 py-1 text-xs font-black text-white">{{ $generacionesActivas->count() }}</span>
                    </div>

                    <div class="max-h-72 space-y-2 overflow-y-auto p-3">
                        @forelse ($generacionesActivas as $generacion)
                            <label wire:key="generacion-historica-activa-{{ $generacion->id }}"
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3 transition hover:border-sky-300 hover:bg-sky-50/60 dark:border-neutral-700 dark:bg-neutral-800/60 dark:hover:border-sky-800 dark:hover:bg-sky-950/20">
                                <input type="checkbox" value="{{ $generacion->id }}" wire:model.live="generacionesSeleccionadas"
                                    class="mt-0.5 h-5 w-5 rounded border-slate-300 text-[#006492] focus:ring-sky-500 dark:border-neutral-600 dark:bg-neutral-900">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-black text-slate-800 dark:text-white">Generación {{ $this->etiquetaGeneracion($generacion) }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">
                                        {{ $generacion->fecha_termino?->format('d/m/Y') ? 'Finaliza: ' . $generacion->fecha_termino->format('d/m/Y') : 'Actualmente activa' }}
                                    </span>
                                </span>
                                <span class="rounded-full bg-sky-100 px-2 py-1 text-[10px] font-black uppercase text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">Activa</span>
                            </label>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500 dark:border-neutral-700">
                                No hay generaciones activas registradas.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-5">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-[#88AC2E] dark:text-lime-300">Paso 2</p>
                <h4 class="mt-1 text-base font-black text-slate-900 dark:text-white">Configura el documento</h4>
            </div>

            <div class="mt-4 space-y-4">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Estatus de alumnos</span>
                    <select wire:model.live="estatus"
                        class="w-full rounded-xl border-slate-300 bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-[#006492] focus:ring-[#006492] dark:border-neutral-700 dark:bg-neutral-800 dark:text-white">
                        @foreach ($estatusDisponibles as $valor => $texto)
                            <option value="{{ $valor }}">{{ $texto }}</option>
                        @endforeach
                    </select>
                    <span class="mt-1.5 block text-xs text-slate-500 dark:text-slate-400">Por defecto se incluyen únicamente alumnos con estatus egresado.</span>
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Grupo específico</span>
                    <select wire:model.live="grupo_id" @disabled($grupos->isEmpty())
                        class="w-full rounded-xl border-slate-300 bg-white text-sm font-semibold text-slate-800 shadow-sm focus:border-[#006492] focus:ring-[#006492] disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white">
                        <option value="">Todos los grupos de las generaciones</option>
                        @foreach ($grupos as $grupo)
                            <option value="{{ $grupo->id }}">{{ $this->etiquetaGrupo($grupo) }}</option>
                        @endforeach
                    </select>
                </label>

                <div>
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Tipo de salida</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" value="unico" wire:model.live="salida" class="peer sr-only">
                            <span class="flex h-full items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 transition peer-checked:border-[#006492] peer-checked:bg-sky-50 peer-checked:ring-2 peer-checked:ring-sky-100 dark:border-neutral-700 dark:bg-neutral-800 dark:peer-checked:border-sky-600 dark:peer-checked:bg-sky-950/30">
                                <flux:icon.document-text class="mt-0.5 h-5 w-5 text-[#006492]" />
                                <span>
                                    <span class="block text-sm font-black text-slate-800 dark:text-white">Documento único</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">Una sección por generación</span>
                                </span>
                            </span>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" value="zip" wire:model.live="salida" class="peer sr-only">
                            <span class="flex h-full items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 transition peer-checked:border-violet-500 peer-checked:bg-violet-50 peer-checked:ring-2 peer-checked:ring-violet-100 dark:border-neutral-700 dark:bg-neutral-800 dark:peer-checked:border-violet-600 dark:peer-checked:bg-violet-950/30">
                                <flux:icon.archive-box-arrow-down class="mt-0.5 h-5 w-5 text-violet-600" />
                                <span>
                                    <span class="block text-sm font-black text-slate-800 dark:text-white">Archivos ZIP</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">Un archivo por generación</span>
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-900/50 dark:bg-amber-950/20">
                    <input type="checkbox" wire:model.live="incluir_archivados"
                        class="mt-0.5 h-5 w-5 rounded border-amber-300 text-amber-600 focus:ring-amber-500 dark:border-amber-800 dark:bg-neutral-900">
                    <span>
                        <span class="block text-sm font-black text-amber-900 dark:text-amber-200">Incluir alumnos archivados</span>
                        <span class="mt-0.5 block text-xs leading-5 text-amber-700 dark:text-amber-300/80">Recupera registros eliminados lógicamente mediante SoftDeletes y los identifica en la columna de estatus.</span>
                    </span>
                </label>
            </div>
        </section>
    </div>

    <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-slate-200 bg-slate-50/70 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/30 sm:px-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-violet-600 dark:text-violet-300">Vista previa de la selección</p>
                    <h4 class="mt-1 text-base font-black text-slate-900 dark:text-white">Resumen del padrón a generar</h4>
                </div>
                <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300">
                    {{ count($generacionesSeleccionadas) }} generación(es) seleccionada(s)
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-4 xl:grid-cols-7 sm:p-5">
            @php
                $tarjetas = [
                    ['Total', $this->resumenSeleccion['total'], 'text-slate-900 dark:text-white', 'bg-slate-100 dark:bg-neutral-800'],
                    ['Hombres', $this->resumenSeleccion['hombres'], 'text-blue-700 dark:text-blue-300', 'bg-blue-50 dark:bg-blue-950/30'],
                    ['Mujeres', $this->resumenSeleccion['mujeres'], 'text-pink-700 dark:text-pink-300', 'bg-pink-50 dark:bg-pink-950/30'],
                    ['Egresados', $this->resumenSeleccion['egresados'], 'text-emerald-700 dark:text-emerald-300', 'bg-emerald-50 dark:bg-emerald-950/30'],
                    ['Bajas', $this->resumenSeleccion['bajas'], 'text-orange-700 dark:text-orange-300', 'bg-orange-50 dark:bg-orange-950/30'],
                    ['Trasladados', $this->resumenSeleccion['trasladados'], 'text-violet-700 dark:text-violet-300', 'bg-violet-50 dark:bg-violet-950/30'],
                    ['Archivados', $this->resumenSeleccion['archivados'], 'text-slate-700 dark:text-slate-300', 'bg-slate-100 dark:bg-neutral-800'],
                ];
            @endphp

            @foreach ($tarjetas as [$etiqueta, $valor, $texto, $fondo])
                <div class="rounded-2xl border border-slate-200 p-3 text-center dark:border-neutral-700 {{ $fondo }}">
                    <p class="text-2xl font-black {{ $texto }}">{{ $valor }}</p>
                    <p class="mt-0.5 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $etiqueta }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="flex flex-col gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#006492]/10 text-[#006492] dark:bg-sky-950/40 dark:text-sky-300">
                <flux:icon.information-circle class="h-5 w-5" />
            </div>
            <div>
                <p class="text-sm font-black text-slate-800 dark:text-white">
                    {{ $salida === 'zip' ? 'Se creará un ZIP con un documento por generación.' : 'Se creará un documento con secciones separadas por generación.' }}
                </p>
                <p class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-slate-400">
                    El PDF usa orientación carta horizontal. El Word conserva el diseño institucional y permanece completamente editable.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($this->urlVistaPdf)
                <a href="{{ $this->urlVistaPdf }}" target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-300 hover:text-[#006492] dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                    <flux:icon.eye class="h-4 w-4" />
                    Vista previa PDF
                </a>
            @else
                <span class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-black text-slate-400 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-slate-600">
                    <flux:icon.eye class="h-4 w-4" />
                    Vista previa PDF
                </span>
            @endif

            @if ($this->urlPdf)
                <a href="{{ $this->urlPdf }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-rose-600 to-orange-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-rose-600/20 transition hover:-translate-y-0.5 hover:shadow-xl">
                    <flux:icon.arrow-down-tray class="h-4 w-4" />
                    {{ $salida === 'zip' ? 'Descargar ZIP PDF' : 'Descargar PDF' }}
                </a>
            @else
                <span class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-slate-300 px-4 py-2.5 text-sm font-black text-white dark:bg-neutral-700">
                    <flux:icon.arrow-down-tray class="h-4 w-4" />
                    Descargar PDF
                </span>
            @endif

            @if ($this->urlWord)
                <a href="{{ $this->urlWord }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#006492] to-blue-700 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-sky-700/20 transition hover:-translate-y-0.5 hover:shadow-xl">
                    <flux:icon.document-arrow-down class="h-4 w-4" />
                    {{ $salida === 'zip' ? 'Descargar ZIP Word' : 'Descargar Word' }}
                </a>
            @else
                <span class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-slate-300 px-4 py-2.5 text-sm font-black text-white dark:bg-neutral-700">
                    <flux:icon.document-arrow-down class="h-4 w-4" />
                    Descargar Word
                </span>
            @endif
        </div>
    </div>

    <div wire:loading.flex wire:target="generacionesSeleccionadas,estatus,grupo_id,incluir_archivados,seleccionarEgresadas,seleccionarTodas,limpiarSeleccion"
        class="fixed inset-0 z-50 items-center justify-center bg-slate-950/20 backdrop-blur-[1px]">
        <div class="flex items-center gap-3 rounded-2xl border border-white/60 bg-white px-5 py-4 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">
            <svg class="h-5 w-5 animate-spin text-[#006492]" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span class="text-sm font-black text-slate-700 dark:text-slate-200">Actualizando selección…</span>
        </div>
    </div>
</div>
