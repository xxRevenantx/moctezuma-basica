<?php

namespace App\Console\Commands;

use App\Services\EstabilizacionHistorialCiclosService;
use Illuminate\Console\Command;

class AuditarHistorialCiclos extends Command
{
    protected $signature = 'academico:auditar-historial-ciclos
        {--inscripcion= : Limita el diagnóstico a un alumno}
        {--reparar : Aplica únicamente correcciones deterministas y no destructivas}
        {--json : Devuelve el resultado en JSON}';

    protected $description = 'Audita y estabiliza la relación entre alumnos, ciclos, asignaciones, movimientos y calificaciones.';

    public function handle(EstabilizacionHistorialCiclosService $service): int
    {
        $inscripcionId = filled($this->option('inscripcion'))
            ? (int) $this->option('inscripcion')
            : null;

        $reparacion = null;
        if ($this->option('reparar')) {
            $reparacion = $service->reparar($inscripcionId);
        }

        $diagnostico = $service->diagnosticar($inscripcionId);

        if ($this->option('json')) {
            $this->line(json_encode([
                'reparacion' => $reparacion,
                'diagnostico' => $diagnostico,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('AUDITORÍA DEL HISTORIAL ACADÉMICO POR CICLO');
        $this->line('El diagnóstico es de solo lectura. --reparar aplica únicamente cambios deterministas.');

        if (! ($diagnostico['disponible'] ?? false)) {
            $this->error((string) ($diagnostico['mensaje'] ?? 'Historial no disponible.'));

            return self::FAILURE;
        }

        if ($reparacion) {
            $this->newLine();
            $this->comment('Correcciones aplicadas');
            $this->table(
                ['Concepto', 'Total'],
                collect($reparacion)
                    ->except(['disponible', 'mensaje'])
                    ->map(fn ($valor, $clave): array => [str_replace('_', ' ', ucfirst((string) $clave)), $valor])
                    ->values()
                    ->all()
            );
        }

        $this->newLine();
        $this->comment('Historiales');
        $this->table(
            ['Indicador', 'Total'],
            collect($diagnostico['historiales'])
                ->map(fn ($valor, $clave): array => [str_replace('_', ' ', ucfirst((string) $clave)), $valor])
                ->values()
                ->all()
        );

        $this->newLine();
        $this->comment('Asignaciones históricas');
        $this->table(
            ['Indicador', 'Total'],
            collect($diagnostico['asignaciones'])
                ->map(fn ($valor, $clave): array => [str_replace('_', ' ', ucfirst((string) $clave)), $valor])
                ->values()
                ->all()
        );

        $this->newLine();
        $this->comment('Vínculos de registros académicos');
        $filas = [];
        foreach ($diagnostico['vinculos'] as $tabla => $datos) {
            $filas[] = [$tabla, $datos['sin_vinculo'], $datos['vinculo_incorrecto']];
        }
        $this->table(['Tabla', 'Sin vínculo', 'Vínculo incorrecto'], $filas);

        $pendientes = collect($diagnostico['historiales'])->except('total')->sum()
            + collect($diagnostico['asignaciones'])->sum()
            + collect($diagnostico['vinculos'])->sum(fn (array $fila): int => array_sum($fila));

        $this->newLine();
        if ($pendientes === 0) {
            $this->info('La estructura histórica quedó consistente.');
        } else {
            $this->warn("Persisten {$pendientes} caso(s) para revisar. Ejecuta con --reparar y vuelve a auditar.");
        }

        return self::SUCCESS;
    }
}
