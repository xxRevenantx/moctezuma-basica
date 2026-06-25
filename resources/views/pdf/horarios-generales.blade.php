<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Horario general de {{ $nivel->nombre }}</title>

    <style>
        @page {
            margin: 7mm 7mm 8mm 7mm;
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

            font-family: 'ARIAL', sans-serif;
            color: #172033;
        }

        .page {
            position: relative;
            width: 100%;
        }

        .watermark {
            position: fixed;
            right: -18mm;
            bottom: -17mm;
            width: 86mm;
            opacity: .08;
            z-index: -1;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }

        .header-table td {
            vertical-align: middle;
            line-height: 1;
        }

        .logo-left {
            width: 80px;
        }

        .logo-right {
            width: 80px;
        }

        .school-name {
            margin: 0;
            color: #4c5563;
            font-size: 17px;
            font-weight: bold;
            text-align: center;
        }

        .title {
            margin: 1mm 0 0;
            color: #006492;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
        }

        .cycle {
            margin: .7mm 0 0;
            color: #006492;
            font-size: 13px;
            font-weight: bold;
            text-align: center;
        }

        .meta {
            width: 100%;
            padding: 5px;
            border: .35mm solid #d8e2ee;
            border-radius: 2.5mm;
            background: #f7fafc;
            color: #475569;
            font-size: 12px;
            text-align: center;
        }

        .meta strong {
            color: #172033;
        }

        .schedule {
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 1.1mm 1.1mm;
        }

        .schedule th,
        .schedule td {
            vertical-align: middle;
        }

        .time-head,
        .day-head {
            padding: 2.2mm 1mm;
            border-radius: 4mm;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }

        .time-head {
            width: 18%;
            background: #ff6173;
        }

        .day-head:nth-child(2) {
            background: #ff8b2c;
        }

        .day-head:nth-child(3) {
            background: #8952b3;
        }

        .day-head:nth-child(4) {
            background: #00a6b7;
        }

        .day-head:nth-child(5) {
            background: #008dca;
        }

        .day-head:nth-child(6) {
            background: #00ad63;
        }

        .time-cell {
            width: 18%;
            padding: 2.4mm 1mm;
            border-radius: 4mm;
            background: #dcefc8;
            color: #172033;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }


        .activity-cell {

            padding: 1.5mm 1.4mm;
            border-radius: 4mm;
            background: #d9eef8;
            text-align: left;
        }

        .activity-cell.empty {
            background: #f3f6f8;
            color: #9aa5b1;
            font-size: 11px;
            text-align: center;
        }

        .activity {
            margin: 0 0 1mm;
            padding-bottom: .8mm;
            border-bottom: .22mm solid rgba(0, 100, 146, .16);
            font-size: 11px;
            line-height: 1;
        }

        .activity:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: 0;
        }

        .activity .group {
            display: inline-block;
            min-width: 10mm;
            margin-right: .6mm;
            padding: .45mm .8mm;
            border-radius: 2mm;
            background: #006492;
            color: #fff;
            font-size: 5.8px;
            font-weight: bold;
            text-align: center;
        }

        .activity.taller .group {
            background: #7c3aed;
        }

        .activity .subject {
            color: #172033;
            font-weight: bold;
        }

        .activity .type {
            color: #7c3aed;
            font-size: 5px;
            font-weight: bold;
        }

        .recess-cell {
            padding: 2.1mm 2mm;
            border-radius: 4mm;
            background: #ffad00;
            color: #111827;
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 4mm;
            text-align: center;
        }

        .legend {
            margin-top: 2.5mm;
            color: #64748b;
            font-size: 15px;
            text-align: center;
        }


        .page-break {
            page-break-before: always;
        }

        .section-title {
            margin: 0 0 3mm;
            padding: 2mm 3mm;
            border-left: 1.6mm solid #006492;
            background: #edf7fb;
            color: #006492;
            font-size: 12px;
            font-weight: bold;
        }

        .teachers {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .teachers th {
            padding: 2mm;
            border: .3mm solid #cbd7e3;
            background: #006492;
            color: #fff;
            font-weight: bold;
            text-align: left;
        }

        .teachers td {
            padding: 1.8mm 2mm;
            border: .3mm solid #d8e2ea;
            vertical-align: top;
        }

        .teachers tr:nth-child(even) td {
            background: #f6f9fb;
        }

        .teacher-name {
            font-weight: bold;
        }

        .groups-summary {
            margin: 0 0 3mm;
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
        }

        footer {
            position: fixed;
            right: 0;
            bottom: -15px;
            left: 0;
            padding-top: 4px;
            border-top: 1px solid #cbd5e1;
            color: #475569;
            font-size: 11px;
            line-height: 1;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $formatoHora = static function (?string $hora): string {
            if (!$hora) {
                return '—';
            }

            foreach (['H:i:s', 'H:i'] as $formato) {
                try {
                    return \Carbon\Carbon::createFromFormat($formato, $hora)->format('h:i A');
                } catch (\Throwable) {
                }
            }

            return $hora;
        };

        $etiquetaGrupo = static function ($grupo): string {
            $grado = trim((string) ($grupo->grado?->nombre ?? ''));
            $grado = preg_match('/^\d+$/', $grado) ? $grado . '°' : $grado;
            $nombreGrupo = trim((string) ($grupo->asignacionGrupo?->nombre ?? ''));
            $texto = trim($grado . ' ' . $nombreGrupo);

            if ($grupo->semestre) {
                $texto .= ' · S' . $grupo->semestre->numero;
            }

            return $texto ?: 'Sin grupo';
        };
    @endphp

    <div class="page">
        @if ($imagen_nivel)
            <img src="{{ $imagen_nivel }}" class="watermark" alt="">
        @endif

        <table class="header-table">
            <tr>
                <td style="width: 28%; text-align: left;">
                    @if ($logo_izquierdo)
                        <img src="{{ $logo_izquierdo }}" class="logo-left" alt="Logo">
                    @endif
                </td>
                <td style="width: 48%;">
                    <p class="school-name">{{ $escuela->nombre ?? 'Centro Universitario Moctezuma' }}</p>
                    <p class="title">Horario General de {{ $nivel->nombre }}</p>
                    <p class="cycle">Ciclo Escolar {{ $ciclo_escolar->inicio_anio }}-{{ $ciclo_escolar->fin_anio }}</p>
                </td>
                <td style="width: 24%; text-align: right;">
                    @if ($logo_derecho)
                        <img src="{{ $logo_derecho }}" class="logo-right" alt="Logo del nivel">
                    @endif
                </td>
            </tr>
        </table>

        <div class="meta">
            <strong>Nivel:</strong> {{ $nivel->nombre }} &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>C.C.T.:</strong> {{ $nivel->cct ?? '—' }} &nbsp;&nbsp;|&nbsp;&nbsp;
        </div>

        <table class="schedule">
            <thead>
                <tr>
                    <th class="time-head">HORARIO</th>
                    @foreach ($tabla['dias'] as $dia)
                        <th class="day-head">{{ mb_strtoupper($dia->dia, 'UTF-8') }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($tabla['filas'] as $fila)
                    @php
                        $hora = $fila['hora'];
                    @endphp

                    @if ($fila['es_receso'])
                        <tr>
                            <td class="time-cell">
                                {{ $formatoHora($hora->hora_inicio) }} - {{ $formatoHora($hora->hora_fin) }}
                            </td>
                            <td class="recess-cell" colspan="{{ max(1, count($tabla['dias'])) }}">
                                {{ $fila['receso_label'] }}
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td class="time-cell">
                                {{ $formatoHora($hora->hora_inicio) }} - {{ $formatoHora($hora->hora_fin) }}
                            </td>

                            @foreach ($tabla['dias'] as $dia)
                                @php
                                    $actividades = $fila['celdas']->get((int) $dia->id, collect());

                                    /*
                                     * Las materias normales se muestran individualmente.
                                     * Los talleres se agrupan por grado y grupo para evitar
                                     * imprimir el nombre de cada taller. En su lugar se muestra
                                     * una sola entrada con el texto "Talleres".
                                     */
                                    $materias = $actividades
                                        ->filter(fn($actividad) => ($actividad['tipo'] ?? 'materia') !== 'taller')
                                        ->values();

                                    $talleres = $actividades
                                        ->filter(fn($actividad) => ($actividad['tipo'] ?? null) === 'taller')
                                        ->groupBy(fn($actividad) => trim((string) ($actividad['grado_grupo'] ?? '')))
                                        ->map(function ($actividadesDelGrupo, $gradoGrupo) {
                                            return [
                                                'tipo' => 'taller',
                                                'grado_grupo' => $gradoGrupo,
                                                'nombre' => 'Talleres',
                                            ];
                                        })
                                        ->values();

                                    $actividadesMostrar = $materias
                                        ->concat($talleres)
                                        ->sortBy(fn($actividad) => (string) ($actividad['grado_grupo'] ?? ''))
                                        ->values();
                                @endphp

                                <td class="activity-cell {{ $actividadesMostrar->isEmpty() ? 'empty' : '' }}">
                                    @forelse ($actividadesMostrar as $actividad)
                                        <div
                                            class="activity {{ ($actividad['tipo'] ?? '') === 'taller' ? 'taller' : '' }}">
                                            <span>{{ $actividad['grado_grupo'] }}</span>
                                            <span class="subject">
                                                {{ ($actividad['tipo'] ?? '') === 'taller' ? 'Taller' : $actividad['nombre'] }}
                                            </span>
                                        </div>
                                    @empty
                                        Sin actividad
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>


    </div>

    @if (($tabla['docentes'] ?? collect())->isNotEmpty())
        <div class="page-break"></div>

        <div class="page">
            @if ($imagen_nivel)
                <img src="{{ $imagen_nivel }}" class="watermark" alt="">
            @endif

            <p class="section-title">Docentes incluidos en el horario general</p>

            <p class="groups-summary">
                <strong>Grados y grupos:</strong>
                {{ $tabla['grupos']->map(fn($grupo) => $etiquetaGrupo($grupo))->implode(', ') }}.
                No se agrega un apartado de profesor titular; únicamente se muestran los docentes relacionados con
                materias y talleres del horario filtrado.
            </p>

            <table class="teachers">
                <thead>
                    <tr>
                        <th style="width: 30%;">Docente</th>
                        <th style="width: 45%;">Materias o talleres</th>
                        <th style="width: 25%;">Grados y grupos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tabla['docentes'] as $docente)
                        <tr>
                            <td class="teacher-name">{{ $docente['docente'] }}</td>
                            <td>{{ implode(', ', $docente['materias']) }}</td>
                            <td>{{ implode(', ', $docente['grupos']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <footer>
        <strong>
            {{ $escuela->nombre ?? 'Centro Universitario Moctezuma' }}
        </strong>

        @if (!empty($nivel->cct))
            - C.C.T. {{ $nivel->cct }}
        @endif

        <br>

        C. {{ $escuela->calle ?? '' }}
        No. {{ $escuela->no_exterior ?? '' }},
        Col. {{ $escuela->colonia ?? '' }},
        C.P. {{ $escuela->codigo_postal ?? '' }},
        {{ $escuela->ciudad ?? '' }},
        {{ $escuela->estado ?? '' }}

        @if (!empty($escuela->telefono))
            · Tel. {{ $escuela->telefono }}
        @endif

        <br>

        <strong>Fecha de expedición:</strong>
        {{ \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
    </footer>
</body>

</html>
