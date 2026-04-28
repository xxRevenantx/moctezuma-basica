<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Lista de asistencia</title>

    @php
        $slugNivel = $nivel->slug ?? '';

        // Preescolar y primaria usan colores pastel.
        // Secundaria y bachillerato usan tonos grises.
        $esPastel = in_array($slugNivel, ['preescolar', 'primaria']);

        $colorBordePagina = $esPastel ? '#f472b6' : '#64748b';
        $colorFondoPrincipal = $esPastel ? '#f9a8d4' : '#cbd5e1';
        $colorFondoAlumno = $esPastel ? '#fbcfe8' : '#e2e8f0';

        $colorSemana1 = $esPastel ? '#fdba74' : '#d1d5db';
        $colorSemana2 = $esPastel ? '#7dd3fc' : '#cbd5e1';
        $colorSemana3 = $esPastel ? '#bef264' : '#e5e7eb';
        $colorSemana4 = $esPastel ? '#fde68a' : '#d1d5db';

        $colorAsistencia = $esPastel ? '#38bdf8' : '#cbd5e1';
        $colorInasistencia = $esPastel ? '#fde047' : '#e5e7eb';
        $colorJustificante = $esPastel ? '#f9a8d4' : '#f1f5f9';

        $diasSemana = ['L', 'M', 'M', 'J', 'V'];

        $totalDias = 20;
        $totalColumnas = 2 + $totalDias + 3;
    @endphp

    <style>
        @page {
            margin: 8px 25px;
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

        /*
        @font-face {
            font-family: 'Claphappy';
            font-style: normal;
            src: url('{{ storage_path('fonts/Claphappy.ttf') }}') format('truetype');
        } */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'ARIAL', DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 10px;
        }



        .contenido {
            position: relative;
            z-index: 2;
            font-family: 'Claphappy', DejaVu Sans, sans-serif;
        }

        .titulo {
            text-align: center;
            font-size: 23px;
            line-height: 1;
            margin-top: -4px;
            margin-bottom: 5px;
            font-family: 'Claphappy', DejaVu Sans, sans-serif;
        }

        .datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .datos td {
            border: none;
            padding: 0 4px;
            vertical-align: middle;
        }

        .label {

            /* white-space: nowrap; */
        }

        .campo {
            display: inline-block;
            height: 22px;
            min-width: 150px;
            padding: 2px 8px;
            border: 1px solid #374151;
            border-radius: 5px;
            background: {{ $esPastel ? '#f5b4e7' : '#e5e7eb' }};
            /* font-weight: 700; */
            text-transform: uppercase;
        }

        .campo-largo {
            min-width: 300px;
        }

        .campo-corto {
            min-width: 100px;
            text-align: center;
        }

        .campo-ciclo {
            /* min-width: 125px; */
            text-align: center;
        }

        .tabla-asistencia {
            width: 100%;
            border-collapse: collapse;
            /* table-layout: fixed; */
            font-size: 8px;
            font-family: 'Claphappy', DejaVu Sans, sans-serif;
        }

        .tabla-asistencia th,
        .tabla-asistencia td {
            border: 1px solid #111827;
            padding: 0;
            font-family: 'Claphappy', DejaVu Sans, sans-serif;
        }

        .th-numero {
            background: {{ $colorFondoPrincipal }};
            font-size: 15px;
            /* font-weight: 700; */
            text-align: center;
            width: 20px;
        }

        .th-alumno {
            /* width: 210px;
            height: 136px; */
            background: {{ $colorFondoAlumno }};
            text-align: center;
            width: 0px;
            font-family: Claphappy, DejaVu Sans, sans-serif;
            font-size: 13px;
        }

        .nombre-titulo {
            /* display: block;
            margin-top: 4px;
            font-size: 14px;
            font-weight: 400; */
        }

        .mes {
            background: {{ $colorFondoPrincipal }};
            font-size: 18px;
            /* font-weight: 400; */
            text-align: center;
        }

        .semana {
            height: 28px;
            font-size: 12px;
            /* font-weight: 700; */
            width: 5px;
            text-align: center;
        }

        .semana-1 {
            background: {{ $colorSemana1 }};
        }

        .semana-2 {
            background: {{ $colorSemana2 }};
        }

        .semana-3 {
            background: {{ $colorSemana3 }};
        }

        .semana-4 {
            background: {{ $colorSemana4 }};
        }

        .dia {
            height: 22px;
            background: #f8fafc;
            font-size: 9px;
            text-align: center;
        }

        .dia-1 {
            background: {{ $esPastel ? '#ffedd5' : '#f8fafc' }};
        }

        .dia-2 {
            background: {{ $esPastel ? '#e0f2fe' : '#f8fafc' }};
        }

        .dia-3 {
            background: {{ $esPastel ? '#ecfccb' : '#f8fafc' }};
        }

        .dia-4 {
            background: {{ $esPastel ? '#fef3c7' : '#f8fafc' }};
        }

        .col-dia {
            width: 2px;
        }

        .resumen {
            /* width: 27px;
            height: 136px;
            text-align: center;
            vertical-align: middle;
            font-weight: 700; */
            width: 0px;
            height: 0px;
        }

        .resumen-asistencia {
            background: {{ $colorAsistencia }};
        }

        .resumen-inasistencia {
            background: {{ $colorInasistencia }};
        }

        .resumen-justificante {
            background: {{ $colorJustificante }};
        }

        .texto-vertical {
            writing-mode: vertical-rl;
            transform: rotate(-90deg);
            display: inline-block;
            /* white-space: nowrap; */
            font-size: 10px;
            width: 5px;
        }

        .fila-alumno td {
            height: 20px;
            background: #ffffff;
            width: 20px;
        }

        .numero {
            text-align: center;
            font-size: 10px;
        }

        .alumno {
            padding-left: 5px !important;
            font-size: 10px;
            text-transform: uppercase;

        }

        .celda {
            background: #ffffff;
        }

        .sin-alumnos {
            text-align: center;
            font-size: 12px;
            /* font-weight: 700; */
            padding: 12px;
        }

        .footer {
            position: fixed;
            left: 18px;
            right: 18px;
            bottom: 5px;
            text-align: center;
            font-size: 8px;
            color: #475569;
            border-top: 1px solid #94a3b8;
            padding-top: 3px;
        }

        .footer p {
            margin: 0;
            line-height: 1.2;
        }

        .encabezado {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .encabezado td {
            vertical-align: top;
            border: none;
        }

        .logo-izquierdo {
            /* width: 205px; */
            text-align: left;
        }

        .logo-izquierdo img {
            width: 100px;
            /* max-height: 100px; */
            object-fit: contain;
        }

        .logo-derecho {
            width: 130px;
            text-align: right;
        }

        .logo-derecho img {
            width: 100px;
            object-fit: contain;
        }

        .titulo-centro {
            text-align: center;
        }

        .nombre-escuela {
            display: inline-block;
            color: #5b6470;
            font-size: 21px;
            font-weight: bold;
            letter-spacing: 0.5px;
            border-top: 1px solid #9ca3af;
            border-bottom: 1px solid #9ca3af;
            padding: 0 10px 1px 10px;
            margin-bottom: 3px;
        }
    </style>
</head>

<body>
    <div class="pagina">
        <div class="contenido">

            <table class="encabezado">
                <tr>
                    <td class="logo-izquierdo">
                        @if ($logoIzquierdo)
                            <img src="{{ $logoIzquierdo }}" alt="">
                        @endif
                    </td>

                    <td class="titulo-centro">
                        <div class="nombre-escuela">
                            {{ strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA') }}
                        </div>

                        <div class="titulo-lista">
                            LISTA DE EVALUACIÓN
                        </div>

                        <div class="ciclo">
                            CICLO ESCOLAR: {{ $cicloEscolar }}
                        </div>

                        <div class="direccion">
                            {{ $escuela->calle ?? 'Francisco I. Madero Ote.' }}
                            @if (!empty($escuela->no_exterior))
                                #{{ $escuela->no_exterior }},
                            @endif
                            @if (!empty($escuela->colonia))
                                Col. {{ $escuela->colonia }},
                            @endif
                            @if (!empty($escuela->ciudad))
                                {{ $escuela->ciudad }},
                            @endif
                            @if (!empty($escuela->municipio))
                                {{ $escuela->municipio }},
                            @endif
                            @if (!empty($escuela->estado))
                                {{ $escuela->estado }}.
                            @endif

                            @if (!empty($escuela->telefono))
                                Tel. {{ $escuela->telefono }}
                            @endif
                        </div>
                    </td>

                    <td class="logo-derecho">
                        @if ($logoDerecho)
                            <img src="{{ $logoDerecho }}" alt="">
                        @endif
                    </td>
                </tr>
            </table>

            <table class="datos">
                <tr>
                    <td style="width: 90px;">
                        <span class="label">Maestro(a):</span>
                    </td>

                    <td>
                        <span class="campo campo-largo">
                            {{ strtoupper($nombreDocente) }}
                        </span>
                    </td>

                    <td style="width: 95px; text-align: right;">
                        <span class="label">Ciclo Escolar:</span>
                    </td>

                    <td style="width: 135px;">
                        <span class="campo campo-ciclo">
                            {{ $cicloEscolar }}
                        </span>
                    </td>

                    <td style="width: 60px; text-align: right;">
                        <span class="label">Grado:</span>
                    </td>

                    <td style="width: 120px;">
                        <span class="campo campo-corto">
                            {{ $grado->nombre }}
                        </span>
                    </td>
                </tr>
            </table>

            <table class="tabla-asistencia" style="font-family: ARIAL">
                <thead>
                    <tr>
                        <th rowspan="3" class="th-numero">
                            #
                        </th>

                        <th rowspan="3" class="th-alumno">
                            Nombre y Apellidos

                        </th>

                        <th colspan="20" class="mes">
                            Mes:______________________________
                        </th>

                        <th rowspan="3" class="resumen resumen-asistencia">
                            <span class="texto-vertical">Asistencia</span>
                        </th>

                        <th rowspan="3" class="resumen resumen-inasistencia">
                            <span class="texto-vertical">Inasistencias</span>
                        </th>

                        <th rowspan="3" class="resumen resumen-justificante">
                            <span class="texto-vertical">Justificantes</span>
                        </th>
                    </tr>

                    <tr>
                        <th colspan="5" class="semana semana-1">Semana 1</th>
                        <th colspan="5" class="semana semana-2">Semana 2</th>
                        <th colspan="5" class="semana semana-3">Semana 3</th>
                        <th colspan="5" class="semana semana-4">Semana 4</th>
                    </tr>

                    <tr>
                        @for ($semana = 1; $semana <= 4; $semana++)
                            @foreach ($diasSemana as $dia)
                                <th class="dia dia-{{ $semana }} col-dia">
                                    {{ $dia }}
                                </th>
                            @endforeach
                        @endfor
                    </tr>
                </thead>

                <tbody>
                    @forelse ($alumnos as $alumno)
                        <tr class="fila-alumno">
                            <td class="numero">
                                {{ $loop->iteration }}
                            </td>

                            <td class="alumno">
                                {{ $alumno->apellido_paterno }}
                                {{ $alumno->apellido_materno }}
                                {{ $alumno->nombre }}
                            </td>

                            @for ($i = 1; $i <= 20; $i++)
                                <td class="celda"></td>
                            @endfor

                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $totalColumnas }}" class="sin-alumnos">
                                No hay alumnos activos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse

                    @for ($i = $alumnos->count() + 1; $i <= 13; $i++)
                        <tr class="fila-alumno">
                            <td class="numero">
                                {{ $i }}
                            </td>

                            <td class="alumno"></td>

                            @for ($d = 1; $d <= 20; $d++)
                                <td class="celda"></td>
                            @endfor

                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        <p>
            {{ strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA') }}
            · C.C.T. {{ $nivel->cct ?? '—' }}
        </p>

        <p>
            C.
            {{ $escuela->calle ?? '' }}
            No.
            {{ $escuela->no_exterior ?? '' }},
            Col.
            {{ $escuela->colonia ?? '' }},
            C.P.
            {{ $escuela->codigo_postal ?? '' }},
            Cd.
            {{ $escuela->ciudad ?? '' }},
            {{ $escuela->estado ?? '' }}.
        </p>

        <p>
            Fecha de expedición:
            {{ now()->translatedFormat('d \\d\\e F \\d\\e\\l Y \\a \\l\\a\\s H:i') }}
        </p>
    </div>
</body>

</html>
