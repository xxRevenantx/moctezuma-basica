<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CambioAcademico extends Model
{
    protected $table = 'cambios_academicos';

    protected $fillable = [
        'inscripcion_id', 'inscripcion_ciclo_id', 'generacion_id', 'tipo', 'motivo',
        'datos_anteriores', 'datos_nuevos', 'realizado_por', 'realizado_at',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'realizado_at' => 'datetime',
    ];

    public function inscripcion() { return $this->belongsTo(Inscripcion::class)->withTrashed(); }
    public function inscripcionCiclo() { return $this->belongsTo(InscripcionCiclo::class, 'inscripcion_ciclo_id'); }
    public function generacion() { return $this->belongsTo(Generacion::class); }
    public function usuario() { return $this->belongsTo(User::class, 'realizado_por'); }
}
