@php
    // El PDF de Listas Generales reutiliza el mismo calendario y diseño docente.
    $registros = [[
        'nivel' => $nivel,
        'grado' => $grado ?? null,
        'grupo' => $grupo ?? null,
        'semestre' => $semestre ?? null,
        'ciclo' => ($cicloEscolar?->inicio_anio ?? '—') . '-' . ($cicloEscolar?->fin_anio ?? '—'),
        'docente' => $nombreDocente ?? 'Docente sin especificar',
        'materia' => $nombreMateria ?? 'GENERAL',
        'periodo' => $nombrePeriodo ?? ($periodoTexto ?? '—'),
        'turno' => $turno ?? 'Matutino',
        'corte' => null,
        'alumnos' => $alumnos,
        'logoIzquierdo' => $logoIzquierdo ?? null,
        'logoDerecho' => $logoDerecho ?? null,
    ]];
@endphp

@include('pdf.asistencia_bimestral_base', [
    'registros' => $registros,
    'calendario' => $calendarioAsistencia,
    'escuela' => $escuela,
])
