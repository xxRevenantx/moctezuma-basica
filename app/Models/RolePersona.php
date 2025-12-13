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
        'estatus',
    ];

    public function rolesPersona()
{
    return $this->belongsToMany(\App\Models\RolePersona::class, 'persona_role', 'persona_id', 'role_persona_id')
        ->withTimestamps();
}
public function personaNiveles()
{
    return $this->hasMany(\App\Models\PersonaNivel::class, 'role_persona_id');
}

}
