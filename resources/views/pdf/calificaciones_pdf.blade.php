<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>

    <style>
        @page {
            margin: 18px 16px 0px 16px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #334155;
            background: #ffffff;
        }

        .header {
            width: 100%;
            border-bottom: 3px solid #93c5fd;
            padding-bottom: 9px;
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            width: 70px;
            text-align: center;
        }

        .logo img {
            max-width: 56px;
            max-height: 56px;
        }

        .titulo-wrap {
            text-align: center;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px 0;
            letter-spacing: .5px;
        }

        .subtitle {
            font-size: 10px;
            color: #64748b;
            margin: 0;
        }

        .pill {
            display: inline-block;
            margin-top: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 8px;
            font-weight: bold;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-table td {
            border: 1px solid #dbeafe;
            padding: 5px 7px;
            vertical-align: top;
        }

        .info-label {
            width: 110px;
            font-weight: bold;
            background: #eff6ff;
            color: #1e3a8a;
        }

        .cards-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin: 4px 0 10px 0;
        }

        .card {
            border-radius: 12px;
            padding: 9px 10px;
            border: 1px solid #e2e8f0;
        }

        .card-blue {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .card-green {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .card-yellow {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .card-red {
            background: #fff1f2;
            border-color: #fecdd3;
        }

        .card-purple {
            background: #f5f3ff;
            border-color: #ddd6fe;
        }

        .card-title {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 3px;
        }

        .card-value {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin: 10px 0 6px 0;
            padding: 6px 8px;
            border-radius: 10px;
            background: #f8fafc;
            border-left: 4px solid #93c5fd;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabla th {
            background: #bfdbfe;
            color: #1e3a8a;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #ffffff;
            /* padding: 5px 3px; */
        }

        .tabla th:nth-child(2n) {
            background: #c7d2fe;
            color: #312e81;
        }

        .tabla td {
            border: 1px solid #e2e8f0;
            /* padding: 4px 3px; */
            font-size: 8px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .tabla tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .matricula {
            background: #f0f9ff;
            font-weight: bold;
            color: #075985;
        }

        .alumno {
            color: #0f172a;
            /* font-weight: bold; */
        }

        .calificacion {
            font-weight: bold;
            border-radius: 6px;
        }

        .cal-buena {
            background: #dcfce7;
            color: #166534;
        }

        .cal-regular {
            background: #fef3c7;
            color: #92400e;
        }

        .cal-baja {
            background: #ffe4e6;
            color: #be123c;
        }

        .cal-especial {
            background: #ede9fe;
            color: #6d28d9;
        }

        .promedio {
            font-weight: bold;
            background: #dbeafe;
            color: #1d4ed8;
        }

        .promedio-bueno {
            background: #dcfce7;
            color: #166534;
        }

        .promedio-regular {
            background: #fef3c7;
            color: #92400e;
        }

        .promedio-bajo {
            background: #ffe4e6;
            color: #be123c;
        }

        .mini-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .mini-table th {
            background: #e0f2fe;
            color: #075985;
            border: 1px solid #ffffff;
            padding: 5px;
            font-size: 8px;
            text-align: left;
        }

        .mini-table td {
            border: 1px solid #e2e8f0;
            padding: 5px;
            font-size: 8px;
        }

        .chart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .chart-table td {
            padding: 4px 5px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .chart-label {
            width: 160px;
            font-size: 8px;
            font-weight: bold;
            color: #334155;
        }

        .chart-value {
            width: 35px;
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }

        .bar-bg {
            height: 10px;
            background: #f1f5f9;
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .bar {
            height: 10px;
            border-radius: 999px;
        }

        .bar-blue {
            background: #93c5fd;
        }

        .bar-green {
            background: #86efac;
        }

        .bar-yellow {
            background: #fde68a;
        }

        .bar-red {
            background: #fda4af;
        }

        .bar-purple {
            background: #c4b5fd;
        }

        .footer {
            margin-top: 12px;
            font-size: 8px;
            color: #64748b;
            text-align: right;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo">
                    @if ($logo_izquierdo)
                        <img src="{{ $logo_izquierdo }}" alt="Logo izquierdo">
                    @endif
                </td>

                <td class="titulo-wrap">
                    <p class="title">{{ $titulo }}</p>
                    <p class="subtitle">
                        {{ $escuela?->nombre ?? 'Centro escolar' }}
                    </p>

                    <span class="pill">
                        {{ $nivel->nombre ?? 'Nivel' }} ·
                        {{ $grado->nombre ?? 'Grado' }} ·
                        Grupo {{ $grupo->nombre ?? '—' }}
                        @if ($esBachillerato)
                            · Semestre {{ $semestre?->numero ?? '—' }}
                        @endif
                    </span>
                </td>

                <td class="logo">
                    @if ($logo_derecho)
                        <img src="{{ $logo_derecho }}" alt="Logo derecho">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Nivel</td>
            <td>{{ $nivel->nombre ?? '—' }}</td>

            <td class="info-label">Grado</td>
            <td>{{ $grado->nombre ?? '—' }}</td>

            @if ($esBachillerato)
                <td class="info-label">Semestre</td>
                <td>{{ $semestre?->numero ?? '—' }}</td>
            @endif
        </tr>

        <tr>
            <td class="info-label">Grupo</td>
            <td>{{ $grupo->nombre ?? '—' }}</td>

            <td class="info-label">Periodo</td>
            <td>
                @if ($periodo)
                    {{ \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') }}
                    -
                    {{ \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') }}
                @else
                    —
                @endif
            </td>

            @if ($esBachillerato)
                <td class="info-label">Ciclo escolar</td>
                <td>
                    {{ $periodo?->cicloEscolar?->inicio_anio ?? '—' }}-{{ $periodo?->cicloEscolar?->fin_anio ?? '—' }}
                </td>
            @endif
        </tr>

        @if (!$esBachillerato)
            <tr>
                <td class="info-label">Ciclo escolar</td>
                <td>
                    {{ $periodo?->cicloEscolar?->inicio_anio ?? '—' }}-{{ $periodo?->cicloEscolar?->fin_anio ?? '—' }}
                </td>

                <td class="info-label">Búsqueda</td>
                <td colspan="3">{{ $busqueda !== '' ? $busqueda : 'Sin filtro' }}</td>
            </tr>
        @else
            <tr>
                <td class="info-label">Búsqueda</td>
                <td colspan="5">{{ $busqueda !== '' ? $busqueda : 'Sin filtro' }}</td>
            </tr>
        @endif
    </table>

    <table class="cards-table">
        <tr>
            <td class="card card-blue">
                <div class="card-title">Promedio global</div>
                <div class="card-value">{{ $promedioGeneralGrupo ?? '—' }}</div>
            </td>

            <td class="card card-green">
                <div class="card-title">Aprobación</div>
                <div class="card-value">{{ $porcentajeAprobacion ?? 0 }}%</div>
            </td>

            <td class="card card-yellow">
                <div class="card-title">Total alumnos</div>
                <div class="card-value">{{ $totalAlumnos ?? count($inscripciones) }}</div>
            </td>

            <td class="card card-purple">
                <div class="card-title">Aprobados</div>
                <div class="card-value">{{ $totalAprobados ?? 0 }}</div>
            </td>

            <td class="card card-red">
                <div class="card-title">Reprobados</div>
                <div class="card-value">{{ $totalReprobados ?? 0 }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Listado de calificaciones</div>

    <table class="tabla">
        <thead>
            <tr>
                <th style="width: 450px;">ALUMNO</th>

                @if ($esBachillerato)
                    <th style="width: 42px;">SEM.</th>
                @endif


                @foreach ($materias as $materia)
                    <th>{{ mb_strtoupper($materia['materia']) }}</th>
                @endforeach

                <th style="width: 58px;">PROM.</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($inscripciones as $fila)
                <tr>
                    <td class="text-left alumno">{{ $fila['alumno'] }}</td>

                    @if ($esBachillerato)
                        <td class="text-center">{{ $fila['semestre'] }}</td>
                    @endif


                    @foreach ($materias as $materia)
                        @php
                            $clave = $fila['inscripcion_id'] . '-' . $materia['id'];
                            $valor = $calificaciones[$clave] ?? '';
                            $valorNormalizado = strtoupper(trim((string) $valor));

                            $claseCalificacion = '';

                            if ($valorNormalizado !== '' && is_numeric($valorNormalizado)) {
                                $numero = (float) $valorNormalizado;

                                if ($numero < 6) {
                                    $claseCalificacion = 'cal-baja';
                                } elseif ($numero < 8) {
                                    $claseCalificacion = 'cal-regular';
                                } else {
                                    $claseCalificacion = 'cal-buena';
                                }
                            } elseif ($valorNormalizado !== '') {
                                $claseCalificacion = 'cal-especial';
                            }
                        @endphp

                        <td class="text-center calificacion {{ $claseCalificacion }}">
                            {{ $valorNormalizado }}
                        </td>
                    @endforeach

                    @php
                        $promedioAlumno = $promedios[$fila['inscripcion_id']] ?? '—';
                        $clasePromedio = 'promedio';

                        if (is_numeric($promedioAlumno)) {
                            $promedioNumero = (float) $promedioAlumno;

                            if ($promedioNumero < 6) {
                                $clasePromedio .= ' promedio-bajo';
                            } elseif ($promedioNumero < 8) {
                                $clasePromedio .= ' promedio-regular';
                            } else {
                                $clasePromedio .= ' promedio-bueno';
                            }
                        }
                    @endphp

                    <td class="text-center {{ $clasePromedio }}">
                        {{ $promedioAlumno }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $esBachillerato ? 6 + count($materias) : 5 + count($materias) }}"
                        class="text-center">
                        No hay registros para mostrar con los filtros actuales.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="page-break-after: always;"></div>

    <div class="section-title">Promedio por materia</div>

    <table class="mini-table">
        <thead>
            <tr>
                <th style="width: 45%;">Materia</th>
                <th style="width: 18%;">Promedio</th>
                <th style="width: 18%;">Capturadas</th>
                <th style="width: 19%;">Estado</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($promediosPorMateria ?? [] as $item)
                @php
                    $promedioMateria = $item['promedio'] ?? '—';
                    $estadoMateria = 'Sin datos';

                    if (is_numeric($promedioMateria)) {
                        $estadoMateria = (float) $promedioMateria >= 6 ? 'Aprobatorio' : 'En riesgo';
                    }
                @endphp

                <tr>
                    <td>{{ $item['materia'] }}</td>
                    <td class="text-center"><strong>{{ $promedioMateria }}</strong></td>
                    <td class="text-center">{{ $item['total_capturadas'] ?? 0 }}</td>
                    <td class="text-center">{{ $estadoMateria }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No hay materias para promediar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Periodos por materia</div>

    <table class="mini-table">
        <thead>
            <tr>
                <th>Materia</th>
                <th>Tipo</th>
                <th>Periodo</th>
                <th>Fecha inicio</th>
                <th>Fecha fin</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($periodosPorMateria ?? [] as $item)
                <tr>
                    <td>{{ $item['materia'] }}</td>
                    <td>{{ $item['tipo'] }}</td>
                    <td>{{ $item['periodo'] }}</td>
                    <td class="text-center">{{ $item['fecha_inicio'] }}</td>
                    <td class="text-center">{{ $item['fecha_fin'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No hay periodos por materia para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="page-break-after: always;"></div>

    <div class="section-title">Gráfica de promedios por materia</div>

    <table class="chart-table">
        <tbody>
            @forelse ($promediosPorMateria ?? [] as $index => $item)
                @php
                    $promedioMateria = $item['promedio'] ?? '—';
                    $porcentaje = $item['porcentaje'] ?? 0;

                    $barClass = 'bar-blue';

                    if (is_numeric($promedioMateria)) {
                        $numeroPromedio = (float) $promedioMateria;

                        if ($numeroPromedio < 6) {
                            $barClass = 'bar-red';
                        } elseif ($numeroPromedio < 8) {
                            $barClass = 'bar-yellow';
                        } else {
                            $barClass = 'bar-green';
                        }
                    } else {
                        $barClass = 'bar-purple';
                    }
                @endphp

                <tr>
                    <td class="chart-label">{{ $item['materia'] }}</td>
                    <td>
                        <div class="bar-bg">
                            <div class="bar {{ $barClass }}" style="width: {{ $porcentaje }}%;"></div>
                        </div>
                    </td>
                    <td class="chart-value">{{ $promedioMateria }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center">No hay información suficiente para graficar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Gráfica de promedio global</div>

    <table class="chart-table">
        <tr>
            <td class="chart-label">Promedio general del grupo</td>
            <td>
                <div class="bar-bg">
                    <div class="bar bar-blue" style="width: {{ $porcentajePromedioGeneral ?? 0 }}%;"></div>
                </div>
            </td>
            <td class="chart-value">{{ $promedioGeneralGrupo ?? '—' }}</td>
        </tr>

        <tr>
            <td class="chart-label">Porcentaje de aprobación</td>
            <td>
                <div class="bar-bg">
                    <div class="bar bar-green" style="width: {{ $porcentajeAprobacion ?? 0 }}%;"></div>
                </div>
            </td>
            <td class="chart-value">{{ $porcentajeAprobacion ?? 0 }}%</td>
        </tr>
    </table>

    <div class="footer">
        Generado el {{ \Carbon\Carbon::parse($fecha_impresion)->format('d/m/Y h:i A') }}
    </div>
</body>

</html>
