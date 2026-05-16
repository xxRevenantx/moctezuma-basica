<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>{{ $titulo ?? 'Diploma' }}</title>

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
            color: #071846;
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

        .logo-izquierdo {
            position: absolute;
            top: 104px;
            left: 118px;
            width: 100px;

            text-align: center;
            z-index: 5;
        }

        .logo-izquierdo img {
            max-width: 100px;
        }

        .logo-derecho {
            position: absolute;
            top: 112px;
            right: 90px;
            width: 190px;
            text-align: center;
            z-index: 5;
        }

        .logo-derecho img {
            max-width: 190px;
        }

        .encabezado {
            position: absolute;
            top: 95px;
            left: 300px;
            right: 300px;
            text-align: center;
            z-index: 6;
        }

        .secretaria {
            font-family: 'ARIAL', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: #071846;
            letter-spacing: .4px;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .escuela {
            margin-top: 5px;
            font-family: 'ARIAL', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #c98626;
            letter-spacing: .4px;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .cct {
            margin-top: 5px;
            font-family: 'ARIAL', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #071846;
            letter-spacing: .4px;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .otorga {
            margin-top: 5px;
            font-family: 'ARIAL', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #071846;
            letter-spacing: .4px;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .titulo-diploma {
            position: absolute;
            top: 232px;
            left: 0;
            right: 0;
            z-index: 6;
            text-align: center;
            font-family: 'ARIAL', sans-serif;
            font-size: 120px;
            line-height: 1;
            font-weight: 700;
            letter-spacing: 3px;
            color: #08265f;
            text-transform: uppercase;
            text-shadow: 5px 5px 0 #efa56f;
        }

        .adorno-izquierdo {
            position: absolute;
            top: 306px;
            left: 120px;
            width: 95px;
            height: 1.5px;
            background: #b5792f;
            z-index: 6;
        }

        .adorno-derecho {
            position: absolute;
            top: 306px;
            right: 120px;
            width: 95px;
            height: 1.5px;
            background: #b5792f;
            z-index: 6;
        }

        .a-texto {
            position: absolute;
            top: 374px;
            left: 0;
            right: 0;
            z-index: 6;
            text-align: center;
            font-family: 'ARIAL', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: #071846;
            text-transform: uppercase;
        }

        .alumno {
            position: absolute;
            top: 410px;
            left: 145px;
            right: 145px;
            z-index: 6;
            text-align: center;
            font-family: 'calibri', 'ARIAL', sans-serif;
            font-size: 40px;
            font-weight: 700;
            color: #071846;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .linea-alumno {
            position: absolute;
            top: 470px;
            left: 145px;
            right: 145px;
            z-index: 6;
            height: 3px;
            background: #eba55f;
        }

        .descripcion {
            position: absolute;
            top: 480px;
            left: 150px;
            right: 150px;
            z-index: 6;
            text-align: center;
            font-family: 'calibri', 'ARIAL', sans-serif;
            font-size: 16px;
            line-height: 1;
            color: #071846;
        }

        .descripcion strong {
            font-weight: 700;
            color: #071846;
        }

        .datos-extra {
            position: absolute;
            top: 550px;
            left: 150px;
            right: 150px;
            z-index: 6;
            width: calc(100% - 300px);
            border-collapse: collapse;
        }

        .datos-extra td {
            width: 33.33%;
            text-align: center;
            font-family: 'calibri', 'ARIAL', sans-serif;
            font-size: 15px;
            color: #071846;
            padding: 2px 8px;
        }

        .datos-extra strong {
            color: #071846;
            font-weight: 700;
        }

        .promedio-box {
            position: absolute;
            top: 550px;
            left: 50%;
            margin-left: -80px;
            width: 160px;
            z-index: 6;
            text-align: center;
            border: 1.5px solid #c98626;
            border-radius: 12px;
            padding: 5px 5px;
            background: rgba(255, 255, 255, .85);
        }

        .promedio-label {
            font-family: 'ARIAL', sans-serif;
            font-size: 10px;
            font-weight: 700;
            color: #c98626;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .promedio {
            margin-top: 1px;
            font-family: 'ARIAL', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #071846;
        }

        .lugar-box {
            position: absolute;
            top: 635px;
            right: 200px;
            width: 145px;
            z-index: 6;
            text-align: center;
            border: 1.5px solid #c98626;
            border-radius: 12px;
            padding: 8px 10px;
            background: rgba(255, 255, 255, .85);
        }

        .lugar-label {
            font-family: 'ARIAL', sans-serif;
            font-size: 10px;
            font-weight: 700;
            color: #c98626;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .lugar {
            margin-top: 1px;
            font-family: 'ARIAL', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #071846;
        }

        .firmas {
            font-family: 'ARIAL', sans-serif;
            width: 100%;
            margin-top: 650px;
            font-size: 15px;
            color: #071846;
        }



        .cargo {
            margin-top: 2px;
            font-size: 10px;
            font-weight: 400;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <div class="diploma">

        <div class="logo-izquierdo">
            @if (!empty($logoIzquierdo))
                <img src="{{ $logoIzquierdo }}" alt="Logo Centro Universitario Moctezuma">
            @endif
        </div>

        <div class="logo-derecho">
            @if (!empty($logoDerecho))
                <img src="{{ $logoDerecho }}" alt="Logo Secretaría de Educación Guerrero">
            @endif
        </div>

        <div class="encabezado">
            <div class="secretaria">
                {{ mb_strtoupper($secretariaTexto ?? 'SECRETARÍA DE EDUCACIÓN GUERRERO') }}
            </div>

            <div class="escuela">
                {{ mb_strtoupper($nombreEscuelaDiploma ?? ($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA')) }}
            </div>

            <div class="cct">
                {{ mb_strtoupper($cctDiploma ?? 'C.C.T. PENDIENTE') }}
            </div>

            <div class="otorga">
                Otorga el presente
            </div>


        </div>



        <div class="adorno-izquierdo"></div>
        <div class="adorno-derecho"></div>

        <div class="titulo-diploma">
            Diploma
        </div>

        <div class="a-texto">
            A:
        </div>

        <div class="alumno">
            {{ $alumnoNombre ?? 'NOMBRE DEL ALUMNO' }}
        </div>

        <div class="linea-alumno"></div>

        <div class="descripcion">
            Por haber obtenido el @if (!empty($lugarAlumno))
                <b><u> {{ $textoLugarAlumno ?? '—' }} </u></b>
            @endif en aprovechamiento académico durante el
            <strong> {{ $nombrePeriodo ?? '—' }}° {{ !empty($esBachillerato) ? 'parcial' : 'periodo' }}</strong>,
            correspondiente al
            @if (!empty($esBachillerato) && !empty($semestre))
                <strong>{{ $semestre->numero ?? '—' }}° Semestre</strong>
            @endif
            del <strong>{{ mb_strtoupper($grado->nombre ?? 'GRADO') }}</strong>° grado
            de <strong>{{ mb_strtoupper($nivel->nombre ?? 'NIVEL') }}</strong>,
            grupo "<strong>{{ mb_strtoupper($grupo->asignacionGrupo?->nombre ?? 'GRUPO') }}</strong>".


        </div>

        <table class="datos-extra">
            <tr>
                <td>
                    <strong>Generación:</strong>
                    {{ $generacion->anio_ingreso ?? '—' }} - {{ $generacion->anio_egreso ?? '—' }}
                </td>

                <td>
                    <strong>Ciclo escolar:</strong>
                    {{ $cicloEscolarTexto ?? '—' }}
                </td>
            </tr>
        </table>

        <div class="promedio-box">
            <div class="promedio-label">
                Promedio
            </div>

            <div class="promedio">
                {{ $promedio ?? '—' }}
            </div>
        </div>


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


    </div>
</body>

</html>
