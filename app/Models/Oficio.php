<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Oficio extends Model
{
    protected $fillable = [
        'inscripcion_id',
        'nivel_id',
        'director_id',
        'folio',
        'tipo_oficio',
        'seccion',
        'fecha_lugar',
        'asunto',
        'dirigido_1_nombre',
        'dirigido_1_cargo',
        'dirigido_1_lugar',
        'dirigido_2_nombre',
        'dirigido_2_cargo',
        'dirigido_2_lugar',
        'periodos_calificaciones',
        'descripcion_html',
    ];

    protected $casts = [
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

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function director()
    {
        return $this->belongsTo(Director::class);
    }
}
