<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\InscripcionTutor;
use App\Models\Tutor;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionResponsablesAlumnoService
{
    public const PARENTESCOS = [
        'MADRE', 'PADRE', 'ABUELA', 'ABUELO', 'HERMANA', 'HERMANO',
        'TÍA', 'TÍO', 'TUTORA', 'TUTOR', 'REPRESENTANTE',
        'SIN PARENTESCO', 'OTRO',
    ];

    public const ESTADOS_TUTELA = [
        'no_aplica',
        'pendiente',
        'acreditado',
        'revocado',
    ];

    /**
     * @param array<int, array<string, mixed>> $responsables
     */
    public function sincronizar(
        Inscripcion $inscripcion,
        array $responsables,
        ?int $usuarioId = null,
        string|Carbon|null $fechaInicio = null,
        bool $desactivarAusentes = false,
    ): void {
        DB::transaction(function () use (
            $inscripcion,
            $responsables,
            $usuarioId,
            $fechaInicio,
            $desactivarAusentes,
        ): void {
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($inscripcion->getKey());
            $normalizados = $this->normalizarLista($responsables);
            $this->validarLista($alumno, $normalizados, $fechaInicio);

            $existentes = InscripcionTutor::query()
                ->where('inscripcion_id', $alumno->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('tutor_id');

            $idsRecibidos = [];

            // La lista normalizada siempre contiene como máximo un principal.
            // Desmarcamos cualquier principal previo antes de guardar para que
            // una sincronización parcial tampoco pueda dejar dos principales.
            if (collect($normalizados)->contains(
                fn (array $item): bool => (bool) ($item['es_principal'] ?? false)
            )) {
                InscripcionTutor::query()
                    ->where('inscripcion_id', $alumno->id)
                    ->where('activo', true)
                    ->where('es_principal', true)
                    ->update([
                        'es_principal' => false,
                        'updated_by' => $usuarioId,
                        'updated_at' => now(),
                    ]);
            }

            foreach ($normalizados as $indice => $datos) {
                $tutorId = (int) $datos['tutor_id'];
                $idsRecibidos[] = $tutorId;
                $relacion = $existentes->get($tutorId) ?: new InscripcionTutor([
                    'inscripcion_id' => $alumno->id,
                    'tutor_id' => $tutorId,
                    'created_by' => $usuarioId,
                ]);

                $relacion->fill([
                    ...$datos,
                    'orden_contacto' => $indice + 1,
                    'activo' => true,
                    'fecha_inicio' => $relacion->fecha_inicio
                        ?: $this->fecha($fechaInicio)
                        ?: optional($alumno->fecha_inscripcion)->toDateString()
                        ?: now()->toDateString(),
                    'fecha_fin' => null,
                    'motivo_fin' => null,
                    'updated_by' => $usuarioId,
                ]);
                $relacion->save();
            }

            if ($desactivarAusentes) {
                InscripcionTutor::query()
                    ->where('inscripcion_id', $alumno->id)
                    ->whereNotIn('tutor_id', $idsRecibidos ?: [0])
                    ->where('activo', true)
                    ->update([
                        'activo' => false,
                        'es_principal' => false,
                        'fecha_fin' => now()->toDateString(),
                        'motivo_fin' => 'Retirado de la relación vigente durante la actualización del alumno.',
                        'updated_by' => $usuarioId,
                        'updated_at' => now(),
                    ]);
            }

            $this->sincronizarTutorLegado($alumno);
        });
    }

    /** @param array<string, mixed> $datos */
    public function agregarOActualizar(
        Inscripcion $inscripcion,
        Tutor $tutor,
        array $datos,
        ?int $usuarioId = null,
    ): InscripcionTutor {
        return DB::transaction(function () use ($inscripcion, $tutor, $datos, $usuarioId): InscripcionTutor {
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($inscripcion->id);
            $tutor = Tutor::query()->lockForUpdate()->findOrFail($tutor->id);

            if (! $tutor->activo) {
                throw ValidationException::withMessages([
                    'responsables' => 'El responsable está archivado y no puede asignarse hasta ser reactivado.',
                ]);
            }

            $relaciones = InscripcionTutor::query()
                ->where('inscripcion_id', $alumno->id)
                ->lockForUpdate()
                ->get();

            $normalizado = $this->normalizarRelacion([
                ...$datos,
                'tutor_id' => $tutor->id,
            ]);

            if ($normalizado['es_principal']) {
                $this->validarMedioContactoPrincipal($tutor);
                InscripcionTutor::query()
                    ->where('inscripcion_id', $alumno->id)
                    ->where('tutor_id', '!=', $tutor->id)
                    ->where('activo', true)
                    ->update([
                        'es_principal' => false,
                        'updated_by' => $usuarioId,
                        'updated_at' => now(),
                    ]);
            }

            $relacion = $relaciones->firstWhere('tutor_id', $tutor->id)
                ?: new InscripcionTutor([
                    'inscripcion_id' => $alumno->id,
                    'tutor_id' => $tutor->id,
                    'created_by' => $usuarioId,
                ]);

            $fechaInicioSolicitada = $this->fecha(Arr::get($datos, 'fecha_inicio'));

            $relacion->fill([
                ...$normalizado,
                'orden_contacto' => $relacion->exists
                    ? max(1, (int) $relacion->orden_contacto)
                    : ((int) $relaciones->max('orden_contacto') + 1),
                'activo' => true,
                'fecha_inicio' => $fechaInicioSolicitada
                    ?: optional($relacion->fecha_inicio)->toDateString()
                    ?: now()->toDateString(),
                'fecha_fin' => null,
                'motivo_fin' => null,
                'updated_by' => $usuarioId,
            ]);
            $relacion->save();

            if (! InscripcionTutor::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('activo', true)
                ->where('es_principal', true)
                ->exists()) {
                $this->validarMedioContactoPrincipal($tutor);
                $relacion->forceFill([
                    'es_principal' => true,
                    'updated_by' => $usuarioId,
                ])->save();
            }

            $this->sincronizarTutorLegado($alumno);

            app(SystemAuditService::class)->record('responsable_alumno_actualizado', 'alumnos', [
                'inscripcion_id' => $alumno->id,
                'tutor_id' => $tutor->id,
                'parentesco' => $relacion->parentesco,
                'es_principal' => $relacion->es_principal,
            ]);

            return $relacion->fresh(['tutor']);
        });
    }

    public function establecerPrincipal(Inscripcion $inscripcion, int $tutorId, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($inscripcion, $tutorId, $usuarioId): void {
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($inscripcion->id);
            $relaciones = InscripcionTutor::query()
                ->where('inscripcion_id', $alumno->id)
                ->lockForUpdate()
                ->get();

            $principal = $relaciones->first(
                fn (InscripcionTutor $relacion): bool => (int) $relacion->tutor_id === $tutorId && $relacion->activo
            );

            if (! $principal) {
                throw ValidationException::withMessages([
                    'responsables' => 'El responsable seleccionado no tiene una relación activa con el alumno.',
                ]);
            }

            $tutorPrincipal = Tutor::query()->lockForUpdate()->findOrFail($principal->tutor_id);

            if (! $tutorPrincipal->activo) {
                throw ValidationException::withMessages([
                    'responsables' => 'El responsable está archivado y no puede establecerse como principal.',
                ]);
            }

            $this->validarMedioContactoPrincipal($tutorPrincipal);

            InscripcionTutor::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('activo', true)
                ->update([
                    'es_principal' => false,
                    'updated_by' => $usuarioId,
                    'updated_at' => now(),
                ]);

            $principal->forceFill([
                'es_principal' => true,
                'updated_by' => $usuarioId,
            ])->save();

            $this->sincronizarTutorLegado($alumno);

            app(SystemAuditService::class)->record('responsable_principal_cambiado', 'alumnos', [
                'inscripcion_id' => $alumno->id,
                'tutor_id' => $tutorId,
            ]);
        });
    }

    public function desactivar(
        Inscripcion $inscripcion,
        int $tutorId,
        string $motivo,
        ?int $usuarioId = null,
    ): void {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 5) {
            throw ValidationException::withMessages([
                'motivoRetiro' => 'Escribe un motivo de al menos 5 caracteres.',
            ]);
        }

        DB::transaction(function () use ($inscripcion, $tutorId, $motivo, $usuarioId): void {
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($inscripcion->id);
            $relaciones = InscripcionTutor::query()
                ->where('inscripcion_id', $alumno->id)
                ->lockForUpdate()
                ->get();
            $relacion = $relaciones->firstWhere('tutor_id', $tutorId);

            if (! $relacion || ! $relacion->activo) {
                return;
            }

            $restantesActivos = $relaciones->filter(
                fn (InscripcionTutor $item): bool => $item->activo && $item->id !== $relacion->id
            );

            if ($restantesActivos->isEmpty() && $this->esMenor($alumno->fecha_nacimiento, $alumno->fecha_inscripcion)) {
                throw ValidationException::withMessages([
                    'responsables' => 'No puedes retirar el último responsable activo de un alumno menor de edad.',
                ]);
            }

            $eraPrincipal = $relacion->es_principal;
            $relacion->forceFill([
                'activo' => false,
                'es_principal' => false,
                'fecha_fin' => now()->toDateString(),
                'motivo_fin' => $motivo,
                'updated_by' => $usuarioId,
            ])->save();

            if ($eraPrincipal) {
                $nuevoPrincipal = $restantesActivos
                    ->sortBy('orden_contacto')
                    ->first();

                if ($nuevoPrincipal) {
                    $tutorNuevoPrincipal = Tutor::query()
                        ->lockForUpdate()
                        ->findOrFail($nuevoPrincipal->tutor_id);

                    if (! $tutorNuevoPrincipal->activo) {
                        throw ValidationException::withMessages([
                            'responsables' => 'No se puede retirar al principal porque el siguiente responsable está archivado.',
                        ]);
                    }

                    $this->validarMedioContactoPrincipal($tutorNuevoPrincipal);

                    $nuevoPrincipal->forceFill([
                        'es_principal' => true,
                        'updated_by' => $usuarioId,
                    ])->save();
                }
            }

            $this->sincronizarTutorLegado($alumno);

            app(SystemAuditService::class)->record('responsable_alumno_desactivado', 'alumnos', [
                'inscripcion_id' => $alumno->id,
                'tutor_id' => $tutorId,
                'motivo' => $motivo,
            ]);
        });
    }

    public function reactivar(Inscripcion $inscripcion, int $tutorId, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($inscripcion, $tutorId, $usuarioId): void {
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($inscripcion->id);
            $relacion = InscripcionTutor::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('tutor_id', $tutorId)
                ->lockForUpdate()
                ->firstOrFail();

            $tienePrincipal = InscripcionTutor::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('activo', true)
                ->where('es_principal', true)
                ->exists();

            $tutor = Tutor::query()->lockForUpdate()->findOrFail($relacion->tutor_id);

            if (! $tutor->activo) {
                throw ValidationException::withMessages([
                    'responsables' => 'Reactiva primero el registro general del responsable.',
                ]);
            }

            if (! $tienePrincipal) {
                $this->validarMedioContactoPrincipal($tutor);
            }

            $relacion->forceFill([
                'activo' => true,
                'es_principal' => ! $tienePrincipal,
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin' => null,
                'motivo_fin' => null,
                'updated_by' => $usuarioId,
            ])->save();

            $this->sincronizarTutorLegado($alumno);
        });
    }

    /** @param array<int, array<string, mixed>> $responsables */
    public function normalizarLista(array $responsables): array
    {
        $resultado = [];
        $vistos = [];
        $principalAsignado = false;

        foreach ($responsables as $datos) {
            $relacion = $this->normalizarRelacion($datos);
            $tutorId = (int) $relacion['tutor_id'];

            if ($tutorId <= 0 || isset($vistos[$tutorId])) {
                continue;
            }

            $vistos[$tutorId] = true;

            if ($relacion['es_principal']) {
                if ($principalAsignado) {
                    $relacion['es_principal'] = false;
                } else {
                    $principalAsignado = true;
                }
            }

            $resultado[] = $relacion;
        }

        if ($resultado !== [] && ! $principalAsignado) {
            $resultado[0]['es_principal'] = true;
        }

        return array_values($resultado);
    }

    /** @param array<string, mixed> $datos */
    public function normalizarRelacion(array $datos): array
    {
        $parentesco = mb_strtoupper(trim((string) Arr::get($datos, 'parentesco', 'OTRO')));
        $esTutorLegal = (bool) Arr::get($datos, 'es_tutor_legal', false);
        $estadoTutela = mb_strtolower(trim((string) Arr::get($datos, 'estado_tutela', 'no_aplica')));
        $estadoTutela = in_array($estadoTutela, self::ESTADOS_TUTELA, true) ? $estadoTutela : 'no_aplica';

        if (! $esTutorLegal) {
            $estadoTutela = 'no_aplica';
        } elseif ($estadoTutela === 'no_aplica') {
            $estadoTutela = 'pendiente';
        }

        return [
            'tutor_id' => (int) Arr::get($datos, 'tutor_id'),
            'parentesco' => in_array($parentesco, self::PARENTESCOS, true) ? $parentesco : 'OTRO',
            'es_principal' => (bool) Arr::get($datos, 'es_principal', false),
            'es_tutor_legal' => $esTutorLegal,
            'estado_tutela' => $estadoTutela,
            'vive_con_alumno' => (bool) Arr::get($datos, 'vive_con_alumno', false),
            'recibe_avisos' => (bool) Arr::get($datos, 'recibe_avisos', true),
            'recibe_calificaciones' => (bool) Arr::get($datos, 'recibe_calificaciones', true),
            'contacto_emergencia' => (bool) Arr::get($datos, 'contacto_emergencia', false),
            'autorizado_recoger' => (bool) Arr::get($datos, 'autorizado_recoger', false),
            'responsable_economico' => (bool) Arr::get($datos, 'responsable_economico', false),
            'fecha_inicio' => $this->fecha(Arr::get($datos, 'fecha_inicio')),
            'observaciones' => blank(Arr::get($datos, 'observaciones'))
                ? null
                : mb_substr(trim((string) Arr::get($datos, 'observaciones')), 0, 1000),
        ];
    }

    /** @param array<int, array<string, mixed>> $responsables */
    private function validarLista(Inscripcion $alumno, array $responsables, string|Carbon|null $fecha): void
    {
        $ids = array_column($responsables, 'tutor_id');
        $tutoresValidos = Tutor::query()->whereIn('id', $ids)->where('activo', true)->count();

        if ($tutoresValidos !== count($ids)) {
            throw ValidationException::withMessages([
                'responsables' => 'Uno de los responsables seleccionados no existe o está archivado.',
            ]);
        }

        if ($this->esMenor($alumno->fecha_nacimiento, $fecha ?: $alumno->fecha_inscripcion) && $responsables === []) {
            throw ValidationException::withMessages([
                'responsables' => 'Un alumno menor de edad debe tener al menos un responsable activo.',
            ]);
        }

        if ($responsables !== [] && count(array_filter($responsables, fn (array $item): bool => $item['es_principal'])) !== 1) {
            throw ValidationException::withMessages([
                'responsables' => 'Debe existir exactamente un contacto principal.',
            ]);
        }

        if ($responsables !== []) {
            $tutores = Tutor::query()->whereIn('id', $ids)->get()->keyBy('id');
            $principal = collect($responsables)->firstWhere('es_principal', true);
            $tutorPrincipal = $principal ? $tutores->get((int) $principal['tutor_id']) : null;

            if ($tutorPrincipal && blank($tutorPrincipal->telefono_celular) && blank($tutorPrincipal->telefono_casa) && blank($tutorPrincipal->correo_electronico)) {
                throw ValidationException::withMessages([
                    'responsables' => 'El contacto principal debe tener al menos teléfono o correo electrónico.',
                ]);
            }
        }
    }

    public function esMenor(mixed $fechaNacimiento, mixed $fechaReferencia = null): bool
    {
        if (blank($fechaNacimiento)) {
            return false;
        }

        try {
            $nacimiento = Carbon::parse($fechaNacimiento)->startOfDay();
            $referencia = blank($fechaReferencia)
                ? now()->startOfDay()
                : Carbon::parse($fechaReferencia)->startOfDay();

            return $nacimiento->greaterThan($referencia->copy()->subYears(18));
        } catch (\Throwable) {
            return false;
        }
    }

    private function sincronizarTutorLegado(Inscripcion $alumno): void
    {
        $principalId = InscripcionTutor::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('activo', true)
            ->orderByDesc('es_principal')
            ->orderBy('orden_contacto')
            ->value('tutor_id');

        $alumno->forceFill(['tutor_id' => $principalId])->saveQuietly();
    }

    private function validarMedioContactoPrincipal(Tutor $tutor): void
    {
        if (blank($tutor->telefono_celular) && blank($tutor->telefono_casa) && blank($tutor->correo_electronico)) {
            throw ValidationException::withMessages([
                'responsables' => 'El contacto principal debe tener al menos teléfono o correo electrónico.',
            ]);
        }
    }

    private function fecha(string|Carbon|null $fecha): ?string
    {
        if (blank($fecha)) {
            return null;
        }

        try {
            return Carbon::parse($fecha)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
