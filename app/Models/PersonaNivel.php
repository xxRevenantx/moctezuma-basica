<?php

namespace App\Models;

use App\Observers\PersonaNivelObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(PersonaNivelObserver::class)]
class PersonaNivel extends Model
{
    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_BAJA = 'baja';

    protected $table = 'persona_nivel';

    protected $fillable = [
        'persona_id',
        'nivel_id',
        'ingreso_seg',
        'ingreso_sep',
        'ingreso_ct',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'horas_administrativas',
        'limite_horas_semanales',
        'actividad_administrativa',
        'observaciones',
        'fecha_baja',
        'motivo_baja',
        'orden',
    ];

    protected $casts = [
        'ingreso_seg' => 'date',
        'ingreso_sep' => 'date',
        'ingreso_ct' => 'date',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_baja' => 'date',
        'horas_administrativas' => 'decimal:2',
        'limite_horas_semanales' => 'decimal:2',
        'orden' => 'integer',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function detalles()
    {
        return $this->hasMany(PersonaNivelDetalle::class, 'persona_nivel_id');
    }

    public function ciclos()
    {
        return $this->hasMany(PersonaNivelCiclo::class, 'persona_nivel_id');
    }

    public function ciclo(int $cicloEscolarId): ?PersonaNivelCiclo
    {
        return $this->ciclos()
            ->whereHas('plantilla', fn ($q) => $q->where('ciclo_escolar_id', $cicloEscolarId))
            ->first();
    }

    public function historial()
    {
        return $this->hasMany(PersonaNivelHistorial::class, 'persona_nivel_id');
    }

    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO
            && (!$this->fecha_fin || $this->fecha_fin->isFuture() || $this->fecha_fin->isToday());
    }
}
