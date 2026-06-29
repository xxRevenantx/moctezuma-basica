<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 20px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10px; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { margin: 0; font-size: 17px; color: #006492; }
        .header p { margin: 3px 0; }
        .datos { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .datos td { border: 1px solid #374151; padding: 6px; }
        .etiqueta { background: #e8d49a; font-weight: bold; text-align: center; }
        .principal { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .principal th, .principal td { border: 1.3px solid #374151; padding: 7px 5px; text-align: center; }
        .principal th { background: #e8d49a; font-weight: bold; }
        .periodo { width: 120px; }
        .numero { font-size: 19px; }
        .final { font-weight: bold; font-size: 16px; background: #f8fafc; }
        .derecha { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .derecha td { border: 1.3px solid #374151; padding: 8px; }
        .marca { font-size: 18px; font-weight: bold; text-align: center; }
        .nota { margin-top: 10px; font-size: 9px; color: #4b5563; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BOLETA OFICIAL DE EVALUACIÓN · PRIMARIA</h1>
        <p><strong>{{ $escuela?->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.' }}</strong></p>
        <p>Ciclo escolar {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }} · CCT {{ $nivel->cct ?? '—' }}</p>
    </div>

    <table class="datos">
        <tr>
            <td class="etiqueta">ALUMNA(O)</td>
            <td>{{ $alumno['alumno'] }}</td>
            <td class="etiqueta">MATRÍCULA</td>
            <td>{{ $alumno['matricula'] }}</td>
            <td class="etiqueta">GRADO Y GRUPO</td>
            <td>{{ $alumno['grado'] }} · {{ $alumno['grupo'] }}</td>
        </tr>
    </table>

    <table class="principal">
        <thead>
            <tr>
                <th rowspan="2" class="periodo">PERIODO DE EVALUACIÓN</th>
                <th colspan="{{ $campos->count() }}">CAMPOS FORMATIVOS</th>
            </tr>
            <tr>
                @foreach ($campos as $campo)
                    <th>{{ mb_strtoupper($campo->nombre) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ([1 => '1er', 2 => '2do', 3 => '3er'] as $numero => $etiqueta)
                <tr>
                    <td>{{ $etiqueta }}</td>
                    @foreach ($campos as $campo)
                        <td class="numero">{{ $alumno['campos'][$campo->id]['periodos'][$numero] ?? '—' }}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr>
                <td class="final">PROMEDIO FINAL</td>
                @foreach ($campos as $campo)
                    <td class="final">{{ $alumno['campos'][$campo->id]['final'] }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <table class="derecha">
        <tr>
            <td class="etiqueta" style="width: 18%;">LENGUA INDÍGENA</td>
            <td style="width: 32%; text-align:center;">------------</td>
            <td class="etiqueta" style="width: 18%;">PROMEDIO FINAL DE GRADO</td>
            <td class="marca" style="width: 12%;">{{ $alumno['promedio_general'] }}</td>
            <td class="etiqueta" style="width: 10%;">ASISTENCIAS</td>
            <td style="width: 10%;"></td>
        </tr>
        <tr>
            <td class="etiqueta">PROMOVIDA(O)</td>
            <td class="marca">{{ $alumno['promocion_confirmada'] === true ? 'X' : '' }}</td>
            <td class="etiqueta">NO PROMOVIDA(O)</td>
            <td class="marca">{{ $alumno['promocion_confirmada'] === false ? 'X' : '' }}</td>
            <td class="etiqueta">FOLIO</td>
            <td>{{ $alumnoModel->folio ?: '—' }}</td>
        </tr>
    </table>

    <p class="nota">
        El promedio final de cada campo se establece con un decimal truncado. El promedio final de grado es la suma de los cuatro promedios oficiales de campo dividida entre cuatro y se presenta con un decimal truncado.
    </p>
</body>
</html>
