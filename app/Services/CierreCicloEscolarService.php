<?php

namespace App\Services;

use App\Models\Generacion;
use App\Models\Inscripcion;
use App\Models\ProcesoCierreCiclo;
use App\Models\ProcesoCierreCicloDetalle;
use App\Models\cicloEscolar;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CierreCicloEscolarService
{
    private const ESTATUS_EGRESABLES = ['activo', 'reingreso'];
    private const ESTATUS_BAJA = ['baja_temporal', 'baja_definitiva', 'trasladado', 'traslado', 'suspendido', 'inactivo'];

    public function diagnostico(int $nivelId, ?int $generacionId, ?int $cicloId): array
    {
        $base = Inscripcion::query()->where('nivel_id', $nivelId)
            ->when($generacionId, fn ($q) => $q->where('generacion_id', $generacionId));
        $alumnos = $base->get();
        $activos = $alumnos->whereIn('estatus', self::ESTATUS_EGRESABLES);
        $sinGrupo = $activos->whereNull('grupo_id')->count();
        $noPromovidos = $alumnos->where('estatus', 'no_promovido')->count();
        $bajasPendientes = $alumnos->whereIn('estatus', self::ESTATUS_BAJA)->count();
        $egresados = $alumnos->where('estatus', 'egresado')->count();

        $promocionesPendientes = 0;
        if ($cicloId && Schema::hasTable('decisiones_promocion_oficial')) {
            $confirmados = DB::table('decisiones_promocion_oficial')->where('nivel_id', $nivelId)
                ->where('ciclo_escolar_id', $cicloId)
                ->when($generacionId, fn ($q) => $q->where('generacion_id', $generacionId))
                ->whereNotNull('promocion_confirmada')->pluck('inscripcion_id');
            $promocionesPendientes = $activos->whereNotIn('id', $confirmados)->count();
        }

        $calificacionesPendientes = 0;
        if ($nivelId !== 1 && $cicloId && Schema::hasTable('calificaciones')) {
            $conCalificacion = DB::table('calificaciones')->where('nivel_id', $nivelId)
                ->where('ciclo_escolar_id', $cicloId)
                ->when($generacionId, fn ($q) => $q->where('generacion_id', $generacionId))
                ->whereNotNull('calificacion')->distinct()->pluck('inscripcion_id');
            $calificacionesPendientes = $activos->whereNotIn('id', $conCalificacion)->count();
        }

        $documentosPendientes = 0;
        if (Schema::hasTable('documentos_alumnos')) {
            $conDocumento = DB::table('documentos_alumnos')->where('es_actual', true)->whereIn('inscripcion_id', $activos->pluck('id'))->distinct()->pluck('inscripcion_id');
            $documentosPendientes = $activos->whereNotIn('id', $conDocumento)->count();
        }

        $generacionesVencidas = Generacion::query()->where('nivel_id', $nivelId)->where('status', true)
            ->whereNotNull('fecha_termino')->whereDate('fecha_termino', '<', now()->toDateString())->count();

        return compact('sinGrupo', 'noPromovidos', 'bajasPendientes', 'egresados', 'promocionesPendientes', 'calificacionesPendientes', 'documentosPendientes', 'generacionesVencidas') + [
            'total' => $alumnos->count(), 'candidatos' => $activos->count(),
        ];
    }

    public function candidatos(int $nivelId, int $generacionId, ?int $cicloId): Collection
    {
        $alumnos = Inscripcion::query()->with(['grado', 'semestre', 'grupo.asignacionGrupo'])
            ->where('nivel_id', $nivelId)->where('generacion_id', $generacionId)
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre')->get();

        return $alumnos->map(function (Inscripcion $alumno) use ($cicloId): array {
            $bloqueos = [];
            if (! in_array($alumno->estatus, self::ESTATUS_EGRESABLES, true)) $bloqueos[] = 'Estatus: ' . $alumno->estatus;
            if (! $alumno->grupo_id) $bloqueos[] = 'Sin grupo';
            if ($alumno->estatus === 'no_promovido') $bloqueos[] = 'No promovido';
            if ($cicloId && Schema::hasTable('decisiones_promocion_oficial')) {
                $decision = DB::table('decisiones_promocion_oficial')->where('inscripcion_id', $alumno->id)->where('ciclo_escolar_id', $cicloId)->latest('id')->first();
                if ($decision && $decision->promocion_confirmada === 0) $bloqueos[] = 'Promoción no aprobada';
            }
            return ['id' => $alumno->id, 'nombre' => trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}"), 'matricula' => $alumno->matricula, 'ubicacion' => $alumno->semestre?->numero ? 'Semestre ' . $alumno->semestre->numero : ($alumno->grado?->nombre ?? 'Sin grado'), 'estatus' => $alumno->estatus, 'apto' => empty($bloqueos), 'observacion' => implode(', ', $bloqueos) ?: 'Apto para egreso'];
        });
    }

    public function ejecutar(array $datos, array $seleccionados, ?int $usuarioId): ProcesoCierreCiclo
    {
        return DB::transaction(function () use ($datos, $seleccionados, $usuarioId): ProcesoCierreCiclo {
            $generacion = Generacion::query()->where('nivel_id', $datos['nivel_id'])->lockForUpdate()->findOrFail($datos['generacion_id']);
            $candidatos = $this->candidatos($datos['nivel_id'], $generacion->id, $datos['ciclo_escolar_id'] ?? null)->keyBy('id');
            $proceso = ProcesoCierreCiclo::create([
                'nivel_id' => $datos['nivel_id'], 'ciclo_escolar_id' => $datos['ciclo_escolar_id'] ?? null, 'generacion_id' => $generacion->id,
                'fecha_egreso' => $datos['fecha_egreso'], 'motivo' => $datos['motivo'], 'total_evaluados' => $candidatos->count(),
                'generacion_cerrada' => (bool) ($datos['cerrar_generacion'] ?? false), 'ciclo_cerrado' => (bool) ($datos['cerrar_ciclo'] ?? false),
                'realizado_por' => $usuarioId, 'realizado_at' => now(),
            ]);
            $procesados = 0; $excluidos = 0;
            foreach ($candidatos as $id => $fila) {
                $alumno = Inscripcion::query()->lockForUpdate()->findOrFail($id);
                $antes = $this->snapshot($alumno);
                if (! in_array($id, $seleccionados) || ! $fila['apto']) {
                    $excluidos++;
                    ProcesoCierreCicloDetalle::create(['proceso_cierre_ciclo_id' => $proceso->id, 'inscripcion_id' => $id, 'resultado' => 'excluido', 'observacion' => $fila['observacion'], 'estado_anterior' => $antes, 'estado_nuevo' => $antes]);
                    continue;
                }
                $alumno->update(['estatus' => 'egresado', 'activo' => false, 'fecha_estatus' => $datos['fecha_egreso'], 'motivo_estatus' => $datos['motivo'], 'fecha_baja' => null, 'motivo_baja' => null, 'usuario_acceso_activo' => false]);
                if (Schema::hasTable('matriculas_alumnos')) DB::table('matriculas_alumnos')->where('inscripcion_id', $alumno->id)->where('vigente', true)->update(['vigente' => false, 'fecha_fin' => $datos['fecha_egreso'], 'updated_at' => now()]);
                $despues = $this->snapshot($alumno->fresh());
                ProcesoCierreCicloDetalle::create(['proceso_cierre_ciclo_id' => $proceso->id, 'inscripcion_id' => $id, 'resultado' => 'egresado', 'observacion' => 'Egreso aplicado correctamente.', 'estado_anterior' => $antes, 'estado_nuevo' => $despues]);
                $procesados++;
            }
            if ($datos['cerrar_generacion'] ?? false) $generacion->update(['status' => false, 'cerrada_at' => now(), 'cerrada_por' => $usuarioId, 'motivo_desactivacion' => $datos['motivo'], 'observaciones' => $datos['motivo']]);
            if (($datos['cerrar_ciclo'] ?? false) && ! empty($datos['ciclo_escolar_id'])) cicloEscolar::query()->whereKey($datos['ciclo_escolar_id'])->update(['es_actual' => false, 'cerrado_at' => now(), 'cerrado_por' => $usuarioId]);
            $proceso->update(['total_procesados' => $procesados, 'total_excluidos' => $excluidos, 'resumen' => $this->diagnostico($datos['nivel_id'], $generacion->id, $datos['ciclo_escolar_id'] ?? null)]);
            return $proceso->fresh();
        });
    }

    private function snapshot(Inscripcion $alumno): array
    {
        return Arr::only($alumno->getAttributes(), ['matricula','nivel_id','grado_id','generacion_id','grupo_id','semestre_id','estatus','activo','fecha_estatus','motivo_estatus','usuario_acceso_activo']);
    }
}
