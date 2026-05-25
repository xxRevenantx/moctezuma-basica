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

        @font-face {
            font-family: 'calibri';
            font-style: normal;
            src: url('{{ storage_path('fonts/calibri-regular.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'calibri';
            font-style: normal;
            font-weight: 700;
            src: url('{{ storage_path('fonts/calibri-bold.ttf') }}') format('truetype');
        }

        body {
            font-family: 'calibri';
            font-size: 10px;
            color: #334155;
            background: #ffffff;
        }

        .encabezado {
            width: 100%;
            border-collapse: collapse;
        }

        .encabezado td {
            border: none;
            vertical-align: top;
        }

        .logo-izquierdo {
            width: 92px;
            text-align: left;
        }

        .logo-izquierdo img {
            width: 92px;
            object-fit: contain;
        }

        .logo-derecho {
            width: 100px;
            text-align: right;
        }

        .logo-derecho img {
            width: 100px;
            object-fit: contain;
        }

        .titulo-centro {
            text-align: center;
        }

        .nombre-escuela {
            display: inline-block;
            color: #4b5563;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-top: 1px solid #9ca3af;
            border-bottom: 1px solid #9ca3af;
            padding: 0 10px 2px 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .titulo-lista {
            font-size: 20px;
            font-weight: 700;
            margin-top: 2px;
            color: #111827;
            text-transform: uppercase;
        }

        .direccion {
            width: 500px;
            margin: 4px auto 0 auto;
            font-size: 11px;
            line-height: 1.25;
            color: #334155;
            text-align: center;
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
            margin-top: 10px;
            font-size: 13px;
        }

        .student-card td {
            border: 1px solid #dbeafe;
            padding: 2px 8px;
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
            padding: 3px 10px;
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
            padding: 3px 5px;
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
            font-size: 10px;
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

        /*
         * Leyenda de calificaciones cualitativas.
         * Se usa para explicar AC, ED y RA en la boleta.
         */
        .leyenda-cualitativa {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
            border: 1px solid #cbd5e1;
        }

        .leyenda-cualitativa td {
            border: 1px solid #cbd5e1;
            padding: 2px 2px;
            text-align: center;
        }

        .leyenda-titulo {
            background: #eff6ff;
            color: #1e3a8a;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 10px;
        }

        .leyenda-nota {
            display: block;
            margin-top: 2px;
            font-size: 8.5px;
            font-weight: normal;
            color: #475569;
            text-transform: none;
            letter-spacing: 0;
        }

        .leyenda-clave {
            display: inline-block;
            min-width: 24px;
            padding: 3px 3px;
            border-radius: 999px;
            font-weight: bold;
            font-size: 10px;
        }

        .leyenda-ac {
            background: #bbf7d0;
            color: #166534;
        }

        .leyenda-ed {
            background: #fde68a;
            color: #92400e;
        }

        .leyenda-ra {
            background: #fecaca;
            color: #991b1b;
        }

        .firmas {
            width: 100%;
            margin-top: 20px;
            font-size: 15px;
            color: #000000;
        }

        .line {
            border-top: 1px solid #94a3b8;
            width: 70%;
            margin: 0 auto 5px auto;
        }

        .footer {
            position: fixed;
            left: 18px;
            right: 18px;
            bottom: 5px;
            text-align: center;
            font-size: 8px;
            color: #475569;
            border-top: 1px solid #94a3b8;
            padding-top: 3px;
        }

        .footer p {
            margin: 0;
            line-height: 1.2;
        }
    </style>
</head>

<body>
    <div class="header">

        <table class="encabezado">
            <tr>
                <td class="logo-izquierdo">
                    @if (!empty($logo_izquierdo))
                        <img src="{{ $logo_izquierdo }}" alt="">
                    @endif
                </td>

                <td class="titulo-centro">
                    <div class="nombre-escuela">
                        {{ strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA') }}
                    </div>

                    <div class="titulo-lista">
                        BOLETA DE CALIFICACIONES<br>
                        C.C.T. {{ $nivel->cct ?? '—' }}
                    </div>
                </td>

                <td class="logo-derecho">
                    @if (!empty($logo_derecho))
                        <img src="{{ $logo_derecho }}" alt="">
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
            <td>{{ $grupo->asignacionGrupo->nombre ?? '—' }}</td>
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
                    <td colspan="{{ $esBachillerato ? 4 : 3 }}" class="text-center">
                        No hay materias para mostrar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @php
        /*
         * Se muestra la leyenda cuando existan calificaciones cualitativas.
         * También se puede dejar siempre visible en preescolar o primaria.
         */
        $mostrarLeyendaCualitativa = collect($filasMaterias)->contains(function ($fila) {
            return in_array(mb_strtoupper((string) ($fila['calificacion'] ?? '')), ['AC', 'ED', 'RA'], true);
        });

        if (!$esBachillerato && !$esSecundaria) {
            $mostrarLeyendaCualitativa = true;
        }
    @endphp

    @if ($mostrarLeyendaCualitativa)
        <table class="leyenda-cualitativa">
            <tr>
                <td colspan="3" class="leyenda-titulo">
                    Leyenda de evaluación cualitativa
                    <span class="leyenda-nota">
                        Las claves AC, ED y RA se utilizan para materias evaluadas de forma cualitativa.
                    </span>
                </td>
            </tr>

            <tr>
                <td style="background: #f0fdf4;">
                    <span class="leyenda-clave leyenda-ac">AC * Acreditado</span>

                </td>

                <td style="background: #fffbeb;">
                    <span class="leyenda-clave leyenda-ed">ED * En desarrollo</span>

                </td>

                <td style="background: #fef2f2;">
                    <span class="leyenda-clave leyenda-ra">RA *Requiere apoyo</span>

                </td>
            </tr>
        </table>
    @endif

    @if (!$esBachillerato && !$esSecundaria)
        <table class="firmas">
            <tr>
                <td style="width: 50%; padding-top: 60px; text-align: center;">
                    <u>{{ mb_strtoupper(trim((optional($docente)->titulo ?? '') . ' ' . (optional($docente)->nombre ?? '') . ' ' . (optional($docente)->apellido_paterno ?? '') . ' ' . (optional($docente)->apellido_materno ?? '')) ?: '____________________________') }}</u><br>

                    @if (optional($docente)->genero === 'M')
                        Firma de la profesora de grupo
                    @else
                        Firma de profesor de grupo
                    @endif
                </td>

                <td style="width: 50%; padding-top: 60px; text-align: center;">
                    <u>{{ mb_strtoupper(trim((optional($director->director)->titulo ?? '') . ' ' . (optional($director->director)->nombre ?? '') . ' ' . (optional($director->director)->apellido_paterno ?? '') . ' ' . (optional($director->director)->apellido_materno ?? '')) ?: '____________________________') }}</u><br>

                    @if ($director->director->genero === 'F')
                        Firma de la directora de la escuela
                    @else
                        Firma del director de la escuela
                    @endif
                </td>
            </tr>
        </table>
    @else
        <table class="firmas">
            <tr>
                <td style="width: 100%; padding-top: 60px; text-align: center;">
                    <u>{{ mb_strtoupper(trim((optional($director->director)->titulo ?? '') . ' ' . (optional($director->director)->nombre ?? '') . ' ' . (optional($director->director)->apellido_paterno ?? '') . ' ' . (optional($director->director)->apellido_materno ?? '')) ?: '____________________________') }}</u><br>

                    @if ($director->director->genero === 'F')
                        Firma de la directora de la escuela
                    @else
                        Firma del director de la escuela
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <div class="footer">
        <p>
            {{ strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA') }}
            · C.C.T. {{ $nivel->cct ?? '—' }}
        </p>

        <p>
            C.
            {{ $escuela->calle ?? '' }}
            No.
            {{ $escuela->no_exterior ?? '' }},
            Col.
            {{ $escuela->colonia ?? '' }},
            C.P.
            {{ $escuela->codigo_postal ?? '' }},
            Cd.
            {{ $escuela->ciudad ?? '' }},
            {{ $escuela->estado ?? '' }}.
        </p>

        <p>
            Fecha de expedición:
            {{ now()->translatedFormat('d \\d\\e F \\d\\e\\l Y \\a \\l\\a\\s H:i') }}
        </p>
    </div>
</body>

</html>
