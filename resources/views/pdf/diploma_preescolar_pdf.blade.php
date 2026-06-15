<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Diploma Preescolar</title>

    <style>
        @page {
            margin: 24px 38px;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #1f2937;
            background: #ffffff;
        }

        .page {
            position: relative;
            width: 100%;
            height: 100%;
            border: 7px solid #006492;
            padding: 34px 48px;
        }

        .inner-border {
            position: absolute;
            inset: 16px;
            border: 3px solid #88AC2E;
        }

        .corner {
            position: absolute;
            width: 95px;
            height: 95px;
            border: 5px solid #efc2ef;
        }

        .corner-tl {
            top: 25px;
            left: 25px;
            border-right: 0;
            border-bottom: 0;
        }

        .corner-tr {
            top: 25px;
            right: 25px;
            border-left: 0;
            border-bottom: 0;
        }

        .corner-bl {
            bottom: 25px;
            left: 25px;
            border-right: 0;
            border-top: 0;
        }

        .corner-br {
            bottom: 25px;
            right: 25px;
            border-left: 0;
            border-top: 0;
        }

        .watermark {
            position: absolute;
            top: 120px;
            left: 245px;
            width: 350px;
            opacity: 0.06;
        }

        .content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            width: 265px;
            max-height: 85px;
            object-fit: contain;
        }

        .mascota {
            width: 85px;
            max-height: 95px;
            object-fit: contain;
        }

        .institucion {
            margin-top: 8px;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: #006492;
        }

        .titulo {
            margin-top: 20px;
            font-size: 48px;
            font-weight: bold;
            color: #88AC2E;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .subtitulo {
            margin-top: 4px;
            font-size: 16px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .otorga {
            margin-top: 30px;
            font-size: 18px;
            color: #334155;
        }

        .alumno {
            margin: 18px auto 0;
            width: 84%;
            border-bottom: 2px solid #006492;
            padding-bottom: 8px;
            font-size: 30px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
        }

        .motivo {
            margin: 28px auto 0;
            width: 82%;
            font-size: 22px;
            line-height: 1.55;
            text-align: center;
            color: #1f2937;
        }

        .nivel {
            margin-top: 12px;
            font-size: 18px;
            font-weight: bold;
            color: #9d2034;
            text-transform: uppercase;
        }

        .detalle {
            margin-top: 18px;
            font-size: 13px;
            color: #475569;
            text-transform: uppercase;
        }

        .fecha {
            margin-top: 26px;
            font-size: 13px;
            color: #334155;
            text-transform: uppercase;
        }

        .firmas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 58px;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 12px;
        }

        .linea {
            width: 240px;
            border-top: 1px solid #1f2a44;
            margin: 0 auto 6px;
        }

        .firma-nombre {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .firma-cargo {
            margin-top: 2px;
            font-size: 11px;
            text-transform: uppercase;
            color: #475569;
        }

        .footer {
            position: absolute;
            left: 46px;
            right: 46px;
            bottom: 18px;
            text-align: center;
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    @php
        $nombreAlumno = trim(
            ($alumno->nombre ?? '') . ' ' . ($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? ''),
        );

        $grado = $alumno->grado?->nombre ?? '';
        $grupo = $alumno->grupo?->asignacionGrupo?->nombre ?? 'S/G';

        $ciclo = $cicloEscolar ? $cicloEscolar->inicio_anio . '-' . $cicloEscolar->fin_anio : '';
    @endphp

    <div class="page">
        <div class="inner-border"></div>

        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>

        @if (!empty($marcaAgua))
            <img class="watermark" src="{{ $marcaAgua }}" alt="Marca de agua">
        @endif

        <div class="content">
            <table class="header">
                <tr>
                    <td style="text-align:left;">
                        @if (!empty($logoPrincipal))
                            <img class="logo" src="{{ $logoPrincipal }}" alt="Centro Universitario Moctezuma">
                        @endif
                    </td>

                    <td style="text-align:right;">
                        @if (!empty($logoPenacho))
                            <img class="mascota" src="{{ $logoPenacho }}" alt="Preescolar">
                        @endif
                    </td>
                </tr>
            </table>

            <div class="institucion">
                Centro Universitario Moctezuma
            </div>

            <div class="titulo">
                Diploma
            </div>

            <div class="subtitulo">
                Nivel Preescolar
            </div>

            <div class="otorga">
                Se otorga el presente diploma a:
            </div>

            <div class="alumno">
                {{ $nombreAlumno }}
            </div>

            <div class="motivo">
                Por haber terminado tus estudios en el nivel preescolar.
            </div>

            <div class="nivel">
                ¡Muchas felicidades!
            </div>

            <div class="detalle">
                {{ $grado }} · Grupo {{ $grupo }} · Ciclo escolar {{ $ciclo }}
            </div>

            <div class="fecha">
                Cd. Altamirano, Guerrero, a {{ $fechaPdf }}
            </div>

            <table class="firmas">
                <tr>
                    <td>
                        <div class="linea"></div>
                        <div class="firma-nombre">{{ $educadoraNombre ?: 'EDUCADORA' }}</div>
                        <div class="firma-cargo">Educadora</div>
                    </td>

                    <td>
                        <div class="linea"></div>
                        <div class="firma-nombre">{{ $directoraNombre ?: 'DIRECCIÓN' }}</div>
                        <div class="firma-cargo">Directora</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Centro Universitario Moctezuma · Francisco I. Madero Ote #800, Col. Esquipulas, Cd. Altamirano, Gro.
        </div>
    </div>
</body>

</html>
