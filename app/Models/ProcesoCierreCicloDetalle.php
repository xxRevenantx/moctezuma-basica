<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcesoCierreCicloDetalle extends Model
{
    protected $table = 'procesos_cierre_ciclo_detalles';
    protected $guarded = [];
    protected $casts = ['estado_anterior' => 'array', 'estado_nuevo' => 'array'];
    public function proceso() { return $this->belongsTo(ProcesoCierreCiclo::class, 'proceso_cierre_ciclo_id'); }
    public function inscripcion() { return $this->belongsTo(Inscripcion::class); }
}
