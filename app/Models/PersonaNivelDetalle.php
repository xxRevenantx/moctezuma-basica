<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaNivelDetalle extends Model
{
    protected $table = 'persona_nivel_detalles';

    protected $fillable = [
        'persona_nivel_id',
        'persona_role_id',
        'grado_id',
        'grupo_id',
        'orden',
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
}
