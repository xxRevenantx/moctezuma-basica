<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alumnos no vigentes</title>
    <style>
        @page { margin: 22px 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 9px; }
        h1 { margin: 0; font-size: 19px; color: #5b21b6; }
        .meta { margin-top: 4px; color: #64748b; font-size: 10px; }
        .badge { display: inline-block; margin-top: 8px; padding: 4px 9px; border-radius: 10px; background: #ede9fe; color: #6d28d9; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #312e81; color: #fff; padding: 6px 5px; text-align: left; font-size: 8px; }
        td { border: 1px solid #cbd5e1; padding: 5px; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        .center { text-align: center; }
        .status { font-weight: bold; color: #6d28d9; }
        .footer { margin-top: 8px; color: #64748b; font-size: 8px; }
    </style>
</head>
<body>
    <h1>Alumnos no vigentes</h1>
    <div class="meta">
        {{ $nivel->nombre }} · Ciclo {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }} · Generado {{ now()->format('d/m/Y H:i') }}
    </div>
    <div class="badge">{{ $categoria }} · {{ $alumnos->count() }} registro(s)</div>

    <table>
        <thead>
            <tr>
                <th class="center">#</th>
                <th>Matrícula</th>
                <th>CURP</th>
                <th>Alumno</th>
                <th>Generación</th>
                <th>Ubicación</th>
                <th>Estatus</th>
                <th>Ingreso</th>
                <th>Salida/cierre</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($alumnos as $indice => $alumno)
                @php
                    $contexto = $service->contextoDe($alumno);
                    $estatus = $service->estatusDe($contexto, $alumno);
                @endphp
                <tr>
                    <td class="center">{{ $indice + 1 }}</td>
                    <td>{{ $service->matriculaDe($contexto, $alumno) ?: '—' }}</td>
                    <td>{{ $alumno->curp ?: '—' }}</td>
                    <td>{{ trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}") }}</td>
                    <td>{{ $contexto?->generacion?->etiqueta ?? '—' }}</td>
                    <td>
                        {{ $contexto?->grado?->nombre ?? '—' }}
                        @if ($contexto?->grupo?->asignacionGrupo?->nombre)
                            · {{ $contexto->grupo->asignacionGrupo->nombre }}
                        @endif
                        @if ($contexto?->semestre?->numero)
                            · Sem. {{ $contexto->semestre->numero }}
                        @endif
                    </td>
                    <td class="status">{{ $service->etiquetaEstatus($estatus) }}</td>
                    <td>{{ optional($service->fechaIngresoDe($contexto))->format('d/m/Y') ?: '—' }}</td>
                    <td>{{ optional($service->fechaSalidaDe($contexto))->format('d/m/Y') ?: '—' }}</td>
                    <td>{{ $service->motivoDe($contexto, $alumno) ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="center">No hay registros con los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Las bajas temporales, definitivas, traslados, suspensiones e inactivos se consultan en el módulo Bajas.</div>
</body>
</html>
