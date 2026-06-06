<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>{{ $constancia->folio ?? 'constancia-estudios' }}</title>

    <style>
        @page {
            margin: 22px 70px 25px 70px;
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
            line-height: 1.4;

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
            font-weight: bold;
            text-transform: uppercase;
            width: 400px;
            line-height: 1.2;
        }

        .bloque-datos {
            margin-top: 24px;
            text-align: right;
            font-size: 14px;
            font-weight: bold;
        }

        .bloque-datos .subrayado {
            text-decoration: underline;
        }

        .bloque-datos.conducta {
            margin-top: 35px;
        }

        .dirigido {
            margin-top: 40px;
            margin-bottom: 22px;
            text-align: left;
            font-size: 13px;
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

        .contenido-principal.conducta {
            margin-top: 52px;
        }

        .parrafo {
            margin: 0 0 18px 0;
            text-align: justify;
            font-size: 15px;
        }

        .titulo-hace-constar {
            margin: 18px 0 24px 0;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            text-decoration: underline;
        }

        .titulo-hace-constar.conducta {
            margin: 45px 0 38px 0;
            text-align: center;
            font-size: 19px;
            text-transform: none;
            letter-spacing: 0;
            text-decoration: none;
        }

        .contenido {
            margin-top: 0;
            text-align: justify;
            font-size: 14.5px;
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

        .tabla-calificaciones {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 16px;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8px;
        }

        .texto-calificaciones {
            text-align: justify;
            font-size: 14.5px;
        }

        .tabla-calificaciones th,
        .tabla-calificaciones td {
            border: 1px solid #555;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.08;
        }

        .tabla-calificaciones th {
            background: #d9d9d9;
            font-weight: bold;
            text-transform: uppercase;
        }

        .tabla-calificaciones .celda-periodo {
            width: 50px;
            background: #6b7280;
            color: #fff;
            font-weight: bold;
        }

        .tabla-calificaciones .celda-promedio {
            width: 52px;
            font-weight: bold;
        }

        .tabla-calificaciones .materia-nombre {
            font-size: 5.5px;
            line-height: 1.05;
            word-break: normal;
        }

        .buena-conducta {
            margin: 38px 0 28px 0;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .parrafo-final {
            margin-top: 18px;
            text-align: justify;
            font-size: 16px;

        }

        .firma {
            margin-top: 26px;
            text-align: center;
        }

        .firma.conducta {
            margin-top: 42px;
        }

        .atentamente {
            margin-bottom: 34px;
            text-align: center;
            font-size: 12px;

            font-weight: bold;
            letter-spacing: 3px;
        }

        .atentamente.conducta {
            margin-bottom: 58px;
            letter-spacing: 0;
            font-size: 13px;
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

        .linea-firma.conducta {
            width: 265px;
        }

        .nombre-firma {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .cargo-final-conducta {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
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

        $clavePlantilla = mb_strtolower($plantilla?->clave ?? '');
        $tituloPlantilla = mb_strtolower($plantilla?->titulo ?? '');
        $textoPlantilla = $clavePlantilla . ' ' . $tituloPlantilla;

        $esRelacionesExteriores = str_contains($textoPlantilla, 'relaciones');
        $esCartaConducta = str_contains($textoPlantilla, 'conducta');
        $esConstanciaEstudios = !$esRelacionesExteriores && !$esCartaConducta;

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

        $fechaConducta = $constancia?->fecha_expedicion
            ? $constancia->fecha_expedicion->translatedFormat('j \d\e F \d\e Y')
            : now()->translatedFormat('j \d\e F \d\e Y');

        $cct = $nivel?->cct ?? '';
        $cicloEscolar = $ciclo?->ciclo ?? '@ciclo';

        $asunto = $esCartaConducta ? 'Carta de conducta' : 'Constancia de estudios';

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
                        {{ $nombreEscuela }} C.C.T: {{ $cct }}<br>
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

        @if ($esCartaConducta)
            <div class="bloque-datos conducta">
                ASUNTO: <span class="subrayado">{{ $asunto }}.</span><br>
                Cd. Altamirano, Gro., a {{ $fechaConducta }}
            </div>
        @else
            <div class="bloque-datos">
                Lugar y fecha: Cd. Altamirano, Gro., a {{ $fechaExpedicion }}.<br>
                Asunto: <span class="subrayado">{{ $asunto }}.</span><br>
            </div>

            <div class="dirigido {{ $esRelacionesExteriores ? 'relaciones-exteriores' : '' }}">
                {!! nl2br(e($dirigidoTexto)) !!}
            </div>
        @endif

        <div class="contenido-principal {{ $esCartaConducta ? 'conducta' : '' }}">
            @if ($esCartaConducta)
                <p class="parrafo">
                    La que suscribe <b>{{ $nombreDirector }}</b>, Directora de la "{{ $nombreEscuela }}",
                    con clave de incorporación <b>{{ $cct }}</b>, ubicada en la Calle Francisco I.
                    Madero No. 800. Col. Esquipulas de Cd. Altamirano, municipio de Pungarabato
                    Guerrero, Región Tierra Caliente.
                </p>

                <div class="titulo-hace-constar conducta">
                    Hace constar que:
                    <b><u>{{ mb_strtoupper($nombreAlumno) }}</u></b>
                </div>

                <div class="contenido">
                    @if ($contenidoHtml !== '')
                        {!! $contenidoHtml !!}
                    @else
                        <p>
                            de acuerdo a la documentación que obra en el archivo de la escuela, cursó el
                            <b>{{ $grado?->nombre }}</b>, {{ $grupoTexto }} en esta institución a mi cargo,
                            durante el ciclo escolar {{ $cicloEscolar }} y en su estancia en la misma observó
                        </p>
                    @endif

                    <div class="buena-conducta">
                        BUENA CONDUCTA
                    </div>
                </div>

                <p class="parrafo-final">
                    Para fines legales que el interesado convenga, conforme a derecho se extiende la presente
                    a los {{ $diaExpedicion }} días del mes de {{ $mesAnioExpedicion }} en Cd. Altamirano,
                    municipio de Pungarabato, Gro.
                </p>

                <div class="firma conducta">
                    <div class="atentamente conducta">
                        ATENTAMENTE
                    </div>

                    <div class="linea-firma conducta"></div>

                    <div class="cargo-final-conducta">
                        {{ $cargoDirector }}
                    </div>

                    <div class="nombre-firma">
                        {{ $nombreDirector }}
                    </div>
                </div>
            @else
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

                @if (
                    $esConstanciaEstudios &&
                        !empty($calificacionesConstancia['materias'] ?? []) &&
                        !empty($calificacionesConstancia['filas'] ?? []))
                    <p class="texto-calificaciones">
                        Se anexan las calificaciones de los siguientes periodos:
                    </p>

                    <table class="tabla-calificaciones">
                        <thead>
                            <tr>
                                <th class="celda-periodo">
                                    MATERIA
                                </th>

                                @foreach ($calificacionesConstancia['materias'] as $materiaCalificacion)
                                    <th>
                                        <div class="materia-nombre">
                                            {{ mb_strtoupper($materiaCalificacion->materia) }}
                                        </div>
                                    </th>
                                @endforeach

                                <th class="celda-promedio">
                                    PROMEDIO
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($calificacionesConstancia['filas'] as $filaCalificacion)
                                <tr>
                                    <td class="celda-periodo">
                                        {{ $filaCalificacion['periodo'] }}
                                    </td>

                                    @foreach ($calificacionesConstancia['materias'] as $materiaCalificacion)
                                        <td>
                                            {{ $filaCalificacion['valores'][$materiaCalificacion->asignacion_materia_id] ?? '' }}
                                        </td>
                                    @endforeach

                                    <td class="celda-promedio">
                                        {{ $filaCalificacion['promedio'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

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
            @endif
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
