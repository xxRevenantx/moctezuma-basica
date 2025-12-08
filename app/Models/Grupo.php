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

}
