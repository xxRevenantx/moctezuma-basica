<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>LISTA DE EVALUACIÓN</title>

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
            top: 140px;
            left: 165px;
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

        table {
            width: 100%;
            border-collapse: collapse;
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
            background: #f8fafc;
            padding: 5px 7px;
            font-size: 10.5px;
            line-height: 13px;
        }

        .leyenda b {
            color: #1e40af;
        }

        .ponderacion {
            width: 100%;
            margin: 6px 0 8px 0;
            border-collapse: collapse;
        }

        .ponderacion th,
        .ponderacion td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            font-size: 10.5px;
            text-align: center;
        }

        .ponderacion th {
            background: #ecfdf5;
            color: #14532d;
            font-weight: bold;
        }

        .ponderacion td {
            background: #ffffff;
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

        .tabla .head-final {
            background: #dcfce7;
            color: #14532d;
        }

        .tabla .head-estatus {
            background: #fef3c7;
            color: #92400e;
        }

        .numero {
            width: 20px;
            text-align: center;
        }

        .alumno {
            width: 190px;
        }

        .campo {
            width: 48px;
            text-align: right;
        }

        .final {
            width: 48px;
            text-align: center;
            background: #f8fafc;
        }

        .estatus {
            width: 72px;
            text-align: center;
            background: #fffbeb;
        }

        .observacion-alumno {
            width: 105px;
            text-align: left;
        }

        .fila-alumno td {
            height: 18px;
            font-size: 10.2px;
        }

        .nombre-alumno {
            font-size: 10.3px;
        }


        .alta-posterior {
            display: block;
            margin-top: 2px;
            color: #9a3412;
            font-size: 6.5px;
            font-weight: bold;
            line-height: 1.1;
        }

        .resumen {
            margin-top: 9px;
            width: 100%;
            border-collapse: collapse;
        }

        .resumen th,
        .resumen td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            font-size: 10.5px;
            text-align: center;
        }

        .resumen th {
            background: #f1f5f9;
            color: #334155;
            font-weight: bold;
        }

        .resumen td {
            background: #ffffff;
            height: 22px;
        }

        .observaciones {
            margin-top: 9px;
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
            width: 155px;
        }

        .linea-observacion {
            display: block;
            border-bottom: 1px solid #cbd5e1;
            height: 17px;
            margin-bottom: 3px;
        }

        .recuperacion {
            margin-top: 8px;
            width: 100%;
            border-collapse: collapse;
        }

        .recuperacion td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            font-size: 10.5px;
        }

        .recuperacion .titulo-recuperacion {
            background: #fef3c7;
            color: #92400e;
            font-weight: bold;
            width: 155px;
        }

        .nota {
            margin-top: 7px;
            padding: 5px 7px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            font-size: 10px;
            color: #1e3a8a;
            text-align: justify;
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
            $inicioPeriodo = $periodo?->fecha_inicio
                ? \Illuminate\Support\Carbon::parse($periodo->fecha_inicio)->startOfDay()
                : null;

            $folio =
                'LE-' .
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
                Se intenta mostrar el horario si las relaciones están disponibles.
                Si no existen, se deja una línea para llenarse a mano.
            */
            $horaInicio = $horario->hora?->hora_inicio ?? null;
            $horaFin = $horario->hora?->hora_fin ?? null;
            $diaHorario = $horario->dia?->dia ?? null;

            $horarioTexto =
                $horaInicio && $horaFin
                    ? trim(($diaHorario ? $diaHorario . ' ' : '') . $horaInicio . ' - ' . $horaFin)
                    : '________________';

            /*
                Para básica puede usarse también AC, ED y RA.
                Para bachillerato se deja la escala numérica como principal.
            */
            $esBasica = !$esBachillerato;
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
                <b>Escala de evaluación:</b>
                10 = Excelente &nbsp;|&nbsp;
                9 = Muy bien &nbsp;|&nbsp;
                8 = Bien &nbsp;|&nbsp;
                7 = Regular &nbsp;|&nbsp;
                6 = Suficiente &nbsp;|&nbsp;
                5 = No aprobado

                @if ($esBasica)
                    <br>
                    <b>Evaluación cualitativa opcional:</b>
                    AC = Acreditado &nbsp;|&nbsp;
                    ED = En desarrollo &nbsp;|&nbsp;
                    RA = Requiere apoyo
                @endif
            </div>

            <table class="ponderacion">
                <thead>
                    <tr>
                        <th colspan="6">Criterios y ponderación de evaluación</th>
                    </tr>

                    <tr>
                        <th>Asistencias</th>
                        <th>Participaciones</th>
                        <th>Tareas</th>
                        <th>Evidencias</th>
                        <th>Examen</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>______ %</td>
                        <td>______ %</td>
                        <td>______ %</td>
                        <td>______ %</td>
                        <td>______ %</td>
                        <td><b>100 %</b></td>
                    </tr>
                </tbody>
            </table>

            <table class="tabla">
                <thead>
                    <tr>
                        <th rowspan="2" class="numero">No.</th>
                        <th rowspan="2" class="alumno">Nombre del alumno</th>
                        <th colspan="5">Evaluación</th>
                        <th rowspan="2" class="head-final final">Cal. Final</th>
                        <th rowspan="2" class="head-estatus estatus">Estatus</th>
                        <th rowspan="2" class="observacion-alumno">Observaciones</th>
                    </tr>

                    <tr>
                        <th class="campo subhead">Asist.</th>
                        <th class="campo subhead">Part.</th>
                        <th class="campo subhead">Tareas</th>
                        <th class="campo subhead">Evid.</th>
                        <th class="campo subhead">Examen</th>
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
                                @php
                                    $fechaAltaLista = $alumno->fecha_inicio_historica
                                        ? \Illuminate\Support\Carbon::parse($alumno->fecha_inicio_historica)->startOfDay()
                                        : null;
                                @endphp
                                @if ($inicioPeriodo && $fechaAltaLista && $fechaAltaLista->gt($inicioPeriodo))
                                    <span class="alta-posterior">Aún no inscrito antes del {{ $fechaAltaLista->format('d/m/Y') }}</span>
                                @endif
                            </td>

                            <td class="campo">%</td>
                            <td class="campo">%</td>
                            <td class="campo">%</td>
                            <td class="campo">%</td>
                            <td class="campo">%</td>
                            <td class="final"></td>
                            <td class="estatus"></td>
                            <td class="observacion-alumno"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>


            <table class="observaciones">
                <tr>
                    <td class="titulo-observaciones">
                        Observaciones generales del docente
                    </td>

                    <td>
                        <span class="linea-observacion"></span>
                        <span class="linea-observacion"></span>
                    </td>
                </tr>
            </table>

            <div class="nota">
                Nota: Registrar cada criterio de evaluación conforme a la ponderación establecida.
                La calificación final deberá asentarse de acuerdo con los criterios institucionales.
                En caso de aplicar redondeo, se recomienda hacerlo únicamente cuando el decimal sea igual o mayor a .6.
            </div>

            <table class="firmas">
                <tr>
                    <td>
                        <span class="linea-firma">
                            Nombre y firma del docente
                        </span>
                    </td>

                    <td>
                        <span class="linea-firma">
                            Vo. Bo. Dirección / Coordinación Académica
                        </span>
                    </td>
                </tr>
            </table>

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
