<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Promedio de los tres periodos</title>
    <style>
        @page { margin: 16px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #0f172a; }
        h1 { margin: 0; text-align: center; font-size: 16px; color: #006492; }
        .subtitulo { text-align: center; font-size: 10px; font-weight: bold; margin: 4px 0 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 0.6px solid #475569; text-align: center; padding: 3px; }
        .amarillo { background: #fef9c3; font-weight: bold; }
        .verde { background: #dcfce7; color: #14532d; font-weight: bold; }
        .turno { width: 68px; background: #f8fafc; font-weight: bold; }
        .promedio { background: #ecfccb; color: #365314; font-weight: bold; }
        .escuela { background: #fde047; font-weight: bold; }
        .materia { height: 112px; padding: 0; vertical-align: bottom; }
        .materia span { display: inline-block; transform: rotate(-90deg); white-space: nowrap; width: 18px; margin-bottom: 42px; font-size: 6px; }
        .nota { background: #fefce8; text-align: left; font-weight: bold; font-size: 8px; }
        .prov { color: #b45309; font-size: 6px; display: block; }
        .separador { page-break-before: always; }
        .grupo-title { margin: 8px 0 4px; padding: 5px; background: #006492; color: white; font-weight: bold; }
        .detalle th { background: #334155; color: white; }
        .left { text-align: left; }
    </style>
</head>
<body>
    <h1>PROMEDIO DE LOS TRES PERIODOS POR MATERIA</h1>
    <div class="subtitulo">{{ mb_strtoupper($reporte['nivel']['nombre']) }} · CICLO ESCOLAR {{ $reporte['ciclo']['texto'] }}</div>

    <table>
        <thead>
            <tr>
                <th rowspan="4" class="amarillo turno">TURNO</th>
                <th colspan="{{ collect($reporte['bloques'])->sum(fn ($bloque) => count($bloque['materias']) + 1) }}" class="amarillo">CAMPOS FORMATIVOS</th>
            </tr>
            <tr>
                @foreach ($reporte['bloques'] as $bloque)
                    <th colspan="{{ count($bloque['materias']) + 1 }}" class="verde">{{ $bloque['titulo'] }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($reporte['bloques'] as $bloque)
                    @foreach ($bloque['campos'] as $campo)
                        <th colspan="{{ $campo['colspan'] }}" style="background: {{ $campo['color_fondo'] }}; color: {{ $campo['color_texto'] }}; font-weight: bold;">
                            {{ mb_strtoupper($campo['nombre']) }}
                        </th>
                    @endforeach
                    <th rowspan="2" class="promedio">PROM. GRAL.</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($reporte['bloques'] as $bloque)
                    @foreach ($bloque['materias'] as $materia)
                        <th class="materia" style="background: {{ $materia['campo_color_fondo'] }}; color: {{ $materia['campo_color_texto'] }};">
                            <span>{{ mb_strtoupper($materia['materia']) }}</span>
                        </th>
                    @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <th class="turno">MATUTINO</th>
                @foreach ($reporte['bloques'] as $bloque)
                    @foreach ($bloque['materias'] as $materia)
                        <td>
                            {{ $materia['promedio_metodo_a'] === null ? '—' : number_format($materia['promedio_metodo_a'], 1, '.', '') }}
                            @if ($materia['provisional'])
                                <span class="prov">PROV.</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="promedio">{{ $bloque['promedio_general'] === null ? '—' : number_format($bloque['promedio_general'], 1, '.', '') }}</td>
                @endforeach
            </tr>
            <tr class="escuela">
                <th class="left">PROM. GRAL. DE LA ESCUELA</th>
                @foreach ($reporte['bloques'] as $bloque)
                    @foreach ($bloque['materias'] as $materia)
                        <td>{{ $materia['promedio_metodo_b'] === null ? '—' : number_format($materia['promedio_metodo_b'], 1, '.', '') }}</td>
                    @endforeach
                    <td>{{ $bloque['promedio_general'] === null ? '—' : number_format($bloque['promedio_general'], 1, '.', '') }}</td>
                @endforeach
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ 1 + collect($reporte['bloques'])->sum(fn ($bloque) => count($bloque['materias']) + 1) }}" class="nota">{{ $reporte['nota'] }}</td>
            </tr>
        </tfoot>
    </table>

    @if (in_array($alcance, ['completo', 'grado', 'grupo'], true))
        <div class="separador"></div>
        <h1>DETALLE POR GRUPO</h1>
        @foreach ($reporte['grupos'] as $grupo)
            <div class="grupo-title">
                {{ $grupo['titulo'] }} · {{ $grupo['total_alumnos'] }} ALUMNOS · PROMEDIO {{ $grupo['promedio_general'] === null ? '—' : number_format($grupo['promedio_general'], 1, '.', '') }}
            </div>
            <table class="detalle">
                <thead>
                    <tr>
                        <th class="left">Materia</th>
                        <th class="left">Campo formativo</th>
                        <th>Método A</th>
                        <th>Método B</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grupo['materias'] as $materia)
                        <tr>
                            <td class="left">{{ $materia['materia'] }}</td>
                            <td class="left">{{ $materia['campo_formativo'] }}</td>
                            <td>{{ $materia['promedio_metodo_a'] === null ? '—' : number_format($materia['promedio_metodo_a'], 1, '.', '') }}</td>
                            <td>{{ $materia['promedio_metodo_b'] === null ? '—' : number_format($materia['promedio_metodo_b'], 1, '.', '') }}</td>
                            <td>{{ $materia['provisional'] ? 'Provisional' : 'Definitivo' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif
</body>
</html>
