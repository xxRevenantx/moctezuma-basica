<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RiesgoAcademicoRegla extends Model
{
    protected $table = 'riesgo_academico_reglas';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
        'peso' => 'decimal:2',
        'max_puntos' => 'decimal:2',
        'parametros' => 'array',
        'aplica_niveles' => 'array',
    ];

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('orden')->orderBy('id');
    }

    public function aplicaANivel(?string $slug, ?int $nivelId): bool
    {
        if ($this->nivel_id !== null && (int) $this->nivel_id !== (int) $nivelId) {
            return false;
        }

        $niveles = collect($this->aplica_niveles ?? [])->filter();

        return $niveles->isEmpty() || ($slug !== null && $niveles->contains($slug));
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }
}
