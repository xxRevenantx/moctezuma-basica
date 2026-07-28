<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante individual de cierre académico</title>
    <style>
        @page { margin: 38px; }
        body { font-family: DejaVu Sans, sans-serif; color:#1e293b; font-size:11px; }
        .header { border-bottom:4px solid #006492; padding-bottom:12px; margin-bottom:20px; }
        .logo { width:135px; }
        h1 { color:#006492; font-size:20px; margin:0; }
        .card { border:1px solid #cbd5e1; border-radius:10px; padding:16px; margin-bottom:16px; }
        .label { color:#006492; font-weight:bold; }
        .result { background:#ecfccb; border:1px solid #88AC2E; padding:12px; font-size:15px; font-weight:bold; text-align:center; }
        table { width:100%; border-collapse:collapse; }
        td { padding:6px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
    </style>
</head>
<body>
    @php($proyeccion = $detalle->proyeccionContinuidad)
    <table class="header"><tr><td style="width:155px">@if($logo)<img src="{{ $logo }}" class="logo">@endif</td><td><h1>Comprobante individual de cierre de grado, nivel y continuidad</h1><p>Proceso #{{ $proceso->id }} · {{ $proceso->fecha_efectiva?->format('d/m/Y') }}</p></td></tr></table>

    <div class="card">
        <table>
            <tr><td class="label">Alumno</td><td>{{ trim(($detalle->inscripcion?->apellido_paterno ?? '').' '.($detalle->inscripcion?->apellido_materno ?? '').' '.($detalle->inscripcion?->nombre ?? '')) }}</td></tr>
            <tr><td class="label">Matrícula histórica</td><td>{{ $detalle->estado_anterior['matricula'] ?? $detalle->inscripcionCicloOrigen?->matricula }}</td></tr>
            <tr><td class="label">Nivel y generación origen</td><td>{{ $detalle->inscripcionCicloOrigen?->nivel?->nombre }} · {{ $detalle->inscripcionCicloOrigen?->generacion?->etiqueta }}</td></tr>
            <tr><td class="label">Ciclo origen</td><td>{{ $proceso->cicloEscolar?->inicio_anio }}-{{ $proceso->cicloEscolar?->fin_anio }}</td></tr>
        </table>
    </div>

    @php
        $etiquetaResultado = match ($detalle->resultado) {
            'continuidad_interna' => 'PROMOCIÓN O CONTINUIDAD PROYECTADA',
            'no_reinscrito' => 'ACREDITÓ, PERO NO SE REINSCRIBIRÁ',
            'egresado' => 'EGRESADO SIN CONTINUIDAD INTERNA',
            'traslado' => 'TRASLADO',
            'baja_definitiva' => 'BAJA DEFINITIVA',
            'no_promovido' => 'NO PROMOVIDO / REPETICIÓN PROYECTADA',
            default => strtoupper(str_replace('_', ' ', $detalle->resultado)),
        };
    @endphp
    <div class="result">Resultado: {{ $etiquetaResultado }}</div>

    @if($detalle->inscripcionCicloDestino || $proyeccion)
        <div class="card" style="margin-top:16px">
            <table>
                <tr><td class="label">Nivel destino</td><td>{{ $detalle->inscripcionCicloDestino?->nivel?->nombre ?? $proyeccion?->nivelDestino?->nombre }}</td></tr>
                <tr><td class="label">Generación destino</td><td>{{ $detalle->inscripcionCicloDestino?->generacion?->etiqueta ?? $proyeccion?->generacionDestino?->etiqueta }}</td></tr>
                <tr><td class="label">Grado / semestre</td><td>{{ $detalle->inscripcionCicloDestino?->semestre?->numero ? 'Semestre '.$detalle->inscripcionCicloDestino->semestre->numero : ($detalle->inscripcionCicloDestino?->grado?->nombre ?? ($proyeccion?->semestreDestino?->numero ? 'Semestre '.$proyeccion->semestreDestino->numero : $proyeccion?->gradoDestino?->nombre)) }}</td></tr>
                <tr><td class="label">Grupo</td><td>{{ $detalle->inscripcionCicloDestino?->grupo?->asignacionGrupo?->nombre ?? $proyeccion?->grupoDestino?->asignacionGrupo?->nombre ?? 'Por confirmar' }}</td></tr>
                <tr><td class="label">Matrícula destino</td><td>{{ $detalle->inscripcionCicloDestino ? ($detalle->estado_nuevo['matricula'] ?? $detalle->inscripcionCicloDestino?->matricula) : ($proyeccion?->matricula_sugerida ?? 'Por confirmar') }}</td></tr>
                @if($proyeccion)<tr><td class="label">Tipo de proyección</td><td>{{ $proyeccion->etiqueta_tipo }}</td></tr><tr><td class="label">Resultado histórico de origen</td><td>{{ $proyeccion->etiqueta_resultado_origen }}</td></tr><tr><td class="label">Estado de proyección</td><td>{{ $proyeccion->etiqueta_estado }}</td></tr>@endif
            </table>
        </div>
    @endif

    <div class="card" style="margin-top:16px">
        <p><span class="label">Motivo:</span> {{ $proceso->motivo }}</p>
        <p><span class="label">Observación:</span> {{ $detalle->observacion }}</p>
        <p><span class="label">Responsable:</span> {{ $proceso->usuarioRealizo?->name }}</p>
    </div>

    <table style="margin-top:55px;text-align:center"><tr><td style="width:45%;border-top:1px solid #334155">Control Escolar</td><td style="width:10%;border:0"></td><td style="width:45%;border-top:1px solid #334155">Dirección</td></tr></table>
</body>
</html>
