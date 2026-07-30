<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Analítica institucional</title>
    <style>
        @page { margin: 22px 26px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 9px; }
        .header { border-bottom: 3px solid #006492; padding-bottom: 10px; margin-bottom: 12px; }
        .logo { width: 150px; float: left; }
        .title { margin-left: 170px; text-align: right; }
        h1 { font-size: 18px; margin: 0; color: #006492; }
        h2 { font-size: 11px; color: #006492; margin: 14px 0 6px; border-bottom: 1px solid #d9e3ea; padding-bottom: 4px; }
        .muted { color: #64748b; }
        .clear { clear: both; }
        .kpis { width: 100%; border-collapse: separate; border-spacing: 5px; }
        .kpis td { border: 1px solid #d9e3ea; border-radius: 7px; padding: 8px; width: 16.66%; }
        .label { font-size: 7px; text-transform: uppercase; color: #64748b; font-weight: bold; }
        .value { font-size: 16px; font-weight: bold; color: #006492; margin-top: 3px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data th { background: #006492; color: white; padding: 5px; text-align: left; }
        table.data td { border: 1px solid #d9e3ea; padding: 4px; vertical-align: top; }
        .alert { border-left: 4px solid #e11d48; background: #fff1f2; margin: 5px 0; padding: 6px; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; color: #64748b; text-align: center; font-size: 7px; }
    </style>
</head>
<body>
    <div class="header">
        @if ($logo)<img src="{{ $logo }}" class="logo" alt="Logo">@endif
        <div class="title">
            <h1>Analítica institucional avanzada</h1>
            <div class="muted">{{ data_get($datos, 'contexto.ciclo') }} · {{ data_get($datos, 'contexto.nivel') }}</div>
            <div class="muted">Comparación: {{ data_get($datos, 'contexto.ciclo_comparacion') ?: 'No aplicada' }}</div>
        </div>
        <div class="clear"></div>
    </div>

    @php($r = $datos['resumen'] ?? [])
    <table class="kpis"><tr>
        <td><div class="label">Matrícula</div><div class="value">{{ $r['matricula'] ?? 0 }}</div></td>
        <td><div class="label">Permanencia</div><div class="value">{{ $r['permanencia'] ?? 0 }}%</div></td>
        <td><div class="label">Promoción</div><div class="value">{{ $r['promocion'] ?? 0 }}%</div></td>
        <td><div class="label">Promedio</div><div class="value">{{ number_format((float) ($r['promedio'] ?? 0), 2) }}</div></td>
        <td><div class="label">Riesgo alto/crítico</div><div class="value">{{ $r['riesgo_alto_critico'] ?? 0 }}%</div></td>
        <td><div class="label">Documentación</div><div class="value">{{ $r['documentacion'] ?? 0 }}%</div></td>
    </tr></table>

    @if (count($datos['alertas'] ?? []) > 0)
        <h2>Alertas directivas</h2>
        @foreach ($datos['alertas'] as $alerta)
            <div class="alert"><strong>{{ strtoupper($alerta['severidad']) }} · {{ $alerta['titulo'] }}</strong><br>{{ $alerta['mensaje'] }}</div>
        @endforeach
    @endif

    <h2>Comparación y trayectoria del ciclo</h2>
    <table class="data"><thead><tr><th>En curso</th><th>Promovidos</th><th>Egresados</th><th>No promovidos</th><th>Traslados</th><th>Bajas</th><th>No reinscritos</th><th>Reingresos</th></tr></thead><tbody><tr>
        <td>{{ data_get($datos, 'matricula.en_curso', 0) }}</td><td>{{ data_get($datos, 'matricula.promovidos', 0) }}</td><td>{{ data_get($datos, 'matricula.egresados', 0) }}</td><td>{{ data_get($datos, 'matricula.no_promovidos', 0) }}</td><td>{{ data_get($datos, 'matricula.traslados', 0) }}</td><td>{{ data_get($datos, 'matricula.bajas', 0) }}</td><td>{{ data_get($datos, 'matricula.no_reinscritos', 0) }}</td><td>{{ data_get($datos, 'matricula.reingresos', 0) }}</td>
    </tr></tbody></table>

    <h2>Tendencia por ciclo</h2>
    <table class="data"><thead><tr><th>Ciclo</th><th>Matrícula</th><th>Promedio</th><th>Permanencia</th><th>Promoción</th><th>Riesgo alto/crítico</th></tr></thead><tbody>
        @foreach ($datos['tendencia_ciclos'] ?? [] as $fila)
            <tr><td>{{ $fila['ciclo'] }}</td><td>{{ $fila['matricula'] }}</td><td>{{ $fila['promedio'] }}</td><td>{{ $fila['permanencia'] }}%</td><td>{{ $fila['promocion'] }}%</td><td>{{ $fila['riesgo'] }}%</td></tr>
        @endforeach
    </tbody></table>

    <h2>Indicadores por grupo</h2>
    <table class="data"><thead><tr><th>Grado/Semestre</th><th>Grupo</th><th>Alumnos</th><th>Promedio</th><th>Riesgo</th></tr></thead><tbody>
        @foreach (array_slice($datos['grupos'] ?? [], 0, 20) as $fila)
            <tr><td>{{ $fila['grado'] }}</td><td>{{ $fila['grupo'] }}</td><td>{{ $fila['alumnos'] }}</td><td>{{ $fila['promedio'] }}</td><td>{{ $fila['riesgo'] }} ({{ $fila['riesgo_porcentaje'] }}%)</td></tr>
        @endforeach
    </tbody></table>

    <h2>Rendimiento, seguimiento e integridad</h2>
    <table class="data"><thead><tr><th>Evaluaciones</th><th>Reprobadas</th><th>Pendientes</th><th>Riesgo alto/crítico</th><th>Seguimientos activos</th><th>Acciones vencidas</th><th>Integridad crítica</th><th>Conflictos horario</th></tr></thead><tbody><tr>
        <td>{{ data_get($datos, 'rendimiento.evaluaciones', 0) }}</td><td>{{ data_get($datos, 'rendimiento.reprobadas', 0) }}</td><td>{{ data_get($datos, 'rendimiento.pendientes', 0) }}</td><td>{{ data_get($datos, 'riesgo.alto_critico', 0) }}</td><td>{{ data_get($datos, 'seguimiento.activos', 0) }}</td><td>{{ data_get($datos, 'seguimiento.acciones_vencidas', 0) }}</td><td>{{ data_get($datos, 'integridad.criticos', 0) }}</td><td>{{ data_get($datos, 'horarios.conflictos_criticos', 0) }}</td>
    </tr></tbody></table>

    <div class="footer">Centro Universitario Moctezuma · Generado {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
