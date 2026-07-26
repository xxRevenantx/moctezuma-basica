<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcesoCierreCicloDetalle extends Model
{
    protected $table = 'procesos_cierre_ciclo_detalles';

    protected $guarded = [];

    protected $casts = [
        'estado_anterior' => 'array',
        'estado_nuevo' => 'array',
        'destino_propuesto' => 'array',
        'revertido_at' => 'datetime',
    ];

    public function proceso() { return $this->belongsTo(ProcesoCierreCiclo::class, 'proceso_cierre_ciclo_id'); }
    public function inscripcion() { return $this->belongsTo(Inscripcion::class)->withTrashed(); }
    public function inscripcionCicloOrigen() { return $this->belongsTo(InscripcionCiclo::class, 'inscripcion_ciclo_origen_id'); }
    public function inscripcionCicloDestino() { return $this->belongsTo(InscripcionCiclo::class, 'inscripcion_ciclo_destino_id'); }
    public function proyeccionContinuidad() { return $this->hasOne(ProyeccionContinuidad::class, 'proceso_cierre_ciclo_detalle_id'); }
    public function usuarioRevirtio() { return $this->belongsTo(User::class, 'revertido_por'); }
}
