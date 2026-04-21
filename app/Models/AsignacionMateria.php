<?php

namespace App\Models;

use App\Observers\AsignacionMateriaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AsignacionMateriaObserver::class)]
class AsignacionMateria extends Model
{
    /** @use HasFactory<\Database\Factories\AsignacionMateriaFactory> */
    use HasFactory;

    protected $fillable = [
        'nivel_id',
        'grado_id',
        'grupo_id',
        'semestre',
        'profesor_id',
        'materia',
        'clave',
        'slug',
        'calificable',
        'extra',
        'orden',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function profesor()
    {
        return $this->belongsTo(Persona::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre', 'id');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'asignacion_materia_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class, 'asignacion_materia_id');
    }
}
