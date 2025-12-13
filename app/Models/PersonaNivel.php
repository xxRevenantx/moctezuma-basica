<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonaNivel extends Model
{
     use HasFactory;


    protected $table = 'persona_nivel';

    protected $fillable = [
        'persona_id',
        'nivel_id',
        'role_persona_id',
        'activo',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    // Relaciones
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function rolePersona()
    {
        return $this->belongsTo(RolePersona::class, 'role_persona_id');
    }
}
