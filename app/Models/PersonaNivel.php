<?php

namespace App\Models;

use App\Observers\PersonaNivelObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(PersonaNivelObserver::class)]
class PersonaNivel extends Model
{
     use HasFactory;


    protected $table = 'persona_nivel';

    protected $fillable = [
        'persona_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'ingreso_seg',
        'ingreso_sep',
        'orden',
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

    // GRADOS
    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    // GRUPOS
    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function rolePersona()
    {
        return $this->belongsTo(RolePersona::class, 'role_persona_id');
    }
}
