<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Matrícula histórica</title>
    <style>
        @page { margin: 24px 24px 30px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 8px; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header td { vertical-align: middle; }
        .brand { background: #006492; color: white; padding: 11px 14px; border-radius: 8px; }
        .brand h1 { margin: 0; font-size: 17px; letter-spacing: .3px; }
        .brand p { margin: 3px 0 0; font-size: 8px; color: #dff4ff; }
        .context { text-align: right; padding-left: 12px; }
        .context strong { display: block; font-size: 12px; color: #006492; }
        .context span { display: block; margin-top: 3px; color: #566275; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin: 0 -5px 10px; }
        .summary td { border: 1px solid #dce4eb; border-radius: 6px; padding: 6px 8px; background: #f8fafc; }
        .summary b { display: block; font-size: 13px; color: #006492; }
        .summary span { color: #64748b; text-transform: uppercase; font-size: 6.8px; letter-spacing: .5px; }
        table.list { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .list th { background: #861f41; color: white; padding: 5px 4px; border: 1px solid #741936; text-transform: uppercase; font-size: 6.5px; }
        .list td { border: 1px solid #d7dde4; padding: 4px; vertical-align: top; line-height: 1.25; word-wrap: break-word; }
        .list tr:nth-child(even) td { background: #f7f9fb; }
        .number { text-align: center; width: 3%; }
        .matricula { width: 9%; }
        .curp { width: 11%; }
        .alumno { width: 17%; font-weight: bold; }
        .generacion { width: 7%; text-align: center; }
        .ubicacion { width: 9%; text-align: center; }
        .estatus { width: 9%; text-align: center; }
        .fechas { width: 10%; }
        .motivo { width: 16%; }
        .badge { display: inline-block; padding: 2px 5px; border-radius: 8px; font-weight: bold; font-size: 6.7px; }
        .active { color: #166534; background: #dcfce7; }
        .low { color: #991b1b; background: #fee2e2; }
        .warn { color: #92400e; background: #fef3c7; }
        .other { color: #3730a3; background: #e0e7ff; }
        .muted { color: #64748b; font-size: 6.6px; }
        .reconstructed { color: #a21caf; font-weight: bold; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; color: #64748b; font-size: 7px; border-top: 1px solid #dce4eb; padding-top: 4px; }
        .footer .page:after { content: counter(page); }
        .empty { padding: 35px !important; text-align: center; color: #64748b; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="brand" style="width: 55%;">
                <h1>CENTRO UNIVERSITARIO MOCTEZUMA</h1>
                <p>Control escolar · Historial académico por ciclo y corte</p>
            </td>
            <td class="context">
                <strong>MATRÍCULA HISTÓRICA</strong>
                <span>{{ $nivel->nombre }} · {{ $cicloEscolar->nombre }} · {{ $corte->ciclo }}</span>
                <span>Generado el {{ now()->format('d/m/Y H:i') }}</span>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><b>{{ $resumen['total'] }}</b><span>Total</span></td>
            <td><b>{{ $resumen['hombres'] }}</b><span>Hombres</span></td>
            <td><b>{{ $resumen['mujeres'] }}</b><span>Mujeres</span></td>
            <td><b>{{ $resumen['bajas'] }}</b><span>Bajas / traslados</span></td>
            <td><b>{{ $estatus === 'todos' ? 'Todos' : str($estatus)->replace('_', ' ')->title() }}</b><span>Estatus consultado</span></td>
            <td><b>{{ $busqueda !== '' ? $busqueda : 'Sin filtro' }}</b><span>Búsqueda</span></td>
        </tr>
    </table>

    <table class="list">
        <thead>
            <tr>
                <th class="number">No.</th>
                <th class="matricula">Matrícula</th>
                <th class="curp">CURP</th>
                <th class="alumno">Alumno</th>
                <th class="generacion">Generación</th>
                <th class="ubicacion">Grado / grupo</th>
                <th class="estatus">Estatus</th>
                <th class="fechas">Fechas</th>
                <th class="motivo">Motivo / observaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                @php
                    $alumno = $row->inscripcion;
                    $estadoClass = match ($row->estatus) {
                        'baja_definitiva', 'traslado' => 'low',
                        'baja_temporal', 'no_promovido' => 'warn',
                        'activo' => 'active',
                        default => 'other',
                    };
                    $grupo = $row->grupo?->asignacionGrupo?->nombre ?? $row->grupo?->grupo ?? $row->grupo?->nombre ?? '—';
                @endphp
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td class="matricula"><b>{{ $row->matricula_contexto }}</b>@if($alumno?->deleted_at)<br><span class="muted">Archivado</span>@endif</td>
                    <td class="curp">{{ $alumno?->curp ?: '—' }}</td>
                    <td class="alumno">
                        {{ trim(($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '') . ' ' . ($alumno?->nombre ?? '')) }}
                        @if($row->datos_reconstruidos)<br><span class="reconstructed">Datos reconstruidos</span>@endif
                    </td>
                    <td class="generacion">{{ $row->generacion ? $row->generacion->anio_ingreso . '-' . $row->generacion->anio_egreso : '—' }}</td>
                    <td class="ubicacion">{{ $row->grado?->nombre ?? '—' }} · {{ $grupo }}@if($row->semestre)<br><span class="muted">Sem. {{ $row->semestre->numero }}</span>@endif</td>
                    <td class="estatus"><span class="badge {{ $estadoClass }}">{{ $row->etiqueta_estatus }}</span>@if($row->numero_estancia > 1)<br><span class="muted">Estancia {{ $row->numero_estancia }}</span>@endif</td>
                    <td class="fechas">
                        <span class="muted">Ingreso al plantel:</span> {{ optional($alumno?->fecha_inscripcion)->format('d/m/Y') ?: '—' }}
                        <br><span class="muted">Inscripción al ciclo:</span> {{ optional($row->fecha_inscripcion ?? $row->fecha_inicio)->format('d/m/Y') ?: '—' }}
                        @if($row->fecha_baja)<br><span class="muted">Baja:</span> {{ $row->fecha_baja->format('d/m/Y') }}@endif
                    </td>
                    <td class="motivo">{{ $row->motivo_baja ?: '—' }}@if($row->observaciones_baja)<br><span class="muted">{{ $row->observaciones_baja }}</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No se encontraron alumnos con los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Este reporte conserva la ubicación académica correspondiente al ciclo y corte seleccionados.</span>
        <span style="float:right;">Página <span class="page"></span></span>
    </div>
</body>
</html>
