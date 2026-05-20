<?php

namespace App\Console\Commands;

use App\Models\Inscripcion;
use App\Models\TrayectoriaAcademica;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CrearTrayectoriasIniciales extends Command
{
    protected $signature = 'trayectorias:crear-iniciales {ciclo_escolar_id}';

    protected $description = 'Crea las trayectorias académicas iniciales de los alumnos inscritos actualmente.';

    public function handle(): int
    {
        $cicloEscolarId = (int) $this->argument('ciclo_escolar_id');

        if ($cicloEscolarId <= 0) {
            $this->error('Debes indicar un ciclo escolar válido.');
            return self::FAILURE;
        }

        $this->info('Creando trayectorias académicas iniciales...');

        $alumnos = Inscripcion::query()
            ->whereNotNull('nivel_id')
            ->whereNotNull('grado_id')
            ->whereNotNull('generacion_id')
            ->whereNotNull('grupo_id')
            ->whereNotNull('ciclo_id')
            ->get();

        if ($alumnos->isEmpty()) {
            $this->warn('No se encontraron alumnos con datos académicos completos.');
            return self::SUCCESS;
        }

        $creadas = 0;
        $omitidas = 0;

        DB::transaction(function () use ($alumnos, $cicloEscolarId, &$creadas, &$omitidas) {
            foreach ($alumnos as $alumno) {
                $existe = TrayectoriaAcademica::query()
                    ->where('inscripcion_id', $alumno->id)
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->exists();

                if ($existe) {
                    $omitidas++;
                    continue;
                }

                TrayectoriaAcademica::create([
                    'inscripcion_id' => $alumno->id,
                    'ciclo_escolar_id' => $cicloEscolarId,
                    'ciclo_id' => $alumno->ciclo_id,

                    'nivel_id' => $alumno->nivel_id,
                    'grado_id' => $alumno->grado_id,
                    'generacion_id' => $alumno->generacion_id,
                    'grupo_id' => $alumno->grupo_id,
                    'semestre_id' => $alumno->semestre_id,

                    'activo' => $alumno->activo ?? true,
                    'fecha_baja' => $alumno->fecha_baja,
                    'motivo_baja' => $alumno->motivo_baja,
                    'observaciones_baja' => $alumno->observaciones_baja,
                    'fecha_inscripcion' => $alumno->fecha_inscripcion ?? $alumno->created_at,
                ]);

                $creadas++;
            }
        });

        $this->info("Trayectorias creadas: {$creadas}");
        $this->info("Trayectorias omitidas porque ya existían: {$omitidas}");

        return self::SUCCESS;
    }
}
