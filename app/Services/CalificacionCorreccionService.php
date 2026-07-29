<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\CalificacionCorreccion;
use App\Models\Inscripcion;
use App\Models\Periodos;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalificacionCorreccionService
{
    public function solicitar(
        Inscripcion $alumno,
        Periodos $periodo,
        ?Calificacion $calificacion,
        array $valorPropuesto,
        string $motivo,
        ?int $usuarioId
    ): CalificacionCorreccion {
        $this->validarMotivo($motivo);

        $asignacionMateriaId = (int) ($valorPropuesto['asignacion_materia_id'] ?? 0);

        $duplicada = CalificacionCorreccion::query()
            ->where('periodo_id', $periodo->id)
            ->where('inscripcion_id', $alumno->id)
            ->whereIn('estado', [CalificacionCorreccion::SOLICITADA, CalificacionCorreccion::AUTORIZADA])
            ->get()
            ->contains(fn (CalificacionCorreccion $item) => (int) data_get($item->valor_propuesto, 'asignacion_materia_id') === $asignacionMateriaId);

        if ($duplicada) {
            throw ValidationException::withMessages([
                'motivoCorreccion' => 'Ya existe una corrección pendiente para este alumno, periodo y materia.',
            ]);
        }

        return CalificacionCorreccion::query()->create([
            'calificacion_id' => $calificacion?->id,
            'periodo_id' => $periodo->id,
            'inscripcion_id' => $alumno->id,
            'inscripcion_ciclo_id' => $valorPropuesto['inscripcion_ciclo_id'] ?? $calificacion?->inscripcion_ciclo_id,
            'estado' => CalificacionCorreccion::SOLICITADA,
            'motivo' => trim($motivo),
            'valor_anterior' => $calificacion?->getAttributes(),
            'valor_propuesto' => $valorPropuesto,
            'solicitada_por' => $usuarioId,
            'solicitada_at' => now(),
        ]);
    }

    public function autorizar(CalificacionCorreccion $correccion, string $observacion, ?int $usuarioId): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $correccion->update([
            'estado' => CalificacionCorreccion::AUTORIZADA,
            'autorizada_por' => $usuarioId,
            'autorizada_at' => now(),
            'observacion_autorizacion' => trim($observacion),
        ]);
    }

    public function aplicar(CalificacionCorreccion $correccion, ?int $usuarioId): ?Calificacion
    {
        if ($correccion->estado !== CalificacionCorreccion::AUTORIZADA) {
            throw ValidationException::withMessages(['correccion' => 'La corrección debe estar autorizada antes de aplicarse.']);
        }

        return DB::transaction(function () use ($correccion, $usuarioId): ?Calificacion {
            $calificacion = $this->aplicarValorPropuesto($correccion);

            $correccion->update([
                'calificacion_id' => $calificacion?->id,
                'inscripcion_ciclo_id' => $calificacion?->inscripcion_ciclo_id ?: $correccion->inscripcion_ciclo_id,
                'estado' => CalificacionCorreccion::APLICADA,
                'aplicada_por' => $usuarioId,
                'aplicada_at' => now(),
            ]);

            return $calificacion;
        });
    }

    /**
     * Aplica una corrección histórica realizada por administración sin requerir
     * una segunda autorización. Se conserva el mismo registro de evidencia,
     * marcándolo solicitado, autorizado y aplicado por el administrador.
     */
    public function aplicarDirecta(
        Inscripcion $alumno,
        Periodos $periodo,
        ?Calificacion $calificacion,
        array $valorPropuesto,
        string $motivo,
        ?int $usuarioId,
    ): CalificacionCorreccion {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede aplicar correcciones históricas.');
        $this->validarMotivo($motivo);

        return DB::transaction(function () use ($alumno, $periodo, $calificacion, $valorPropuesto, $motivo, $usuarioId): CalificacionCorreccion {
            $ahora = now();

            $correccion = CalificacionCorreccion::query()->create([
                'calificacion_id' => $calificacion?->id,
                'periodo_id' => $periodo->id,
                'inscripcion_id' => $alumno->id,
                'inscripcion_ciclo_id' => $valorPropuesto['inscripcion_ciclo_id'] ?? $calificacion?->inscripcion_ciclo_id,
                'estado' => CalificacionCorreccion::AUTORIZADA,
                'motivo' => trim($motivo),
                'valor_anterior' => $calificacion?->getAttributes(),
                'valor_propuesto' => $valorPropuesto,
                'solicitada_por' => $usuarioId,
                'solicitada_at' => $ahora,
                'autorizada_por' => $usuarioId,
                'autorizada_at' => $ahora,
                'observacion_autorizacion' => 'Corrección histórica directa realizada por administración.',
            ]);

            $resultado = $this->aplicarValorPropuesto($correccion);

            $correccion->update([
                'calificacion_id' => $resultado?->id,
                'inscripcion_ciclo_id' => $resultado?->inscripcion_ciclo_id ?: $correccion->inscripcion_ciclo_id,
                'estado' => CalificacionCorreccion::APLICADA,
                'aplicada_por' => $usuarioId,
                'aplicada_at' => $ahora,
            ]);

            return $correccion->fresh();
        });
    }

    private function aplicarValorPropuesto(CalificacionCorreccion $correccion): ?Calificacion
    {
        $propuesto = $correccion->valor_propuesto ?? [];
        $accion = (string) ($propuesto['accion'] ?? 'actualizar');

        if ($accion === 'eliminar') {
            $calificacion = $correccion->calificacion;
            $calificacion?->delete();

            return null;
        }

        $calificacion = $correccion->calificacion ?: new Calificacion();
        $permitidos = [
            'inscripcion_ciclo_id', 'asignacion_materia_id', 'nivel_id', 'grado_id', 'grupo_id',
            'ciclo_escolar_id', 'generacion_id', 'semestre_id', 'calificacion', 'valor_numerico',
            'es_numerica', 'clave_especial', 'observacion', 'capturado_por', 'fecha_captura',
            'ip_captura',
        ];
        $calificacion->fill(collect($propuesto)->only($permitidos)->all());
        $calificacion->inscripcion_id = $correccion->inscripcion_id;
        $calificacion->periodo_id = $correccion->periodo_id;
        $calificacion->save();

        return $calificacion;
    }

    private function validarMotivo(string $motivo): void
    {
        if (mb_strlen(trim($motivo)) < 10) {
            throw ValidationException::withMessages([
                'motivoCorreccion' => 'El motivo debe contener al menos 10 caracteres.',
            ]);
        }
    }
}
