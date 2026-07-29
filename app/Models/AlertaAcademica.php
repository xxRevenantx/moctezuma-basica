<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AlertaAcademica extends Model
{
    protected $table = 'alertas_academicas';
    protected $guarded = [];
    protected $casts = [
        'fecha_limite' => 'date', 'generada_at' => 'datetime', 'leida_at' => 'datetime',
        'atendida_at' => 'datetime', 'metadata' => 'array',
    ];

    public function scopePendientes(Builder $query): Builder { return $query->where('estado', 'pendiente'); }
    public function inscripcion() { return $this->belongsTo(Inscripcion::class)->withTrashed(); }
    public function evaluacion() { return $this->belongsTo(RiesgoAcademicoEvaluacion::class, 'riesgo_evaluacion_id'); }
    public function caso() { return $this->belongsTo(SeguimientoAcademicoCaso::class, 'seguimiento_caso_id'); }
    public function destinatario() { return $this->belongsTo(User::class, 'destinatario_id'); }
}
