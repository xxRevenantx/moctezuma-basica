<div id="expedientes-digitales-root" class="space-y-6" x-data="expedienteUploader()"
    @expediente-documento-guardado.window="onSaved($event.detail)">
    @php
        $coloresEstado = [
            'pendiente' =>
                'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/50',
            'no_aplica' =>
                'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/50',
            'recibido' =>
                'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50',
            'validado' =>
                'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50',
            'rechazado' =>
                'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50',
            'reemplazado' =>
                'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700',
            'emitida' =>
                'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50',
            'cancelada' =>
                'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50',
        ];
    @endphp

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-cloak x-show="opening" x-transition.opacity
        class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm">
        <div
            class="flex min-w-[280px] flex-col items-center rounded-3xl border border-white/15 bg-slate-950/90 px-7 py-6 text-center text-white shadow-2xl">
            <div class="relative size-12">
                <div class="absolute inset-0 rounded-full border-4 border-white/20"></div>
                <div class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-sky-400">
                </div>
            </div>
            <p class="mt-4 text-sm font-black">Preparando formulario…</p>
            <p class="mt-1 text-xs text-slate-300">Cargando los datos del documento seleccionado.</p>
        </div>
    </div>


    <section
        class="relative overflow-hidden rounded-[30px] border border-slate-200/80 bg-gradient-to-br from-slate-950 via-indigo-950 to-sky-900 p-6 text-white shadow-2xl shadow-indigo-950/20 sm:p-8">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-sky-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 left-20 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl">
        </div>

        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-sky-100 backdrop-blur">
                    <flux:icon name="shield-check" class="size-4" />
                    Acceso autorizado para administración y control escolar
                </div>

                <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Expedientes digitales</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200 sm:text-base">
                    Conserva el historial documental del alumno durante preescolar, primaria, secundaria y
                    bachillerato. Cada reemplazo genera una nueva versión sin eliminar la anterior.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:min-w-[650px] xl:grid-cols-5">
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-semibold text-slate-300">Alumnos</p>
                    <p class="mt-1 text-2xl font-black">{{ number_format($metricas['total']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-semibold text-emerald-200">Completos</p>
                    <p class="mt-1 text-2xl font-black">{{ number_format($metricas['completos']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-semibold text-amber-200">Incompletos</p>
                    <p class="mt-1 text-2xl font-black">{{ number_format($metricas['incompletos']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-semibold text-violet-200">Egresados</p>
                    <p class="mt-1 text-2xl font-black">{{ number_format($metricas['egresados']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-semibold text-rose-200">Bajas</p>
                    <p class="mt-1 text-2xl font-black">{{ number_format($metricas['bajas']) }}</p>
                </div>
            </div>
        </div>
    </section>

    <section
        class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Buscar alumno
                </label>
                <div class="relative">
                    <flux:icon name="magnifying-glass"
                        class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-slate-400" />
                    <input wire:model.live.debounce.350ms="buscar" type="search"
                        placeholder="Nombre, matrícula, CURP o folio..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                </div>
            </div>

            <div class="lg:col-span-3">
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Nivel actual
                </label>
                <select wire:model.live="nivel_id"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                    <option value="">Todos los niveles</option>
                    @foreach ($niveles as $nivel)
                        <option value="{{ $nivel['id'] }}">{{ $nivel['nombre'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Expediente
                </label>
                <select wire:model.live="estado_expediente"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                    <option value="todos">Todos</option>
                    <option value="completos">Completos</option>
                    <option value="incompletos">Incompletos</option>
                    <option value="egresados">Egresados</option>
                    <option value="bajas">Bajas, traslados y archivados</option>
                </select>
            </div>

            <div class="flex items-end gap-2 lg:col-span-2">
                <select wire:model.live="perPage"
                    class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-800 outline-none dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>

                <button type="button" wire:click="limpiarFiltros"
                    class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300 dark:hover:bg-indigo-950/30"
                    title="Limpiar filtros">
                    <flux:icon name="arrow-path" class="size-4" />
                </button>
            </div>
        </div>
    </section>

    <section
        class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div
            class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white">Alumnos y avance documental</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Los documentos son opcionales y el indicador funciona como control administrativo.
                </p>
            </div>
            <span
                class="inline-flex items-center gap-2 self-start rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300">
                <flux:icon name="document-duplicate" class="size-4" />
                {{ number_format($alumnos->total()) }} resultados
            </span>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                <thead class="bg-slate-50 dark:bg-neutral-950">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                            Alumno</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                            Ubicación escolar</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                            Expediente</th>
                        <th class="px-5 py-3 text-right text-[11px] font-black uppercase tracking-wider text-slate-500">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($alumnos as $alumno)
                        @php
                            $resumen = $alumno->resumen_documental;
                        @endphp
                        <tr wire:key="expediente-alumno-{{ $alumno->id }}"
                            class="transition hover:bg-slate-50/80 dark:hover:bg-neutral-800/50">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($alumno->foto_existe)
                                        <img src="{{ $alumno->foto_url }}" alt="Fotografía de {{ $this->nombreCompleto($alumno) }}"
                                            class="size-11 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-neutral-700">
                                    @else
                                        <div class="relative flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-500 font-black text-white shadow-sm">
                                            {{ $alumno->iniciales }}
                                            @if ($alumno->foto_path)
                                                <span class="absolute -right-1 -top-1 flex size-4 items-center justify-center rounded-full bg-rose-500 ring-2 ring-white dark:ring-neutral-900" title="La fotografía registrada no existe">
                                                    <flux:icon name="exclamation-triangle" class="size-2.5" />
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="font-black text-slate-900 dark:text-white">
                                            {{ $this->nombreCompleto($alumno) }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            {{ $alumno->matricula }} · {{ $alumno->curp }}
                                        </p>
                                        @if (!$alumno->foto_existe)
                                            <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                                                {{ $alumno->foto_path ? 'Requiere volver a cargar foto' : 'Sin fotografía' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                    {{ $alumno->nivel?->nombre ?? 'Sin nivel' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $alumno->grado?->nombre ?? 'Sin grado' }} ·
                                    Grupo {{ $alumno->grupo?->asignacionGrupo?->nombre ?? '—' }}
                                </p>
                                @if ($etiquetaEstado = $this->etiquetaEstadoExpediente($alumno))
                                    <span
                                        class="mt-2 inline-flex rounded-full px-2 py-1 text-[10px] font-black uppercase {{ $this->claseEstadoExpediente($alumno) }}">
                                        {{ $etiquetaEstado }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-2.5 w-36 overflow-hidden rounded-full bg-slate-100 dark:bg-neutral-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-sky-500"
                                            style="width: {{ $resumen['porcentaje'] }}%"></div>
                                    </div>
                                    <span class="text-sm font-black text-slate-800 dark:text-white">
                                        {{ $resumen['completados'] }}/{{ $resumen['total'] }}
                                    </span>
                                </div>
                                <p
                                    class="mt-2 text-xs font-bold {{ $resumen['completo'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ $resumen['completo'] ? 'Expediente completo' : $resumen['pendientes'] . ' documento(s) pendiente(s)' }}
                                </p>
                                @if (($resumen['archivos_faltantes'] ?? 0) > 0)
                                    <p class="mt-1 text-xs font-black text-rose-600 dark:text-rose-400">
                                        {{ $resumen['archivos_faltantes'] }} archivo(s) físico(s) faltante(s)
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button type="button" @click="openStudentFile({{ $alumno->id }})"
                                    :disabled="openingStudentFile"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-lg disabled:cursor-wait disabled:opacity-70">
                                    <flux:icon name="folder-open" class="size-4" />
                                    Abrir expediente
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div
                                    class="mx-auto flex size-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                                    <flux:icon name="document-magnifying-glass" class="size-7" />
                                </div>
                                <h3 class="mt-4 font-black text-slate-800 dark:text-white">No se encontraron alumnos
                                </h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Modifica los filtros para
                                    ampliar la búsqueda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-neutral-800 lg:hidden">
            @forelse ($alumnos as $alumno)
                @php
                    $resumen = $alumno->resumen_documental;
                @endphp
                <article class="p-5" wire:key="expediente-card-{{ $alumno->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">{{ $this->nombreCompleto($alumno) }}
                            </h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $alumno->matricula }} ·
                                {{ $alumno->nivel?->nombre ?? 'Sin nivel' }}</p>
                            @if ($etiquetaEstado = $this->etiquetaEstadoExpediente($alumno))
                                <span class="mt-2 inline-flex rounded-full px-2 py-1 text-[10px] font-black uppercase {{ $this->claseEstadoExpediente($alumno) }}">
                                    {{ $etiquetaEstado }}
                                </span>
                            @endif
                        </div>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-black {{ $resumen['completo'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300' }}">
                            {{ $resumen['completados'] }}/{{ $resumen['total'] }}
                        </span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-neutral-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-sky-500"
                            style="width: {{ $resumen['porcentaje'] }}%"></div>
                    </div>
                    <button type="button" @click="openStudentFile({{ $alumno->id }})"
                        :disabled="openingStudentFile"
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white disabled:cursor-wait disabled:opacity-70">
                        <flux:icon name="folder-open" class="size-4" />
                        Abrir expediente
                    </button>
                </article>
            @empty
                <div class="p-12 text-center text-sm text-slate-500">No se encontraron alumnos.</div>
            @endforelse
        </div>

        @if ($alumnos->hasPages())
            <div class="border-t border-slate-200 px-5 py-4 dark:border-neutral-800">
                {{ $alumnos->onEachSide(1)->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </section>

    @if ($alumnoSeleccionado && $resumenSeleccionado)
        @php
            $documentosActuales = $documentosSeleccionados->where('es_actual', true);
            $documentosHistoricos = $documentosSeleccionados->where('es_actual', false);
            $slugsCertificados = ['certificado-estudios', 'certificado-terminacion'];
            $certificados = $documentosActuales->filter(
                fn($documento) => in_array($documento->tipoDocumento?->slug, $slugsCertificados, true),
            );
            $slugsAcademicos = ['boleta-final-grado', 'constancia-estudios', 'constancia-baja-traslado', 'constancia-traslado-calificaciones'];
            $academicos = $documentosActuales->filter(
                fn($documento) => in_array($documento->tipoDocumento?->slug, $slugsAcademicos, true),
            );
            $generales = $documentosActuales->reject(
                fn($documento) => in_array($documento->tipoDocumento?->slug, $slugsCertificados, true) ||
                    in_array($documento->tipoDocumento?->slug, $slugsAcademicos, true),
            );
            $soloHistorico = $alumnoSeleccionado->expedienteSoloLectura();
            $esEgresado = $alumnoSeleccionado->esEgresado();

            $tipoConstanciaEstudios = collect($tiposDocumentos)->firstWhere('slug', 'constancia-estudios');
            $tipoConstanciaBaja = collect($tiposDocumentos)->firstWhere('slug', 'constancia-baja-traslado');
            $nivelContextoId = $alumnoSeleccionado->nivel_id;
            $gradoContextoId = $alumnoSeleccionado->grado_id;
            $cicloContextoId = data_get($ciclosEscolares, '0.id');
            $slugReingreso = $alumnoSeleccionado->nivel?->slug;
            $estatusRetornable = ! $alumnoSeleccionado->trashed() && $alumnoSeleccionado->esBajaAdministrativa();
        @endphp

        <section id="expediente-seleccionado"
            class="scroll-mt-24 overflow-hidden rounded-[30px] border border-indigo-200 bg-white shadow-xl shadow-indigo-500/10 dark:border-indigo-900/50 dark:bg-neutral-900">
            <div
                class="relative overflow-hidden bg-gradient-to-r from-indigo-700 via-blue-700 to-sky-600 p-6 text-white sm:p-7">
                <div class="pointer-events-none absolute -right-12 -top-20 size-56 rounded-full bg-white/10 blur-2xl">
                </div>
                <div class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full bg-white/15 px-3 py-1 text-xs font-black uppercase tracking-wider">
                                Expediente del alumno
                            </span>
                            @if ($esEgresado)
                                <span class="rounded-full bg-violet-500/85 px-3 py-1 text-xs font-black uppercase">
                                    Egresado · histórico editable
                                </span>
                            @elseif ($soloHistorico)
                                <span class="rounded-full bg-rose-500/80 px-3 py-1 text-xs font-black uppercase">
                                    {{ $alumnoSeleccionado->etiqueta_estatus }} · solo histórico
                                </span>
                            @endif
                        </div>
                        <h2 class="mt-3 text-2xl font-black sm:text-3xl">
                            {{ $this->nombreCompleto($alumnoSeleccionado) }}</h2>
                        <p class="mt-2 text-sm text-blue-100">
                            {{ $alumnoSeleccionado->matricula }} ·
                            {{ $alumnoSeleccionado->nivel?->nombre ?? 'Sin nivel' }} ·
                            {{ $alumnoSeleccionado->grado?->nombre ?? 'Sin grado' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if ($soloHistorico && $estatusRetornable && $slugReingreso)
                            <a href="{{ route('submodulos.accion', ['slug_nivel' => $slugReingreso, 'accion' => 'bajas']) }}"
                                class="inline-flex items-center gap-2 rounded-2xl bg-violet-500 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-violet-400 hover:shadow-lg">
                                <flux:icon name="arrow-path" class="size-4" />
                                Reingresar o reincorporar
                            </a>
                        @endif

                        @if (!$soloHistorico)
                            <button type="button" wire:click="abrirOrganizador"
                                class="inline-flex items-center gap-2 rounded-2xl bg-violet-500 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-violet-400 hover:shadow-lg">
                                <flux:icon name="squares-plus" class="size-4" />
                                Organizar expediente
                            </button>
                        @endif

                        @if ($documentosSeleccionados->isNotEmpty() || $alumnoSeleccionado->cambiosAcademicos->isNotEmpty() || $alumnoSeleccionado->movimientos->isNotEmpty() || $alumnoSeleccionado->matriculasAlumno->isNotEmpty())
                            <a href="{{ route('misrutas.expedientes.zip', $alumnoSeleccionado) }}"
                                class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-indigo-700 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                                <flux:icon name="archive-box" class="size-4" />
                                Descargar ZIP
                            </a>
                        @endif

                        <button type="button" wire:click="cerrarExpediente"
                            class="inline-flex size-11 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-white backdrop-blur transition hover:bg-white/20"
                            title="Cerrar expediente">
                            <flux:icon name="x-mark" class="size-5" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-7 p-5 sm:p-7">
                @if (($resumenSeleccionado['archivos_faltantes'] ?? 0) > 0 || ($resumenSeleccionado['foto_faltante'] ?? false))
                    <div class="rounded-3xl border border-rose-200 bg-rose-50 p-4 text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-200">
                        <div class="flex items-start gap-3">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-rose-500 text-white">
                                <flux:icon name="exclamation-triangle" class="size-5" />
                            </div>
                            <div>
                                <h3 class="font-black">Se detectaron archivos faltantes</h3>
                                <p class="mt-1 text-sm">Los registros continúan en la base de datos, pero no cuentan como disponibles hasta volver a cargar sus archivos.</p>
                                <ul class="mt-2 list-disc pl-5 text-xs font-bold">
                                    @if ($resumenSeleccionado['foto_faltante'] ?? false)
                                        <li>Fotografía del alumno</li>
                                    @endif
                                    @foreach (collect($resumenSeleccionado['items'])->where('archivo_faltante', true) as $item)
                                        <li>{{ $item['etiqueta'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div
                        class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-neutral-800 dark:bg-neutral-950">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Avance del expediente</p>
                        <div class="mt-3 flex items-end justify-between gap-3">
                            <p class="text-4xl font-black text-slate-900 dark:text-white">
                                {{ $resumenSeleccionado['porcentaje'] }}%</p>
                            <p class="text-sm font-bold text-slate-500">
                                {{ $resumenSeleccionado['completados'] }}/{{ $resumenSeleccionado['total'] }}</p>
                        </div>
                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-neutral-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-sky-500"
                                style="width: {{ $resumenSeleccionado['porcentaje'] }}%"></div>
                        </div>
                    </div>

                    <div
                        class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Tutor registrado</p>
                        <p class="mt-3 text-lg font-black text-slate-900 dark:text-white">
                            @if ($alumnoSeleccionado->tutor)
                                {{ trim($alumnoSeleccionado->tutor->nombre . ' ' . $alumnoSeleccionado->tutor->apellido_paterno . ' ' . $alumnoSeleccionado->tutor->apellido_materno) }}
                            @else
                                Sin tutor asignado
                            @endif
                        </p>
                        <p class="mt-1 text-sm text-slate-500">{{ $alumnoSeleccionado->tutor?->parentesco ?? '—' }}
                        </p>
                    </div>

                    <div
                        class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Certificado esperado</p>
                        <p class="mt-3 text-lg font-black text-slate-900 dark:text-white">
                            {{ $resumenSeleccionado['nivel_certificado_requerido']['nombre'] ?? 'No aplica todavía' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">El certificado acredita el nivel anterior, no reemplaza
                            los previos.</p>
                    </div>
                </div>

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
                    <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-indigo-50/70 p-5 dark:border-neutral-800 dark:from-neutral-900 dark:to-indigo-950/20">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-start gap-3">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm">
                                    <flux:icon name="academic-cap" class="size-5" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Asignación académica actual</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        La generación identifica al grupo escolar del alumno; los cambios quedan en la bitácora administrativa.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-black text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:ring-indigo-900">
                                    {{ $alumnoSeleccionado->cambiosAcademicos->count() }} cambios
                                </span>
                                <span class="rounded-full bg-sky-100 px-3 py-1.5 text-xs font-black text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900">
                                    {{ $alumnoSeleccionado->movimientos->count() }} movimientos
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Generación</p>
                                <p class="mt-2 font-black text-slate-900 dark:text-white">{{ $alumnoSeleccionado->generacion?->etiqueta ?? 'Sin generación' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $alumnoSeleccionado->generacion?->status ? 'Activa' : 'Inactiva' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Ubicación actual</p>
                                <p class="mt-2 font-black text-slate-900 dark:text-white">{{ $alumnoSeleccionado->nivel?->nombre ?? 'Sin nivel' }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $alumnoSeleccionado->grado?->nombre ?? 'Sin grado' }}
                                    @if ($alumnoSeleccionado->semestre) · Semestre {{ $alumnoSeleccionado->semestre->numero }} @endif
                                    · Grupo {{ $alumnoSeleccionado->grupo?->asignacionGrupo?->nombre ?? '—' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Estatus actual</p>
                                <p class="mt-2 font-black text-slate-900 dark:text-white">{{ \Illuminate\Support\Str::headline($alumnoSeleccionado->estatus ?: ($alumnoSeleccionado->activo ? 'activo' : 'inactivo')) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $alumnoSeleccionado->fecha_estatus?->format('d/m/Y H:i') ?? 'Sin fecha de cambio' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Ingreso al plantel</p>
                                <p class="mt-2 font-black text-slate-900 dark:text-white">{{ $alumnoSeleccionado->fecha_inscripcion?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Dato general del alumno</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
                            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-9 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                        <flux:icon name="identification" class="size-4" />
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 dark:text-white">Matrículas por nivel</h4>
                                        <p class="text-xs text-slate-500">Las anteriores se conservan como referencia administrativa.</p>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-2">
                                    @forelse ($alumnoSeleccionado->matriculasAlumno as $matriculaAlumno)
                                        <div class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800 dark:bg-neutral-950">
                                            <div>
                                                <p class="font-black text-slate-900 dark:text-white">{{ $matriculaAlumno->matricula }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $matriculaAlumno->nivel?->nombre ?? 'Nivel no especificado' }} · asignada {{ $matriculaAlumno->fecha_asignacion?->format('d/m/Y') ?? 'sin fecha' }}</p>
                                            </div>
                                            <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $matriculaAlumno->vigente ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-neutral-800 dark:text-slate-300' }}">
                                                {{ $matriculaAlumno->vigente ? 'Vigente' : 'Histórica' }}
                                            </span>
                                        </div>
                                    @empty
                                        <p class="rounded-xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500 dark:border-neutral-700">No hay matrículas por nivel registradas.</p>
                                    @endforelse
                                </div>
                            </article>

                            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-9 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                                        <flux:icon name="clock" class="size-4" />
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 dark:text-white">Bitácora de cambios</h4>
                                        <p class="text-xs text-slate-500">Generación, grado, semestre, grupo, estatus y correcciones.</p>
                                    </div>
                                </div>

                                <div class="mt-4 max-h-96 space-y-3 overflow-y-auto pr-1">
                                    @forelse ($alumnoSeleccionado->cambiosAcademicos as $cambio)
                                        <div wire:key="cambio-academico-{{ $cambio->id }}" class="relative rounded-xl border border-slate-200 bg-white p-3 pl-4 dark:border-neutral-800 dark:bg-neutral-950">
                                            <span class="absolute bottom-3 left-0 top-3 w-1 rounded-r-full bg-violet-500"></span>
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p class="font-black text-slate-900 dark:text-white">{{ \Illuminate\Support\Str::headline($cambio->tipo) }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">Generación {{ $cambio->generacion?->etiqueta ?? '—' }}</p>
                                                </div>
                                                <span class="shrink-0 text-xs font-bold text-slate-500">{{ $cambio->realizado_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</span>
                                            </div>
                                            @if ($cambio->motivo)
                                                <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-300"><strong>Motivo:</strong> {{ $cambio->motivo }}</p>
                                            @endif
                                            <p class="mt-2 text-[10px] font-bold uppercase tracking-wide text-slate-400">Registró: {{ $cambio->usuario?->name ?? 'Sistema' }}</p>
                                        </div>
                                    @empty
                                        <p class="rounded-xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500 dark:border-neutral-700">Todavía no hay cambios académicos registrados.</p>
                                    @endforelse
                                </div>
                            </article>
                        </div>
                    </div>
                </section>

                <div>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Control de documentos
                                esperados</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Son opcionales: el sistema solo
                                informa cuáles aún no están cargados.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($resumenSeleccionado['items'] as $item)
                            <article
                                id="documento-{{ $item['tipo_id'] }}-{{ $item['nivel_id'] ?? 0 }}-{{ $item['grado_id'] ?? 0 }}-{{ $item['ciclo_escolar_id'] ?? 0 }}"
                                data-document-type="{{ $item['tipo_id'] }}"
                                class="rounded-2xl border p-4 transition {{ $item['archivo_faltante'] ? 'border-rose-200 bg-rose-50/60 dark:border-rose-900/50 dark:bg-rose-950/10' : (($item['no_aplica'] ?? false) ? 'border-violet-200 bg-violet-50/60 dark:border-violet-900/50 dark:bg-violet-950/10' : ($item['presente'] ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/50 dark:bg-emerald-950/10' : 'border-amber-200 bg-amber-50/60 dark:border-amber-900/50 dark:bg-amber-950/10')) }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex size-10 shrink-0 items-center justify-center rounded-2xl {{ $item['archivo_faltante'] ? 'bg-rose-500 text-white' : (($item['no_aplica'] ?? false) ? 'bg-violet-500 text-white' : ($item['presente'] ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white')) }}">
                                            <flux:icon :name="$item['archivo_faltante'] ? 'exclamation-triangle' : (($item['no_aplica'] ?? false) ? 'minus-circle' : ($item['presente'] ? 'check' : 'clock'))" class="size-5" />
                                        </div>
                                        <div>
                                            <h4 class="font-black text-slate-900 dark:text-white">
                                                {{ $item['etiqueta'] }}</h4>
                                            <span
                                                class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $coloresEstado[$item['estado']] ?? $coloresEstado['pendiente'] }}">
                                                {{ ucfirst($item['estado']) }}
                                            </span>
                                            @if ($item['archivo_faltante'])
                                                <span class="ml-1 mt-2 inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-black uppercase text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900">
                                                    Archivo faltante
                                                </span>
                                            @endif
                                            @if (($item['no_aplica'] ?? false) && filled($item['motivo_no_aplica'] ?? null))
                                                <p class="mt-2 text-xs leading-5 text-violet-700 dark:text-violet-300">
                                                    <strong>Motivo:</strong> {{ $item['motivo_no_aplica'] }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if ($item['documento_id'] && ! $item['archivo_faltante'])
                                        <a href="{{ route('misrutas.expedientes.preview', $item['documento_id']) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            <flux:icon name="eye" class="size-3.5" />
                                            Ver archivo
                                        </a>
                                    @endif

                                    @if (!$soloHistorico)
                                        <button type="button"
                                            @click="openUpload(
                                                {{ $item['tipo_id'] }},
                                                {{ $item['nivel_id'] ?? 'null' }},
                                                {{ $item['grado_id'] ?? 'null' }},
                                                {{ $item['ciclo_escolar_id'] ?? 'null' }},
                                                'esperado-{{ $loop->index }}-{{ $item['tipo_id'] }}'
                                            )"
                                            :disabled="opening || closing"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-black text-white transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70">
                                            <span
                                                x-show="opening && openingKey === 'esperado-{{ $loop->index }}-{{ $item['tipo_id'] }}'"
                                                class="size-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                            <flux:icon
                                                x-show="!(opening && openingKey === 'esperado-{{ $loop->index }}-{{ $item['tipo_id'] }}')"
                                                name="arrow-up-tray" class="size-3.5" />
                                            {{ $item['presente'] && !($item['no_aplica'] ?? false) ? 'Nueva versión' : 'Subir' }}
                                        </button>

                                        @if ($item['no_aplica'] ?? false)
                                            <button type="button"
                                                wire:click="quitarNoAplica({{ $item['no_aplica_id'] }})"
                                                wire:confirm="¿Retirar la marca No aplica? El documento volverá a mostrarse como pendiente."
                                                class="inline-flex items-center gap-1.5 rounded-xl border border-violet-200 bg-white px-3 py-2 text-xs font-black text-violet-700 transition hover:bg-violet-50 dark:border-violet-900 dark:bg-neutral-900 dark:text-violet-300">
                                                <flux:icon name="arrow-uturn-left" class="size-3.5" />
                                                Quitar No aplica
                                            </button>
                                        @elseif (!$item['presente'])
                                            <button type="button"
                                                wire:click="abrirNoAplica({{ $item['tipo_id'] }}, {{ $item['nivel_id'] ?? 'null' }}, {{ $item['grado_id'] ?? 'null' }}, {{ $item['ciclo_escolar_id'] ?? 'null' }})"
                                                class="inline-flex items-center gap-1.5 rounded-xl border border-violet-200 bg-white px-3 py-2 text-xs font-black text-violet-700 transition hover:bg-violet-50 dark:border-violet-900 dark:bg-neutral-900 dark:text-violet-300">
                                                <flux:icon name="minus-circle" class="size-3.5" />
                                                No aplica
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-300">
                                <flux:icon name="document-duplicate" class="size-5" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white">Documentos generales actuales
                                </h3>
                                <p class="text-xs text-slate-500">Acta, registro, CURP, domicilio e INE del padre,
                                    madre o tutor.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @forelse ($generales as $documento)
                                <div wire:key="general-doc-{{ $documento->id }}"
                                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="font-black text-slate-900 dark:text-white">
                                                {{ $documento->tipoDocumento?->nombre }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Versión {{ $documento->version }} · {{ $documento->tamano_legible }} ·
                                                {{ $documento->created_at?->format('d/m/Y H:i') }}
                                            </p>
                                            @if ($documento->observaciones)
                                                <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-300">
                                                    {{ $documento->observaciones }}
                                                </p>
                                            @endif
                                        </div>
                                        <span
                                            class="inline-flex self-start rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $coloresEstado[$documento->estado] ?? $coloresEstado['pendiente'] }}">
                                            {{ ucfirst($documento->estado) }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <a href="{{ route('misrutas.expedientes.preview', $documento) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            <flux:icon name="eye" class="size-3.5" /> Ver
                                        </a>
                                        <a href="{{ route('misrutas.expedientes.download', $documento) }}"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            <flux:icon name="arrow-down-tray" class="size-3.5" /> Descargar
                                        </a>
                                        @include('livewire.documentacion.partials.acciones-documento-organizado', [
                                            'documento' => $documento,
                                            'soloHistorico' => $soloHistorico,
                                        ])
                                        @if (!$soloHistorico)
                                            <select
                                                wire:change="actualizarEstado({{ $documento->id }}, $event.target.value)"
                                                class="ml-auto rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                                @foreach (['pendiente', 'recibido', 'validado', 'rechazado', 'reemplazado'] as $estado)
                                                    <option value="{{ $estado }}" @selected($documento->estado === $estado)>
                                                        {{ ucfirst($estado) }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div
                                    class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-neutral-700">
                                    Todavía no hay documentos generales cargados.
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <flux:icon name="academic-cap" class="size-5" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white">Certificados</h3>
                                <p class="text-xs text-slate-500">Certificados de estudios por nivel y certificado
                                    de terminación del alumno.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @forelse ($certificados as $documento)
                                <div wire:key="certificado-doc-{{ $documento->id }}"
                                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="font-black text-slate-900 dark:text-white">
                                                @if ($documento->tipoDocumento?->slug === 'certificado-estudios')
                                                    Certificado de {{ $documento->nivel?->nombre ?? 'nivel no especificado' }}
                                                @else
                                                    {{ $documento->tipoDocumento?->nombre ?? 'Certificado de terminación' }}
                                                @endif
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Versión {{ $documento->version }} · {{ $documento->tamano_legible }} ·
                                                {{ $documento->created_at?->format('d/m/Y H:i') }}
                                            </p>
                                            @if ($documento->observaciones)
                                                <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-300">
                                                    {{ $documento->observaciones }}
                                                </p>
                                            @endif
                                        </div>
                                        <span
                                            class="inline-flex self-start rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $coloresEstado[$documento->estado] ?? $coloresEstado['pendiente'] }}">
                                            {{ ucfirst($documento->estado) }}
                                        </span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <a href="{{ route('misrutas.expedientes.preview', $documento) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            <flux:icon name="eye" class="size-3.5" /> Ver
                                        </a>
                                        <a href="{{ route('misrutas.expedientes.download', $documento) }}"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            <flux:icon name="arrow-down-tray" class="size-3.5" /> Descargar
                                        </a>
                                        @include('livewire.documentacion.partials.acciones-documento-organizado', [
                                            'documento' => $documento,
                                            'soloHistorico' => $soloHistorico,
                                        ])
                                        @if (!$soloHistorico)
                                            <select
                                                wire:change="actualizarEstado({{ $documento->id }}, $event.target.value)"
                                                class="ml-auto rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                                @foreach (['pendiente', 'recibido', 'validado', 'rechazado', 'reemplazado'] as $estado)
                                                    <option value="{{ $estado }}" @selected($documento->estado === $estado)>
                                                        {{ ucfirst($estado) }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div
                                    class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-neutral-700">
                                    Todavía no hay certificados cargados.
                                </div>
                            @endforelse
                        </div>
                    </section>
                </div>

                <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300">
                                <flux:icon name="folder-open" class="size-5" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white">Historial académico documental
                                </h3>
                                <p class="text-xs text-slate-500">Boletas finales, constancias de estudios y
                                    constancias de baja o traslado.</p>
                            </div>
                        </div>

                    </div>

                    @if (!$soloHistorico)
                        <div class="mb-5 grid grid-cols-1 gap-3 lg:grid-cols-2">
                            @foreach ([$tipoConstanciaEstudios, $tipoConstanciaBaja] as $tipoAccion)
                                @if ($tipoAccion)
                                    @php
                                        $esBajaAccion = $tipoAccion['slug'] === 'constancia-baja-traslado';
                                        $claveAccion = 'academico-' . $tipoAccion['id'];
                                    @endphp

                                    <article data-document-type="{{ $tipoAccion['id'] }}"
                                        class="rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-sky-50 p-4 dark:border-indigo-900/50 dark:from-indigo-950/20 dark:to-sky-950/20">
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm">
                                                <flux:icon
                                                    :name="$esBajaAccion ? 'arrow-right-start-on-rectangle' : 'document-text'"
                                                    class="size-5" />
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="font-black text-slate-900 dark:text-white">
                                                    {{ $tipoAccion['nombre'] }}
                                                </h4>
                                                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                                    {{ $esBajaAccion
                                                        ? 'Registra un archivo externo de baja o traslado sin eliminar el historial.'
                                                        : 'Adjunta una constancia antigua, externa o emitida fuera del flujo automático.' }}
                                                </p>
                                            </div>
                                        </div>

                                        <button type="button"
                                            @click="openUpload(
                                                {{ $tipoAccion['id'] }},
                                                {{ $nivelContextoId ?? 'null' }},
                                                {{ $gradoContextoId ?? 'null' }},
                                                {{ $cicloContextoId ?? 'null' }},
                                                '{{ $claveAccion }}'
                                            )"
                                            :disabled="opening || closing"
                                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black text-white transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70">
                                            <span x-show="opening && openingKey === '{{ $claveAccion }}'"
                                                class="size-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                            <flux:icon x-show="!(opening && openingKey === '{{ $claveAccion }}')"
                                                name="arrow-up-tray" class="size-4" />
                                            Subir {{ mb_strtolower($tipoAccion['nombre']) }}
                                        </button>
                                    </article>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        @forelse ($academicos as $documento)
                            @php
                                $cicloDocumento = $documento->cicloEscolar
                                    ? $documento->cicloEscolar->inicio_anio . '-' . $documento->cicloEscolar->fin_anio
                                    : null;
                            @endphp
                            <article wire:key="academico-doc-{{ $documento->id }}"
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black text-slate-900 dark:text-white">
                                            {{ $documento->tipoDocumento?->nombre }}
                                        </p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            {{ $documento->nivel?->nombre ?? 'Sin nivel' }}
                                            @if ($documento->grado)
                                                · {{ $documento->grado->nombre }}
                                            @endif
                                            @if ($documento->grupo?->asignacionGrupo?->nombre)
                                                · Grupo {{ $documento->grupo->asignacionGrupo->nombre }}
                                            @endif
                                            @if ($cicloDocumento)
                                                · {{ $cicloDocumento }}
                                            @endif
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ ucfirst($documento->origen ?? 'subido') }}
                                            @if ($documento->folio)
                                                · Folio {{ $documento->folio }}
                                            @endif
                                            @if ($documento->fecha_documento)
                                                · {{ $documento->fecha_documento->format('d/m/Y') }}
                                            @endif
                                        </p>
                                        @if ($documento->tipo_movimiento || $documento->motivo)
                                            <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-300">
                                                @if ($documento->tipo_movimiento)
                                                    <strong>{{ str_replace('_', ' ', ucfirst($documento->tipo_movimiento)) }}:</strong>
                                                @endif
                                                {{ $documento->motivo }}
                                            </p>
                                        @endif
                                    </div>
                                    <span
                                        class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $coloresEstado[$documento->estado] ?? $coloresEstado['pendiente'] }}">
                                        {{ ucfirst($documento->estado) }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <a href="{{ route('misrutas.expedientes.preview', $documento) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                        <flux:icon name="eye" class="size-3.5" /> Ver
                                    </a>
                                    <a href="{{ route('misrutas.expedientes.download', $documento) }}"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                        <flux:icon name="arrow-down-tray" class="size-3.5" /> Descargar
                                    </a>
                                    @include('livewire.documentacion.partials.acciones-documento-organizado', [
                                        'documento' => $documento,
                                        'soloHistorico' => $soloHistorico,
                                    ])
                                    @if (!$soloHistorico && !in_array($documento->estado, ['emitida', 'cancelada'], true))
                                        @php
                                            $esConstancia = in_array(
                                                $documento->tipoDocumento?->slug,
                                                ['constancia-estudios', 'constancia-baja-traslado'],
                                                true,
                                            );

                                            $estadosAcademicos = $esConstancia
                                                ? [
                                                    'pendiente',
                                                    'recibido',
                                                    'validado',
                                                    'rechazado',
                                                    'reemplazado',
                                                    'cancelada',
                                                ]
                                                : ['pendiente', 'recibido', 'validado', 'rechazado', 'reemplazado'];
                                        @endphp
                                        <select
                                            wire:change="actualizarEstado({{ $documento->id }}, $event.target.value)"
                                            class="ml-auto rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            @foreach ($estadosAcademicos as $estado)
                                                <option value="{{ $estado }}" @selected($documento->estado === $estado)>
                                                    {{ ucfirst($estado) }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div
                                class="lg:col-span-2 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-neutral-700">
                                Todavía no hay boletas, constancias de estudios ni constancias de baja o traslado.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-3xl border border-violet-200 bg-violet-50/40 p-5 dark:border-violet-900/50 dark:bg-violet-950/10">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">Archivos fuente originales</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Se conservan por separado como respaldo privado. Pueden contener uno o varios documentos combinados.
                            </p>
                        </div>
                        @if (!$soloHistorico && $fuentesSeleccionadas->isNotEmpty())
                            <button type="button" wire:click="abrirOrganizador"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-violet-600 px-4 py-2.5 text-sm font-black text-white transition hover:bg-violet-700">
                                <flux:icon name="squares-plus" class="size-4" />
                                Organizar fuentes
                            </button>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-2">
                        @forelse ($fuentesSeleccionadas as $fuente)
                            <article wire:key="fuente-expediente-{{ $fuente->id }}"
                                class="rounded-2xl border border-violet-100 bg-white p-4 dark:border-violet-900/50 dark:bg-neutral-950">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-black text-slate-900 dark:text-white">{{ $fuente->nombre_original }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ max((int) $fuente->paginas, 1) }} página(s) ·
                                            {{ number_format(((int) $fuente->tamano_bytes) / 1024, 1) }} KB ·
                                            {{ $fuente->created_at?->format('d/m/Y H:i') }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Subió: {{ $fuente->usuario?->name ?? 'Sistema' }}
                                            @if ($fuente->protegido) · Protegido @endif
                                        </p>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $fuente->estado === 'activo' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $fuente->estado }}
                                    </span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="{{ route('misrutas.expedientes.fuentes.download', $fuente) }}"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:text-slate-200">
                                        <flux:icon name="arrow-down-tray" class="size-3.5" /> Original
                                    </a>
                                    <a href="{{ route('misrutas.expedientes.fuentes.preview', $fuente) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:text-slate-200">
                                        <flux:icon name="eye" class="size-3.5" /> Vista previa
                                    </a>
                                    @if (!$soloHistorico && !$fuente->protegido)
                                        <button type="button" wire:click="abrirOrganizador({{ $fuente->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3 py-2 text-xs font-black text-white hover:bg-violet-700">
                                            <flux:icon name="squares-plus" class="size-3.5" /> Organizar
                                        </button>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="lg:col-span-2 rounded-2xl border border-dashed border-violet-200 bg-white/70 p-7 text-center text-sm text-slate-500 dark:border-violet-900 dark:bg-neutral-950">
                                Aún no hay archivos fuente registrados. Los documentos existentes se incorporarán al abrir el organizador.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-800">
                    <div
                        class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                        <h3 class="font-black text-slate-900 dark:text-white">Historial completo de versiones</h3>
                        <p class="text-sm text-slate-500">Ningún archivo se elimina; las versiones reemplazadas
                            permanecen disponibles.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                            <thead>
                                <tr class="text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    <th class="px-5 py-3">Documento</th>
                                    <th class="px-5 py-3">Versión</th>
                                    <th class="px-5 py-3">Estado</th>
                                    <th class="px-5 py-3">Carga</th>
                                    <th class="px-5 py-3 text-right">Archivo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                @forelse ($documentosSeleccionados as $documento)
                                    <tr wire:key="historial-doc-{{ $documento->id }}">
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-slate-900 dark:text-white">
                                                {{ $documento->tipoDocumento?->nombre }}
                                                @if ($documento->nivel)
                                                    <span class="font-normal text-slate-500">—
                                                        {{ $documento->nivel->nombre }}</span>
                                                @endif
                                            </p>
                                            <p class="mt-1 max-w-md truncate text-xs text-slate-500"
                                                title="{{ $documento->nombre_original }}">
                                                {{ $documento->nombre_original }}
                                            </p>
                                        </td>
                                        <td class="px-5 py-4 text-sm font-black text-slate-700 dark:text-slate-200">
                                            v{{ $documento->version }}</td>
                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $coloresEstado[$documento->estado] ?? $coloresEstado['pendiente'] }}">
                                                {{ ucfirst($documento->estado) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-xs text-slate-500">
                                            <p>{{ $documento->created_at?->format('d/m/Y H:i') }}</p>
                                            <p class="mt-1">
                                                {{ $documento->usuarioQueSubio?->name ?? 'Usuario no disponible' }}
                                            </p>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            @if ($documento->archivo_existe)
                                                <div class="inline-flex gap-2">
                                                    <a href="{{ route('misrutas.expedientes.preview', $documento) }}"
                                                        target="_blank"
                                                        class="inline-flex size-9 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-indigo-950/30"
                                                        title="Ver archivo">
                                                        <flux:icon name="eye" class="size-4" />
                                                    </a>
                                                    <a href="{{ route('misrutas.expedientes.download', $documento) }}"
                                                        class="inline-flex size-9 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-indigo-950/30"
                                                        title="Descargar archivo">
                                                        <flux:icon name="arrow-down-tray" class="size-4" />
                                                    </a>
                                                </div>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-xl bg-rose-50 px-3 py-2 text-[10px] font-black uppercase text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900">
                                                    <flux:icon name="exclamation-triangle" class="size-3.5" />
                                                    Faltante
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">No
                                            hay versiones registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                    <h3 class="font-black text-slate-900 dark:text-white">Generación y asignación actual</h3>
                    <p class="mt-1 text-sm text-slate-500">El alumno conserva una sola generación. La documentación permanece aunque cambie de grado, grupo o estatus.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 dark:border-neutral-700 dark:bg-neutral-950 dark:text-slate-300">
                            <span class="size-2 rounded-full bg-indigo-500"></span>
                            Generación {{ $alumnoSeleccionado->generacion?->etiqueta ?? 'sin asignar' }} ·
                            {{ $alumnoSeleccionado->nivel?->nombre ?? 'Sin nivel' }} ·
                            {{ $alumnoSeleccionado->grado?->nombre ?? 'Sin grado' }} ·
                            Grupo {{ $alumnoSeleccionado->grupo?->asignacionGrupo?->nombre ?? '—' }}
                        </span>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                    <h3 class="font-black text-slate-900 dark:text-white">Bajas, traslados y reingresos</h3>
                    <p class="mt-1 text-sm text-slate-500">Cada movimiento permanece en el expediente aunque el alumno
                        vuelva a activarse.</p>

                    <div class="mt-4 space-y-3">
                        @forelse ($alumnoSeleccionado->movimientos->sortByDesc(fn($movimiento) => ($movimiento->fecha?->format('Ymd') ?? '') . str_pad((string) $movimiento->id, 10, '0', STR_PAD_LEFT)) as $movimiento)
                            <div
                                class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $movimiento->tipo === 'reingreso' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' }}">
                                            {{ str_replace('_', ' ', ucfirst($movimiento->tipo)) }}
                                        </span>
                                        <span
                                            class="text-xs font-bold text-slate-500">{{ $movimiento->fecha?->format('d/m/Y') ?? 'Sin fecha' }}</span>
                                    </div>
                                    <p class="mt-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                                        {{ $movimiento->nivelNuevo?->nombre ?? $movimiento->nivelAnterior?->nombre ?? ($alumnoSeleccionado->nivel?->nombre ?? 'Sin nivel') }}
                                        ·
                                        {{ $alumnoSeleccionado->grado?->nombre ?? 'Sin grado' }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $movimiento->motivo ?: 'Sin motivo registrado' }}</p>
                                    @if ($movimiento->observaciones)
                                        <p class="mt-1 text-xs text-slate-500">{{ $movimiento->observaciones }}</p>
                                    @endif
                                </div>
                                <span class="text-xs text-slate-500">Registró:
                                    {{ $movimiento->usuario?->name ?? 'Usuario no disponible' }}</span>
                            </div>
                        @empty
                            <div
                                class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-neutral-700">
                                No hay bajas, traslados o reingresos registrados.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>

        <livewire:documentacion.organizador-paginas-expediente
            :inscripcion-id="$alumnoSeleccionado->id"
            :niveles="$niveles"
            :grados="$grados"
            :grupos="$grupos"
            :ciclos="$ciclosEscolares"
            :key="'organizador-expediente-' . $alumnoSeleccionado->id" />
    @endif

    @if ($mostrarNoAplica)
        <div class="fixed inset-0 z-[115] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm"
            wire:key="modal-no-aplica-{{ $no_aplica_tipo_id }}">
            <div class="w-full max-w-xl overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-neutral-900">
                <div class="bg-gradient-to-r from-violet-700 to-indigo-700 px-6 py-5 text-white">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-violet-100">Control documental</p>
                            <h3 class="mt-1 text-xl font-black">Marcar documento como No aplica</h3>
                            <p class="mt-1 text-sm text-violet-100">La justificación quedará registrada con usuario y fecha.</p>
                        </div>
                        <button type="button" wire:click="cerrarNoAplica"
                            class="flex size-10 items-center justify-center rounded-2xl bg-white/10 hover:bg-white/20">
                            <flux:icon name="x-mark" class="size-5" />
                        </button>
                    </div>
                </div>
                <div class="space-y-4 p-6">
                    <div class="rounded-2xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-800 dark:border-violet-900 dark:bg-violet-950/20 dark:text-violet-200">
                        Usa esta opción únicamente cuando el documento realmente no corresponda al alumno. No sustituye una carga pendiente.
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Motivo obligatorio</label>
                        <textarea wire:model="no_aplica_motivo" rows="4" maxlength="1000"
                            placeholder="Ejemplo: no existe tutor distinto de la madre; por ello el INE del tutor no corresponde."
                            class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white"></textarea>
                        @error('no_aplica_motivo')
                            <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-neutral-800">
                    <button type="button" wire:click="cerrarNoAplica"
                        class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-600 dark:border-neutral-700 dark:text-slate-300">Cancelar</button>
                    <button type="button" wire:click="guardarNoAplica" wire:loading.attr="disabled" wire:target="guardarNoAplica"
                        class="inline-flex items-center gap-2 rounded-2xl bg-violet-600 px-5 py-2.5 text-sm font-black text-white hover:bg-violet-700 disabled:opacity-60">
                        <span wire:loading wire:target="guardarNoAplica" class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        Guardar No aplica
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($mostrarCarga)
        @php
            $tipoSeleccionado = collect($tiposDocumentos)->firstWhere('id', $tipo_documento_id);
            $requiereNivel = (bool) ($tipoSeleccionado['requiere_nivel'] ?? false);
            $slugTipo = $tipoSeleccionado['slug'] ?? '';
            $esAcademico = in_array(
                $slugTipo,
                ['boleta-final-grado', 'constancia-estudios', 'constancia-baja-traslado', 'constancia-traslado-calificaciones'],
                true,
            );
            $esBajaTraslado = $slugTipo === 'constancia-baja-traslado';
            $nivelesDocumento =
                $slugTipo === 'boleta-final-grado'
                    ? collect($niveles)->filter(function ($nivel) {
                        $texto = strtolower(trim(($nivel['slug'] ?? '') . ' ' . ($nivel['nombre'] ?? '')));
                        return str_contains($texto, 'primaria') || str_contains($texto, 'secundaria');
                    })
                    : collect($niveles);
            $gradosFiltrados = collect($grados)->where('nivel_id', (int) $nivel_certificado_id);
            $gruposFiltrados = collect($grupos)
                ->where('nivel_id', (int) $nivel_certificado_id)
                ->where('grado_id', (int) $grado_documento_id);
        @endphp

        <div x-cloak x-show="true" x-transition.opacity
            class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/75 p-2 backdrop-blur-md sm:p-4"
            wire:key="modal-carga-documento-{{ $tipo_documento_id }}" @click.self="requestClose()">
            <div x-transition.scale.origin.center
                class="relative flex max-h-[96vh] w-full max-w-7xl flex-col overflow-hidden rounded-[30px] border border-white/15 bg-white shadow-2xl dark:bg-neutral-900">

                <div x-cloak x-show="closing" x-transition.opacity
                    class="absolute inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm">
                    <div
                        class="rounded-3xl border border-white/15 bg-slate-950/90 px-8 py-6 text-center text-white shadow-2xl">
                        <div
                            class="mx-auto size-11 animate-spin rounded-full border-4 border-white/20 border-t-sky-400">
                        </div>
                        <p class="mt-4 text-sm font-black">Cerrando formulario…</p>
                        <p class="mt-1 text-xs text-slate-300">Descartando la carga temporal de forma segura.</p>
                    </div>
                </div>

                <div
                    class="shrink-0 bg-gradient-to-r from-indigo-700 via-blue-700 to-sky-600 px-5 py-4 text-white sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-100">Nuevo archivo
                            </p>
                            <h3 class="mt-1 text-xl font-black sm:text-2xl">Subir documento</h3>
                            <p class="mt-1 text-sm text-blue-100">
                                Vista previa local · PDF o imagen · máximo {{ config('expedientes_organizador.max_upload_mb', 30) }} MB.
                            </p>
                        </div>

                        <button type="button" @click="requestClose()" :disabled="closing || saving"
                            class="inline-flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white transition hover:bg-white/20 disabled:opacity-50"
                            title="Cerrar formulario">
                            <flux:icon name="x-mark" class="size-5" />
                        </button>
                    </div>
                </div>

                <form class="flex min-h-0 flex-1 flex-col"
                    @submit.prevent="submitDocument({{ $modo_integracion === 'reemplazar' ? 'true' : 'false' }})">
                    <div class="grid min-h-0 flex-1 grid-cols-1 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                        <div
                            class="space-y-5 overflow-y-auto border-b border-slate-200 p-5 dark:border-neutral-800 sm:p-6 xl:border-b-0 xl:border-r">

                            <div
                                class="rounded-3xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-sky-50 p-4 dark:border-indigo-900/50 dark:from-indigo-950/30 dark:to-sky-950/20">
                                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-indigo-500">
                                    Documento seleccionado
                                </p>
                                <div class="mt-3 flex items-center gap-3">
                                    <div
                                        class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm">
                                        <flux:icon name="document-text" class="size-6" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-base font-black text-slate-900 dark:text-white">
                                            {{ $tipoSeleccionado['nombre'] ?? 'Documento no disponible' }}
                                        </p>
                                        <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">
                                            Alumno: {{ $this->nombreCompleto($alumnoSeleccionado) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-violet-200 bg-violet-50/50 p-4 dark:border-violet-900/50 dark:bg-violet-950/10">
                                <div class="flex items-start gap-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-violet-600 text-white">
                                        <flux:icon name="squares-plus" class="size-5" />
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 dark:text-white">Preparación para el organizador</h4>
                                        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                            El original se conserva. Después de cargarlo podrás asignar cada página, girarla y combinarla con otras fuentes.
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label class="cursor-pointer rounded-2xl border p-3 transition {{ $contenido_archivo === 'un_documento' ? 'border-violet-400 bg-white ring-2 ring-violet-100 dark:bg-neutral-900' : 'border-violet-100 bg-white/60 dark:border-violet-900 dark:bg-neutral-950' }}">
                                        <input type="radio" wire:model.live="contenido_archivo" value="un_documento" class="sr-only">
                                        <span class="block text-sm font-black text-slate-900 dark:text-white">Un solo documento</span>
                                        <span class="mt-1 block text-xs text-slate-500">Todas las páginas se asignan inicialmente al tipo seleccionado.</span>
                                    </label>
                                    <label class="cursor-pointer rounded-2xl border p-3 transition {{ $contenido_archivo === 'varios_documentos' ? 'border-violet-400 bg-white ring-2 ring-violet-100 dark:bg-neutral-900' : 'border-violet-100 bg-white/60 dark:border-violet-900 dark:bg-neutral-950' }}">
                                        <input type="radio" wire:model.live="contenido_archivo" value="varios_documentos" class="sr-only">
                                        <span class="block text-sm font-black text-slate-900 dark:text-white">Varios documentos combinados</span>
                                        <span class="mt-1 block text-xs text-slate-500">Las páginas quedan sin clasificar para repartirlas manualmente.</span>
                                    </label>
                                </div>

                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label class="cursor-pointer rounded-2xl border p-3 transition {{ $modo_integracion === 'agregar' ? 'border-indigo-400 bg-white ring-2 ring-indigo-100 dark:bg-neutral-900' : 'border-slate-200 bg-white/60 dark:border-neutral-700 dark:bg-neutral-950' }}">
                                        <input type="radio" wire:model.live="modo_integracion" value="agregar" class="sr-only">
                                        <span class="block text-sm font-black text-slate-900 dark:text-white">Agregar páginas</span>
                                        <span class="mt-1 block text-xs text-slate-500">Conserva las páginas actuales y añade las nuevas.</span>
                                    </label>
                                    <label class="cursor-pointer rounded-2xl border p-3 transition {{ $modo_integracion === 'reemplazar' ? 'border-rose-400 bg-white ring-2 ring-rose-100 dark:bg-neutral-900' : 'border-slate-200 bg-white/60 dark:border-neutral-700 dark:bg-neutral-950' }}">
                                        <input type="radio" wire:model.live="modo_integracion" value="reemplazar" class="sr-only">
                                        <span class="block text-sm font-black text-slate-900 dark:text-white">Reemplazar documento</span>
                                        <span class="mt-1 block text-xs text-slate-500">Retira del borrador las páginas del contexto actual; el historial se conserva.</span>
                                    </label>
                                </div>

                                <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-950">
                                    <input type="checkbox" wire:model="permitir_archivo_duplicado"
                                        class="mt-0.5 size-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                    <span>
                                        <span class="block text-sm font-black text-slate-800 dark:text-slate-100">Permitir archivo duplicado</span>
                                        <span class="mt-1 block text-xs text-slate-500">Úsalo solo cuando el mismo archivo deba conservarse otra vez de forma intencional.</span>
                                    </span>
                                </label>
                                @error('modo_integracion') <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                                @error('contenido_archivo') <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            @if ($requiereNivel)
                                <div>
                                    <label
                                        class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                        {{ $slugTipo === 'certificado-estudios' ? 'Nivel que acredita el certificado' : 'Nivel del documento' }}
                                    </label>
                                    <select wire:model.live="nivel_certificado_id"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                        <option value="">Selecciona el nivel</option>
                                        @foreach ($nivelesDocumento as $nivel)
                                            <option value="{{ $nivel['id'] }}">{{ $nivel['nombre'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('nivel_certificado_id')
                                        <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            @if ($esAcademico)
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                            Grado
                                        </label>
                                        <select wire:model.live="grado_documento_id"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                            <option value="">Selecciona el grado</option>
                                            @foreach ($gradosFiltrados as $grado)
                                                <option value="{{ $grado['id'] }}">{{ $grado['nombre'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('grado_documento_id')
                                            <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                            Grupo opcional
                                        </label>
                                        <select wire:model="grupo_documento_id"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                            <option value="">Sin grupo específico</option>
                                            @foreach ($gruposFiltrados as $grupo)
                                                <option value="{{ $grupo['id'] }}">{{ $grupo['nombre'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('grupo_documento_id')
                                            <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                            Ciclo escolar
                                        </label>
                                        <select wire:model.live="ciclo_escolar_documento_id"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                            <option value="">Selecciona el ciclo</option>
                                            @foreach ($ciclosEscolares as $ciclo)
                                                <option value="{{ $ciclo['id'] }}">{{ $ciclo['nombre'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('ciclo_escolar_documento_id')
                                            <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                            Fecha del documento
                                        </label>
                                        <input type="date" wire:model="fecha_documento"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                        @error('fecha_documento')
                                            <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                            Folio opcional
                                        </label>
                                        <input type="text" wire:model="folio_documento" maxlength="100"
                                            placeholder="Folio del documento"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                        @error('folio_documento')
                                            <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                            Origen
                                        </label>
                                        <select wire:model="origen_documento"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                            <option value="externo">Sistema externo / otra escuela</option>
                                            <option value="subido">Carga administrativa</option>
                                            <option value="digitalizado">Documento digitalizado</option>
                                        </select>
                                    </div>
                                </div>
                            @endif

                            @if ($esBajaTraslado)
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                            Tipo de movimiento
                                        </label>
                                        <select wire:model="tipo_movimiento_documento"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                            <option value="baja_definitiva">Baja definitiva</option>
                                            <option value="baja_temporal">Baja temporal</option>
                                            <option value="traslado">Traslado</option>
                                            <option value="cambio_interno_nivel">Cambio interno de nivel</option>
                                            <option value="cambio_grupo">Cambio de grupo</option>
                                            <option value="reingreso">Reingreso</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                            Motivo
                                        </label>
                                        <textarea wire:model="motivo_documento" rows="3" maxlength="2000" placeholder="Motivo libre del movimiento…"
                                            class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40"></textarea>
                                        @error('motivo_documento')
                                            <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                    Archivo del documento
                                </label>

                                <div @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                                    @drop.prevent="handleDrop($event)"
                                    :class="dragging
                                        ?
                                        'border-indigo-500 bg-indigo-50 ring-4 ring-indigo-100 dark:bg-indigo-950/30 dark:ring-indigo-950/50' :
                                        'border-slate-300 bg-slate-50 dark:border-neutral-700 dark:bg-neutral-950'"
                                    class="relative rounded-3xl border-2 border-dashed p-5 text-center transition">
                                    <input data-pdf-input type="file" accept="application/pdf,image/jpeg,image/png,image/webp,.pdf,.jpg,.jpeg,.png,.webp"
                                        class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                        :disabled="uploading || saving || closing" @change="handleInput($event)">

                                    <div class="pointer-events-none">
                                        <div
                                            class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                                            <flux:icon name="document-arrow-up" class="size-6" />
                                        </div>
                                        <p class="mt-3 text-sm font-black text-slate-800 dark:text-white"
                                            x-text="fileName || 'Arrastra el archivo aquí o haz clic para seleccionarlo'">
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            PDF, JPG, JPEG, PNG o WEBP · máximo {{ config('expedientes_organizador.max_upload_mb', 30) }} MB · validación inmediata
                                        </p>
                                        <p x-show="fileSize" class="mt-1 text-xs font-bold text-indigo-600"
                                            x-text="fileSize"></p>
                                    </div>
                                </div>

                                <div x-cloak x-show="uploading || uploadProgress > 0"
                                    class="mt-3 rounded-2xl border border-indigo-100 bg-indigo-50 p-3 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                                    <div
                                        class="flex items-center justify-between gap-3 text-xs font-black text-indigo-700 dark:text-indigo-300">
                                        <span x-text="uploading ? 'Cargando archivo…' : 'Archivo preparado'"></span>
                                        <span x-text="`${uploadProgress}%`"></span>
                                    </div>
                                    <div
                                        class="mt-2 h-2 overflow-hidden rounded-full bg-indigo-100 dark:bg-indigo-950">
                                        <div class="h-full rounded-full bg-indigo-600 transition-all duration-200"
                                            :style="`width: ${uploadProgress}%`"></div>
                                    </div>
                                </div>

                                <div x-cloak x-show="fileError"
                                    class="mt-3 rounded-2xl border border-rose-200 bg-rose-50 p-3 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300"
                                    x-text="fileError"></div>

                                @error('archivo')
                                    <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                    Observaciones opcionales
                                </label>
                                <textarea wire:model="observaciones" rows="3" maxlength="2000"
                                    placeholder="Ejemplo: documento entregado por el tutor…"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40"></textarea>
                                @error('observaciones')
                                    <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if ($reemplazaDocumentoActual)
                                <div
                                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                    Ya existe una versión actual de este documento. Al guardar, la versión anterior se
                                    conservará con el estado <strong>Reemplazada</strong>.
                                </div>
                            @elseif (in_array($slugTipo, ['constancia-estudios', 'constancia-baja-traslado'], true))
                                <div
                                    class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-xs leading-5 text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-200">
                                    Esta constancia se agregará como un documento histórico independiente y no
                                    reemplazará constancias anteriores.
                                </div>
                            @endif
                        </div>

                        <div
                            class="flex min-h-[420px] flex-col bg-slate-100 p-4 dark:bg-neutral-950 sm:p-5 xl:min-h-0">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                                        Vista previa
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Los PDF usan el visor del navegador y las imágenes se muestran directamente.
                                    </p>
                                </div>

                                <button x-cloak x-show="hasFile" type="button" @click="clearSelectedFile()"
                                    :disabled="uploading || saving"
                                    class="relative z-20 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:border-rose-200 hover:text-rose-600 disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                    <flux:icon name="trash" class="size-3.5" />
                                    Quitar
                                </button>
                            </div>

                            <div
                                class="relative min-h-[340px] flex-1 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-inner dark:border-neutral-800 dark:bg-neutral-900">
                                <template x-if="previewUrl && previewKind === 'pdf'">
                                    <iframe :src="previewUrl" title="Vista previa del PDF"
                                        class="h-full min-h-[560px] w-full bg-white xl:min-h-full"></iframe>
                                </template>

                                <template x-if="previewUrl && previewKind === 'image'">
                                    <div class="flex h-full min-h-[560px] items-center justify-center bg-slate-100 p-5 dark:bg-neutral-950 xl:min-h-full">
                                        <img :src="previewUrl" alt="Vista previa de la imagen"
                                            class="max-h-[70vh] max-w-full rounded-2xl object-contain shadow-lg">
                                    </div>
                                </template>

                                <div x-show="!previewUrl"
                                    class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center">
                                    <div
                                        class="flex size-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                                        <flux:icon name="document-magnifying-glass" class="size-8" />
                                    </div>
                                    <p class="mt-4 text-sm font-black text-slate-700 dark:text-slate-200">
                                        La vista previa aparecerá aquí
                                    </p>
                                    <p class="mt-1 max-w-sm text-xs leading-5 text-slate-500">
                                        El archivo se muestra localmente en el navegador antes de terminar la carga.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="sticky bottom-0 z-30 flex shrink-0 flex-col-reverse gap-2 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur dark:border-neutral-800 dark:bg-neutral-900/95 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <p class="text-xs text-slate-500">
                            Ningún archivo previo será eliminado físicamente.
                        </p>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row">
                            <button type="button" @click="requestClose()" :disabled="saving || closing"
                                class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-600 transition hover:bg-slate-50 disabled:opacity-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800">
                                Cancelar
                            </button>

                            <button type="submit" :disabled="saving || uploading || !uploadReady || closing"
                                class="inline-flex min-w-[190px] items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-show="saving"
                                    class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                <flux:icon x-show="!saving" name="arrow-up-tray" class="size-4" />
                                <span x-text="saving ? 'Preparando…' : 'Guardar y organizar'"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        window.expedienteUploader = window.expedienteUploader || function() {
            return {
                opening: false,
                openingKey: null,
                openingStudentFile: false,
                closing: false,
                saving: false,
                uploading: false,
                uploadProgress: 0,
                uploadReady: false,
                previewUrl: null,
                previewKind: null,
                fileName: '',
                fileSize: '',
                fileError: '',
                hasFile: false,
                dragging: false,
                serverUploadName: null,

                wait(milliseconds) {
                    return new Promise(resolve => window.setTimeout(resolve, milliseconds));
                },

                async openStudentFile(alumnoId) {
                    if (this.openingStudentFile || this.opening || this.closing || this.saving) {
                        return;
                    }

                    this.openingStudentFile = true;

                    try {
                        await this.$wire.verExpediente(alumnoId);
                        await this.$nextTick();

                        window.requestAnimationFrame(() => {
                            window.requestAnimationFrame(() => {
                                const target = document.getElementById('expediente-seleccionado');

                                if (!target) {
                                    return;
                                }

                                target.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start',
                                });

                                target.animate(
                                    [{
                                            boxShadow: '0 0 0 0 rgba(79, 70, 229, 0)'
                                        },
                                        {
                                            boxShadow: '0 0 0 6px rgba(79, 70, 229, .22)'
                                        },
                                        {
                                            boxShadow: '0 0 0 0 rgba(79, 70, 229, 0)'
                                        },
                                    ], {
                                        duration: 900,
                                        easing: 'ease-out',
                                    },
                                );
                            });
                        });
                    } catch (error) {
                        this.showError('No fue posible abrir el expediente del alumno.');
                    } finally {
                        this.openingStudentFile = false;
                    }
                },

                async openUpload(tipoId, nivelId = null, gradoId = null, cicloId = null, key = null) {
                    if (this.opening || this.closing || this.saving) {
                        return;
                    }

                    this.opening = true;
                    this.openingKey = key;
                    const startedAt = performance.now();

                    try {
                        await this.$wire.abrirCarga(tipoId, nivelId, gradoId, cicloId);
                    } catch (error) {
                        this.showError('No fue posible preparar el formulario de carga.');
                    } finally {
                        const elapsed = performance.now() - startedAt;
                        await this.wait(Math.max(0, 300 - elapsed));
                        this.opening = false;
                        this.openingKey = null;
                    }
                },

                async requestClose() {
                    if (this.closing || this.saving) {
                        return;
                    }

                    if (this.hasFile) {
                        const confirmed = await this.confirm({
                            title: '¿Cerrar sin guardar?',
                            text: 'El archivo seleccionado y los cambios del formulario se descartarán.',
                            confirmText: 'Sí, cerrar',
                            icon: 'warning',
                        });

                        if (!confirmed) {
                            return;
                        }
                    }

                    this.closing = true;

                    try {
                        await this.wait(250);
                        await this.removeTemporaryUpload();
                        this.revokePreview();
                        await this.$wire.cerrarCarga();
                    } finally {
                        this.closing = false;
                        this.resetLocalFileState();
                    }
                },

                async handleInput(event) {
                    const file = event.target.files?.[0] ?? null;
                    await this.prepareFile(file);
                },

                async handleDrop(event) {
                    this.dragging = false;
                    const file = event.dataTransfer?.files?.[0] ?? null;
                    await this.prepareFile(file);
                },

                async prepareFile(file) {
                    this.fileError = '';

                    if (!file) {
                        return;
                    }

                    const maxBytes = {{ (int) config('expedientes_organizador.max_upload_mb', 30) }} * 1024 * 1024;
                    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
                    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
                    const allowedMimes = [
                        '',
                        'application/pdf',
                        'application/x-pdf',
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                    ];

                    if (!allowedExtensions.includes(extension) || !allowedMimes.includes(file.type)) {
                        await this.rejectFile('Selecciona un archivo PDF, JPG, JPEG, PNG o WEBP válido.');
                        return;
                    }

                    if (file.size > maxBytes) {
                        await this.rejectFile('El archivo supera el límite de {{ (int) config('expedientes_organizador.max_upload_mb', 30) }} MB.');
                        return;
                    }

                    const previewKind = extension === 'pdf' ? 'pdf' : 'image';

                    try {
                        const bytes = new Uint8Array(await file.slice(0, 16).arrayBuffer());
                        const isPdf = bytes.length >= 5 && String.fromCharCode(...bytes.slice(0, 5)) === '%PDF-';
                        const isJpeg = bytes.length >= 3 && bytes[0] === 0xff && bytes[1] === 0xd8 && bytes[2] === 0xff;
                        const isPng = bytes.length >= 8 && [0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]
                            .every((value, index) => bytes[index] === value);
                        const isWebp = bytes.length >= 12
                            && String.fromCharCode(...bytes.slice(0, 4)) === 'RIFF'
                            && String.fromCharCode(...bytes.slice(8, 12)) === 'WEBP';
                        const signatureValid = extension === 'pdf'
                            ? isPdf
                            : (['jpg', 'jpeg'].includes(extension) ? isJpeg : (extension === 'png' ? isPng : isWebp));

                        if (!signatureValid) {
                            await this.rejectFile('La firma interna del archivo no coincide con su extensión.');
                            return;
                        }
                    } catch (error) {
                        await this.rejectFile('No fue posible validar el archivo seleccionado.');
                        return;
                    }

                    await this.removeTemporaryUpload();
                    this.revokePreview();

                    this.previewKind = previewKind;
                    this.previewUrl = URL.createObjectURL(file);
                    this.fileName = file.name;
                    this.fileSize = this.formatBytes(file.size);
                    this.hasFile = true;
                    this.uploadReady = false;
                    this.uploading = true;
                    this.uploadProgress = 0;

                    await new Promise((resolve, reject) => {
                        this.$wire.upload(
                            'archivo',
                            file,
                            uploadedFilename => {
                                this.serverUploadName = uploadedFilename;
                                this.uploadProgress = 100;
                                this.uploading = false;
                                this.uploadReady = true;
                                resolve(uploadedFilename);
                            },
                            () => {
                                this.uploading = false;
                                this.uploadReady = false;
                                this.fileError = 'No fue posible cargar temporalmente el archivo.';
                                reject(new Error(this.fileError));
                            },
                            event => {
                                this.uploadProgress = Number(event.detail?.progress ?? 0);
                            },
                        );
                    }).catch(() => {
                        this.showError(this.fileError || 'No fue posible cargar el archivo.');
                    });
                },

                async rejectFile(message) {
                    await this.removeTemporaryUpload();
                    this.revokePreview();
                    this.resetLocalFileState();
                    this.fileError = message;
                    this.resetInput();
                    this.showError(message);
                },

                async clearSelectedFile() {
                    if (this.uploading || this.saving) {
                        return;
                    }

                    await this.removeTemporaryUpload();
                    this.revokePreview();
                    this.resetLocalFileState();
                    this.resetInput();
                },

                async removeTemporaryUpload() {
                    const uploadedName = this.serverUploadName;
                    this.serverUploadName = null;

                    if (uploadedName) {
                        await new Promise(resolve => {
                            this.$wire.removeUpload('archivo', uploadedName, resolve, resolve);
                        });

                        return;
                    }

                    try {
                        await this.$wire.set('archivo', null, false);
                    } catch (error) {
                        // No hay carga temporal que retirar.
                    }
                },

                async submitDocument(replacesCurrent) {
                    if (this.saving || this.uploading || this.closing) {
                        return;
                    }

                    if (!this.uploadReady) {
                        this.fileError = 'Selecciona y espera a que termine de cargar un archivo válido.';
                        this.showError(this.fileError);
                        return;
                    }

                    if (replacesCurrent) {
                        const confirmed = await this.confirm({
                            title: '¿Guardar una nueva versión?',
                            text: 'La versión actual se conservará en el historial con el estado Reemplazada.',
                            confirmText: 'Sí, guardar versión',
                            icon: 'warning',
                        });

                        if (!confirmed) {
                            return;
                        }
                    }

                    this.saving = true;

                    try {
                        await this.$wire.subirDocumento();
                    } catch (error) {
                        this.showError('No fue posible guardar el documento.');
                    } finally {
                        this.saving = false;
                    }
                },

                onSaved(detail) {
                    this.revokePreview();
                    this.resetLocalFileState();
                    this.resetInput();

                    const data = Array.isArray(detail) ? detail[0] : detail;
                    const tipoId = Number(data?.tipoId ?? 0);
                    const nivelId = Number(data?.nivelId ?? 0);
                    const gradoId = Number(data?.gradoId ?? 0);
                    const cicloId = Number(data?.cicloId ?? 0);

                    window.setTimeout(() => {
                        const exactId = `documento-${tipoId}-${nivelId}-${gradoId}-${cicloId}`;
                        const target = document.getElementById(exactId) ||
                            document.querySelector(`[data-document-type="${tipoId}"]`);

                        if (!target) {
                            return;
                        }

                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });

                        target.animate(
                            [{
                                    boxShadow: '0 0 0 0 rgba(16, 185, 129, 0)'
                                },
                                {
                                    boxShadow: '0 0 0 6px rgba(16, 185, 129, .30)'
                                },
                                {
                                    boxShadow: '0 0 0 0 rgba(16, 185, 129, 0)'
                                },
                            ], {
                                duration: 1400,
                                easing: 'ease-out',
                            },
                        );
                    }, 180);
                },

                revokePreview() {
                    if (this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                        this.previewUrl = null;
                    }

                    this.previewKind = null;
                },

                resetLocalFileState() {
                    this.fileName = '';
                    this.fileSize = '';
                    this.fileError = '';
                    this.hasFile = false;
                    this.uploading = false;
                    this.uploadReady = false;
                    this.uploadProgress = 0;
                    this.dragging = false;
                    this.serverUploadName = null;
                    this.previewKind = null;
                },

                resetInput() {
                    const input = this.$root.querySelector('[data-pdf-input]');

                    if (input) {
                        input.value = '';
                    }
                },

                formatBytes(bytes) {
                    if (!Number.isFinite(bytes) || bytes <= 0) {
                        return '';
                    }

                    const units = ['B', 'KB', 'MB'];
                    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
                    const value = bytes / Math.pow(1024, index);

                    return `${value.toFixed(index === 0 ? 0 : 2)} ${units[index]}`;
                },

                async confirm({
                    title,
                    text,
                    confirmText,
                    icon = 'warning'
                }) {
                    if (window.Swal) {
                        const result = await Swal.fire({
                            title,
                            text,
                            icon,
                            showCancelButton: true,
                            confirmButtonText: confirmText,
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true,
                            focusCancel: true,
                        });

                        return result.isConfirmed;
                    }

                    return window.confirm(`${title}\n\n${text}`);
                },

                showError(message) {
                    if (window.Swal) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: message,
                            showConfirmButton: false,
                            timer: 3200,
                            timerProgressBar: true,
                        });
                    }
                },
            };
        };

        (() => {
            const registerListeners = () => {
                if (window.__expedientesDigitalesListenersRegistered) {
                    return;
                }

                window.__expedientesDigitalesListenersRegistered = true;

                Livewire.on('notify', event => {
                    const detail = Array.isArray(event) ? event[0] : event;

                    if (window.Swal) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: detail?.type || 'success',
                            title: detail?.message || 'Operación realizada',
                            showConfirmButton: false,
                            timer: 2600,
                            timerProgressBar: true,
                        });
                    }
                });

                Livewire.on('documento-guardado', event => {
                    const detail = Array.isArray(event) ? event[0] : event;

                    window.dispatchEvent(new CustomEvent('expediente-documento-guardado', {
                        detail,
                    }));
                });
            };

            if (window.Livewire) {
                registerListeners();
            } else {
                document.addEventListener('livewire:init', registerListeners, {
                    once: true
                });
            }
        })();
    </script>
</div>
