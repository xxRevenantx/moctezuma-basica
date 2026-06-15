<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Reconocimiento Preescolar</title>

    <style>
        @page {
            margin: 25px 38px;
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
            border: 6px solid #f4b6d2;
            padding: 34px 46px;
        }

        .inner-border {
            position: absolute;
            inset: 16px;
            border: 2px solid #006492;
        }

        .watermark {
            position: absolute;
            top: 115px;
            left: 235px;
            width: 360px;
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
            margin-bottom: 8px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            width: 260px;
            max-height: 85px;
            object-fit: contain;
        }

        .mascota {
            width: 80px;
            max-height: 90px;
            object-fit: contain;
        }

        .institucion {
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: #006492;
        }

        .titulo {
            margin-top: 22px;
            font-size: 42px;
            font-weight: bold;
            color: #9d2034;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .subtitulo {
            margin-top: 6px;
            font-size: 15px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .otorga {
            margin-top: 28px;
            font-size: 17px;
        }

        .alumno {
            margin: 18px auto 0;
            width: 82%;
            border-bottom: 2px solid #006492;
            padding-bottom: 8px;
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
        }

        .motivo {
            margin: 22px auto 0;
            width: 82%;
            font-size: 18px;
            line-height: 1.6;
            text-align: center;
        }

        .detalle {
            margin-top: 18px;
            font-size: 13px;
            color: #475569;
            text-transform: uppercase;
        }

        .lugar {
            display: inline-block;
            margin-top: 18px;
            border-radius: 999px;
            background: #efc2ef;
            padding: 8px 22px;
            font-size: 17px;
            font-weight: bold;
            color: #6b1240;
            text-transform: uppercase;
        }

        .fecha {
            margin-top: 24px;
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

        $tipoTexto =
            $reconocimiento->tipo_reconocimiento === 'anual'
                ? 'Reconocimiento anual'
                : 'Reconocimiento del ' . $reconocimiento->periodo . '° periodo';

        $lugarTexto = $reconocimiento->texto_lugar ?: 'Reconocimiento especial';
    @endphp

    <div class="page">
        <div class="inner-border"></div>

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
                Reconocimiento
            </div>

            <div class="subtitulo">
                {{ $tipoTexto }}
            </div>

            <div class="otorga">
                Se otorga el presente reconocimiento a:
            </div>

            <div class="alumno">
                {{ $nombreAlumno }}
            </div>

            <div class="lugar">
                {{ $lugarTexto }}
            </div>

            <div class="motivo">
                {{ $reconocimiento->motivo }}
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
