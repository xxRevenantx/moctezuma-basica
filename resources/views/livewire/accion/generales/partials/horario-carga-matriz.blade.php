<div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 dark:border-neutral-800">
    <div class="flex items-center justify-between gap-3 bg-slate-50 px-4 py-3 dark:bg-neutral-950/50">
        <p class="text-sm font-black text-slate-900 dark:text-white">{{ $tipoMatriz }}: {{ $matriz['nombre'] }}</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-[1050px] w-full border-collapse text-xs">
            <thead>
                <tr class="bg-[#006492] text-white">
                    <th class="border border-white/20 px-3 py-2 text-center">HORA</th>
                    @foreach ($dias as $dia)
                        <th class="border border-white/20 px-3 py-2 text-center">{{ mb_strtoupper($dia->dia) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($matriz['filas'] as $fila)
                    @if ($fila['es_receso'])
                        <tr class="bg-lime-50 text-lime-800 dark:bg-lime-950/20 dark:text-lime-300">
                            <td class="border border-slate-200 px-3 py-2 text-center font-black dark:border-neutral-800">{{ $fila['hora'] }}</td>
                            <td colspan="{{ max(1, $dias->count()) }}" class="border border-slate-200 px-3 py-2 text-center font-black tracking-widest dark:border-neutral-800">RECESO</td>
                        </tr>
                    @else
                        <tr>
                            <td class="border border-slate-200 bg-slate-50 px-3 py-2 text-center font-black dark:border-neutral-800 dark:bg-neutral-950/50">{{ $fila['hora'] }}</td>
                            @foreach ($dias as $dia)
                                <td class="min-w-48 border border-slate-200 p-2 align-top dark:border-neutral-800">
                                    @forelse ($fila['celdas'][$dia->id] ?? [] as $item)
                                        <p class="font-black text-slate-900 dark:text-white">{{ $item['nombre'] }}</p>
                                        <p class="mt-0.5 text-[10px] text-slate-500">{{ $item['contexto'] }}</p>
                                    @empty
                                        <span class="text-slate-300">—</span>
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
