<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReanudacionCcpPlantilla extends Model
{
    use SoftDeletes;

    protected $table = 'reanudacion_ccp_plantillas';

    protected $fillable = [
        'nombre',
        'contenido',
        'activo',
        'orden',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];
}
