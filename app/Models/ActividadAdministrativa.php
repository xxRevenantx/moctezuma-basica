<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActividadAdministrativa extends Model
{
    protected $table = 'actividades_administrativas';

    protected $fillable = [
        'nombre',
        'horas_sugeridas',
        'activo',
        'orden',
    ];

    protected $casts = [
        'horas_sugeridas' => 'decimal:2',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function detallesPersonaNivel()
    {
        return $this->hasMany(PersonaNivelDetalle::class, 'actividad_administrativa_id');
    }
}
