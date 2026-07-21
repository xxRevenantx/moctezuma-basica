<?php

namespace App\Models;

use App\Observers\PersonaNivelDetalleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(PersonaNivelDetalleObserver::class)]
class PersonaNivelDetalle extends Model
{
    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_BAJA = 'baja';

    protected $table = 'persona_nivel_detalles';

    protected $fillable = [
        'persona_nivel_id',
        'persona_nivel_ciclo_id',
        'persona_role_id',
        'grado_id',
        'grupo_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'confirmado',
        'pendiente_motivo',
        'es_titular',
        'es_titular_principal',
        'asignacion_materia_id',
        'materia_manual',
        'ajuste_horas_frente_grupo',
        'horas_administrativas',
        'actividad_administrativa_id',
        'actividad_administrativa_manual',
        'limite_horas_semanales',
        'observaciones',
        'fecha_baja',
        'motivo_baja',
        'archivado_at',
        'archivado_por',
        'motivo_archivo',
        'orden',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_baja' => 'date',
        'es_titular' => 'boolean',
        'confirmado' => 'boolean',
        'archivado_at' => 'datetime',
        'es_titular_principal' => 'boolean',
        'ajuste_horas_frente_grupo' => 'decimal:2',
        'horas_administrativas' => 'decimal:2',
        'limite_horas_semanales' => 'decimal:2',
        'orden' => 'integer',
    ];


    public function scopeVigenteEnCiclo(Builder $query, int $cicloEscolarId, bool $soloPublicada = true): Builder
    {
        $cicloActual = (bool) CicloEscolar::query()->whereKey($cicloEscolarId)->value('es_actual');
        $estados = $cicloActual ? [self::ESTADO_ACTIVO] : [self::ESTADO_ACTIVO, self::ESTADO_BAJA];

        return $query
            ->whereNull('archivado_at')
            ->whereIn('estado', $estados)
            ->where('confirmado', true)
            ->whereHas('cicloAsignacion', function (Builder $membresia) use ($cicloEscolarId, $soloPublicada, $estados) {
                $membresia
                    ->whereIn('estado', $estados)
                    ->whereHas('plantilla', function (Builder $plantilla) use ($cicloEscolarId, $soloPublicada) {
                        $plantilla->where('ciclo_escolar_id', $cicloEscolarId);

                        if ($soloPublicada) {
                            $plantilla->whereIn('estado', ['publicada', 'cerrada']);
                        }
                    });
            });
    }

    public function cabecera()
    {
        return $this->belongsTo(PersonaNivel::class, 'persona_nivel_id');
    }

    public function cicloAsignacion()
    {
        return $this->belongsTo(PersonaNivelCiclo::class, 'persona_nivel_ciclo_id');
    }

    public function usuarioArchivo()
    {
        return $this->belongsTo(User::class, 'archivado_por');
    }

    public function personaRole()
    {
        return $this->belongsTo(PersonaRole::class, 'persona_role_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function asignacionMateria()
    {
        return $this->belongsTo(AsignacionMateria::class, 'asignacion_materia_id');
    }

    public function actividadAdministrativa()
    {
        return $this->belongsTo(ActividadAdministrativa::class, 'actividad_administrativa_id');
    }

    public function historial()
    {
        return $this->hasMany(PersonaNivelHistorial::class, 'persona_nivel_detalle_id');
    }

    public function nombreMateria(): ?string
    {
        return $this->asignacionMateria?->materia?->materia
            ?? $this->asignacionMateria?->materia?->nombre
            ?? $this->materia_manual;
    }

    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO
            && (!$this->fecha_fin || $this->fecha_fin->isFuture() || $this->fecha_fin->isToday());
    }
}
