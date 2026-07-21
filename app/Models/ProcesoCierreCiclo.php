<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcesoCierreCiclo extends Model
{
    protected $table = 'procesos_cierre_ciclo';
    protected $guarded = [];
    protected $casts = ['fecha_egreso' => 'date', 'fecha_efectiva' => 'date', 'resumen' => 'array', 'generacion_cerrada' => 'boolean', 'ciclo_cerrado' => 'boolean', 'realizado_at' => 'datetime', 'revertido_at' => 'datetime'];

    public function detalles() { return $this->hasMany(ProcesoCierreCicloDetalle::class); }
    public function generacion() { return $this->belongsTo(Generacion::class); }
    public function cicloEscolar() { return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id'); }
    public function cicloDestino() { return $this->belongsTo(CicloEscolar::class, 'ciclo_destino_id'); }
}
