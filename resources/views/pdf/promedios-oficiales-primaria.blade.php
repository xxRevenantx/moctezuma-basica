<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 14px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 7px; color: #111827; }
        h1 { text-align: center; color: #006492; margin: 0 0 4px; font-size: 14px; }
        .sub { text-align: center; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 10px; }
        th, td { border: 0.7px solid #374151; padding: 3px; text-align: center; }
        th { font-weight: bold; }
        .alumno { text-align: left; width: 130px; }
        .grupo { background: #006492; color: white; font-size: 9px; text-align: left; }
        .final { background: #fff4a3; font-weight: bold; }
        .nota { font-size: 7px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>PROMEDIOS OFICIALES DE PRIMARIA POR CAMPOS FORMATIVOS</h1>
    <div class="sub">{{ $escuela?->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.' }} · Ciclo {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}</div>

    @foreach ($reporte['grupos'] as $grupo)
        <table>
            <tr><th colspan="{{ 4 + ($reporte['campos']->count() * 4) + 2 }}" class="grupo">{{ $grupo['titulo'] }} · Promedio {{ $grupo['promedio'] }}</th></tr>
            <tr>
                <th rowspan="2" class="alumno">ALUMNO</th>
                <th rowspan="2">MATRÍCULA</th>
                @foreach ($reporte['campos'] as $campo)
                    <th colspan="4" style="background: {{ $campo->color_fondo }}; color: {{ $campo->color_texto }};">{{ mb_strtoupper($campo->nombre) }}</th>
                @endforeach
                <th rowspan="2" class="final">PROMEDIO DE GRADO</th>
                <th rowspan="2">PROMOCIÓN</th>
            </tr>
            <tr>
                @foreach ($reporte['campos'] as $campo)
                    <th>P1</th><th>P2</th><th>P3</th><th>FINAL</th>
                @endforeach
            </tr>
            @foreach ($grupo['alumnos'] as $alumno)
                <tr>
                    <td class="alumno">{{ $alumno['alumno'] }}</td>
                    <td>{{ $alumno['matricula'] }}</td>
                    @foreach ($reporte['campos'] as $campo)
                        @php($datos = $alumno['campos'][$campo->id])
                        <td>{{ $datos['periodos'][1] ?? '—' }}</td>
                        <td>{{ $datos['periodos'][2] ?? '—' }}</td>
                        <td>{{ $datos['periodos'][3] ?? '—' }}</td>
                        <td class="final">{{ $datos['final'] }}</td>
                    @endforeach
                    <td class="final">{{ $alumno['promedio_general'] }}</td>
                    <td>{{ $alumno['promocion_confirmada'] === null ? 'PENDIENTE' : ($alumno['promocion_confirmada'] ? 'PROMOVIDA(O)' : 'NO PROMOVIDA(O)') }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

    <p class="nota">NOTA: El promedio final de grado es la suma de los cuatro promedios finales precisos de campo dividida entre cuatro. El truncamiento se aplica únicamente al resultado mostrado.</p>
</body>
</html>
