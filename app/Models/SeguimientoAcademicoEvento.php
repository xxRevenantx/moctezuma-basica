<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoAcademicoEvento extends Model
{
    protected $table = 'seguimiento_academico_eventos';
    protected $guarded = [];
    protected $casts = ['datos_anteriores' => 'array', 'datos_nuevos' => 'array', 'ocurrido_at' => 'datetime'];

    public function caso() { return $this->belongsTo(SeguimientoAcademicoCaso::class, 'seguimiento_caso_id'); }
    public function evaluacion() { return $this->belongsTo(RiesgoAcademicoEvaluacion::class, 'riesgo_evaluacion_id'); }
    public function usuario() { return $this->belongsTo(User::class, 'registrado_por'); }
}
