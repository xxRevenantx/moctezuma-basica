@props([
    'titulo' => 'Estadística',
    'descripcion' => '',
    'filas' => collect(),
    'totales' => [],
    'bloques' => [],
    'acento' => 'from-sky-500 via-blue-600 to-indigo-600',
])

@php
    $sexosTabla = [
        'h' => 'Hombres',
        'm' => 'Mujeres',
        't' => 'Total',
    ];

    $estilosBloques = [
        'inicial' => [
            'head' => 'bg-slate-100 text-slate-700 dark:bg-neutral-800 dark:text-slate-200',
            'cell' => [
                'h' => 'bg-white text-slate-700 dark:bg-neutral-900 dark:text-slate-200',
                'm' => 'bg-white text-slate-700 dark:bg-neutral-900 dark:text-slate-200',
                't' => 'bg-slate-50 text-slate-900 dark:bg-neutral-800 dark:text-white',
            ],
            'total' => ['h' => 'bg-slate-800', 'm' => 'bg-slate-800', 't' => 'bg-slate-950'],
        ],
        'altas' => [
            'head' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300',
            'cell' => [
                'h' => 'bg-blue-50/50 text-blue-800 dark:bg-blue-950/10 dark:text-blue-300',
                'm' => 'bg-blue-50/50 text-blue-800 dark:bg-blue-950/10 dark:text-blue-300',
                't' => 'bg-blue-100/70 text-blue-900 dark:bg-blue-950/20 dark:text-blue-200',
            ],
            'total' => ['h' => 'bg-blue-700', 'm' => 'bg-blue-700', 't' => 'bg-blue-800'],
        ],
        'inscripcion_total' => [
            'head' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300',
            'cell' => [
                'h' => 'bg-indigo-50/50 text-indigo-800 dark:bg-indigo-950/10 dark:text-indigo-300',
                'm' => 'bg-indigo-50/50 text-indigo-800 dark:bg-indigo-950/10 dark:text-indigo-300',
                't' => 'bg-indigo-100/70 text-indigo-900 dark:bg-indigo-950/20 dark:text-indigo-200',
            ],
            'total' => ['h' => 'bg-indigo-700', 'm' => 'bg-indigo-700', 't' => 'bg-indigo-800'],
        ],
        'bajas' => [
            'head' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
            'cell' => [
                'h' => 'bg-rose-50/50 text-rose-800 dark:bg-rose-950/10 dark:text-rose-300',
                'm' => 'bg-rose-50/50 text-rose-800 dark:bg-rose-950/10 dark:text-rose-300',
                't' => 'bg-rose-100/70 text-rose-900 dark:bg-rose-950/20 dark:text-rose-200',
            ],
            'total' => ['h' => 'bg-rose-700', 'm' => 'bg-rose-700', 't' => 'bg-rose-800'],
        ],
        'existencia' => [
            'head' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
            'cell' => [
                'h' => 'bg-emerald-50/50 text-emerald-800 dark:bg-emerald-950/10 dark:text-emerald-300',
                'm' => 'bg-emerald-50/50 text-emerald-800 dark:bg-emerald-950/10 dark:text-emerald-300',
                't' => 'bg-emerald-100/70 text-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-200',
            ],
            'total' => ['h' => 'bg-emerald-700', 'm' => 'bg-emerald-700', 't' => 'bg-emerald-800'],
        ],
        'promovidos' => [
            'head' => 'bg-lime-50 text-lime-700 dark:bg-lime-950/30 dark:text-lime-300',
            'cell' => [
                'h' => 'bg-lime-50/70 text-lime-800 dark:bg-lime-950/10 dark:text-lime-300',
                'm' => 'bg-lime-50/70 text-lime-800 dark:bg-lime-950/10 dark:text-lime-300',
                't' => 'bg-lime-100 text-lime-900 dark:bg-lime-950/20 dark:text-lime-200',
            ],
            'total' => ['h' => 'bg-lime-700', 'm' => 'bg-lime-700', 't' => 'bg-lime-800'],
        ],
        'no_promovidos' => [
            'head' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
            'cell' => [
                'h' => 'bg-amber-50/70 text-amber-800 dark:bg-amber-950/10 dark:text-amber-300',
                'm' => 'bg-amber-50/70 text-amber-800 dark:bg-amber-950/10 dark:text-amber-300',
                't' => 'bg-amber-100 text-amber-900 dark:bg-amber-950/20 dark:text-amber-200',
            ],
            'total' => ['h' => 'bg-amber-700', 'm' => 'bg-amber-700', 't' => 'bg-amber-800'],
        ],
    ];
