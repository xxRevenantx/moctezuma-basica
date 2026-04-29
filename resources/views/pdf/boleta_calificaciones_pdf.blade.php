<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>

    <style>
        @page {
            margin: 20px 50px 18px;
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
            font-family: 'ARIAL', sans-serif;
            font-size: 10px;
            color: #334155;
            background: #ffffff;
        }

        .header {
            border-bottom: 3px solid #93c5fd;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            width: 100px;
            text-align: center;
        }

        .logo img {
            width: 90px;

        }

        .title-wrap {
            text-align: center;
        }

        .title {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: .5px;
        }

        .subtitle {
            margin: 4px 0 0 0;
            font-size: 10px;
            color: #64748b;
        }

        .pill {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 8px;
            font-weight: bold;
        }

        .student-card {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .student-card td {
            border: 1px solid #dbeafe;
            padding: 7px 8px;
        }

        .label {
            width: 105px;
            background: #eff6ff;
            color: #1e3a8a;
            font-weight: bold;
        }

        .value {
            color: #0f172a;
            font-weight: bold;
        }

        .cards {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin: 4px 0 10px 0;
        }

        .card {
            border-radius: 14px;
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
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
        }

        .card-value {
            margin-top: 3px;
            font-size: 17px;
            color: #0f172a;
            font-weight: bold;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin: 10px 0 6px 0;
            padding: 7px 9px;
            border-radius: 10px;
            background: #f8fafc;
            border-left: 4px solid #93c5fd;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla th {
            background: #bfdbfe;
            color: #1e3a8a;
            border: 1px solid #ffffff;
            padding: 7px 5px;
            font-size: 13px;
            text-align: center;
            font-weight: bold;
        }

        .tabla td {
            border: 1px solid #e2e8f0;
            padding: 6px 5px;
            font-size: 12px;
            vertical-align: middle;
        }

        .tabla tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .materia {
            font-weight: bold;
            color: #0f172a;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-ok {
            background: #dcfce7;
            color: #166534;
        }

        .badge-regular {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-risk {
            background: #ffe4e6;
            color: #be123c;
        }

        .badge-special {
            background: #ede9fe;
            color: #6d28d9;
        }

        .badge-empty {
            background: #f1f5f9;
            color: #475569;
        }

        .bar-bg {
            width: 100%;
            height: 9px;
            background: #f1f5f9;
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .bar {
            height: 9px;
            border-radius: 999px;
        }

        .bar-ok {
            background: #86efac;
        }

        .bar-regular {
            background: #fde68a;
        }

        .bar-risk {
            background: #fda4af;
        }

        .bar-special {
            background: #c4b5fd;
        }

        .global-box {
            margin-top: 10px;
            border: 1px solid #bfdbfe;
            border-radius: 14px;
            padding: 10px;
            background: #eff6ff;
        }

        .global-title {
            font-size: 10px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 6px;
        }

        .signatures {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            padding-top: 28px;
            color: #334155;
        }

        .line {
            border-top: 1px solid #94a3b8;
            width: 70%;
            margin: 0 auto 5px auto;
        }

        .footer {
            margin-top: 14px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            text-align: right;
            font-size: 8px;
            color: #64748b;
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

                <td class="title-wrap">
                    <p class="title">{{ $titulo }}</p>
                    <p class="subtitle">{{ $escuela?->nombre ?? 'Centro escolar' }}</p>
                    <span class="pill">
                        {{ $nivel->nombre ?? 'Nivel' }} ·
                        {{ $nombrePeriodo }} ·
                        Ciclo {{ $cicloEscolarTexto }}
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



    <table class="student-card">
        <tr>
            <td class="label">Alumno</td>
            <td class="value" colspan="3">{{ $nombreAlumno }}</td>
        </tr>

        <tr>
            <td class="label">Matrícula</td>
            <td>{{ $inscripcion->matricula ?? '—' }}</td>

            <td class="label">Nivel</td>
            <td>{{ $nivel->nombre ?? '—' }}</td>
        </tr>

        <tr>
            <td class="label">Grado</td>
            <td>{{ $grado->nombre ?? '—' }}</td>

            <td class="label">Grupo</td>
            <td>{{ $grupo->nombre ?? '—' }}</td>
        </tr>



        @if ($esBachillerato)
            <tr>
                <td class="label">Semestre</td>
                <td>{{ $semestre?->numero ?? '—' }}</td>

                <td class="label">Parcial</td>
                <td>{{ $nombrePeriodo }}</td>
            </tr>
        @else
            <tr>
                <td class="label">Periodo</td>
                <td>{{ $nombrePeriodo }}</td>

                <td class="label">Ciclo escolar</td>
                <td>{{ $cicloEscolarTexto }}</td>
            </tr>
        @endif
    </table>

    <table class="cards">
        <tr>
            <td class="card card-blue">
                <div class="card-title">Promedio</div>
                <div class="card-value">{{ $promedio }}</div>
            </td>

            <td class="card card-green">
                <div class="card-title">Estado</div>
                <div class="card-value">{{ $estadoPromedio }}</div>
            </td>

            <td class="card card-yellow">
                <div class="card-title">Captura</div>
                <div class="card-value">{{ $porcentajeCaptura }}%</div>
            </td>

            <td class="card card-purple">
                <div class="card-title">Especiales</div>
                <div class="card-value">{{ $especiales }}</div>
            </td>

            <td class="card card-red">
                <div class="card-title">En riesgo</div>
                <div class="card-value">{{ $reprobadas }}</div>
            </td>
        </tr>
    </table>



    <table class="tabla">
        <thead>
            <tr>
                @if ($esBachillerato)
                    <th style="width: 13%;">Clave</th>
                @endif
                <th style="width: 25%;">Materia</th>
                <th style="width: 13%;">Calificación</th>
                <th style="width: 15%;">Estado</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($filasMaterias as $fila)
                @php
                    $badgeClass = 'badge-empty';
                    $barClass = 'bar-special';

                    if ($fila['estado'] === 'Aprobado') {
                        $badgeClass = 'badge-ok';
                        $barClass = 'bar-ok';
                    } elseif ($fila['estado'] === 'Regular') {
                        $badgeClass = 'badge-regular';
                        $barClass = 'bar-regular';
                    } elseif ($fila['estado'] === 'En riesgo') {
                        $badgeClass = 'badge-risk';
                        $barClass = 'bar-risk';
                    } elseif ($fila['estado'] === 'Especial') {
                        $badgeClass = 'badge-special';
                        $barClass = 'bar-special';
                    }
                @endphp

                <tr>
                    @if ($esBachillerato)
                        <td class="text-center">{{ $fila['clave'] }}</td>
                    @endif
                    <td class="materia">
                        {{ $fila['materia'] }}
                        @if ($fila['extra'])
                            <span style="font-size: 7px; color:#64748b;">(Extra)</span>
                        @endif
                    </td>

                    <td class="text-center">
                        <strong>{{ $fila['calificacion'] }}</strong>
                    </td>

                    <td class="text-center">
                        <span class="badge {{ $badgeClass }}">{{ $fila['estado'] }}</span>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">
                        No hay materias para mostrar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                Control escolar
            </td>

            <td>
                <div class="line"></div>
                Padre, madre o tutor
            </td>
        </tr>
    </table>

    <div class="footer">
        Generado el {{ \Carbon\Carbon::parse($fecha_impresion)->format('d/m/Y h:i A') }}
    </div>
</body>

</html>
