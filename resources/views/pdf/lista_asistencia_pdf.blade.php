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
            margin: 20px 22px 18px 22px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'calibri';
            font-size: 11px;
            color: #0f172a;
        }

        .page-break {
            page-break-after: always;
        }

        .hoja {
            position: relative;
            min-height: 735px;
        }

        .marca {
            position: absolute;
            top: 145px;
            left: 170px;
            width: 430px;
            opacity: 0.06;
            z-index: -1;
        }

        .encabezado {
            width: 100%;
            margin-bottom: 9px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 6px;
        }

        .logo {
            width: 88px;
        }

        .escudo {
            width: 76px;
        }

        .titulo {
            text-align: center;
            font-weight: bold;
            font-size: 20px;
            letter-spacing: 1px;
            color: #0f172a;
        }

        .subtitulo {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin-top: 3px;
            color: #1e293b;
        }

        .folio {
            display: inline-block;
            margin-top: 4px;
            padding: 3px 8px;
            border-radius: 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: bold;
        }

        .datos {
            width: 100%;
            margin-top: 8px;
            margin-bottom: 6px;
            border-collapse: collapse;
        }

        .datos td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            font-size: 11px;
            vertical-align: top;
        }

        .datos .label {
            width: 80px;
            background: #f1f5f9;
            font-weight: bold;
            color: #334155;
        }

        .datos .valor {
            font-weight: bold;
            color: #0f172a;
        }

        .bloque-info {
            width: 100%;
            margin: 7px 0 8px 0;
            border-collapse: collapse;
        }

        .bloque-info td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            font-size: 11px;
            text-align: center;
        }

        .bloque-info .label {
            background: #f8fafc;
            color: #475569;
            font-weight: bold;
        }

        .bloque-info .valor {
            background: #ffffff;
            color: #0f172a;
            font-weight: bold;
        }

        .leyenda {
            width: 100%;
            margin: 6px 0 7px 0;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            padding: 5px 0px 5px;
            font-size: 10.5px;
            text-align: center;
        }

        .leyenda b {
            color: #1e40af;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla th,
        .tabla td {
            border: 1px solid #64748b;
            padding: 2px 3px;
        }

        .tabla th {
            background: #dbeafe;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            color: #0f172a;
        }

        .tabla .subhead {
            background: #eff6ff;
            color: #1e3a8a;
        }

        .tabla .head-resumen {
            background: #dcfce7;
            color: #14532d;
        }

        .numero {
            width: 20px;
            text-align: center;
        }

        .alumno {
            width: 160px;
        }

        .dia {
            width: 15px;
            text-align: center;
        }

        .resumen {
            width: 18px;
            text-align: center;
            background: #f8fafc;
        }

        .porcentaje {
            width: 15px;
            text-align: center;
            background: #f8fafc;
        }

        .fila-alumno td {
            height: 18px;
        }

        .nombre-alumno {
            font-size: 11px;
        }

        .observaciones {
            margin-top: 10px;
            width: 100%;
            border: 1px solid #cbd5e1;
            border-collapse: collapse;
        }

        .observaciones td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            font-size: 10.5px;
        }

        .observaciones .titulo-observaciones {
            background: #f1f5f9;
            font-weight: bold;
            color: #334155;
            width: 150px;
        }

        .linea-observacion {
            display: block;
            border-bottom: 1px solid #cbd5e1;
            height: 18px;
            margin-bottom: 3px;
        }

        .firmas {
            width: 100%;
            margin-top: 100px;
            border-collapse: collapse;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            font-size: 11px;
            color: #0f172a;
            padding: 0 30px;
        }

        .linea-firma {
            border-top: 1px solid #0f172a;
            padding-top: 4px;
            display: block;
            font-weight: bold;
        }

        .nota {
            margin-top: 7px;
            font-size: 9.5px;
            color: #475569;
            text-align: justify;
        }

        .footer {
            position: fixed;
            left: 26px;
            right: 26px;
            bottom: 8px;
            text-align: center;
            font-size: 10px;
            color: #475569;
            border-top: 1px solid #94a3b8;
            padding-top: 3px;
            z-index: 3;
        }

        .footer p {
            margin: 0;
            line-height: 10px;
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

            $profesorBloque = $bloque['profesor_historico'] ?? $profesor;
            $nombreProfesor = trim(
                ($profesorBloque->titulo ? $profesorBloque->titulo . ' ' : '') .
                    ($profesorBloque->nombre ?? '') .
                    ' ' .
                    ($profesorBloque->apellido_paterno ?? '') .
                    ' ' .
                    ($profesorBloque->apellido_materno ?? ''),
            );

            $ciclo =
                $periodo?->inicio_anio && $periodo?->fin_anio
                    ? $periodo->inicio_anio . '-' . $periodo->fin_anio
                    : 'No especificado';

            $nombreMes = $periodo?->meses ?? ($parcial?->meses ?? '—');

            $folio =
                'LA-' .
                mb_strtoupper($nivel?->slug ?? 'NIVEL', 'UTF-8') .
                '-' .
                str_pad($horario->grado_id ?? 0, 2, '0', STR_PAD_LEFT) .
                '-' .
                str_pad($grupo?->id ?? 0, 2, '0', STR_PAD_LEFT) .
                '-' .
                now()->format('Ymd-His');

            $logoNivel =
                $nivel?->logo && file_exists(public_path('storage/logos/' . $nivel->logo))
                    ? public_path('storage/logos/' . $nivel->logo)
                    : null;

            $logoPrincipal = file_exists(public_path('imagenes/logo-letra.png'))
                ? public_path('imagenes/logo-letra.png')
                : null;

            /*
                Se intenta mostrar horario si el modelo tiene relaciones cargadas.
                Si no existen esas relaciones, simplemente se muestra una línea para llenarse a mano.
            */
            $horaInicio = $horario->hora?->hora_inicio ?? null;
            $horaFin = $horario->hora?->hora_fin ?? null;
            $diaHorario = $horario->dia?->dia ?? null;

            $horarioTexto =
                $horaInicio && $horaFin
                    ? trim(($diaHorario ? $diaHorario . ' ' : '') . $horaInicio . ' - ' . $horaFin)
                    : '________________';
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

                    <td style="width: 50%; text-align: center; line-height:13px;">
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

            <table class="datos">
                <tr>
                    <td class="label">NIVEL</td>
                    <td class="valor">{{ mb_strtoupper($nivel?->nombre ?? 'No especificado', 'UTF-8') }}</td>

                    <td class="label">DOCENTE</td>
                    <td class="valor">{{ mb_strtoupper($nombreProfesor ?: 'No especificado', 'UTF-8') }}</td>
                </tr>

                <tr>
                    <td class="label">MATERIA</td>
                    <td class="valor" colspan="3">
                        {{ mb_strtoupper($materia?->materia ?? 'No especificada', 'UTF-8') }}</td>
                </tr>
                <tr>
                    <td class="label">CORTE</td>
                    <td class="valor" colspan="3">{{ $fechaCorte ?? optional($bloque['fecha_corte'] ?? null)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</td>
                </tr>
            </table>

            <table class="bloque-info">
                <tr>
                    <td class="label">
                        @if ($esBachillerato)
                            Parcial
                        @else
                            Periodo
                        @endif
                    </td>

                    <td class="label">Mes</td>
                    <td class="label">Grado</td>
                    <td class="label">Grupo</td>
                    <td class="label">Turno</td>

                    @if ($horario->semestre)
                        <td class="label">Semestre</td>
                    @endif
                </tr>

                <tr>
                    <td class="valor">
                        @if ($esBachillerato)
                            {{ $parcial?->parcial ?? '—' }}
                        @else
                            {{ $periodo?->periodo ?? '—' }}
                        @endif
                    </td>

                    <td class="valor">{{ $nombreMes }}</td>
                    <td class="valor">{{ $horario->grado?->nombre ?? '—' }}°</td>
                    <td class="valor">"{{ $grupo?->asignacionGrupo?->nombre ?? '—' }}"</td>
                    <td class="valor">Matutino</td>

                    @if ($horario->semestre)
                        <td class="valor">{{ $horario->semestre->numero }}°</td>
                    @endif
                </tr>
            </table>

            <div class="leyenda">
                <b>Registro sugerido:</b>
                A = Asistencia &nbsp;|&nbsp;
                F = Falta &nbsp;|&nbsp;
                R = Retardo &nbsp;|&nbsp;
                J = Justificante &nbsp;|&nbsp;
                P = Permiso
            </div>

            <table class="tabla">
                <thead>
                    <tr>
                        <th rowspan="3" class="numero">No.</th>
                        <th rowspan="3" class="alumno">Nombre del alumno</th>
                        <th colspan="20">
                            MES:
                        </th>
                        <th colspan="6" class="head-resumen">Resumen</th>
                    </tr>

                    <tr>
                        @for ($i = 1; $i <= 4; $i++)
                            <th colspan="5" class="subhead">SEM. {{ $i }}</th>
                        @endfor

                        <th rowspan="2" class="head-resumen resumen">A</th>
                        <th rowspan="2" class="head-resumen resumen">F</th>
                        <th rowspan="2" class="head-resumen resumen">R</th>
                        <th rowspan="2" class="head-resumen resumen">J</th>
                        <th rowspan="2" class="head-resumen resumen">P</th>
                        <th rowspan="2" class="head-resumen porcentaje">%</th>
                    </tr>

                    <tr>
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
                        <tr class="fila-alumno">
                            <td class="numero">{{ $i + 1 }}</td>

                            <td class="nombre-alumno">
                                {{ $alumno->apellido_paterno }}
                                {{ $alumno->apellido_materno }}
                                {{ $alumno->nombre }}
                            </td>

                            @for ($c = 1; $c <= 20; $c++)
                                <td class="dia"></td>
                            @endfor

                            <td class="resumen"></td>
                            <td class="resumen"></td>
                            <td class="resumen"></td>
                            <td class="resumen"></td>
                            <td class="resumen"></td>
                            <td class="porcentaje"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="observaciones">
                <tr>
                    <td class="titulo-observaciones">
                        Observaciones del docente
                    </td>

                    <td>
                        <span class="linea-observacion"></span>
                        <span class="linea-observacion"></span>
                    </td>
                </tr>

                <tr>
                    <td class="titulo-observaciones">
                        Incidencias del mes
                    </td>

                    <td>
                        Faltas recurrentes: ____________________
                        &nbsp;&nbsp;&nbsp;
                        Retardos recurrentes: ____________________
                        &nbsp;&nbsp;&nbsp;
                        Justificantes: ____________________
                    </td>
                </tr>
            </table>

            <table class="firmas">
                <tr>
                    <td>
                        <span class="linea-firma">
                            Nombre y firma del docente
                        </span>
                    </td>

                    <td>
                        <span class="linea-firma">
                            Vo. Bo. Dirección / Coordinación
                        </span>
                    </td>
                </tr>
            </table>

            <div class="nota">
                Nota: Este formato permite registrar asistencia, faltas, retardos, justificantes y permisos.
                El resumen puede llenarse al finalizar el mes para dar seguimiento académico y administrativo.
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
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
