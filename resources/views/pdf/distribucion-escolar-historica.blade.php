<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Distribución escolar histórica</title>
    <style>
        @page { margin: 18px 20px 24px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 7px;
        }
        .header {
            width: 100%;
            border-bottom: 3px solid #88AC2E;
            margin-bottom: 10px;
            padding-bottom: 8px;
        }
        .header td { vertical-align: middle; }
        .logo { width: 115px; }
        .title { text-align: center; }
        .title h1 {
            margin: 0;
            color: #006492;
            font-size: 18px;
            letter-spacing: .4px;
        }
        .title h2 {
            margin: 4px 0 0;
            color: #88AC2E;
            font-size: 12px;
        }
        .meta {
            width: 185px;
            text-align: right;
            color: #6b7280;
            line-height: 1.5;
        }
        .block {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .block-title {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .block-title td {
            background: #006492;
            color: white;
            padding: 6px 8px;
            font-weight: bold;
        }
        .block-title .cycle {
            font-size: 11px;
        }
        .block-title .totals {
            text-align: right;
            font-size: 7px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.data th {
            background: #88AC2E;
            color: white;
            border: 1px solid #ffffff;
            padding: 3px 2px;
            text-align: center;
            font-size: 5.4px;
            line-height: 1.12;
        }
        table.data td {
            border: 1px solid #d1d5db;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.15;
            word-wrap: break-word;
        }
        table.data tbody tr:nth-child(even) td { background: #f8fafc; }
        table.data tfoot td {
            background: #e5e7eb;
            font-weight: bold;
        }
        .left { text-align: left !important; }
        .active { color: #047857; font-weight: bold; }
        .inactive { color: #475569; font-weight: bold; }
        .drop { color: #be123c; font-weight: bold; }
        .transfer { color: #b45309; font-weight: bold; }
        .suspended { color: #c2410c; font-weight: bold; }
        .graduated { color: #6d28d9; font-weight: bold; }
        .footer {
            position: fixed;
            bottom: -16px;
            left: 0;
            right: 0;
            border-top: 1px solid #d1d5db;
            padding-top: 4px;
            color: #6b7280;
            font-size: 6px;
            text-align: center;
        }
        .empty {
            padding: 30px;
            text-align: center;
            border: 1px dashed #9ca3af;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="logo">
                @if ($logo)
                    <img src="{{ $logo }}" style="max-width: 105px; max-height: 58px;" alt="Logo">
                @endif
            </td>
            <td class="title">
                <h1>DISTRIBUCIÓN E HISTORIAL ESCOLAR</h1>
                <h2>{{ mb_strtoupper($nivel->nombre) }}{{ $subtitulo ? ' · ' . mb_strtoupper($subtitulo) : '' }}</h2>
            </td>
            <td class="meta">
                <b>Generado:</b> {{ $generadoEn->format('d/m/Y H:i') }}<br>
                <b>Usuario:</b> {{ $generadoPor }}<br>
                <b>Alcance:</b> {{ ($filtros['modo'] ?? 'ciclo') === 'historico' ? 'Historial completo' : 'Ciclo seleccionado' }}
            </td>
        </tr>
    </table>

    @forelse ($bloques as $bloque)
        <div class="block">
            <table class="block-title">
                <tr>
                    <td class="cycle">DISTRIBUCIÓN ESCOLAR · {{ $bloque['ciclo'] }}</td>
                    <td class="totals">
                        Histórico: {{ $bloque['totales']['total_historico'] }} ·
                        Activos: {{ $bloque['totales']['activos'] }} ·
                        No activos: {{ $bloque['totales']['inactivos'] + $bloque['totales']['bajas'] + $bloque['totales']['traslados'] + $bloque['totales']['suspendidos'] + $bloque['totales']['egresados'] }}
                    </td>
                </tr>
            </table>

            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 5%;">Regional</th>
                        <th style="width: 3%;">Zona</th>
                        <th style="width: 5%;">CCT</th>
                        <th style="width: 10%;">Nombre CT</th>
                        <th style="width: 4.5%;">Nivel</th>
                        <th style="width: 3.5%;">Turno</th>
                        <th style="width: 3%;">Grado</th>
                        <th style="width: 3%;">Sem.</th>
                        <th style="width: 3%;">Grupo</th>
                        <th style="width: 2%;">H</th>
                        <th style="width: 2%;">M</th>
                        <th style="width: 3.5%;">Total histórico</th>
                        <th style="width: 3%;">Activos</th>
                        <th style="width: 3.5%;">Inactivos</th>
                        <th style="width: 3%;">Bajas</th>
                        <th style="width: 3.5%;">Traslados</th>
                        <th style="width: 4%;">Suspendidos</th>
                        <th style="width: 3.5%;">Egresados</th>
                        <th style="width: 5.5%;">Generación</th>
                        <th style="width: 12%;">Maestro</th>
                        <th style="width: 14%;">Director</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bloque['filas'] as $fila)
                        <tr>
                            <td>{{ $fila['regional'] }}</td>
                            <td>{{ $fila['zona'] }}</td>
                            <td><b>{{ $fila['cct'] }}</b></td>
                            <td class="left"><b>{{ $fila['nombre_ct'] }}</b></td>
                            <td>{{ $fila['nivel'] }}</td>
                            <td>{{ $fila['turno'] }}</td>
                            <td><b>{{ $fila['grado'] }}°</b></td>
                            <td>{{ $fila['semestre'] }}</td>
                            <td><b>{{ $fila['grupo'] }}</b></td>
                            <td>{{ $fila['hombres'] }}</td>
                            <td>{{ $fila['mujeres'] }}</td>
                            <td><b>{{ $fila['total_historico'] }}</b></td>
                            <td class="active">{{ $fila['activos'] }}</td>
                            <td class="inactive">{{ $fila['inactivos'] }}</td>
                            <td class="drop">{{ $fila['bajas'] }}</td>
                            <td class="transfer">{{ $fila['traslados'] }}</td>
                            <td class="suspended">{{ $fila['suspendidos'] }}</td>
                            <td class="graduated">{{ $fila['egresados'] }}</td>
                            <td><b>{{ $fila['generacion'] }}</b></td>
                            <td class="left">{{ $fila['maestro'] }}</td>
                            <td class="left">{{ $fila['director'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="9">TOTALES</td>
                        <td>{{ $bloque['totales']['hombres'] }}</td>
                        <td>{{ $bloque['totales']['mujeres'] }}</td>
                        <td>{{ $bloque['totales']['total_historico'] }}</td>
                        <td class="active">{{ $bloque['totales']['activos'] }}</td>
                        <td class="inactive">{{ $bloque['totales']['inactivos'] }}</td>
                        <td class="drop">{{ $bloque['totales']['bajas'] }}</td>
                        <td class="transfer">{{ $bloque['totales']['traslados'] }}</td>
                        <td class="suspended">{{ $bloque['totales']['suspendidos'] }}</td>
                        <td class="graduated">{{ $bloque['totales']['egresados'] }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @empty
        <div class="empty">No hay información disponible para los filtros seleccionados.</div>
    @endforelse

    <div class="footer">
        Centro Universitario Moctezuma · Reporte administrativo confidencial · La información conserva matrículas, generaciones y trayectoria histórica.
    </div>
</body>
</html>
