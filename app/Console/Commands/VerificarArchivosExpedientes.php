<?php

namespace App\Console\Commands;

use App\Services\ExpedienteIntegridadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VerificarArchivosExpedientes extends Command
{
    protected $signature = 'expedientes:verificar-archivos
                            {--personal : Revisar únicamente expedientes del personal}
                            {--alumnos : Revisar únicamente expedientes de alumnos}
                            {--sin-fotos : Omitir la revisión de fotografías}
                            {--marcar-pendiente : Cambiar a pendiente los documentos cuyo archivo físico no existe}
                            {--reporte= : Guardar también un reporte CSV en la ruta indicada}';

    protected $description = 'Detecta documentos y fotografías registrados en la BD cuyo archivo físico ya no existe.';

    public function handle(ExpedienteIntegridadService $servicio): int
    {
        $soloPersonal = (bool) $this->option('personal');
        $soloAlumnos = (bool) $this->option('alumnos');

        $incluirPersonal = ! $soloAlumnos || $soloPersonal;
        $incluirAlumnos = ! $soloPersonal || $soloAlumnos;

        if ($soloPersonal && $soloAlumnos) {
            $incluirPersonal = true;
            $incluirAlumnos = true;
        }

        $incidencias = $servicio->incidencias(
            incluirPersonal: $incluirPersonal,
            incluirAlumnos: $incluirAlumnos,
            incluirFotos: ! $this->option('sin-fotos'),
        );

        if ($incidencias->isEmpty()) {
            $this->components->info('Todos los archivos revisados existen correctamente.');

            return self::SUCCESS;
        }

        $this->table(
            ['Origen', 'Categoría', 'ID', 'Persona/Alumno', 'Detalle', 'Estado BD', 'Disco', 'Ruta'],
            $incidencias->map(fn(array $fila) => [
                $fila['origen'],
                $fila['categoria'],
                $fila['registro_id'],
                $fila['responsable'],
                $fila['detalle'],
                $fila['estado'],
                $fila['disco'],
                $fila['ruta'],
            ])->all()
        );

        $this->newLine();
        $this->warn("Se encontraron {$incidencias->count()} incidencia(s).");

        if ($this->option('marcar-pendiente')) {
            $actualizados = $servicio->marcarDocumentosFaltantesPendientes();
            $this->warn("{$actualizados} documento(s) fueron marcados como pendientes. Las fotografías no modifican estados de BD.");
        }

        if ($rutaReporte = trim((string) $this->option('reporte'))) {
            $this->guardarReporte($rutaReporte, $incidencias->all());
            $this->components->info('Reporte guardado en: ' . $rutaReporte);
        }

        return self::SUCCESS;
    }

    private function guardarReporte(string $ruta, array $incidencias): void
    {
        File::ensureDirectoryExists(dirname($ruta));
        $archivo = fopen($ruta, 'w');

        fwrite($archivo, "\xEF\xBB\xBF");
        fputcsv($archivo, ['Origen', 'Categoría', 'ID', 'Responsable', 'Detalle', 'Estado BD', 'Disco', 'Ruta']);

        foreach ($incidencias as $fila) {
            fputcsv($archivo, [
                $fila['origen'],
                $fila['categoria'],
                $fila['registro_id'],
                $fila['responsable'],
                $fila['detalle'],
                $fila['estado'],
                $fila['disco'],
                $fila['ruta'],
            ]);
        }

        fclose($archivo);
    }
}
