<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de cierre académico</title>
    <style>
        @page { margin: 26px 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        .header { width: 100%; border-bottom: 3px solid #006492; padding-bottom: 8px; margin-bottom: 12px; }
        .header td { vertical-align: middle; }
        .logo { width: 120px; }
        h1 { margin: 0; font-size: 18px; color: #006492; }
        h2 { margin: 2px 0 0; font-size: 11px; color: #88AC2E; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { border: 1px solid #cbd5e1; padding: 6px; }
        .meta b { color: #006492; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0 -6px 12px; }
        .summary td { border: 1px solid #dbeafe; background: #f8fafc; text-align: center; padding: 8px; border-radius: 6px; }
        .summary strong { display: block; font-size: 16px; color: #006492; }
        .section-title { margin: 14px 0 6px; padding: 6px 8px; color: white; background: #006492; font-size: 11px; }
        table.detail { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        table.detail th { background: #e0f2fe; color: #075985; border: 1px solid #94a3b8; padding: 5px; font-size: 8px; }
        table.detail td { border: 1px solid #cbd5e1; padding: 5px; vertical-align: top; }
        table.detail tr:nth-child(even) td { background: #f8fafc; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; text-align: center; color: #64748b; font-size: 8px; }
        .status { font-weight: bold; }
        .reverted { color: #b91c1c; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width:140px">@if($logo)<img src="{{ $logo }}" class="logo">@endif</td>
            <td>
                <h1>Acta y concentrado de cierre de grado, nivel y continuidad</h1>
                <h2>Centro Universitario Moctezuma - Control Escolar</h2>
            </td>
            <td style="width:150px;text-align:right"><b>Proceso #{{ $proceso->id }}</b><br>{{ $proceso->realizado_at?->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td><b>Nivel:</b> {{ $proceso->nivel?->nombre }}</td>
            <td><b>Generación:</b> {{ $proceso->generacion?->etiqueta }}</td>
            <td><b>Alcance:</b> {{ ucfirst($proceso->alcance ?? 'generación') }} @if($proceso->grupoOrigen) · Grupo {{ $proceso->grupoOrigen?->asignacionGrupo?->nombre }} @endif</td>
        </tr>
        <tr>
            <td><b>Ciclo origen:</b> {{ $proceso->cicloEscolar?->inicio_anio }}-{{ $proceso->cicloEscolar?->fin_anio }}</td>
            <td><b>Ciclo destino:</b> {{ $proceso->cicloDestino ? $proceso->cicloDestino->inicio_anio.'-'.$proceso->cicloDestino->fin_anio : 'No aplica' }}</td>
            <td><b>Fecha efectiva:</b> {{ $proceso->fecha_efectiva?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td colspan="2"><b>Motivo:</b> {{ $proceso->motivo }}</td>
            <td><b>Responsable:</b> {{ $proceso->usuarioRealizo?->name ?? 'Sin registro' }}</td>
        </tr>
        <tr>
            <td><b>Integridad:</b> {{ str_starts_with((string) $proceso->integridad_estado, 'verificado') ? 'Respaldo verificado' : 'Proceso anterior / sin firma' }}</td>
            <td><b>Firma de simulación:</b> {{ $proceso->vista_previa_hash ? substr($proceso->vista_previa_hash, 0, 16).'…' : 'No disponible' }}</td>
            <td><b>Firma de respaldo:</b> {{ $proceso->respaldo_hash ? substr($proceso->respaldo_hash, 0, 16).'…' : 'No disponible' }}</td>
        </tr>
        @if($proceso->revertido_at)
            <tr><td colspan="3" class="reverted"><b>Proceso revertido:</b> {{ $proceso->revertido_at->format('d/m/Y H:i') }}. {{ $proceso->motivo_reversion }}</td></tr>
        @endif
    </table>

    <table class="summary">
        <tr>
            @foreach([
                'continuidad_interna' => 'Promoción / continuidad proyectada',
                'no_reinscrito' => 'No reinscritos',
                'egresado' => 'Egresados',
                'traslado' => 'Traslados',
                'baja_definitiva' => 'Bajas',
                'no_promovido' => 'Repetición proyectada',
                'sin_cambio' => 'Sin cambio',
            ] as $clave => $etiqueta)
                <td><strong>{{ $detallesPorResultado->get($clave, collect())->count() }}</strong>{{ $etiqueta }}</td>
            @endforeach
        </tr>
    </table>

    @foreach($detallesPorResultado as $resultado => $detalles)
        @php
            $etiquetaResultado = match ($resultado) {
                'continuidad_interna' => 'PROMOCIÓN O CONTINUIDAD PROYECTADA',
                'no_reinscrito' => 'ACREDITÓ, PERO NO SE REINSCRIBIRÁ',
                'egresado' => 'EGRESADO SIN CONTINUIDAD INTERNA',
                'traslado' => 'TRASLADO',
                'baja_definitiva' => 'BAJA DEFINITIVA',
                'no_promovido' => 'NO PROMOVIDO / REPETICIÓN PROYECTADA',
                'sin_cambio' => 'SIN CAMBIO HISTÓRICO',
                default => strtoupper(str_replace('_', ' ', $resultado)),
            };
        @endphp
        <div class="section-title">{{ $etiquetaResultado }} - {{ $detalles->count() }} alumno(s)</div>
        <table class="detail">
            <thead>
                <tr>
                    <th style="width:24px">#</th>
                    <th style="width:92px">Matrícula</th>
                    <th>Alumno</th>
                    <th style="width:90px">Origen</th>
                    <th style="width:130px">Destino</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detalles as $indice => $detalle)
                    @php
                        $origen = $detalle->inscripcionCicloOrigen;
                        $destino = $detalle->inscripcionCicloDestino;
                        $proyeccion = $detalle->proyeccionContinuidad;
                        $alumno = $detalle->inscripcion;
                        $origenTxt = $origen?->semestre?->numero ? 'Sem. '.$origen->semestre->numero : ($origen?->grado?->nombre ?? 'Sin grado');
                        $origenTxt .= ' / '.($origen?->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo');
                        $destinoTxt = 'No aplica';
                        if ($destino) {
                            $destinoTxt = ($destino->nivel?->nombre ?? '').' · '.($destino->semestre?->numero ? 'Sem. '.$destino->semestre->numero : ($destino->grado?->nombre ?? '')).' · '.($destino->grupo?->asignacionGrupo?->nombre ?? '');
                        } elseif ($proyeccion) {
                            $destinoTxt = ($proyeccion->nivelDestino?->nombre ?? '').' · '.($proyeccion->semestreDestino?->numero ? 'Sem. '.$proyeccion->semestreDestino->numero : ($proyeccion->gradoDestino?->nombre ?? '')).' · '.($proyeccion->grupoDestino?->asignacionGrupo?->nombre ?? 'Grupo por confirmar').' · '.$proyeccion->etiqueta_tipo.' · '.$proyeccion->etiqueta_estado;
                        }
                    @endphp
                    <tr>
                        <td>{{ $indice + 1 }}</td>
                        <td>{{ $detalle->estado_anterior['matricula'] ?? $origen?->matricula ?? $alumno?->matricula }}</td>
                        <td><b>{{ trim(($alumno?->apellido_paterno ?? '').' '.($alumno?->apellido_materno ?? '').' '.($alumno?->nombre ?? '')) }}</b>@if($detalle->revertido_at)<br><span class="reverted">Revertido</span>@endif</td>
                        <td>{{ $origenTxt }}</td>
                        <td>{{ $destinoTxt }}</td>
                        <td>{{ $detalle->observacion }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div style="margin-top:26px;width:100%;">
        <table style="width:100%;border-collapse:collapse;text-align:center;">
            <tr>
                <td style="width:45%;padding-top:30px;border-top:1px solid #334155;">Responsable de Control Escolar</td>
                <td style="width:10%"></td>
                <td style="width:45%;padding-top:30px;border-top:1px solid #334155;">Dirección del nivel</td>
            </tr>
        </table>
    </div>

    <div class="footer">Documento generado por el sistema Moctezuma Básica. La promoción, repetición o continuidad solo se formaliza al confirmar el ingreso al ciclo destino; el historial anterior permanece conservado.</div>
</body>
</html>
