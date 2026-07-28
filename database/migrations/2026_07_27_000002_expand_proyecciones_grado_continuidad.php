<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proyecciones_continuidad')) {
            return;
        }

        if (! Schema::hasColumn('proyecciones_continuidad', 'tipo_proyeccion')) {
            Schema::table('proyecciones_continuidad', function (Blueprint $table): void {
                $table->string('tipo_proyeccion', 30)
                    ->nullable()
                    ->after('grupo_destino_id')
                    ->index();
            });
        }

        if (! Schema::hasColumn('proyecciones_continuidad', 'resultado_origen')) {
            Schema::table('proyecciones_continuidad', function (Blueprint $table): void {
                $table->string('resultado_origen', 40)
                    ->nullable()
                    ->after('tipo_proyeccion')
                    ->index();
            });
        }

        if (! Schema::hasColumn('proyecciones_continuidad', 'estatus_pendiente')) {
            Schema::table('proyecciones_continuidad', function (Blueprint $table): void {
                $table->string('estatus_pendiente', 30)
                    ->nullable()
                    ->after('resultado_origen');
            });
        }

        $this->normalizarProyeccionesExistentes();
    }

    /**
     * Completa las columnas nuevas para proyecciones creadas con versiones
     * anteriores del módulo. No cambia su estado pendiente, confirmado o
     * cancelado, ni modifica los historiales de origen o destino.
     */
    private function normalizarProyeccionesExistentes(): void
    {
        $ultimoId = 0;

        do {
            $filas = DB::table('proyecciones_continuidad as proyeccion')
                ->leftJoin(
                    'inscripcion_ciclos as origen',
                    'origen.id',
                    '=',
                    'proyeccion.inscripcion_ciclo_origen_id'
                )
                ->select([
                    'proyeccion.id',
                    'proyeccion.tipo_proyeccion',
                    'proyeccion.resultado_origen',
                    'proyeccion.estatus_pendiente',
                    'proyeccion.nivel_destino_id',
                    'proyeccion.grado_destino_id',
                    'proyeccion.semestre_destino_id',
                    'proyeccion.snapshot_origen',
                    'origen.nivel_id as nivel_origen_id',
                    'origen.grado_id as grado_origen_id',
                    'origen.semestre_id as semestre_origen_id',
                    'origen.resultado_final as resultado_final_origen',
                    'origen.estatus_actual_ciclo as estatus_ciclo_origen',
                ])
                ->where('proyeccion.id', '>', $ultimoId)
                ->where(function ($query): void {
                    $query->whereNull('proyeccion.tipo_proyeccion')
                        ->orWhere('proyeccion.tipo_proyeccion', '')
                        ->orWhereNull('proyeccion.resultado_origen')
                        ->orWhere('proyeccion.resultado_origen', '')
                        ->orWhereNull('proyeccion.estatus_pendiente')
                        ->orWhere('proyeccion.estatus_pendiente', '');
                })
                ->orderBy('proyeccion.id')
                ->limit(250)
                ->get();

            foreach ($filas as $fila) {
                $ultimoId = max($ultimoId, (int) $fila->id);
                $tipo = trim((string) ($fila->tipo_proyeccion ?? ''));

                if ($tipo === '') {
                    $mismoNivel = (int) $fila->nivel_destino_id === (int) $fila->nivel_origen_id;
                    $mismoGrado = (int) $fila->grado_destino_id === (int) $fila->grado_origen_id;
                    $mismoSemestre = (int) ($fila->semestre_destino_id ?? 0)
                        === (int) ($fila->semestre_origen_id ?? 0);

                    $tipo = ! $mismoNivel
                        ? 'siguiente_nivel'
                        : (($mismoGrado && $mismoSemestre) ? 'repeticion' : 'siguiente_grado');
                }

                $resultado = trim((string) ($fila->resultado_origen ?? ''));
                if ($resultado === '') {
                    $resultadoHistorico = trim((string) ($fila->resultado_final_origen ?? ''));

                    $resultado = match ($tipo) {
                        'repeticion' => 'no_promovido',
                        'siguiente_nivel' => $resultadoHistorico !== ''
                            ? $resultadoHistorico
                            : 'egresado',
                        default => in_array($resultadoHistorico, ['promovido', 'promovido_grado'], true)
                            ? $resultadoHistorico
                            : 'promovido_grado',
                    };
                }

                $estatusPendiente = trim((string) ($fila->estatus_pendiente ?? ''));
                if ($estatusPendiente === '') {
                    $snapshot = json_decode((string) ($fila->snapshot_origen ?? ''), true);
                    $estatusSnapshot = is_array($snapshot)
                        ? trim((string) ($snapshot['estatus'] ?? ''))
                        : '';

                    $estatusPendiente = $estatusSnapshot !== ''
                        ? $estatusSnapshot
                        : (trim((string) ($fila->estatus_ciclo_origen ?? '')) ?: (
                            $tipo === 'siguiente_nivel'
                                ? 'egresado'
                                : 'pendiente_reinscripcion'
                        ));
                }

                DB::table('proyecciones_continuidad')
                    ->where('id', $fila->id)
                    ->update([
                        'tipo_proyeccion' => $tipo,
                        'resultado_origen' => $resultado,
                        'estatus_pendiente' => $estatusPendiente,
                    ]);
            }
        } while ($filas->isNotEmpty());
    }

    public function down(): void
    {
        // No se eliminan las columnas para no perder la clasificación de las
        // proyecciones históricas ya confirmadas o canceladas.
    }
};
