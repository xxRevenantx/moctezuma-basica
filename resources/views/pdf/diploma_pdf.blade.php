<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>

    <style>
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

        @page {
            size: letter landscape;
            margin: 0;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'calibri', 'ARIAL', sans-serif;
            background: #ffffff;
            color: #1e293b;
        }

        .diploma {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;

            background-image: url("{{ public_path('imagenes/diploma.jpg') }}");
            background-size: 100% 100%;
            background-position: center center;
            background-repeat: no-repeat;
        }

        .contenido {
            position: relative;
            z-index: 5;
            text-align: center;
        }

        .fecha {
            position: absolute;
            right: 58px;
            top: 48px;
            z-index: 6;
            font-size: 11px;
            color: #64748b;
        }

        .marca {
            position: absolute;
            left: 50%;
            bottom: 110px;
            transform: translateX(-50%);
            z-index: 3;
            font-size: 78px;
            font-weight: bold;
            color: #f8fafc;
            letter-spacing: 5px;
        }

        .etiqueta {
            display: inline-block;
            padding: 7px 18px;
            border-radius: 999px;
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .titulo {
            margin-top: 22px;
            font-size: 46px;
            font-weight: bold;
            color: #92400e;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .subtitulo {
            margin-top: 6px;
            font-size: 15px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .texto {
            margin-top: 34px;
            font-size: 17px;
            line-height: 1.8;
            color: #334155;
        }

        .alumno {
            margin: 22px auto 0 auto;
            max-width: 850px;
            padding: 16px 20px;
            border-bottom: 3px solid #f59e0b;
            font-size: 34px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: .5px;
        }

        .detalle {
            margin: 24px auto 0 auto;
            max-width: 850px;
            font-size: 15px;
            line-height: 1.7;
            color: #334155;
        }

        .detalle strong {
            color: #0f172a;
        }

        .promedio-box {
            margin: 26px auto 0 auto;
            width: 180px;
            border-radius: 24px;
            background: #ecfdf5;
            border: 2px solid #86efac;
            padding: 12px 18px;
        }

        .promedio-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #047857;
        }

        .promedio {
            margin-top: 4px;
            font-size: 34px;
            font-weight: bold;
            color: #065f46;
        }

        .info {
            margin-top: 28px;
            width: 100%;
            border-collapse: collapse;
        }

        .info td {
            padding: 7px 10px;
            font-size: 12px;
            color: #475569;
            text-align: center;
        }

        .info strong {
            color: #0f172a;
        }

        .firmas {
            position: absolute;
            left: 70px;
            right: 70px;
            bottom: 48px;
            z-index: 6;
            width: calc(100% - 140px);
            border-collapse: collapse;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            font-size: 11px;
            color: #475569;
            padding: 0 40px;
        }

        .linea {
            border-top: 1.5px solid #334155;
            padding-top: 8px;
            font-weight: bold;
            color: #0f172a;
        }

        .leyenda {
            margin-top: 16px;
            font-size: 11px;
            color: #64748b;
        }

        .lugar-box {
            margin: 16px auto 0 auto;
            width: 210px;
            border-radius: 24px;
            background: #fffbeb;
            border: 2px solid #facc15;
            padding: 12px 18px;
        }

        .lugar-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #92400e;
        }

        .lugar {
            margin-top: 4px;
            font-size: 24px;
            font-weight: bold;
            color: #78350f;
        }

        .logo-izquierdo img,
        .logo-derecho img {
            max-width: 120px;
            max-height: 120px;
        }
    </style>
</head>

<body>
    <div class="diploma">


        <div class="contenido">

            <table class="encabezado">
                <tr>
                    <td class="logo-izquierdo">
                        @if ($logoIzquierdo)
                            <img src=" {{ $logoIzquierdo }}" alt="">
                        @endif
                    </td>

                    <td class="titulo-centro">
                        <div class="nombre-escuela">
                            {{ strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA') }}
                        </div>



                    </td>

                    <td class="logo-derecho">
                        @if ($logoDerecho)
                            <img src="{{ $logoDerecho }}" alt="">
                        @endif
                    </td>
                </tr>
            </table>



            <div class="titulo">
                Diploma
            </div>

            <div class="subtitulo">
                De aprovechamiento académico
            </div>

            <div class="texto">
                Se otorga el presente reconocimiento a:
            </div>

            <div class="alumno">
                {{ $alumnoNombre }}
            </div>

            <div class="detalle">
                Por su desempeño académico durante el
                <strong>{{ mb_strtoupper($nombrePeriodo) }}</strong>,
                correspondiente al nivel
                <strong>{{ mb_strtoupper($nivel->nombre ?? 'NIVEL') }}</strong>,
                grado
                <strong>{{ mb_strtoupper($grado->nombre ?? 'GRADO') }}</strong>,
                grupo
                <strong>{{ mb_strtoupper($grupo->nombre ?? 'GRUPO') }}</strong>.

                @if ($esBachillerato && $semestre)
                    <br>
                    Semestre:
                    <strong>{{ $semestre->numero }}</strong>
                @endif
            </div>

            <div class="promedio-box">
                <div class="promedio-label">Promedio</div>

                <div class="promedio">
                    {{ $promedio ?? '—' }}
                </div>
            </div>

            @if (!empty($lugarAlumno))
                <div class="lugar-box">
                    <div class="lugar-label">Lugar obtenido</div>

                    <div class="lugar">
                        {{ $textoLugarAlumno }}
                    </div>
                </div>
            @endif

            <table class="info">
                <tr>
                    <td>
                        <strong>Generación:</strong>
                        {{ $generacion->anio_ingreso ?? '—' }} - {{ $generacion->anio_egreso ?? '—' }}
                    </td>

                    <td>
                        <strong>{{ $esBachillerato ? 'Parcial:' : 'Periodo:' }}</strong>
                        {{ $nombrePeriodo }}
                    </td>

                    <td>
                        <strong>Ciclo escolar:</strong>
                        {{ $periodo?->cicloEscolar?->inicio_anio ?? '—' }}-{{ $periodo?->cicloEscolar?->fin_anio ?? '—' }}
                    </td>
                </tr>
            </table>

            <div class="leyenda">
                Documento generado automáticamente por el Sistema Web de Control Escolar.
            </div>
        </div>

        <table class="firmas">
            <tr>
                <td>
                    <div class="linea">
                        Control Escolar
                    </div>
                </td>

                <td>
                    <div class="linea">
                        Dirección Académica
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
