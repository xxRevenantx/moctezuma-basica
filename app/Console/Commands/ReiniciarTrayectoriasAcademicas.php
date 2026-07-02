<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReiniciarTrayectoriasAcademicas extends Command
{
    private const FRASE_CONFIRMACION = 'ELIMINAR_TRAYECTORIAS';

    protected $signature = 'trayectorias:reiniciar
        {--confirmar= : Escribe ELIMINAR_TRAYECTORIAS para ejecutar sin la pregunta interactiva}
        {--solo-respaldo : Crea el respaldo, pero no elimina ningún registro}';

    protected $description = 'Respalda y vacía trayectorias_academicas sin eliminar alumnos, matrículas, movimientos, documentos ni calificaciones.';

    public function handle(): int
    {
        if (!Schema::hasTable('trayectorias_academicas')) {
            $this->error('La tabla trayectorias_academicas no existe.');

            return self::FAILURE;
        }

        $total = DB::table('trayectorias_academicas')->count();

        if ($total === 0) {
            $this->info('La tabla trayectorias_academicas ya está vacía. No se realizó ningún cambio.');

            return self::SUCCESS;
        }

        $this->warn("Se encontraron {$total} trayectoria(s) académica(s).");
        $this->line('Se conservarán inscripciones, matrículas, movimientos, bajas, reingresos, documentos y calificaciones.');
        $this->line('Las referencias a trayectorias quedarán en NULL para evitar registros huérfanos.');

        try {
            $rutaRespaldo = $this->crearRespaldo();
        } catch (Throwable $e) {
            report($e);
            $this->error('No fue posible crear el respaldo. No se eliminó ninguna trayectoria.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Respaldo creado: storage/app/private/{$rutaRespaldo}");

        if ($this->option('solo-respaldo')) {
            $this->info('Proceso terminado en modo solo respaldo.');

            return self::SUCCESS;
        }

        if (!$this->confirmacionValida()) {
            $this->warn('Operación cancelada. El respaldo sí se conservó.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function (): void {
                // Esta columna existe en algunas instalaciones sin llave foránea.
                // Se limpia manualmente para que no queden IDs inexistentes.
                if (
                    Schema::hasTable('calificaciones_campos_formativos')
                    && Schema::hasColumn('calificaciones_campos_formativos', 'trayectoria_academica_id')
                ) {
                    DB::table('calificaciones_campos_formativos')
                        ->whereNotNull('trayectoria_academica_id')
                        ->update(['trayectoria_academica_id' => null]);
                }

                // Evita dependencias internas durante la eliminación masiva.
                if (Schema::hasColumn('trayectorias_academicas', 'trayectoria_origen_id')) {
                    DB::table('trayectorias_academicas')
                        ->whereNotNull('trayectoria_origen_id')
                        ->update(['trayectoria_origen_id' => null]);
                }

                /*
                 * Se usa DELETE y no TRUNCATE. Las llaves foráneas con
                 * ON DELETE SET NULL conservan movimientos, documentos,
                 * constancias y decisiones de promoción.
                 */
                DB::table('trayectorias_academicas')->delete();
            }, 3);

            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE trayectorias_academicas AUTO_INCREMENT = 1');
            }
        } catch (Throwable $e) {
            report($e);
            $this->error('Ocurrió un error. La transacción fue revertida y no se vació la tabla.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Se eliminaron {$total} trayectoria(s) académica(s).");
        $this->info('El módulo Matrícula funcionará temporalmente con los datos actuales de inscripciones.');
        $this->warn('Los ciclos históricos permanecerán vacíos hasta ejecutar una reconstrucción validada.');

        return self::SUCCESS;
    }

    private function confirmacionValida(): bool
    {
        $opcion = trim((string) $this->option('confirmar'));

        if (hash_equals(self::FRASE_CONFIRMACION, $opcion)) {
            return true;
        }

        if (!$this->input->isInteractive()) {
            $this->error('En modo no interactivo debes agregar --confirmar=ELIMINAR_TRAYECTORIAS.');

            return false;
        }

        $respuesta = trim((string) $this->ask(
            'Para continuar escribe exactamente ' . self::FRASE_CONFIRMACION
        ));

        return hash_equals(self::FRASE_CONFIRMACION, $respuesta);
    }

    private function crearRespaldo(): string
    {
        $fecha = now()->format('Y-m-d_H-i-s');
        $ruta = "respaldos/trayectorias/trayectorias_{$fecha}.json";

        $tablasReferenciadas = [
            'movimientos_alumnos' => [
                'trayectoria_academica_id',
                'trayectoria_origen_id',
                'trayectoria_destino_id',
            ],
            'documentos_alumnos' => ['trayectoria_academica_id'],
            'constancias_traslado' => ['trayectoria_academica_id'],
            'decisiones_promocion_oficial' => ['trayectoria_academica_id'],
            'calificaciones_campos_formativos' => ['trayectoria_academica_id'],
        ];

        $referencias = [];

        foreach ($tablasReferenciadas as $tabla => $columnas) {
            if (!Schema::hasTable($tabla)) {
                continue;
            }

            $columnasExistentes = collect($columnas)
                ->filter(fn (string $columna) => Schema::hasColumn($tabla, $columna))
                ->values()
                ->all();

            if ($columnasExistentes === []) {
                continue;
            }

            $query = DB::table($tabla);
            $query->where(function ($subquery) use ($columnasExistentes): void {
                foreach ($columnasExistentes as $indice => $columna) {
                    $indice === 0
                        ? $subquery->whereNotNull($columna)
                        : $subquery->orWhereNotNull($columna);
                }
            });

            $referencias[$tabla] = $query
                ->get(array_merge(['id'], $columnasExistentes))
                ->map(fn ($fila) => (array) $fila)
                ->all();
        }

        $contenido = [
            'formato' => 'reinicio_trayectorias_v1',
            'generado_en' => now()->toIso8601String(),
            'base_de_datos' => DB::getDatabaseName(),
            'total_trayectorias' => DB::table('trayectorias_academicas')->count(),
            'trayectorias' => DB::table('trayectorias_academicas')->orderBy('id')->get()
                ->map(fn ($fila) => (array) $fila)
                ->all(),
            'referencias' => $referencias,
        ];

        $json = json_encode(
            $contenido,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        if (!Storage::disk('local')->put($ruta, $json)) {
            throw new \RuntimeException('Storage no pudo escribir el archivo de respaldo.');
        }

        return $ruta;
    }
}
