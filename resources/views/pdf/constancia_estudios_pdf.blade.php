<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>{{ $constancia->folio ?? 'constancia-estudios' }}</title>

    <style>
        @page {
            margin: 22px 70px 35px 70px;
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/ARIAL.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: bold;
            src: url('{{ storage_path('fonts/ARIALBD.ttf') }}') format('truetype');
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'ARIAL', sans-serif;
            font-size: 14px;
            color: #000;
            line-height: 1.45;
        }

        .pagina {
            width: 100%;
            min-height: 100%;
            position: relative;
        }

        .marca-agua {
            position: fixed;
            top: 190px;
            left: 120px;
            width: 480px;
            opacity: 0.055;
            z-index: -1;
        }

        .encabezado {
            width: 100%;
            padding-bottom: 10px;
            border-bottom: 1px solid #777;
        }

        .tabla-encabezado {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-izquierdo {
            width: 175px;
            vertical-align: top;
            text-align: left;
        }

        .logo-derecho {
            width: 70px;
            vertical-align: top;
            text-align: right;
        }

        .logo-gobierno {
            width: 165px;
            height: auto;
        }

        .logo-institucion {
            width: 90px;
            height: auto;
        }

        .datos-escuela {
            text-align: center;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.2;
            font-weight: bold;
            text-transform: uppercase;
            width: 400px;
        }

        .bloque-datos {
            margin-top: 24px;
            text-align: right;
            font-size: 14px;
            line-height: 1.65;
            font-weight: bold;
        }

        .bloque-datos .subrayado {
            text-decoration: underline;
        }

        .dirigido {
            margin-top: 70px;
            margin-bottom: 22px;
            text-align: left;
            font-size: 13px;
            line-height: 1.35;
            font-weight: bold;
            text-transform: uppercase;
            white-space: pre-line;
        }

        .dirigido.relaciones-exteriores {
            margin-top: 105px;
        }

        .contenido-principal {
            margin-top: 0;
        }

        .parrafo {
            margin: 0 0 18px 0;
            text-align: justify;
            font-size: 14.5px;
            line-height: 1.45;
        }

        .titulo-hace-constar {
            margin: 28px 0 24px 0;
            text-align: center;
            font-size: 24px;
            line-height: 1;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            text-decoration: underline;
        }

        .contenido {
            margin-top: 0;
            text-align: justify;
            font-size: 14.5px;
            line-height: 1.45;
        }

        .contenido p {
            margin-top: 0;
            margin-bottom: 16px;
            text-align: justify;
        }

        .contenido strong,
        .contenido b {
            font-weight: bold;
        }

        .contenido em,
        .contenido i {
            font-style: italic;
        }

        .contenido u {
            text-decoration: underline;
        }

        .contenido s,
        .contenido strike {
            text-decoration: line-through;
        }

        .contenido [style*="text-align: center"] {
            text-align: center;
        }

        .contenido [style*="text-align: right"] {
            text-align: right;
        }

        .contenido [style*="text-align: justify"] {
            text-align: justify;
        }

        .contenido [style*="text-align: left"] {
            text-align: left;
        }

        .contenido ul,
        .contenido ol {
            margin-top: 8px;
            margin-bottom: 8px;
            padding-left: 28px;
        }

        .contenido li {
            margin-bottom: 4px;
        }

        .contenido table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .contenido table td,
        .contenido table th {
            border: 1px solid #111;
            padding: 5px 7px;
            vertical-align: top;
        }

        .contenido table th {
            font-weight: bold;
            background: #f3f4f6;
        }

        .contenido img {
            max-width: 100%;
            height: auto;
        }

        .parrafo-final {
            margin-top: 26px;
            text-align: justify;
            font-size: 14.5px;
            line-height: 1.45;
        }

        .firma {
            margin-top: 26px;
            text-align: center;
        }

        .atentamente {
            margin-bottom: 34px;
            text-align: center;
            font-size: 12px;
            line-height: 1.15;
            font-weight: bold;
            letter-spacing: 3px;
        }

        .cargo-firma {
            display: block;
            margin-top: 3px;
            font-size: 11px;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .linea-firma {
            width: 210px;
            margin: 0 auto 5px auto;
            border-top: 1px solid #777;
        }

        .nombre-firma {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
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
        $nivel = $alumno?->nivel;
        $grado = $alumno?->grado;
        $grupo = $alumno?->grupo?->asignacionGrupo;
        $ciclo = $alumno?->ciclo;
        $director = $nivel?->director ?? null;

        $nombreAlumno = trim(
            ($alumno->nombre ?? '') . ' ' . ($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? ''),
        );

        $nombreDirector = trim(
            ($director->titulo ?? '') .
                ' ' .
                ($director->nombre ?? '') .
                ' ' .
                ($director->apellido_paterno ?? '') .
                ' ' .
                ($director->apellido_materno ?? ''),
        );

        $nombreDirector = $nombreDirector ?: 'DIRECTORA DE LA ESCUELA';

        $cargoDirector = $director?->cargo ?: 'DIRECTORA DE LA ESCUELA';

        $fechaExpedicion = $constancia?->fecha_expedicion
            ? $constancia->fecha_expedicion->translatedFormat('d \d\e F \d\e Y')
            : now()->translatedFormat('d \d\e F \d\e Y');

        $diaExpedicion = $constancia?->fecha_expedicion
            ? $constancia->fecha_expedicion->translatedFormat('d')
            : now()->translatedFormat('d');

        $mesAnioExpedicion = $constancia?->fecha_expedicion
            ? $constancia->fecha_expedicion->translatedFormat('F \d\e Y')
            : now()->translatedFormat('F \d\e Y');

        $nombreNivel = mb_strtoupper($nivel?->nombre ?? 'NIVEL EDUCATIVO');

        $cct = $nivel?->cct ?? '';

        $cicloEscolar = $ciclo?->ciclo ?? '@ciclo';

        // El asunto se mantiene igual para constancia de estudios y relaciones exteriores.
        $asunto = 'Constancia de estudios';

        // Se identifica si la plantilla corresponde a relaciones exteriores.
        $esRelacionesExteriores = str_contains(
            mb_strtolower($plantilla?->clave ?? ($plantilla?->titulo ?? '')),
            'relaciones',
        );

        $dirigidoTexto = trim((string) ($constancia?->dirigido_a ?? ''));

        if ($dirigidoTexto === '') {
            $dirigidoTexto = $esRelacionesExteriores
                ? "SECRETARÍA DE RELACIONES EXTERIORES\nP R E S E N T E:"
                : 'A QUIEN CORRESPONDA:';
        }

        $nombreEscuela = match (true) {
            str_contains(mb_strtolower($nivel?->nombre ?? ''), 'preescolar') => 'ESCUELA PREESCOLAR MOCTEZUMA',
            str_contains(mb_strtolower($nivel?->nombre ?? ''), 'primaria') => 'ESC. PRIM. PART. MOCTEZUMA',
            str_contains(mb_strtolower($nivel?->nombre ?? ''), 'secundaria') => 'ESC. SEC. PART. MOCTEZUMA',
            str_contains(mb_strtolower($nivel?->nombre ?? ''), 'bachillerato')
                => 'BACHILLERATO GENERAL CENTRO UNIVERSITARIO MOCTEZUMA',
            default => 'CENTRO UNIVERSITARIO MOCTEZUMA',
        };

        $grupoTexto = $grupo?->nombre ? 'grupo: "' . $grupo->nombre . '"' : 'grupo correspondiente';

        $contenidoHtml = trim((string) ($constancia?->contenido_generado_html ?? ''));
    @endphp

    <div class="pagina">
        @if (file_exists(public_path('imagenes/logo-letra.png')))
            <img class="marca-agua" src="{{ public_path('imagenes/logo-letra.png') }}" alt="">
        @elseif (file_exists(public_path('imagenes/marca-agua.png')))
            <img class="marca-agua" src="{{ public_path('imagenes/marca-agua.png') }}" alt="">
        @endif

        <div class="encabezado">
            <table class="tabla-encabezado">
                <tr>
                    <td class="logo-izquierdo">
                        @if (file_exists(public_path('imagenes/logo-edu.png')))
                            <img class="logo-gobierno" src="{{ public_path('imagenes/logo-edu.png') }}" alt="">
                        @elseif (file_exists(public_path('imagenes/logo-oficial.jpg')))
                            <img class="logo-gobierno" src="{{ public_path('imagenes/logo-oficial.jpg') }}"
                                alt="">
                        @endif
                    </td>

                    <td class="datos-escuela">
                        SECRETARIA DE EDUCACIÓN GUERRERO<br>
                        SUBSECRETARÍA DE EDUCACIÓN BÁSICA<br>
                        DIRECCIÓN GENERAL DE EDUCACIÓN PRIMARIA<br>
                        {{ $nombreEscuela }}<br>
                        {{ $cct }}<br>
                        FRANCISCO I. MADERO #800 OTE. COL. ESQUIPULAS. CD.<br>
                        ALTAMIRANO, GRO. TEL. 767 688 0774
                    </td>

                    <td class="logo-derecho">
                        @if ($nivel?->logo && file_exists(public_path('imagenes/logo-letra.png')))
                            <img class="logo-institucion" src="{{ public_path('imagenes/logo-letra.png') }}"
                                alt="">
                        @elseif (file_exists(public_path('imagenes/logo.png')))
                            <img class="logo-institucion" src="{{ public_path('imagenes/logo.png') }}" alt="">
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="bloque-datos">
            Lugar y fecha: Cd. Altamirano, Gro., a {{ $fechaExpedicion }}.<br>
            Asunto: <span class="subrayado">{{ $asunto }}.</span><br>
            Ciclo escolar: {{ $cicloEscolar }}
        </div>

        <div class="dirigido {{ $esRelacionesExteriores ? 'relaciones-exteriores' : '' }}">
            {!! nl2br(e($dirigidoTexto)) !!}
        </div>

        <div class="contenido-principal">
            <p class="parrafo">
                La que suscribe <b>{{ $nombreDirector }}</b>, Directora de la "{{ $nombreEscuela }}",
                con clave de incorporación <b>{{ $cct }}</b>, ubicada en la Calle Francisco I.
                Madero No. 800. Col. Esquipulas de Cd. Altamirano, municipio de Pungarabato
                Guerrero, Región Tierra Caliente.
            </p>

            <div class="titulo-hace-constar">
                HACE CONSTAR
            </div>

            <div class="contenido">
                @if ($contenidoHtml !== '')
                    {!! $contenidoHtml !!}
                @else
                    <p>
                        Que el alumno: <b><u>{{ $nombreAlumno }}</u></b>,
                        CURP: <b><u>{{ $alumno?->curp }}</u></b>,
                        matrícula: <b><u>{{ $alumno?->matricula }}</u></b>,
                        se encuentra inscrito y cursando en el
                        <b>{{ $grado?->nombre }}</b> de <b>{{ mb_strtolower($nivel?->nombre ?? '') }}</b>,
                        {{ $grupoTexto }}, en esta institución educativa con Clave de Incorporación
                        C.C.T: <b><u>{{ $cct }}</u></b>, en el ciclo escolar {{ $cicloEscolar }}.
                    </p>
                @endif
            </div>

            <p class="parrafo-final">
                Para fines legales que el interesado convenga, conforme a derecho se extiende la presente
                a los {{ $diaExpedicion }} días del mes de {{ $mesAnioExpedicion }} en Cd. Altamirano,
                municipio de Pungarabato Guerrero, Región Tierra Caliente.
            </p>

            <div class="firma">
                <div class="atentamente">
                    A T E N T A M E N T E
                    <span class="cargo-firma">{{ $cargoDirector }}</span>
                </div>

                <div class="linea-firma"></div>

                <div class="nombre-firma">
                    {{ $nombreDirector }}
                </div>
            </div>
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
