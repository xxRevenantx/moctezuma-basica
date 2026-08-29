<section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <div class="relative overflow-hidden bg-gradient-to-r from-[#7b1738] via-rose-700 to-[#006492] px-6 py-6 text-white sm:px-8">
        <div class="absolute -right-10 -top-14 h-44 w-44 rounded-full bg-white/10 blur-2xl"></div>
        <div class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-black uppercase tracking-wide ring-1 ring-white/20">
                    <flux:icon.chart-bar-square class="h-4 w-4" />
                    Estadística escolar
                </div>
                <h2 class="mt-3 text-2xl font-black">Desglose SEP · Alumnado y grupos</h2>
                <p class="mt-2 max-w-3xl text-sm text-white/80">
                    Vista de apoyo para los formatos 911 de inicio de cursos. Considera altas y bajas hasta el corte estadístico y distribuye la matrícula por grado, sexo, edad y, cuando corresponde, nuevo ingreso o repetidores.
                </p>
            </div>

            @if (!empty($datos))
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur">
                        <p class="text-[10px] font-black uppercase text-white/70">Formato</p>
                        <p class="mt-1 text-lg font-black">{{ data_get($datos, 'contexto.codigo_formato') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur">
                        <p class="text-[10px] font-black uppercase text-white/70">Alumnos</p>
                        <p class="mt-1 text-lg font-black">{{ data_get($datos, 'resumen.contabilizados', 0) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur">
                        <p class="text-[10px] font-black uppercase text-white/70">Incidencias</p>
                        <p class="mt-1 text-lg font-black">{{ data_get($datos, 'resumen.incidencias', 0) }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-5 dark:border-neutral-800 dark:bg-neutral-900/70">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr_1fr_auto] xl:items-end">
            <flux:field>
                <flux:label>Ciclo escolar</flux:label>
                <flux:select wire:model.live="ciclo_escolar_id">
                    @foreach ($ciclosEscolares as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">
                            {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · actual' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Nivel</flux:label>
                <flux:select wire:model.live="nivel_id">
                    @foreach ($niveles as $nivel)
                        <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex flex-wrap gap-2">
                <flux:button type="button" variant="ghost" wire:click="actualizar" icon="arrow-path">
                    Actualizar
                </flux:button>

                @if ($ciclo_escolar_id && $nivel_id && empty($error))
                    <a href="{{ route('misrutas.alumnos.estadistica-911.reporte', ['formato' => 'pdf', 'ciclo_escolar_id' => $ciclo_escolar_id, 'nivel_id' => $nivel_id]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#006492] px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:brightness-110">
                        <flux:icon.arrow-down-tray class="h-4 w-4" />
                        PDF
                    </a>
                    <a href="{{ route('misrutas.alumnos.estadistica-911.reporte', ['formato' => 'excel', 'ciclo_escolar_id' => $ciclo_escolar_id, 'nivel_id' => $nivel_id]) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#88AC2E] px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:brightness-95">
                        <flux:icon.table-cells class="h-4 w-4" />
                        Excel
                    </a>
                @endif
            </div>
        </div>

        @if (!empty($datos))
            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-white px-3 py-1.5 font-bold text-slate-600 ring-1 ring-slate-200 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700">
                    {{ data_get($datos, 'contexto.nivel') }} · CCT {{ data_get($datos, 'contexto.cct') }}
                </span>
                <span class="rounded-full bg-white px-3 py-1.5 font-bold text-slate-600 ring-1 ring-slate-200 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700">
                    Ciclo {{ data_get($datos, 'contexto.ciclo') }}
                </span>
                <span class="rounded-full bg-amber-100 px-3 py-1.5 font-black text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                    Matrícula al {{ data_get($datos, 'contexto.fecha_corte_texto') }}
                </span>
                <span class="rounded-full bg-sky-100 px-3 py-1.5 font-black text-sky-800 dark:bg-sky-950/40 dark:text-sky-300">
                    Edad al {{ data_get($datos, 'contexto.fecha_edad_texto') }}
                </span>
            </div>
        @endif
    </div>

    <div wire:loading.delay class="border-b border-slate-200 bg-sky-50 px-5 py-3 text-sm font-bold text-sky-700 dark:border-neutral-800 dark:bg-sky-950/30 dark:text-sky-300">
        Calculando el desglose estadístico...
    </div>

    @if ($error)
        <div class="m-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-300">
            {{ $error }}
        </div>
    @elseif (!empty($datos))
        @php($slug911 = data_get($datos, 'contexto.nivel_slug'))
        @php($columnas911 = data_get($datos, 'columnas', []))
        @php($grupos911 = collect(data_get($datos, 'grupos.por_grado', []))->keyBy('grado_id'))

        <div class="px-5 py-5 sm:px-6">
            <div class="mb-4">
                <h3 class="text-base font-black text-slate-900 dark:text-white">
                    Alumnas y alumnos por grado, sexo{{ $slug911 === 'preescolar' ? '' : ', nuevo ingreso, repetidores' }} y edad
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Los totales se construyen desde <code>inscripcion_ciclos</code> al corte seleccionado. Las filas con datos incompletos o que caerían en un área sombreada del formato oficial se separan como incidencias y no se asignan artificialmente a una celda.
                </p>
                <p class="mt-2 inline-flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300">
                    <span class="inline-block h-4 w-7 rounded border border-slate-300 bg-slate-300/80 dark:border-neutral-700 dark:bg-neutral-700/80"></span>
                    Área sombreada del formato oficial: no se utiliza.
                </p>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-neutral-800">
                <table class="min-w-max w-full border-collapse text-xs">
                    <thead class="bg-[#7b1738] text-white">
                        <tr>
                            <th class="border border-white/20 px-3 py-3 text-left">Grado</th>
                            <th class="border border-white/20 px-3 py-3 text-left">Sexo</th>
                            @if ($slug911 !== 'preescolar')
                                <th class="border border-white/20 px-3 py-3 text-left">Condición</th>
                            @endif
                            @foreach ($columnas911 as $etiquetaEdad)
                                <th class="min-w-[76px] border border-white/20 px-2 py-3 text-center leading-tight">{{ $etiquetaEdad }}</th>
                            @endforeach
                            <th class="min-w-[70px] border border-white/20 bg-[#006492] px-2 py-3 text-center">Total</th>
                            @if ($slug911 === 'secundaria')
                                <th class="min-w-[70px] border border-white/20 bg-[#006492] px-2 py-3 text-center">Grupos</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-neutral-800 dark:bg-neutral-900">
                        @foreach (data_get($datos, 'grados', []) as $grado911)
                            @if ($slug911 === 'preescolar')
                                @foreach (['hombres' => 'Hombres', 'mujeres' => 'Mujeres', 'subtotal' => 'Subtotal'] as $claveFila => $etiquetaFila)
                                    <tr class="{{ $claveFila === 'subtotal' ? 'bg-slate-100 font-black dark:bg-neutral-800' : '' }}">
                                        <td class="border border-slate-200 px-3 py-2 font-black dark:border-neutral-800">{{ $grado911['grado'] }}°</td>
                                        <td class="border border-slate-200 px-3 py-2 dark:border-neutral-800">{{ $etiquetaFila }}</td>
                                        @foreach (array_keys($columnas911) as $claveEdad)
                                            @php($sombreada911 = in_array($claveEdad, data_get($grado911, 'sombreadas.'.($claveFila === 'subtotal' ? 'subtotal' : 'simple'), []), true))
                                            <td class="border border-slate-200 px-2 py-2 text-center font-bold dark:border-neutral-800 {{ $sombreada911 ? 'bg-slate-300/80 dark:bg-neutral-700/80' : '' }}">
                                                @unless ($sombreada911)
                                                    {{ data_get($grado911, "{$claveFila}.edades.{$claveEdad}", 0) ?: '' }}
                                                @endunless
                                            </td>
                                        @endforeach
                                        <td class="border border-slate-200 bg-sky-50 px-2 py-2 text-center font-black text-[#006492] dark:border-neutral-800 dark:bg-sky-950/20 dark:text-sky-300">
                                            {{ data_get($grado911, "{$claveFila}.total", 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                @foreach (['hombres' => 'Hombres', 'mujeres' => 'Mujeres'] as $sexoClave => $sexoEtiqueta)
                                    @foreach (['nuevo_ingreso' => 'Nuevo ingreso', 'repetidores' => 'Repetidores'] as $condicionClave => $condicionEtiqueta)
                                        <tr>
                                            <td class="border border-slate-200 px-3 py-2 font-black dark:border-neutral-800">{{ $grado911['grado'] }}°</td>
                                            <td class="border border-slate-200 px-3 py-2 dark:border-neutral-800">{{ $sexoEtiqueta }}</td>
                                            <td class="border border-slate-200 px-3 py-2 dark:border-neutral-800">{{ $condicionEtiqueta }}</td>
                                            @foreach (array_keys($columnas911) as $claveEdad)
                                                @php($sombreada911 = in_array($claveEdad, data_get($grado911, "sombreadas.{$condicionClave}", []), true))
                                                <td class="border border-slate-200 px-2 py-2 text-center font-bold dark:border-neutral-800 {{ $sombreada911 ? 'bg-slate-300/80 dark:bg-neutral-700/80' : '' }}">
                                                    @unless ($sombreada911)
                                                        {{ data_get($grado911, "{$sexoClave}.{$condicionClave}.edades.{$claveEdad}", 0) ?: '' }}
                                                    @endunless
                                                </td>
                                            @endforeach
                                            <td class="border border-slate-200 bg-sky-50 px-2 py-2 text-center font-black text-[#006492] dark:border-neutral-800 dark:bg-sky-950/20 dark:text-sky-300">
                                                {{ data_get($grado911, "{$sexoClave}.{$condicionClave}.total", 0) }}
                                            </td>
                                            @if ($slug911 === 'secundaria')
                                                <td class="border border-slate-200 px-2 py-2 text-center dark:border-neutral-800"></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endforeach
                                <tr class="bg-slate-100 font-black dark:bg-neutral-800">
                                    <td class="border border-slate-200 px-3 py-2 dark:border-neutral-800">{{ $grado911['grado'] }}°</td>
                                    <td class="border border-slate-200 px-3 py-2 dark:border-neutral-800" colspan="2">Subtotal</td>
                                    @foreach (array_keys($columnas911) as $claveEdad)
                                        @php($sombreada911 = in_array($claveEdad, data_get($grado911, 'sombreadas.subtotal', []), true))
                                        <td class="border border-slate-200 px-2 py-2 text-center dark:border-neutral-800 {{ $sombreada911 ? 'bg-slate-300/80 dark:bg-neutral-700/80' : '' }}">
                                            @unless ($sombreada911)
                                                {{ data_get($grado911, "subtotal.edades.{$claveEdad}", 0) ?: '' }}
                                            @endunless
                                        </td>
                                    @endforeach
                                    <td class="border border-slate-200 bg-sky-100 px-2 py-2 text-center text-[#006492] dark:border-neutral-800 dark:bg-sky-950/40 dark:text-sky-300">
                                        {{ data_get($grado911, 'subtotal.total', 0) }}
                                    </td>
                                    @if ($slug911 === 'secundaria')
                                        <td class="border border-slate-200 bg-sky-100 px-2 py-2 text-center text-[#006492] dark:border-neutral-800 dark:bg-sky-950/40 dark:text-sky-300">
                                            {{ data_get($grupos911->get($grado911['grado_id']), 'total', 0) }}
                                        </td>
                                    @endif
                                </tr>
                            @endif
                        @endforeach

                        {{-- Totales generales del formato --}}
                        @if ($slug911 === 'preescolar')
                            @foreach (['hombres' => 'Hombres', 'mujeres' => 'Mujeres', 'total' => 'Total'] as $claveTotal => $etiquetaTotal)
                                <tr class="bg-[#7b1738]/10 font-black dark:bg-[#7b1738]/20">
                                    <td class="border border-slate-200 px-3 py-2 text-[#7b1738] dark:border-neutral-800 dark:text-rose-300">Total</td>
                                    <td class="border border-slate-200 px-3 py-2 dark:border-neutral-800">{{ $etiquetaTotal }}</td>
                                    @foreach (array_keys($columnas911) as $claveEdad)
                                        <td class="border border-slate-200 px-2 py-2 text-center dark:border-neutral-800">
                                            {{ data_get($datos, "totales.{$claveTotal}.edades.{$claveEdad}", 0) ?: '' }}
                                        </td>
                                    @endforeach
                                    <td class="border border-slate-200 bg-[#006492] px-2 py-2 text-center text-white dark:border-neutral-800">
                                        {{ data_get($datos, "totales.{$claveTotal}.total", 0) }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            @foreach ([
                                ['sexo' => 'hombres', 'sexo_etiqueta' => 'Hombres', 'condicion' => 'nuevo_ingreso', 'condicion_etiqueta' => 'Nuevo ingreso'],
                                ['sexo' => 'hombres', 'sexo_etiqueta' => 'Hombres', 'condicion' => 'repetidores', 'condicion_etiqueta' => 'Repetidores'],
                                ['sexo' => 'mujeres', 'sexo_etiqueta' => 'Mujeres', 'condicion' => 'nuevo_ingreso', 'condicion_etiqueta' => 'Nuevo ingreso'],
                                ['sexo' => 'mujeres', 'sexo_etiqueta' => 'Mujeres', 'condicion' => 'repetidores', 'condicion_etiqueta' => 'Repetidores'],
                            ] as $filaTotal911)
                                <tr class="bg-[#7b1738]/10 font-black dark:bg-[#7b1738]/20">
                                    <td class="border border-slate-200 px-3 py-2 text-[#7b1738] dark:border-neutral-800 dark:text-rose-300">Total</td>
                                    <td class="border border-slate-200 px-3 py-2 dark:border-neutral-800">{{ $filaTotal911['sexo_etiqueta'] }}</td>
                                    <td class="border border-slate-200 px-3 py-2 dark:border-neutral-800">{{ $filaTotal911['condicion_etiqueta'] }}</td>
                                    @foreach (array_keys($columnas911) as $claveEdad)
                                        @php($sombreadaTotal911 = $filaTotal911['condicion'] === 'repetidores' && (($slug911 === 'primaria' && $claveEdad === 'menos_6') || ($slug911 === 'secundaria' && $claveEdad === 'menos_12')))
                                        <td class="border border-slate-200 px-2 py-2 text-center dark:border-neutral-800 {{ $sombreadaTotal911 ? 'bg-slate-300/80 dark:bg-neutral-700/80' : '' }}">
                                            @unless ($sombreadaTotal911)
                                                {{ data_get($datos, "totales.{$filaTotal911['sexo']}.{$filaTotal911['condicion']}.edades.{$claveEdad}", 0) ?: '' }}
                                            @endunless
                                        </td>
                                    @endforeach
                                    <td class="border border-slate-200 bg-sky-100 px-2 py-2 text-center text-[#006492] dark:border-neutral-800 dark:bg-sky-950/40 dark:text-sky-300">
                                        {{ data_get($datos, "totales.{$filaTotal911['sexo']}.{$filaTotal911['condicion']}.total", 0) }}
                                    </td>
                                    @if ($slug911 === 'secundaria')
                                        <td class="border border-slate-200 px-2 py-2 dark:border-neutral-800"></td>
                                    @endif
                                </tr>
                            @endforeach
                            <tr class="bg-[#7b1738] font-black text-white">
                                <td class="border border-white/20 px-3 py-2">Total</td>
                                <td class="border border-white/20 px-3 py-2" colspan="2">Total general</td>
                                @foreach (array_keys($columnas911) as $claveEdad)
                                    <td class="border border-white/20 px-2 py-2 text-center">{{ data_get($datos, "totales.total.edades.{$claveEdad}", 0) ?: '' }}</td>
                                @endforeach
                                <td class="border border-white/20 bg-[#006492] px-2 py-2 text-center">{{ data_get($datos, 'totales.total.total', 0) }}</td>
                                @if ($slug911 === 'secundaria')
                                    <td class="border border-white/20 bg-[#006492] px-2 py-2 text-center">{{ data_get($datos, 'grupos.total', 0) }}</td>
                                @endif
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                    <h4 class="text-sm font-black text-slate-900 dark:text-white">Grupos por grado</h4>
                    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach (data_get($datos, 'grupos.por_grado', []) as $grupo911)
                            <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200 dark:bg-neutral-900 dark:ring-neutral-800">
                                <p class="text-xs font-bold text-slate-500">{{ $grupo911['grado'] }}°</p>
                                <p class="text-xl font-black text-slate-900 dark:text-white">{{ $grupo911['total'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs font-black text-[#006492]">Total de grupos: {{ data_get($datos, 'grupos.total', 0) }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                    <h4 class="text-sm font-black text-slate-900 dark:text-white">Control de cortes 911</h4>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <dt class="text-slate-500">Matrícula vigente al 30 de septiembre</dt>
                        <dd class="text-right font-black text-slate-900 dark:text-white">{{ data_get($datos, 'resumen.historiales_al_corte', 0) }}</dd>
                        <dt class="text-slate-500">Fecha de referencia de edad</dt>
                        <dd class="text-right font-black text-slate-900 dark:text-white">{{ data_get($datos, 'contexto.fecha_edad_texto') }}</dd>
                        <dt class="text-slate-500">Contabilizados en matriz</dt>
                        <dd class="text-right font-black text-emerald-700 dark:text-emerald-300">{{ data_get($datos, 'resumen.contabilizados', 0) }}</dd>
                        <dt class="text-slate-500">Incidencias</dt>
                        <dd class="text-right font-black text-rose-700 dark:text-rose-300">{{ data_get($datos, 'resumen.incidencias', 0) }}</dd>
                    </dl>
                    @if (data_get($datos, 'contexto.criterio_repetidor'))
                        <p class="mt-3 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ data_get($datos, 'contexto.criterio_repetidor') }}</p>
                    @endif
                </div>
            </div>

            @if (!empty(data_get($datos, 'incidencias', [])))
                <details class="group mt-5 rounded-2xl border border-rose-200 bg-rose-50/60 dark:border-rose-900/40 dark:bg-rose-950/10">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-black text-rose-700 dark:text-rose-300">
                        <span class="inline-flex items-center gap-2">
                            <flux:icon.exclamation-triangle class="h-4 w-4" />
                            Revisar {{ count(data_get($datos, 'incidencias', [])) }} incidencia(s) antes de capturar la 911
                        </span>
                        <flux:icon.chevron-down class="h-4 w-4 transition group-open:rotate-180" />
                    </summary>
                    <div class="overflow-x-auto border-t border-rose-200 p-4 dark:border-rose-900/40">
                        <table class="min-w-full text-xs">
                            <thead><tr class="text-left text-rose-800 dark:text-rose-200"><th class="p-2">Matrícula</th><th class="p-2">Alumno</th><th class="p-2">Grado</th><th class="p-2">Motivo</th></tr></thead>
                            <tbody>
                                @foreach (data_get($datos, 'incidencias', []) as $incidencia)
                                    <tr class="border-t border-rose-100 dark:border-rose-900/30">
                                        <td class="p-2 font-bold">{{ $incidencia['matricula'] ?: '—' }}</td>
                                        <td class="p-2">{{ $incidencia['alumno'] }}</td>
                                        <td class="p-2">{{ $incidencia['grado'] ?: '—' }}</td>
                                        <td class="p-2">{{ $incidencia['motivo'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </div>
    @endif
</section>
