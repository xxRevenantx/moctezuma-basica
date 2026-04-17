<?php

namespace App\Models;

use App\Observers\GradoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


#[ObservedBy(GradoObserver::class)]
class Grado extends Model
{
    /** @use HasFactory<\Database\Factories\GradoFactory> */
    use HasFactory;

    protected $fillable = [
        'nivel_id',
        'nombre',
        'slug',
        'orden'
    ];

    // RELACION CON NIVEL
    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    // RELACION CON SEMESTRES
    public function semestres()
    {
        return $this->hasMany(Semestre::class);
    }


    // RELACION CON GRUPOS
    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }

    // RELACION CON PERSONA_NIVEL
    public function personaNiveles()
    {
        return $this->hasMany(PersonaNivel::class);
    }

    // RELACION CON INSCRIPCIONES
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }

    // RELACION CON MATERIAS A PROMEDIAR
    public function materiasPromediar()
    {
        return $this->hasMany(MateriaPromediar::class, 'grado_id');
    }

    // RELACION CON ASIGNACIONES DE MATERIAS
    public function asignacionesMaterias()
    {
        return $this->hasMany(AsignacionMateria::class, 'grado_id');
    }

    // RELACION CON HORARIOS
    public function horarios()
    {
        return $this->hasMany(Horario::class, 'grado_id');
    }
}
