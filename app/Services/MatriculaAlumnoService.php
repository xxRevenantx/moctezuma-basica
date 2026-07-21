<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\MatriculaAlumno;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatriculaAlumnoService
{
    public function asegurarVigente(
        Inscripcion $alumno,
        string $origen,
        ?int $usuarioId,
        ?string $fecha = null
    ): MatriculaAlumno {
        $fecha ??= now()->toDateString();

        return DB::transaction(function () use ($alumno, $origen, $usuarioId, $fecha): MatriculaAlumno {
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($alumno->id);
            $vigentes = MatriculaAlumno::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('vigente', true)
                ->lockForUpdate()
                ->orderByDesc('fecha_asignacion')
                ->get();

            $misma = $vigentes->first(fn (MatriculaAlumno $m) =>
                (int) $m->nivel_id === (int) $alumno->nivel_id
                && (string) $m->matricula === (string) $alumno->matricula
            );

            if ($misma) {
                $vigentes->where('id', '!=', $misma->id)->each(fn (MatriculaAlumno $m) => $this->cerrar($m, $fecha));
                return $misma;
            }

            $vigentes->each(fn (MatriculaAlumno $m) => $this->cerrar($m, $fecha));

            $historica = MatriculaAlumno::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('nivel_id', $alumno->nivel_id)
                ->where('matricula', $alumno->matricula)
                ->lockForUpdate()
                ->first();

            if ($historica) {
                $historica->update([
                    'vigente' => true,
                    'fecha_fin' => null,
                    'origen' => $origen,
                    'registrado_por' => $usuarioId,
                ]);

                return $historica->refresh();
            }

            $ocupada = MatriculaAlumno::query()
                ->where('matricula', $alumno->matricula)
                ->where('inscripcion_id', '!=', $alumno->id)
                ->exists();

            if ($ocupada) {
                throw ValidationException::withMessages([
                    'matricula' => 'La matrícula ya pertenece al historial de otro alumno.',
                ]);
            }

            return MatriculaAlumno::query()->create([
                'inscripcion_id' => $alumno->id,
                'nivel_id' => $alumno->nivel_id,
                'matricula' => $alumno->matricula,
                'fecha_asignacion' => $fecha,
                'fecha_fin' => null,
                'vigente' => true,
                'origen' => $origen,
                'registrado_por' => $usuarioId,
            ]);
        });
    }

    public function sincronizarCambioAsignacion(
        Inscripcion $alumno,
        array $antes,
        array $despues,
        ?int $usuarioId,
        ?string $fecha = null
    ): void {
        $nivelCambio = (int) ($antes['nivel_id'] ?? 0) !== (int) ($despues['nivel_id'] ?? 0);
        $matriculaCambio = (string) ($antes['matricula'] ?? '') !== (string) ($despues['matricula'] ?? '');

        if ($nivelCambio || $matriculaCambio || ! $alumno->matriculaVigente()->exists()) {
            $this->asegurarVigente($alumno, $nivelCambio ? 'cambio_nivel' : 'correccion', $usuarioId, $fecha);
        }
    }

    public function aplicarEstatus(
        Inscripcion $alumno,
        string $estatus,
        ?int $usuarioId,
        ?string $fecha = null
    ): void {
        $fecha ??= now()->toDateString();

        if (in_array($estatus, ['activo', 'reingreso', 'no_promovido'], true)) {
            $this->asegurarVigente($alumno, $estatus === 'reingreso' ? 'reingreso' : 'reactivacion', $usuarioId, $fecha);
            return;
        }

        if (in_array($estatus, ['baja_definitiva', 'trasladado', 'traslado', 'egresado'], true)) {
            $this->cerrarVigentes($alumno, $fecha);
        }
        // baja_temporal, suspendido e inactivo conservan la matrícula vigente.
    }

    public function cerrarVigentes(Inscripcion $alumno, ?string $fecha = null): int
    {
        $fecha ??= now()->toDateString();
        $registros = MatriculaAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('vigente', true)
            ->lockForUpdate()
            ->get();

        $registros->each(fn (MatriculaAlumno $m) => $this->cerrar($m, $fecha));
        return $registros->count();
    }

    private function cerrar(MatriculaAlumno $matricula, string $fecha): void
    {
        $matricula->update([
            'vigente' => false,
            'fecha_fin' => $fecha,
        ]);
    }
}
