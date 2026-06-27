<section class="mt-8 overflow-hidden rounded-[1.75rem] border border-emerald-200 bg-white shadow-sm dark:border-emerald-900/50 dark:bg-neutral-900">
    <div class="h-1.5 bg-gradient-to-r from-[#006492] via-sky-400 to-[#88AC2E]"></div>

    <div class="border-b border-slate-200 p-5 dark:border-neutral-800 sm:p-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-emerald-700 dark:text-emerald-300">
                    Evaluación oficial de primaria
                </p>
                <h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">
                    Calificaciones por campo formativo
                </h3>
                <p class="mt-2 max-w-4xl text-sm text-slate-600 dark:text-slate-300">
                    La sugerencia se obtiene de las materias internas con el mismo peso. Se trunca a entero únicamente para proponer una calificación y el docente puede modificarla antes de confirmar.
                </p>
                @if ($periodo)
                    <p class="mt-2 text-xs font-bold text-sky-700 dark:text-sky-300">
                        {{ $periodo['descripcion'] }} · Periodo {{ $periodo['numero'] }}
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button type="button" wire:click="aplicarSugerencias" variant="ghost" icon="sparkles">
                    Copiar sugerencias
                </flux:button>
                <flux:button type="button" wire:click="guardar" wire:loading.attr="disabled" variant="primary" icon="check-circle">
                    Guardar oficiales
                </flux:button>
            </div>
        </div>
    </div>

    @error('calificacionesOficiales')
        <div class="m-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-200">
            {{ $message }}
        </div>
    @enderror

    <div class="overflow-x-auto">
        <table class="min-w-[1180px] w-full border-collapse text-sm">
            <thead>
                <tr class="bg-slate-950 text-white">
                    <th class="sticky left-0 z-20 min-w-[280px] border border-slate-700 bg-slate-950 px-4 py-3 text-left">Alumno</th>
                    @foreach ($campos as $campo)
                        <th colspan="2" class="border border-slate-700 px-4 py-3 text-center" style="background-color: {{ $campo['color_fondo'] }}; color: {{ $campo['color_texto'] }};">
                            {{ $campo['nombre'] }}
                        </th>
                    @endforeach
                </tr>
                <tr class="bg-slate-100 text-xs font-black uppercase text-slate-600 dark:bg-neutral-800 dark:text-slate-200">
                    <th class="sticky left-0 z-20 border border-slate-200 bg-slate-100 px-4 py-2 text-left dark:border-neutral-700 dark:bg-neutral-800">Matrícula</th>
                    @foreach ($campos as $campo)
                        <th class="border border-slate-200 px-3 py-2 text-center dark:border-neutral-700">Sugerencia</th>
                        <th class="border border-slate-200 px-3 py-2 text-center dark:border-neutral-700">Oficial</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($alumnos as $alumno)
                    <tr wire:key="campo-oficial-{{ $alumno['inscripcion_id'] }}" class="hover:bg-sky-50/50 dark:hover:bg-sky-950/10">
                        <td class="sticky left-0 z-10 border border-slate-200 bg-white px-4 py-3 dark:border-neutral-700 dark:bg-neutral-900">
                            <p class="font-black text-slate-900 dark:text-white">{{ $alumno['alumno'] }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $alumno['matricula'] }}</p>
                        </td>

                        @foreach ($campos as $campo)
                            @php($celda = $alumno['campos'][$campo['id']])
                            <td class="border border-slate-200 px-3 py-3 text-center dark:border-neutral-700">
                                <div class="font-black text-slate-800 dark:text-white">
                                    {{ $celda['sugerencia_entera'] ?? '—' }}
                                </div>
                                <div class="mt-1 text-[11px] font-bold text-slate-500">
                                    Preciso: {{ $celda['promedio_sugerido_texto'] }}
                                </div>
                                @if (count($celda['materias']) > 0)
                                    <div class="mt-2 text-left text-[10px] leading-4 text-slate-500">
                                        @foreach ($celda['materias'] as $materia)
                                            <div>{{ $materia['nombre'] }}: {{ number_format($materia['calificacion'], 1) }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="border border-slate-200 px-3 py-3 dark:border-neutral-700">
                                <input
                                    type="number"
                                    min="0"
                                    max="10"
                                    step="1"
                                    wire:model.blur="calificacionesOficiales.{{ $alumno['inscripcion_id'] }}.{{ $campo['id'] }}"
                                    class="mx-auto block w-20 rounded-xl border border-slate-300 bg-white px-3 py-2 text-center text-base font-black text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white"
                                >
                                @error('calificacionesOficiales.' . $alumno['inscripcion_id'] . '.' . $campo['id'])
                                    <p class="mt-1 text-[10px] font-bold text-red-600">{{ $message }}</p>
                                @enderror
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-14 text-center text-sm font-semibold text-slate-500">
                            No hay alumnos disponibles para este grupo y periodo.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-200 bg-amber-50 px-5 py-4 text-xs font-semibold text-amber-800 dark:border-neutral-800 dark:bg-amber-950/20 dark:text-amber-200">
        Las calificaciones internas por materia no se eliminan. La boleta oficial de primaria utiliza exclusivamente las calificaciones confirmadas de los cuatro campos formativos.
    </div>
</section>
