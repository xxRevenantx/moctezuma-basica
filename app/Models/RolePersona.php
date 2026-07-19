<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePersona extends Model
{
    protected $table = 'role_personas';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function personas()
    {
        return $this->belongsToMany(
            Persona::class,
            'persona_role',
            'role_persona_id',
            'persona_id'
        )->withTimestamps();
    }

    public function personaNiveles()
    {
        return $this->hasMany(PersonaNivel::class, 'role_persona_id');
    }
}
