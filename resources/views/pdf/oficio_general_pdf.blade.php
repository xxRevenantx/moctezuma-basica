<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>{{ $oficio->folio }}</title>

    <style>
        @page {
            margin: 10px 60px 30px 60px;
        }

        body {
            margin: 0;
            font-family: "Times New Roman", Times, serif;
            font-size: 15px;
            color: #000;
        }

        .pagina {
            width: 100%;
            position: relative;
        }

        .marca-agua {
            position: fixed;
            top: 190px;
            left: 110px;
            width: 500px;
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
            width: 100px;
            /* vertical-align: top; */
        }

        .logo-guerrero {
            width: 150px;
        }

        .logo-centro {
            width: 80px;
            vertical-align: top;
            text-align: left;
        }

        .logo-derecho {
            width: 120px;
            vertical-align: top;
            text-align: right;
        }



        .logo-edu {
            width: 70px;
        }

        .logo-moctezuma {
            width: 95px;
        }

        .datos-escuela {
            text-align: center;
            font-weight: bold;
            line-height: 1.05;
            font-size: 13px;
            text-transform: uppercase;
            width: 400px;
        }

        .bloque-info {
            width: 210px;
            margin-left: auto;
            margin-top: 40px;
            text-align: left;
            line-height: 1.6;
        }

        .bloque-info strong {
            font-weight: bold;
        }

        .anio {
            margin-top: 8px;
            text-align: right;
            font-weight: bold;
            font-style: italic;
        }

        .fecha {
            margin-top: 22px;
            text-align: right;
            font-weight: bold;
        }

        .dirigidos {
            margin-top: 24px;
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

        .datos-alta {
            margin-top: 22px;
            line-height: 1.45;
            text-transform: uppercase;
        }

        .datos-alta p {
            margin: 0 0 4px 0;
        }

        .texto-calificaciones {
            margin-top: 14px;
            font-size: 12px;
            font-weight: bold;
            text-transform: none;
        }

        .tabla-calificaciones {
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
            font-size: 7px;
            table-layout: fixed;
        }

        .tabla-calificaciones th,
        .tabla-calificaciones td {
            border: 1px solid #555;
            padding: 3px 2px;
            text-align: center;
        }

        .tabla-calificaciones th {
            background: #e5e7eb;
            font-weight: bold;
        }

        .firma {
            margin-top: 58px;
            text-align: center;
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

        $apellidoPaterno = mb_strtoupper($alumno?->apellido_paterno ?? '');
        $apellidoMaterno = mb_strtoupper($alumno?->apellido_materno ?? '');
        $nombresAlumno = mb_strtoupper($alumno?->nombre ?? '');
        $curpAlumno = mb_strtoupper($alumno?->curp ?? '');

        $generoAlumno = mb_strtolower($alumno?->genero ?? '');

        $fechaNacimiento = '';

        if (!empty($alumno?->fecha_nacimiento)) {
            try {
                $fechaNacimiento = \Carbon\Carbon::parse($alumno->fecha_nacimiento)->format('d/m/Y');
            } catch (\Throwable $e) {
                $fechaNacimiento = $alumno->fecha_nacimiento;
            }
        }

        $nombreEscuela = match (true) {
            str_contains($nivelNombre, 'primaria') => 'ESC. PRIM. MOCTEZUMA',
            str_contains($nivelNombre, 'secundaria') => 'ESC. SEC. PART. MOCTEZUMA',
            str_contains($nivelNombre, 'bachillerato') => 'BACHILLERATO GENERAL CENTRO UNIVERSITARIO MOCTEZUMA',
            default => 'CENTRO UNIVERSITARIO MOCTEZUMA',
        };

        $direccionGeneral = match (true) {
            str_contains($nivelNombre, 'primaria') => 'DIRECCIÓN GENERAL DE EDUCACIÓN PRIMARIA',
            str_contains($nivelNombre, 'secundaria') => 'DIRECCIÓN GENERAL DE EDUCACIÓN SECUNDARIA',
            str_contains($nivelNombre, 'bachillerato') => 'DIRECCIÓN GENERAL DE BACHILLERATO',
            default => 'DIRECCIÓN GENERAL DE EDUCACIÓN',
        };

        $asunto = $oficio->asunto ?: ($oficio->tipo_oficio === 'Alta' ? 'Alta por traslado.' : 'Baja por traslado.');

        $descripcion = trim((string) $oficio->descripcion_html);

        $generoAlumno = mb_strtoupper(trim($alumno?->genero ?? ''));

        $esMujer = $generoAlumno === 'M';
        $esHombre = $generoAlumno === 'H';

        $textoAlumno = $esMujer ? 'ALUMNA' : 'ALUMNO';
        $textoAlumnoRegular = $esMujer ? 'ALUMNA REGULAR' : 'ALUMNO REGULAR';
        $textoDelAlumno = $esMujer ? 'DE LA ALUMNA' : 'DEL ALUMNO';
        $textoDelMenor = $esMujer ? 'DE LA MENOR' : 'DEL MENOR';
        $textoInteresado = $esMujer ? 'LA INTERESADA' : 'EL INTERESADO';
        $textoNacido = $esMujer ? 'NACIDA' : 'NACIDO';

        $apellidoPaterno = mb_strtoupper($alumno?->apellido_paterno ?? '');
        $apellidoMaterno = mb_strtoupper($alumno?->apellido_materno ?? '');
        $nombresAlumno = mb_strtoupper($alumno?->nombre ?? '');
        $curpAlumno = mb_strtoupper($alumno?->curp ?? '');

        $fechaNacimiento = '';

        if (!empty($alumno?->fecha_nacimiento)) {
            try {
                $fechaNacimiento = \Carbon\Carbon::parse($alumno->fecha_nacimiento)->format('d/m/Y');
            } catch (\Throwable $e) {
                $fechaNacimiento = $alumno->fecha_nacimiento;
            }
        }

        $gradoTexto = $grado ? mb_strtoupper($grado) : '_____';
        $nivelTexto = $nivel ? mb_strtoupper($nivel->nombre) : '_____';
        $grupoTexto = $grupo ? mb_strtoupper($grupo) : '_____';
        $motivoTraslado = 'CAMBIO DE ESCUELA';

        if ($descripcion === '') {
            if ($oficio->tipo_oficio === 'Alta') {
                $descripcion =
                    '
                <p>
                    LA QUE SUSCRIBE C. ' .
                    e(mb_strtoupper($nombreDirector)) .
                    ', DIRECTOR(A) DE LA ' .
                    e($nombreEscuela) .
                    ' CON C.C.T. ' .
                    e($cct) .
                    ', UBICADA EN FRANCISCO I. MADERO OTE #800, COL. ESQUIPULAS,
                    EN CD. ALTAMIRANO, MUNICIPIO DE PUNGARABATO, GRO. SE DIRIGE ANTE ESA
                    DEPENDENCIA QUE DIGNAMENTE REPRESENTA PARA SOLICITAR LA <strong>ALTA</strong>
                    ' .
                    e($textoDelAlumno) .
                    ' AL <strong>' .
                    e($gradoTexto) .
                    '°</strong> GRADO DE ' .
                    e($nivelTexto) .
                    '. A CONTINUACIÓN RINDO SUS DATOS PERSONALES:
                </p>

                <div class="datos-alta">
                    <p>
                        CURP:
                        <strong><u>' .
                    e($curpAlumno) .
                    '</u></strong>
                    </p>

                    <p>
                        APELLIDO PATERNO:
                        <strong><u>' .
                    e($apellidoPaterno) .
                    '</u></strong>
                    </p>

                    <p>
                        APELLIDO MATERNO:
                        <strong><u>' .
                    e($apellidoMaterno) .
                    '</u></strong>
                    </p>

                    <p>
                        NOMBRE(S):
                        <strong><u>' .
                    e($nombresAlumno) .
                    '</u></strong>
                    </p>

                    <p>
                        FECHA DE NACIMIENTO:
                        <strong><u>' .
                    e($fechaNacimiento) .
                    '</u></strong>
                    </p>

                    <p>
                        MOTIVO DE TRASLADO:
                        <strong><u>' .
                    e($motivoTraslado) .
                    '</u></strong>
                    </p>
                </div>
            ';
            } else {
                $descripcion =
                    '
                <p>
                    LA QUE SUSCRIBE C. ' .
                    e(mb_strtoupper($nombreDirector)) .
                    ', DIRECTOR(A) DE ' .
                    e($nombreEscuela) .
                    ' CON C.C.T. ' .
                    e($cct) .
                    ', UBICADA EN FRANCISCO I. MADERO OTE. #800,
                    COL. ESQUIPULAS, EN CD. ALTAMIRANO, MUNICIPIO DE PUNGARABATO, GRO.
                    AUTORIZO LA <strong>BAJA DEFINITIVA</strong> ' .
                    e($textoDelMenor) .
                    '
                    <strong><u>' .
                    e(mb_strtoupper($nombreAlumno)) .
                    '</u></strong>
                    CON CURP <strong>' .
                    e($curpAlumno) .
                    '</strong>, QUIEN ERA ' .
                    e($textoAlumnoRegular) .
                    ' EN
                    <strong>' .
                    e($gradoTexto) .
                    '° GRADO, DE ' .
                    $nivelTexto .
                    ', GRUPO "' .
                    e($grupoTexto) .
                    '"</strong>,
                    DE ESTA INSTITUCIÓN EDUCATIVA.
                </p>

                <p>
                    PARA FINES QUE EL INTERESADO CONVENGA, CONFORME A DERECHO SE EXTIENDE LA PRESENTE.
                </p>
            ';
            }
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
                        @if (file_exists(public_path('imagenes/logo-edu.png')))
                            <img class="logo-guerrero" src="{{ public_path('imagenes/logo-edu.png') }}">
                        @elseif(file_exists(public_path('imagenes/logo-edu.png')))
                            <img class="logo-guerrero" src="{{ public_path('imagenes/logo-edu.png') }}">
                        @endif
                    </td>



                    <td class="datos-escuela">
                        SECRETARÍA DE EDUCACIÓN GUERRERO<br>
                        SECRETARÍA DE EDUCACIÓN BÁSICA<br>
                        {{ $direccionGeneral }}<br>
                        {{ $nombreEscuela }} C.C.T. {{ $cct }}<br>
                        FRANCISCO I. MADERO #800 COL. ESQUIPULAS CD.<br>
                        ALTAMIRANO, GRO.
                    </td>

                    <td class="logo-derecho">
                        @if (file_exists(public_path('imagenes/logo-letra.png')))
                            <img class="logo-moctezuma" src="{{ public_path('imagenes/logo-letra.png') }}">
                        @elseif(file_exists(public_path('imagenes/logo-letra.png')))
                            <img class="logo-moctezuma" src="{{ public_path('imagenes/logo-letra.png') }}">
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
            {{ $lema }}
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

        @if (!empty($calificacionesOficio['materias'] ?? []) && !empty($calificacionesOficio['filas'] ?? []))
            <div class="texto-calificaciones">
                Se anexan las calificaciones de los siguientes periodos:
            </div>

            <table class="tabla-calificaciones">
                <thead>
                    <tr>
                        <th>PERIODO</th>

                        @foreach ($calificacionesOficio['materias'] as $materiaCalificacion)
                            <th>{{ mb_strtoupper($materiaCalificacion->materia) }}</th>
                        @endforeach

                        <th>PROMEDIO</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($calificacionesOficio['filas'] as $filaCalificacion)
                        <tr>
                            <td>{{ $filaCalificacion['periodo'] }}</td>

                            @foreach ($calificacionesOficio['materias'] as $materiaCalificacion)
                                <td>
                                    {{ $filaCalificacion['valores'][$materiaCalificacion->asignacion_materia_id] ?? '' }}
                                </td>
                            @endforeach

                            <td>{{ $filaCalificacion['promedio'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="firma">
            <div class="atentamente">ATENTAMENTE</div>

            <div class="linea-firma"></div>

            <div>
                {{ $director?->cargo ?? 'DIRECTOR(A) DE LA ESCUELA' }}<br>
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


</body>

</html>
