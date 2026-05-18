<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>LISTA DE EVALUACIÓN</title>

    <style>
        @page {
            margin: 22px 24px 28px 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
        }

        .page-break {
            page-break-after: always;
        }

        .hoja {
            position: relative;
            min-height: 730px;
        }

        .marca {
            position: absolute;
            top: 130px;
            left: 165px;
            width: 430px;
            opacity: 0.08;
            z-index: -1;
        }

        .encabezado {
            width: 100%;
            margin-bottom: 12px;
        }

        .logo {
            width: 160px;
        }

        .escudo {
            width: 74px;
        }

        .titulo {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            letter-spacing: 1px;
        }

        .subtitulo {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin-top: 4px;
        }

        .datos {
            margin-top: 14px;
            font-size: 10px;
            line-height: 1.9;
        }

        .datos b {
            text-decoration: underline;
        }

        .centrado {
            text-align: center;
            margin: 12px 0;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla th,
        .tabla td {
            border: 1px solid #555;
            padding: 2px 4px;
        }

        .tabla th {
            background: #d9d9d9;
            font-size: 8px;
            font-weight: normal;
        }

        .numero {
            width: 22px;
            text-align: center;
        }

        .alumno {
            width: 220px;
        }

        .campo {
            width: 72px;
            text-align: center;
        }

        .nota {
            margin-top: 8px;
            font-size: 9px;
            font-weight: bold;
        }

        .campos {
            margin-top: 14px;
            font-size: 12px;
            font-weight: bold;
        }

        .firma {
            position: absolute;
            bottom: 70px;
            left: 190px;
            width: 260px;
            text-align: center;
            border-top: 1px solid #475569;
            padding-top: 6px;
            font-size: 10px;
        }

        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #222;
            padding-top: 4px;
            text-align: right;
            font-size: 8px;
            font-weight: bold;
            font-style: italic;
        }
    </style>
</head>

<body>
    @foreach ($bloques as $indice => $bloque)
        @php
            $horario = $bloque['horario_base'];
            $nivel = $horario->nivel;
            $materia = $horario->asignacionMateria?->materia;
            $grupo = $horario->grupo;

            $nombreProfesor = trim(
                ($profesor->titulo ? $profesor->titulo . ' ' : '') .
                    ($profesor->nombre ?? '') .
                    ' ' .
                    ($profesor->apellido_paterno ?? '') .
                    ' ' .
                    ($profesor->apellido_materno ?? ''),
            );

            $ciclo =
                $periodo?->inicio_anio && $periodo?->fin_anio
                    ? $periodo->inicio_anio . '-' . $periodo->fin_anio
                    : '2025-2026';

            $logoNivel =
                $nivel?->logo && file_exists(public_path('storage/logos/' . $nivel->logo))
                    ? public_path('storage/logos/' . $nivel->logo)
                    : null;
        @endphp

        <div class="hoja">
            @if ($logoNivel)
                <img class="marca" src="{{ $logoNivel }}">
            @endif

            <table class="encabezado">
                <tr>
                    <td style="width: 25%;">
                        <img class="logo" src="{{ public_path('imagenes/logo_moctezuma.png') }}">
                    </td>

                    <td style="width: 50%; text-align: center;">
                        <div class="titulo">LISTA DE EVALUACIÓN</div>
                        <div class="subtitulo">Ciclo escolar {{ $ciclo }}</div>
                        <div class="subtitulo">C.C.T. {{ $nivel?->cct ?? 'No especificado' }}</div>
                    </td>

                    <td style="width: 25%; text-align: right;">
                        @if ($logoNivel)
                            <img class="escudo" src="{{ $logoNivel }}">
                        @endif
                    </td>
                </tr>
            </table>

            <div class="datos">
                NIVEL: <b>{{ mb_strtoupper($nivel?->nombre ?? 'No especificado', 'UTF-8') }}</b><br>
                DOCENTE: <b>{{ mb_strtoupper($nombreProfesor, 'UTF-8') }}</b><br>
                MATERIA: <b>{{ mb_strtoupper($materia?->materia ?? 'No especificada', 'UTF-8') }}</b>
            </div>

            <div class="centrado">
                Periodo:
                <b>{{ $periodo?->periodo ?? '—' }}</b>
                &nbsp;&nbsp;&nbsp;
                Parcial:
                <b>{{ $parcial?->parcial ?? '—' }}</b>
                &nbsp;&nbsp;&nbsp;
                Grado:
                <b>{{ $horario->grado?->nombre ?? '—' }}°</b>
                &nbsp;&nbsp;&nbsp;
                Grupo:
                <b>"{{ $grupo?->asignacionGrupo?->nombre ?? '—' }}"</b>
                &nbsp;&nbsp;&nbsp;
                Turno:
                <b>Matutino</b>
            </div>

            <table class="tabla">
                <thead>
                    <tr>
                        <th rowspan="2" class="numero">No.</th>
                        <th rowspan="2" class="alumno">Nombre del alumno</th>
                        <th colspan="6">Evaluación</th>
                    </tr>
                    <tr>
                        <th class="campo">Asistencias</th>
                        <th class="campo">Participaciones</th>
                        <th class="campo">Tareas</th>
                        <th class="campo">Evidencias</th>
                        <th class="campo">Examen</th>
                        <th class="campo">Cal. Final</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($bloque['alumnos'] as $i => $alumno)
                        <tr>
                            <td class="numero">{{ $i + 1 }}</td>
                            <td>
                                {{ $alumno->apellido_paterno }}
                                {{ $alumno->apellido_materno }}
                                {{ $alumno->nombre }}
                            </td>

                            <td class="campo">%</td>
                            <td class="campo">%</td>
                            <td class="campo">%</td>
                            <td class="campo">%</td>
                            <td class="campo">%</td>
                            <td class="campo"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="nota">
                Nota: La calificación final solo será redondeada si es mayor o igual a .6
            </div>

            <div class="campos">
                CAMPOS A EVALUAR
                <br><br>
                Asistencias _______% &nbsp;&nbsp;
                Participaciones _______% &nbsp;&nbsp;
                Tareas _______% &nbsp;&nbsp;
                Evidencias _______% &nbsp;&nbsp;
                Examen _______%
            </div>

            <div class="firma">
                Nombre y firma del Docente
            </div>

            <div class="footer">
                CENTRO UNIVERSITARIO MOCTEZUMA - LISTA DE EVALUACIÓN - {{ mb_strtoupper($fecha, 'UTF-8') }}
            </div>
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
