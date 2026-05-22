<?php

namespace App\Models;

use App\Observers\MateriaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;


#[ObservedBy(MateriaObserver::class)]
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



    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionMateria::class, 'materia_id');
    }
}
