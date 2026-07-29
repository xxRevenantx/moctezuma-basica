<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulacionCierreCiclo extends Model
{
    protected $table = 'simulaciones_cierre_ciclo';

    protected $guarded = [];

    protected $casts = [
        'contenido' => 'array',
        'resumen' => 'array',
        'generado_at' => 'datetime',
        'expira_at' => 'datetime',
        'consumida_at' => 'datetime',
        'cancelada_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function cicloOrigen()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_origen_id');
    }

    public function cicloDestino()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_destino_id');
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }

    public function grupoOrigen()
    {
        return $this->belongsTo(Grupo::class, 'grupo_origen_id');
    }

    public function proceso()
    {
        return $this->hasOne(ProcesoCierreCiclo::class, 'simulacion_cierre_ciclo_id');
    }
}
