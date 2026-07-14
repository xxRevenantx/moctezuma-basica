<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiberacionSueldoConfiguracion extends Model
{
    protected $table = 'liberacion_sueldo_configuraciones';

    protected $fillable = [
        'logo_encabezado_path',
        'actualizado_por',
    ];
}
