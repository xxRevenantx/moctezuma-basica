<div class="space-y-4">
    <form wire:submit.prevent="guardarPeriodo" class="space-y-4">
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">

            <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500"></div>

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
                        Crear nuevo periodo
                    </h2>

                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Para básica se utiliza mes y periodo. Para bachillerato se utiliza generación, semestre, mes y
                        parcial.
                    </p>

                    @if ((int) $nivel_id === 4)
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
                        <flux:select wire:model="ciclo_escolar_id" label="Ciclo escolar">
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
                            {{ !empty($nivel_id) && (int) $nivel_id !== 4
                                ? 'border-sky-200 bg-sky-100 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-300'
                                : 'border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' }}">
                            {{ !empty($nivel_id) && (int) $nivel_id !== 4 ? 'Habilitado' : 'Deshabilitado' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="{{ empty($nivel_id) || (int) $nivel_id === 4 ? 'opacity-60' : '' }}">
                            <flux:select wire:model="mes_basica_id" label="Mes básica"
                                :disabled="empty($nivel_id) || (int) $nivel_id === 4">
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

                        <div class="{{ empty($nivel_id) || (int) $nivel_id === 4 ? 'opacity-60' : '' }}">
                            <flux:select wire:model="periodo_basica_id" label="Periodo básica"
                                :disabled="empty($nivel_id) || (int) $nivel_id === 4">
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
                            {{ (int) $nivel_id === 4
                                ? 'border-emerald-200 bg-emerald-100 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                                : 'border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' }}">
                            {{ (int) $nivel_id === 4 ? 'Habilitado' : 'Deshabilitado' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="{{ (int) $nivel_id !== 4 ? 'opacity-60' : '' }}">
                            <flux:select wire:model="generacion_id" label="Generación"
                                :disabled="(int) $nivel_id !== 4">
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

                        <div class="{{ (int) $nivel_id !== 4 ? 'opacity-60' : '' }}">
                            <flux:select wire:model="semestre_id" label="Semestre" :disabled="(int) $nivel_id !== 4">
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

                        <div class="{{ (int) $nivel_id !== 4 ? 'opacity-60' : '' }}">
                            <flux:select wire:model="mes_bachillerato_id" label="Mes bachillerato"
                                :disabled="(int) $nivel_id !== 4">
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

                        <div class="{{ (int) $nivel_id !== 4 ? 'opacity-60' : '' }}">
                            <flux:select wire:model="parcial_bachillerato_id" label="Parcial"
                                :disabled="(int) $nivel_id !== 4">
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
