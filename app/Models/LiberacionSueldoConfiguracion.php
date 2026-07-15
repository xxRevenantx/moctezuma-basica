<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiberacionSueldoConfiguracion extends Model
{
    protected $table = 'liberacion_sueldo_configuraciones';

    protected $fillable = [
        'logo_encabezado_path',
        'franja_inferior_path',
        'franja_ancho_mm',
        'franja_alto_mm',
        'franja_inferior_mm',
        'actualizado_por',
    ];

    protected $casts = [
        'franja_ancho_mm' => 'float',
        'franja_alto_mm' => 'float',
        'franja_inferior_mm' => 'float',
    ];
}
