<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MateriaPromediar extends Model
{
    use HasFactory;

    protected $table = 'materia_promediar';

    protected $fillable = [
        'nivel_id',
        'grado_id',
        'semestre_id',
        'numero_materias',
    ];

    protected $casts = [
        'nivel_id' => 'integer',
        'grado_id' => 'integer',
        'semestre_id' => 'integer',
        'numero_materias' => 'integer',
    ];

    // Relación con nivel
    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    // Relación con grado
    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    // Relación con semestre
    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }
}
