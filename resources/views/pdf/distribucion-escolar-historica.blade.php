<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Distribución escolar · Centro Universitario Moctezuma</title>

    <style>
        @page {
            margin: 22px 24px 30px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #172033;
            font-size: 8.4px;
            line-height: 1.35;
        }

        .top-line {
            height: 5px;
            margin-bottom: 10px;
            background: #006492;
        }

        .header {
            width: 100%;
            margin-bottom: 9px;
            border-collapse: collapse;
        }

        .header td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 150px;
        }

        .logo {
            max-width: 130px;
            max-height: 60px;
        }

        .title-cell {
            text-align: center;
        }

        .institution {
            margin: 0;
            color: #006492;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: .7px;
        }

        .title {
            margin: 3px 0 0;
            color: #101827;
            font-size: 18px;
            font-weight: bold;
        }

        .subtitle {
            margin: 3px 0 0;
            color: #88AC2E;
            font-size: 10px;
            font-weight: bold;
        }

        .meta {
            width: 190px;
            text-align: right;
            color: #667085;
            font-size: 7px;
            line-height: 1.55;
        }

        .scope {
            margin: 0 0 10px;
            padding: 7px 9px;
            border: 1px solid #dce5ea;
            background: #f8fafc;
            color: #475569;
        }

        .scope strong {
            color: #006492;
        }

        .metrics {
            width: 100%;
            margin: 0 0 12px;
            border-collapse: separate;
            border-spacing: 4px 0;
        }

        .metric {
            padding: 7px 7px;
            border: 1px solid #dce5ea;
            background: #ffffff;
            vertical-align: top;
        }

        .metric.primary {
            border-color: #006492;
            background: #006492;
            color: #ffffff;
        }

        .metric.warning {
            border-color: #f1c96c;
            background: #fff8e8;
        }

        .metric .label {
            color: #64748b;
            font-size: 5.8px;
            font-weight: bold;
            text-align: center;
        }

        .metric.primary .label {
            color: #dff5ff;
        }

        .metric .value {
            margin-top: 2px;
            color: #111827;
            font-size: 16px;
            font-weight: bold;
            line-height: 1;
            text-align: center;
        }

        .metric.primary .value {
            color: #ffffff;
        }

        .metric.warning .value {
            color: #9a6700;
        }

        .block {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .block-head {
            width: 100%;
            border-collapse: collapse;
        }

        .block-head td {
            padding: 6px 8px;
            background: #006492;
            color: #ffffff;
            font-weight: bold;
        }

        .block-head .right {
            color: #e8f7fc;
            font-size: 6.5px;
            text-align: right;
        }

        .data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .data th {
            padding: 5px 3px;
            border: 1px solid #ffffff;
            background: #101827;
            color: #ffffff;
            font-size: 6.4px;
            text-align: center;
        }

        .data th.total-head {
            background: #88AC2E;
        }

        .data td {
            padding: 5px 3px;
            border: 1px solid #d9e1e7;
            text-align: center;
            vertical-align: middle;
        }

        .data tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .active {
            color: #006492;
            font-weight: bold;
        }

        .inactive {
            color: #a16207;
            font-weight: bold;
        }

        .drop {
            color: #be123c;
            font-weight: bold;
        }

        .transfer {
            color: #c2410c;
            font-weight: bold;
        }

        .graduated {
            color: #6d28d9;
            font-weight: bold;
        }

        .total {
            background: #edf7df !important;
            color: #42650a;
            font-weight: bold;
        }

        .data tfoot td {
            border-top: 2px solid #006492;
            background: #e9eef2;
            font-weight: bold;
        }

        .data tfoot td.total {
            background: #88AC2E !important;
            color: #ffffff;
        }

        .note {
            margin-top: 8px;
            padding: 7px 9px;
            border-left: 4px solid #88AC2E;
            background: #f7faef;
            color: #475569;
            font-size: 7px;
        }

        .note strong {
            color: #006492;
        }

        .footer {
            position: fixed;
            right: 0;
            bottom: -18px;
            left: 0;
            padding-top: 4px;
            border-top: 1px solid #d8e0e5;
            color: #7a8694;
            font-size: 6px;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $filasGlobales = collect($bloques)
            ->flatMap(fn ($bloque) => collect($bloque['filas'] ?? []));

        $totalesGlobales = [
            'hombres' => (int) collect($bloques)->sum(
                fn ($bloque) => $bloque['totales']['hombres_vigentes'] ?? 0
            ),
            'mujeres' => (int) collect($bloques)->sum(
                fn ($bloque) => $bloque['totales']['mujeres_vigentes'] ?? 0
            ),
            'total' => (int) collect($bloques)->sum(
                fn ($bloque) => $bloque['totales']['total_historico'] ?? 0
            ),
            'activos' => (int) collect($bloques)->sum(
                fn ($bloque) => $bloque['totales']['activos'] ?? 0
            ),
            'no_vigentes' => (int) collect($bloques)->sum(
                fn ($bloque) => $bloque['totales']['no_vigentes'] ?? 0
            ),
        ];

        $ciclosTexto = collect($listado)
            ->pluck('ciclo')
            ->filter()
            ->unique()
            ->implode(' · ');

        $generacionesTexto = collect($listado)
            ->pluck('generacion')
            ->filter()
            ->unique()
            ->take(5)
            ->implode(' · ');

        $esBachillerato = ($nivel->slug ?? null) === 'bachillerato';
    @endphp

    <div class="top-line"></div>

    <table class="header">
        <tr>
            <td class="logo-cell">
                @if ($logo)
                    <img src="{{ $logo }}" class="logo" alt="Centro Universitario Moctezuma">
                @endif
            </td>

            <td class="title-cell">
                <p class="institution">CENTRO UNIVERSITARIO MOCTEZUMA</p>
                <h1 class="title">DISTRIBUCIÓN ESCOLAR</h1>
                <p class="subtitle">
                    {{ mb_strtoupper($nivel->nombre) }}
                    @if ($subtitulo)
                        · {{ mb_strtoupper($subtitulo) }}
                    @endif
                </p>
            </td>

            <td class="meta">
                <b>CCT:</b> {{ $nivel->cct ?: '—' }}<br>
                <b>Emisión:</b> {{ $generadoEn->format('d/m/Y H:i') }}<br>
                <b>Usuario:</b> {{ $generadoPor }}
            </td>
        </tr>
    </table>

    <div class="scope">
        <strong>Alcance:</strong>
        {{ $ciclosTexto ?: 'Ciclo seleccionado' }}
        ·
        {{ $generacionesTexto ?: 'Todas las generaciones' }}
        ·
        {{ $filasGlobales->count() }} {{ $filasGlobales->count() === 1 ? 'grupo/ubicación' : 'grupos/ubicaciones' }}
    </div>

    <table class="metrics">
        <tr>
            <td class="metric primary">
                <div class="label">MATRÍCULA VIGENTE</div>
                <div class="value">{{ $totalesGlobales['activos'] }}</div>
            </td>

            <td class="metric">
                <div class="label">REGISTROS DEL CICLO</div>
                <div class="value">{{ $totalesGlobales['total'] }}</div>
            </td>

            <td class="metric warning">
                <div class="label">NO VIGENTES</div>
                <div class="value">{{ $totalesGlobales['no_vigentes'] }}</div>
            </td>

            <td class="metric">
                <div class="label">HOMBRES VIGENTES</div>
                <div class="value">{{ $totalesGlobales['hombres'] }}</div>
            </td>

            <td class="metric">
                <div class="label">MUJERES VIGENTES</div>
                <div class="value">{{ $totalesGlobales['mujeres'] }}</div>
            </td>

            <td class="metric">
                <div class="label">GRUPOS</div>
                <div class="value">{{ $filasGlobales->count() }}</div>
            </td>
        </tr>
    </table>

    @foreach ($bloques as $bloque)
        <div class="block">
            <table class="block-head">
                <tr>
                    <td>
                        {{ mb_strtoupper($bloque['ciclo']) }}
                    </td>

                    <td class="right">
                        Vigentes {{ $bloque['totales']['activos'] }}
                        · No vigentes {{ $bloque['totales']['no_vigentes'] ?? 0 }}
                        · Total {{ $bloque['totales']['total_historico'] }}
                    </td>
                </tr>
            </table>

            <table class="data">
                <thead>
                    <tr>
                        <th>Grado</th>

                        @if ($esBachillerato)
                            <th>Sem.</th>
                        @endif

                        <th>Grupo</th>
                        <th>H</th>
                        <th>M</th>
                        <th>Vigentes</th>
                        <th>Inactivos</th>
                        <th>Bajas</th>
                        <th>Trasl.</th>
                        <th>Susp.</th>
                        <th>Egres.</th>
                        <th class="total-head">Total ciclo</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($bloque['filas'] as $fila)
                        <tr>
                            <td><b>{{ $fila['grado'] }}</b></td>

                            @if ($esBachillerato)
                                <td>{{ $fila['semestre'] }}</td>
                            @endif

                            <td><b>{{ $fila['grupo'] }}</b></td>
                            <td>{{ $fila['hombres_vigentes'] ?? 0 }}</td>
                            <td>{{ $fila['mujeres_vigentes'] ?? 0 }}</td>
                            <td class="active">{{ $fila['activos'] }}</td>
                            <td class="inactive">{{ $fila['inactivos'] }}</td>
                            <td class="drop">{{ $fila['bajas'] }}</td>
                            <td class="transfer">{{ $fila['traslados'] }}</td>
                            <td class="transfer">{{ $fila['suspendidos'] }}</td>
                            <td class="graduated">{{ $fila['egresados'] }}</td>
                            <td class="total">{{ $fila['total_historico'] }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <td>TOTALES</td>

                        @if ($esBachillerato)
                            <td>—</td>
                        @endif

                        <td>{{ count($bloque['filas']) }} grupos</td>
                        <td>{{ $bloque['totales']['hombres_vigentes'] ?? 0 }}</td>
                        <td>{{ $bloque['totales']['mujeres_vigentes'] ?? 0 }}</td>
                        <td>{{ $bloque['totales']['activos'] }}</td>
                        <td>{{ $bloque['totales']['inactivos'] }}</td>
                        <td>{{ $bloque['totales']['bajas'] }}</td>
                        <td>{{ $bloque['totales']['traslados'] }}</td>
                        <td>{{ $bloque['totales']['suspendidos'] }}</td>
                        <td>{{ $bloque['totales']['egresados'] }}</td>
                        <td class="total">{{ $bloque['totales']['total_historico'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endforeach

    <div class="note">
        <strong>Criterio de lectura.</strong>
        H + M corresponde a matrícula vigente.
        “Registros del ciclo” conserva todos los historiales no anulados.
        Por ello el total puede ser mayor que la matrícula vigente aun cuando Bajas sea 0.
    </div>

    <div class="footer">
        CENTRO UNIVERSITARIO MOCTEZUMA · DISTRIBUCIÓN ESCOLAR INSTITUCIONAL
    </div>
</body>

</html>