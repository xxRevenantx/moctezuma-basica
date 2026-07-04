<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Promedio anual de bachillerato</title>
    <style>
        @page {
            size: letter landscape;
            margin: 18px 22px 20px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #172033;
            font-size: 8px;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .header td {
            vertical-align: middle;
        }

        .brand {
            font-size: 15px;
            font-weight: bold;
            color: #006492;
            line-height: 1.15;
        }

        .subtitle {
            margin-top: 3px;
            font-size: 10px;
            font-weight: bold;
            color: #88AC2E;
        }

        .meta {
            text-align: right;
            font-size: 7.5px;
            line-height: 1.45;
            color: #475569;
        }

        .rule {
            height: 4px;
            background: #006492;
            border-bottom: 2px solid #88AC2E;
            margin-bottom: 8px;
        }

        .context {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px 0;
            margin: 0 -5px 8px;
        }

        .context td {
            width: 25%;
            padding: 6px 8px;
            background: #f4f8fb;
            border: 1px solid #d8e3eb;
            border-radius: 6px;
        }

        .context .label {
            display: block;
            font-size: 6.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }

        .context .value {
            display: block;
            margin-top: 2px;
            font-size: 9px;
            font-weight: bold;
            color: #0f2747;
        }

        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px 0;
            margin: 0 -5px 9px;
        }

        .summary td {
            width: 20%;
            padding: 6px 7px;
            text-align: center;
            border: 1px solid #d8e3eb;
            border-radius: 6px;
        }

        .summary .number {
            display: block;
            font-size: 14px;
            font-weight: bold;
            color: #0f2747;
        }

        .summary .caption {
            display: block;
            margin-top: 2px;
            font-size: 6.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .main-table th {
            padding: 5px 4px;
            background: #006492;
            color: #fff;
            border: 1px solid #0a5577;
            font-size: 6.8px;
            text-transform: uppercase;
        }

        .main-table td {
            padding: 4px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        .main-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        .center {
            text-align: center;
        }

        .student {
            font-weight: bold;
            color: #0f2747;
        }

        .place {
            font-weight: bold;
            color: #006492;
        }

        .annual {
            font-weight: bold;
            color: #0f2747;
            font-size: 9px;
        }

        .status {
            font-size: 6.8px;
            font-weight: bold;
        }

        .diagnostics {
            margin-top: 8px;
            padding: 7px 9px;
            border: 1px solid #f0c36a;
            background: #fff9e9;
            color: #7c4a03;
            border-radius: 6px;
            line-height: 1.4;
        }

        .formula {
            margin-top: 7px;
            padding-top: 5px;
            border-top: 1px solid #d8e3eb;
            color: #64748b;
            font-size: 6.8px;
            line-height: 1.4;
        }
    </style>
</head>

<body>
    @php
        $numeros = data_get($reporte, 'contexto.numeros_semestre', []);
        $semestreInicial = $numeros[0] ?? '—';
        $semestreFinal = $numeros[1] ?? '—';
        $resumen = $reporte['resumen'] ?? [];
        $diagnostico = $reporte['diagnostico'] ?? [];
    @endphp

    <table class="header">
        <tr>
            <td>
                <div class="brand">CENTRO UNIVERSITARIO MOCTEZUMA</div>
                <div class="subtitle">PROMEDIO ANUAL DE BACHILLERATO</div>
            </td>
            <td class="meta">
                C.C.T. {{ $nivel->cct ?? '12PBH0071R' }}<br>
                Francisco I. Madero Ote. #800, Col. Esquipulas<br>
                Cd. Altamirano, Guerrero
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="context">
        <tr>
            <td>
                <span class="label">Generación</span>
                <span class="value">{{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}</span>
            </td>
            <td>
                <span class="label">Ciclo escolar</span>
                <span class="value">{{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}</span>
            </td>
            <td>
                <span class="label">Año académico</span>
                <span class="value">{{ data_get($reporte, 'contexto.nombre_anio', '—') }}</span>
            </td>
            <td>
                <span class="label">Semestres integrados</span>
                <span class="value">{{ $semestreInicial }} y {{ $semestreFinal }}</span>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <span class="number">{{ $resumen['total_alumnos'] ?? 0 }}</span>
                <span class="caption">Alumnos</span>
            </td>
            <td>
                <span class="number">{{ $resumen['promedio_general'] ?? '—' }}</span>
                <span class="caption">Promedio anual general</span>
            </td>
            <td>
                <span class="number">{{ $diagnostico['completos'] ?? 0 }}</span>
                <span class="caption">Completos</span>
            </td>
            <td>
                <span class="number">{{ $diagnostico['incompletos'] ?? 0 }}</span>
                <span class="caption">Incompletos</span>
            </td>
            <td>
                <span class="number">{{ $resumen['con_reconocimiento'] ?? 0 }}</span>
                <span class="caption">Con reconocimiento</span>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 4%;">N.º</th>
                <th style="width: 8%;">Lugar</th>
                <th style="width: 28%;">Alumno</th>
                <th style="width: 13%;">Matrícula</th>
                <th style="width: 10%;">Sem. {{ $semestreInicial }}</th>
                <th style="width: 10%;">Sem. {{ $semestreFinal }}</th>
                <th style="width: 11%;">Promedio anual</th>
                <th style="width: 16%;">Situación</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reporte['alumnos'] as $indice => $fila)
                <tr>
                    <td class="center">{{ $indice + 1 }}</td>
                    <td class="center place">{{ $fila['texto_lugar'] ?? 'Pendiente' }}</td>
                    <td class="student">{{ $fila['alumno'] ?? 'Sin nombre' }}</td>
                    <td class="center">{{ $fila['matricula'] ?? '—' }}</td>
                    <td class="center">
                        {{ \App\Support\PromedioExcel::formatear(data_get($fila, 'periodos.1'), 2, '—') }}
                    </td>
                    <td class="center">
                        {{ \App\Support\PromedioExcel::formatear(data_get($fila, 'periodos.2'), 2, '—') }}
                    </td>
                    <td class="center annual">
                        {{ \App\Support\PromedioExcel::formatear($fila['promedio_final'] ?? $fila['promedio_provisional'] ?? null, 2, '—') }}
                        @if (!($fila['completo'] ?? false))
                            <small>PROV.</small>
                        @endif
                    </td>
                    <td class="center status">{{ $fila['estatus'] ?? 'Pendiente' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (!empty($diagnostico['alertas']))
        <div class="diagnostics">
            <strong>Diagnóstico administrativo:</strong>
            {{ implode(' ', $diagnostico['alertas']) }}
        </div>
    @endif

    <div class="formula">
        <strong>Fórmula institucional:</strong>
        cada materia calificable se obtiene con (P1 + P2) ÷ 2; el promedio semestral se calcula con los
        promedios finales de las materias calificables; el promedio anual se obtiene con
        (promedio del semestre {{ $semestreInicial }} + promedio del semestre {{ $semestreFinal }}) ÷ 2.
        Ambos semestres representan el 50 %. Los alumnos incompletos o con materias no acreditadas no participan
        en el ranking anual ni generan reconocimiento. Documento emitido el {{ $fecha_documento }}.
    </div>
</body>

</html>
