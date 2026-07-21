<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ReconstruccionHistorialCiclosService;
use Illuminate\Console\Command;

class ReconstruirHistorialCiclos extends Command
{
    protected $signature = 'historial-ciclos:reconstruir
        {--inscripcion= : Limita el diagnóstico a un alumno}
        {--confirmar : Aplica únicamente registros exactos o de alta confianza}
        {--json= : Guarda la vista previa en un archivo JSON}';

    protected $description = 'Previsualiza y reconstruye ciclos históricos respaldados por movimientos y cambios académicos.';

    public function handle(ReconstruccionHistorialCiclosService $service): int
    {
        $inscripcionId = $this->option('inscripcion') ? (int) $this->option('inscripcion') : null;
        $diagnostico = $service->diagnostico($inscripcionId);

        $this->newLine();
        $this->info('Diagnóstico del historial por ciclo');
        $this->table(['Concepto', 'Cantidad'], [
            ['Eventos académicos utilizables', $diagnostico['total_eventos']],
            ['Ciclos detectados', $diagnostico['total_ciclos_detectados']],
            ['Ya existentes', $diagnostico['ya_existentes']],
            ['Aplicables', $diagnostico['aplicables']],
            ['Requieren revisión', $diagnostico['requieren_revision']],
        ]);

        $filas = collect($diagnostico['candidatos'])->take(100)->map(fn (array $fila) => [
            $fila['inscripcion_id'],
            $fila['alumno'],
            $fila['ciclo'],
            $fila['asignaciones'],
            $fila['resultado'] ?: 'En curso / indeterminado',
            $fila['nivel_confianza'],
            $fila['ya_existe'] ? 'Existente' : ($fila['aplicable'] ? 'Aplicar' : 'Revisar'),
        ])->all();

        if ($filas) {
            $this->table(['ID', 'Alumno', 'Ciclo', 'Ubicaciones', 'Resultado', 'Confianza', 'Acción'], $filas);
        }
        if (count($diagnostico['candidatos']) > 100) {
            $this->warn('Solo se muestran los primeros 100 resultados. Usa --json para consultar el diagnóstico completo.');
        }

        if ($ruta = $this->option('json')) {
            file_put_contents((string) $ruta, json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info('Vista previa guardada en: '.$ruta);
        }

        if (! $this->option('confirmar')) {
            $this->comment('Vista previa únicamente. Ejecuta nuevamente con --confirmar para crear los registros aplicables.');
            return self::SUCCESS;
        }

        if (! $this->confirm('¿Confirmas la reconstrucción de los registros exactos y de alta confianza?', false)) {
            $this->warn('Operación cancelada.');
            return self::SUCCESS;
        }

        $usuarioId = (int) (User::query()->where('is_admin', true)->value('id') ?: User::query()->value('id'));
        if (! $usuarioId) {
            $this->error('No existe un usuario para auditar la reconstrucción.');
            return self::FAILURE;
        }

        $resultado = $service->aplicar($inscripcionId, $usuarioId);
        $this->info("Ciclos creados: {$resultado['creados']}");
        $this->info("Asignaciones creadas: {$resultado['asignaciones']}");
        $this->warn("Omitidos o ya existentes: {$resultado['omitidos']}");

        return self::SUCCESS;
    }
}
