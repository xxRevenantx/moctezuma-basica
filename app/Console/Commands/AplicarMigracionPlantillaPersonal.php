<?php

namespace App\Console\Commands;

use App\Models\CicloEscolar;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelCiclo;
use App\Models\PersonaNivelDetalle;
use App\Models\PlantillaPersonalNivel;
use App\Services\PlantillaPersonalCicloService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AplicarMigracionPlantillaPersonal extends Command
{
    protected $signature = 'plantilla-personal:aplicar-migracion
        {--ciclo=2025-2026 : Ciclo histórico que recibirá la plantilla existente}
        {--confirmar : Aplica los cambios después de revisar la previsualización}';

    protected $description = 'Reconstruye de forma segura la fotografía histórica de la plantilla de personal por ciclo.';

    public function handle(PlantillaPersonalCicloService $service): int
    {
        [$inicio, $fin] = array_pad(explode('-', (string) $this->option('ciclo'), 2), 2, null);
        $ciclo = CicloEscolar::query()
            ->where('inicio_anio', (int) $inicio)
            ->where('fin_anio', (int) $fin)
            ->first();

        if (!$ciclo) {
            $this->error('No se encontró el ciclo indicado.');
            return self::FAILURE;
        }

        $candidatos = PersonaNivelDetalle::query()
            ->with(['cabecera', 'grupo'])
            ->whereNull('archivado_at')
            ->where(function (Builder $query) use ($ciclo): void {
                $query->whereNull('persona_nivel_ciclo_id')
                    ->orWhereHas('grupo', fn (Builder $grupo) => $grupo->where('ciclo_escolar_id', $ciclo->id))
                    ->orWhereNull('grupo_id');
            })
            ->get();

        $sinMembresia = $candidatos->whereNull('persona_nivel_ciclo_id')->count();
        $conGrupoCiclo = $candidatos->filter(fn (PersonaNivelDetalle $detalle) => (int) $detalle->grupo?->ciclo_escolar_id === (int) $ciclo->id)->count();
        $generales = $candidatos->whereNull('grupo_id')->count();

        $this->table(['Concepto', 'Cantidad'], [
            ['Ciclo histórico', $ciclo->nombre],
            ['Asignaciones candidatas', $candidatos->count()],
            ['Sin membresía de ciclo', $sinMembresia],
            ['Con grupo del ciclo histórico', $conGrupoCiclo],
            ['Funciones generales', $generales],
        ]);

        if (!$this->option('confirmar')) {
            $this->warn('Vista previa únicamente. Ejecuta nuevamente con --confirmar para reconstruir la fotografía histórica.');
            return self::SUCCESS;
        }

        $resultado = DB::transaction(function () use ($ciclo, $candidatos, $service): array {
            $plantillas = [];
            $membresias = [];
            $vinculadas = 0;
            $copiadas = 0;

            foreach ($candidatos as $detalle) {
                $personaNivel = $detalle->cabecera;
                if (!$personaNivel) {
                    continue;
                }

                $nivelId = (int) $personaNivel->nivel_id;
                $plantilla = $plantillas[$nivelId] ??= PlantillaPersonalNivel::query()->firstOrCreate([
                    'ciclo_escolar_id' => $ciclo->id,
                    'nivel_id' => $nivelId,
                ], [
                    'estado' => PlantillaPersonalNivel::ESTADO_CERRADA,
                    'cerrada_at' => $ciclo->cerrado_at ?: now(),
                    'observaciones' => 'Fotografía histórica reconstruida mediante comando administrativo.',
                ]);

                $plantilla->forceFill([
                    'estado' => PlantillaPersonalNivel::ESTADO_CERRADA,
                    'cerrada_at' => $plantilla->cerrada_at ?: ($ciclo->cerrado_at ?: now()),
                ])->save();

                $key = $plantilla->id . ':' . $personaNivel->id;
                $membresia = $membresias[$key] ??= PersonaNivelCiclo::query()->firstOrCreate([
                    'plantilla_personal_nivel_id' => $plantilla->id,
                    'persona_nivel_id' => $personaNivel->id,
                ], [
                    'estado' => PersonaNivelCiclo::ESTADO_ACTIVO,
                    'orden' => $personaNivel->orden,
                    'fecha_inicio' => $ciclo->inicio_anio . '-07-01',
                    'fecha_fin' => $ciclo->fin_anio . '-06-30',
                ]);

                if (!$detalle->persona_nivel_ciclo_id) {
                    $detalle->forceFill(['persona_nivel_ciclo_id' => $membresia->id])->save();
                    $vinculadas++;
                    continue;
                }

                if ((int) $detalle->cicloAsignacion?->plantilla?->ciclo_escolar_id === (int) $ciclo->id) {
                    continue;
                }

                $existe = PersonaNivelDetalle::query()
                    ->where('persona_nivel_ciclo_id', $membresia->id)
                    ->where('persona_role_id', $detalle->persona_role_id)
                    ->when($detalle->grado_id, fn (Builder $q) => $q->where('grado_id', $detalle->grado_id), fn (Builder $q) => $q->whereNull('grado_id'))
                    ->when($detalle->grupo_id, fn (Builder $q) => $q->where('grupo_id', $detalle->grupo_id), fn (Builder $q) => $q->whereNull('grupo_id'))
                    ->exists();

                if ($existe) {
                    continue;
                }

                $copia = $detalle->replicate([
                    'persona_nivel_ciclo_id', 'created_at', 'updated_at',
                    'archivado_at', 'archivado_por', 'motivo_archivo',
                    'fecha_baja', 'motivo_baja',
                ]);
                $copia->persona_nivel_ciclo_id = $membresia->id;
                $copia->fecha_inicio = $copia->fecha_inicio ?: $ciclo->inicio_anio . '-07-01';
                $copia->fecha_fin = $copia->fecha_fin ?: $ciclo->fin_anio . '-06-30';
                $copia->estado = PersonaNivelDetalle::ESTADO_ACTIVO;
                $copia->confirmado = true;
                $copia->pendiente_motivo = null;
                $copia->save();
                $copiadas++;
            }

            foreach ($plantillas as $plantilla) {
                $service->actualizarDiagnostico($plantilla);
            }

            return compact('vinculadas', 'copiadas');
        });

        $this->info("Fotografía histórica reconstruida: {$resultado['vinculadas']} asignaciones vinculadas y {$resultado['copiadas']} copias históricas creadas.");
        $this->comment('Las plantillas del ciclo quedaron cerradas e inmutables. Los datos SEG, SEP y C.T. permanecen en persona_nivel.');

        return self::SUCCESS;
    }
}
