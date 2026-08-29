<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 18px 20px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 8.5px; }
        h1 { margin: 0; font-size: 16px; color: #11234E; }
        .meta { margin-top: 5px; color: #64748b; font-size: 8px; }
        .badge { display: inline-block; padding: 3px 7px; border-radius: 10px; background: #fee2e2; color: #9f1239; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; vertical-align: middle; }
        th { background: #11234E; color: #fff; font-size: 7.5px; text-transform: uppercase; }
        tr:nth-child(even) td { background: #f8fafc; }
        .center { text-align: center; }
        .small { font-size: 7px; }
        .total { margin-top: 9px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $titulo }}</h1>
    <div class="meta">
        Generado: {{ $datos['generado_at'] ?? '' }} ·
        Edad escolar calculada al 31 de diciembre del año de inicio del ciclo escolar.
        En educación básica, +2 años o más respecto a la edad esperada se muestra como extraedad.
    </div>

    <div class="total">Total: {{ number_format((int) ($datos['total'] ?? 0)) }} alumno(s)</div>

    <table>
        <thead>
            <tr>
                <th>Matrícula</th>
                <th>Alumno</th>
                <th>Nivel</th>
                <th>Grado / Grupo</th>
                <th>Ciclo</th>
                <th>Nacimiento</th>
                <th>Actual</th>
                <th>Al corte</th>
                <th>Esperada</th>
                <th>Diferencia</th>
                <th>Situación</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($datos['filas'] ?? [] as $fila)
                <tr>
                    <td>{{ $fila['matricula'] ?: '—' }}</td>
                    <td>{{ $fila['alumno'] }}</td>
                    <td>{{ $fila['nivel'] }}</td>
                    <td>{{ $fila['grado'] }}°{{ $fila['grupo'] ? ' · '.$fila['grupo'] : '' }}</td>
                    <td class="center">{{ $fila['ciclo'] }}</td>
                    <td class="center">{{ $fila['fecha_nacimiento'] }}</td>
                    <td class="center">{{ $fila['edad_actual'] }}</td>
                    <td class="center">{{ $fila['edad_corte'] }}</td>
                    <td class="center">{{ $fila['edad_esperada'] }}</td>
                    <td class="center">{{ ($fila['diferencia'] ?? 0) > 0 ? '+' : '' }}{{ $fila['diferencia'] }}</td>
                    <td><span class="badge">{{ $fila['etiqueta'] }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="center">No hay alumnos que cumplan los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
