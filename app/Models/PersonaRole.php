<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaRole extends Model
{
    protected $table = 'persona_role';

    protected $fillable = [
        'persona_id',
        'role_persona_id',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

     public function rolePersona()
    {
        return $this->belongsTo(\App\Models\RolePersona::class, 'role_persona_id');
    }

}
