<div class="space-y-4">
    <section
        class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="h-1.5 w-full bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>

        <div wire:loading.flex wire:target="descargarPlantillaPeriodos,importarPeriodos,archivo_periodos"
            class="absolute inset-0 z-30 hidden items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-slate-950/75">
            <div
                class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <span class="h-5 w-5 animate-spin rounded-full border-2 border-sky-200 border-t-sky-600"></span>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Procesando plantilla de periodos...
                </span>
            </div>
        </div>

        <div class="space-y-5 p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-[#006492] dark:bg-sky-950/40 dark:text-sky-300">
                        <flux:icon.table-cells class="h-5 w-5" />
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            Importar periodos desde Excel
                        </h2>
                        <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500 dark:text-slate-400">
                            Descarga la plantilla con catálogos de la base de datos. Los periodos nuevos se crean y los
                            existentes actualizan únicamente sus fechas, evitando registros duplicados.
                        </p>
                    </div>
                </div>

                <flux:button type="button" variant="ghost" wire:click="descargarPlantillaPeriodos"
                    wire:loading.attr="disabled" wire:target="descargarPlantillaPeriodos"
                    class="cursor-pointer rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                    <span class="inline-flex items-center gap-2">
                        <flux:icon.document-arrow-down class="h-4 w-4" />
                        Descargar plantilla
                    </span>
                </flux:button>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_auto_auto] lg:items-end">
                <div>
                    <label
                        class="group flex cursor-pointer items-center justify-between gap-4 rounded-2xl border-2 border-dashed border-sky-200 bg-sky-50/60 px-4 py-4 transition hover:border-sky-400 hover:bg-sky-50 dark:border-sky-900/50 dark:bg-sky-950/20">
                        <input type="file" wire:model="archivo_periodos" accept=".xlsx,.xls" class="hidden">

                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-sky-600 shadow-sm dark:bg-slate-800 dark:text-sky-300">
                                <flux:icon.cloud-arrow-up class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    {{ $archivo_periodos ? 'Archivo seleccionado' : 'Selecciona la plantilla completada' }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Formatos permitidos: XLSX o XLS. Máximo 10 MB.
                                </p>
                            </div>
                        </div>

                        <span
                            class="shrink-0 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-sky-700 shadow-sm ring-1 ring-sky-100 dark:bg-slate-800 dark:text-sky-300 dark:ring-sky-900/40">
                            Buscar archivo
                        </span>
                    </label>

                    @error('archivo_periodos')
                        <p class="mt-2 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <flux:button type="button" variant="primary" wire:click="importarPeriodos" wire:loading.attr="disabled"
                    wire:target="importarPeriodos,archivo_periodos" class="cursor-pointer rounded-xl">
                    <span class="inline-flex items-center gap-2">
                        <flux:icon.arrow-up-tray class="h-4 w-4" />
                        Importar periodos
                    </span>
                </flux:button>

                <flux:button type="button" variant="ghost" wire:click="limpiarArchivoPeriodos"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <span class="inline-flex items-center gap-2">
                        <flux:icon.x-mark class="h-4 w-4" />
                        Limpiar
                    </span>
                </flux:button>
            </div>

            @if ($mensaje_importacion)
                <div
                    class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                    {{ $mensaje_importacion }}
                </div>
            @endif

            @if ($error_importacion)
                <div
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                    {{ $error_importacion }}
                </div>
            @endif

            @if (!empty($errores_importacion))
                <div class="overflow-hidden rounded-xl border border-red-200 dark:border-red-900/50">
                    <div
                        class="border-b border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                        Errores encontrados en la plantilla
                    </div>
                    <div class="max-h-64 overflow-y-auto bg-white px-4 py-3 dark:bg-slate-900">
                        <ol class="space-y-1.5 text-xs text-red-700 dark:text-red-300">
                            @foreach ($errores_importacion as $error)
                                <li class="flex gap-2">
                                    <span class="font-bold">{{ $loop->iteration }}.</span>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <form wire:submit.prevent="guardarPeriodo" class="space-y-4">
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">

            <div class="h-1.5 w-full bg-gradient-to-r from-[#006492] via-cyan-500 to-[#88AC2E]"></div>

            <div wire:loading.flex wire:target="guardarPeriodo,nivel_id"
                class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-slate-950/70">
                <div
                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                    <svg class="h-5 w-5 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>

                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">
                        Procesando información...
                    </span>
                </div>
            </div>

            <div class="space-y-5 p-5">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                        Registrar periodo académico
                    </h2>

                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Completa la configuración académica y el rango de fechas. Los campos se adaptan automáticamente
                        al nivel seleccionado.
                    </p>

                    @if ($this->esBachillerato)
                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-800/60 dark:bg-emerald-900/20 dark:text-emerald-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Bachillerato seleccionado: generación, semestre, mes y parcial habilitados.
                        </div>
                    @elseif (!empty($nivel_id))
                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700 dark:border-sky-800/60 dark:bg-sky-900/20 dark:text-sky-300">
                            <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                            Básica seleccionada: mes y periodo de básica habilitados.
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <flux:select wire:model.live="nivel_id" label="Nivel">
                            <flux:select.option value="">Selecciona un nivel</flux:select.option>

                            @foreach ($niveles as $nivel)
                                <flux:select.option value="{{ $nivel->id }}">
                                    {{ $nivel->nombre }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        @error('nivel_id')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar">
                            <flux:select.option value="">Selecciona un ciclo escolar</flux:select.option>

                            @foreach ($ciclosEscolares as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">
                                    {{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        @error('ciclo_escolar_id')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:input wire:model="fecha_inicio" type="date" label="Fecha inicio" />

                        @error('fecha_inicio')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:input wire:model="fecha_fin" type="date" label="Fecha fin" />

                        @error('fecha_fin')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                Periodos de básica
                            </h3>

                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Aplica para preescolar, primaria y secundaria.
                            </p>
                        </div>

                        <span
                            class="rounded-full border px-3 py-1 text-xs font-medium
                            {{ $this->esBasica
                                ? 'border-sky-200 bg-sky-100 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-300'
                                : 'border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' }}">
                            {{ $this->esBasica ? 'Habilitado' : 'Deshabilitado' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="{{ !$this->esBasica ? 'opacity-60' : '' }}">
                            <flux:select wire:model="mes_basica_id" label="Mes básica" :disabled="!$this->esBasica">
                                <flux:select.option value="">Selecciona un mes</flux:select.option>

                                @foreach ($mesesBasica as $mes)
                                    <flux:select.option value="{{ $mes->id }}">
                                        {{ $mes->meses }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('mes_basica_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="{{ !$this->esBasica ? 'opacity-60' : '' }}">
                            <flux:select wire:model="periodo_basica_id" label="Periodo básica"
                                :disabled="!$this->esBasica">
                                <flux:select.option value="">Selecciona un periodo</flux:select.option>

                                @foreach ($periodosBasica as $periodo)
                                    <flux:select.option value="{{ $periodo->id }}">
                                        {{ $periodo->descripcion }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('periodo_basica_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                Periodos de bachillerato
                            </h3>

                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Aplica únicamente para el nivel bachillerato.
                            </p>
                        </div>

                        <span
                            class="rounded-full border px-3 py-1 text-xs font-medium
                            {{ $this->esBachillerato
                                ? 'border-emerald-200 bg-emerald-100 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                                : 'border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' }}">
                            {{ $this->esBachillerato ? 'Habilitado' : 'Deshabilitado' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="{{ !$this->esBachillerato ? 'opacity-60' : '' }}">
                            <flux:select wire:model.live="generacion_id" label="Generación"
                                :disabled="!$this->esBachillerato || !$ciclo_escolar_id || !$generacion_id">
                                <flux:select.option value="">Selecciona una generación</flux:select.option>

                                @foreach ($generaciones as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">
                                        {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('generacion_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="{{ !$this->esBachillerato ? 'opacity-60' : '' }}">
                            <flux:select wire:model.live="semestre_id" label="Semestre" :disabled="!$this->esBachillerato || !$ciclo_escolar_id || !$generacion_id">
                                <flux:select.option value="">Selecciona un semestre</flux:select.option>

                                @foreach ($semestres as $semestre)
                                    <flux:select.option value="{{ $semestre->id }}">
                                        {{ $semestre->numero }}° Semestre
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('semestre_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="{{ !$this->esBachillerato ? 'opacity-60' : '' }}">
                            <flux:select wire:model="mes_bachillerato_id" label="Mes bachillerato"
                                :disabled="!$this->esBachillerato || !$ciclo_escolar_id || !$generacion_id">
                                <flux:select.option value="">Selecciona un mes</flux:select.option>

                                @foreach ($mesesBachillerato as $mes)
                                    <flux:select.option value="{{ $mes->id }}">
                                        {{ $mes->meses }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('mes_bachillerato_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="{{ !$this->esBachillerato ? 'opacity-60' : '' }}">
                            <flux:select wire:model="parcial_bachillerato_id" label="Parcial"
                                :disabled="!$this->esBachillerato || !$ciclo_escolar_id || !$generacion_id">
                                <flux:select.option value="">Selecciona un parcial</flux:select.option>

                                @foreach ($parciales as $parcial)
                                    <flux:select.option value="{{ $parcial->id }}">
                                        {{ $parcial->descripcion }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('parcial_bachillerato_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-2">
                    <flux:button type="submit" variant="primary" class="px-4 py-2 text-xs sm:text-sm"
                        spinner="guardarPeriodo">
                        Guardar periodo
                    </flux:button>
                </div>
            </div>
        </div>
    </form>
</div>
