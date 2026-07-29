<?php

namespace App\Console\Commands;

use App\Services\IntegridadAcademicaService;
use Illuminate\Console\Command;

class AuditarIntegridadAcademica extends Command
{
    protected $signature = 'academico:auditar-integridad
        {--inscripcion= : Limita el análisis a un alumno}
        {--json : Devuelve el resultado en JSON}';

    protected $description = 'Detecta y sincroniza casos de integridad académica sin aplicar correcciones automáticamente.';

    public function handle(IntegridadAcademicaService $service): int
    {
        $inscripcionId = filled($this->option('inscripcion')) ? (int) $this->option('inscripcion') : null;
        $resultado = $service->ejecutar(null, 'consola', $inscripcionId);

        if ($this->option('json')) {
            $this->line(json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        if (! ($resultado['disponible'] ?? false)) {
            $this->error((string) ($resultado['mensaje'] ?? 'El centro de integridad no está disponible.'));
            return self::FAILURE;
        }

        $this->info('Análisis de integridad académica terminado.');
        $this->table(
            ['Indicador', 'Total'],
            [
                ['Detectados', $resultado['detectados'] ?? 0],
                ['Nuevos', $resultado['nuevos'] ?? 0],
                ['Actualizados', $resultado['actualizados'] ?? 0],
                ['Reabiertos', $resultado['reabiertos'] ?? 0],
                ['Resueltos automáticamente', $resultado['resueltos_automaticamente'] ?? 0],
                ['Críticos abiertos', data_get($resultado, 'resumen.criticos', 0)],
                ['Advertencias abiertas', data_get($resultado, 'resumen.advertencias', 0)],
            ]
        );

        return self::SUCCESS;
    }
}
