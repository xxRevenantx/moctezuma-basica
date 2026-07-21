<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\PreinscripcionCiclo;
use App\Models\ProcesoCierreCiclo;
use App\Models\ProcesoCierreCicloDetalle;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CierreCicloEscolarService
{
    private const ESTATUS_EGRESABLES = ['activo', 'reingreso'];
    private const ESTATUS_BAJA = ['baja_temporal', 'baja_definitiva', 'trasladado', 'traslado', 'suspendido', 'inactivo'];

    public function __construct(
        private readonly GestionAcademicaService $gestionAcademica,
        private readonly HistorialCicloEscolarService $historialCiclos,
    ) {}

    public function diagnostico(int $nivelId, ?int $generacionId, ?int $cicloId): array
    {
        $base = Inscripcion::query()
            ->where('nivel_id', $nivelId)
            ->when($generacionId, fn ($q) => $q->where('generacion_id', $generacionId))
            ->when($cicloId, fn ($q) => $q->where('ciclo_escolar_id', $cicloId));

        $alumnos = $base->get();
        $activos = $alumnos->whereIn('estatus', self::ESTATUS_EGRESABLES);
        $sinGrupo = $activos->whereNull('grupo_id')->count();
        $noPromovidos = $alumnos->where('estatus', 'no_promovido')->count();
        $bajasPendientes = $alumnos->whereIn('estatus', self::ESTATUS_BAJA)->count();
        $egresados = $alumnos->where('estatus', 'egresado')->count();

        $preinscripcionesNoFormalizadas = $cicloId
            ? PreinscripcionCiclo::query()
                ->where('ciclo_escolar_id', $cicloId)
                ->where('nivel_id', $nivelId)
                ->when($generacionId, fn ($q) => $q->where('generacion_id', $generacionId))
                ->whereIn('estado', ['pendiente', 'cancelada', 'vencida'])
                ->count()
            : 0;

        $promocionesPendientes = 0;
        if ($cicloId && Schema::hasTable('decisiones_promocion_oficial')) {
            $confirmados = DB::table('decisiones_promocion_oficial')
                ->where('nivel_id', $nivelId)
                ->where('ciclo_escolar_id', $cicloId)
                ->when($generacionId, fn ($q) => $q->where('generacion_id', $generacionId))
                ->whereNotNull('promocion_confirmada')
                ->pluck('inscripcion_id');
            $promocionesPendientes = $activos->whereNotIn('id', $confirmados)->count();
        }

        $calificacionesPendientes = 0;
        if ($nivelId !== 1 && $cicloId && Schema::hasTable('calificaciones')) {
            $conCalificacion = DB::table('calificaciones')
                ->where('nivel_id', $nivelId)
                ->where('ciclo_escolar_id', $cicloId)
                ->when($generacionId, fn ($q) => $q->where('generacion_id', $generacionId))
                ->whereNotNull('calificacion')
                ->distinct()
                ->pluck('inscripcion_id');
            $calificacionesPendientes = $activos->whereNotIn('id', $conCalificacion)->count();
        }

        $documentosPendientes = 0;
        if (Schema::hasTable('documentos_alumnos')) {
            $conDocumento = DB::table('documentos_alumnos')
                ->where('es_actual', true)
                ->whereIn('inscripcion_id', $activos->pluck('id'))
                ->distinct()
                ->pluck('inscripcion_id');
            $documentosPendientes = $activos->whereNotIn('id', $conDocumento)->count();
        }

        $generacionesVencidas = Generacion::query()
            ->where('nivel_id', $nivelId)
            ->where('status', true)
            ->whereNotNull('fecha_termino')
            ->whereDate('fecha_termino', '<', now()->toDateString())
            ->count();

        return compact(
            'sinGrupo',
            'noPromovidos',
            'bajasPendientes',
            'egresados',
            'promocionesPendientes',
            'calificacionesPendientes',
            'documentosPendientes',
            'generacionesVencidas',
            'preinscripcionesNoFormalizadas'
        ) + [
            'total' => $alumnos->count(),
            'candidatos' => $activos->count(),
        ];
    }

    public function candidatos(int $nivelId, int $generacionId, ?int $cicloId): Collection
    {
        $alumnos = Inscripcion::query()
            ->with(['grado', 'semestre', 'grupo.asignacionGrupo', 'cicloEscolarActualHistorial'])
            ->where('nivel_id', $nivelId)
            ->where('generacion_id', $generacionId)
            ->when($cicloId, fn ($q) => $q->where('ciclo_escolar_id', $cicloId))
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        return $alumnos->map(function (Inscripcion $alumno) use ($cicloId): array {
            $bloqueos = [];
            if (! in_array($alumno->estatus, self::ESTATUS_EGRESABLES, true)) {
                $bloqueos[] = 'Estatus: ' . $alumno->estatus;
            }
            if (! $alumno->grupo_id) {
                $bloqueos[] = 'Sin grupo';
            }
            if ($cicloId && (int) $alumno->ciclo_escolar_id !== $cicloId) {
                $bloqueos[] = 'Pertenece a otro ciclo';
            }
            if ($cicloId && ! InscripcionCiclo::query()->where('inscripcion_id', $alumno->id)->where('ciclo_escolar_id', $cicloId)->exists()) {
                $bloqueos[] = 'Sin historial formal del ciclo';
            }
            if ($cicloId && Schema::hasTable('decisiones_promocion_oficial')) {
                $decision = DB::table('decisiones_promocion_oficial')
                    ->where('inscripcion_id', $alumno->id)
                    ->where('ciclo_escolar_id', $cicloId)
                    ->latest('id')
                    ->first();
                if ($decision && $decision->promocion_confirmada === 0) {
                    $bloqueos[] = 'Promoción no aprobada';
                }
            }

            return [
                'id' => $alumno->id,
                'nombre' => trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}"),
                'matricula' => $alumno->matricula,
                'ubicacion' => $alumno->semestre?->numero
                    ? 'Semestre ' . $alumno->semestre->numero . ' · ' . ($alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo')
                    : ($alumno->grado?->nombre ?? 'Sin grado') . ' · ' . ($alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo'),
                'estatus' => $alumno->estatus,
                'apto' => empty($bloqueos),
                'observacion' => implode(', ', $bloqueos) ?: 'Apto para egreso',
            ];
        });
    }

    public function ejecutar(array $datos, array $seleccionados, ?int $usuarioId): ProcesoCierreCiclo
    {
        return DB::transaction(function () use ($datos, $seleccionados, $usuarioId): ProcesoCierreCiclo {
            $generacion = Generacion::query()
                ->where('nivel_id', $datos['nivel_id'])
                ->lockForUpdate()
                ->findOrFail($datos['generacion_id']);

            $cicloId = (int) ($datos['ciclo_escolar_id'] ?? 0);
            if (! $cicloId) {
                throw ValidationException::withMessages(['ciclo_escolar_id' => 'Selecciona el ciclo que se cerrará.']);
            }

            $candidatos = $this->candidatos($datos['nivel_id'], $generacion->id, $cicloId)->keyBy('id');
            $hash = hash('sha256', json_encode([
                'ciclo' => $cicloId,
                'generacion' => $generacion->id,
                'seleccionados' => collect($seleccionados)->map(fn ($id) => (int) $id)->sort()->values()->all(),
                'candidatos' => $candidatos->values()->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $proceso = ProcesoCierreCiclo::query()->create([
                'nivel_id' => $datos['nivel_id'],
                'ciclo_escolar_id' => $cicloId,
                'generacion_id' => $generacion->id,
                'tipo' => 'egreso_generacion',
                'estado' => 'procesando',
                'fecha_egreso' => $datos['fecha_egreso'],
                'fecha_efectiva' => $datos['fecha_egreso'],
                'motivo' => $datos['motivo'],
                'total_evaluados' => $candidatos->count(),
                'generacion_cerrada' => (bool) ($datos['cerrar_generacion'] ?? false),
                'ciclo_cerrado' => (bool) ($datos['cerrar_ciclo'] ?? false),
                'vista_previa_hash' => $hash,
                'realizado_por' => $usuarioId,
                'realizado_at' => now(),
            ]);

            $procesados = 0;
            $excluidos = 0;

            foreach ($candidatos as $id => $fila) {
                $alumno = Inscripcion::query()->lockForUpdate()->findOrFail($id);
                $antes = $this->snapshot($alumno);
                $cicloOrigen = $this->historialCiclos->cicloActual($alumno, $cicloId);

                if (! in_array($id, $seleccionados, true) || ! $fila['apto']) {
                    $excluidos++;
                    ProcesoCierreCicloDetalle::query()->create([
                        'proceso_cierre_ciclo_id' => $proceso->id,
                        'inscripcion_id' => $id,
                        'inscripcion_ciclo_origen_id' => $cicloOrigen?->id,
                        'resultado' => 'excluido',
                        'resultado_propuesto' => 'egresado',
                        'observacion' => $fila['observacion'],
                        'estado_anterior' => $antes,
                        'estado_nuevo' => $antes,
                    ]);
                    continue;
                }

                $actualizado = $this->gestionAcademica->cambiarEstatus(
                    $alumno,
                    'egresado',
                    $datos['motivo'],
                    $usuarioId,
                    $datos['fecha_egreso']
                );
                $despues = $this->snapshot($actualizado);

                ProcesoCierreCicloDetalle::query()->create([
                    'proceso_cierre_ciclo_id' => $proceso->id,
                    'inscripcion_id' => $id,
                    'inscripcion_ciclo_origen_id' => $cicloOrigen?->id,
                    'resultado' => 'egresado',
                    'resultado_propuesto' => 'egresado',
                    'observacion' => 'Egreso aplicado correctamente. El registro del ciclo queda cerrado e inmutable.',
                    'estado_anterior' => $antes,
                    'estado_nuevo' => $despues,
                ]);
                $procesados++;
            }

            if ($datos['cerrar_generacion'] ?? false) {
                $generacion->update([
                    'status' => false,
                    'cerrada_at' => now(),
                    'cerrada_por' => $usuarioId,
                    'motivo_desactivacion' => $datos['motivo'],
                    'observaciones' => $datos['motivo'],
                ]);
            }

            if ($datos['cerrar_ciclo'] ?? false) {
                CicloEscolar::query()->whereKey($cicloId)->update([
                    'es_actual' => false,
                    'cerrado_at' => now(),
                    'cerrado_por' => $usuarioId,
                ]);
            }

            $proceso->update([
                'estado' => 'completado',
                'total_procesados' => $procesados,
                'total_excluidos' => $excluidos,
                'resumen' => $this->diagnostico($datos['nivel_id'], $generacion->id, $cicloId),
            ]);

            return $proceso->fresh();
        });
    }

    private function snapshot(Inscripcion $alumno): array
    {
        return Arr::only($alumno->getAttributes(), [
            'matricula', 'ciclo_escolar_id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id',
            'estatus', 'activo', 'fecha_estatus', 'motivo_estatus', 'usuario_acceso_activo',
        ]);
    }
}
