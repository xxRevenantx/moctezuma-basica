<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HorarioRegla extends Model
{
    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'categoria', 'activa', 'peso',
        'parametros', 'orden', 'actualizado_por',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'peso' => 'integer',
        'parametros' => 'array',
        'orden' => 'integer',
    ];

    public function scopeActivas(Builder $query): Builder { return $query->where('activa', true); }
    public function usuarioActualizacion() { return $this->belongsTo(User::class, 'actualizado_por'); }
}
