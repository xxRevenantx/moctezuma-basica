<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>{{ $oficio->folio }}</title>

    <style>
        @page {
            margin: 25px 55px 35px 55px;
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
            margin: 0;
            font-family: 'ARIAL', sans-serif;
            font-size: 15px;
            color: #000;
        }

        .pagina {
            width: 100%;
            position: relative;
        }

        .encabezado {
            width: 100%;
            margin-bottom: 22px;
        }

        .tabla-encabezado {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-izquierdo {
            width: 250px;
            vertical-align: top;
        }

        .logo-gobierno {
            width: 215px;
        }

        .datos-superiores {
            text-align: right;
            font-size: 12px;
            line-height: 1.15;
            letter-spacing: 0.5px;
        }

        .datos-superiores .titulo {
            font-size: 14px;
            font-family: 'Times New Roman', serif;
        }

        .bloque-info {
            width: 380px;
            margin-left: auto;
            margin-top: 15px;
            font-size: 17px;
            line-height: 1.28;
        }

        .bloque-info strong {
            font-weight: bold;
        }

        .lema {
            margin-top: 7px;
            font-weight: bold;
            font-style: italic;
            text-transform: uppercase;
        }

        .nivel-rojo {
            margin-top: 18px;
            margin-left: 22px;
            color: red;
            font-size: 21px;
            font-weight: normal;
            letter-spacing: 0.5px;
        }

        .fecha {
            margin-top: 28px;
            text-align: center;
            font-size: 20px;
        }

        .dirigidos {
            width: 100%;
            margin-top: 18px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .tabla-dirigidos {
            width: 100%;
            border-collapse: collapse;
        }

        .dirigido-izquierdo {
            width: 70%;
            vertical-align: top;
        }

        .dirigido-derecho {
            width: 50%;
            vertical-align: top;
            padding-left: 40px;
            padding-top: 80px;
        }

        .contenido {
            margin-top: 20px;
            text-align: justify;
            font-size: 16px;
            line-height: 1.1;
        }

        .contenido p {
            /* margin: 0 0 9px 0; */
            text-align: justify;
        }

        .contenido strong,
        .contenido b {
            font-weight: bold;
        }

        .contenido u {
            text-decoration: underline;
        }

        .tabla-alumno {
            width: 100%;
            margin-top: 12px;
            border-collapse: collapse;
            font-size: 14px;
        }

        .tabla-alumno th,
        .tabla-alumno td {
            border: 1px solid #000;
            padding: 2px 5px;
            text-align: center;
        }

        .tabla-alumno th {
            font-weight: normal;
        }

        .despedida {
            margin-top: 14px;
            font-size: 16px;
        }

        .firma {
            margin-top: 42px;
            text-align: center;
            font-size: 16px;
        }

        .firma .atentamente {
            font-size: 16px;
            margin-bottom: 2px;
        }

        .firma .cargo {
            font-size: 16px;
            margin-bottom: 50px;
        }

        .linea-firma {
            width: 190px;
            margin: 0 auto 5px auto;
            border-top: 1px solid #777;
        }

        .pie {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -16px;
            padding-top: 9px;
            text-align: center;
        }

        .tira {
            width: 100%;
            object-fit: cover;
        }
    </style>
</head>

<body>
    @php
        $nombreAlumno = trim(
            ($alumno->nombre ?? '') . ' ' . ($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? ''),
        );

        $nombreDirector = trim(
            ($director?->titulo ?? '') .
                ' ' .
                ($director?->nombre ?? '') .
                ' ' .
                ($director?->apellido_paterno ?? '') .
                ' ' .
                ($director?->apellido_materno ?? ''),
        );

        $grado = $alumno?->grado?->nombre ?? '';
        $grupo = $alumno?->grupo?->asignacionGrupo?->nombre ?? '';
        $cct = $nivel?->cct ?? '';

        $generoAlumno = mb_strtoupper(trim($alumno?->genero ?? ''));

        $esMujer = $generoAlumno === 'M';

        $textoNino = $esMujer ? 'niña' : 'niño';
        $textoNinoMayuscula = $esMujer ? 'NIÑA' : 'NIÑO';

        $textoAlumno = $esMujer ? 'alumna' : 'alumno';
        $textoAlumnoMayuscula = $esMujer ? 'ALUMNA' : 'ALUMNO';

        $textoDelAlumno = $esMujer ? 'de la alumna' : 'del alumno';
        $textoSiguienteNino = $esMujer ? 'siguiente niña' : 'siguiente niño';

        $asunto = $oficio->asunto ?: ($oficio->tipo_oficio === 'Alta' ? 'ALTA POR TRASLADO' : 'BAJA POR TRASLADO');

        $tipoOficio = mb_strtoupper($oficio->tipo_oficio ?? '');

        $descripcion = trim((string) $oficio->descripcion_html);

        if ($descripcion === '') {
            if ($oficio->tipo_oficio === 'Alta') {
                $descripcion =
                    '
                    <p>
                        La que suscribe C. ' .
                    e($nombreDirector) .
                    ', directora del Jardín de Niños Centro Universitario Moctezuma C.C.T. ' .
                    e($cct) .
                    ',
                        ubicada en Francisco I. Madero Ote. 800. Col. Esquipulas. Cd. Altamirano, Gro.,
                        perteneciente a la zona escolar 137, sector 013.
                        Me dirijo a usted de la manera más atenta y respetuosa para solicitar la
                        <strong>ALTA</strong> ' .
                    e($textoDelAlumno) .
                    ' a esta institución educativa. A continuación se presentan los datos de la ' .
                    e($textoSiguienteNino) .
                    ':
                    </p>
                ';
            } else {
                $descripcion =
                    '
                    <p>
                        La que suscribe C. ' .
                    e($nombreDirector) .
                    ', directora del Jardín de Niños Centro Universitario Moctezuma C.C.T. ' .
                    e($cct) .
                    ',
                        ubicada en Francisco I. Madero Ote. 800. Col. Esquipulas. Cd. Altamirano, Gro.,
                        perteneciente a la zona escolar 137, sector 013.
                        Me dirijo a usted de la manera más atenta y respetuosa para solicitar la
                        <strong>BAJA</strong> ' .
                    e($textoDelAlumno) .
                    ' de esta institución educativa. A continuación se presentan los datos de la ' .
                    e($textoSiguienteNino) .
                    ':
                    </p>
                ';
            }
        }
    @endphp

    <div class="pagina">

        <div class="encabezado">
            <table class="tabla-encabezado">
                <tr>
                    <td class="logo-izquierdo">
                        @if (file_exists(public_path('imagenes/logo-guerrero.png')))
                            <img class="logo-gobierno" src="{{ public_path('imagenes/logo-guerrero.png') }}">
                        @elseif(file_exists(public_path('imagenes/logo-edu.png')))
                            <img class="logo-gobierno" src="{{ public_path('imagenes/logo-edu.png') }}">
                        @endif
                    </td>

                    <td class="datos-superiores">
                        <div class="titulo">SUBSECRETARÍA DE EDUCACIÓN BÁSICA</div>
                        DIRECCIÓN GENERAL DE EDUCACIÓN INICIAL Y PREESCOLAR
                    </td>
                </tr>
            </table>

            <div class="bloque-info">
                <strong>DEPENDENCIA:</strong> CENTRO UNIVERSITARIO MOCTEZUMA<br>
                <strong>C.C.T:</strong> {{ $cct }}<br>
                <strong>ASUNTO:</strong> {{ $asunto }}<br>
                <strong>NÚM. DE OFICIO:</strong> {{ $oficio->folio }}
                <div class="lema">"{{ $lema }}"</div>
            </div>
        </div>


        <div class="fecha">
            {{ $oficio->fecha_lugar }}
        </div>

        <div class="dirigidos">
            <table class="tabla-dirigidos">
                <tr>
                    <td class="dirigido-izquierdo">
                        {{ $oficio->dirigido_1_nombre }}<br>
                        {{ $oficio->dirigido_1_cargo }}<br>
                        {{ $oficio->dirigido_1_lugar }}<br>
                        PRESENTE.
                    </td>

                    <td class="dirigido-derecho">
                        CON ATENCIÓN<br>
                        {{ $oficio->dirigido_2_nombre }}<br>
                        {{ $oficio->dirigido_2_cargo }}<br>
                        {{ $oficio->dirigido_2_lugar }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="contenido">
            {!! $descripcion !!}
        </div>

        <table class="tabla-alumno">
            <thead>
                <tr>
                    <th>Nombre {{ $esMujer ? 'de la niña' : 'del niño' }}</th>
                    <th>CURP</th>
                    <th>GRADO Y GRUPO</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>{{ mb_strtoupper($nombreAlumno) }}</td>
                    <td>{{ $alumno?->curp ?? '----------------' }}</td>
                    <td>{{ $grado }} "{{ $grupo }}"</td>
                </tr>
            </tbody>
        </table>

        <div class="despedida">
            Sin otro particular reciba un cordial saludo.
        </div>

        <div class="firma">
            <div class="atentamente">ATENTAMENTE</div>
            <div class="cargo">Directora</div>

            <div class="linea-firma"></div>
            <div>{{ $nombreDirector }}</div>
        </div>

        <div class="pie">
            @if (file_exists(public_path('imagenes/tira.jpg')))
                <img class="tira" src="{{ public_path('imagenes/tira.jpg') }}" alt="">
            @elseif (file_exists(public_path('imagenes/tira.png')))
                <img class="tira" src="{{ public_path('imagenes/tira.png') }}" alt="">
            @endif
        </div>

    </div>
</body>

</html>
