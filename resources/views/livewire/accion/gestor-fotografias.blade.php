<div x-data="gestorFotografiasPro" class="min-h-screen bg-slate-50/70 pb-28 dark:bg-neutral-950/40">
    <div class="mx-auto max-w-[1700px] space-y-6 px-4 py-5 sm:px-6 lg:px-8">
        {{-- Encabezado --}}
        <section class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="relative overflow-hidden bg-gradient-to-br from-[#006492] via-sky-700 to-indigo-800 px-5 py-6 text-white sm:px-7 sm:py-8">
                <div class="pointer-events-none absolute -right-20 -top-28 h-72 w-72 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-32 left-1/3 h-64 w-64 rounded-full bg-[#88AC2E]/25 blur-3xl"></div>

                <div class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-white/15 ring-1 ring-white/25 backdrop-blur">
                            <flux:icon.camera class="h-7 w-7" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-black tracking-tight sm:text-3xl">Gestor de fotografías de alumnos</h1>
                                <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-black ring-1 ring-white/20">2.5 × 3 cm</span>
                                <span class="rounded-full bg-[#88AC2E]/90 px-3 py-1 text-[11px] font-black">295 × 354 px · 300 ppp</span>
                            </div>
                            <p class="mt-2 max-w-4xl text-sm leading-6 text-sky-100">
                                Carga individual o masiva, arrastra fotografías, corrige el encuadre y revisa los cambios antes de guardarlos. Las imágenes se normalizan en proporción 5:6 sin deformar el rostro.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('submodulos.accion', ['slug_nivel' => $slug_nivel, 'accion' => 'matricula']) }}"
                            wire:navigate
                            class="inline-flex items-center gap-2 rounded-2xl bg-white/10 px-4 py-2.5 text-sm font-black ring-1 ring-white/20 transition hover:bg-white/20">
                            <flux:icon.arrow-left class="h-4 w-4" />
                            Volver a Matrícula
                        </a>
                        <button type="button" wire:click="limpiarPendientes"
                            @disabled($this->pendientesCount === 0)
                            class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-sky-800 shadow-sm transition hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-50">
                            <flux:icon.x-mark class="h-4 w-4" />
                            Descartar pendientes
                        </button>
                    </div>
                </div>
            </div>

            {{-- Niveles --}}
            <div class="border-b border-slate-200 px-5 py-4 dark:border-neutral-800 sm:px-7">
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach ($niveles as $item)
                        <button type="button" wire:click="seleccionarNivel('{{ $item->slug }}')"
                            class="inline-flex shrink-0 items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-black transition
                                {{ $slug_nivel === $item->slug
                                    ? 'border-[#006492] bg-[#006492] text-white shadow-lg shadow-sky-900/15'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-[#006492] dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300' }}">
                            <flux:icon.users class="h-4 w-4" />
                            {{ $item->nombre }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Indicadores --}}
            <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-5 sm:p-7">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/60">
                    <p class="text-xs font-black uppercase tracking-wider text-slate-400">Alumnos</p>
                    <p class="mt-1 text-3xl font-black text-slate-900 dark:text-white">{{ number_format($resumen['total']) }}</p>
                </div>
                <button type="button" wire:click="$set('estado_foto', 'con_foto')"
                    class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-left transition hover:-translate-y-0.5 hover:shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/25">
                    <p class="text-xs font-black uppercase tracking-wider text-emerald-600">Con foto</p>
                    <p class="mt-1 text-3xl font-black text-emerald-800 dark:text-emerald-300">{{ number_format($resumen['con_foto']) }}</p>
                </button>
                <button type="button" wire:click="$set('estado_foto', 'sin_foto')"
                    class="rounded-3xl border border-amber-200 bg-amber-50 p-4 text-left transition hover:-translate-y-0.5 hover:shadow-sm dark:border-amber-900/60 dark:bg-amber-950/25">
                    <p class="text-xs font-black uppercase tracking-wider text-amber-600">Sin foto</p>
                    <p class="mt-1 text-3xl font-black text-amber-800 dark:text-amber-300">{{ number_format($resumen['sin_foto']) }}</p>
                </button>
                <button type="button" wire:click="$set('estado_foto', 'archivo_faltante')"
                    class="rounded-3xl border border-rose-200 bg-rose-50 p-4 text-left transition hover:-translate-y-0.5 hover:shadow-sm dark:border-rose-900/60 dark:bg-rose-950/25">
                    <p class="text-xs font-black uppercase tracking-wider text-rose-600">Archivo faltante</p>
                    <p class="mt-1 text-3xl font-black text-rose-800 dark:text-rose-300">{{ number_format($resumen['faltantes']) }}</p>
                </button>
                <div class="rounded-3xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900/60 dark:bg-sky-950/25">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-sky-600">Cobertura</p>
                            <p class="mt-1 text-3xl font-black text-sky-800 dark:text-sky-300">{{ $resumen['porcentaje'] }}%</p>
                        </div>
                        <div class="grid h-12 w-12 place-items-center rounded-full bg-white text-sm font-black text-sky-700 shadow-sm dark:bg-neutral-900">
                            {{ $resumen['porcentaje'] }}%
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Filtros --}}
        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
                <div class="grid flex-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
                    <flux:field>
                        <flux:label class="text-xs font-black uppercase tracking-wider text-slate-500">Ciclo escolar</flux:label>
                        <flux:select wire:model.live="ciclo_escolar_id" class="font-semibold">
                            @foreach ($ciclosEscolares as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · Actual' : '' }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label class="text-xs font-black uppercase tracking-wider text-slate-500">Generación</flux:label>
                        <flux:select wire:model.live="generacion_id" class="font-semibold">
                            <flux:select.option value="">Todas</flux:select.option>
                            @foreach ($generaciones as $generacion)
                                <flux:select.option value="{{ $generacion->id }}">{{ $generacion->nombre ?? ($generacion->anio_ingreso . ' - ' . $generacion->anio_egreso) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label class="text-xs font-black uppercase tracking-wider text-slate-500">Grado</flux:label>
                        <flux:select wire:model.live="grado_id" class="font-semibold">
                            <flux:select.option value="">Todos</flux:select.option>
                            @foreach ($grados as $grado)
                                <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    @if ($this->esBachillerato())
                        <flux:field>
                            <flux:label class="text-xs font-black uppercase tracking-wider text-slate-500">Semestre</flux:label>
                            <flux:select wire:model.live="semestre_id" class="font-semibold">
                                <flux:select.option value="">Todos</flux:select.option>
                                @foreach ($semestres as $semestre)
                                    <flux:select.option value="{{ $semestre->id }}">{{ $semestre->nombre ?? ('Semestre ' . $semestre->numero) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:label class="text-xs font-black uppercase tracking-wider text-slate-500">Grupo</flux:label>
                        <flux:select wire:model.live="grupo_id" class="font-semibold">
                            <flux:select.option value="">Todos</flux:select.option>
                            @foreach ($grupos as $grupo)
                                <flux:select.option value="{{ $grupo->id }}">{{ $grupo->asignacionGrupo?->nombre ?? $grupo->grupo ?? $grupo->nombre ?? ('Grupo ' . $grupo->id) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label class="text-xs font-black uppercase tracking-wider text-slate-500">Fotografía</flux:label>
                        <flux:select wire:model.live="estado_foto" class="font-semibold">
                            <flux:select.option value="todos">Todos</flux:select.option>
                            <flux:select.option value="sin_foto">Sin fotografía</flux:select.option>
                            <flux:select.option value="con_foto">Con fotografía</flux:select.option>
                            <flux:select.option value="archivo_faltante">Archivo faltante</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row xl:w-[430px]">
                    <flux:input class="flex-1 font-semibold" type="search" wire:model.live.debounce.350ms="search"
                        icon="magnifying-glass" placeholder="Nombre, matrícula, CURP…" aria-label="Buscar alumno" clearable />
                    <button type="button" wire:click="limpiarFiltros"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-600 transition hover:border-sky-300 hover:text-[#006492] dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-300">
                        <flux:icon.arrow-path class="h-4 w-4" />
                        Limpiar
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4 dark:border-neutral-800">
                <div class="rounded-2xl bg-slate-50 px-3 py-2 dark:bg-neutral-950/60">
                    <flux:checkbox wire:model.live="incluir_no_vigentes" label="Incluir alumnos no vigentes del ciclo" />
                </div>
                <span class="text-xs font-semibold text-slate-400">
                    Los activos se muestran por defecto. Esta opción permite consultar bajas, traslados u otros movimientos que sí iniciaron el ciclo.
                </span>
            </div>
        </section>

        {{-- Carga masiva --}}
        <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="grid h-10 w-10 place-items-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300">
                            <flux:icon.arrow-up-tray class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">Carga masiva inteligente</h2>
                            <p class="text-sm text-slate-500">Nombra los archivos con matrícula, CURP o ID y el sistema intentará asignarlos automáticamente.</p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 text-[11px] font-black">
                    <span class="rounded-full bg-sky-50 px-3 py-1.5 text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900">1. Matrícula</span>
                    <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900">2. CURP</span>
                    <span class="rounded-full bg-violet-50 px-3 py-1.5 text-violet-700 ring-1 ring-violet-200 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900">3. ID</span>
                </div>
            </div>

            <div class="mt-5 rounded-[28px] border-2 border-dashed border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 p-6 text-center transition hover:border-[#006492] dark:border-sky-900/60 dark:from-sky-950/20 dark:via-neutral-950 dark:to-indigo-950/20"
                @dragover.prevent="$el.classList.add('ring-4','ring-sky-100')"
                @dragleave.prevent="$el.classList.remove('ring-4','ring-sky-100')"
                @drop.prevent="$el.classList.remove('ring-4','ring-sky-100'); cargarMasivos($event.dataTransfer.files)">
                <input x-ref="bulkInput" type="file" multiple accept="image/jpeg,image/png,image/webp"
                    wire:model="archivosMasivos" class="hidden">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-white text-[#006492] shadow-lg shadow-sky-900/10 ring-1 ring-sky-100 dark:bg-neutral-900 dark:ring-neutral-800">
                    <flux:icon.photo class="h-8 w-8" />
                </div>
                <h3 class="mt-4 text-base font-black text-slate-900 dark:text-white">Arrastra aquí todas las fotografías del grupo</h3>
                <p class="mx-auto mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                    JPG, JPEG, PNG o WebP · máximo 5 MB por fotografía. También puedes seleccionar varias desde tu equipo.
                </p>
                <button type="button" @click="$refs.bulkInput.click()"
                    class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-[#006492] px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-sky-900/15 transition hover:-translate-y-0.5 hover:bg-sky-800">
                    <flux:icon.folder-open class="h-4 w-4" />
                    Seleccionar fotografías
                </button>
                <div wire:loading.flex wire:target="archivosMasivos" class="mt-4 items-center justify-center gap-2 text-sm font-bold text-[#006492]">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-sky-200 border-t-[#006492]"></div>
                    Analizando archivos y buscando coincidencias…
                </div>
            </div>

            @error('archivosMasivos')
                <div class="mt-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900">{{ $message }}</div>
            @enderror

            @if (count($archivosMasivos) > 0)
                <div class="mt-5">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">Archivos preparados · {{ count($archivosMasivos) }}</h3>
                            <p class="text-xs text-slate-500">Corrige manualmente cualquier fotografía que no haya encontrado alumno.</p>
                        </div>
                        <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900">
                            {{ collect($asignacionesMasivas)->filter()->count() }} asignadas · {{ collect($asignacionesMasivas)->filter(fn ($id) => ! $id)->count() }} sin asignar
                        </span>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($archivosMasivos as $index => $archivo)
                            @php
                                $previewMasivo = null;
                                try { $previewMasivo = $archivo?->temporaryUrl(); } catch (Throwable $e) {}
                                $asignado = $asignacionesMasivas[$index] ?? null;
                            @endphp
                            <article wire:key="archivo-masivo-{{ $index }}"
                                class="flex gap-3 rounded-3xl border {{ isset($erroresArchivos[$index]) ? 'border-rose-200 bg-rose-50/50' : ($asignado ? 'border-emerald-200 bg-emerald-50/40' : 'border-amber-200 bg-amber-50/50') }} p-3 dark:border-neutral-700 dark:bg-neutral-950/40">
                                <button type="button"
                                    @if ($previewMasivo) @click="abrirVisor(@js($previewMasivo), 'Fotografía pendiente', @js($archivo?->getClientOriginalName() ?? 'Archivo de carga masiva'))" @endif
                                    @disabled(! $previewMasivo)
                                    class="group/preview relative h-[108px] w-[90px] shrink-0 overflow-hidden rounded-2xl bg-slate-100 text-left ring-1 ring-black/5 transition enabled:cursor-zoom-in enabled:hover:ring-2 enabled:hover:ring-[#006492] dark:bg-neutral-800"
                                    aria-label="{{ $previewMasivo ? 'Ampliar fotografía ' . ($archivo?->getClientOriginalName() ?? '') : 'Previsualización no disponible' }}">
                                    @if ($previewMasivo)
                                        <img src="{{ $previewMasivo }}" alt="Previsualización" class="h-full w-full object-cover">
                                        <span class="pointer-events-none absolute inset-0 grid place-items-center bg-slate-950/0 text-white opacity-0 transition group-hover/preview:bg-slate-950/35 group-hover/preview:opacity-100">
                                            <span class="grid h-9 w-9 place-items-center rounded-full bg-white/20 backdrop-blur"><flux:icon.magnifying-glass-plus class="h-5 w-5" /></span>
                                        </span>
                                    @else
                                        <div class="grid h-full w-full place-items-center text-slate-400"><flux:icon.photo class="h-7 w-7" /></div>
                                    @endif
                                </button>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-black text-slate-800 dark:text-white" title="{{ $archivo?->getClientOriginalName() }}">{{ $archivo?->getClientOriginalName() }}</p>
                                            <p class="mt-0.5 text-[11px] font-bold {{ $asignado ? 'text-emerald-600' : 'text-amber-600' }}">{{ $coincidenciaArchivo[$index] ?? 'Pendiente de analizar' }}</p>
                                        </div>
                                        <button type="button" wire:click="eliminarArchivoMasivo({{ $index }})"
                                            class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-100 hover:text-rose-600 dark:hover:bg-rose-950/40">
                                            <flux:icon.x-mark class="h-4 w-4" />
                                        </button>
                                    </div>

                                    @if (isset($erroresArchivos[$index]))
                                        <p class="mt-2 text-xs font-bold text-rose-600">{{ $erroresArchivos[$index] }}</p>
                                    @else
                                        <flux:select wire:model.live="asignacionesMasivas.{{ $index }}" size="sm" class="mt-2 text-xs font-semibold">
                                            <flux:select.option value="">— Seleccionar alumno —</flux:select.option>
                                            @foreach ($alumnosAsignables as $opcion)
                                                <flux:select.option value="{{ $opcion->id }}">{{ $this->nombreCompleto($opcion) }} · {{ $opcion->matricula ?: $opcion->curp }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        @if ($asignado && $previewMasivo)
                                            <button type="button"
                                                @click="abrirEditorUrl(@js($previewMasivo), {{ (int) $asignado }}, @js($alumnosAsignables->firstWhere('id', (int) $asignado) ? $this->nombreCompleto($alumnosAsignables->firstWhere('id', (int) $asignado)) : 'Alumno'))"
                                                class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-black text-[#006492] hover:underline">
                                                <flux:icon.adjustments-horizontal class="h-3.5 w-3.5" />
                                                Ajustar recorte
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- Galería --}}
        <section>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Alumnos</h2>
                    <p class="text-sm text-slate-500">Arrastra una foto sobre cualquier tarjeta o usa el botón de selección. Los cambios quedan pendientes hasta que presiones Guardar.</p>
                </div>
                <div class="flex items-center gap-2 text-xs font-black text-slate-500">
                    <span>Mostrar</span>
                    <flux:select wire:model.live="perPage" size="sm" class="w-20 text-xs font-bold" aria-label="Alumnos por página">
                        <flux:select.option value="12">12</flux:select.option>
                        <flux:select.option value="24">24</flux:select.option>
                        <flux:select.option value="48">48</flux:select.option>
                        <flux:select.option value="96">96</flux:select.option>
                    </flux:select>
                    <span>por página</span>
                </div>
            </div>

            @if ($alumnos->count() === 0)
                <div class="rounded-[30px] border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                        <flux:icon.user-group class="h-8 w-8" />
                    </div>
                    <h3 class="mt-4 font-black text-slate-800 dark:text-white">No hay alumnos con estos filtros</h3>
                    <p class="mt-1 text-sm text-slate-500">Cambia el estado de fotografía o limpia los filtros para ampliar la búsqueda.</p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6">
                    @foreach ($alumnos as $alumno)
                        @php
                            $historial = $alumno->ciclosEscolaresHistorial->first();
                            $individual = $fotosIndividuales[$alumno->id] ?? null;
                            $masivoIndex = $this->indiceMasivoParaAlumno((int) $alumno->id);
                            $masivo = $masivoIndex !== null ? ($archivosMasivos[$masivoIndex] ?? null) : null;
                            $pendiente = $individual ?: $masivo;
                            $previewPendiente = null;
                            try { $previewPendiente = $pendiente?->temporaryUrl(); } catch (Throwable $e) {}
                            $marcadaEliminar = (bool) ($eliminaciones[$alumno->id] ?? false);
                            $nombreAlumno = $this->nombreCompleto($alumno);
                        @endphp

                        <article wire:key="foto-alumno-{{ $alumno->id }}"
                            class="group relative overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-sky-300 hover:shadow-xl hover:shadow-sky-900/5 dark:border-neutral-800 dark:bg-neutral-900"
                            @dragover.prevent="$el.classList.add('ring-4','ring-sky-100','border-sky-400')"
                            @dragleave.prevent="$el.classList.remove('ring-4','ring-sky-100','border-sky-400')"
                            @drop.prevent="$el.classList.remove('ring-4','ring-sky-100','border-sky-400'); abrirEditorArchivo($event.dataTransfer.files[0], {{ $alumno->id }}, @js($nombreAlumno))">

                            <div class="p-4">
                                <div class="mb-3 flex items-start justify-between gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-black ring-1
                                        {{ $previewPendiente ? 'bg-violet-50 text-violet-700 ring-violet-200' : ($marcadaEliminar ? 'bg-rose-50 text-rose-700 ring-rose-200' : ($alumno->foto_existe ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200')) }}">
                                        {{ $previewPendiente ? 'CAMBIO PENDIENTE' : ($marcadaEliminar ? 'ELIMINAR FOTO' : ($alumno->foto_existe ? 'CON FOTO' : 'SIN FOTO')) }}
                                    </span>
                                    <span class="text-[10px] font-black text-slate-400">ID {{ $alumno->id }}</span>
                                </div>

                                @if ($previewPendiente && $alumno->foto_existe)
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <p class="mb-1 text-center text-[9px] font-black uppercase tracking-wider text-slate-400">Actual</p>
                                            <button type="button" @click="abrirVisor(@js($alumno->foto_url), @js($nombreAlumno), 'Fotografía actual')"
                                                class="group/photo relative mx-auto block aspect-[5/6] w-full max-w-[118px] cursor-zoom-in overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-black/5 transition hover:ring-2 hover:ring-[#006492]"
                                                aria-label="Ampliar fotografía actual de {{ $nombreAlumno }}">
                                                <img src="{{ $alumno->foto_url }}" alt="Foto actual de {{ $nombreAlumno }}" class="h-full w-full object-cover">
                                                <span class="pointer-events-none absolute inset-0 grid place-items-center bg-slate-950/0 text-white opacity-0 transition group-hover/photo:bg-slate-950/35 group-hover/photo:opacity-100"><flux:icon.magnifying-glass-plus class="h-6 w-6" /></span>
                                            </button>
                                        </div>
                                        <div>
                                            <p class="mb-1 text-center text-[9px] font-black uppercase tracking-wider text-violet-500">Nueva</p>
                                            <button type="button" @click="abrirVisor(@js($previewPendiente), @js($nombreAlumno), 'Nueva fotografía · cambio pendiente')"
                                                class="group/photo relative mx-auto block aspect-[5/6] w-full max-w-[118px] cursor-zoom-in overflow-hidden rounded-2xl bg-violet-50 ring-2 ring-violet-300 transition hover:ring-[#006492]"
                                                aria-label="Ampliar nueva fotografía de {{ $nombreAlumno }}">
                                                <img src="{{ $previewPendiente }}" alt="Nueva foto de {{ $nombreAlumno }}" class="h-full w-full object-cover">
                                                <span class="pointer-events-none absolute inset-0 grid place-items-center bg-slate-950/0 text-white opacity-0 transition group-hover/photo:bg-slate-950/35 group-hover/photo:opacity-100"><flux:icon.magnifying-glass-plus class="h-6 w-6" /></span>
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <button type="button"
                                        @if ($previewPendiente)
                                            @click="abrirVisor(@js($previewPendiente), @js($nombreAlumno), 'Nueva fotografía · cambio pendiente')"
                                        @elseif ($alumno->foto_existe && ! $marcadaEliminar)
                                            @click="abrirVisor(@js($alumno->foto_url), @js($nombreAlumno), 'Fotografía del expediente')"
                                        @endif
                                        @disabled(! $previewPendiente && (! $alumno->foto_existe || $marcadaEliminar))
                                        class="group/photo relative mx-auto block aspect-[5/6] w-full max-w-[158px] overflow-hidden rounded-[24px] bg-gradient-to-b from-slate-100 to-slate-200 text-left ring-1 ring-black/5 transition enabled:cursor-zoom-in enabled:hover:ring-2 enabled:hover:ring-[#006492] dark:from-neutral-800 dark:to-neutral-950"
                                        aria-label="{{ ($previewPendiente || ($alumno->foto_existe && ! $marcadaEliminar)) ? 'Ampliar fotografía de ' . $nombreAlumno : 'Fotografía no disponible' }}">
                                        @if ($previewPendiente)
                                            <img src="{{ $previewPendiente }}" alt="Nueva foto de {{ $nombreAlumno }}" class="h-full w-full object-cover">
                                        @elseif ($alumno->foto_existe && ! $marcadaEliminar)
                                            <img src="{{ $alumno->foto_url }}" alt="Foto de {{ $nombreAlumno }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="absolute inset-0 flex flex-col items-center justify-end overflow-hidden bg-gradient-to-b from-sky-50 to-slate-100 dark:from-sky-950/20 dark:to-neutral-900">
                                                <svg viewBox="0 0 180 216" class="h-full w-full text-slate-300 dark:text-neutral-700" fill="currentColor" aria-label="Silueta de estudiante">
                                                    <circle cx="90" cy="66" r="40"></circle>
                                                    <path d="M20 216c4-58 32-91 70-91s66 33 70 91H20Z"></path>
                                                </svg>
                                            </div>
                                        @endif

                                        @if ($marcadaEliminar)
                                            <div class="absolute inset-0 grid place-items-center bg-rose-950/65 text-center text-white backdrop-blur-sm">
                                                <div>
                                                    <flux:icon.trash class="mx-auto h-8 w-8" />
                                                    <p class="mt-2 text-xs font-black">Se eliminará al guardar</p>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="pointer-events-none absolute inset-x-3 bottom-3 rounded-xl bg-slate-950/55 px-2 py-1.5 text-center text-[9px] font-black uppercase tracking-wider text-white backdrop-blur">
                                            2.5 cm × 3 cm · 5:6
                                        </div>
                                        @if ($previewPendiente || ($alumno->foto_existe && ! $marcadaEliminar))
                                            <span class="pointer-events-none absolute inset-0 grid place-items-center bg-slate-950/0 text-white opacity-0 transition group-hover/photo:bg-slate-950/25 group-hover/photo:opacity-100">
                                                <span class="grid h-11 w-11 place-items-center rounded-full bg-slate-950/45 shadow-lg backdrop-blur"><flux:icon.magnifying-glass-plus class="h-6 w-6" /></span>
                                            </span>
                                        @endif
                                    </button>
                                @endif

                                <div class="mt-4 min-w-0 text-center">
                                    <h3 class="line-clamp-2 min-h-[40px] text-sm font-black leading-5 text-slate-900 dark:text-white">{{ $nombreAlumno }}</h3>
                                    <p class="mt-1 truncate text-[11px] font-black text-[#006492]">{{ $alumno->matricula ?: 'Sin matrícula' }}</p>
                                    <p class="mt-1 line-clamp-2 min-h-[32px] text-[11px] font-semibold leading-4 text-slate-500">{{ $this->etiquetaGrupo($historial) }}</p>
                                </div>

                                <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
                                    x-ref="fotoInput{{ $alumno->id }}"
                                    @change="abrirEditor($event, {{ $alumno->id }}, @js($nombreAlumno))">

                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <button type="button" @click="$refs.fotoInput{{ $alumno->id }}.click()"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#006492] px-3 py-2 text-[11px] font-black text-white transition hover:bg-sky-800">
                                        <flux:icon.photo class="h-3.5 w-3.5" />
                                        {{ $alumno->foto_existe ? 'Reemplazar' : 'Seleccionar' }}
                                    </button>

                                    @if ($previewPendiente)
                                        <button type="button" @click="abrirEditorUrl(@js($previewPendiente), {{ $alumno->id }}, @js($nombreAlumno))"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-[11px] font-black text-violet-700 transition hover:bg-violet-100 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-300">
                                            <flux:icon.adjustments-horizontal class="h-3.5 w-3.5" />
                                            Editar
                                        </button>
                                    @elseif ($alumno->foto_existe)
                                        <button type="button" @click="abrirEditorUrl(@js($alumno->foto_url), {{ $alumno->id }}, @js($nombreAlumno))"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-[11px] font-black text-slate-600 transition hover:border-sky-300 hover:text-[#006492] dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-300">
                                            <flux:icon.adjustments-horizontal class="h-3.5 w-3.5" />
                                            Editar
                                        </button>
                                    @else
                                        <button type="button" @click="$refs.fotoInput{{ $alumno->id }}.click()"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-[11px] font-black text-slate-600 transition hover:border-sky-300 hover:text-[#006492] dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-300">
                                            <flux:icon.arrow-up-tray class="h-3.5 w-3.5" />
                                            Arrastrar
                                        </button>
                                    @endif
                                </div>

                                <div class="mt-2 flex items-center justify-center gap-3">
                                    @if ($individual)
                                        <button type="button" wire:click="quitarFotoPendiente({{ $alumno->id }})" class="text-[10px] font-black text-slate-400 hover:text-rose-600">Descartar nueva</button>
                                    @endif
                                    @if ($alumno->foto_path)
                                        <button type="button" wire:click="alternarEliminar({{ $alumno->id }})"
                                            class="text-[10px] font-black {{ $marcadaEliminar ? 'text-emerald-600' : 'text-rose-500' }} hover:underline">
                                            {{ $marcadaEliminar ? 'Conservar foto' : 'Eliminar foto' }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-6">{{ $alumnos->links() }}</div>
            @endif
        </section>
    </div>

    {{-- Barra fija de cambios --}}
    @if ($this->pendientesCount > 0)
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-18px_45px_-25px_rgba(15,23,42,.45)] backdrop-blur-xl dark:border-neutral-800 dark:bg-neutral-950/95">
            <div class="mx-auto flex max-w-[1700px] flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300">
                        <span class="text-base font-black">{{ $this->pendientesCount }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ $this->pendientesCount }} alumno(s) con cambios pendientes</p>
                        <p class="text-xs text-slate-500">Nada se modifica definitivamente hasta guardar.</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="limpiarPendientes"
                        class="flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-600 sm:flex-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                        Cancelar cambios
                    </button>
                    <button type="button" @click="confirmarGuardado({{ $this->pendientesCount }}, {{ collect($eliminaciones)->filter()->count() }})" wire:loading.attr="disabled" wire:target="guardarCambios"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#006492] to-sky-700 px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-sky-900/20 transition hover:-translate-y-0.5 disabled:opacity-60 sm:flex-none">
                        <span wire:loading.remove wire:target="guardarCambios" class="inline-flex items-center gap-2"><flux:icon.check class="h-4 w-4" /> Guardar {{ $this->pendientesCount }} cambio(s)</span>
                        <span wire:loading.flex wire:target="guardarCambios" class="items-center gap-2"><span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span> Guardando…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Visor de fotografía --}}
    <div x-cloak x-show="viewerOpen" x-transition.opacity
        class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/85 p-3 backdrop-blur-md sm:p-6"
        @click.self="cerrarVisor()" @keydown.escape.window="if (viewerOpen) cerrarVisor()" role="dialog" aria-modal="true"
        aria-labelledby="visor-fotografia-titulo">
        <div x-show="viewerOpen" x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="scale-100 opacity-100"
            x-transition:leave-end="scale-95 opacity-0"
            class="relative flex max-h-[94vh] w-full max-w-3xl flex-col overflow-hidden rounded-[30px] border border-white/15 bg-white shadow-2xl shadow-black/40 dark:bg-neutral-900">
            <div class="relative overflow-hidden bg-gradient-to-r from-[#006492] via-sky-700 to-indigo-800 px-5 py-4 text-white sm:px-6">
                <div class="pointer-events-none absolute -right-10 -top-16 h-40 w-40 rounded-full bg-white/15 blur-2xl"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white/15 ring-1 ring-white/20 backdrop-blur">
                            <flux:icon.photo class="h-6 w-6" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[.2em] text-sky-100">Vista ampliada</p>
                            <h3 id="visor-fotografia-titulo" class="truncate text-lg font-black" x-text="viewerName"></h3>
                            <p class="truncate text-xs font-semibold text-sky-100" x-text="viewerDetail"></p>
                        </div>
                    </div>
                    <button type="button" @click="cerrarVisor()"
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/10 text-white ring-1 ring-white/20 transition hover:bg-white/20"
                        aria-label="Cerrar vista ampliada">
                        <flux:icon.x-mark class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-auto bg-[radial-gradient(circle_at_top,_#e0f2fe,_#f8fafc_55%,_#e2e8f0)] p-5 dark:bg-[radial-gradient(circle_at_top,_#082f49,_#0a0a0a_55%,_#171717)] sm:p-8">
                <div class="mx-auto w-fit rounded-[26px] bg-white p-2 shadow-2xl shadow-slate-900/20 ring-1 ring-black/10 dark:bg-neutral-800">
                    <img :src="viewerUrl" :alt="'Fotografía ampliada de ' + viewerName"
                        class="max-h-[64vh] w-auto max-w-full rounded-[20px] object-contain" />
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-[11px] font-black">
                    <span class="rounded-full bg-white/90 px-3 py-1.5 text-slate-600 shadow-sm ring-1 ring-slate-200 dark:bg-neutral-900/90 dark:text-neutral-300 dark:ring-neutral-700">2.5 × 3 cm</span>
                    <span class="rounded-full bg-white/90 px-3 py-1.5 text-slate-600 shadow-sm ring-1 ring-slate-200 dark:bg-neutral-900/90 dark:text-neutral-300 dark:ring-neutral-700">Proporción 5:6</span>
                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700 shadow-sm ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900">Fotografía escolar</span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4 dark:border-neutral-800 dark:bg-neutral-900 sm:px-6">
                <a :href="viewerUrl" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-600 transition hover:border-sky-300 hover:text-[#006492] dark:border-neutral-700 dark:text-neutral-300">
                    <flux:icon.arrows-pointing-out class="h-4 w-4" /> Abrir original
                </a>
                <button type="button" @click="cerrarVisor()"
                    class="rounded-2xl bg-[#006492] px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-sky-900/15 transition hover:bg-sky-800">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    {{-- Editor de recorte --}}
    <div x-cloak x-show="editorOpen" x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 p-3 backdrop-blur-sm"
        @keydown.escape.window="if (!uploadingEditor) cerrarEditor()">
        <div x-show="editorOpen" x-transition.scale.origin.center
            class="max-h-[96vh] w-full max-w-4xl overflow-y-auto rounded-[30px] bg-white shadow-2xl dark:bg-neutral-900">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-neutral-800">
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Editar fotografía</h3>
                    <p class="text-xs font-semibold text-slate-500" x-text="editorName"></p>
                </div>
                <button type="button" @click="cerrarEditor()" :disabled="uploadingEditor"
                    class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-50 dark:hover:bg-neutral-800">
                    <flux:icon.x-mark class="h-5 w-5" />
                </button>
            </div>

            <div class="grid gap-6 p-5 lg:grid-cols-[1fr_300px] lg:p-6">
                <div>
                    <div class="rounded-[26px] bg-slate-100 p-4 dark:bg-neutral-950">
                        <div class="mx-auto w-full max-w-[420px]">
                            <p class="mb-2 text-center text-[11px] font-black uppercase tracking-[.2em] text-slate-400">Área final · relación 5:6</p>
                            <canvas x-ref="cropCanvas" width="295" height="354"
                                class="mx-auto aspect-[5/6] w-full max-w-[295px] cursor-grab rounded-2xl bg-white shadow-xl ring-1 ring-black/10 active:cursor-grabbing"
                                @pointerdown="iniciarArrastre($event)"
                                @pointermove="moverArrastre($event)"
                                @pointerup="terminarArrastre()"
                                @pointerleave="terminarArrastre()"></canvas>
                            <p class="mt-3 text-center text-xs text-slate-500">Arrastra la imagen dentro del marco para ajustar el encuadre.</p>
                        </div>
                    </div>
                </div>

                <aside class="space-y-5">
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-800">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-500">Zoom</span>
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-black text-slate-700 dark:bg-neutral-800 dark:text-neutral-200" x-text="Math.round(zoom * 100) + '%'">100%</span>
                        </div>
                        <input type="range" min="1" max="3" step="0.01" x-model.number="zoom" @input="dibujarEditor()"
                            class="mt-3 w-full accent-[#006492]">
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-800">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-500">Rotación</span>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <button type="button" @click="rotar(-90)" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-200 dark:bg-neutral-800 dark:text-neutral-200">
                                <flux:icon.arrow-uturn-left class="h-4 w-4" /> -90°
                            </button>
                            <button type="button" @click="rotar(90)" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-200 dark:bg-neutral-800 dark:text-neutral-200">
                                <flux:icon.arrow-uturn-right class="h-4 w-4" /> +90°
                            </button>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-xs leading-5 text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/25 dark:text-sky-200">
                        <p class="font-black">Salida institucional</p>
                        <p class="mt-1">2.5 cm de ancho × 3 cm de alto, 295 × 354 píxeles, relación 5:6. El servidor vuelve a validar y normalizar la foto antes de guardarla.</p>
                    </div>

                    <button type="button" @click="reiniciarEditor()" :disabled="uploadingEditor"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-600 hover:bg-slate-50 disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                        Restablecer encuadre
                    </button>
                </aside>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 px-5 py-4 dark:border-neutral-800 sm:flex-row sm:justify-end">
                <button type="button" @click="cerrarEditor()" :disabled="uploadingEditor"
                    class="rounded-2xl border border-slate-200 px-5 py-2.5 text-sm font-black text-slate-600 disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-300">Cancelar</button>
                <button type="button" @click="exportarEditor()" :disabled="uploadingEditor"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#006492] px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-sky-900/15 disabled:opacity-60">
                    <template x-if="!uploadingEditor"><span class="inline-flex items-center gap-2"><flux:icon.check class="h-4 w-4" /> Usar esta fotografía</span></template>
                    <template x-if="uploadingEditor"><span class="inline-flex items-center gap-2"><span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span> Preparando…</span></template>
                </button>
            </div>
        </div>
    </div>

    @script
        <script>
            Alpine.data('gestorFotografiasPro', () => ({
                viewerOpen: false,
                viewerUrl: '',
                viewerName: '',
                viewerDetail: '',
                editorOpen: false,
                editorStudentId: null,
                editorName: '',
                image: null,
                zoom: 1,
                rotation: 0,
                offsetX: 0,
                offsetY: 0,
                dragging: false,
                lastX: 0,
                lastY: 0,
                uploadingEditor: false,

                abrirVisor(url, nombre, detalle = '') {
                    if (!url) return;
                    this.viewerUrl = url;
                    this.viewerName = nombre || 'Fotografía del alumno';
                    this.viewerDetail = detalle;
                    this.viewerOpen = true;
                },

                cerrarVisor() {
                    this.viewerOpen = false;
                    window.setTimeout(() => {
                        if (!this.viewerOpen) {
                            this.viewerUrl = '';
                            this.viewerName = '';
                            this.viewerDetail = '';
                        }
                    }, 200);
                },

                cargarMasivos(files) {
                    if (!files || !files.length) return;
                    const dt = new DataTransfer();
                    Array.from(files).forEach(file => dt.items.add(file));
                    this.$refs.bulkInput.files = dt.files;
                    this.$refs.bulkInput.dispatchEvent(new Event('change', { bubbles: true }));
                },

                abrirEditor(event, id, nombre) {
                    const file = event.target.files?.[0];
                    event.target.value = '';
                    this.abrirEditorArchivo(file, id, nombre);
                },

                abrirEditorArchivo(file, id, nombre) {
                    if (!file) return;
                    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                        Swal.fire('Formato no válido', 'Selecciona una fotografía JPG, PNG o WebP.', 'warning');
                        return;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        Swal.fire('Fotografía demasiado grande', 'Cada archivo puede pesar hasta 5 MB.', 'warning');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = () => this.cargarImagenEditor(reader.result, id, nombre);
                    reader.readAsDataURL(file);
                },

                async abrirEditorUrl(url, id, nombre) {
                    if (!url) return;
                    try {
                        const response = await fetch(url);
                        if (!response.ok) throw new Error('No fue posible leer la imagen.');
                        const blob = await response.blob();
                        const file = new File([blob], `foto-${id}.jpg`, { type: blob.type || 'image/jpeg' });
                        this.abrirEditorArchivo(file, id, nombre);
                    } catch (error) {
                        Swal.fire('No se pudo abrir el editor', 'Selecciona nuevamente la fotografía desde tu equipo para editarla.', 'warning');
                    }
                },

                cargarImagenEditor(src, id, nombre) {
                    const img = new Image();
                    img.onload = () => {
                        this.image = img;
                        this.editorStudentId = Number(id);
                        this.editorName = nombre;
                        this.zoom = 1;
                        this.rotation = 0;
                        this.offsetX = 0;
                        this.offsetY = 0;
                        this.editorOpen = true;
                        this.$nextTick(() => this.dibujarEditor());
                    };
                    img.onerror = () => Swal.fire('Imagen inválida', 'No fue posible leer la fotografía seleccionada.', 'error');
                    img.src = src;
                },

                dibujarEditor() {
                    const canvas = this.$refs.cropCanvas;
                    if (!canvas || !this.image) return;
                    const ctx = canvas.getContext('2d');
                    const w = canvas.width;
                    const h = canvas.height;
                    ctx.clearRect(0, 0, w, h);
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);

                    const normalRotation = ((this.rotation % 360) + 360) % 360;
                    const quarter = normalRotation === 90 || normalRotation === 270;
                    const rotatedW = quarter ? this.image.height : this.image.width;
                    const rotatedH = quarter ? this.image.width : this.image.height;
                    const cover = Math.max(w / rotatedW, h / rotatedH) * this.zoom;

                    ctx.save();
                    ctx.translate(w / 2 + this.offsetX, h / 2 + this.offsetY);
                    ctx.rotate(this.rotation * Math.PI / 180);
                    ctx.scale(cover, cover);
                    ctx.drawImage(this.image, -this.image.width / 2, -this.image.height / 2);
                    ctx.restore();
                },

                iniciarArrastre(event) {
                    this.dragging = true;
                    this.lastX = event.clientX;
                    this.lastY = event.clientY;
                    event.currentTarget.setPointerCapture?.(event.pointerId);
                },

                moverArrastre(event) {
                    if (!this.dragging) return;
                    const rect = event.currentTarget.getBoundingClientRect();
                    const scaleX = event.currentTarget.width / rect.width;
                    const scaleY = event.currentTarget.height / rect.height;
                    this.offsetX += (event.clientX - this.lastX) * scaleX;
                    this.offsetY += (event.clientY - this.lastY) * scaleY;
                    this.lastX = event.clientX;
                    this.lastY = event.clientY;
                    this.dibujarEditor();
                },

                terminarArrastre() {
                    this.dragging = false;
                },

                rotar(grados) {
                    this.rotation += grados;
                    this.offsetX = 0;
                    this.offsetY = 0;
                    this.dibujarEditor();
                },

                reiniciarEditor() {
                    this.zoom = 1;
                    this.rotation = 0;
                    this.offsetX = 0;
                    this.offsetY = 0;
                    this.dibujarEditor();
                },

                cerrarEditor() {
                    if (this.uploadingEditor) return;
                    this.editorOpen = false;
                    this.image = null;
                    this.editorStudentId = null;
                },

                async confirmarGuardado(total, eliminaciones) {
                    if (eliminaciones > 0) {
                        const result = await Swal.fire({
                            title: 'Guardar cambios de fotografías',
                            html: `Se aplicarán <b>${total}</b> cambio(s), incluyendo <b>${eliminaciones}</b> eliminación(es) de fotografía.<br><br>Las fotografías reemplazadas o eliminadas dejarán de estar disponibles.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, guardar cambios',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#006492',
                            reverseButtons: true,
                        });
                        if (!result.isConfirmed) return;
                    }

                    await this.$wire.guardarCambios();
                },

                exportarEditor() {
                    const canvas = this.$refs.cropCanvas;
                    const alumnoId = this.editorStudentId;
                    if (!canvas || !alumnoId || this.uploadingEditor) return;

                    this.uploadingEditor = true;
                    canvas.toBlob((blob) => {
                        if (!blob) {
                            this.uploadingEditor = false;
                            Swal.fire('No se pudo preparar la fotografía', 'Intenta nuevamente.', 'error');
                            return;
                        }

                        const file = new File([blob], `alumno-${alumnoId}.jpg`, { type: 'image/jpeg' });
                        this.$wire.upload('fotoEditor', file,
                            async () => {
                                try {
                                    await this.$wire.asignarFotoEditor(alumnoId);
                                    this.editorOpen = false;
                                    this.image = null;
                                } finally {
                                    this.uploadingEditor = false;
                                }
                            },
                            () => {
                                this.uploadingEditor = false;
                                Swal.fire('No se pudo cargar la fotografía', 'Revisa el archivo e inténtalo nuevamente.', 'error');
                            }
                        );
                    }, 'image/jpeg', 0.92);
                },
            }));
        </script>
    @endscript
</div>
