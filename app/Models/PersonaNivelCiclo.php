<?php

namespace App\Models;

use App\Observers\PersonaNivelCicloObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(PersonaNivelCicloObserver::class)]
class PersonaNivelCiclo extends Model
{
    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_BAJA = 'baja';

    protected $table = 'persona_nivel_ciclos';

    protected $fillable = [
        'plantilla_personal_nivel_id',
        'persona_nivel_id',
        'estado',
        'orden',
        'fecha_inicio',
        'fecha_fin',
        'fecha_baja',
        'motivo_baja',
        'copiado_desde_id',
    ];

    protected $casts = [
        'orden' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_baja' => 'date',
    ];

    public function plantilla()
    {
        return $this->belongsTo(PlantillaPersonalNivel::class, 'plantilla_personal_nivel_id');
    }

    public function personaNivel()
    {
        return $this->belongsTo(PersonaNivel::class, 'persona_nivel_id');
    }

    public function detalles()
    {
        return $this->hasMany(PersonaNivelDetalle::class, 'persona_nivel_ciclo_id');
    }

    public function origen()
    {
        return $this->belongsTo(self::class, 'copiado_desde_id');
    }
}
