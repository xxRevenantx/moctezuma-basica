<?php

namespace App\Console\Commands;

use App\Models\AsignacionMateria;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Taller;
use App\Models\TallerSesion;
use App\Services\HorarioTallerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MigrarTalleresConjuntosSecundaria extends Command
{
    protected $signature = 'talleres:migrar-secundaria {--eliminar-legado : Archiva las asignaciones antiguas llamadas Taller sin borrar su historial}';

    protected $description = 'Convierte los registros antiguos de Taller de secundaria en una sesión compartida para varios grupos.';

    public function handle(HorarioTallerService $service): int
    {
        if (!Schema::hasTable('talleres') || !Schema::hasColumn('horarios', 'taller_sesion_id')) {
            $this->error('Primero ejecuta: php artisan migrate');
            return self::FAILURE;
        }

        $nivel = Nivel::query()->where('slug', 'secundaria')->first();

        if (!$nivel) {
            $this->error('No se encontró el nivel secundaria.');
            return self::FAILURE;
        }

        $materias = Materia::query()
            ->where('nivel_id', $nivel->id)
            ->where('slug', 'taller')
            ->where('extra', true)
            ->where('receso', true)
            ->get();

        if ($materias->isEmpty()) {
            $this->info('No hay talleres antiguos pendientes de convertir.');
            return self::SUCCESS;
        }

        $asignaciones = AsignacionMateria::query()
            ->whereIn('materia_id', $materias->pluck('id'))
            ->get();

        $horarios = Horario::query()
            ->whereIn('asignacion_materia_id', $asignaciones->pluck('id'))
            ->whereNull('taller_sesion_id')
            ->get();

        if ($horarios->isEmpty()) {
            $this->warn('Las materias antiguas existen, pero no tienen bloques de horario para convertir.');

            if ($this->option('eliminar-legado')) {
                AsignacionMateria::query()
                    ->whereIn('id', $asignaciones->pluck('id'))
                    ->update([
                        'estado' => AsignacionMateria::ESTADO_ARCHIVADA,
                        'fecha_fin' => now()->toDateString(),
                        'updated_at' => now(),
                    ]);
                $this->info('Las asignaciones antiguas sin horario se archivaron; no se eliminó información.');
            }

            return self::SUCCESS;
        }

        $ultimoCicloId = DB::table('ciclo_escolares')->max('id');

        if (!$ultimoCicloId) {
            $this->error('No existe un ciclo escolar para relacionar la sesión.');
            return self::FAILURE;
        }

        $taller = Taller::query()->firstOrCreate(
            [
                'nivel_id' => $nivel->id,
                'slug' => Str::slug('Taller'),
            ],
            [
                'nombre' => 'Taller',
                'clave' => 'TALLER',
                'descripcion' => 'Sesión semanal conjunta para grupos de secundaria.',
                'activo' => true,
            ]
        );

        $convertidas = 0;

        DB::transaction(function () use ($horarios, $asignaciones, $materias, $ultimoCicloId, $taller, $service, &$convertidas) {
            $horarios
                ->groupBy(function (Horario $horario) use ($ultimoCicloId) {
                    return implode('|', [
                        $horario->ciclo_escolar_id ?: $ultimoCicloId,
                        $horario->dia_id,
                        $horario->hora_id,
                    ]);
                })
                ->each(function ($bloques) use ($ultimoCicloId, $taller, $service, &$convertidas) {
                    /** @var Horario $primero */
                    $primero = $bloques->first();
                    $cicloId = (int) ($primero->ciclo_escolar_id ?: $ultimoCicloId);
                    $profesorId = $bloques
                        ->map(fn(Horario $horario) => $horario->asignacionMateria?->profesor_id)
                        ->filter()
                        ->first();
                    $grupoIds = $bloques->pluck('grupo_id')->filter()->unique()->values();

                    if ($grupoIds->count() < 2) {
                        return;
                    }

                    $sesion = TallerSesion::query()->firstOrNew([
                        'taller_id' => $taller->id,
                        'ciclo_escolar_id' => $cicloId,
                        'dia_id' => $primero->dia_id,
                        'hora_id' => $primero->hora_id,
                    ]);

                    if (!$sesion->exists || !$sesion->profesor_id) {
                        $sesion->profesor_id = $profesorId;
                    }

                    $sesion->ubicacion ??= null;
                    $sesion->conflicto_forzado = false;
                    $sesion->forzado_por = null;
                    $sesion->motivo_conflicto = null;
                    $sesion->save();

                    Horario::query()->whereIn('id', $bloques->pluck('id'))->delete();
                    $sesion->grupos()->syncWithoutDetaching($grupoIds->all());
                    $sesion->load('grupos');
                    $service->sincronizarHorarios($sesion);
                    $convertidas++;
                });

            if ($this->option('eliminar-legado')) {
                AsignacionMateria::query()
                    ->whereIn('id', $asignaciones->pluck('id'))
                    ->update([
                        'estado' => AsignacionMateria::ESTADO_ARCHIVADA,
                        'fecha_fin' => now()->toDateString(),
                        'updated_at' => now(),
                    ]);
            }
        });

        $this->info("Sesiones conjuntas convertidas: {$convertidas}");
        $this->info('Cada sesión compartida cuenta como un solo bloque semanal para el profesor.');

        if (!$this->option('eliminar-legado')) {
            $this->warn('Las materias antiguas permanecen disponibles. Usa --eliminar-legado únicamente para archivar sus asignaciones después de verificar la conversión.');
        }

        return self::SUCCESS;
    }
}
