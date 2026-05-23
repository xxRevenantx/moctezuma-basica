<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>{{ $titulo ?? 'Diploma de promedio' }}</title>

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
            top: 485px;
            left: 150px;
            right: 150px;
            z-index: 6;
            text-align: center;
            font-family: 'calibri', 'ARIAL', sans-serif;
            font-size: 16px;
            line-height: 1.15;
            color: #071846;
        }

        .descripcion strong {
            font-weight: 700;
            color: #071846;
        }

        .datos-extra {
            position: absolute;
            top: 555px;
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
            top: 605px;
            left: 50%;
            margin-left: -85px;
            width: 170px;
            z-index: 6;
            text-align: center;
            border: 1.5px solid #c98626;
            border-radius: 12px;
            padding: 6px 6px;
            background: rgba(255, 255, 255, .88);
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
            font-size: 26px;
            font-weight: 700;
            color: #071846;
        }

        .periodos-box {
            position: absolute;
            top: 605px;
            left: 150px;
            width: 250px;
            z-index: 6;
            border: 1.5px solid #c98626;
            border-radius: 12px;
            padding: 7px 10px;
            background: rgba(255, 255, 255, .88);
        }

        .periodos-titulo {
            font-family: 'ARIAL', sans-serif;
            font-size: 10px;
            font-weight: 700;
            color: #c98626;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
            margin-bottom: 4px;
        }

        .periodos-tabla {
            width: 100%;
            border-collapse: collapse;
        }

        .periodos-tabla td {
            font-family: 'calibri', 'ARIAL', sans-serif;
            font-size: 12px;
            color: #071846;
            padding: 1px 3px;
            border-bottom: 1px solid rgba(201, 134, 38, .25);
        }

        .periodos-tabla .valor {
            text-align: right;
            font-weight: 700;
        }

        .estado-box {
            position: absolute;
            top: 605px;
            right: 150px;
            width: 250px;
            z-index: 6;
            text-align: center;
            border: 1.5px solid #c98626;
            border-radius: 12px;
            padding: 8px 10px;
            background: rgba(255, 255, 255, .88);
        }

        .estado-label {
            font-family: 'ARIAL', sans-serif;
            font-size: 10px;
            font-weight: 700;
            color: #c98626;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .estado {
            margin-top: 3px;
            font-family: 'ARIAL', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #071846;
            text-transform: uppercase;
        }

        .firmas {
            font-family: 'ARIAL', sans-serif;
            width: 100%;
            margin-top: 690px;
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
    @php
        /*
         * Se preparan datos seguros para evitar errores si alguna variable no llega.
         */
        $nombreNivel = mb_strtolower($nivel->nombre ?? '', 'UTF-8');

        $secretariaTexto = $secretariaTexto ?? 'SECRETARÍA DE EDUCACIÓN GUERRERO';

        $nombreEscuelaDiploma =
            $nombreEscuelaDiploma ??
            match (true) {
                str_contains($nombreNivel, 'preescolar') => 'JARDÍN DE NIÑOS PART. CENTRO UNIVERSITARIO MOCTEZUMA',
                str_contains($nombreNivel, 'primaria') => 'ESC.PRIM.PART. CENTRO UNIVERSITARIO MOCTEZUMA',
                str_contains($nombreNivel, 'secundaria') => 'ESC.SEC.PART. CENTRO UNIVERSITARIO MOCTEZUMA',
                str_contains($nombreNivel, 'bachillerato') => 'BACHILLERATO GENERAL CENTRO UNIVERSITARIO MOCTEZUMA',
                default => mb_strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA', 'UTF-8'),
            };

        $cctDiploma =
            $cctDiploma ??
            match (true) {
                str_contains($nombreNivel, 'preescolar') => data_get($nivel, 'cct') ?: 'C.C.T. 12PJN0226W',
                str_contains($nombreNivel, 'primaria') => data_get($nivel, 'cct') ?: 'C.C.T. 12PPR0070B',
                str_contains($nombreNivel, 'secundaria') => data_get($nivel, 'cct') ?: 'C.C.T. 12PES0105U',
                str_contains($nombreNivel, 'bachillerato') => data_get($nivel, 'cct') ?: 'C.C.T. 12PBH0071R',
                default => data_get($nivel, 'cct') ?: data_get($escuela, 'cct') ?: 'C.C.T. NO EXISTE',
            };

        $logoIzquierdoFinal = $logoIzquierdo ?? ($logo_izquierdo ?? null);
        $logoDerechoFinal = $logoDerecho ?? ($logo_derecho ?? null);

        $alumnoNombreFinal = mb_strtoupper($alumnoNombre ?? ($nombreAlumno ?? 'NOMBRE DEL ALUMNO'), 'UTF-8');

        $periodosResumen = collect($periodosResumen ?? []);
        $promediosPeriodos = collect($promediosPeriodos ?? []);

        $textoDocumento = !empty($esBachillerato) ? 'promedio semestral' : 'promedio anual';

        $textoPeriodoDocumento = !empty($esBachillerato)
            ? 'los parciales correspondientes'
            : 'los periodos correspondientes';

        $estadoTexto = mb_strtoupper($estadoPromedio ?? 'SIN DATOS', 'UTF-8');
    @endphp

    <div class="diploma">

        <div class="logo-izquierdo">
            @if (!empty($logoIzquierdoFinal))
                <img src="{{ $logoIzquierdoFinal }}" alt="Logo Centro Universitario Moctezuma">
            @endif
        </div>

        <div class="logo-derecho">
            @if (!empty($logoDerechoFinal))
                <img src="{{ $logoDerechoFinal }}" alt="Logo institucional">
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
            Diploma
        </div>

        <div class="a-texto">
            A:
        </div>

        <div class="alumno">
            {{ $alumnoNombreFinal }}
        </div>

        <div class="linea-alumno"></div>

        <div class="descripcion">
            Por haber obtenido un destacado desempeño académico con
            <strong>{{ $textoDocumento }}</strong> durante
            <strong>{{ $textoPeriodoDocumento }}</strong>
            del ciclo escolar <strong>{{ $cicloEscolarTexto ?? '—' }}</strong>,
            correspondiente al
            @if (!empty($esBachillerato) && !empty($semestre))
                <strong>{{ $semestre->numero ?? '—' }}° semestre</strong>
            @endif

            @if (!empty($grado))
                del <strong>{{ mb_strtoupper($grado->nombre ?? 'GRADO', 'UTF-8') }}</strong> grado
            @endif

            de <strong>{{ mb_strtoupper($nivel->nombre ?? 'NIVEL', 'UTF-8') }}</strong>,
            grupo "<strong>{{ mb_strtoupper($grupo->asignacionGrupo?->nombre ?? 'GRUPO', 'UTF-8') }}</strong>".
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

                <td>
                    <strong>Documento:</strong>
                    {{ !empty($esBachillerato) ? 'Semestral' : 'Anual' }}
                </td>
            </tr>
        </table>

        <div class="periodos-box">
            <div class="periodos-titulo">
                {{ !empty($esBachillerato) ? 'Promedio por parcial' : 'Promedio por periodo' }}
            </div>

            <table class="periodos-tabla">
                @forelse ($periodosResumen as $numeroPeriodo => $periodoResumen)
                    <tr>
                        <td>
                            {{ $periodoResumen['nombre'] ?? (!empty($esBachillerato) ? 'Parcial ' . $numeroPeriodo : 'Periodo ' . $numeroPeriodo) }}
                        </td>

                        <td class="valor">
                            {{ $promediosPeriodos[$numeroPeriodo] ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td>Sin periodos</td>
                        <td class="valor">—</td>
                    </tr>
                @endforelse
            </table>
        </div>

        <div class="promedio-box">
            <div class="promedio-label">
                Promedio final
            </div>

            <div class="promedio">
                {{ $promedio ?? '—' }}
            </div>
        </div>

        <div class="estado-box">
            <div class="estado-label">
                Estado académico
            </div>

            <div class="estado">
                {{ $estadoTexto }}
            </div>
        </div>

        <table class="firmas">
            <tr>
                <td style="width: 100%; padding-top: 60px; text-align: center;">
                    <u>{{ mb_strtoupper(trim((optional($director->director)->titulo ?? '') . ' ' . (optional($director->director)->nombre ?? '') . ' ' . (optional($director->director)->apellido_paterno ?? '') . ' ' . (optional($director->director)->apellido_materno ?? '')) ?: '____________________________', 'UTF-8') }}</u><br>

                    @if (optional($director->director)->genero === 'F')
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
