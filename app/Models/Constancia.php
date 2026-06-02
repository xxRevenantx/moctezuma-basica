<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Constancia extends Model
{
    protected $fillable = [
        'inscripcion_id',
        'constancia_plantilla_id',
        'folio',
        'fecha_expedicion',
        'dirigido_a',
        'modo_descarga',
        'periodos_calificaciones',
        'contenido_generado_html',
    ];

    protected $casts = [
        'fecha_expedicion' => 'date',
        'periodos_calificaciones' => 'array',
    ];

    public function alumno()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function plantilla()
    {
        return $this->belongsTo(ConstanciaPlantilla::class, 'constancia_plantilla_id');
    }
}
