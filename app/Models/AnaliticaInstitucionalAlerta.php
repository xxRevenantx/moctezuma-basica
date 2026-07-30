<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AnaliticaInstitucionalAlerta extends Model
{
    protected $table = 'analitica_institucional_alertas';

    protected $guarded = [];

    protected $casts = [
        'evidencia' => 'array',
        'detectada_at' => 'datetime',
        'resuelta_at' => 'datetime',
    ];

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', 'activa');
    }

    public function snapshot() { return $this->belongsTo(AnaliticaInstitucionalSnapshot::class, 'snapshot_id'); }
    public function cicloEscolar() { return $this->belongsTo(CicloEscolar::class); }
    public function nivel() { return $this->belongsTo(Nivel::class); }
    public function resueltaPor() { return $this->belongsTo(User::class, 'resuelta_por'); }
}
