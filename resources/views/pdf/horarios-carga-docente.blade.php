<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Horarios y carga docente</title>
    <style>
        @page { margin: 24px 26px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 8.5px; margin: 0; }
        .header { border-bottom: 4px solid #006492; padding-bottom: 8px; margin-bottom: 10px; }
        .school { font-size: 16px; font-weight: 700; color: #006492; text-align: center; }
        .title { margin-top: 3px; font-size: 12px; font-weight: 700; text-align: center; color: #263238; }
        .meta { width: 100%; margin-top: 7px; border-collapse: collapse; }
        .meta td { border: 1px solid #d8e0e8; padding: 4px 6px; }
        .meta .label { width: 11%; font-weight: 700; background: #eef6f9; color: #006492; }
        .section-title { margin: 8px 0 6px; padding: 6px 8px; background: #006492; color: white; font-size: 10px; font-weight: 700; }
        table.report { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.report th { background: #006492; color: white; font-size: 7.5px; padding: 4px; border: 1px solid #aab7c4; text-align: center; }
        table.report td { padding: 4px; border: 1px solid #ccd5df; vertical-align: top; word-wrap: break-word; }
        table.schedule th:first-child, table.schedule td:first-child { width: 68px; text-align: center; font-weight: 700; }
        .recess td { background: #edf8d7; color: #486414; text-align: center; font-weight: 700; letter-spacing: 1px; }
        .cell-item { margin-bottom: 4px; padding-bottom: 3px; border-bottom: 1px dotted #d5dbe2; }
        .cell-item:last-child { margin-bottom: 0; border-bottom: none; }
        .cell-main { font-weight: 700; }
        .muted { color: #64748b; font-size: 7px; }
        .badge { display: inline-block; padding: 1px 4px; border-radius: 5px; background: #edf8d7; color: #486414; font-size: 6.5px; font-weight: 700; }
        .summary { margin-top: 8px; width: 100%; border-collapse: collapse; }
        .summary td { border: 1px solid #d8e0e8; padding: 5px; text-align: center; }
        .summary strong { color: #006492; font-size: 11px; }
        .notice { margin-top: 7px; padding: 6px 8px; border: 1px solid #f4d48d; background: #fff8e7; color: #7a5200; }
        .signatures { margin-top: 18px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        .signatures td { width: 50%; padding: 18px 30px 0; text-align: center; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #263238; padding-top: 4px; font-size: 7.5px; }
        .page-break { page-break-before: always; }
        .keep { page-break-inside: avoid; }
        .format-label { margin: 0 0 4px; font-size: 10px; font-weight: 700; color: #006492; }
    </style>
</head>
<body>
@php
    $dias = collect($reporte['dias'] ?? []);
    $ciclo = $reporte['ciclo_escolar'] ?? null;
    $laboral = function ($profesorId) use ($datosLaborales) {
        $dato = $profesorId ? ($datosLaborales[(int) $profesorId] ?? []) : [];
        return [
            'nombramiento' => filled($dato['nombramiento'] ?? null) ? trim((string) $dato['nombramiento']) : 'S/C',
            'clave' => filled($dato['clave_presupuestal'] ?? null) ? trim((string) $dato['clave_presupuestal']) : 'S/C',
        ];
    };
    $cabecera = function ($titulo) use ($configuracion, $ciclo) {
        echo '<div class="header">';
        echo '<div class="school">' . e($configuracion['escuela'] ?? 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.') . '</div>';
        echo '<div class="title">' . e($titulo) . '</div>';
        echo '<table class="meta"><tr>';
        echo '<td class="label">Ciclo</td><td>' . e($ciclo?->nombre ?? 'S/C') . '</td>';
        echo '<td class="label">CCT</td><td>' . e($configuracion['cct'] ?? 'S/C') . '</td>';
        echo '<td class="label">Zona</td><td>' . e($configuracion['zona_escolar'] ?? 'S/C') . '</td>';
        echo '<td class="label">Turno</td><td>' . e($configuracion['turno'] ?? 'S/C') . '</td>';
        echo '</tr></table></div>';
    };
@endphp

@if (in_array('asig', $formatos, true))
    {!! $cabecera('ASIG - ASIGNACIÓN Y CARGA DOCENTE') !!}

    <table class="report">
        <thead>
            <tr>
                <th style="width:18%">DOCENTE</th>
                <th style="width:19%">ASIGNATURA / TALLER</th>
                <th style="width:6%">CLAVE</th>
                <th style="width:17%">GRUPOS</th>
                <th style="width:8%">SESIONES</th>
                <th style="width:8%">HORAS RELOJ</th>
                <th style="width:12%">NOMBRAMIENTO</th>
                <th style="width:12%">CLAVE PRESUPUESTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reporte['carga_docente'] as $fila)
                @php $dato = $laboral($fila['profesor_id']); @endphp
                <tr>
                    <td><strong>{{ $fila['docente'] }}</strong></td>
                    <td>{{ $fila['materia'] }} @if($fila['tipo'] === 'taller') <span class="badge">TALLER</span> @endif</td>
                    <td style="text-align:center">{{ $fila['clave'] ?: 'S/C' }}</td>
                    <td>{{ implode(', ', $fila['grupos']) }}</td>
                    <td style="text-align:center"><strong>{{ $fila['sesiones_semanales'] }}</strong></td>
                    <td style="text-align:center"><strong>{{ number_format((float) $fila['horas_reloj'], 2) }}</strong></td>
                    <td>{{ $dato['nombramiento'] }}</td>
                    <td>{{ $dato['clave'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td><span class="muted">GRUPOS</span><br><strong>{{ $reporte['resumen']['grupos'] }}</strong></td>
            <td><span class="muted">DOCENTES</span><br><strong>{{ $reporte['resumen']['docentes'] }}</strong></td>
            <td><span class="muted">SESIONES SEMANALES</span><br><strong>{{ $reporte['resumen']['sesiones'] }}</strong></td>
            <td><span class="muted">HORAS RELOJ</span><br><strong>{{ number_format((float) $reporte['resumen']['horas_reloj'], 2) }}</strong></td>
            <td><span class="muted">ALERTAS</span><br><strong>{{ $reporte['resumen']['alertas'] }}</strong></td>
        </tr>
    </table>

    @if (($reporte['alertas']['total'] ?? 0) > 0)
        <div class="notice">
            Validaciones: {{ collect($reporte['alertas']['sin_docente'] ?? [])->count() }} sin docente,
            {{ collect($reporte['alertas']['espacios_vacios'] ?? [])->count() }} espacios vacíos,
            {{ collect($reporte['alertas']['traslapes_docente'] ?? [])->count() }} traslapes de docente y
            {{ collect($reporte['alertas']['traslapes_grupo'] ?? [])->count() }} traslapes de grupo.
        </div>
    @endif

    <table class="signatures">
        <tr>
            <td><div class="signature-line">DIRECTOR(A)<br>{{ $configuracion['director'] ?? 'S/C' }}</div></td>
            <td><div class="signature-line">SUPERVISOR(A)<br>{{ $configuracion['supervisor'] ?? 'S/C' }}</div></td>
        </tr>
    </table>
@endif

@if (in_array('general', $formatos, true))
    @if (in_array('asig', $formatos, true)) <div class="page-break"></div> @endif
    {!! $cabecera('FOR-HORGEN - HORARIO GENERAL') !!}

    <table class="report schedule">
        <thead>
            <tr>
                <th>HORA</th>
                @foreach ($reporte['tabla_general']['dias'] as $dia)
                    <th>{{ mb_strtoupper($dia->dia) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($reporte['tabla_general']['filas'] as $fila)
                @if ($fila['es_receso'])
                    <tr class="recess">
                        <td>{{ $fila['hora'] }}</td>
                        <td colspan="{{ max(1, $dias->count()) }}">RECESO</td>
                    </tr>
                @else
                    <tr>
                        <td>{{ $fila['hora'] }}</td>
                        @foreach ($reporte['tabla_general']['dias'] as $dia)
                            <td>
                                @forelse ($fila['celdas'][$dia->id] ?? [] as $item)
                                    <div class="cell-item">
                                        <div class="cell-main">{{ $item['grupo'] }} - {{ $item['nombre'] }}</div>
                                        <div class="muted">{{ $item['profesor'] }}</div>
                                    </div>
                                @empty
                                    <span class="muted">-</span>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
@endif

@if (in_array('formatos', $formatos, true))
    @foreach ($reporte['formatos_docentes'] as $matriz)
        @if (!$loop->first || in_array('asig', $formatos, true) || in_array('general', $formatos, true))
            <div class="page-break"></div>
        @endif
        {!! $cabecera('FORM-HOR - HORARIO INDIVIDUAL DE DOCENTE') !!}
        @php $dato = $laboral($matriz['id']); @endphp
        <p class="format-label">DOCENTE: {{ $matriz['nombre'] }}</p>
        <table class="meta" style="margin-bottom:7px">
            <tr>
                <td class="label">Nombramiento</td><td>{{ $dato['nombramiento'] }}</td>
                <td class="label">Clave presupuestal</td><td>{{ $dato['clave'] }}</td>
            </tr>
        </table>

        <table class="report schedule">
            <thead>
                <tr>
                    <th>HORA</th>
                    @foreach ($dias as $dia)<th>{{ mb_strtoupper($dia->dia) }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($matriz['filas'] as $fila)
                    @if ($fila['es_receso'])
                        <tr class="recess"><td>{{ $fila['hora'] }}</td><td colspan="{{ max(1, $dias->count()) }}">RECESO</td></tr>
                    @else
                        <tr>
                            <td>{{ $fila['hora'] }}</td>
                            @foreach ($dias as $dia)
                                <td>
                                    @forelse ($fila['celdas'][$dia->id] ?? [] as $item)
                                        <div class="cell-item"><div class="cell-main">{{ $item['nombre'] }}</div><div class="muted">{{ $item['contexto'] }}</div></div>
                                    @empty <span class="muted">-</span> @endforelse
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endforeach

    @foreach ($reporte['formatos_grupos'] as $matriz)
        @if (!$loop->first || collect($reporte['formatos_docentes'] ?? [])->isNotEmpty() || in_array('asig', $formatos, true) || in_array('general', $formatos, true))
            <div class="page-break"></div>
        @endif
        {!! $cabecera('FORM-HOR - HORARIO POR GRUPO') !!}
        <p class="format-label">GRUPO: {{ $matriz['nombre'] }}</p>

        <table class="report schedule">
            <thead>
                <tr>
                    <th>HORA</th>
                    @foreach ($dias as $dia)<th>{{ mb_strtoupper($dia->dia) }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($matriz['filas'] as $fila)
                    @if ($fila['es_receso'])
                        <tr class="recess"><td>{{ $fila['hora'] }}</td><td colspan="{{ max(1, $dias->count()) }}">RECESO</td></tr>
                    @else
                        <tr>
                            <td>{{ $fila['hora'] }}</td>
                            @foreach ($dias as $dia)
                                <td>
                                    @forelse ($fila['celdas'][$dia->id] ?? [] as $item)
                                        <div class="cell-item"><div class="cell-main">{{ $item['nombre'] }}</div><div class="muted">{{ $item['contexto'] }}</div></div>
                                    @empty <span class="muted">-</span> @endforelse
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endforeach
@endif

@if (in_array('complementarias', $formatos, true))
    @if (in_array('asig', $formatos, true) || in_array('general', $formatos, true) || in_array('formatos', $formatos, true))
        <div class="page-break"></div>
    @endif
    {!! $cabecera('HOR-COMP. - MATERIAS COMPLEMENTARIAS Y TALLERES') !!}

    <table class="report">
        <thead>
            <tr>
                <th style="width:14%">TIPO</th>
                <th style="width:18%">ACTIVIDAD</th>
                <th style="width:7%">CLAVE</th>
                <th style="width:18%">DOCENTE</th>
                <th style="width:20%">GRUPOS</th>
                <th style="width:8%">SESIONES</th>
                <th style="width:8%">HORAS RELOJ</th>
                <th style="width:7%">CLAVE PRESUP.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reporte['complementarias'] as $fila)
                @php $dato = $laboral($fila['profesor_id']); @endphp
                <tr>
                    <td><strong>{{ $fila['tipo'] }}</strong></td>
                    <td>{{ $fila['nombre'] }}</td>
                    <td style="text-align:center">{{ $fila['clave'] ?: 'S/C' }}</td>
                    <td>{{ $fila['docente'] }}</td>
                    <td>{{ implode(', ', $fila['grupos']) }}</td>
                    <td style="text-align:center">{{ $fila['sesiones_semanales'] }}</td>
                    <td style="text-align:center">{{ number_format((float) $fila['horas_reloj'], 2) }}</td>
                    <td>{{ $dato['clave'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center; padding:12px">Sin materias complementarias o talleres para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif
</body>
</html>
