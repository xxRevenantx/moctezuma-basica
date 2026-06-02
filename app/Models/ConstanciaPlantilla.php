<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaPlantilla extends Model
{
    protected $table = 'constancia_plantillas';

    protected $fillable = [
        'clave',
        'titulo',
        'contenido_html',
        'variables',
        'activo',
    ];

    protected $casts = [
        'variables' => 'array',
        'activo' => 'boolean',
    ];

    public function constancias()
    {
        return $this->hasMany(Constancia::class, 'constancia_plantilla_id');
    }
}
