<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>LISTA DE ASISTENCIA</title>

    <style>
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
            margin: 22px 24px 28px 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'calibri';
            font-size: 12px;
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
            top: 135px;
            left: 170px;
            width: 430px;
            opacity: 0.08;
            z-index: -1;
        }

        .encabezado {
            width: 100%;
            margin-bottom: 12px;
        }

        .logo {
            width: 90px;
        }

        .escudo {
            width: 78px;
        }

        .titulo {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            letter-spacing: 1px;
        }

        .subtitulo {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin-top: 4px;
        }

        .datos {
            margin-top: 14px;
            font-size: 11px;
            line-height: 1.8;
        }

        .datos b {
            text-decoration: underline;
        }

        .centrado {
            text-align: center;
            margin: 12px 0;
            font-size: 12px;
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
            width: 235px;
        }

        .dia {
            width: 16px;
            text-align: center;
        }

        .footer {
            position: absolute;
            bottom: 0;
            left: 30px;
            right: 30px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            text-align: center;
            color: #64748b;
            font-size: 9px;
        }
    </style>
</head>

<body>
    @foreach ($bloques as $bloque)
        @php
            $horario = $bloque['horario_base'];
            $nivel = $horario->nivel;
            $materia = $horario->asignacionMateria?->materia;
            $grupo = $horario->grupo;

            $periodo = $bloque['periodo'] ?? null;
            $parcial = $bloque['parcial'] ?? null;
            $esBachillerato = $bloque['es_bachillerato'] ?? false;

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
                    : 'No especificado';

            $logoNivel =
                $nivel?->logo && file_exists(public_path('storage/logos/' . $nivel->logo))
                    ? public_path('storage/logos/' . $nivel->logo)
                    : null;

            $logoPrincipal = file_exists(public_path('imagenes/logo-letra.png'))
                ? public_path('imagenes/logo-letra.png')
                : null;
        @endphp

        <div class="hoja">
            @if ($logoNivel)
                <img class="marca" src="{{ $logoNivel }}">
            @endif

            <table class="encabezado">
                <tr>
                    <td style="width: 25%;">
                        @if ($logoPrincipal)
                            <img class="logo" src="{{ $logoPrincipal }}">
                        @endif
                    </td>

                    <td style="width: 50%; text-align: center;">
                        <div class="titulo">LISTA DE ASISTENCIA</div>
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
                @if ($esBachillerato)
                    Parcial:
                    <b>{{ $parcial?->parcial ?? '—' }}</b>
                @else
                    Periodo:
                    <b>{{ $periodo?->periodo ?? '—' }}</b>
                @endif

                &nbsp;&nbsp;&nbsp;
                Mes:
                <b>{{ $periodo?->meses ?? ($parcial?->meses ?? '—') }}</b>

                &nbsp;&nbsp;&nbsp;
                Grado:
                <b>{{ $horario->grado?->nombre ?? '—' }}°</b>

                &nbsp;&nbsp;&nbsp;
                Grupo:
                <b>"{{ $grupo?->asignacionGrupo?->nombre ?? '—' }}"</b>

                &nbsp;&nbsp;&nbsp;
                Estatus:
                <b>ACTIVO</b>

                &nbsp;&nbsp;&nbsp;
                Turno:
                <b>Matutino</b>

                @if ($horario->semestre)
                    &nbsp;&nbsp;&nbsp;
                    Semestre:
                    <b>{{ $horario->semestre->numero }}°</b>
                @endif
            </div>

            <table class="tabla">
                <thead>
                    <tr>
                        <th rowspan="2" class="numero">No.</th>
                        <th rowspan="2" class="alumno">Nombre del alumno</th>
                        <th colspan="20">Mes:</th>
                    </tr>
                    <tr>
                        @for ($i = 1; $i <= 4; $i++)
                            <th colspan="5">SEM. {{ $i }}</th>
                        @endfor
                    </tr>
                    <tr>
                        <th></th>
                        <th></th>
                        @for ($i = 1; $i <= 4; $i++)
                            <th class="dia">L</th>
                            <th class="dia">M</th>
                            <th class="dia">M</th>
                            <th class="dia">J</th>
                            <th class="dia">V</th>
                        @endfor
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

                            @for ($c = 1; $c <= 20; $c++)
                                <td style="height: 18px;"></td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="footer">
                CENTRO UNIVERSITARIO MOCTEZUMA - LISTA DE ASISTENCIA - {{ mb_strtoupper($fecha, 'UTF-8') }}
            </div>
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
