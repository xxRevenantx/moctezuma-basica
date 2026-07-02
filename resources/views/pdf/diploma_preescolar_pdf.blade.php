<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Diploma Preescolar</title>

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
            background-image: url("{{ public_path('imagenes/diploma_preescolar_2.jpg') }}");
            background-size: 100% 100%;
            background-position: center center;
            background-repeat: no-repeat;
        }

        .logo-izquierdo {
            position: absolute;
            top: 104px;
            left: 118px;
            width: 130px;
            text-align: center;
            z-index: 5;
        }

        .logo-izquierdo img {
            max-width: 130px;
            max-height: 130px;
        }

        .logo-derecho {
            position: absolute;
            top: 110px;
            right: 90px;
            width: 200px;
            text-align: center;
            z-index: 5;
        }

        .logo-derecho img {
            max-width: 200px;
            max-height: 200px;
        }

        .encabezado {
            position: absolute;
            top: 95px;
            left: 280px;
            right: 280px;
            text-align: center;
            z-index: 6;
            line-height: 0;
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
            font-family: 'ARIAL', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #c98626;
            letter-spacing: .4px;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .cct {
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
            top: 250px;
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
            top: 350px;
            left: 100px;
            width: 95px;
            height: 1.5px;
            background: #b5792f;
            z-index: 6;
        }

        .adorno-derecho {
            position: absolute;
            top: 350px;
            right: 100px;
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
            left: 145px;
            right: 145px;
            z-index: 6;
            text-align: center;
            font-family: 'calibri', 'ARIAL', sans-serif;
            font-size: 18px;
            line-height: 1;
            color: #071846;
        }

        .descripcion strong {
            font-weight: 700;
            color: #071846;
        }



        .datos-extra {
            position: absolute;
            top: 540px;
            left: 150px;
            right: 150px;
            z-index: 6;
            width: calc(100% - 300px);
            border-collapse: collapse;
        }

        .datos-extra td {
            width: 50%;
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
            top: 650px;
            left: 13%;
            right: 13%;
            z-index: 6;
            width: 74%;
            border-collapse: separate;
            border-spacing: 28px 12px;
            font-family: 'ARIAL', sans-serif;
            color: #071846;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 12px;

        }

        .firma-supervisor {
            padding-top: 15px !important;
        }

        .firma-linea {
            width: 70%;
            height: 1px;
            margin: 0 auto 5px;
            border-top: 1.5px solid #071846;
        }

        .firma-nombre {
            min-height: 18px;
            font-size: 14px;
            line-height: 1.05;
            font-weight: 700;
            text-transform: uppercase;
        }

        .cargo {
            margin-top: 3px;
            font-size: 9px;
            line-height: 1.1;
            font-weight: 400;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    @php
        $nombreAlumno = trim(
            ($alumno->nombre ?? '') . ' ' . ($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? ''),
        );

        $nombreAlumno = mb_strtoupper($nombreAlumno ?: 'NOMBRE DEL ALUMNO', 'UTF-8');

        $nivel = $alumno->nivel ?? null;
        $grado = $alumno->grado ?? null;
        $grupo = $alumno->grupo ?? null;
        $generacion = $alumno->generacion ?? null;

        $secretariaTexto = 'SECRETARÍA DE EDUCACIÓN GUERRERO';

        $nombreEscuelaDiploma = 'JARDÍN DE NIÑOS PART. CENTRO UNIVERSITARIO MOCTEZUMA';

        $cctDiploma = data_get($nivel, 'cct') ?: 'C.C.T. 12PJN0226W';

        $cicloEscolarTexto = $cicloEscolar
            ? ($cicloEscolar->inicio_anio ?? '') . '-' . ($cicloEscolar->fin_anio ?? '')
            : '—';

        $gradoTexto = mb_strtoupper($grado->nombre ?? 'GRADO', 'UTF-8');

        $grupoTexto = mb_strtoupper($grupo->asignacionGrupo?->nombre ?? 'S/G', 'UTF-8');

        $logoIzquierdoFinal = $logoPrincipal ?? null;
        $logoDerechoFinal = $logoPenacho ?? null;

        $educadoraFinal = mb_strtoupper($educadoraNombre ?: 'NOMBRE DE LA EDUCADORA', 'UTF-8');
        $directoraFinal = mb_strtoupper($directoraNombre ?: 'NOMBRE DE LA DIRECTORA', 'UTF-8');
        $supervisoraFinal = mb_strtoupper($supervisoraNombre ?: 'NOMBRE DEL SUPERVISOR', 'UTF-8');
    @endphp

    <div class="diploma">

        <div class="logo-izquierdo">
            @if (!empty($logoIzquierdoFinal))
                <img src="{{ $logoIzquierdoFinal }}" alt="Logo Centro Universitario Moctezuma">
            @endif
        </div>

        <div class="logo-derecho">
            @if (!empty($logoDerechoFinal))
                <img src="{{ $logoDerechoFinal }}" alt="Preescolar">
            @endif
        </div>

        <div class="encabezado">
            <div class="secretaria">
                {{ mb_strtoupper($secretariaTexto, 'UTF-8') }}
            </div>

            <div class="escuela">
                {{ mb_strtoupper($nombreEscuelaDiploma, 'UTF-8') }}
            </div>

            <div class="cct">
                {{ mb_strtoupper($cctDiploma, 'UTF-8') }}
            </div>

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
            {{ $nombreAlumno }}
        </div>

        <div class="linea-alumno"></div>

        <div class="descripcion">
            Por haber terminado satisfactoriamente sus estudios en el
            <strong>nivel preescolar</strong>, demostrando dedicación, alegría
            y esfuerzo durante su formación inicial.
        </div>


        <table class="datos-extra">
            <tr>
                <td>
                    <strong>Grado y grupo:</strong>
                    {{ $gradoTexto }} · Grupo {{ $grupoTexto }}
                </td>

                <td>
                    <strong>Ciclo escolar:</strong>
                    {{ $cicloEscolarTexto }}
                </td>
            </tr>
        </table>

        <div class="fecha">
            Cd. Altamirano, Guerrero, a {{ $fechaPdf }}
        </div>

        <table class="firmas">
            <tr>
                <td>
                    <div class="firma-linea"></div>
                    <div class="firma-nombre">
                        {{ $educadoraFinal }}
                    </div>
                    <div class="cargo">
                        Educadora
                    </div>
                </td>

                <td>
                    <div class="firma-linea"></div>
                    <div class="firma-nombre">
                        {{ $directoraFinal }}
                    </div>
                    <div class="cargo">
                        Directora de la escuela
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="firma-supervisor">
                    <div class="firma-linea" style="width: 40%;"></div>
                    <div class="firma-nombre">
                        {{ $supervisoraFinal }}
                    </div>
                    <div class="cargo">
                        Supervisora escolar · Zona 137, Cd. Altamirano, Gro.
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
