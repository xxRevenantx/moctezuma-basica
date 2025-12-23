<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    /** @use HasFactory<\Database\Factories\GrupoFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'nivel_id',
        'grado_id',
        'generacion_id',
        'semestre_id',
    ];

    // RELACIONES
    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }
    public function grado()
    {
        return $this->belongsTo( Grado::class);
    }
    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }
    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    // RELACION CON PERSONA_NIVEL
    public function personaNiveles()
    {
        return $this->hasMany(PersonaNivel::class);
    }

public function docentesGrupo()
{
    return $this->hasMany(\App\Models\DocenteGrupo::class, 'grupo_id');
}

public function docentes()
{
    return $this->belongsToMany(\App\Models\Persona::class, 'docente_grupo', 'grupo_id', 'persona_id')
        ->withPivot(['ciclo_escolar_id', 'es_tutor'])
        ->withTimestamps();
}

// Docentes por ciclo escolar
public function docentesPorCiclo(int $cicloEscolarId)
{
    return $this->docentes()->wherePivot('ciclo_escolar_id', $cicloEscolarId);
}

// Tutor por ciclo escolar
public function tutorPorCiclo(int $cicloEscolarId)
{
    return $this->docentes()
        ->wherePivot('ciclo_escolar_id', $cicloEscolarId)
        ->wherePivot('es_tutor', true);
}



}