@endphp

<div
    class="overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <div class="h-1.5 w-full bg-gradient-to-r {{ $acento }}"></div>

    <div class="space-y-4 p-4 sm:p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                    Corte estadístico
                </p>

                <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                    {{ $titulo }}
                </h3>

                @if ($descripcion)
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ $descripcion }}
                    </p>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-neutral-800">
            <table class="min-w-[1120px] w-full border-collapse text-center text-sm">
                <thead>
                    <tr>
                        <th rowspan="2"
                            class="sticky left-0 z-20 border border-slate-300 bg-slate-900 px-4 py-3 text-xs font-black uppercase tracking-wide text-white dark:border-neutral-700">
                            Grado
                        </th>

                        @foreach ($bloques as $claveBloque => $textoBloque)
                            @php
                                $estilo = $estilosBloques[$claveBloque] ?? $estilosBloques['inicial'];
                            @endphp
                            <th colspan="3"
                                class="border border-slate-300 px-3 py-2 text-xs font-black uppercase tracking-wide dark:border-neutral-700 {{ $estilo['head'] }}">
                                {{ $textoBloque }}
                            </th>
                        @endforeach
                    </tr>

                    <tr>
                        @foreach ($bloques as $claveBloque => $textoBloque)
                            @foreach (['H', 'M', 'T'] as $sexo)
                                <th
                                    class="border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                    {{ $sexo }}
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @forelse ($filas as $fila)
                        <tr class="transition hover:bg-sky-50/60 dark:hover:bg-neutral-800/70">
                            <td
                                class="sticky left-0 z-10 border border-slate-300 bg-white px-4 py-3 text-base font-black text-slate-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white">
                                {{ $fila['grado'] }}°
                            </td>

                            @foreach ($bloques as $claveBloque => $textoBloque)
                                @foreach ($sexosTabla as $claveSexo => $textoSexo)
                                    @php
                                        $estilo = $estilosBloques[$claveBloque] ?? $estilosBloques['inicial'];
                                        $valor = $fila[$claveBloque][$claveSexo] ?? 0;
                                        $nombres = $fila[$claveBloque]['nombres_' . $claveSexo] ?? [];
                                        $tituloTooltip = $textoBloque . ' · ' . $fila['grado'] . '° · ' . $textoSexo;
                                    @endphp

                                    <td
                                        class="border border-slate-300 px-3 py-3 font-bold dark:border-neutral-700 {{ $estilo['cell'][$claveSexo] }}">
                                        @if ($valor)
                                            <flux:tooltip toggleable interactive position="bottom" align="center"
                                                gap="8">
                                                <button type="button"
                                                    class="rounded-xl px-2 py-1 transition hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm dark:hover:bg-neutral-900/60">
                                                    {{ $valor }}
                                                </button>

                                                <flux:tooltip.content
                                                    class="w-80 max-w-[20rem] overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 text-slate-700 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                                    <div
                                                        class="bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 px-4 py-3 text-white">
                                                        <p
                                                            class="text-xs font-black uppercase tracking-[0.18em] text-white/80">
                                                            Detalle de alumnos
                                                        </p>

                                                        <p class="mt-1 text-sm font-black">
                                                            {{ $tituloTooltip }}
                                                        </p>
                                                    </div>

                                                    <div class="space-y-3 p-4">
                                                        <div
                                                            class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-neutral-800">
                                                            <span
                                                                class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                Total
                                                            </span>

                                                            <span
                                                                class="rounded-full bg-sky-100 px-3 py-1 text-sm font-black text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                                                {{ $valor }}
                                                            </span>
                                                        </div>

                                                        <div class="max-h-64 space-y-1 overflow-y-auto pr-1">
                                                            @forelse ($nombres as $alumno)
                                                                <div
                                                                    class="flex items-start gap-2 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                                    <span
                                                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-sky-50 text-[10px] font-black text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                                                        {{ $loop->iteration }}
                                                                    </span>

                                                                    <span class="text-left">{{ $alumno }}</span>
                                                                </div>
                                                            @empty
                                                                <div
                                                                    class="rounded-xl border border-dashed border-slate-200 p-3 text-center text-xs font-bold text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                                                                    No hay alumnos en esta categoría.
                                                                </div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </flux:tooltip.content>
                                            </flux:tooltip>
                                        @else
                                            <span>0</span>
                                        @endif
                                    </td>
                                @endforeach
                            @endforeach
                        </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 1 + count($bloques) * 3 }}"
                                    class="border border-slate-300 px-4 py-10 text-center dark:border-neutral-700">
                                    <p class="font-black text-slate-700 dark:text-slate-200">
                                        No hay grados registrados para este nivel.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        Cuando existan grados y alumnos inscritos, se mostrará la estadística.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr>
                            <td
                                class="sticky left-0 z-10 border border-slate-300 bg-slate-900 px-4 py-3 text-sm font-black uppercase tracking-wide text-white dark:border-neutral-700">
                                Total
                            </td>

                            @foreach ($bloques as $claveBloque => $textoBloque)
                                @foreach ($sexosTabla as $claveSexo => $textoSexo)
                                    @php
                                        $estilo = $estilosBloques[$claveBloque] ?? $estilosBloques['inicial'];
                                        $valor = $totales[$claveBloque][$claveSexo] ?? 0;
                                        $nombres = $totales[$claveBloque]['nombres_' . $claveSexo] ?? [];
                                        $tituloTooltip = $textoBloque . ' · Total general · ' . $textoSexo;
                                    @endphp

                                    <td
                                        class="border border-slate-300 px-3 py-3 font-black text-white dark:border-neutral-700 {{ $estilo['total'][$claveSexo] }}">
                                        @if ($valor)
                                            <flux:tooltip toggleable interactive position="top" align="center"
                                                gap="8">
                                                <button type="button"
                                                    class="rounded-xl px-2 py-1 transition hover:-translate-y-0.5 hover:bg-white/15 hover:shadow-sm">
                                                    {{ $valor }}
                                                </button>

                                                <flux:tooltip.content
                                                    class="w-80 max-w-[20rem] overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 text-slate-700 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                                    <div
                                                        class="bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 px-4 py-3 text-white">
                                                        <p
                                                            class="text-xs font-black uppercase tracking-[0.18em] text-white/80">
                                                            Detalle de alumnos
                                                        </p>

                                                        <p class="mt-1 text-sm font-black">
                                                            {{ $tituloTooltip }}
                                                        </p>
                                                    </div>

                                                    <div class="space-y-3 p-4">
                                                        <div
                                                            class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-neutral-800">
                                                            <span
                                                                class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                Total
                                                            </span>

                                                            <span
                                                                class="rounded-full bg-sky-100 px-3 py-1 text-sm font-black text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                                                {{ $valor }}
                                                            </span>
                                                        </div>

                                                        <div class="max-h-64 space-y-1 overflow-y-auto pr-1">
                                                            @forelse ($nombres as $alumno)
                                                                <div
                                                                    class="flex items-start gap-2 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-neutral-800">
                                                                    <span
                                                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-sky-50 text-[10px] font-black text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                                                        {{ $loop->iteration }}
                                                                    </span>

                                                                    <span class="text-left">{{ $alumno }}</span>
                                                                </div>
                                                            @empty
                                                                <div
                                                                    class="rounded-xl border border-dashed border-slate-200 p-3 text-center text-xs font-bold text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                                                                    No hay alumnos en esta categoría.
                                                                </div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </flux:tooltip.content>
                                            </flux:tooltip>
                                        @else
                                            <span>0</span>
                                        @endif
                                    </td>
                                @endforeach
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
