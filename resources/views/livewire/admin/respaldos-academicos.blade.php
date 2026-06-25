<div class="space-y-6">
    <section
        class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-sky-400/20 blur-3xl"></div>
        <div class="absolute -bottom-24 left-12 h-64 w-64 rounded-full bg-lime-400/15 blur-3xl"></div>

        <div
            class="relative bg-gradient-to-br from-slate-950 via-sky-950 to-emerald-950 px-6 py-8 text-white sm:px-8">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.16em] text-sky-100 backdrop-blur">
                        <flux:icon.shield-check class="h-4 w-4" />
                        Administración exclusiva
                    </div>

                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">
                        Respaldos académicos
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        Exporta e importa todos los alumnos y todas las calificaciones conservando exactamente sus
                        identificadores. La importación actualiza o crea por ID, pero nunca cambia el ID de un registro
                        existente.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:min-w-[620px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-slate-300">Alumnos</p>
                        <p class="mt-1 text-2xl font-black">{{ number_format($estadisticas['alumnos']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-slate-300">Trayectorias</p>
                        <p class="mt-1 text-2xl font-black">{{ number_format($estadisticas['trayectorias']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-slate-300">Calificaciones</p>
                        <p class="mt-1 text-2xl font-black">{{ number_format($estadisticas['calificaciones']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-slate-300">Bitácora</p>
                        <p class="mt-1 text-2xl font-black">
                            {{ number_format($estadisticas['bitacora_calificaciones']) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="rounded-[1.6rem] border border-amber-200 bg-amber-50 p-5 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/25">
        <div class="flex items-start gap-4">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/20">
                <flux:icon.triangle-alert class="h-5 w-5" />
            </div>
            <div>
                <h2 class="font-black text-amber-950 dark:text-amber-100">Antes de importar</h2>
                <p class="mt-1 text-sm leading-6 text-amber-900/80 dark:text-amber-200/80">
                    Conserva un respaldo SQL adicional. El sistema valida la firma interna del ID, las columnas, las
                    relaciones y los valores únicos. Si una sola fila falla, se revierte toda la operación. Los registros
                    que no estén en el Excel no se eliminan.
                </p>
            </div>
        </div>
    </section>

    <div class="grid gap-6 2xl:grid-cols-2">
        {{-- RESPALDO DE ALUMNOS --}}
        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-slate-200 bg-gradient-to-r from-sky-50 to-emerald-50 px-6 py-5 dark:border-neutral-800 dark:from-sky-950/30 dark:to-emerald-950/20">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-emerald-500 text-white shadow-lg shadow-sky-500/20">
                            <flux:icon.users class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-sky-700 dark:text-sky-300">
                                Respaldo integral
                            </p>
                            <h2 class="text-xl font-black text-slate-900 dark:text-white">Todos los alumnos</h2>
                        </div>
                    </div>

                    <span
                        class="rounded-full border border-sky-200 bg-white px-3 py-1 text-xs font-black text-sky-700 dark:border-sky-800 dark:bg-neutral-900 dark:text-sky-300">
                        {{ number_format($estadisticas['alumnos']) }} registros
                    </span>
                </div>
            </div>

            <div class="space-y-6 p-6">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-neutral-800/70">
                        <p class="text-xs font-bold text-slate-500">Activos</p>
                        <p class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                            {{ number_format($estadisticas['alumnos_activos']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-neutral-800/70">
                        <p class="text-xs font-bold text-slate-500">Trayectorias</p>
                        <p class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                            {{ number_format($estadisticas['trayectorias']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-neutral-800/70">
                        <p class="text-xs font-bold text-slate-500">Matrículas</p>
                        <p class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                            {{ number_format($estadisticas['matriculas']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-neutral-800/70">
                        <p class="text-xs font-bold text-slate-500">Movimientos</p>
                        <p class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                            {{ number_format($estadisticas['movimientos']) }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                    <p class="text-sm font-black text-slate-800 dark:text-white">El archivo incluye</p>
                    <div class="mt-3 grid gap-2 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                        <span class="flex items-center gap-2"><flux:icon.file-check class="h-4 w-4 text-emerald-500" /> Tutores y alumnos, incluso archivados</span>
                        <span class="flex items-center gap-2"><flux:icon.file-check class="h-4 w-4 text-emerald-500" /> Trayectorias por ciclo y corte</span>
                        <span class="flex items-center gap-2"><flux:icon.file-check class="h-4 w-4 text-emerald-500" /> Matrículas históricas</span>
                        <span class="flex items-center gap-2"><flux:icon.file-check class="h-4 w-4 text-emerald-500" /> Línea de tiempo de movimientos</span>
                    </div>
                </div>

                <button type="button" wire:click="exportarAlumnos" wire:loading.attr="disabled"
                    wire:target="exportarAlumnos"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-600 to-emerald-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-sky-500/20 transition hover:-translate-y-0.5 hover:shadow-xl disabled:cursor-wait disabled:opacity-60">
                    <flux:icon.download wire:loading.remove wire:target="exportarAlumnos" class="h-5 w-5" />
                    <span wire:loading.remove wire:target="exportarAlumnos">Exportar respaldo de alumnos</span>
                    <span wire:loading wire:target="exportarAlumnos">Generando archivo...</span>
                </button>

                <div class="border-t border-dashed border-slate-200 pt-6 dark:border-neutral-700">
                    <label class="block text-sm font-black text-slate-800 dark:text-white">
                        Importar respaldo de alumnos
                    </label>
                    <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                        Acepta exclusivamente el archivo generado desde esta misma sección.
                    </p>

                    <label
                        class="mt-4 flex cursor-pointer items-center gap-4 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-4 transition hover:border-sky-400 hover:bg-sky-50/50 dark:border-neutral-700 dark:bg-neutral-800/60 dark:hover:border-sky-700">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-sky-600 shadow-sm dark:bg-neutral-900">
                            <flux:icon.upload class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-800 dark:text-white">
                                {{ $archivoAlumnos?->getClientOriginalName() ?? 'Seleccionar archivo Excel' }}
                            </p>
                            <p class="text-xs text-slate-500">XLSX o XLS · máximo 50 MB</p>
                        </div>
                        <input type="file" wire:model="archivoAlumnos" accept=".xlsx,.xls" class="sr-only">
                    </label>

                    <div wire:loading wire:target="archivoAlumnos"
                        class="mt-2 text-xs font-bold text-sky-600 dark:text-sky-400">
                        Cargando archivo...
                    </div>

                    @error('archivoAlumnos')
                        <p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 dark:bg-red-950/30 dark:text-red-300">
                            {{ $message }}
                        </p>
                    @enderror

                    <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-2xl bg-slate-50 p-4 dark:bg-neutral-800/60">
                        <input type="checkbox" wire:model="confirmarAlumnos"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        <span class="text-sm leading-5 text-slate-600 dark:text-slate-300">
                            Confirmo que revisé el archivo. Comprendo que se actualizarán datos usando el mismo ID y que
                            no se cambiará ningún ID existente.
                        </span>
                    </label>
                    @error('confirmarAlumnos')
                        <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                    @enderror

                    <button type="button" wire:click="importarAlumnos" wire:loading.attr="disabled"
                        wire:target="importarAlumnos"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-5 py-3.5 text-sm font-black text-sky-700 transition hover:bg-sky-100 disabled:cursor-wait disabled:opacity-60 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-300">
                        <flux:icon.upload wire:loading.remove wire:target="importarAlumnos" class="h-5 w-5" />
                        <span wire:loading.remove wire:target="importarAlumnos">Importar alumnos conservando IDs</span>
                        <span wire:loading wire:target="importarAlumnos">Validando e importando...</span>
                    </button>
                </div>

                @if ($resumenAlumnos)
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/25">
                        <div class="flex items-center gap-2 font-black text-emerald-800 dark:text-emerald-200">
                            <flux:icon.file-check class="h-5 w-5" />
                            Última importación de alumnos
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-xl bg-white/70 p-2 dark:bg-neutral-900/50">
                                <p class="text-xs text-slate-500">Creados</p>
                                <p class="font-black">{{ $resumenAlumnos['total_creados'] }}</p>
                            </div>
                            <div class="rounded-xl bg-white/70 p-2 dark:bg-neutral-900/50">
                                <p class="text-xs text-slate-500">Actualizados</p>
                                <p class="font-black">{{ $resumenAlumnos['total_actualizados'] }}</p>
                            </div>
                            <div class="rounded-xl bg-white/70 p-2 dark:bg-neutral-900/50">
                                <p class="text-xs text-slate-500">Sin cambios</p>
                                <p class="font-black">{{ $resumenAlumnos['total_sin_cambios'] }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- RESPALDO DE CALIFICACIONES --}}
        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-slate-200 bg-gradient-to-r from-indigo-50 to-violet-50 px-6 py-5 dark:border-neutral-800 dark:from-indigo-950/30 dark:to-violet-950/20">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-500/20">
                            <flux:icon.file-spreadsheet class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                                Respaldo integral
                            </p>
                            <h2 class="text-xl font-black text-slate-900 dark:text-white">Todas las calificaciones</h2>
                        </div>
                    </div>

                    <span
                        class="rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-black text-indigo-700 dark:border-indigo-800 dark:bg-neutral-900 dark:text-indigo-300">
                        {{ number_format($estadisticas['calificaciones']) }} registros
                    </span>
                </div>
            </div>

            <div class="space-y-6 p-6">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-neutral-800/70">
                        <p class="text-xs font-bold text-slate-500">Calificaciones</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                            {{ number_format($estadisticas['calificaciones']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-neutral-800/70">
                        <p class="text-xs font-bold text-slate-500">Registros de bitácora</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                            {{ number_format($estadisticas['bitacora_calificaciones']) }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                    <p class="text-sm font-black text-slate-800 dark:text-white">El archivo incluye</p>
                    <div class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                        <span class="flex items-center gap-2"><flux:icon.file-check class="h-4 w-4 text-violet-500" /> Calificaciones de todos los niveles, ciclos y periodos</span>
                        <span class="flex items-center gap-2"><flux:icon.file-check class="h-4 w-4 text-violet-500" /> Valores numéricos y claves especiales</span>
                        <span class="flex items-center gap-2"><flux:icon.file-check class="h-4 w-4 text-violet-500" /> Contexto completo: alumno, materia, grado, grupo y periodo</span>
                        <span class="flex items-center gap-2"><flux:icon.file-check class="h-4 w-4 text-violet-500" /> Bitácora de cambios con sus IDs originales</span>
                    </div>
                </div>

                <button type="button" wire:click="exportarCalificaciones" wire:loading.attr="disabled"
                    wire:target="exportarCalificaciones"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:shadow-xl disabled:cursor-wait disabled:opacity-60">
                    <flux:icon.download wire:loading.remove wire:target="exportarCalificaciones" class="h-5 w-5" />
                    <span wire:loading.remove wire:target="exportarCalificaciones">Exportar respaldo de calificaciones</span>
                    <span wire:loading wire:target="exportarCalificaciones">Generando archivo...</span>
                </button>

                <div class="border-t border-dashed border-slate-200 pt-6 dark:border-neutral-700">
                    <label class="block text-sm font-black text-slate-800 dark:text-white">
                        Importar respaldo de calificaciones
                    </label>
                    <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                        La operación conserva los IDs de calificaciones y de la bitácora.
                    </p>

                    <label
                        class="mt-4 flex cursor-pointer items-center gap-4 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-4 transition hover:border-indigo-400 hover:bg-indigo-50/50 dark:border-neutral-700 dark:bg-neutral-800/60 dark:hover:border-indigo-700">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm dark:bg-neutral-900">
                            <flux:icon.upload class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-800 dark:text-white">
                                {{ $archivoCalificaciones?->getClientOriginalName() ?? 'Seleccionar archivo Excel' }}
                            </p>
                            <p class="text-xs text-slate-500">XLSX o XLS · máximo 50 MB</p>
                        </div>
                        <input type="file" wire:model="archivoCalificaciones" accept=".xlsx,.xls" class="sr-only">
                    </label>

                    <div wire:loading wire:target="archivoCalificaciones"
                        class="mt-2 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                        Cargando archivo...
                    </div>

                    @error('archivoCalificaciones')
                        <p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 dark:bg-red-950/30 dark:text-red-300">
                            {{ $message }}
                        </p>
                    @enderror

                    <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-2xl bg-slate-50 p-4 dark:bg-neutral-800/60">
                        <input type="checkbox" wire:model="confirmarCalificaciones"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm leading-5 text-slate-600 dark:text-slate-300">
                            Confirmo que revisé el archivo. Comprendo que se actualizarán calificaciones usando el mismo
                            ID y que ningún ID existente será reemplazado.
                        </span>
                    </label>
                    @error('confirmarCalificaciones')
                        <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                    @enderror

                    <button type="button" wire:click="importarCalificaciones" wire:loading.attr="disabled"
                        wire:target="importarCalificaciones"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl border border-indigo-200 bg-indigo-50 px-5 py-3.5 text-sm font-black text-indigo-700 transition hover:bg-indigo-100 disabled:cursor-wait disabled:opacity-60 dark:border-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300">
                        <flux:icon.upload wire:loading.remove wire:target="importarCalificaciones" class="h-5 w-5" />
                        <span wire:loading.remove wire:target="importarCalificaciones">Importar calificaciones conservando IDs</span>
                        <span wire:loading wire:target="importarCalificaciones">Validando e importando...</span>
                    </button>
                </div>

                @if ($resumenCalificaciones)
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/25">
                        <div class="flex items-center gap-2 font-black text-emerald-800 dark:text-emerald-200">
                            <flux:icon.file-check class="h-5 w-5" />
                            Última importación de calificaciones
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-xl bg-white/70 p-2 dark:bg-neutral-900/50">
                                <p class="text-xs text-slate-500">Creados</p>
                                <p class="font-black">{{ $resumenCalificaciones['total_creados'] }}</p>
                            </div>
                            <div class="rounded-xl bg-white/70 p-2 dark:bg-neutral-900/50">
                                <p class="text-xs text-slate-500">Actualizados</p>
                                <p class="font-black">{{ $resumenCalificaciones['total_actualizados'] }}</p>
                            </div>
                            <div class="rounded-xl bg-white/70 p-2 dark:bg-neutral-900/50">
                                <p class="text-xs text-slate-500">Sin cambios</p>
                                <p class="font-black">{{ $resumenCalificaciones['total_sin_cambios'] }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <section
        class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900">
                <flux:icon.history class="h-5 w-5" />
            </div>
            <div>
                <h3 class="font-black text-slate-900 dark:text-white">Comportamiento de la importación</h3>
                <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Un ID existente se actualiza sin tocar su llave primaria. Un ID inexistente se inserta con el mismo
                    número del archivo. La columna oculta <code>__id_original</code> permite detectar modificaciones
                    accidentales del ID. No se borran alumnos, trayectorias, matrículas, movimientos, calificaciones ni
                    registros de bitácora que no estén incluidos en el archivo.
                </p>
            </div>
        </div>
    </section>
</div>
