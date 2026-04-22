<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        @page {
            margin: 22px 18px 18px 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
        }

        .header {
            width: 100%;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 14px;
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
            max-width: 58px;
            max-height: 58px;
        }

        .titulo-wrap {
            text-align: center;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px 0;
        }

        .subtitle {
            font-size: 10px;
            color: #64748b;
            margin: 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .info-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
        }

        .info-label {
            width: 120px;
            font-weight: bold;
            background: #e2e8f0;
            color: #0f172a;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin: 12px 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabla th {
            background: #0f172a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #ffffff;
            padding: 6px 4px;
        }

        .tabla td {
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            font-size: 9px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .promedio {
            font-weight: bold;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .footer {
            margin-top: 14px;
            font-size: 9px;
            color: #64748b;
            text-align: right;
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
                    {{ \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') }} -
                    {{ \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') }}
                @else
                    —
                @endif
            </td>
            @if ($esBachillerato)
                <td class="info-label">Ciclo escolar</td>
                <td>{{ $periodo?->cicloEscolar?->inicio_anio ?? '—' }}-{{ $periodo?->cicloEscolar?->fin_anio ?? '—' }}
                </td>
            @endif
        </tr>
        @if (!$esBachillerato)
            <tr>
                <td class="info-label">Ciclo escolar</td>
                <td>{{ $periodo?->cicloEscolar?->inicio_anio ?? '—' }}-{{ $periodo?->cicloEscolar?->fin_anio ?? '—' }}
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

    <div class="section-title">Listado de calificaciones</div>

    <table class="tabla">
        <thead>
            <tr>
                <th style="width: 70px;">MATRÍCULA</th>
                <th style="width: 180px;">ALUMNO</th>
                <th style="width: 70px;">GRADO</th>

                @if ($esBachillerato)
                    <th style="width: 50px;">SEM.</th>
                @endif

                <th style="width: 55px;">GRUPO</th>

                @foreach ($materias as $materia)
                    <th>{{ mb_strtoupper($materia['materia']) }}</th>
                @endforeach

                <th style="width: 55px;">PROM.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inscripciones as $fila)
                <tr>
                    <td class="text-center">{{ $fila['matricula'] }}</td>
                    <td class="text-left">{{ $fila['alumno'] }}</td>
                    <td class="text-center">{{ $fila['grado'] }}</td>

                    @if ($esBachillerato)
                        <td class="text-center">{{ $fila['semestre'] }}</td>
                    @endif

                    <td class="text-center">{{ $fila['grupo'] }}</td>

                    @foreach ($materias as $materia)
                        @php
                            $clave = $fila['inscripcion_id'] . '-' . $materia['id'];
                        @endphp
                        <td class="text-center">{{ $calificaciones[$clave] ?? '' }}</td>
                    @endforeach

                    <td class="text-center promedio">
                        {{ $promedios[$fila['inscripcion_id']] ?? '—' }}
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

    <div class="footer">
        Generado el {{ \Carbon\Carbon::parse($fecha_impresion)->format('d/m/Y h:i A') }}
    </div>
</body>

</html>
