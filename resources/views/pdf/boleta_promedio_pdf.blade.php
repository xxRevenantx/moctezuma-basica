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
            font-family: 'ARIAL';
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

        .tabla {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla th {
            background: #bfdbfe;
            color: #1e3a8a;
            border: 1px solid #ffffff;
            padding: 7px 5px;
            font-size: 12px;
            text-align: center;
            font-weight: bold;
        }

        .tabla td {
            border: 1px solid #e2e8f0;
            padding: 3px 5px;
            font-size: 11px;
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

        .tabla tfoot td {
            border: 1px solid #bfdbfe;
            padding: 6px 5px;
            font-size: 12px;
            font-weight: bold;
        }

        .tabla tfoot tr {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    @php
        /*
         * Se normalizan los periodos para pintar encabezados.
         * En bachillerato normalmente serán dos parciales.
         * En básica normalmente serán tres periodos.
         */
        $periodosResumen = collect($periodosResumen ?? []);

        $cantidadColumnas = $esBachillerato ? 5 : 4;

        $mostrarLeyendaCualitativa = collect($filasMaterias)->contains(function ($fila) {
            foreach ($fila['calificaciones'] ?? [] as $calificacionPeriodo) {
                $valor = mb_strtoupper((string) ($calificacionPeriodo['calificacion'] ?? ''));

                if (in_array($valor, ['AC', 'ED', 'RA'], true)) {
                    return true;
                }
            }

            return false;
        });

        if (!$esBachillerato && !$esSecundaria) {
            $mostrarLeyendaCualitativa = true;
        }
    @endphp

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
                        {{ $titulo }}<br>
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
            <td class="value" style="text-align: center; text-transform: uppercase;">
                {{ $inscripcion->apellido_paterno }}</td>
            <td class="value" style="text-align: center; text-transform: uppercase;">
                {{ $inscripcion->apellido_materno }}</td>
            <td class="value" style="text-align: center; text-transform: uppercase;">{{ $inscripcion->nombre }}</td>
        </tr>
        <tr>
            <td style="text-align: center"></td>
            <td style="text-align: center">Apellido Paterno</td>
            <td style="text-align: center">Apellido Materno</td>
            <td style="text-align: center">Nombre(s)</td>
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

                <td class="label">Ciclo escolar</td>
                <td>{{ $cicloEscolarTexto }}</td>
            </tr>
        @else
            <tr>
                <td class="label">Documento</td>
                <td>{{ $tipo === 'anual' ? 'Boleta' : 'Boleta Semestral' }}</td>

                <td class="label">Ciclo escolar</td>
                <td>{{ $cicloEscolarTexto }}</td>
            </tr>
        @endif
    </table>


    <table class="tabla">
        <thead>
            <tr>
                @if ($esBachillerato)
                    <th style="width: 11%;">CLAVE</th>
                @endif

                <th style="width: 25%;">MATERIA</th>

                @foreach ($periodosResumen as $numeroPeriodo => $periodoResumen)
                    <th style="width: 11%;">
                        {{ $periodoResumen['nombre'] ?? ($esBachillerato ? 'Parcial ' . $numeroPeriodo : 'Periodo ' . $numeroPeriodo) }}
                    </th>
                @endforeach

                <th style="width: 11%;">PROMEDIO</th>
                <th style="width: 14%;">STATUS</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($filasMaterias as $fila)
                @php
                    $badgeClass = 'badge-empty';

                    if ($fila['estado'] === 'Aprobado') {
                        $badgeClass = 'badge-ok';
                    } elseif ($fila['estado'] === 'Regular') {
                        $badgeClass = 'badge-regular';
                    } elseif ($fila['estado'] === 'En riesgo') {
                        $badgeClass = 'badge-risk';
                    } elseif ($fila['estado'] === 'Especial') {
                        $badgeClass = 'badge-special';
                    }
                @endphp

                <tr>
                    @if ($esBachillerato)
                        <td class="text-center">{{ $fila['clave'] ?? '—' }}</td>
                    @endif

                    <td class="materia">
                        {{ $fila['materia'] ?? 'Materia' }}

                        @if (($fila['extra'] ?? 0) == 1)
                            <span style="font-size: 7px; color:#64748b;">(Extra)</span>
                        @endif
                    </td>

                    @foreach ($periodosResumen as $numeroPeriodo => $periodoResumen)
                        @php
                            $calificacionPeriodo = $fila['calificaciones'][$numeroPeriodo] ?? null;
                            $valorPeriodo = $calificacionPeriodo['calificacion'] ?? '—';
                        @endphp

                        <td class="text-center">
                            <strong>{{ $valorPeriodo }}</strong>
                        </td>
                    @endforeach

                    <td class="text-center">
                        <strong>{{ $fila['promedio'] ?? '—' }}</strong>
                    </td>

                    <td class="text-center">
                        <span class="badge {{ $badgeClass }}">
                            {{ $fila['estado'] ?? 'Sin captura' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $cantidadColumnas + $periodosResumen->count() }}" class="text-center">
                        No hay materias para mostrar.
                    </td>
                </tr>
            @endforelse
        </tbody>

        <tfoot>
            <tr>
                @if ($esBachillerato)
                    <td></td>
                @endif

                <td class="materia text-center" style="background:#eff6ff; color:#1e3a8a;">
                    Promedio {{ $esBachillerato ? 'semestral' : 'por periodo' }}
                </td>

                @foreach ($periodosResumen as $numeroPeriodo => $periodoResumen)
                    <td class="text-center" style="background:#eff6ff; color:#1e3a8a; font-weight:bold;">
                        {{ $promediosPeriodos[$numeroPeriodo] ?? '—' }}
                    </td>
                @endforeach

                <td class="text-center" style="background:#ecfdf5; color:#166534; font-weight:bold;">
                    {{ $promedio }}
                </td>

                <td class="text-center" style="background:#ecfdf5;">
                    @php
                        $badgePromedioClass = 'badge-empty';

                        if ($estadoPromedio === 'Aprobado') {
                            $badgePromedioClass = 'badge-ok';
                        } elseif ($estadoPromedio === 'Regular') {
                            $badgePromedioClass = 'badge-regular';
                        } elseif ($estadoPromedio === 'Reprobado' || $estadoPromedio === 'En riesgo') {
                            $badgePromedioClass = 'badge-risk';
                        }
                    @endphp

                    <span class="badge {{ $badgePromedioClass }}">
                        {{ $estadoPromedio }}
                    </span>
                </td>
            </tr>
        </tfoot>
    </table>

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
                    <span class="leyenda-clave leyenda-ra">RA * Requiere apoyo</span>
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

                    @if (optional($director->director)->genero === 'F')
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

                    @if (optional($director->director)->genero === 'F')
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
