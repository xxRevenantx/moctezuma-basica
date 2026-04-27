<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Lista de evaluación</title>

    <style>
        @page {
            margin: 15px 26px 0;
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

        * {
            box-sizing: border-box;
        }

        body {
            font-family: ARIAL,
                color: #001333;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .pagina {
            position: relative;
            width: 100%;
            min-height: 100%;
        }

        .marca-agua {
            position: absolute;
            top: 50px;
            left: 220px;
            width: 560px;
            opacity: 0.10;
            z-index: 0;
        }

        .contenido {
            position: relative;
            z-index: 2;
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

        .titulo-lista {
            font-size: 18px;
            font-weight: normal;
            color: #001333;
            margin-top: 2px;
            line-height: 1.1;
        }

        .ciclo {
            font-size: 17px;
            font-weight: bold;
            color: #001333;
            margin-top: 1px;
            line-height: 1.1;
        }

        .direccion {
            font-size: 10px;
            color: #001333;
            line-height: 1.25;
            margin-top: 2px;
        }

        .datos {
            margin-top: 8px;
            font-size: 13px;
            color: #001333;
        }

        .datos p {
            margin: 0 0 8px 0;
        }

        .subrayado {
            text-decoration: underline;
            font-weight: bold;
        }

        .docente {
            text-decoration: underline;
            text-transform: uppercase;
        }

        .linea-centro {
            text-align: center;
            font-size: 13px;
            margin-top: 12px;
        }

        .linea-centro span {
            margin: 0 8px;
        }

        .fechas {
            text-align: center;
            font-size: 13px;
            margin-top: 12px;
            margin-bottom: 10px;
        }

        .linea-fecha {
            display: inline-block;
            width: 185px;
            border-bottom: 1px solid #001333;
            height: 13px;
            vertical-align: bottom;
        }

        .tabla-evaluacion {
            width: 100%;
            border-collapse: collapse;
            /* table-layout: fixed; */
            font-size: 8px;
        }

        .tabla-evaluacion th,
        .tabla-evaluacion td {
            border: 1px solid #333;
            /* padding: 2px 3px; */
        }

        .tabla-evaluacion thead th {
            background: #c7c7c7;
            /* color: #001333; */
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
        }

        .col-numero {
            width: 5px;
            text-align: center;

        }

        .col-alumno {
            text-align: center;
            width: 300px;
        }

        .col-materia {
            /* width: 52px;
            height: 165px; */
            /* padding: 0; */
            /* vertical-align: bottom; */
        }

        .materia-vertical {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            display: inline-block;
            font-size: 8px;
            line-height: 1;
            max-height: 155px;
        }

        .col-promedio {
            /* width: 58px;
            height: 165px; */
        }

        .tbody-numero {
            text-align: center;
            width: 5px;
            font-size: 12px;
        }

        .tbody-alumno {
            /* text-align: left; */
            font-size: 14px;
            /* white-space: nowrap; */
            overflow: hidden;
        }

        .fila-alumno td {
            height: 19px;
        }

        .celda-calificacion {
            height: 19px;
        }

        .texto-promedio {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            display: inline-block;
            font-size: 8px;
        }

        .sin-materias {
            text-align: center;
            font-size: 12px;
            padding: 14px;
        }

        .rotate {
            /* writing-mode: vertical-rl; */
            /* transform: rotate(-90deg); */
            /* white-space: nowrap; */
            /* width: 50px;
            rotate: 270deg;
            height: 70px; */

        }

        footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 12px;
            text-align: center;
            font-size: 10px;
            color: #475569;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
        }

        footer p {
            margin: 0;
            line-height: 1.25;
        }
    </style>
</head>

<body>
    <div class="pagina">

        @if ($marcaAgua)
            <img src="{{ $marcaAgua }}" class="marca-agua" alt="">
        @endif

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

            <div class="datos">
                <p>
                    Nivel:
                    <span class="subrayado">{{ strtoupper($nivel->nombre) }}</span>
                </p>

                <p>
                    Nombre del Docente:
                    <span class="docente">{{ strtoupper($nombreDocente) }}</span>
                </p>
            </div>

            <div class="linea-centro">
                <span>
                    Periodo No:
                    <span class="subrayado">{{ $periodoNumero }}</span>
                </span>

                <span>
                    Grado:
                    <span class="subrayado">{{ $grado->nombre }}</span>
                </span>

                <span>
                    Grupo:
                    <span class="subrayado">"{{ $grupo->nombre }}"</span>
                </span>

                <span>
                    Turno:
                    <span class="subrayado">{{ $turno }}</span>
                </span>
            </div>

            <div class="fechas">
                que comprende las fechas
                <span class="linea-fecha">
                    {{ $fechaInicio ?? '' }}
                </span>
                al
                <span class="linea-fecha">
                    {{ $fechaFin ?? '' }}
                </span>
            </div>

            @if ($materias->isEmpty())
                <div class="sin-materias">
                    No hay materias calificables asignadas para este grupo.
                </div>
            @else
                <table class="tabla-evaluacion">
                    <thead>
                        <tr>
                            <th class="col-numero">
                                No.
                            </th>

                            <th class="col-alumno">
                                NOMBRE DEL ALUMNO
                            </th>

                            @foreach ($materias as $materia)
                                <th class="col-materia">
                                    <div style="text-align:center;  text-transform:uppercase" class="rotate">
                                        {{ $materia->materia }}
                                    </div>

                                </th>
                            @endforeach

                            <th class="col-promedio">
                                <span>
                                    PROMEDIO
                                </span>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($alumnos as $alumno)
                            <tr class="fila-alumno">
                                <td class="tbody-numero">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="tbody-alumno">
                                    {{ $alumno->apellido_paterno }}
                                    {{ $alumno->apellido_materno }}
                                    {{ $alumno->nombre }}
                                </td>

                                @foreach ($materias as $materia)
                                    <td class="celda-calificacion"></td>
                                @endforeach

                                <td></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $materias->count() + 3 }}" class="sin-materias">
                                    No hay alumnos activos con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse


                    </tbody>
                </table>
            @endif

        </div>
    </div>
    <footer>
        <p class="uppercase fw-700">{{ $escuela->nombre }} · C.C.T. {{ $nivel->cct }} </p>
        <p>
            C. {{ $escuela->calle }} No. {{ $escuela->no_exterior }}, Col. {{ $escuela->colonia }},
            C.P. {{ $escuela->codigo_postal }}, Cd. {{ $escuela->ciudad }}, {{ $escuela->estado }}.
        </p>
        <p>Fecha de expedición: {{ now()->translatedFormat('d \\d\\e F \\d\\e\\l Y \\a \\l\\a\\s H:i') }}</p>
    </footer>
</body>

</html>
