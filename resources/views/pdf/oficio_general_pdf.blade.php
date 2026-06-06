<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>{{ $oficio->folio }}</title>

    <style>
        @page {
            margin: 10px 38px 35px 38px;
        }

        body {
            margin: 0;
            font-family: "Times New Roman", Times, serif;
            font-size: 12px;
            color: #000;
        }

        .pagina {
            width: 100%;
            position: relative;
        }

        .marca-agua {
            position: fixed;
            top: 190px;
            left: 65px;
            width: 455px;
            opacity: 0.055;
            z-index: -1;
        }

        .encabezado {
            width: 100%;
            border-bottom: 1px solid #777;
            padding-bottom: 5px;
        }

        .tabla-encabezado {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-izquierdo {
            width: 185px;
            vertical-align: top;
        }

        .logo-centro {
            width: 80px;
            vertical-align: top;
            text-align: left;
        }

        .logo-derecho {
            width: 95px;
            vertical-align: top;
            text-align: right;
        }

        .logo-guerrero {
            width: 180px;
        }

        .logo-edu {
            width: 70px;
        }

        .logo-moctezuma {
            width: 95px;
        }

        .datos-escuela {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            line-height: 1.05;
            text-transform: uppercase;
        }

        .bloque-info {
            width: 210px;
            margin-left: auto;
            margin-top: 14px;
            text-align: left;
            font-size: 13px;
            line-height: 1.25;
        }

        .bloque-info strong {
            font-weight: bold;
        }

        .anio {
            margin-top: 8px;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            font-style: italic;
        }

        .fecha {
            margin-top: 22px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        .dirigidos {
            margin-top: 24px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .tabla-dirigidos {
            width: 100%;
            border-collapse: collapse;
        }

        .dirigido-izquierdo {
            width: 50%;
            vertical-align: top;
        }

        .dirigido-derecho {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding-top: 48px;
        }

        .contenido {
            margin-top: 34px;
            text-align: justify;
            font-size: 12px;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .contenido p {
            margin: 0 0 9px 0;
            text-align: justify;
        }

        .contenido strong,
        .contenido b {
            font-weight: bold;
        }

        .contenido u {
            text-decoration: underline;
        }

        .firma {
            margin-top: 58px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .firma .atentamente {
            margin-bottom: 52px;
        }

        .linea-firma {
            width: 200px;
            margin: 0 auto 10px auto;
            border-top: 1px solid #777;
        }

        .pie {
            position: fixed;
            left: 38px;
            right: 38px;
            bottom: 18px;
        }

        .linea-pie {
            border-top: 1px solid #000;
            margin-bottom: 8px;
        }

        .greca {
            width: 100%;
            height: 14px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    @php
        $nivelNombre = mb_strtolower($nivel?->nombre ?? '');

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

        $nombreEscuela = match (true) {
            str_contains($nivelNombre, 'primaria') => 'ESC. PRIM. MOCTEZUMA',
            str_contains($nivelNombre, 'secundaria') => 'ESC. SEC. PART. MOCTEZUMA',
            str_contains($nivelNombre, 'bachillerato') => 'BACHILLERATO GENERAL CENTRO UNIVERSITARIO MOCTEZUMA',
            default => 'CENTRO UNIVERSITARIO MOCTEZUMA',
        };

        $asunto = $oficio->asunto ?: ($oficio->tipo_oficio === 'Alta' ? 'Alta por traslado.' : 'Baja por traslado.');

        $descripcion = trim((string) $oficio->descripcion_html);

        if ($descripcion === '') {
            $descripcion =
                '
            <p>
                LA QUE SUSCRIBE C. ' .
                e(mb_strtoupper($nombreDirector)) .
                ', DIRECTOR(A) DE ' .
                e($nombreEscuela) .
                '
                CON C.C.T. ' .
                e($cct) .
                ', UBICADA EN FRANCISCO I. MADERO OTE. #800, COL. ESQUIPULAS,
                EN CD. ALTAMIRANO, MUNICIPIO DE PUNGARABATO, GRO. AUTORIZO LA ' .
                e(mb_strtoupper($oficio->tipo_oficio)) .
                '
                DEFINITIVA DEL(LA) MENOR <strong><u>' .
                e(mb_strtoupper($nombreAlumno)) .
                '</u></strong>
                CON CURP <strong>' .
                e($alumno?->curp ?? '') .
                '</strong>, QUIEN ERA ALUMNO(A) REGULAR EN
                <strong>' .
                e(mb_strtoupper($grado)) .
                ' GRADO, GRUPO "' .
                e($grupo) .
                '"</strong>,
                DE ESTA INSTITUCIÓN EDUCATIVA.
            </p>

            <p>
                PARA FINES QUE EL INTERESADO CONVENGA, CONFORME A DERECHO SE EXTIENDE LA PRESENTE.
            </p>
        ';
        }
    @endphp

    <div class="pagina">

        @if (file_exists(public_path('imagenes/logo-marca-agua.png')))
            <img class="marca-agua" src="{{ public_path('imagenes/logo-marca-agua.png') }}">
        @elseif(file_exists(public_path('imagenes/logo-letra.png')))
            <img class="marca-agua" src="{{ public_path('imagenes/logo-letra.png') }}">
        @endif

        <div class="encabezado">
            <table class="tabla-encabezado">
                <tr>
                    <td class="logo-izquierdo">
                        @if (file_exists(public_path('imagenes/logo-guerrero.png')))
                            <img class="logo-guerrero" src="{{ public_path('imagenes/logo-guerrero.png') }}">
                        @elseif(file_exists(public_path('imagenes/logo-edu.png')))
                            <img class="logo-guerrero" src="{{ public_path('imagenes/logo-edu.png') }}">
                        @endif
                    </td>

                    <td class="logo-centro">
                        @if (file_exists(public_path('imagenes/logo-educacion.png')))
                            <img class="logo-edu" src="{{ public_path('imagenes/logo-educacion.png') }}">
                        @endif
                    </td>

                    <td class="datos-escuela">
                        SECRETARÍA DE EDUCACIÓN GUERRERO<br>
                        SECRETARÍA DE EDUCACIÓN BÁSICA<br>
                        DIRECCIÓN GENERAL DE EDUCACIÓN SECUNDARIA<br>
                        {{ $nombreEscuela }} C.C.T. {{ $cct }}<br>
                        FRANCISCO I. MADERO #800 COL. ESQUIPULAS CD.<br>
                        ALTAMIRANO, GRO.
                    </td>

                    <td class="logo-derecho">
                        @if (file_exists(public_path('imagenes/logo-moctezuma.png')))
                            <img class="logo-moctezuma" src="{{ public_path('imagenes/logo-moctezuma.png') }}">
                        @elseif(file_exists(public_path('imagenes/logo.png')))
                            <img class="logo-moctezuma" src="{{ public_path('imagenes/logo.png') }}">
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="bloque-info">
            <strong>Sección:</strong> {{ $oficio->seccion ?: 'ADMINISTRATIVA' }}<br>
            <strong>No. Oficio:</strong> {{ $oficio->folio }}<br>
            <strong>Asunto:</strong> {{ $asunto }}
        </div>

        <div class="anio">
            "2025, AÑO DE LA MUJER INDÍGENA "
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
                        {{ $oficio->dirigido_1_lugar }}
                    </td>

                    <td class="dirigido-derecho">
                        CON ATENCIÓN<br><br>
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

        <div class="firma">
            <div class="atentamente">ATENTAMENTE</div>

            <div class="linea-firma"></div>

            <div>
                {{ $director?->cargo ?? 'DIRECTOR(A) DE LA ESCUELA' }}<br>
                {{ $nombreDirector }}
            </div>
        </div>

        <div class="pie">
            <div class="linea-pie"></div>

            @if (file_exists(public_path('imagenes/greca.png')))
                <img class="greca" src="{{ public_path('imagenes/greca.png') }}">
            @elseif(file_exists(public_path('imagenes/tira.png')))
                <img class="greca" src="{{ public_path('imagenes/tira.png') }}">
            @elseif(file_exists(public_path('imagenes/tira.jpg')))
                <img class="greca" src="{{ public_path('imagenes/tira.jpg') }}">
            @endif
        </div>

    </div>
</body>

</html>
