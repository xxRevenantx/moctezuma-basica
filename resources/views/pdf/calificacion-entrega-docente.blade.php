<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $entrega->folio }}</title>
    <style>
        @page { margin: 24px 28px 34px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 9px; margin: 0; }
        .header { border: 1px solid #d9e2ec; border-radius: 12px; padding: 15px 17px; margin-bottom: 12px; }
        .brand { font-size: 18px; font-weight: 800; color: #006492; }
        .brand-line { height: 4px; width: 92px; background: #88AC2E; margin-top: 4px; }
        .folio { float: right; text-align: right; }
        .folio strong { display: block; font-size: 12px; color: #006492; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .grid td { width: 25%; padding: 5px 8px; border: 1px solid #e4e9ef; vertical-align: top; }
        .label { display: block; color: #64748b; font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .value { display: block; margin-top: 2px; font-weight: 700; font-size: 9px; }
        h2 { margin: 15px 0 7px; padding: 8px 10px; background: #f2f7fa; border-left: 4px solid #006492; font-size: 11px; color: #123047; page-break-after: avoid; }
        table.grades { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        table.grades thead { display: table-header-group; }
        table.grades tr { page-break-inside: avoid; }
        table.grades th { background: #006492; color: #fff; padding: 6px; border: 1px solid #00547a; text-align: left; font-size: 8px; }
        table.grades td { padding: 5px 6px; border: 1px solid #dbe2e8; }
        table.grades td.grade { text-align: center; font-weight: 800; width: 68px; }
        table.grades td.number { width: 28px; text-align: center; color: #64748b; }
        .declaration { margin-top: 14px; border: 1px solid #c9d9e3; background: #f8fbfc; padding: 12px 14px; border-radius: 10px; line-height: 1.45; }
        .signature { margin-top: 12px; width: 100%; border-collapse: collapse; }
        .signature td { width: 33.33%; padding: 7px 9px; border: 1px solid #e1e7ec; }
        .hash { margin-top: 10px; font-family: DejaVu Sans Mono, monospace; font-size: 6.5px; color: #64748b; word-break: break-all; }
        .footer { position: fixed; bottom: -22px; left: 0; right: 0; text-align: center; color: #718096; font-size: 7px; }
        .clear { clear: both; }
    </style>
</head>
<body>
    <div class="footer">Comprobante institucional interno · {{ $entrega->folio }} · Documento verificable por huella SHA-256</div>

    <div class="header">
        <div class="folio">
            <span class="label">Folio de entrega</span>
            <strong>{{ $entrega->folio }}</strong>
            <span>Versión {{ $entrega->version }}</span>
        </div>
        <div class="brand">Centro Universitario Moctezuma</div>
        <div class="brand-line"></div>
        <div class="clear"></div>

        <table class="grid">
            <tr>
                <td><span class="label">Docente</span><span class="value">{{ $entrega->docente_nombre }}</span></td>
                <td><span class="label">CURP</span><span class="value">{{ $entrega->docente_curp }}</span></td>
                <td><span class="label">Usuario institucional</span><span class="value">{{ $entrega->correo_institucional }}</span></td>
                <td><span class="label">Usuario ID</span><span class="value">#{{ $entrega->user_id }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Nivel</span><span class="value">{{ $entrega->nivel?->nombre }}</span></td>
                <td><span class="label">Grado / grupo</span><span class="value">{{ $entrega->grado?->nombre }} · {{ $entrega->grupo?->asignacionGrupo?->nombre ?? 'Grupo' }}</span></td>
                <td><span class="label">Ciclo / generación</span><span class="value">{{ $entrega->cicloEscolar?->ciclo ?? $entrega->cicloEscolar?->nombre ?? $entrega->ciclo_escolar_id }} · {{ $entrega->generacion?->anio_ingreso }}-{{ $entrega->generacion?->anio_egreso }}</span></td>
                <td><span class="label">Periodo</span><span class="value">{{ $entrega->periodo?->periodoBasica?->periodo ?? $entrega->periodo?->parcialBachillerato?->parcial ?? 'Periodo '.$entrega->periodo_id }}</span></td>
            </tr>
        </table>
    </div>

    @foreach ($materias as $detalles)
        <h2>{{ $detalles->first()?->materia_nombre }}</h2>
        <table class="grades">
            <thead>
                <tr><th style="width:28px;text-align:center">#</th><th style="width:105px">Matrícula</th><th>Alumno</th><th style="width:72px;text-align:center">Calificación</th><th style="width:260px">Observación</th></tr>
            </thead>
            <tbody>
                @foreach ($detalles as $detalle)
                    <tr>
                        <td class="number">{{ $loop->iteration }}</td>
                        <td>{{ $detalle->matricula ?: '—' }}</td>
                        <td>{{ $detalle->alumno_nombre }}</td>
                        <td class="grade">{{ $detalle->calificacion ?? '—' }}</td>
                        <td>{{ $detalle->observacion ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="declaration">
        <strong>Declaración de conformidad:</strong><br>
        {{ $entrega->declaracion }}
    </div>

    <table class="signature">
        <tr>
            <td><span class="label">Confirmado por</span><span class="value">{{ $entrega->docente_nombre }}</span></td>
            <td><span class="label">Fecha y hora</span><span class="value">{{ $entrega->confirmada_at?->format('d/m/Y H:i:s') }}</span></td>
            <td><span class="label">Dirección IP</span><span class="value">{{ $entrega->ip_confirmacion ?: 'No disponible' }}</span></td>
        </tr>
    </table>

    <div class="hash">Snapshot SHA-256: {{ $entrega->snapshot_sha256 }}</div>
</body>
</html>
