<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Control de revisión/finalización independiente para cada alumno, ciclo y evaluación. */
class FichaEstado extends Model
{
    protected $table = 'ficha_estados';

    protected $fillable = [
        'inscripcion_id', 'ciclo_escolar_id', 'periodo', 'estado',
        'revisado_por', 'revisado_at',
    ];

    protected $casts = ['periodo' => 'integer', 'revisado_at' => 'datetime'];
}
