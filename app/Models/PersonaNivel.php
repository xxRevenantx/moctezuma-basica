<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaNivel extends Model
{
    protected $table = 'persona_nivel';

    protected $fillable = [
        'persona_id',
        'nivel_id',
        'ingreso_seg',
        'ingreso_sep',
        'ingreso_ct',
        'orden',
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
}
