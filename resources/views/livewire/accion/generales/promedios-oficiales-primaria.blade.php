<section class="mt-8 overflow-hidden rounded-[1.75rem] border border-[#006492]/20 bg-white shadow-sm dark:border-sky-900/50 dark:bg-neutral-900">
    <div class="h-1.5 bg-gradient-to-r from-[#006492] via-sky-400 to-[#88AC2E]"></div>

    <div class="border-b border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-[#006492] dark:text-sky-300">Fuente oficial de primaria</p>
                <h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Promedios por campos formativos</h3>
                <p class="mt-2 max-w-4xl text-sm text-slate-600 dark:text-slate-300">
                    Cada periodo del campo usa la calificación oficial confirmada cuando existe; de lo contrario, se calcula automáticamente desde sus materias participantes. El promedio final de cada campo se trunca a un decimal; el promedio final de grado es la suma de esos cuatro valores oficiales dividida entre cuatro y solo es definitivo cuando todo está completo.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button type="button" wire:click="exportarExcel" wire:loading.attr="disabled" icon="document-arrow-down" variant="primary">
                    Exportar Excel
                </flux:button>
                <a href="{{ $this->pdfUrl }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-slate-200">
                    <flux:icon.document-text class="h-4 w-4" />
                    PDF concentrado
                </a>
            </div>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar">
                <option value="">Selecciona</option>
                @foreach ($ciclosEscolares as $ciclo)
                    <option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · Actual' : '' }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="generacion_id" label="Generación">
                <option value="">Todas</option>
                @foreach ($generaciones as $generacion)
                    <option value="{{ $generacion->id }}">{{ $generacion->anio_ingreso }}-{{ $generacion->anio_egreso }}{{ $generacion->status ? '' : ' · Cerrada' }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="grado_id" label="Grado">
                <option value="">Todos</option>
                @foreach ($grados as $grado)
                    <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="grupo_id" label="Grupo">
                <option value="">Todos</option>
                @foreach ($grupos as $grupo)
                    <option value="{{ $grupo->id }}">{{ $grupo->asignacionGrupo?->nombre ?? 'Sin grupo' }}</option>
                @endforeach
            </flux:select>

            <div class="flex items-end">
                <flux:button type="button" wire:click="limpiarFiltros" variant="ghost" icon="arrow-path" class="w-full">Limpiar</flux:button>
            </div>
        </div>
    </div>

    @error('promocion')
        <div class="m-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-200">{{ $message }}</div>
    @enderror

    @php($resumen = $this->reporte['resumen'])
    <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-5 sm:p-6">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <p class="text-xs font-black uppercase text-slate-500">Alumnos</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['total_alumnos'] }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
            <p class="text-xs font-black uppercase text-emerald-700">Completos</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['completos'] }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
            <p class="text-xs font-black uppercase text-amber-700">Pendientes</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['pendientes'] }}</p>
        </div>
        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900/50 dark:bg-sky-950/20">
            <p class="text-xs font-black uppercase text-sky-700">Promedio oficial</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['promedio_general'] }}</p>
        </div>
        <div class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900/50 dark:bg-violet-950/20">
            <p class="text-xs font-black uppercase text-violet-700">Promociones confirmadas</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $resumen['promovidos_confirmados'] }}</p>
        </div>
    </div>

    <div class="space-y-5 px-5 pb-6 sm:px-6">
        @forelse ($this->reporte['grupos'] as $grupo)
            <section class="overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-700">
                <div class="flex flex-col gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-700 dark:bg-neutral-800">
                    <div>
                        <h4 class="font-black text-slate-950 dark:text-white">{{ $grupo['titulo'] }}</h4>
                        <p class="text-xs font-semibold text-slate-500">{{ $grupo['total'] }} alumnos · {{ $grupo['completos'] }} completos</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-200 dark:bg-neutral-900 dark:text-sky-300 dark:ring-sky-900">Promedio {{ $grupo['promedio'] }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1450px] w-full border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-950 text-white">
                                <th rowspan="2" class="sticky left-0 z-20 min-w-[260px] border border-slate-700 bg-slate-950 px-4 py-3 text-left">Alumno</th>
                                @foreach ($this->reporte['campos'] as $campo)
                                    <th colspan="4" class="border border-slate-700 px-3 py-3 text-center" style="background-color: {{ $campo->color_fondo }}; color: {{ $campo->color_texto }};">{{ $campo->nombre }}</th>
                                @endforeach
                                <th rowspan="2" class="border border-slate-700 px-3 py-3 text-center">Promedio final de grado</th>
                                <th rowspan="2" class="border border-slate-700 px-3 py-3 text-center">Promoción</th>
                                <th rowspan="2" class="border border-slate-700 px-3 py-3 text-center">Boleta</th>
                            </tr>
                            <tr class="bg-slate-100 text-xs font-black uppercase text-slate-600 dark:bg-neutral-800 dark:text-slate-200">
                                @foreach ($this->reporte['campos'] as $campo)
                                    <th class="border border-slate-200 px-2 py-2 dark:border-neutral-700">P1</th>
                                    <th class="border border-slate-200 px-2 py-2 dark:border-neutral-700">P2</th>
                                    <th class="border border-slate-200 px-2 py-2 dark:border-neutral-700">P3</th>
                                    <th class="border border-slate-200 px-2 py-2 dark:border-neutral-700">Final</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grupo['alumnos'] as $alumno)
                                <tr wire:key="oficial-anual-{{ $alumno['inscripcion_id'] }}" class="hover:bg-sky-50/50 dark:hover:bg-sky-950/10">
                                    <td class="sticky left-0 z-10 border border-slate-200 bg-white px-4 py-3 dark:border-neutral-700 dark:bg-neutral-900">
                                        <p class="font-black text-slate-900 dark:text-white">{{ $alumno['alumno'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $alumno['matricula'] }}</p>
                                    </td>
                                    @foreach ($this->reporte['campos'] as $campo)
                                        @php($datosCampo = $alumno['campos'][$campo->id])
                                        <td class="border border-slate-200 px-2 py-3 text-center font-bold dark:border-neutral-700">{{ $datosCampo['periodos'][1] ?? '—' }}</td>
                                        <td class="border border-slate-200 px-2 py-3 text-center font-bold dark:border-neutral-700">{{ $datosCampo['periodos'][2] ?? '—' }}</td>
                                        <td class="border border-slate-200 px-2 py-3 text-center font-bold dark:border-neutral-700">{{ $datosCampo['periodos'][3] ?? '—' }}</td>
                                        <td class="border border-slate-200 px-2 py-3 text-center font-black dark:border-neutral-700">
                                            {{ $datosCampo['final'] }}
                                            @if (! $datosCampo['completo'] && $datosCampo['provisional_preciso'] !== null)
                                                <span class="block text-[9px] font-black text-amber-600">PROV. {{ $datosCampo['provisional'] }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="border border-slate-200 px-3 py-3 text-center dark:border-neutral-700">
                                        <span class="text-lg font-black text-sky-700 dark:text-sky-300">{{ $alumno['promedio_general'] }}</span>
                                        @if (! $alumno['completo'] && $alumno['promedio_provisional_preciso'] !== null)
                                            <span class="block text-[10px] font-black text-amber-600">PROVISIONAL {{ $alumno['promedio_provisional'] }}</span>
                                        @endif
                                    </td>
                                    <td class="border border-slate-200 px-3 py-3 dark:border-neutral-700">
                                        <div class="flex min-w-[220px] flex-col gap-2">
                                            <div class="text-center text-xs font-black">
                                                Sugerencia: {{ $alumno['promocion_sugerida'] === null ? 'Pendiente' : ($alumno['promocion_sugerida'] ? 'PROMOVIDA(O)' : 'NO PROMOVIDA(O)') }}
                                            </div>
                                            <div class="flex justify-center gap-2">
                                                <button type="button" wire:click="confirmarPromocion({{ $alumno['inscripcion_id'] }}, true)" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-black text-white">Promover</button>
                                                <button type="button" wire:click="confirmarPromocion({{ $alumno['inscripcion_id'] }}, false)" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-black text-white">No promover</button>
                                            </div>
                                            @if ($alumno['promocion_confirmada'] !== null)
                                                <div class="text-center text-[10px] font-black {{ $alumno['promocion_confirmada'] ? 'text-emerald-700' : 'text-rose-700' }}">
                                                    CONFIRMADA: {{ $alumno['promocion_confirmada'] ? 'PROMOVIDA(O)' : 'NO PROMOVIDA(O)' }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="border border-slate-200 px-3 py-3 text-center dark:border-neutral-700">
                                        <a href="{{ route('calificaciones.boleta-oficial-primaria', ['inscripcion' => $alumno['inscripcion_id'], 'ciclo_escolar_id' => $ciclo_escolar_id, 'grado_id' => $alumno['grado_id'], 'grupo_id' => $alumno['grupo_id'], 'generacion_id' => $alumno['generacion_id']]) }}" target="_blank" class="inline-flex items-center gap-1 rounded-xl bg-[#006492] px-3 py-2 text-xs font-black text-white">
                                            <flux:icon.document-text class="h-4 w-4" /> PDF
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 p-10 text-center text-sm font-semibold text-slate-500 dark:border-neutral-700">
                No hay calificaciones ni datos académicos suficientes para los filtros seleccionados.
            </div>
        @endforelse
    </div>
</section>
