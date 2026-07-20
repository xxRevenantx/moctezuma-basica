    <div class="space-y-6">


        {{-- IMPORTAR Y EXPORTAR ALUMNOS --}}
        <div
            class="relative overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">

            <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-500"></div>

            <div class="p-5 sm:p-6 lg:p-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <flux:icon.arrow-down-tray class="h-5 w-5" />
                            </div>

                            <div>
                                <h2 class="text-xl font-bold tracking-tight text-slate-800 dark:text-white">
                                    Importar y exportar alumnos
                                </h2>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Descarga la plantilla, llena los datos y vuelve a cargar el archivo desde este
                                    módulo.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <flux:button type="button" variant="ghost" wire:click="descargarPlantillaAlumnos"
                            wire:loading.attr="disabled" wire:target="descargarPlantillaAlumnos"
                            class="cursor-pointer rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">

                            <span wire:loading.remove wire:target="descargarPlantillaAlumnos"
                                class="inline-flex items-center gap-2">
                                <flux:icon.document-arrow-down class="h-4 w-4" />
                                Descargar plantilla
                            </span>

                            <span wire:loading wire:target="descargarPlantillaAlumnos"
                                class="inline-flex items-center gap-2">
                                <span
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-600"></span>
                                Preparando...
                            </span>
                        </flux:button>

                        <flux:button type="button" variant="ghost" wire:click="exportarAlumnos"
                            wire:loading.attr="disabled" wire:target="exportarAlumnos"
                            class="cursor-pointer rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">

                            <span wire:loading.remove wire:target="exportarAlumnos"
                                class="inline-flex items-center gap-2">
                                <flux:icon.table-cells class="h-4 w-4" />
                                Exportar alumnos
                            </span>

                            <span wire:loading wire:target="exportarAlumnos" class="inline-flex items-center gap-2">
                                <span
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-sky-200 border-t-sky-600"></span>
                                Exportando...
                            </span>
                        </flux:button>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-[1fr_auto_auto] lg:items-end">
                    <div>
                        <div class="mb-1 flex items-center gap-2">
                            <flux:label>Archivo Excel</flux:label>

                            <span
                                class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                XLSX, XLS o CSV
                            </span>
                        </div>

                        <label
                            class="group relative flex cursor-pointer flex-col justify-center rounded-2xl border-2 border-dashed border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 px-4 py-4 transition duration-300 hover:border-sky-400 hover:shadow-lg hover:shadow-sky-500/10 dark:border-sky-900/40 dark:from-sky-950/20 dark:via-neutral-900 dark:to-indigo-950/20">

                            <input type="file" wire:model="archivoAlumnos" accept=".xlsx,.xls,.csv" class="hidden">

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-sky-600 shadow-sm dark:bg-neutral-800 dark:text-sky-300">
                                        <flux:icon.cloud-arrow-up class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                            Selecciona el archivo de alumnos
                                        </p>

                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            Usa la plantilla descargada para evitar errores de columnas.
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-xs font-semibold text-sky-700 shadow-sm ring-1 ring-sky-100 dark:bg-neutral-800 dark:text-sky-300 dark:ring-sky-900/30">
                                    Buscar archivo
                                </div>
                            </div>
                        </label>

                        <div wire:loading wire:target="archivoAlumnos"
                            class="mt-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                            Cargando archivo...
                        </div>

                        @if ($archivoAlumnos)
                            <div
                                class="mt-2 inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <flux:icon.check-circle class="h-4 w-4" />
                                Archivo seleccionado correctamente
                            </div>
                        @endif

                        @error('archivoAlumnos')
                            <p class="mt-2 text-xs font-semibold text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <flux:button type="button" variant="primary" wire:click="importarAlumnos"
                        wire:loading.attr="disabled" wire:target="importarAlumnos,archivoAlumnos"
                        class="cursor-pointer rounded-2xl">

                        <span wire:loading.remove wire:target="importarAlumnos" class="inline-flex items-center gap-2">
                            <flux:icon.arrow-up-tray class="h-4 w-4" />
                            Importar alumnos
                        </span>

                        <span wire:loading wire:target="importarAlumnos" class="inline-flex items-center gap-2">
                            <span
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            Importando...
                        </span>
                    </flux:button>

                    <flux:button type="button" variant="ghost" wire:click="limpiarArchivoAlumnos"
                        class="cursor-pointer rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:bg-neutral-700">

                        <span class="inline-flex items-center gap-2">
                            <flux:icon.x-mark class="h-4 w-4" />
                            Limpiar
                        </span>
                    </flux:button>
                </div>

                @if ($mensajeImportacionAlumnos)
                    <div
                        class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {{ $mensajeImportacionAlumnos }}
                    </div>
                @endif

                @if ($errorImportacionAlumnos)
                    <div
                        class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 shadow-sm dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                        {{ $errorImportacionAlumnos }}
                    </div>
                @endif

                @if (!empty($erroresImportacionAlumnos))
                    <div
                        class="mt-5 overflow-hidden rounded-2xl border border-rose-200 bg-white shadow-sm dark:border-rose-900/40 dark:bg-neutral-900">
                        <div
                            class="flex flex-col gap-1 border-b border-rose-100 bg-rose-50 px-4 py-3 dark:border-rose-900/40 dark:bg-rose-950/30">
                            <h3 class="text-sm font-bold text-rose-700 dark:text-rose-300">
                                Errores encontrados en el archivo
                            </h3>

                            <p class="text-xs text-rose-600 dark:text-rose-300">
                                Corrige las filas marcadas y vuelve a importar la plantilla.
                            </p>
                        </div>

                        <div class="max-h-80 overflow-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                                <thead class="sticky top-0 bg-slate-50 dark:bg-neutral-800">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                            Fila
                                        </th>

                                        <th
                                            class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                            Campo
                                        </th>

                                        <th
                                            class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                            Error
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                    @foreach ($erroresImportacionAlumnos as $error)
                                        <tr class="transition hover:bg-rose-50/60 dark:hover:bg-rose-950/20">
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-slate-200">
                                                {{ $error['fila'] }}
                                            </td>

                                            <td
                                                class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">
                                                {{ $error['campo'] }}
                                            </td>

                                            <td class="px-4 py-3 text-rose-600 dark:text-rose-300">
                                                {{ implode(', ', $error['errores']) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div wire:loading.flex wire:target="importarAlumnos"
                class="absolute inset-0 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                <div
                    class="rounded-3xl border border-slate-200 bg-white px-6 py-5 text-center shadow-xl dark:border-neutral-700 dark:bg-neutral-900">
                    <div
                        class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-sky-200 border-t-sky-600">
                    </div>

                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Importando alumnos...
                    </p>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="guardar" class="space-y-6">
            <div
                class="relative overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
                <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-500"></div>

                <div class="p-5 sm:p-6 lg:p-8">
                    {{-- Encabezado --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                                Nueva inscripción
                            </h1>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Captura los datos del alumno y su asignación escolar.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($esBachillerato)
                                <span
                                    class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                    Modo bachillerato activo
                                </span>
                            @endif

                            <span
                                class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                Matrícula automática según selección
                            </span>
                        </div>
                    </div>

                    @if ($curpSuccess)
                        <div x-data="{ mostrar: true }" x-init="setTimeout(() => {
                            mostrar = false

                            setTimeout(() => {
                                $wire.dispatch('limpiar-curp-success')
                            }, 500)
                        }, 2000)" x-show="mostrar"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-500"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                            class="mt-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                            <p class="font-semibold">CURP encontrada</p>
                            <p class="mt-1">{{ $curpSuccess }}</p>
                        </div>
                    @endif

                    {{-- DATOS PERSONALES --}}
                    <section class="space-y-5">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                <flux:icon.user class="h-5 w-5" />
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                    Datos personales
                                </h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Información básica del alumno.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2">
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>CURP</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:input wire:model.live="curp" maxlength="18" placeholder="Ingresa la CURP" />
                                @if ($curpAdvertencia)
                                    <div
                                        class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                        <p class="font-semibold">Advertencia sobre la CURP</p>
                                        <p class="mt-1">{{ $curpAdvertencia }}</p>
                                    </div>
                                @endif

                                @if ($curpError)
                                    <div
                                        class="mt-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                                        <p class="font-semibold">Error en la CURP</p>
                                        <p class="mt-1">{{ $curpError }}</p>
                                    </div>
                                @endif
                                @error('curp')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                                @if ($curpError)
                                    <p class="mt-2 text-xs font-semibold text-amber-600">{{ $curpError }}</p>
                                @endif
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Matrícula</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                        Editable
                                    </span>
                                </div>
                                <flux:input wire:model.live.debounce.500ms="matricula"
                                    placeholder="Ingresa o edita la matrícula" />
                                @error('matricula')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Folio</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="folio" placeholder="Opcional" />
                                @error('folio')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Nombre(s)</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:input wire:model="nombre" placeholder="Nombre(s)" />
                                @error('nombre')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Apellido paterno</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:input wire:model="apellido_paterno" placeholder="Apellido paterno" />
                                @error('apellido_paterno')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Apellido materno</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="apellido_materno" placeholder="Apellido materno" />
                                @error('apellido_materno')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Fecha de nacimiento</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:input type="date" wire:model="fecha_nacimiento" />
                                @error('fecha_nacimiento')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Género</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:select wire:model="genero">
                                    <flux:select.option value="">Selecciona una opción</flux:select.option>
                                    <flux:select.option value="H">Hombre</flux:select.option>
                                    <flux:select.option value="M">Mujer</flux:select.option>
                                </flux:select>
                                @error('genero')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Fecha real de ingreso</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:input type="date" wire:model="fecha_inscripcion" />
                                @error('fecha_inscripcion')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Ciclo escolar</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:select wire:model.live="ciclo_escolar_id">
                                    <flux:select.option value="">Selecciona un ciclo escolar</flux:select.option>
                                    @foreach ($cicloEscolares as $cicloEscolar)
                                        <flux:select.option value="{{ $cicloEscolar->id }}">
                                            {{ $cicloEscolar->inicio_anio }} - {{ $cicloEscolar->fin_anio }}
                                            @if ($cicloEscolar->cerrado_at)
                                                · Cerrado
                                            @elseif ($cicloEscolar->es_actual)
                                                · Actual
                                            @endif
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('ciclo_escolar_id')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Momento de ingreso</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:select wire:model.live="ciclo_id">
                                    <flux:select.option value="">Selecciona el momento de ingreso</flux:select.option>
                                    @foreach ($ciclos as $ciclo)
                                        <flux:select.option value="{{ $ciclo->id }}">
                                            {{ $ciclo->ciclo }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('ciclo_id')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Tipo de ingreso</flux:label>
                                    <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:select wire:model.live="tipo_ingreso">
                                    <flux:select.option value="nuevo_ingreso">Nuevo ingreso</flux:select.option>
                                    <flux:select.option value="traslado">Traslado de otra institución</flux:select.option>
                                    <flux:select.option value="captura_historica">Captura histórica</flux:select.option>
                                </flux:select>
                                @error('tipo_ingreso')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if ($tipo_ingreso === 'captura_historica')
                                <div class="md:col-span-2">
                                    <div class="mb-1 flex items-center gap-2">
                                        <flux:label>Motivo de captura histórica</flux:label>
                                        <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                            Obligatorio
                                        </span>
                                    </div>
                                    <flux:textarea wire:model="motivo_captura_historica" rows="3"
                                        placeholder="Explica la razón administrativa y la documentación que respalda la captura..." />
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-300">
                                        La captura histórica requiere permiso de edición académica y quedará registrada como motivo de estatus.
                                    </p>
                                    @error('motivo_captura_historica')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Estado inicial</flux:label>
                                    <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:select wire:model="estado_inscripcion">
                                    <flux:select.option value="inscrito">Inscrito y activo</flux:select.option>
                                    <flux:select.option value="preinscrito">Preinscrito, pendiente de activación</flux:select.option>
                                </flux:select>
                                @error('estado_inscripcion')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <div
                        class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                    </div>

                    {{-- ASIGNACIÓN ESCOLAR --}}
                    <section class="space-y-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                                    <flux:icon.academic-cap class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">Asignación escolar</h2>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        El sistema calcula la generación y solo muestra grupos compatibles con el ciclo seleccionado.
                                    </p>
                                </div>
                            </div>

                            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <flux:icon.users class="h-4 w-4" />
                                Cupo ilimitado
                            </span>
                        </div>

                        <div class="relative grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div wire:loading.flex wire:target="ciclo_escolar_id,ciclo_id,tipo_ingreso,nivel_id,grado_id,semestre_id"
                                class="absolute inset-0 z-20 items-center justify-center rounded-2xl bg-white/75 backdrop-blur-sm dark:bg-neutral-900/75">
                                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-lg dark:border-neutral-700 dark:bg-neutral-900">
                                    <svg class="h-5 w-5 animate-spin text-violet-600" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                                    </svg>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Validando asignación...</span>
                                </div>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Nivel</flux:label>
                                    <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">Obligatorio</span>
                                </div>
                                <flux:select wire:model.live="nivel_id" :disabled="!$ciclo_escolar_id">
                                    <flux:select.option value="">Selecciona un nivel</flux:select.option>
                                    @foreach ($niveles as $nivel)
                                        <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('nivel_id')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if (!$esBachillerato)
                                <div>
                                    <div class="mb-1 flex items-center gap-2">
                                        <flux:label>Grado</flux:label>
                                        <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">Obligatorio</span>
                                    </div>
                                    <flux:select wire:model.live="grado_id" :disabled="!$nivel_id || $grados->isEmpty()">
                                        <flux:select.option value="">Selecciona un grado</flux:select.option>
                                        @foreach ($grados as $grado)
                                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}°</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('grado_id')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @else
                                <div>
                                    <div class="mb-1 flex items-center gap-2">
                                        <flux:label>Semestre</flux:label>
                                        <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">Obligatorio</span>
                                    </div>
                                    <flux:select wire:model.live="semestre_id"
                                        :disabled="!$nivel_id || $semestres->isEmpty() || $tipo_ingreso === 'nuevo_ingreso'">
                                        <flux:select.option value="">Selecciona un semestre</flux:select.option>
                                        @foreach ($semestres as $semestre)
                                            <flux:select.option value="{{ $semestre->id }}">
                                                {{ $semestre->numero }}° semestre · {{ $semestre->grado?->nombre }}° grado
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @if ($tipo_ingreso === 'nuevo_ingreso')
                                        <p class="mt-1 text-xs text-violet-600">Se calcula desde el momento de ingreso.</p>
                                    @endif
                                    @error('semestre_id')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Generación</flux:label>
                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">Automática</span>
                                </div>
                                <flux:input value="{{ $generacionAutomaticaLabel ?: 'Se calculará automáticamente' }}" readonly disabled />
                                @error('generacion_id')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Grupo</flux:label>
                                    <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">Obligatorio</span>
                                </div>
                                <flux:select wire:model.live="grupo_id" :disabled="!$generacion_id || empty($grupos)">
                                    <flux:select.option value="">Selecciona un grupo</flux:select.option>
                                    @foreach ($grupos as $grupo)
                                        <flux:select.option value="{{ $grupo['id'] }}">{{ $grupo['label'] }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('grupo_id')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @if ($asignacionAdvertencia)
                            <div class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-3">
                                    <flux:icon.exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0" />
                                    <span>{{ $asignacionAdvertencia }}</span>
                                </div>
                                @if (auth()->user()?->canAccess('academico.crear'))
                                    <flux:button type="button" size="sm" variant="primary" href="{{ route('misrutas.grupos') }}">
                                        Administrar grupos
                                    </flux:button>
                                @endif
                            </div>
                        @endif

                        @php
                            $cicloAsignacion = $cicloEscolares->firstWhere('id', (int) $ciclo_escolar_id);
                            $nivelAsignacion = $niveles->firstWhere('id', (int) $nivel_id);
                            $gradoAsignacion = $grados->firstWhere('id', (int) $grado_id);
                            $semestreAsignacion = $semestres->firstWhere('id', (int) $semestre_id);
                            $grupoAsignacion = collect($grupos)->firstWhere('id', (int) $grupo_id);
                        @endphp

                        @if ($generacion_id && $grupo_id && !$asignacionAdvertencia)
                            <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 via-white to-sky-50 dark:border-emerald-900/40 dark:from-emerald-950/30 dark:via-neutral-900 dark:to-sky-950/30">
                                <div class="flex items-center gap-2 border-b border-emerald-100 px-4 py-3 text-sm font-black text-emerald-700 dark:border-emerald-900/40 dark:text-emerald-300">
                                    <flux:icon.check-circle class="h-5 w-5" />
                                    Asignación escolar válida
                                </div>
                                <div class="grid grid-cols-2 gap-4 p-4 text-sm md:grid-cols-3 xl:grid-cols-6">
                                    <div><p class="text-xs text-slate-500">Ciclo</p><p class="font-bold">{{ $cicloAsignacion?->inicio_anio }}-{{ $cicloAsignacion?->fin_anio }}</p></div>
                                    <div><p class="text-xs text-slate-500">Nivel</p><p class="font-bold">{{ $nivelAsignacion?->nombre }}</p></div>
                                    <div><p class="text-xs text-slate-500">{{ $esBachillerato ? 'Semestre' : 'Grado' }}</p><p class="font-bold">{{ $esBachillerato ? ($semestreAsignacion?->numero . '°') : ($gradoAsignacion?->nombre . '°') }}</p></div>
                                    <div><p class="text-xs text-slate-500">Generación</p><p class="font-bold">{{ $generacionAutomaticaLabel }}</p></div>
                                    <div><p class="text-xs text-slate-500">Grupo</p><p class="font-bold">{{ $grupoAsignacion['label'] ?? '—' }}</p></div>
                                    <div><p class="text-xs text-slate-500">Fecha de captura</p><p class="font-bold">{{ now()->format('d/m/Y') }}</p></div>
                                </div>
                            </div>
                        @endif
                    </section>

                    <div class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700"></div>

                    {{-- OBSERVACIONES Y SEGUIMIENTO --}}
                    <section class="space-y-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                    <flux:icon.document-text class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                            Observaciones y seguimiento
                                        </h2>
                                        <span
                                            class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                            Opcional
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Registra notas internas de la inscripción. Cada ciclo escolar conserva su propia observación.
                                    </p>
                                </div>
                            </div>

                            @if ($ciclo_escolar_id)
                                @php($cicloObservacion = $cicloEscolares->firstWhere('id', (int) $ciclo_escolar_id))
                                <span
                                    class="inline-flex w-fit items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-black text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                    <flux:icon.calendar-days class="h-4 w-4" />
                                    Ciclo {{ $cicloObservacion ? $cicloObservacion->inicio_anio . '-' . $cicloObservacion->fin_anio : 'seleccionado' }}
                                </span>
                            @endif
                        </div>

                        <div
                            class="rounded-[24px] border border-amber-100 bg-gradient-to-br from-amber-50/70 via-white to-sky-50/60 p-4 shadow-sm dark:border-amber-900/30 dark:from-amber-950/10 dark:via-neutral-900 dark:to-sky-950/10 sm:p-5">
                            <x-forms.tinymce-observaciones
                                model="observaciones"
                                id="observaciones-inscripcion-crear"
                                :value="$observaciones"
                                :height="260"
                            />

                            @error('observaciones')
                                <p class="mt-3 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </section>

                    <div
                        class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                    </div>

                    {{-- NACIMIENTO --}}
                    <section class="space-y-5">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <flux:icon.map-pin class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                    Datos de nacimiento
                                </h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Lugar de nacimiento del alumno.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>País de nacimiento</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="pais_nacimiento" placeholder="País de nacimiento" />
                                @error('pais_nacimiento')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Estado de nacimiento</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="estado_nacimiento" placeholder="Estado de nacimiento" />
                                @error('estado_nacimiento')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Lugar de nacimiento</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="lugar_nacimiento" placeholder="Lugar de nacimiento" />
                                @error('lugar_nacimiento')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <div
                        class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                    </div>

                    {{-- TUTOR Y DOMICILIO --}}
                    <section class="space-y-5">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                <flux:icon.home class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                    Tutor y domicilio
                                </h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Selecciona el tutor y captura la dirección.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2">
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Tutor</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:select wire:model="tutor_id">
                                    <flux:select.option value="">Selecciona un tutor</flux:select.option>
                                    @foreach ($tutores as $tutor)
                                        <flux:select.option value="{{ $tutor->id }}">
                                            {{ trim(($tutor->nombre ?? '') . ' ' . ($tutor->apellido_paterno ?? '') . ' ' . ($tutor->apellido_materno ?? '')) }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('tutor_id')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2 flex items-end">
                                <label
                                    class="inline-flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                                    <input type="checkbox" wire:model.live="copiar_direccion_tutor"
                                        class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    Copiar dirección del tutor
                                </label>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Calle</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="calle" placeholder="Calle" />
                                @error('calle')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Número exterior</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="numero_exterior" placeholder="Número exterior" />
                                @error('numero_exterior')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Número interior</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="numero_interior" placeholder="Número interior" />
                                @error('numero_interior')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Colonia</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="colonia" placeholder="Colonia" />
                                @error('colonia')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Código postal</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="codigo_postal" placeholder="Código postal" />
                                @error('codigo_postal')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Municipio</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="municipio" placeholder="Municipio" />
                                @error('municipio')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Estado de residencia</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="estado_residencia" placeholder="Estado de residencia" />
                                @error('estado_residencia')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Ciudad de residencia</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="ciudad_residencia" placeholder="Ciudad de residencia" />
                                @error('ciudad_residencia')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <div
                        class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                    </div>

                    {{-- FOTO --}}
                    {{-- FOTO --}}
                    <section class="space-y-5">
                        <div
                            class="rounded-[26px] border border-pink-200 bg-pink-50/70 p-4 dark:border-pink-900/40 dark:bg-pink-950/20">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-pink-100 text-pink-700 shadow-sm dark:bg-pink-950/40 dark:text-pink-300">
                                    <flux:icon.camera class="h-5 w-5" />
                                </div>

                                <div>
                                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                        Fotografía
                                    </h2>

                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Sube una foto del alumno si la tienes disponible.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div x-data="{
                            preview: null,
                            nombreArchivo: '',

                            usarTemporal(event) {
                                const file = event.target.files[0];

                                if (!file) {
                                    return;
                                }

                                this.nombreArchivo = file.name;

                                const reader = new FileReader();

                                reader.onload = (e) => {
                                    this.preview = e.target.result;
                                };

                                reader.readAsDataURL(file);
                            },

                            limpiar() {
                                this.preview = null;
                                this.nombreArchivo = '';

                                const input = document.getElementById('foto');

                                if (input) {
                                    input.value = '';
                                }
                            }
                        }" x-on:foto-limpiada.window="limpiar()"
                            class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">

                            <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500">
                            </div>

                            <div class="p-5 sm:p-6">
                                <div class="mb-4 flex items-center gap-2">
                                    <h3 class="text-base font-bold text-slate-800 dark:text-white">
                                        Fotografía del alumno
                                    </h3>

                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                        Opcional
                                    </span>
                                </div>

                                <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                                    Sube una imagen clara y reciente en formato JPG, JPEG o PNG.
                                </p>

                                <div class="grid grid-cols-1 gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
                                    <div class="flex items-center justify-center">
                                        <div class="relative">

                                            {{-- Loader al subir foto --}}
                                            <div wire:loading.flex wire:target="foto"
                                                class="absolute inset-0 z-20 hidden items-center justify-center rounded-[26px] bg-white/70 backdrop-blur-sm dark:bg-neutral-950/70">

                                                <div class="flex flex-col items-center gap-3">
                                                    <div class="relative h-12 w-12">
                                                        <div
                                                            class="absolute inset-0 rounded-full border-4 border-sky-200 dark:border-sky-900/40">
                                                        </div>

                                                        <div
                                                            class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-sky-500 border-r-indigo-500">
                                                        </div>
                                                    </div>

                                                    <p
                                                        class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                        Subiendo foto...
                                                    </p>
                                                </div>
                                            </div>

                                            {{-- Preview de la foto --}}
                                            <div
                                                class="group relative h-52 w-52 overflow-hidden rounded-[26px] border border-slate-200 bg-gradient-to-br from-slate-50 to-slate-100 shadow-lg dark:border-neutral-700 dark:from-neutral-800 dark:to-neutral-900">

                                                {{-- Vista previa inmediata con Alpine --}}
                                                <template x-if="preview">
                                                    <img :src="preview" alt="Vista previa de la fotografía"
                                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                                </template>

                                                {{-- Foto temporal de Livewire --}}
                                                @if (!empty($foto) && is_object($foto))
                                                    <img src="{{ $foto->temporaryUrl() }}"
                                                        alt="Fotografía temporal del alumno"
                                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                                        x-show="!preview">
                                                @else
                                                    {{-- Estado vacío --}}
                                                    <div x-show="!preview"
                                                        class="flex h-full w-full flex-col items-center justify-center px-4 text-center">

                                                        <div
                                                            class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-md dark:bg-neutral-800">
                                                            <flux:icon.camera
                                                                class="h-8 w-8 text-slate-400 dark:text-slate-500" />
                                                        </div>

                                                        <p
                                                            class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                                                            Sin fotografía
                                                        </p>

                                                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                                                            Aquí se mostrará la imagen seleccionada
                                                        </p>
                                                    </div>
                                                @endif

                                                <div
                                                    class="absolute left-3 top-3 rounded-full bg-black/55 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur-sm">
                                                    Foto
                                                </div>

                                                <div x-show="preview" x-transition
                                                    class="absolute bottom-3 left-3 right-3 rounded-2xl bg-emerald-500/90 px-3 py-2 text-center text-[11px] font-bold text-white shadow-lg backdrop-blur-sm">
                                                    Foto seleccionada
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col justify-center">
                                        <label for="foto"
                                            class="group relative flex cursor-pointer flex-col items-center justify-center rounded-[26px] border-2 border-dashed border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 px-6 py-8 text-center transition duration-300 hover:border-sky-400 hover:shadow-lg hover:shadow-sky-500/10 dark:border-sky-900/40 dark:from-sky-950/20 dark:via-neutral-900 dark:to-indigo-950/20">

                                            <input id="foto" type="file" wire:model="foto"
                                                accept="image/png,image/jpeg,image/webp,.jpg,.jpeg,.png,.webp" class="hidden"
                                                @change="usarTemporal($event)">

                                            <div
                                                class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                                                <flux:icon.cloud-arrow-up class="h-8 w-8" />
                                            </div>

                                            <h4 class="text-sm font-bold text-slate-800 dark:text-white">
                                                Haz clic para subir tu fotografía
                                            </h4>

                                            <p class="mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">
                                                Puedes quitar la selección antes de guardar la inscripción.
                                            </p>

                                            <div
                                                class="mt-4 inline-flex items-center rounded-full bg-white px-4 py-2 text-xs font-semibold text-sky-700 shadow-sm ring-1 ring-sky-100 dark:bg-neutral-800 dark:text-sky-300 dark:ring-sky-900/30">
                                                JPG, JPEG o PNG
                                            </div>
                                        </label>

                                        <div x-show="nombreArchivo" x-transition class="mt-4">
                                            <div
                                                class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                <flux:icon.check-circle class="h-4 w-4" />
                                                <span class="truncate" x-text="nombreArchivo"></span>
                                            </div>
                                        </div>

                                        <div class="mt-5 flex flex-wrap gap-3">
                                            <label for="foto"
                                                class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition hover:scale-[1.01]">
                                                <flux:icon.image-plus class="mr-2 h-4 w-4" />
                                                Seleccionar foto
                                            </label>

                                            <button type="button" @click="limpiar()" wire:click="quitarFotoTemporal"
                                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:bg-neutral-700">
                                                <flux:icon.trash-2 class="mr-2 h-4 w-4" />
                                                Quitar selección
                                            </button>
                                        </div>

                                        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                                            Recomendación: usa una imagen vertical, con buena iluminación y fondo
                                            limpio.
                                        </p>

                                        @error('foto')
                                            <p class="mt-3 text-sm font-medium text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div
                        class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 dark:border-neutral-800 sm:flex-row sm:justify-end">
                        <flux:button type="button" variant="ghost" wire:click="cancelar"
                            class="cursor-pointer rounded-2xl">
                            Cancelar
                        </flux:button>

                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                            wire:target="guardar,foto,curp" class="cursor-pointer rounded-2xl">
                            <span wire:loading.remove wire:target="guardar">Guardar inscripción</span>
                            <span wire:loading wire:target="guardar">Guardando...</span>
                        </flux:button>
                    </div>
                </div>

                <div wire:loading.flex wire:target="guardar"
                    class="absolute inset-0 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                    <div
                        class="rounded-3xl border border-slate-200 bg-white px-6 py-5 text-center shadow-xl dark:border-neutral-700 dark:bg-neutral-900">
                        <div
                            class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-sky-200 border-t-sky-600">
                        </div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Guardando inscripción...
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
