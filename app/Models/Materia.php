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
        'campo_formativo_id',
        'materia',
        'clave',
        'slug',
        'calificable',
        'extra',
        'receso',
        'participa_en_calificacion_oficial',
        'orden',
    ];

    protected $casts = [
        'calificable' => 'boolean',
        'extra' => 'boolean',
        'receso' => 'boolean',
        'participa_en_calificacion_oficial' => 'boolean',
        'orden' => 'integer',
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

    public function campoFormativo()
    {
        return $this->belongsTo(CampoFormativo::class, 'campo_formativo_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionMateria::class, 'materia_id');
    }
}
