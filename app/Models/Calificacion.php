<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{

    protected $table = 'calificaciones';

    protected $fillable = [
        'inscripcion_id',
        'asignacion_materia_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'ciclo_escolar_id',
        'generacion_id',
        'semestre_id',
        'periodo_id',
        'calificacion',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function asignacionMateria()
    {
        return $this->belongsTo(AsignacionMateria::class);
    }

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

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class);
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    public function periodo()
    {
        return $this->belongsTo(Periodos::class, 'periodo_id');
    }
}
