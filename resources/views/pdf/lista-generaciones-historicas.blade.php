<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }} - {{ $nivel->nombre }}</title>
    <style>
        @page {
            margin: 22px 24px 26px 24px;
        }

        * {
            box-sizing: border-box;
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            src: url('{{ storage_path('fonts/ARIAL.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: 700;
            src: url('{{ storage_path('fonts/ARIALBD.ttf') }}') format('truetype');
        }

        body {
            margin: 0;
            font-family: 'ARIAL', DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 8px;
        }

        .page {
            width: 100%;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #006492;
            margin-bottom: 8px;
        }

        .header td {
            vertical-align: middle;
            padding: 2px 6px 7px;
        }

        .logo-cell {
            width: 14%;
            text-align: center;
        }

        .logo {
            max-width: 64px;
            max-height: 58px;
        }

        .school {
            text-align: center;
        }

        .school-name {
            font-size: 15px;

            color: #006492;
            letter-spacing: .3px;
        }

        .level {
            margin-top: 2px;
            font-size: 9px;

            color: #88AC2E;
        }

        .document-title {
            margin-top: 4px;
            font-size: 11px;

            color: #0f172a;
        }

        .document-meta {
            margin-top: 3px;
            font-size: 7.5px;
            color: #475569;
        }

        .green-line {
            height: 3px;
            background: #88AC2E;
            margin-top: -8px;
            margin-bottom: 8px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 7px;
        }

        .info-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            background: #f8fafc;
        }

        .info-label {
            display: block;
            font-size: 6.5px;

            color: #64748b;
            text-transform: uppercase;
        }

        .info-value {
            display: block;
            margin-top: 1px;
            font-size: 8px;

            color: #0f172a;
        }

        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px 0;
            margin: 0 -3px 8px;
        }

        .summary td {
            text-align: center;
            padding: 5px 3px;
            border-radius: 5px;
        }

        .summary .label {
            font-size: 6px;

            text-transform: uppercase;
        }

        .summary .value {
            margin-top: 1px;
            font-size: 12px;
            font-weight: 900;
        }

        .s-total {
            background: #e2e8f0;
            color: #0f172a;
        }

        .s-men {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .s-women {
            background: #fce7f3;
            color: #be185d;
        }

        .s-grad {
            background: #dcfce7;
            color: #15803d;
        }

        .s-low {
            background: #ffedd5;
            color: #c2410c;
        }

        .s-move {
            background: #ede9fe;
            color: #6d28d9;
        }

        .s-archive {
            background: #f1f5f9;
            color: #475569;
        }

        .group-title {
            margin: 7px 0 4px;
            padding: 4px 7px;
            border-left: 4px solid #88AC2E;
            background: #eff6ff;
            color: #006492;
            font-size: 8px;
            font-weight: 900;
        }

        table.students {
            width: 100%;
            border-collapse: collapse;
        }

        .students th {
            border: 1px solid #075985;
            background: #006492;
            color: #fff;
            padding: 4px 3px;
            font-size: 8px;
            text-transform: uppercase;
            text-align: center;
        }

        .students td {
            border: 1px solid #94a3b8;
            padding: 3.5px 3px;
            vertical-align: middle;
            font-size: 6.5px;
            line-height: 1.18;
            word-wrap: break-word;
        }

        .students tr:nth-child(even) td {
            background: #f8fafc;
        }

        .center {
            text-align: center;
        }

        .name {
            font-weight: 700;
        }

        .status {
            font-size: 6.1px;
        }

        .subtotal {
            margin: 3px 0 7px;
            text-align: right;
            color: #475569;
            font-size: 6.5px;
            font-weight: 700;
        }

        .empty {
            margin-top: 28px;
            padding: 20px;
            border: 1px dashed #94a3b8;
            background: #f8fafc;
            color: #64748b;
            text-align: center;
            font-size: 10px;
        }

        .footer-note {
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 6.2px;
            text-align: center;
        }
    </style>
</head>

<body>
    @foreach ($generaciones as $generacion)
        <section class="page">
            <table class="header">
                <tr>
                    <td class="logo-cell">
                        @if ($logo_nivel)
                            <img class="logo" src="{{ $logo_nivel }}" alt="Logo del nivel">
                        @endif
                    </td>
                    <td class="school">
                        <div class="school-name">CENTRO UNIVERSITARIO MOCTEZUMA A.C.</div>
                        <div class="level">{{ mb_strtoupper($nivel->nombre) }} · C.C.T. {{ $nivel->cct }}</div>
                        <div class="document-title">{{ $titulo }}</div>
                        <div class="document-meta">Generación {{ $generacion['etiqueta'] }} ·
                            {{ $generacion['estado'] }} · {{ $estatus_etiqueta }}</div>
                    </td>
                    <td class="logo-cell">
                        @if ($logo_institucional)
                            <img class="logo" src="{{ $logo_institucional }}" alt="Logo institucional">
                        @endif
                    </td>
                </tr>
            </table>
            <div class="green-line"></div>

            <table class="info-table">
                <tr>
                    <td style="width: 24%">
                        <span class="info-label">Nivel educativo</span>
                        <span class="info-value">{{ $nivel->nombre }}</span>
                    </td>
                    <td style="width: 24%">
                        <span class="info-label">Generación</span>
                        <span class="info-value">{{ $generacion['etiqueta'] }}</span>
                    </td>
                    <td style="width: 24%">
                        <span class="info-label">Filtro aplicado</span>
                        <span class="info-value">{{ $estatus_etiqueta }}</span>
                    </td>
                    <td style="width: 28%">
                        <span class="info-label">Fecha de emisión</span>
                        <span
                            class="info-value">{{ $fecha_generacion }}{{ $incluir_archivados ? ' · Incluye archivados' : '' }}</span>
                    </td>
                </tr>
            </table>

            <table class="summary">
                <tr>
                    <td class="s-total">
                        <div class="label">Total</div>
                        <div class="value">{{ $generacion['resumen']['total'] }}</div>
                    </td>
                    <td class="s-men">
                        <div class="label">Hombres</div>
                        <div class="value">{{ $generacion['resumen']['hombres'] }}</div>
                    </td>
                    <td class="s-women">
                        <div class="label">Mujeres</div>
                        <div class="value">{{ $generacion['resumen']['mujeres'] }}</div>
                    </td>
                    <td class="s-grad">
                        <div class="label">Egresados</div>
                        <div class="value">{{ $generacion['resumen']['egresados'] }}</div>
                    </td>
                    <td class="s-low">
                        <div class="label">Bajas</div>
                        <div class="value">{{ $generacion['resumen']['bajas'] }}</div>
                    </td>
                    <td class="s-move">
                        <div class="label">Trasladados</div>
                        <div class="value">{{ $generacion['resumen']['trasladados'] }}</div>
                    </td>
                    <td class="s-archive">
                        <div class="label">Archivados</div>
                        <div class="value">{{ $generacion['resumen']['archivados'] }}</div>
                    </td>
                </tr>
            </table>

            @if (collect($generacion['grupos'])->isEmpty())
                <div class="empty">No se encontraron alumnos con los filtros seleccionados para esta generación.</div>
            @else
                @php $consecutivo = 1; @endphp
                @foreach ($generacion['grupos'] as $grupo)
                    <div class="group-title">{{ $grupo['titulo'] }}</div>
                    <table class="students">
                        <thead>
                            <tr>
                                <th style="width: 4%">No.</th>
                                <th style="width: 11%">Matrícula</th>
                                <th style="width: 24%">Nombre completo</th>
                                <th style="width: 17%">CURP</th>
                                <th style="width: 7%">Género</th>
                                <th style="width: 9%">Generación</th>
                                <th style="width: 12%">Grupo</th>
                                <th style="width: 16%">Estatus / fecha de egreso</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grupo['alumnos'] as $alumno)
                                <tr>
                                    <td class="center">{{ $consecutivo++ }}</td>
                                    <td>{{ $alumno['matricula'] }}</td>
                                    <td class="name">{{ $alumno['nombre'] }}</td>
                                    <td>{{ $alumno['curp'] }}</td>
                                    <td class="center">{{ $alumno['genero'] }}</td>
                                    <td class="center">{{ $alumno['generacion'] }}</td>
                                    <td>{{ $alumno['grupo'] }}</td>
                                    <td class="status">
                                        {{ $alumno['estatus'] }}
                                        @if ($alumno['fecha_egreso'] !== '—')
                                            <br><strong>{{ $alumno['fecha_egreso'] }}</strong>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="subtotal">
                        Subtotal: {{ $grupo['resumen']['total'] }} alumnos · Hombres:
                        {{ $grupo['resumen']['hombres'] }} · Mujeres: {{ $grupo['resumen']['mujeres'] }}
                    </div>
                @endforeach
            @endif

            <div class="footer-note">
                Documento institucional generado por el Sistema de Control Escolar · Sin firmas · Orden alfabético por
                apellidos y nombre.
            </div>
        </section>
    @endforeach
</body>

</html>
