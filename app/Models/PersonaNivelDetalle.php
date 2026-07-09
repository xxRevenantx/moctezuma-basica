<?php

namespace App\Models;

use App\Observers\PersonaNivelDetalleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(PersonaNivelDetalleObserver::class)]
class PersonaNivelDetalle extends Model
{
    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_BAJA = 'baja';

    protected $table = 'persona_nivel_detalles';

    protected $fillable = [
        'persona_nivel_id',
        'persona_role_id',
        'grado_id',
        'grupo_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
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
        'orden',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_baja' => 'date',
        'es_titular' => 'boolean',
        'es_titular_principal' => 'boolean',
        'ajuste_horas_frente_grupo' => 'decimal:2',
        'horas_administrativas' => 'decimal:2',
        'limite_horas_semanales' => 'decimal:2',
        'orden' => 'integer',
    ];

    public function cabecera()
    {
        return $this->belongsTo(PersonaNivel::class, 'persona_nivel_id');
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
