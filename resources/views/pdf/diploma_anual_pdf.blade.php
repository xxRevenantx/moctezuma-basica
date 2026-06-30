<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>DIPLOMA</title>

    <style>
        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: 400;
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
            font-weight: 400;
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

        * {
            box-sizing: border-box;
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
            color: #071846;
            background: #ffffff;
        }

        .diploma {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-image: url("{{ public_path('imagenes/diploma_2.jpg') }}");
            background-size: 100% 100%;
            background-position: center center;
            background-repeat: no-repeat;
        }

        /*
        |--------------------------------------------------------------------------
        | Logotipos
        |--------------------------------------------------------------------------
        */

        .logo-izquierdo {
            position: absolute;
            top: 80px;
            left: 118px;
            z-index: 5;
            width: 150px;
            text-align: center;
        }

        .logo-izquierdo img {
            max-width: 130px;
            max-height: 100px;
        }

        .logo-derecho {
            position: absolute;
            top: 90px;
            right: 90px;
            z-index: 5;
            width: 150px;
            text-align: center;
        }

        .logo-derecho img {
            max-width: 150px;
            max-height: 100px;
        }

        /*
        |--------------------------------------------------------------------------
        | Encabezado
        |--------------------------------------------------------------------------
        */

        .encabezado {
            position: absolute;
            top: 55px;
            left: 280px;
            right: 280px;
            z-index: 6;
            text-align: center;
            line-height: 0;
        }

        .secretaria {
            font-family: 'ARIAL', sans-serif;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.15;
            color: #071846;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        .escuela {
            font-family: 'ARIAL', sans-serif;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.15;
            color: #c98626;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        .cct {
            font-family: 'ARIAL', sans-serif;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.15;
            color: #071846;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        .otorga {
            margin-top: 5px;
            font-family: 'ARIAL', sans-serif;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.15;
            color: #071846;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        /*
        |--------------------------------------------------------------------------
        | Título
        |--------------------------------------------------------------------------
        */

        .titulo-diploma {
            position: absolute;
            top: 200px;
            left: 0;
            right: 0;
            z-index: 6;
            text-align: center;
            font-family: 'ARIAL', sans-serif;
            font-size: 120px;
            font-weight: 700;
            line-height: 1;
            color: #08265f;
            letter-spacing: 3px;
            text-shadow: 5px 5px 0 #efa56f;
            text-transform: uppercase;
        }

        .adorno-izquierdo {
            position: absolute;
            top: 300px;
            left: 100px;
            z-index: 6;
            width: 95px;
            height: 1.5px;
            background: #b5792f;
        }

        .adorno-derecho {
            position: absolute;
            top: 300px;
            right: 100px;
            z-index: 6;
            width: 95px;
            height: 1.5px;
            background: #b5792f;
        }

        /*
        |--------------------------------------------------------------------------
        | Alumno
        |--------------------------------------------------------------------------
        */

        .a-texto {
            position: absolute;
            top: 350px;
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
            top: 400px;
            left: 145px;
            right: 145px;
            z-index: 6;
            text-align: center;
            font-family: 'calibri', 'ARIAL', sans-serif;
            font-size: 40px;
            font-weight: 700;
            line-height: 1.05;
            color: #071846;
            letter-spacing: .6px;
            text-transform: uppercase;
        }

        .linea-alumno {
            position: absolute;
            top: 455px;
            left: 145px;
            right: 145px;
            z-index: 6;
            height: 3px;
            background: #eba55f;
        }

        /*
        |--------------------------------------------------------------------------
        | Descripción
        |--------------------------------------------------------------------------
        */

        .descripcion {
            position: absolute;
            top: 470px;
            left: 135px;
            right: 135px;
            z-index: 6;
            text-align: center;
            font-family: 'calibri', 'ARIAL', sans-serif;
            font-size: 17px;
            line-height: 1;
            color: #071846;
        }

        .descripcion strong {
            font-weight: 700;
            color: #071846;
        }

        /*
        |--------------------------------------------------------------------------
        | Datos académicos
        |--------------------------------------------------------------------------
        */

        .datos-extra {
            position: absolute;
            top: 530px;
            left: 150px;
            right: 150px;
            z-index: 6;
            width: calc(100% - 300px);
            border-collapse: collapse;
            table-layout: fixed;
        }

        .datos-extra td {
            width: 50%;
            padding: 2px 8px;
            text-align: center;
            font-family: 'calibri', 'ARIAL', sans-serif;
            font-size: 15px;
            color: #071846;
        }

        .datos-extra strong {
            font-weight: 700;
            color: #071846;
        }

        .fecha {
            position: absolute;
            top: 580px;
            left: 0;
            right: 0;
            z-index: 6;
            text-align: center;
            font-family: 'calibri', 'ARIAL', sans-serif;
            font-size: 14px;
            color: #071846;
            text-transform: uppercase;
        }

        .firmas {
            position: absolute;
            top: 625px;
            left: 10%;
            right: 10%;
            z-index: 6;
            width: 80%;
            border-collapse: collapse;
            table-layout: fixed;
            font-family: 'ARIAL', sans-serif;
            color: #071846;
        }

        .firmas td {
            text-align: center;
            vertical-align: top;
        }

        /*
|--------------------------------------------------------------------------
| Dos firmas: director y supervisor
|--------------------------------------------------------------------------
*/

        .firmas.dos-firmas td {
            width: 50%;
            padding: 10px 28px 0;
        }

        /*
|--------------------------------------------------------------------------
| Tres firmas: docente y director arriba, supervisor abajo
|--------------------------------------------------------------------------
*/

        .firmas.tres-firmas .fila-superior td {
            width: 50%;
            padding: 10px 28px 0;
        }

        .firmas.tres-firmas .fila-inferior td {
            padding-top: 0px;
        }

        .bloque-firma {
            width: 280px;
            margin: 0 auto;
        }

        /*
|--------------------------------------------------------------------------
| Espacio disponible para firmar
|--------------------------------------------------------------------------
*/

        .espacio-firma {
            height: 36px;
        }

        /*
|--------------------------------------------------------------------------
| Línea de firma
|--------------------------------------------------------------------------
*/

        .linea-firma {
            width: 100%;
            height: 1px;
            margin-bottom: 5px;
            background: #071846;
        }

        /*
|--------------------------------------------------------------------------
| Nombre debajo de la línea
|--------------------------------------------------------------------------
*/

        .firma-nombre {
            min-height: 16px;
            padding: 0 3px;
            font-family: 'ARIAL', sans-serif;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.15;
            color: #071846;
            text-transform: uppercase;
        }

        .cargo {
            margin-top: 3px;
            font-family: 'ARIAL', sans-serif;
            font-size: 9px;
            font-weight: 400;
            line-height: 1.15;
            color: #071846;
            text-transform: uppercase;
        }

        /*
        |--------------------------------------------------------------------------
        | Marca de agua
        |--------------------------------------------------------------------------
        */

        .watermark {
            position: absolute;
            top: 100px;
            bottom: 0;
            left: 240px;
            z-index: 1;
            width: 600px;
            opacity: .06;
        }
    </style>
</head>

<body>
    @php
        /*
        |--------------------------------------------------------------------------
        | Información institucional
        |--------------------------------------------------------------------------
        */

        $nombreEscuela = trim((string) ($escuela->nombre ?? 'Centro Universitario Moctezuma A.C.'));

        $nombreNivel = trim((string) ($nivel->nombre ?? 'Nivel educativo'));

        $cctNivel = trim((string) ($nivel->cct ?? ''));

        $secretariaTexto = 'SECRETARÍA DE EDUCACIÓN GUERRERO';

        /*
        |--------------------------------------------------------------------------
        | Información académica
        |--------------------------------------------------------------------------
        */

        $nombreGrado = trim((string) ($grado->nombre ?? ''));

        $numeroSemestre = $semestre?->numero;

        $nombreGrupo = trim((string) ($grupo?->asignacionGrupo?->nombre ?? 'S/G'));

        $nombreAlumnoFinal = mb_strtoupper(trim((string) ($nombreAlumno ?? 'NOMBRE DEL ALUMNO')), 'UTF-8');

        $nivelTexto = mb_strtoupper($nombreNivel !== '' ? $nombreNivel : 'NIVEL EDUCATIVO', 'UTF-8');

        $gradoTexto = mb_strtoupper($nombreGrado !== '' ? $nombreGrado : 'GRADO', 'UTF-8');

        $grupoTexto = mb_strtoupper($nombreGrupo !== '' ? $nombreGrupo : 'S/G', 'UTF-8');

        /*
        |--------------------------------------------------------------------------
        | Director
        |--------------------------------------------------------------------------
        */

        $directorPersona = $director?->director;

        $nombreDirector = trim(
            collect([
                $directorPersona?->titulo,
                $directorPersona?->nombre,
                $directorPersona?->apellido_paterno,
                $directorPersona?->apellido_materno,
            ])
                ->filter(fn($valor) => filled($valor))
                ->implode(' '),
        );

        $nombreDirectorFinal = mb_strtoupper($nombreDirector !== '' ? $nombreDirector : 'DIRECCIÓN ESCOLAR', 'UTF-8');

        /*
        |--------------------------------------------------------------------------
        | Supervisor
        |--------------------------------------------------------------------------
        */

        $supervisorPersona = $director?->supervisor;

        $nombreSupervisor = trim(
            collect([
                $supervisorPersona?->titulo,
                $supervisorPersona?->nombre,
                $supervisorPersona?->apellido_paterno,
                $supervisorPersona?->apellido_materno,
            ])
                ->filter(fn($valor) => filled($valor))
                ->implode(' '),
        );

        $nombreSupervisorFinal = mb_strtoupper(
            $nombreSupervisor !== '' ? $nombreSupervisor : 'SUPERVISIÓN ESCOLAR ',
            'UTF-8',
        );

        /*
        |--------------------------------------------------------------------------
        | Docente titular
        |--------------------------------------------------------------------------
        */

        $nombreDocente = trim(
            collect([$docente?->titulo, $docente?->nombre, $docente?->apellido_paterno, $docente?->apellido_materno])
                ->filter(fn($valor) => filled($valor))
                ->implode(' '),
        );

        $nombreDocenteFinal = mb_strtoupper($nombreDocente !== '' ? $nombreDocente : 'DOCENTE TITULAR', 'UTF-8');

        /*
        |--------------------------------------------------------------------------
        | Configuración de firmas
        |--------------------------------------------------------------------------
        |
        | En secundaria y bachillerato se omite el docente titular.
        | El director y el supervisor se muestran en todos los niveles.
        */

        $sinDocenteTitular = isset($mostrarSoloDirector)
            ? (bool) $mostrarSoloDirector
            : (bool) ($esSecundaria ?? false) || (bool) ($esBachillerato ?? false);

        /*
        |--------------------------------------------------------------------------
        | Descripción del diploma terminal
        |--------------------------------------------------------------------------
        */

        if ($esBachillerato) {
            $semestreTexto = $numeroSemestre ? $numeroSemestre . '° semestre' : 'sexto semestre';

            $descripcionTerminal =
                'Por haber concluido satisfactoriamente el ' .
                $semestreTexto .
                ' y sus estudios del nivel Bachillerato, demostrando dedicación, responsabilidad y esfuerzo durante su formación académica.';

            $datoEscolarTexto = mb_strtoupper($semestreTexto, 'UTF-8');

            $etiquetaDatoEscolar = 'Semestre y grupo:';
        } else {
            $descripcionTerminal =
                'Por haber concluido satisfactoriamente el ' .
                ($nombreGrado !== '' ? $nombreGrado . '° grado' : 'grado correspondiente') .
                ' y sus estudios del nivel ' .
                $nombreNivel .
                ', demostrando dedicación, responsabilidad y esfuerzo durante su formación académica.';

            $datoEscolarTexto = $gradoTexto;

            $etiquetaDatoEscolar = 'Grado y grupo:';
        }

        /*
        |--------------------------------------------------------------------------
        | Logos y fecha
        |--------------------------------------------------------------------------
        */

        $logoIzquierdoFinal = $logo_izquierdo ?? null;
        $logoDerechoFinal = $logo_derecho ?? null;

        $fechaDiploma =
            isset($fechaPdf) && trim((string) $fechaPdf) !== ''
                ? $fechaPdf
                : now()->locale('es')->translatedFormat('d \d\e F \d\e Y');
    @endphp

    <div class="diploma">

        @if (!empty($watermark))
            <img src="{{ $watermark }}" class="watermark" alt="Marca de agua">
        @endif

        <div class="logo-izquierdo">
            @if (!empty($logoIzquierdoFinal))
                <img src="{{ $logoIzquierdoFinal }}" alt="Logo Centro Universitario Moctezuma">
            @endif
        </div>

        <div class="logo-derecho">
            @if (!empty($logoDerechoFinal))
                <img src="{{ $logoDerechoFinal }}" alt="Logotipo del nivel educativo">
            @endif
        </div>

        <div class="encabezado">
            <div class="secretaria">
                {{ mb_strtoupper($secretariaTexto, 'UTF-8') }}
            </div>

            <div class="escuela">
                {{ mb_strtoupper($nombreEscuela, 'UTF-8') }}
            </div>

            @if ($cctNivel !== '')
                <div class="cct">
                    {{ str_starts_with(mb_strtoupper($cctNivel, 'UTF-8'), 'C.C.T.')
                        ? mb_strtoupper($cctNivel, 'UTF-8')
                        : 'C.C.T. ' . mb_strtoupper($cctNivel, 'UTF-8') }}
                </div>
            @endif

            <div class="otorga">
                Otorga el presente
            </div>
        </div>

        <div class="adorno-izquierdo"></div>
        <div class="adorno-derecho"></div>

        <div class="titulo-diploma">
            DIPLOMA
        </div>

        <div class="a-texto">
            A:
        </div>

        <div class="alumno">
            {{ $nombreAlumnoFinal }}
        </div>

        <div class="linea-alumno"></div>

        <div class="descripcion">
            {!! preg_replace('/(' . preg_quote($nombreNivel, '/') . ')/iu', '<strong>$1</strong>', e($descripcionTerminal)) !!}
        </div>

        <table class="datos-extra">
            <tr>
                <td>
                    <strong>
                        {{ $etiquetaDatoEscolar }}
                    </strong>

                    {{ $datoEscolarTexto }} · Grupo {{ $grupoTexto }}
                </td>

                <td>
                    <strong>
                        Ciclo escolar:
                    </strong>

                    {{ $cicloEscolarTexto ?? '—' }}
                </td>
            </tr>
        </table>

        <div class="fecha">
            Cd. Altamirano, Guerrero, a {{ $fechaDiploma }}
        </div>

        @if ($sinDocenteTitular)
            {{-- Director y supervisor lado a lado --}}
            <table class="firmas dos-firmas">
                <tr>
                    <td>
                        <div class="bloque-firma">
                            <div class="espacio-firma"></div>

                            <div class="linea-firma"></div>

                            <div class="firma-nombre">
                                {{ $nombreDirectorFinal }}
                            </div>

                            <div class="cargo">
                                Dirección escolar
                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="bloque-firma">
                            <div class="espacio-firma"></div>

                            <div class="linea-firma"></div>

                            <div class="firma-nombre">
                                {{ $nombreSupervisorFinal }}
                            </div>

                            <div class="cargo">
                                Supervisión escolar zona {{ $supervisorPersona->zona_escolar }}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        @else
            {{-- Docente y director arriba; supervisor abajo --}}
            <table class="firmas tres-firmas">
                <tr class="fila-superior">
                    <td>
                        <div class="bloque-firma">
                            <div class="espacio-firma"></div>

                            <div class="linea-firma"></div>

                            <div class="firma-nombre">
                                {{ $nombreDocenteFinal }}
                            </div>

                            <div class="cargo">
                                Docente titular
                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="bloque-firma">
                            <div class="espacio-firma"></div>

                            <div class="linea-firma"></div>

                            <div class="firma-nombre">
                                {{ $nombreDirectorFinal }}
                            </div>

                            <div class="cargo">
                                Dirección escolar
                            </div>
                        </div>
                    </td>
                </tr>

                <tr class="fila-inferior">
                    <td colspan="2">
                        <div class="bloque-firma">
                            <div class="espacio-firma"></div>

                            <div class="linea-firma"></div>

                            <div class="firma-nombre">
                                {{ $nombreSupervisorFinal }}
                            </div>

                            <div class="cargo">
                                Supervisión escolar zona {{ $supervisorPersona->zona_escolar }}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        @endif
    </div>
</body>

</html>
