<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalificacionEntregaDetalle extends Model
{
    protected $fillable = [
        'calificacion_entrega_id',
        'calificacion_id',
        'inscripcion_id',
        'asignacion_materia_id',
        'matricula',
        'alumno_nombre',
        'materia_nombre',
        'calificacion',
        'observacion',
        'es_numerica',
        'valor_numerico',
    ];

    protected $casts = [
        'es_numerica' => 'boolean',
        'valor_numerico' => 'decimal:2',
    ];

    public function entrega()
    {
        return $this->belongsTo(CalificacionEntrega::class, 'calificacion_entrega_id');
    }
}
