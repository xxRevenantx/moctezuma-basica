<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Horario;
use App\Models\HorarioVersion;
use App\Models\HorarioVersionDetalle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HorarioVersionService
{
    public function __construct(private readonly HorarioConflictoService $conflictos)
    {
    }

    public function crearBorradorDesdeHorarioActual(
        int $cicloEscolarId,
        int $nivelId,
        ?int $usuarioId,
        ?string $nombre = null
    ): HorarioVersion {
        return DB::transaction(function () use ($cicloEscolarId, $nivelId, $usuarioId, $nombre): HorarioVersion {
            $version = HorarioVersion::query()->create([
                'uuid' => (string) Str::uuid(),
                'ciclo_escolar_id' => $cicloEscolarId,
                'nivel_id' => $nivelId,
                'numero' => $this->siguienteNumero($cicloEscolarId, $nivelId),
                'nombre' => $nombre ?: 'Borrador desde horario actual',
                'estado' => 'borrador',
                'objetivo' => 'manual',
                'creado_por' => $usuarioId,
                'motivo' => 'Borrador creado sin alterar la versión publicada.',
            ]);

            Horario::query()
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $nivelId)
                ->with(['asignacionMateria', 'tallerSesion'])
                ->orderBy('id')
                ->chunk(300, function ($horarios) use ($version): void {
                    $filas = [];
                    $ahora = now();
                    foreach ($horarios as $horario) {
                        $filas[] = $this->detalleDesdeHorario($horario, $version->id, $ahora);
                    }
                    if ($filas !== []) {
                        HorarioVersionDetalle::query()->insert($filas);
                    }
                });

            $this->evento($version, 'creacion', 'Borrador creado desde el horario actual', null, $usuarioId);
            return $this->actualizarDiagnostico($version);
        });
    }

    public function copiarVersion(
        HorarioVersion $origen,
        string $estado,
        string $objetivo,
        ?int $usuarioId,
        ?string $nombre = null
    ): HorarioVersion {
        return DB::transaction(function () use ($origen, $estado, $objetivo, $usuarioId, $nombre): HorarioVersion {
            $origen->loadMissing('detalles');
            $version = HorarioVersion::query()->create([
                'uuid' => (string) Str::uuid(),
                'ciclo_escolar_id' => $origen->ciclo_escolar_id,
                'nivel_id' => $origen->nivel_id,
                'generacion_id' => $origen->generacion_id,
                'version_origen_id' => $origen->id,
                'numero' => $this->siguienteNumero($origen->ciclo_escolar_id, $origen->nivel_id),
                'nombre' => $nombre ?: 'Copia de '.$origen->nombre,
                'estado' => $estado,
                'objetivo' => $objetivo,
                'creado_por' => $usuarioId,
                'motivo' => 'Versión derivada de '.$origen->nombre.'.',
            ]);

            $ahora = now();
            $filas = $origen->detalles->map(function (HorarioVersionDetalle $detalle) use ($version, $ahora): array {
                $datos = $detalle->only([
                    'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'grupo_id',
                    'hora_id', 'dia_id', 'asignacion_materia_id', 'taller_sesion_id',
                    'profesor_id', 'sesion_compartida', 'clave_sesion_compartida',
                    'motivo_sesion_compartida', 'traslape_excepcional',
                    'motivo_traslape_excepcional', 'motivo_autorizacion_disponibilidad',
                    'coensenanza', 'bloqueado', 'origen',
                ]);
                return array_merge($datos, [
                    'horario_version_id' => $version->id,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            })->all();

            foreach (array_chunk($filas, 500) as $chunk) {
                HorarioVersionDetalle::query()->insert($chunk);
            }

            $this->evento($version, 'copia', 'Versión creada a partir de otra versión', 'Origen: '.$origen->nombre, $usuarioId, ['version_origen_id' => $origen->id]);
            return $this->actualizarDiagnostico($version);
        });
    }

    /**
     * @param array<int,array<string,mixed>> $detalles
     */
    public function crearPropuesta(
        int $cicloEscolarId,
        int $nivelId,
        string $objetivo,
        array $detalles,
        array $metadata,
        ?int $usuarioId
    ): HorarioVersion {
        return DB::transaction(function () use ($cicloEscolarId, $nivelId, $objetivo, $detalles, $metadata, $usuarioId): HorarioVersion {
            $etiqueta = match ($objetivo) {
                'compactar_docente' => 'Menos huecos docentes',
                'distribucion_alumnos' => 'Mejor distribución para alumnos',
                'preferencias' => 'Mayor respeto a preferencias',
                default => 'Equilibrio general',
            };

            $version = HorarioVersion::query()->create([
                'uuid' => (string) Str::uuid(),
                'ciclo_escolar_id' => $cicloEscolarId,
                'nivel_id' => $nivelId,
                'numero' => $this->siguienteNumero($cicloEscolarId, $nivelId),
                'nombre' => 'Propuesta · '.$etiqueta,
                'estado' => 'propuesta',
                'objetivo' => $objetivo,
                'creado_por' => $usuarioId,
                'metricas' => $metadata,
                'motivo' => 'Generada automáticamente por el optimizador de horarios.',
            ]);

            $ahora = now();
            $filas = [];
            foreach ($detalles as $detalle) {
                $filas[] = array_merge([
                    'horario_version_id' => $version->id,
                    'semestre_id' => null,
                    'taller_sesion_id' => null,
                    'profesor_id' => null,
                    'sesion_compartida' => false,
                    'clave_sesion_compartida' => null,
                    'motivo_sesion_compartida' => null,
                    'traslape_excepcional' => false,
                    'motivo_traslape_excepcional' => null,
                    'motivo_autorizacion_disponibilidad' => null,
                    'coensenanza' => false,
                    'bloqueado' => false,
                    'origen' => 'generado',
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ], collect($detalle)->only([
                    'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'grupo_id',
                    'hora_id', 'dia_id', 'asignacion_materia_id', 'taller_sesion_id',
                    'profesor_id', 'sesion_compartida', 'clave_sesion_compartida',
                    'motivo_sesion_compartida', 'traslape_excepcional',
                    'motivo_traslape_excepcional', 'motivo_autorizacion_disponibilidad',
                    'coensenanza', 'bloqueado', 'origen',
                ])->all());
            }

            foreach (array_chunk($filas, 500) as $chunk) {
                HorarioVersionDetalle::query()->insert($chunk);
            }

            $this->evento($version, 'generacion', 'Propuesta automática generada', null, $usuarioId, $metadata);
            return $this->actualizarDiagnostico($version);
        });
    }

    public function convertirPropuestaEnBorrador(HorarioVersion $version, ?int $usuarioId): HorarioVersion
    {
        abort_unless(in_array($version->estado, ['propuesta', 'borrador'], true), 422);
        $version->update([
            'estado' => 'borrador',
            'revisado_por' => $usuarioId,
        ]);
        $this->evento($version, 'estado', 'Propuesta convertida en borrador editable', null, $usuarioId);
        return $version->refresh();
    }

    public function moverDetalle(HorarioVersionDetalle $detalle, int $diaId, int $horaId, ?int $usuarioId): HorarioVersion
    {
        $version = $detalle->version()->firstOrFail();
        $this->asegurarEditable($version);
        if ($detalle->bloqueado) {
            throw new \DomainException('El bloque está fijado. Desbloquéalo antes de moverlo.');
        }

        $antes = ['dia_id' => $detalle->dia_id, 'hora_id' => $detalle->hora_id];
        $detalle->update(['dia_id' => $diaId, 'hora_id' => $horaId, 'origen' => 'manual']);
        $this->evento($version, 'movimiento', 'Clase movida en el editor visual', null, $usuarioId, [
            'detalle_id' => $detalle->id,
            'antes' => $antes,
            'despues' => ['dia_id' => $diaId, 'hora_id' => $horaId],
        ]);
        return $this->actualizarDiagnostico($version);
    }

    public function intercambiarDetalles(HorarioVersionDetalle $primero, HorarioVersionDetalle $segundo, ?int $usuarioId): HorarioVersion
    {
        if ((int) $primero->horario_version_id !== (int) $segundo->horario_version_id) {
            throw new \DomainException('Las clases deben pertenecer a la misma versión.');
        }
        $version = $primero->version()->firstOrFail();
        $this->asegurarEditable($version);
        if ($primero->bloqueado || $segundo->bloqueado) {
            throw new \DomainException('No se pueden intercambiar bloques fijados.');
        }

        DB::transaction(function () use ($primero, $segundo): void {
            $a = [$primero->dia_id, $primero->hora_id];
            $b = [$segundo->dia_id, $segundo->hora_id];
            $primero->update(['dia_id' => $b[0], 'hora_id' => $b[1], 'origen' => 'manual']);
            $segundo->update(['dia_id' => $a[0], 'hora_id' => $a[1], 'origen' => 'manual']);
        });

        $this->evento($version, 'intercambio', 'Dos clases fueron intercambiadas', null, $usuarioId, [
            'detalle_a' => $primero->id,
            'detalle_b' => $segundo->id,
        ]);
        return $this->actualizarDiagnostico($version);
    }

    public function alternarBloqueo(HorarioVersionDetalle $detalle, ?int $usuarioId): HorarioVersion
    {
        $version = $detalle->version()->firstOrFail();
        $this->asegurarEditable($version);
        $detalle->update(['bloqueado' => ! $detalle->bloqueado]);
        $this->evento($version, 'bloqueo', $detalle->bloqueado ? 'Bloque fijado' : 'Bloque liberado', null, $usuarioId, ['detalle_id' => $detalle->id]);
        return $version->refresh();
    }

    public function alternarBloqueoDia(HorarioVersion $version, int $diaId, bool $bloquear, ?int $usuarioId): HorarioVersion
    {
        $this->asegurarEditable($version);
        $afectados = $version->detalles()->where('dia_id', $diaId)->update([
            'bloqueado' => $bloquear,
            'updated_at' => now(),
        ]);
        $this->evento(
            $version,
            'bloqueo_dia',
            $bloquear ? 'Día completo fijado' : 'Día completo liberado',
            null,
            $usuarioId,
            ['dia_id' => $diaId, 'bloques_afectados' => $afectados]
        );
        return $version->refresh();
    }

    public function cambiarProfesorDetalle(
        HorarioVersionDetalle $detalle,
        ?int $profesorId,
        string $motivo,
        ?int $usuarioId
    ): HorarioVersion {
        $version = $detalle->version()->firstOrFail();
        $this->asegurarEditable($version);
        $anterior = $detalle->profesor_id;
        $detalle->update([
            'profesor_id' => $profesorId,
            'origen' => 'manual',
        ]);
        $this->evento($version, 'cambio_docente', 'Docente del bloque actualizado', $motivo, $usuarioId, [
            'detalle_id' => $detalle->id,
            'profesor_anterior_id' => $anterior,
            'profesor_nuevo_id' => $profesorId,
        ]);
        return $this->actualizarDiagnostico($version);
    }

    public function agregarCoensenanza(
        HorarioVersionDetalle $detalle,
        int $profesorId,
        string $motivo,
        ?int $usuarioId
    ): HorarioVersion {
        $version = $detalle->version()->firstOrFail();
        $this->asegurarEditable($version);
        if ((int) $detalle->profesor_id === $profesorId) {
            throw new \DomainException('Selecciona un docente de apoyo diferente.');
        }

        $yaExiste = $version->detalles()
            ->where('grupo_id', $detalle->grupo_id)
            ->where('dia_id', $detalle->dia_id)
            ->where('hora_id', $detalle->hora_id)
            ->where('profesor_id', $profesorId)
            ->exists();
        if ($yaExiste) {
            throw new \DomainException('Ese docente ya participa en el mismo bloque.');
        }

        $clave = $detalle->clave_sesion_compartida ?: 'coensenanza-'.Str::uuid();
        DB::transaction(function () use ($detalle, $profesorId, $motivo, $clave): void {
            $detalle->update([
                'coensenanza' => true,
                'sesion_compartida' => true,
                'clave_sesion_compartida' => $clave,
                'motivo_sesion_compartida' => $motivo,
            ]);

            $copia = $detalle->replicate(['id', 'created_at', 'updated_at']);
            $copia->profesor_id = $profesorId;
            $copia->coensenanza = true;
            $copia->sesion_compartida = true;
            $copia->clave_sesion_compartida = $clave;
            $copia->motivo_sesion_compartida = $motivo;
            $copia->origen = 'manual';
            $copia->bloqueado = false;
            $copia->save();
        });

        $this->evento($version, 'coensenanza', 'Docente de apoyo agregado', $motivo, $usuarioId, [
            'detalle_id' => $detalle->id,
            'profesor_apoyo_id' => $profesorId,
        ]);
        return $this->actualizarDiagnostico($version);
    }

    public function clasificarSimultaneidad(
        HorarioVersion $version,
        int $profesorId,
        int $diaId,
        int $horaId,
        string $tipo,
        string $motivo,
        ?int $usuarioId
    ): HorarioVersion {
        $this->asegurarEditable($version);
        $detalles = $version->detalles()
            ->where('profesor_id', $profesorId)
            ->where('dia_id', $diaId)
            ->where('hora_id', $horaId)
            ->get();

        if ($detalles->pluck('grupo_id')->unique()->count() < 2) {
            throw new \DomainException('No existe simultaneidad de grupos en ese bloque.');
        }

        $clave = 'compartida-'.Str::uuid();
        foreach ($detalles as $detalle) {
            $detalle->update([
                'sesion_compartida' => $tipo === 'compartida',
                'clave_sesion_compartida' => $tipo === 'compartida' ? $clave : null,
                'motivo_sesion_compartida' => $tipo === 'compartida' ? $motivo : null,
                'traslape_excepcional' => $tipo === 'excepcional',
                'motivo_traslape_excepcional' => $tipo === 'excepcional' ? $motivo : null,
            ]);
        }

        $this->evento($version, 'clasificacion', 'Simultaneidad docente clasificada', $motivo, $usuarioId, [
            'tipo' => $tipo,
            'profesor_id' => $profesorId,
            'dia_id' => $diaId,
            'hora_id' => $horaId,
            'detalles' => $detalles->pluck('id')->all(),
        ]);
        return $this->actualizarDiagnostico($version);
    }

    public function actualizarDiagnostico(HorarioVersion $version): HorarioVersion
    {
        $version->unsetRelation('detalles');
        $resultado = $this->conflictos->analizar($version);
        $version->update([
            'puntaje' => $resultado['puntaje'],
            'metricas' => array_merge($version->metricas ?? [], $resultado['metricas']),
            'conflictos' => [
                'criticos' => $resultado['criticos'],
                'advertencias' => $resultado['advertencias'],
                'informativos' => $resultado['informativos'],
            ],
            'hash_integridad' => $this->hash($version),
        ]);
        return $version->refresh();
    }

    public function solicitarRevision(HorarioVersion $version, ?int $usuarioId): HorarioVersion
    {
        $this->asegurarEditable($version);
        $version = $this->actualizarDiagnostico($version);
        $version->update(['estado' => 'en_revision', 'revisado_por' => $usuarioId]);
        $this->evento($version, 'revision', 'Versión enviada a revisión', null, $usuarioId);
        return $version->refresh();
    }

    public function programarPublicacion(
        HorarioVersion $version,
        Carbon $fecha,
        string $motivo,
        ?int $usuarioId,
        bool $aceptarAdvertencias = false
    ): HorarioVersion {
        $version = $this->validarParaPublicar($version, $aceptarAdvertencias);
        if ($fecha->lte(now())) {
            return $this->publicar($version, $motivo, $usuarioId, $aceptarAdvertencias, $fecha);
        }
        $version->update([
            'estado' => 'programada',
            'publicar_at' => $fecha,
            'vigencia_desde' => $fecha,
            'motivo' => $motivo,
            'revisado_por' => $usuarioId,
        ]);
        $this->evento($version, 'programacion', 'Publicación programada', $motivo, $usuarioId, ['publicar_at' => $fecha->toIso8601String()]);
        return $version->refresh();
    }

    public function publicar(
        HorarioVersion $version,
        string $motivo,
        ?int $usuarioId,
        bool $aceptarAdvertencias = false,
        ?Carbon $vigencia = null
    ): HorarioVersion {
        $version = $this->validarParaPublicar($version, $aceptarAdvertencias);

        return DB::transaction(function () use ($version, $motivo, $usuarioId, $vigencia): HorarioVersion {
            $version = HorarioVersion::query()->lockForUpdate()->findOrFail($version->id);
            $version->load('detalles');

            HorarioVersion::query()
                ->delContexto($version->ciclo_escolar_id, $version->nivel_id)
                ->where('estado', 'publicada')
                ->whereKeyNot($version->id)
                ->update(['estado' => 'sustituida', 'updated_at' => now()]);

            Horario::query()
                ->where('ciclo_escolar_id', $version->ciclo_escolar_id)
                ->where('nivel_id', $version->nivel_id)
                ->delete();

            $ahora = now();
            $filas = $version->detalles->map(fn (HorarioVersionDetalle $detalle) => [
                'nivel_id' => $detalle->nivel_id,
                'grado_id' => $detalle->grado_id,
                'generacion_id' => $detalle->generacion_id,
                'semestre_id' => $detalle->semestre_id,
                'grupo_id' => $detalle->grupo_id,
                'hora_id' => $detalle->hora_id,
                'dia_id' => $detalle->dia_id,
                'asignacion_materia_id' => $detalle->asignacion_materia_id,
                'profesor_id' => $detalle->profesor_id,
                'taller_sesion_id' => $detalle->taller_sesion_id,
                'ciclo_escolar_id' => $version->ciclo_escolar_id,
                'horario_version_id' => $version->id,
                'sesion_compartida' => $detalle->sesion_compartida,
                'clave_sesion_compartida' => $detalle->clave_sesion_compartida,
                'motivo_sesion_compartida' => $detalle->motivo_sesion_compartida,
                'traslape_excepcional' => $detalle->traslape_excepcional,
                'motivo_traslape_excepcional' => $detalle->motivo_traslape_excepcional,
                'motivo_autorizacion_disponibilidad' => $detalle->motivo_autorizacion_disponibilidad,
                'coensenanza' => $detalle->coensenanza,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->all();

            foreach (array_chunk($filas, 500) as $chunk) {
                Horario::query()->insert($chunk);
            }

            $version->update([
                'estado' => 'publicada',
                'motivo' => $motivo,
                'publicar_at' => null,
                'vigencia_desde' => $vigencia ?? $ahora,
                'publicado_at' => $ahora,
                'publicado_por' => $usuarioId,
                'hash_integridad' => $this->hash($version),
            ]);

            $this->evento($version, 'publicacion', 'Horario publicado', $motivo, $usuarioId, [
                'bloques' => count($filas),
                'vigencia_desde' => ($vigencia ?? $ahora)->toIso8601String(),
            ]);

            return $version->refresh();
        });
    }

    public function publicarProgramadas(): int
    {
        $publicadas = 0;
        HorarioVersion::query()
            ->where('estado', 'programada')
            ->whereNotNull('publicar_at')
            ->where('publicar_at', '<=', now())
            ->orderBy('publicar_at')
            ->each(function (HorarioVersion $version) use (&$publicadas): void {
                try {
                    $this->publicar($version, $version->motivo ?: 'Publicación programada.', $version->revisado_por, true, $version->vigencia_desde);
                    $publicadas++;
                } catch (\Throwable $exception) {
                    report($exception);
                    $this->evento($version, 'error_publicacion', 'La publicación programada fue bloqueada', $exception->getMessage(), null);
                }
            });
        return $publicadas;
    }

    public function crearRestauracion(HorarioVersion $versionAnterior, ?int $usuarioId): HorarioVersion
    {
        return $this->copiarVersion(
            $versionAnterior,
            'borrador',
            'restauracion',
            $usuarioId,
            'Restauración propuesta de '.$versionAnterior->nombre
        );
    }

    private function validarParaPublicar(HorarioVersion $version, bool $aceptarAdvertencias): HorarioVersion
    {
        $version = $this->actualizarDiagnostico($version);
        $conflictos = $version->conflictos ?? [];
        $criticos = collect($conflictos['criticos'] ?? []);
        $advertencias = collect($conflictos['advertencias'] ?? []);

        if ($criticos->isNotEmpty()) {
            throw new \DomainException('No se puede publicar: existen '.$criticos->count().' conflictos críticos.');
        }
        if ($advertencias->isNotEmpty() && ! $aceptarAdvertencias) {
            throw new \DomainException('La versión tiene advertencias. Confírmalas expresamente antes de publicar.');
        }
        if ($version->detalles()->count() === 0) {
            throw new \DomainException('No se puede publicar una versión sin bloques.');
        }
        return $version;
    }

    private function asegurarEditable(HorarioVersion $version): void
    {
        if (! in_array($version->estado, ['propuesta', 'borrador'], true)) {
            throw new \DomainException('Solo las propuestas y borradores pueden editarse. Crea una nueva versión para modificar un horario publicado.');
        }
    }

    private function siguienteNumero(int $cicloEscolarId, int $nivelId): int
    {
        return ((int) HorarioVersion::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->lockForUpdate()
            ->max('numero')) + 1;
    }

    private function detalleDesdeHorario(Horario $horario, int $versionId, $ahora): array
    {
        return [
            'horario_version_id' => $versionId,
            'nivel_id' => $horario->nivel_id,
            'grado_id' => $horario->grado_id,
            'generacion_id' => $horario->generacion_id,
            'semestre_id' => $horario->semestre_id,
            'grupo_id' => $horario->grupo_id,
            'hora_id' => $horario->hora_id,
            'dia_id' => $horario->dia_id,
            'asignacion_materia_id' => $horario->asignacion_materia_id,
            'taller_sesion_id' => $horario->taller_sesion_id,
            'profesor_id' => $horario->profesor_id ?? $horario->asignacionMateria?->profesor_id ?? $horario->tallerSesion?->profesor_id,
            'sesion_compartida' => $horario->sesion_compartida,
            'clave_sesion_compartida' => $horario->clave_sesion_compartida,
            'motivo_sesion_compartida' => $horario->motivo_sesion_compartida,
            'traslape_excepcional' => (bool) ($horario->traslape_excepcional ?? false),
            'motivo_traslape_excepcional' => $horario->motivo_traslape_excepcional ?? null,
            'motivo_autorizacion_disponibilidad' => $horario->motivo_autorizacion_disponibilidad ?? null,
            'coensenanza' => (bool) ($horario->coensenanza ?? false),
            'bloqueado' => true,
            'origen' => 'actual',
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];
    }

    private function evento(
        HorarioVersion $version,
        string $tipo,
        string $titulo,
        ?string $descripcion,
        ?int $usuarioId,
        array $metadata = []
    ): void {
        $version->eventos()->create([
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'metadata' => $metadata === [] ? null : $metadata,
            'usuario_id' => $usuarioId,
            'ocurrido_at' => now(),
        ]);
    }

    private function hash(HorarioVersion $version): string
    {
        $version->loadMissing('detalles');
        $datos = $version->detalles
            ->sortBy(fn (HorarioVersionDetalle $detalle) => implode('-', [
                $detalle->grupo_id, $detalle->dia_id, $detalle->hora_id,
                $detalle->asignacion_materia_id, $detalle->profesor_id, $detalle->id,
            ]))
            ->map(fn (HorarioVersionDetalle $detalle) => $detalle->only([
                'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'grupo_id',
                'hora_id', 'dia_id', 'asignacion_materia_id', 'taller_sesion_id',
                'profesor_id', 'sesion_compartida', 'clave_sesion_compartida',
                'traslape_excepcional', 'coensenanza', 'bloqueado',
            ]))
            ->values()
            ->all();

        $clave = (string) config('app.key');
        return hash_hmac('sha256', json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $clave);
    }
}
