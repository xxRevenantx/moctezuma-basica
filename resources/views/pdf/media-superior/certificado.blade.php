<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de estudios</title>
    <style>
        @page { margin: 10mm 12mm; }
        body { font-family: Arial, sans-serif; font-size: 8px; color:#111; }
        table { width:100%; border-collapse:collapse; }
        .header td { border:0; vertical-align:middle; }
        .logo-seg { width:135px; max-height:55px; object-fit:contain; }
        .logo-cum { width:155px; max-height:52px; object-fit:contain; }
        .title { text-align:center; font-size:12px; font-weight:bold; line-height:1.35; }
        .folio { text-align:right; font-size:9px; margin:4px 0; }
        .student { border:1px solid #111; margin-top:6px; }
        .student td { padding:4px 5px; }
        .intro { margin:10px 0; text-align:justify; line-height:1.45; }
        .subjects th, .subjects td { border:.7px solid #111; padding:3px 4px; }
        .subjects th { text-align:center; }
        .subjects .name { text-align:left; }
        .center { text-align:center; }
        .semester { background:#f3f4f6; font-weight:bold; }
        .extra-section { background:#fff7db; color:#7c5200; font-weight:bold; }
        .summary { margin-top:8px; text-align:right; font-size:9px; }
        .place { margin-top:20px; text-align:right; }
        .signatures { margin-top:70px; table-layout:fixed; }
        .signatures td { text-align:center; vertical-align:bottom; padding:0 15px; }
        .line { border-top:1px solid #111; padding-top:4px; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td style="width:25%;"><img class="logo-seg" src="{{ $institucional['logo_seg'] }}"></td>
        <td class="title" style="width:50%;">CERTIFICADO {{ mb_strtoupper($modalidad_certificado) }} DE ESTUDIOS<br>BACHILLERATO GENERAL</td>
        <td style="width:25%;text-align:right;"><img class="logo-cum" src="{{ $institucional['logo_plantel'] }}"></td>
    </tr>
</table>
<div class="folio"><strong>FOLIO:</strong> {{ $folio }}</div>
<table class="student">
    <tr><td><strong>PLANTEL:</strong> {{ mb_strtoupper($institucional['plantel']) }}</td><td><strong>C.C.T.:</strong> {{ $institucional['cct'] }}</td></tr>
    <tr><td><strong>NÚMERO DE ACUERDO:</strong> {{ $institucional['numero_acuerdo'] ?: 'PENDIENTE DE CONFIGURAR' }}</td><td><strong>MODALIDAD:</strong> {{ mb_strtoupper($institucional['modalidad']) }}</td></tr>
    <tr><td colspan="2"><strong>ALUMNO:</strong> {{ mb_strtoupper(trim($alumno->apellido_paterno.' '.$alumno->apellido_materno.' '.$alumno->nombre)) }}</td></tr>
    <tr><td><strong>CURP:</strong> {{ $alumno->curp }}</td><td><strong>MATRÍCULA:</strong> {{ $alumno->matricula }}</td></tr>
    <tr><td><strong>GENERACIÓN:</strong> {{ $alumno->generacion?->etiqueta }}</td><td><strong>SEMESTRES ACREDITADOS:</strong> {{ $semestres_certificados->pluck('numero')->implode(', ') }}</td></tr>
</table>
<p class="intro">
    Se certifica que el alumno acreditó las asignaturas que se relacionan en el presente documento, conforme a los registros académicos del plantel. Las columnas de regularización se conservan vacías porque el nivel no opera regularizaciones en esta etapa.
</p>
<table class="subjects">
    <thead><tr><th style="width:9%;">SEM.</th><th style="width:16%;">CLAVE</th><th>ASIGNATURA</th><th style="width:14%;">CALIFICACIÓN</th><th style="width:22%;">REGULARIZACIÓN</th></tr></thead>
    <tbody>
        @foreach($semestres_certificados as $semestre)
            <tr class="semester"><td class="center">{{ $semestre['numero'] }}°</td><td colspan="4">CICLO ESCOLAR {{ $semestre['ciclo']?->nombre ?: '—' }}</td></tr>
            @foreach($semestre['oficiales'] as $materia)
                <tr><td class="center">{{ $semestre['numero'] }}</td><td class="center">{{ $materia['clave'] }}</td><td class="name">{{ mb_strtoupper($materia['nombre']) }}</td><td class="center">{{ $materia['valor'] }}</td><td></td></tr>
            @endforeach
            @if($institucional['mostrar_materias_extra'] && $semestre['extras']->isNotEmpty())
                <tr class="extra-section"><td class="center">{{ $semestre['numero'] }}°</td><td colspan="4">MATERIAS EXTRA INFORMATIVAS · NO INTERVIENEN EN EL PROMEDIO NI EN LA ACREDITACIÓN</td></tr>
                @foreach($semestre['extras'] as $materia)
                    <tr><td class="center">{{ $semestre['numero'] }}</td><td class="center">{{ $materia['clave'] }}</td><td class="name">{{ mb_strtoupper($materia['nombre']) }}</td><td class="center">{{ $materia['valor'] }}</td><td></td></tr>
                @endforeach
            @endif
        @endforeach
    </tbody>
</table>
<div class="summary"><strong>PROMEDIO GENERAL:</strong> {{ $promedio_certificado }}</div>
<p class="place">SE EXPIDE EN {{ mb_strtoupper($institucional['localidad_expedicion']) }}, A {{ $fecha_documento_texto }}.</p>
<table class="signatures">
    <tr>
        <td><div class="line"><strong>{{ $institucional['firmantes']['director']['nombre'] }}</strong><br>{{ $institucional['firmantes']['director']['cargo'] }}</div></td>
        <td><div class="line"><strong>{{ $institucional['firmantes']['jefe_registro']['nombre'] }}</strong><br>{{ $institucional['firmantes']['jefe_registro']['cargo'] }}</div></td>
    </tr>
</table>
</body>
</html>
