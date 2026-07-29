<?php

namespace App\Services;

use App\Models\AlertaAcademica;
use App\Models\RiesgoAcademicoEvaluacion;
use App\Models\SeguimientoAcademicoAccion;
use App\Models\SeguimientoAcademicoCaso;
use App\Models\SeguimientoAcademicoEvento;
use App\Models\SeguimientoAcademicoPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeguimientoAcademicoService
{
    public function abrirDesdeEvaluacion(RiesgoAcademicoEvaluacion $evaluacion, array $datos, int $usuarioId): SeguimientoAcademicoCaso
    {
        return DB::transaction(function () use ($evaluacion, $datos, $usuarioId): SeguimientoAcademicoCaso {
            $existente = SeguimientoAcademicoCaso::query()->activos()->where('inscripcion_ciclo_id', $evaluacion->inscripcion_ciclo_id)->lockForUpdate()->first();
            if ($existente) {
                return $existente;
            }

            $caso = SeguimientoAcademicoCaso::create([
                'folio' => 'SEG-'.now()->format('Y').'-'.Str::upper(Str::substr((string) Str::ulid(), -8)),
                'inscripcion_id' => $evaluacion->inscripcion_id,
                'inscripcion_ciclo_id' => $evaluacion->inscripcion_ciclo_id,
                'riesgo_evaluacion_id' => $evaluacion->id,
                'ciclo_escolar_id' => $evaluacion->ciclo_escolar_id,
                'nivel_id' => $evaluacion->nivel_id,
                'estado' => 'abierto',
                'prioridad' => $datos['prioridad'] ?? ($evaluacion->nivel_riesgo === 'critico' ? 'critica' : 'alta'),
                'riesgo_inicial' => $evaluacion->nivel_riesgo,
                'riesgo_actual' => $evaluacion->nivel_riesgo,
                'puntaje_inicial' => $evaluacion->puntaje,
                'puntaje_actual' => $evaluacion->puntaje,
                'motivo_apertura' => $datos['motivo_apertura'],
                'resumen' => $datos['resumen'] ?? null,
                'responsable_id' => $datos['responsable_id'] ?? null,
                'proxima_revision_at' => $datos['proxima_revision_at'] ?? null,
                'apertura_automatica' => false,
                'abierto_at' => now(),
                'abierto_por' => $usuarioId,
            ]);

            $this->evento($caso, 'apertura', 'Seguimiento abierto', $datos['motivo_apertura'], null, ['riesgo' => $evaluacion->nivel_riesgo, 'puntaje' => $evaluacion->puntaje], $usuarioId, $evaluacion->id);

            return $caso;
        });
    }

    public function actualizarCaso(SeguimientoAcademicoCaso $caso, array $datos, int $usuarioId): SeguimientoAcademicoCaso
    {
        return DB::transaction(function () use ($caso, $datos, $usuarioId): SeguimientoAcademicoCaso {
            $caso = SeguimientoAcademicoCaso::query()->lockForUpdate()->findOrFail($caso->id);
            $antes = $caso->only(['estado', 'prioridad', 'responsable_id', 'proxima_revision_at', 'resumen']);
            $caso->fill($datos)->save();
            $this->evento($caso, 'actualizacion', 'Datos del seguimiento actualizados', null, $antes, $caso->only(array_keys($antes)), $usuarioId);
            return $caso->fresh();
        });
    }

    public function cerrarCaso(SeguimientoAcademicoCaso $caso, string $motivo, int $usuarioId): void
    {
        DB::transaction(function () use ($caso, $motivo, $usuarioId): void {
            $caso = SeguimientoAcademicoCaso::query()->lockForUpdate()->findOrFail($caso->id);
            $pendientes = $caso->acciones()->whereIn('estado', ['pendiente', 'en_proceso'])->count();
            if ($pendientes > 0) {
                throw new \DomainException('No se puede cerrar el seguimiento mientras existan acciones pendientes.');
            }
            $antes = $caso->only(['estado', 'cerrado_at', 'motivo_cierre']);
            $caso->forceFill(['estado' => 'cerrado', 'cerrado_at' => now(), 'cerrado_por' => $usuarioId, 'motivo_cierre' => $motivo])->save();
            $this->evento($caso, 'cierre', 'Seguimiento cerrado', $motivo, $antes, ['estado' => 'cerrado'], $usuarioId);
            $caso->alertas()->where('estado', 'pendiente')->update(['estado' => 'atendida', 'atendida_at' => now(), 'atendida_por' => $usuarioId]);
        });
    }

    public function reabrirCaso(SeguimientoAcademicoCaso $caso, string $motivo, int $usuarioId): void
    {
        DB::transaction(function () use ($caso, $motivo, $usuarioId): void {
            $caso = SeguimientoAcademicoCaso::query()->lockForUpdate()->findOrFail($caso->id);
            $caso->forceFill(['estado' => 'en_seguimiento', 'cerrado_at' => null, 'cerrado_por' => null, 'motivo_cierre' => null])->save();
            $this->evento($caso, 'reapertura', 'Seguimiento reabierto', $motivo, ['estado' => 'cerrado'], ['estado' => 'en_seguimiento'], $usuarioId);
        });
    }

    public function crearPlan(SeguimientoAcademicoCaso $caso, array $datos, int $usuarioId): SeguimientoAcademicoPlan
    {
        return DB::transaction(function () use ($caso, $datos, $usuarioId): SeguimientoAcademicoPlan {
            $plan = SeguimientoAcademicoPlan::create(array_merge($datos, [
                'seguimiento_caso_id' => $caso->id,
                'estado' => 'activo',
                'creado_por' => $usuarioId,
            ]));
            if ($caso->estado === 'abierto') {
                $caso->update(['estado' => 'en_seguimiento']);
            }
            $this->evento($caso, 'plan', 'Plan de intervención creado', $plan->nombre.': '.$plan->objetivo, null, $plan->only(['id', 'nombre', 'objetivo', 'fecha_fin_prevista']), $usuarioId);
            return $plan;
        });
    }

    public function crearAccion(SeguimientoAcademicoCaso $caso, array $datos, int $usuarioId): SeguimientoAcademicoAccion
    {
        return DB::transaction(function () use ($caso, $datos, $usuarioId): SeguimientoAcademicoAccion {
            $accion = SeguimientoAcademicoAccion::create(array_merge($datos, [
                'seguimiento_caso_id' => $caso->id,
                'estado' => 'pendiente',
                'creado_por' => $usuarioId,
                'actualizado_por' => $usuarioId,
            ]));
            if ($caso->estado === 'abierto') {
                $caso->update(['estado' => 'en_seguimiento']);
            }
            $this->evento($caso, 'accion', 'Acción de intervención agregada', $accion->descripcion, null, $accion->only(['id', 'tipo', 'responsable_id', 'fecha_limite']), $usuarioId);
            return $accion;
        });
    }

    public function actualizarAccion(SeguimientoAcademicoAccion $accion, array $datos, int $usuarioId): SeguimientoAcademicoAccion
    {
        return DB::transaction(function () use ($accion, $datos, $usuarioId): SeguimientoAcademicoAccion {
            $accion = SeguimientoAcademicoAccion::query()->lockForUpdate()->findOrFail($accion->id);
            $antes = $accion->only(['estado', 'resultado', 'evidencia', 'completada_at']);
            if (($datos['estado'] ?? null) === 'completada') {
                $datos['completada_at'] = now();
            } elseif (array_key_exists('estado', $datos)) {
                $datos['completada_at'] = null;
            }
            $accion->fill($datos + ['actualizado_por' => $usuarioId])->save();
            $this->evento($accion->caso, 'accion_actualizada', 'Acción de intervención actualizada', $accion->descripcion, $antes, $accion->only(array_keys($antes)), $usuarioId);
            if ($accion->estado === 'completada') {
                AlertaAcademica::query()->where('seguimiento_caso_id', $accion->seguimiento_caso_id)->where('metadata->accion_id', $accion->id)->where('estado', 'pendiente')->update(['estado' => 'atendida', 'atendida_at' => now(), 'atendida_por' => $usuarioId]);
            }
            return $accion->fresh();
        });
    }

    public function registrarNota(SeguimientoAcademicoCaso $caso, string $titulo, string $descripcion, int $usuarioId): void
    {
        $this->evento($caso, 'nota', $titulo, $descripcion, null, null, $usuarioId);
    }

    private function evento(SeguimientoAcademicoCaso $caso, string $tipo, string $titulo, ?string $descripcion, ?array $antes, ?array $despues, ?int $usuarioId, ?int $evaluacionId = null): void
    {
        SeguimientoAcademicoEvento::create([
            'seguimiento_caso_id' => $caso->id,
            'riesgo_evaluacion_id' => $evaluacionId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'datos_anteriores' => $antes,
            'datos_nuevos' => $despues,
            'registrado_por' => $usuarioId,
            'ocurrido_at' => now(),
        ]);
    }
}
