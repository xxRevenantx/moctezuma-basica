<?php

namespace App\Console\Commands;

use App\Models\Inscripcion;
use App\Services\Expedientes\OrganizadorExpedienteService;
use Illuminate\Console\Command;
use Throwable;

class MigrarFuentesOrganizadorExpedientes extends Command
{
    protected $signature = 'expedientes:migrar-fuentes-organizador
                            {--alumno= : ID de una inscripción específica}
                            {--solo-faltantes : Procesar solamente alumnos con documentos sin fuente asociada}';

    protected $description = 'Registra como archivos fuente los documentos existentes y calcula sus páginas para habilitar el organizador.';

    public function handle(OrganizadorExpedienteService $servicio): int
    {
        $alumnoId = $this->option('alumno');
        $soloFaltantes = (bool) $this->option('solo-faltantes');
        $procesados = 0;
        $errores = 0;

        $consulta = Inscripcion::withTrashed()
            ->select('id')
            ->when($alumnoId, fn ($query) => $query->whereKey((int) $alumnoId))
            ->when($soloFaltantes, fn ($query) => $query->whereHas('documentos', function ($documentos): void {
                $documentos->whereDoesntHave('fuente');
            }));

        $total = (clone $consulta)->count();

        if ($total === 0) {
            $this->components->info('No hay expedientes pendientes de migrar.');

            return self::SUCCESS;
        }

        $barra = $this->output->createProgressBar($total);
        $barra->start();

        $consulta->orderBy('id')->chunkById(100, function ($alumnos) use ($servicio, &$procesados, &$errores, $barra): void {
            foreach ($alumnos as $alumno) {
                try {
                    $servicio->sincronizarFuentesExistentes($alumno, null);
                    // Actualiza páginas y consistencia sin crear borradores administrativos.
                    $servicio->actualizarConteosFuentes($alumno);
                    $procesados++;
                } catch (Throwable $e) {
                    report($e);
                    $errores++;
                    $this->newLine();
                    $this->components->warn("Alumno {$alumno->id}: {$e->getMessage()}");
                } finally {
                    $barra->advance();
                }
            }
        });

        $barra->finish();
        $this->newLine(2);
        $this->table(['Resultado', 'Cantidad'], [
            ['Expedientes procesados', $procesados],
            ['Expedientes con error', $errores],
        ]);

        return $errores > 0 ? self::FAILURE : self::SUCCESS;
    }
}
