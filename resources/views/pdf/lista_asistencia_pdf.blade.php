@php
    // Las listas docentes y las listas generales comparten exactamente la misma plantilla.
    $registros = $bloques
        ->map(function ($bloque) use ($profesor, $fechaCorte) {
            $horario = $bloque['horario_base'];
            $nivel = $horario->nivel;
            $grupo = $horario->grupo;
            $profesorBloque = $bloque['profesor_historico'] ?? $profesor;
            $nombreProfesor = trim(
                implode(
                    ' ',
                    array_filter([
                        $profesorBloque?->titulo,
                        $profesorBloque?->nombre,
                        $profesorBloque?->apellido_paterno,
                        $profesorBloque?->apellido_materno,
                    ]),
                ),
            );
            $periodo = $bloque['periodo'] ?? null;
            $parcial = $bloque['parcial'] ?? null;

            return [
                'nivel' => $nivel,
                'grado' => $horario->grado,
                'grupo' => $grupo,
                'semestre' => $horario->semestre,
                'ciclo' =>
                    $periodo?->inicio_anio && $periodo?->fin_anio
                        ? $periodo->inicio_anio . '-' . $periodo->fin_anio
                        : '—',
                'docente' => $nombreProfesor ?: 'Docente sin especificar',
                'materia' => $horario->asignacionMateria?->materia?->materia ?? 'Taller',
                'periodo' =>
                    $bloque['es_bachillerato'] ?? false
                        ? 'Parcial ' . ($parcial?->parcial ?? '—')
                        : 'Periodo ' . ($periodo?->periodo ?? '—'),
                'turno' => 'Matutino',
                'corte' => $fechaCorte,
                'alumnos' => $bloque['alumnos'],
                'logoIzquierdo' => is_file(public_path('imagenes/logo-letra.png'))
                    ? public_path('imagenes/logo-letra.png')
                    : null,
                'logoDerecho' =>
                    $nivel?->logo && is_file(public_path('storage/logos/' . $nivel->logo))
                        ? public_path('storage/logos/' . $nivel->logo)
                        : null,
            ];
        })
        ->all();
@endphp

@include('pdf.asistencia_bimestral_base', [
    'registros' => $registros,
    'calendario' => $calendarioAsistencia,
    'escuela' => $escuela,
])
