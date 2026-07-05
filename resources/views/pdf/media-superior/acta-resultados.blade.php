<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de resultados</title>
    <style>
        @page { margin: 12mm 15mm; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        .header td { border: 0; vertical-align: middle; }
        .logo-seg { width: 140px; max-height: 55px; object-fit: contain; }
        .logo-cum { width: 150px; max-height: 50px; object-fit: contain; }
        .title { text-align: center; font-weight: bold; font-size: 12px; line-height: 1.45; }
        .meta { border: 1px solid #111; margin-top: 14px; }
        .meta td { padding: 5px 6px; border: 0; }
        .resultados { margin-top: 18px; }
        .resultados th, .resultados td { border: .7px solid #111; padding: 5px 4px; text-align: center; }
        .resultados th { font-size: 8px; }
        .resultados .nombre { text-align: left; }
        .place { text-align: right; margin-top: 28px; }
        .firmas { margin-top: 78px; table-layout: fixed; }
        .firmas td { text-align: center; vertical-align: bottom; padding: 0 8px; }
        .linea { border-top: 1px solid #111; padding-top: 3px; }
        .empty { height: 25px; }
        .warning { margin-top: 8px; color: #b45309; font-weight: bold; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td style="width:25%;"><img class="logo-seg" src="{{ $institucional['logo_seg'] }}"></td>
        <td class="title" style="width:50%;">
            SISTEMA EDUCATIVO ESTATAL<br>
            <span style="font-size:10px;font-weight:normal;">ACREDITACIÓN Y CERTIFICACIÓN DE ESTUDIOS</span><br>
            ACTA DE RESULTADOS DE EVALUACIÓN
        </td>
        <td style="width:25%;text-align:right;"><img class="logo-cum" src="{{ $institucional['logo_plantel'] }}"></td>
    </tr>
</table>

<table class="meta">
    <tr><td><strong>NOMBRE DEL PLANTEL:</strong> {{ mb_strtoupper($institucional['plantel']) }}</td><td><strong>CICLO ESCOLAR:</strong> {{ $ciclo->nombre }}</td></tr>
    <tr><td><strong>C.C.T.:</strong> {{ $institucional['cct'] }}</td><td><strong>GENERACIÓN:</strong> {{ $generacion->etiqueta }}</td></tr>
    <tr><td><strong>BACHILLERATO:</strong> GENERAL</td><td><strong>GRUPO:</strong> "{{ $grupo->asignacionGrupo?->nombre }}"</td></tr>
    <tr><td><strong>ASIGNATURA:</strong> {{ mb_strtoupper($asignacion->materia?->materia ?: '') }}</td><td><strong>TURNO:</strong> {{ mb_strtoupper($institucional['turno']) }}</td></tr>
    <tr><td><strong>CLAVE:</strong> {{ $asignacion->materia?->clave ?: '—' }}</td><td><strong>SEMESTRE:</strong> {{ $semestre->numero }}°</td></tr>
    <tr><td><strong>ACREDITACIÓN:</strong></td><td><strong>TOTAL DE ALUMNOS:</strong> {{ $filas->count() }}</td></tr>
    <tr><td><strong>REGULARIZACIÓN:</strong></td><td></td></tr>
</table>

<table class="resultados">
    <thead>
        <tr>
            <th rowspan="2" style="width:11%;">NO. PROGRESIVO</th>
            <th rowspan="2" style="width:16%;">NO. MATRÍCULA</th>
            <th rowspan="2">NOMBRE DEL ALUMNO</th>
            <th colspan="2" style="width:18%;">CALIFICACIÓN</th>
            <th rowspan="2" style="width:10%;">% DE ASIST.</th>
            <th rowspan="2" style="width:11%;">ACREDITADO</th>
        </tr>
        <tr><th>NÚMERO</th><th>LETRA</th></tr>
    </thead>
    <tbody>
        @forelse($filas as $fila)
            <tr>
                <td>{{ $fila['numero'] }}</td>
                <td>{{ $fila['matricula'] }}</td>
                <td class="nombre">{{ $fila['nombre'] }}</td>
                <td>{{ $fila['calificacion_numero'] }}</td>
                <td>{{ $fila['calificacion_letra'] }}</td>
                <td>{{ $fila['asistencia'] !== null ? number_format((float)$fila['asistencia'], 0) . '%' : '' }}</td>
                <td>{{ $fila['acreditado'] }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">SIN ALUMNOS</td></tr>
        @endforelse
        @for($i=$filas->count(); $i<max(3, $filas->count()+2); $i++)
            <tr><td class="empty"></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor
    </tbody>
</table>

<p class="place">{{ mb_strtoupper($institucional['localidad_expedicion']) }}, A {{ $fecha_documento_texto }}</p>
<table class="firmas">
    <tr>
        @foreach(['control_escolar','director','profesor'] as $rol)
            <td>
                <div class="linea">
                    <strong>{{ $institucional['firmantes'][$rol]['nombre'] }}</strong><br>
                    {{ $institucional['firmantes'][$rol]['cargo'] }}
                </div>
            </td>
        @endforeach
    </tr>
</table>
@if($diagnostico['asistencias_pendientes'] > 0)
    <div class="warning">Hay {{ $diagnostico['asistencias_pendientes'] }} porcentaje(s) de asistencia pendiente(s). La columna se deja vacía.</div>
@endif
</body>
</html>
