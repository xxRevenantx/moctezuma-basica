<?php

namespace App\Services;

use Illuminate\Support\Str;

class SystemAssistantService
{
    /**
     * Responde únicamente con datos obtenidos de reglas internas verificables.
     * No genera SQL, no modifica información y no ejecuta acciones automáticas.
     *
     * @return array<string,mixed>
     */
    public function answer(string $question): array
    {
        $question = trim(preg_replace('/\s+/u', ' ', $question) ?? '');
        $normalized = Str::of($question)->lower()->ascii()->toString();

        if (mb_strlen($question) < 3) {
            return $this->unsupported('Escribe una consulta un poco más específica.');
        }

        $issues = collect(app(AcademicIntegrityService::class)->analyze());

        $catalog = [
            'choques_profesor' => [
                ['choque', 'profesor'],
                ['conflicto', 'profesor'],
                ['docente', 'horario'],
            ],
            'choques_grupo' => [
                ['choque', 'grupo'],
                ['conflicto', 'grupo'],
            ],
            'materias_sin_profesor' => [
                ['materia', 'sin profesor'],
                ['materias', 'docente'],
                ['carga', 'sin profesor'],
            ],
            'alumnos_sin_calificaciones' => [
                ['alumno', 'sin calificacion'],
                ['calificacion', 'pendiente'],
                ['faltan', 'calificaciones'],
            ],
            'alumnos_sin_expediente' => [
                ['alumno', 'sin documento'],
                ['documento', 'pendiente'],
                ['expediente', 'incompleto'],
                ['sin expediente'],
            ],
            'alumnos_sin_asignacion' => [
                ['alumno', 'sin grupo'],
                ['alumno', 'sin generacion'],
                ['asignacion', 'incompleta'],
            ],
            'duplicado_curp' => [
                ['curp', 'duplicada'],
                ['curp', 'repetida'],
            ],
            'duplicado_matricula' => [
                ['matricula', 'duplicada'],
                ['matricula', 'repetida'],
            ],
            'cargas_duplicadas' => [
                ['carga', 'duplicada'],
                ['materia', 'duplicada'],
            ],
            'ciclo_actual_invalido' => [
                ['ciclo', 'actual'],
                ['ciclo', 'escolar'],
            ],
            'referencias_huerfanas' => [
                ['referencia', 'huerfana'],
                ['dato', 'huerfano'],
            ],
        ];

        foreach ($catalog as $key => $patterns) {
            foreach ($patterns as $terms) {
                if (collect($terms)->every(fn (string $term): bool => str_contains($normalized, $term))) {
                    return $this->formatIssue($issues->firstWhere('key', $key), $question, $key);
                }
            }
        }

        if (Str::contains($normalized, ['problema', 'incidencia', 'integridad', 'riesgo', 'pendiente'])) {
            $critical = $issues->where('severity', 'critical')->sum('count');
            $warning = $issues->where('severity', 'warning')->sum('count');
            $info = $issues->where('severity', 'info')->sum('count');

            return [
                'supported' => true,
                'title' => 'Resumen de integridad académica',
                'summary' => "Se detectaron {$critical} incidencias críticas, {$warning} advertencias y {$info} avisos informativos.",
                'details' => $issues->take(6)->map(fn (array $issue): string => $issue['title'].' ('.number_format($issue['count']).')')->all(),
                'severity' => $critical > 0 ? 'critical' : ($warning > 0 ? 'warning' : 'info'),
                'url' => route('misrutas.centro-control'),
                'notice' => 'El asistente solo consulta reglas internas y no modifica datos.',
            ];
        }

        return $this->unsupported(
            'Puedo responder sobre choques de horario, materias sin profesor, alumnos sin calificaciones, documentos pendientes, duplicados y estado general de integridad.'
        );
    }

    /** @param array<string,mixed>|null $issue */
    private function formatIssue(?array $issue, string $question, string $key): array
    {
        if (! $issue) {
            return [
                'supported' => true,
                'title' => 'Sin incidencias activas',
                'summary' => 'La revisión actual no encontró registros que coincidan con esta consulta.',
                'details' => [],
                'severity' => 'success',
                'url' => route('misrutas.centro-control'),
                'notice' => 'Resultado basado en la revisión de integridad disponible en este momento.',
                'question' => $question,
                'key' => $key,
            ];
        }

        $details = [];
        if (! empty($issue['samples'])) {
            $details[] = 'Ejemplos: '.implode(', ', $issue['samples']);
        }
        $details[] = $this->recommendation($key);

        return [
            'supported' => true,
            'title' => $issue['title'],
            'summary' => number_format((int) $issue['count']).' registro(s) requieren revisión. '.$issue['description'],
            'details' => $details,
            'severity' => $issue['severity'],
            'url' => $issue['url'] ?? route('misrutas.centro-control'),
            'notice' => 'La respuesta proviene de consultas controladas; no se ejecutó SQL libre ni se modificó información.',
            'question' => $question,
            'key' => $key,
        ];
    }

    private function recommendation(string $key): string
    {
        return match ($key) {
            'choques_profesor', 'choques_grupo' => 'Revisa primero los bloques coincidentes y cambia solo una asignación a la vez.',
            'materias_sin_profesor' => 'Asigna al docente antes de publicar horarios o generar listas.',
            'alumnos_sin_calificaciones' => 'Confirma que el periodo esté abierto y que la carga académica sea correcta antes de capturar.',
            'alumnos_sin_expediente' => 'Abre el expediente del alumno y valida que el documento vigente esté marcado como actual.',
            'alumnos_sin_asignacion' => 'Completa nivel, grado, grupo y generación desde matrícula.',
            'duplicado_curp', 'duplicado_matricula' => 'No elimines registros sin comparar primero identidad, trayectoria y documentos relacionados.',
            'cargas_duplicadas' => 'Compara ciclo, grupo, materia y profesor antes de conservar una sola carga.',
            'ciclo_actual_invalido' => 'Debe existir exactamente un ciclo escolar marcado como actual.',
            default => 'Abre el módulo relacionado y corrige manualmente después de verificar los datos.',
        };
    }

    /** @return array<string,mixed> */
    private function unsupported(string $message): array
    {
        return [
            'supported' => false,
            'title' => 'Consulta no reconocida',
            'summary' => $message,
            'details' => [
                'Ejemplo: ¿Qué profesores tienen choque de horario?',
                'Ejemplo: ¿Cuántos alumnos no tienen documentos?',
                'Ejemplo: Muéstrame las materias sin profesor.',
            ],
            'severity' => 'info',
            'url' => null,
            'notice' => 'Por seguridad, el asistente no interpreta consultas abiertas como SQL ni realiza cambios.',
        ];
    }
}
