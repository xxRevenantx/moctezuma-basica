<?php

namespace App\Console\Commands;

use App\Models\CicloEscolar;
use App\Models\Inscripcion;
use App\Models\TrayectoriaAcademica;
use App\Services\TrayectoriaAcademicaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CrearTrayectoriasIniciales extends Command
{
    protected $signature = 'trayectorias:crear-iniciales {ciclo_escolar_id}';

    protected $description = 'Crea la trayectoria inicial y la matrícula por nivel de los alumnos activos que todavía no tienen registro en el ciclo y corte.';

    public function handle(TrayectoriaAcademicaService $service): int
    {
        $cicloEscolarId = (int) $this->argument('ciclo_escolar_id');

        if (!CicloEscolar::query()->whereKey($cicloEscolarId)->exists()) {
            $this->error('Debes indicar un ciclo escolar válido.');

            return self::FAILURE;
        }

        if (!Schema::hasColumn('trayectorias_academicas', 'numero_estancia')) {
            $this->error('Primero ejecuta php artisan migrate.');

            return self::FAILURE;
        }

        $alumnos = Inscripcion::query()
            ->where('activo', true)
            ->whereNotNull('nivel_id')
            ->whereNotNull('grado_id')
            ->whereNotNull('generacion_id')
            ->whereNotNull('grupo_id')
            ->whereNotNull('ciclo_id')
            ->orderBy('id')
            ->get();

        if ($alumnos->isEmpty()) {
            $this->warn('No se encontraron alumnos activos con datos académicos completos.');

            return self::SUCCESS;
        }

        $creadas = 0;
        $omitidas = 0;
        $errores = 0;

        $barra = $this->output->createProgressBar($alumnos->count());
        $barra->start();

        foreach ($alumnos as $alumno) {
            try {
                $existe = TrayectoriaAcademica::query()
                    ->where('inscripcion_id', $alumno->id)
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('ciclo_id', $alumno->ciclo_id)
                    ->where('vigente_en_corte', true)
                    ->exists();

                if ($existe) {
                    $omitidas++;
                    $barra->advance();
                    continue;
                }

                $service->registrarInscripcionEnContexto($alumno, [
                    'ciclo_escolar_id' => $cicloEscolarId,
                    'ciclo_id' => $alumno->ciclo_id,
                    'nivel_id' => $alumno->nivel_id,
                    'grado_id' => $alumno->grado_id,
                    'generacion_id' => $alumno->generacion_id,
                    'grupo_id' => $alumno->grupo_id,
                    'semestre_id' => $alumno->semestre_id,
                    'fecha_inscripcion' => $alumno->fecha_inscripcion ?: $alumno->created_at ?: now(),
                    'matricula' => $alumno->matricula,
                ], null, 'inicializacion');

                $creadas++;
            } catch (\Throwable $e) {
                report($e);
                $errores++;
            }

            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);
        $this->info("Trayectorias creadas: {$creadas}");
        $this->info("Omitidas porque ya existían: {$omitidas}");

        if ($errores > 0) {
            $this->warn("Registros con error: {$errores}. Revisa storage/logs/laravel.log.");
        }

        return $errores > 0 ? self::FAILURE : self::SUCCESS;
    }
}
