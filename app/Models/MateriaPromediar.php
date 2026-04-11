<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaPromediar extends Model
{
    protected $table = "materia_promediar";

    protected $fillable = [
        'nivel_id',
        'grado_id',
        'grupo_id',
        'semestre_id',
        'numero_materias',
    ];

    // Relaciones

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }


}
