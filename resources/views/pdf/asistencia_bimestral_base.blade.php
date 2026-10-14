<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lista institucional de asistencia — 8 semanas</title>
    <style>
        @page {
            size: letter landscape;
            margin: 16px 19px 17px;
        }

        @font-face {
            font-family: AsistenciaCalibri;
            src: url('{{ storage_path('fonts/calibri-regular.ttf') }}') format('truetype');
            font-weight: 400;
        }

        @font-face {
            font-family: AsistenciaCalibri;
            src: url('{{ storage_path('fonts/calibri-bold.ttf') }}') format('truetype');
            font-weight: 700;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #173047;
            font-family: AsistenciaCalibri, DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        .pagina-asistencia {
            width: 100%;
        }

        .salto-asistencia {
            page-break-after: always;
            height: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .encabezado-asistencia {
            border-bottom: 2px solid #006492;
            margin-bottom: 7px;
        }

        .encabezado-asistencia td {
            padding-bottom: 4px;
            vertical-align: middle;
        }

        .logo-institucional {
            max-width: 88px;
            max-height: 66px;
        }

        .logo-nivel {
            max-width: 74px;
            max-height: 62px;
        }

        .titulo-principal {
            color: #006492;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            line-height: 1.08;
        }

        .subtitulo-principal {
            font-size: 10px;
            color: #334155;
            text-align: center;
            line-height: 1.25;
            margin-top: 2px;
        }

        .rango-principal {
            text-align: center;
            color: #53720f;
            font-weight: 700;
            font-size: 10px;
            margin-top: 3px;
        }

        .datos-asistencia {
            table-layout: fixed;
            margin-bottom: 5px;
        }

        .datos-asistencia td {
            padding: 2px 5px;
            border: 1px solid #cedce5;
            vertical-align: middle;
            font-size: 9.4px;
        }

        .datos-asistencia .etiqueta {
            background: #eaf3f7;
            color: #31546a;
            font-weight: 700;
            width: 7.5%;
        }

        .datos-asistencia .dato {
            font-weight: 700;
        }

        .leyenda-asistencia {
            margin: 4px 0 6px;
            padding: 3px 5px;
            background: #f4f8ed;
            border: 1px solid #d7e6c3;
            font-size: 9px;
            text-align: center;
        }

        .leyenda-asistencia b {
            color: #006492;
        }

        .grid-asistencia {
            width: 100%;
        }

        .grid-asistencia th,
        .grid-asistencia td {
            border: 0.6px solid #71889a;
            padding: 0px 1px;
            vertical-align: middle;
            text-align: center;
            overflow-wrap: break-word;
        }

        .grid-asistencia th {
            background: #dbeaf3;
            color: #173047;
            font-size: 8px;
            font-weight: 700;
            line-height: 1;
        }

        .grid-asistencia .head-semana {
            color: #006492;
            background: #eaf2f8;
            font-size: 8px;
        }

        .grid-asistencia .head-mes {
            background: #d5eaf3;
            color: #004865;
        }

        .grid-asistencia .head-total {
            background: #dfedc9;
            color: #45681e;
        }

        .grid-asistencia .numero {
            font-size: 9px;
        }

        .grid-asistencia .nombre {
            text-align: left;
            padding-left: 4px;
            font-size: 9.8px;
            line-height: 1.07;
            word-wrap: break-word;
        }

        .grid-asistencia td.casilla {
            height: 20px;
        }

        .grid-asistencia .resumen-mes {
            background: #f0f7fb;
        }

        .grid-asistencia .resumen-total {
            background: #f5f9e9;
        }

        .grid-asistencia .fecha-small {
            font-size: 7.1px;
        }

        .grid-asistencia .alta-posterior {
            display: block;
            margin-top: 1px;
            color: #9a3412;
            font-size: 7px;
        }

        .grid-asistencia tr {
            page-break-inside: avoid;
        }

        .grid-asistencia .separacion-semana {
            border-left: 1.6px solid #006492;
        }

        .panel-pie {
            margin-top: 7px;
            width: 100%;
            table-layout: fixed;
        }

        .panel-pie th,
        .panel-pie td {
            padding: 3px 5px;
            border: 1px solid #c6d8e3;
            font-size: 8.5px;
        }

        .panel-pie th {
            background: #eaf3f7;
            color: #006492;
            text-align: center;
        }

        .panel-pie td {
            height: 19px;
        }

        .nota-asistencia {
            color: #536274;
            font-size: 8px;
            margin: 5px 0;
        }

        .firmas-asistencia {
            margin-top: 18px;
            table-layout: fixed;
        }

        .firmas-asistencia td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 45px;
            font-size: 9px;
            font-weight: 700;
        }

        .linea-firma-asistencia {
            display: block;
            border-top: 1px solid #455665;
            padding-top: 3px;
        }

        .pie-institucional {
            border-top: 1px solid #a4c6d6;
            margin-top: 9px;
            padding-top: 4px;
            color: #50677a;
            font-size: 8.5px;
            text-align: center;
        }

        .pie-institucional span {
            color: #006492;
            font-weight: 700;
        }
    </style>
</head>

<body>
    @foreach ($registros as $registro)
        @php
            $alumnosLista = collect($registro['alumnos'])->values();
            // Distribución conservadora para mantener nombres completos y firmas legibles.
            $nombresLargos = $alumnosLista->contains(
                fn($a) => mb_strlen(
                    trim(
                        implode(
                            ' ',
                            array_filter([
                                $a->apellido_paterno ?? null,
                                $a->apellido_materno ?? null,
                                $a->nombre ?? null,
                            ]),
                        ),
                    ),
                    'UTF-8',
                ) > 43,
            );
            $filasPorPagina = $nombresLargos ? 21 : 24;
            $paginasAlumnos = $alumnosLista->chunk($filasPorPagina);
            if ($paginasAlumnos->isEmpty()) {
                $paginasAlumnos = collect([collect()]);
            }
            $nivelRegistro = $registro['nivel'];
            $grupoRegistro = $registro['grupo'];
            $nombreGrupo = $grupoRegistro?->asignacionGrupo?->nombre ?? ($grupoRegistro?->nombre ?? '—');
            $meses = $calendario['meses'];
            $columnasFinales = 2 + 40 + 6;
        @endphp
        @foreach ($paginasAlumnos as $indicePagina => $alumnosPagina)
            <div class="pagina-asistencia">
                <table class="encabezado-asistencia">
                    <tr>
                        <td style="width: 18%; text-align: left;">
                            @if (!empty($registro['logoIzquierdo']))
                                <img class="logo-institucional" src="{{ $registro['logoIzquierdo'] }}"
                                    alt="Escudo institucional">
                            @endif
                        </td>
                        <td style="width: 64%;">
                            <div class="titulo-principal">LISTA DE ASISTENCIA</div>
                            <div class="subtitulo-principal">
                                {{ mb_strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA A. C.', 'UTF-8') }}
                                · CICLO ESCOLAR {{ $registro['ciclo'] }}
                            </div>
                            <div class="subtitulo-principal">C.C.T. {{ $nivelRegistro?->cct ?? '—' }}</div>
                            <div class="rango-principal">
                                DEL {{ $calendario['inicioTexto'] }} AL {{ $calendario['finTexto'] }}
                                · {{ $indicePagina + 1 }} / {{ $paginasAlumnos->count() }}
                            </div>
                        </td>
                        <td style="width: 18%; text-align: right;">
                            @if (!empty($registro['logoDerecho']))
                                <img class="logo-nivel" src="{{ $registro['logoDerecho'] }}" alt="Escudo del nivel">
                            @endif
                        </td>
                    </tr>
                </table>

                <table class="datos-asistencia">
                    <tr>
                        <td class="etiqueta">NIVEL</td>
                        <td class="dato" style="width: 17%;">
                            {{ mb_strtoupper($nivelRegistro?->nombre ?? '—', 'UTF-8') }}</td>
                        <td class="etiqueta">GRADO/GRUPO</td>
                        <td class="dato" style="width: 17%;">{{ $registro['grado']?->nombre ?? '—' }}° ·
                            "{{ $nombreGrupo }}"</td>
                        <td class="etiqueta">DOCENTE</td>
                        <td class="dato" style="width: 43%;">{{ mb_strtoupper($registro['docente'], 'UTF-8') }}</td>
                    </tr>
                    <tr>
                        <td class="etiqueta">MATERIA</td>
                        <td class="dato">{{ mb_strtoupper($registro['materia'], 'UTF-8') }}</td>
                        <td class="etiqueta">PERIODO</td>
                        <td class="dato">{{ $registro['periodo'] }}</td>
                        <td class="etiqueta">TURNO/CORTE</td>
                        <td class="dato">
                            {{ $registro['turno'] }}
                            @if (!empty($registro['corte']))
                                · Corte: {{ $registro['corte'] }}
                            @endif
                            @if ($registro['semestre'])
                                · Semestre {{ $registro['semestre']->numero }}°
                            @endif
                        </td>
                    </tr>
                </table>

                <div class="leyenda-asistencia">
                    <b>Registro manual:</b> A = asistencia · F = falta · R = retardo · J = justificante · P = permiso
                    &nbsp; | &nbsp; <b>Resumen:</b> total general del periodo de 8 semanas (el % se calcula sobre días
                    de clase registrados).
                </div>

                <table class="grid-asistencia">
                    <colgroup>
                        <col style="width: 23px;">
                        <col style="width: 236px;">
                        @for ($col = 0; $col < 40; $col++)
                            <col style="width: 15px;">
                        @endfor
                        @for ($col = 0; $col < 6; $col++)
                            <col style="width: 20px;">
                        @endfor
                    </colgroup>
                    <thead>
                        <tr>
                            <th rowspan="3">N°</th>
                            <th rowspan="3">APELLIDOS Y NOMBRE(S)</th>
                            @foreach ($calendario['semanas'] as $semana)
                                <th colspan="5" class="head-semana">SEM. {{ $semana['numero'] }} ·
                                    {{ substr($semana['inicio'], 8, 2) }}/{{ substr($semana['inicio'], 5, 2) }}–{{ substr($semana['fin'], 8, 2) }}/{{ substr($semana['fin'], 5, 2) }}
                                </th>
                            @endforeach
                            <th colspan="6" class="head-total">TOTAL 8 SEM.</th>
                        </tr>
                        <tr>
                            @foreach ($calendario['semanas'] as $semana)
                                @foreach ($semana['dias'] as $indiceDia => $dia)
                                    <th class="{{ $indiceDia === 0 ? 'separacion-semana' : '' }}">{{ $dia['dia'] }}
                                    </th>
                                @endforeach
                            @endforeach
                            <th rowspan="2" class="head-total">A</th>
                            <th rowspan="2" class="head-total">F</th>
                            <th rowspan="2" class="head-total">R</th>
                            <th rowspan="2" class="head-total">J</th>
                            <th rowspan="2" class="head-total">P</th>
                            <th rowspan="2" class="head-total">%</th>
                        </tr>
                        <tr>
                            @foreach ($calendario['semanas'] as $semana)
                                @foreach ($semana['dias'] as $indiceDia => $dia)
                                    <th class="fecha-small {{ $indiceDia === 0 ? 'separacion-semana' : '' }}"
                                        title="{{ $dia['fecha'] }}">{{ $dia['numero'] }}</th>
                                @endforeach
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($alumnosPagina as $alumno)
                            @php
                                $fechaAlta = $alumno->fecha_inicio_historica ?? null;
                                $altaPosterior =
                                    $fechaAlta &&
                                    \Carbon\Carbon::parse($fechaAlta)->gt(\Carbon\Carbon::parse($calendario['inicio']));
                            @endphp
                            <tr>
                                <td class="numero">{{ $indicePagina * $filasPorPagina + $loop->iteration }}</td>
                                <td class="nombre">
                                    {{ trim(implode(' ', array_filter([$alumno->apellido_paterno ?? null, $alumno->apellido_materno ?? null, $alumno->nombre ?? null]))) }}
                                    @if ($altaPosterior)
                                        <span class="alta-posterior">Alta:
                                            {{ \Carbon\Carbon::parse($fechaAlta)->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                @foreach ($calendario['semanas'] as $semana)
                                    @foreach ($semana['dias'] as $indiceDia => $dia)
                                        <td class="casilla {{ $indiceDia === 0 ? 'separacion-semana' : '' }}"></td>
                                    @endforeach
                                @endforeach
                                @for ($r = 0; $r < 6; $r++)
                                    <td class="resumen-total"></td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $columnasFinales }}" style="text-align:center; padding: 10px;">Sin
                                    alumnos para los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <table class="panel-pie">
                    <tr>
                        <th style="width: 16%;">RESUMEN GRUPAL</th>
                        @foreach ($meses as $mes)
                            <th>{{ $mes['nombre'] }} ({{ $mes['dias'] }} días)</th>
                        @endforeach
                        <th>PERIODO COMPLETO (40 días)</th>
                    </tr>
                    <tr>
                        <td>Alumnos en esta hoja: {{ $alumnosPagina->count() }}</td>
                        @foreach ($meses as $mes)
                            <td>A: ____ F: ____ R: ____ J: ____ P: ____</td>
                        @endforeach
                        <td>A: ____ F: ____ R: ____ J: ____ P: ____</td>
                    </tr>
                    <tr>
                        <th>OBSERVACIONES</th>
                        <td colspan="{{ count($meses) + 1 }}">
                            ____________________________________________________________________________________________
                            &nbsp;
                            ____________________________________________________________________________________________
                        </td>
                    </tr>
                </table>
                <div class="nota-asistencia">
                    Resúmenes para llenado manual; los días festivos o sin clase pueden marcarse «—» y excluirse del
                    porcentaje.
                    Los meses se agrupan por su fecha real (8 semanas pueden abarcar tres meses calendario).
                </div>
                <table class="firmas-asistencia">
                    <tr>
                        <td><span class="linea-firma-asistencia">NOMBRE Y FIRMA DEL DOCENTE</span></td>
                        <td><span class="linea-firma-asistencia">Vo. Bo. DIRECCIÓN / COORDINACIÓN</span></td>
                    </tr>
                </table>
                <div class="pie-institucional">
                    <span>{{ mb_strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA', 'UTF-8') }}</span>
                    · C.C.T. {{ $nivelRegistro?->cct ?? '—' }}
                    · Generado: {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>
            @if (!$loop->last || !$loop->parent->last)
                <div class="salto-asistencia"></div>
            @endif
        @endforeach
    @endforeach
</body>

</html>
