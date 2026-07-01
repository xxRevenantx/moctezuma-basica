@php
    $puedeDescargarPorGrado = $ciclo_escolar_id !== '' && $grado_id !== '';
    $esGradoTerminalSeleccionado = $puedeDescargarPorGrado
        && $grado_terminal_id !== null
        && (int) $grado_id === (int) $grado_terminal_id;
    $parametrosZipPreescolar = [
        'ciclo_escolar_id' => $ciclo_escolar_id,
        'grado_id' => $grado_id,
        'tipo_reconocimiento' => $tipo_reconocimiento,
        'periodo' => $tipo_reconocimiento === 'anual' ? 0 : $periodo,
        'fecha' => $fecha_pdf,
    ];

    if ($generacion_id !== '') {
        $parametrosZipPreescolar['generacion_id'] = $generacion_id;
    }

    $parametrosListaPreescolar = $parametrosZipPreescolar;

    if ($grupo_id !== '') {
        $parametrosListaPreescolar['grupo_id'] = $grupo_id;
    }
@endphp

<div class="space-y-6">
    <div
        class="overflow-hidden rounded-[1.8rem] border border-pink-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="bg-gradient-to-r from-pink-500 via-rose-500 to-fuchsia-600 px-6 py-5 text-white">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-white/80">
                Preescolar
            </p>

            <h2 class="mt-1 text-2xl font-black">
                Lugares Preescolar
            </h2>

            <p class="mt-2 max-w-4xl text-sm leading-6 text-white/85">
                Asigna manualmente reconocimientos por periodo o anuales. Cada grupo conserva sus propios lugares,
                se permiten empates y puedes descargar la lista institucional en PDF o Word.
            </p>
        </div>

        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-7">
            <flux:field>
                <flux:label>Ciclo escolar</flux:label>
                <flux:select wire:model.live="ciclo_escolar_id">
                    <flux:select.option value="">Selecciona ciclo</flux:select.option>
                    @foreach ($cicloEscolares as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">
                            {{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Generación</flux:label>
                <flux:select wire:model.live="generacion_id">
                    <flux:select.option value="">Todas</flux:select.option>
                    @foreach ($generaciones as $generacion)
                        <flux:select.option value="{{ $generacion->id }}">
                            {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grado</flux:label>
                <flux:select wire:model.live="grado_id">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($grados as $grado)
                        <flux:select.option value="{{ $grado->id }}">
                            {{ $grado->nombre }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grupo</flux:label>
                <flux:select wire:model.live="grupo_id">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($grupos as $grupo)
                        <flux:select.option value="{{ $grupo->id }}">
                            {{ $grupo->grado?->nombre ?? 'Grado' }} · Grupo
                            {{ $grupo->asignacionGrupo?->nombre ?? '—' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Opción</flux:label>
                <flux:select wire:model.live="tipo_reconocimiento">
                    <flux:select.option value="periodo">Por periodo</flux:select.option>
                    <flux:select.option value="anual">Fin de curso</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Fecha para documentos</flux:label>
                <flux:input type="date" wire:model.live="fecha_pdf" />
                <flux:error name="fecha_pdf" />
            </flux:field>

            @if ($tipo_reconocimiento === 'periodo')
                <flux:field>
                    <flux:label>Periodo</flux:label>
                    <flux:select wire:model.live="periodo">
                        <flux:select.option value="1">1er periodo</flux:select.option>
                        <flux:select.option value="2">2do periodo</flux:select.option>
                        <flux:select.option value="3">3er periodo</flux:select.option>
                    </flux:select>
                </flux:field>
            @else
                <div class="flex items-end">
                    <div
                        class="w-full rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                        Fin de curso · reconocimiento anual
                    </div>
                </div>
            @endif
        </div>

        <div class="flex flex-wrap gap-2 px-5 pb-5">
            <flux:button type="button" wire:click="limpiarFiltros" variant="outline" icon="arrow-path">
                Limpiar filtros
            </flux:button>

            @if ($puedeDescargarPorGrado)
                <flux:button href="{{ route('misrutas.lugares-preescolar.documentos.zip', array_merge([
                    'tipo' => 'reconocimientos',
                ], $parametrosZipPreescolar)) }}" target="_blank" variant="filled" icon="document-arrow-down">
                    Reconocimientos ZIP
                </flux:button>
            @else
                <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-slate-200 px-4 py-2 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-400">
                    <flux:icon.document-arrow-down class="h-4 w-4" />
                    Reconocimientos ZIP
                </span>
            @endif

            @if ($tipo_reconocimiento === 'anual' && $esGradoTerminalSeleccionado)
                <flux:button href="{{ route('misrutas.lugares-preescolar.documentos.zip', array_merge([
                    'tipo' => 'diplomas',
                ], $parametrosZipPreescolar)) }}" target="_blank" variant="primary" icon="academic-cap">
                    Diplomas ZIP
                </flux:button>
            @else
                <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-slate-200 px-4 py-2 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-400"
                    title="Disponible en Fin de curso y únicamente para tercer grado">
                    <flux:icon.academic-cap class="h-4 w-4" />
                    Diplomas ZIP
                </span>
            @endif

            @if ($puedeDescargarPorGrado)
                <a href="{{ route('generales.cuadro-honor', array_merge([
                    'slug_nivel' => $slug_nivel,
                    'formato' => 'pdf',
                ], $parametrosListaPreescolar)) }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2 text-sm font-black text-white shadow-sm transition hover:bg-sky-700">
                    <flux:icon.document-text class="h-4 w-4" />
                    Lista PDF
                </a>

                <a href="{{ route('generales.cuadro-honor', array_merge([
                    'slug_nivel' => $slug_nivel,
                    'formato' => 'word',
                ], $parametrosListaPreescolar)) }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black text-white shadow-sm transition hover:bg-emerald-700">
                    <flux:icon.document-arrow-down class="h-4 w-4" />
                    Lista Word
                </a>
            @else
                <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-slate-200 px-4 py-2 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-400"
                    title="Selecciona un grado">
                    <flux:icon.document-text class="h-4 w-4" />
                    Lista PDF
                </span>

                <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-slate-200 px-4 py-2 text-sm font-black text-slate-500 dark:bg-neutral-800 dark:text-slate-400"
                    title="Selecciona un grado">
                    <flux:icon.document-arrow-down class="h-4 w-4" />
                    Lista Word
                </span>
            @endif

        </div>
    </div>

    <div
        class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-neutral-950">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="min-w-[260px] px-4 py-3 text-left">Alumno</th>
                        <th class="min-w-[150px] px-4 py-3 text-left">Grado / Grupo</th>
                        <th class="min-w-[150px] px-4 py-3 text-left">Lugar</th>
                        <th class="min-w-[360px] px-4 py-3 text-left">Motivo</th>
                        <th class="px-4 py-3 text-center">Guardar</th>
                        <th class="px-4 py-3 text-center">Reconocimiento</th>

                        @if ($tipo_reconocimiento === 'anual')
                            <th class="px-4 py-3 text-center">Diploma</th>
                        @endif

                        <th class="px-4 py-3 text-center">Eliminar</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($alumnos as $alumno)
                        @php
                            $nombreAlumno = trim(
                                ($alumno->apellido_paterno ?? '') .
                                    ' ' .
                                    ($alumno->apellido_materno ?? '') .
                                    ' ' .
                                    ($alumno->nombre ?? ''),
                            );

                            $pdfUrl = $this->urlPdf($alumno->id);
                            $mostrarDiploma = $tipo_reconocimiento === 'anual';
                            $diplomaUrl = $mostrarDiploma
                                ? $this->urlDiploma($alumno->id, $alumno->grado_id)
                                : null;
                        @endphp

                        <tr class="hover:bg-pink-50/40 dark:hover:bg-white/5">
                            <td class="px-4 py-4 font-bold text-slate-900 dark:text-white">
                                {{ $loop->iteration }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="font-black text-slate-900 dark:text-white">
                                    {{ $nombreAlumno }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $alumno->matricula ?? 'Sin matrícula' }}
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-700 dark:text-slate-200">
                                    {{ $alumno->grado?->nombre ?? 'S/G' }}
                                </div>

                                <div class="text-xs text-slate-500">
                                    Grupo {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'S/G' }}
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <flux:select wire:model.live="lugares.{{ $alumno->id }}">
                                    <flux:select.option value="">Reconocimiento general</flux:select.option>
                                    <flux:select.option value="1">1er lugar</flux:select.option>
                                    <flux:select.option value="2">2do lugar</flux:select.option>
                                    <flux:select.option value="3">3er lugar</flux:select.option>
                                </flux:select>

                                <flux:error name="lugares.{{ $alumno->id }}" />
                            </td>

                            <td class="px-4 py-4">
                                <flux:textarea wire:model.live.debounce.500ms="motivos.{{ $alumno->id }}"
                                    rows="3" placeholder="Si se deja vacío, se generará un texto automático." />

                                <flux:error name="motivos.{{ $alumno->id }}" />
                            </td>

                            <td class="px-4 py-4 text-center">
                                <flux:button type="button" wire:click="guardarAlumno({{ $alumno->id }})"
                                    wire:loading.attr="disabled" wire:target="guardarAlumno({{ $alumno->id }})"
                                    variant="primary" size="sm" icon="check">
                                    Guardar
                                </flux:button>
                            </td>

                            <td class="px-4 py-4 text-center">
                                @if ($pdfUrl)
                                    <flux:button href="{{ $pdfUrl }}" target="_blank" variant="filled"
                                        size="sm" icon="document-text">
                                        Rec.
                                    </flux:button>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">
                                        Sin guardar
                                    </span>
                                @endif
                            </td>

                            @if ($tipo_reconocimiento === 'anual')
                                <td class="px-4 py-4 text-center">
                                    @if ($diplomaUrl)
                                        <flux:button href="{{ $diplomaUrl }}" target="_blank" variant="primary"
                                            size="sm" icon="academic-cap">
                                            Diploma
                                        </flux:button>
                                    @else
                                        <span class="text-xs font-semibold text-slate-400">
                                            No aplica
                                        </span>
                                    @endif
                                </td>
                            @endif

                            <td class="px-4 py-4 text-center">
                                <flux:button type="button" wire:click="eliminarAlumno({{ $alumno->id }})"
                                    wire:confirm="¿Seguro que deseas eliminar este reconocimiento?" variant="danger"
                                    size="sm" icon="trash">
                                    Eliminar
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tipo_reconocimiento === 'anual' ? 9 : 8 }}"
                                class="px-4 py-12 text-center text-slate-500">
                                No hay alumnos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
