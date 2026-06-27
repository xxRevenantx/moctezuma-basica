<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 36px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .frame { border: 3px solid #006492; padding: 18px 22px; min-height: 890px; }
        .green { border-top: 6px solid #88AC2E; margin: -18px -22px 16px; }
        h1 { color: #006492; text-align: center; font-size: 19px; margin: 4px 0; }
        .sub { text-align: center; font-weight: bold; color: #4b5563; }
        .folio { text-align: right; font-size: 10px; margin: 8px 0 16px; }
        .data { width: 100%; border-collapse: collapse; margin: 14px 0; }
        .data td { padding: 5px 7px; border-bottom: 1px solid #d1d5db; }
        .grades { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .grades th { background: #006492; color: white; padding: 6px; font-size: 9px; }
        .grades td { border: 1px solid #cbd5e1; padding: 5px; font-size: 9px; }
        .period { background: #e8f4f8; color: #004d70; font-weight: bold; }
        .signature { margin-top: 46px; text-align: center; }
        .line { width: 270px; border-top: 1px solid #111827; margin: 0 auto 6px; }
        .note { margin-top: 16px; font-size: 9px; color: #4b5563; }
    </style>
</head>
<body>
@php
    $alumno = $constancia->inscripcion;
    $trayectoria = $constancia->trayectoriaAcademica;
    $grupo = $trayectoria?->grupo?->asignacionGrupo?->nombre ?? $trayectoria?->grupo?->nombre ?? '—';
    $nombre = trim(($alumno?->nombre ?? '') . ' ' . ($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? ''));
@endphp
<div class="frame">
    <div class="green"></div>
    <h1>CONSTANCIA DE TRASLADO CON CALIFICACIONES</h1>
    <div class="sub">CENTRO UNIVERSITARIO MOCTEZUMA A.C.</div>
    <div class="folio">Folio: <strong>{{ $constancia->folio }}</strong> · Fecha: {{ $constancia->fecha_emision?->format('d/m/Y') }}</div>

    <p>Por medio de la presente se hace constar que el alumno que se identifica a continuación estuvo inscrito en esta institución, conservándose su expediente e historial académico.</p>

    <table class="data">
        <tr><td><strong>Alumno:</strong> {{ $nombre }}</td><td><strong>Matrícula:</strong> {{ $alumno?->matricula }}</td></tr>
        <tr><td><strong>CURP:</strong> {{ $alumno?->curp }}</td><td><strong>Ciclo:</strong> {{ $constancia->cicloEscolar?->nombre }}</td></tr>
        <tr><td><strong>Nivel:</strong> {{ $trayectoria?->nivel?->nombre }}</td><td><strong>Generación:</strong> {{ $trayectoria?->generacion ? $trayectoria->generacion->anio_ingreso . '-' . $trayectoria->generacion->anio_egreso : '—' }}</td></tr>
        <tr><td><strong>Grado:</strong> {{ $trayectoria?->grado?->nombre }}</td><td><strong>Grupo:</strong> {{ $grupo }} @if($trayectoria?->semestre) · Semestre {{ $trayectoria->semestre->numero }} @endif</td></tr>
    </table>

    <table class="grades">
        <thead><tr><th>Periodo / parcial</th><th>Materia</th><th>Calificación</th><th>Observación</th></tr></thead>
        <tbody>
        @forelse($calificaciones as $periodoId => $filas)
            @php
                $periodo = $filas->first()?->periodo;
                $nombrePeriodo = $periodo?->periodoBasica?->periodo
                    ?? $periodo?->parcialBachillerato?->parcial
                    ?? 'Periodo ' . $periodoId;
            @endphp
            @foreach($filas as $fila)
                <tr>
                    <td class="period">{{ $nombrePeriodo }}</td>
                    <td>{{ $fila->asignacionMateria?->materia?->nombre ?? '—' }}</td>
                    <td style="text-align:center;font-weight:bold">{{ $fila->calificacion ?? '—' }}</td>
                    <td>{{ $fila->observacion ?? '' }}</td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="4" style="text-align:center;padding:18px">No existen calificaciones capturadas para los periodos seleccionados.</td></tr>
        @endforelse
        </tbody>
    </table>

    @if($constancia->observaciones)
        <p class="note"><strong>Observaciones:</strong> {{ $constancia->observaciones }}</p>
    @endif

    <p class="note">Las calificaciones anteriores permanecen vinculadas al ciclo, periodo, materia y profesor que correspondían al momento de su captura.</p>

    <div class="signature">
        <div class="line"></div>
        <strong>DIRECCIÓN / CONTROL ESCOLAR</strong><br>
        Centro Universitario Moctezuma A.C.
    </div>
</div>
</body>
</html>
