<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{

    protected $table = 'materias';

    protected $fillable = [
        'nivel_id',
        'grado_id',
        'semestre_id',
        'materia',
        'clave',
        'slug',
        'calificable',
        'extra',
        'receso',
        'orden',
    ];

    // RELACIONES
    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionMateria::class);
    }
}
