<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Bitácora de textos, importaciones y transiciones de estado, con autor. */
class FichaHistorial extends Model
{
    protected $table = 'ficha_historiales';

    protected $fillable = [
        'inscripcion_id', 'ciclo_escolar_id', 'periodo', 'campo',
        'accion', 'valor_anterior', 'valor_nuevo', 'user_id',
    ];

    protected $casts = ['periodo' => 'integer'];
}
