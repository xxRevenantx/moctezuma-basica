<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcesoCierreCiclo extends Model
{
    protected $table = 'procesos_cierre_ciclo';

    protected $guarded = [];

    protected $casts = [
        'fecha_egreso' => 'date',
        'fecha_efectiva' => 'date',
        'resumen' => 'array',
        'estado_anterior_generacion' => 'array',
        'generacion_cerrada' => 'boolean',
        'ciclo_cerrado' => 'boolean',
        'realizado_at' => 'datetime',
        'confirmacion_at' => 'datetime',
        'revertido_at' => 'datetime',
    ];

    public function detalles() { return $this->hasMany(ProcesoCierreCicloDetalle::class); }
    public function proyeccionesContinuidad() { return $this->hasMany(ProyeccionContinuidad::class, 'proceso_cierre_ciclo_id'); }
    public function generacion() { return $this->belongsTo(Generacion::class); }
    public function nivel() { return $this->belongsTo(Nivel::class); }
    public function grupoOrigen() { return $this->belongsTo(Grupo::class, 'grupo_origen_id'); }
    public function cicloEscolar() { return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id'); }
    public function cicloDestino() { return $this->belongsTo(CicloEscolar::class, 'ciclo_destino_id'); }
    public function usuarioRealizo() { return $this->belongsTo(User::class, 'realizado_por'); }
    public function usuarioRevirtio() { return $this->belongsTo(User::class, 'revertido_por'); }

    public function getPuedeRevertirseAttribute(): bool
    {
        return $this->estado === 'completado' && ! $this->revertido_at;
    }
}
