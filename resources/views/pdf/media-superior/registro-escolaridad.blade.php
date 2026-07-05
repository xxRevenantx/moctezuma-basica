<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de escolaridad</title>
    <style>
        @page { margin: 10mm 10mm 8mm 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; font-size: 7px; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; border: 0; }
        .logo-seg { width: 150px; max-height: 58px; object-fit: contain; }
        .logo-cum { width: 180px; max-height: 55px; object-fit: contain; }
        .title { text-align: center; font-weight: bold; font-size: 12px; line-height: 1.35; }
        .meta { margin-top: 5px; }
        .meta td { padding: 2px 4px; border: 0; }
        .meta strong { font-size: 7px; }
        .stats { width: 190px; font-size: 6.5px; }
        .stats th, .stats td { border: .6px solid #111; padding: 2px; text-align: center; }
        .main { table-layout: fixed; margin-top: 4px; }
        .main th, .main td { border: .6px solid #111; padding: 2px; text-align: center; vertical-align: middle; }
        .main thead th { font-size: 6px; height: 40px; }
        .vertical { writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; height: 150px; padding: 3px 1px !important; }
        .student { text-align: left !important; font-size: 6.2px; }
        .small { font-size: 5.5px; }
        .line { border-bottom: .6px solid #111; height: 13px; }
        .footer { margin-top: 5px; }
        .footer td { vertical-align: top; padding: 2px; }
        .signatures { width: 54%; margin: 5px auto 0; table-layout: fixed; }
        .signatures td { border: .6px solid #111; height: 85px; text-align: center; vertical-align: bottom; padding: 4px; font-size: 6px; }
        .warning { color: #b45309; font-weight: bold; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td style="width:22%;"><img class="logo-seg" src="{{ $institucional['logo_seg'] }}"></td>
        <td class="title" style="width:56%;">
            GOBIERNO DEL ESTADO LIBRE Y SOBERANO DE GUERRERO<br>
            <span style="font-size:10px;font-weight:normal;">SECRETARÍA DE EDUCACIÓN GUERRERO</span><br>
            REGISTRO DE ESCOLARIDAD
        </td>
        <td style="width:22%;text-align:right;"><img class="logo-cum" src="{{ $institucional['logo_plantel'] }}"></td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td><strong>NOMBRE DEL PLANTEL:</strong> {{ mb_strtoupper($institucional['plantel']) }}</td>
        <td rowspan="4" style="width:205px;">
            <table class="stats">
                <tr><th colspan="4">DATOS ESTADÍSTICOS</th></tr>
                <tr><th>CONCEPTO</th><th>HOMBRES</th><th>MUJERES</th><th>TOTAL</th></tr>
                <tr><td>INSCRITOS</td><td>{{ $estadistica['hombres'] }}</td><td>{{ $estadistica['mujeres'] }}</td><td>{{ $estadistica['total'] }}</td></tr>
            </table>
        </td>
    </tr>
    <tr><td><strong>CLAVE DE CENTRO DE TRABAJO:</strong> {{ $institucional['cct'] }}</td></tr>
    <tr><td><strong>DOMICILIO:</strong> {{ mb_strtoupper($institucional['direccion']) }} &nbsp; <strong>MUNICIPIO:</strong> {{ mb_strtoupper($institucional['municipio']) }} &nbsp; <strong>ENTIDAD:</strong> {{ mb_strtoupper($institucional['estado']) }}</td></tr>
    <tr><td><strong>NÚMERO DE ACUERDO:</strong> {{ $institucional['numero_acuerdo'] ?: 'PENDIENTE DE CONFIGURAR' }}</td></tr>
</table>
<table class="meta">
    <tr>
        <td><strong>BACHILLERATO:</strong> GENERAL</td>
        <td><strong>CICLO ESCOLAR:</strong> {{ $ciclo->nombre }}</td>
        <td><strong>SEMESTRE:</strong> {{ $semestre->numero }}°</td>
        <td><strong>TURNO:</strong> {{ mb_strtoupper($institucional['turno']) }}</td>
        <td><strong>GRUPO:</strong> {{ $grupo->asignacionGrupo?->nombre }}</td>
        <td><strong>GENERACIÓN:</strong> {{ $generacion->etiqueta }}</td>
    </tr>
    <tr>
        <td><strong>MODALIDAD:</strong> {{ mb_strtoupper($institucional['modalidad']) }}</td>
        <td colspan="5"></td>
    </tr>
</table>

<table class="main">
    <thead>
        <tr>
            <th style="width:25px;">NÚM. PROG.</th>
            <th style="width:33px;" class="vertical">ASIGNATURAS NO ACREDITADAS</th>
            <th style="width:36px;" class="vertical">SITUACIÓN ESCOLAR</th>
            <th style="width:75px;">NÚMERO DE MATRÍCULA</th>
            <th style="width:175px;">NOMBRE DEL ALUMNO</th>
            <th style="width:24px;" class="vertical">SEXO H O M</th>
            @foreach ($asignaciones as $asignacion)
                <th class="vertical">{{ mb_strtoupper($asignacion->materia?->materia ?: '') }}</th>
            @endforeach
            <th style="width:38px;" class="vertical">ASIGNATURAS ACREDITADAS</th>
            <th style="width:38px;" class="vertical">ASIGNATURAS NO ACREDITADAS</th>
            <th style="width:40px;" class="vertical">SITUACIÓN ESCOLAR</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($filas as $fila)
            <tr>
                <td>{{ $fila['numero'] }}</td>
                <td>{{ $fila['asignaturas_no_acreditadas'] }}</td>
                <td class="small">{{ $fila['situacion_escolar'] }}</td>
                <td>{{ $fila['matricula'] }}</td>
                <td class="student">{{ $fila['nombre'] }}</td>
                <td>{{ $fila['sexo'] }}</td>
                @foreach ($fila['materias'] as $materia)
                    <td>{{ $materia['valor'] }}</td>
                @endforeach
                <td>{{ $fila['materias']->where('acreditada', true)->count() }}</td>
                <td>{{ $fila['asignaturas_no_acreditadas'] }}</td>
                <td class="small">{{ $fila['situacion_escolar'] }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ 9 + $asignaciones->count() }}" style="height:26px;">SIN ALUMNOS PARA EL CORTE SELECCIONADO</td></tr>
        @endforelse
        @for ($i = $filas->count(); $i < max(3, min(8, $filas->count() + 2)); $i++)
            <tr><td style="height:16px;"></td><td></td><td></td><td></td><td></td><td></td>@foreach($asignaciones as $a)<td></td>@endforeach<td></td><td></td><td></td></tr>
        @endfor
    </tbody>
</table>

<table class="footer">
    <tr><td colspan="3" class="line"><strong>ALUMNOS DADOS DE ALTA O QUE REPITEN SEMESTRE</strong></td></tr>
    <tr><td><strong>INSCRIPCIÓN/REINSCRIPCIÓN:</strong></td><td><strong>ACREDITACIÓN/CERTIFICACIÓN:</strong></td><td><strong>REGULARIZACIÓN:</strong></td></tr>
    <tr><td><strong>FECHA:</strong></td><td><strong>FECHA:</strong> {{ $fecha_documento_corta }}</td><td><strong>FECHA:</strong></td></tr>
</table>

<table class="signatures">
    <tr>
        <td>
            <strong>{{ $institucional['firmantes']['director']['nombre'] }}</strong><br>
            {{ $institucional['firmantes']['director']['cargo'] }}
        </td>
        <td>
            <strong>{{ $institucional['firmantes']['jefe_registro']['nombre'] }}</strong><br>
            {{ $institucional['firmantes']['jefe_registro']['cargo'] }}
        </td>
    </tr>
</table>
@if(!$institucional['firmantes']['jefe_registro']['configurado'])
    <div class="warning">Firmante de Registro y Certificación pendiente de configurar.</div>
@endif
</body>
</html>
