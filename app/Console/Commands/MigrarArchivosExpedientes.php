<?php

namespace App\Console\Commands;

use App\Models\DocumentoAlumno;
use App\Models\DocumentoPersonal;
use App\Models\Inscripcion;
use App\Models\Persona;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrarArchivosExpedientes extends Command
{
    protected $signature = 'expedientes:migrar-almacenamiento
                            {destino : Nombre del disco de destino, por ejemplo s3}
                            {--origen=local : Disco donde están actualmente los documentos}
                            {--fotos : Copiar también fotografías de alumnos y personal}
                            {--origen-fotos=public : Disco donde están actualmente las fotografías}
                            {--confirmar : Ejecutar la copia y actualizar la BD; sin esta opción solo simula}
                            {--eliminar-origen : Eliminar el archivo original después de verificar la copia}';

    protected $description = 'Copia expedientes y fotografías a un disco compartido y actualiza el disco de los documentos en la BD.';

    public function handle(): int
    {
        $origen = trim((string) $this->option('origen'));
        $destino = trim((string) $this->argument('destino'));
        $origenFotos = trim((string) $this->option('origen-fotos'));
        $ejecutar = (bool) $this->option('confirmar');
        $eliminarOrigen = (bool) $this->option('eliminar-origen');

        if ($origen === '' || $destino === '') {
            $this->components->error('Indica discos de origen y destino válidos.');

            return self::FAILURE;
        }

        if ($origen === $destino && ! $this->option('fotos')) {
            $this->components->warn('El disco de documentos de origen y destino es el mismo; no hay nada que migrar.');

            return self::SUCCESS;
        }

        try {
            Storage::disk($origen);
            Storage::disk($destino);

            if ($this->option('fotos')) {
                Storage::disk($origenFotos);
            }
        } catch (Throwable $e) {
            $this->components->error('No fue posible cargar la configuración de los discos: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->components->info($ejecutar
            ? 'Migración real iniciada.'
            : 'Simulación iniciada. No se copiará ni modificará nada hasta agregar --confirmar.');

        $estadisticas = [
            'documentos_encontrados' => 0,
            'documentos_copiados' => 0,
            'documentos_actualizados' => 0,
            'documentos_faltantes' => 0,
            'documentos_error' => 0,
            'fotos_encontradas' => 0,
            'fotos_copiadas' => 0,
            'fotos_faltantes' => 0,
            'fotos_error' => 0,
        ];

        if ($origen !== $destino) {
            $this->migrarDocumentos(DocumentoPersonal::class, $origen, $destino, $ejecutar, $eliminarOrigen, $estadisticas);
            $this->migrarDocumentos(DocumentoAlumno::class, $origen, $destino, $ejecutar, $eliminarOrigen, $estadisticas);
        }

        if ($this->option('fotos')) {
            $rutasFotos = collect();

            Persona::query()
                ->select(['id', 'foto'])
                ->whereNotNull('foto')
                ->where('foto', '!=', '')
                ->chunkById(500, function ($personas) use ($rutasFotos): void {
                    foreach ($personas as $persona) {
                        $foto = ltrim((string) $persona->foto, '/');
                        $rutasFotos->push(str_starts_with($foto, 'personal/') ? $foto : 'personal/' . $foto);
                    }
                });

            Inscripcion::withTrashed()
                ->select(['id', 'foto_path'])
                ->whereNotNull('foto_path')
                ->where('foto_path', '!=', '')
                ->chunkById(500, function ($alumnos) use ($rutasFotos): void {
                    foreach ($alumnos as $alumno) {
                        $rutasFotos->push(ltrim((string) $alumno->foto_path, '/'));
                    }
                });

            foreach ($rutasFotos->filter()->unique()->values() as $ruta) {
                $estadisticas['fotos_encontradas']++;
                $resultado = $this->copiarArchivo($origenFotos, $destino, $ruta, $ejecutar, $eliminarOrigen);
                $estadisticas['fotos_' . $resultado]++;
            }
        }

        $this->table(['Concepto', 'Cantidad'], [
            ['Documentos detectados', $estadisticas['documentos_encontrados']],
            ['Documentos copiados/verificados', $estadisticas['documentos_copiados']],
            ['Registros de documentos actualizados', $estadisticas['documentos_actualizados']],
            ['Documentos faltantes en origen', $estadisticas['documentos_faltantes']],
            ['Errores en documentos', $estadisticas['documentos_error']],
            ['Fotografías detectadas', $estadisticas['fotos_encontradas']],
            ['Fotografías copiadas/verificadas', $estadisticas['fotos_copiadas']],
            ['Fotografías faltantes en origen', $estadisticas['fotos_faltantes']],
            ['Errores en fotografías', $estadisticas['fotos_error']],
        ]);

        if (! $ejecutar) {
            $this->newLine();
            $this->warn('Para ejecutar la migración vuelve a correr el comando con --confirmar.');
        } elseif ($this->option('fotos')) {
            $this->newLine();
            $this->components->info("Cuando verifiques los archivos, configura FOTOS_DISK={$destino} en el .env de todos los equipos.");
        }

        return ($estadisticas['documentos_error'] + $estadisticas['fotos_error']) > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @param class-string<Model> $modelo
     * @param array<string, int> $estadisticas
     */
    private function migrarDocumentos(
        string $modelo,
        string $origen,
        string $destino,
        bool $ejecutar,
        bool $eliminarOrigen,
        array &$estadisticas
    ): void {
        $modelo::query()
            ->where('disco', $origen)
            ->whereNotNull('ruta')
            ->where('ruta', '!=', '')
            ->chunkById(200, function ($documentos) use ($origen, $destino, $ejecutar, $eliminarOrigen, &$estadisticas): void {
                foreach ($documentos as $documento) {
                    $estadisticas['documentos_encontrados']++;
                    $resultado = $this->copiarArchivo($origen, $destino, (string) $documento->ruta, $ejecutar, false);
                    $estadisticas['documentos_' . $resultado]++;

                    if ($ejecutar && $resultado === 'copiados') {
                        $documento->forceFill(['disco' => $destino])->save();
                        $estadisticas['documentos_actualizados']++;

                        if ($eliminarOrigen) {
                            Storage::disk($origen)->delete((string) $documento->ruta);
                        }
                    }
                }
            });
    }

    private function copiarArchivo(
        string $origen,
        string $destino,
        string $ruta,
        bool $ejecutar,
        bool $eliminarOrigen
    ): string {
        try {
            $discoOrigen = Storage::disk($origen);
            $discoDestino = Storage::disk($destino);

            if (! $discoOrigen->exists($ruta)) {
                return 'faltantes';
            }

            if (! $ejecutar) {
                return 'copiados';
            }

            if (! $discoDestino->exists($ruta)) {
                $flujo = $discoOrigen->readStream($ruta);

                if (! is_resource($flujo)) {
                    return 'error';
                }

                try {
                    $guardado = $discoDestino->put($ruta, $flujo);
                } finally {
                    fclose($flujo);
                }

                if (! $guardado) {
                    return 'error';
                }
            }

            if (! $discoDestino->exists($ruta)) {
                return 'error';
            }

            if ($eliminarOrigen && $origen !== $destino) {
                $discoOrigen->delete($ruta);
            }

            return 'copiados';
        } catch (Throwable $e) {
            $this->newLine();
            $this->error("Error al copiar {$ruta}: {$e->getMessage()}");

            return 'error';
        }
    }
}
