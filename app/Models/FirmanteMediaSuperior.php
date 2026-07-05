<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FirmanteMediaSuperior extends Model
{
    public const ROL_DIRECTOR = 'director_plantel';
    public const ROL_CONTROL_ESCOLAR = 'control_escolar';
    public const ROL_JEFE_REGISTRO = 'jefe_registro_certificacion';

    protected $table = 'firmantes_media_superior';

    protected $fillable = [
        'nivel_id',
        'rol',
        'director_id',
        'persona_id',
        'cargo_impresion',
        'ciclo_desde_id',
        'ciclo_hasta_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function director()
    {
        return $this->belongsTo(Director::class);
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function cicloDesde()
    {
        return $this->belongsTo(cicloEscolar::class, 'ciclo_desde_id');
    }

    public function cicloHasta()
    {
        return $this->belongsTo(cicloEscolar::class, 'ciclo_hasta_id');
    }

    public function scopeVigentePara(Builder $query, int $cicloId): Builder
    {
        $ciclo = cicloEscolar::query()->find($cicloId, ['id', 'inicio_anio']);

        if (! $ciclo) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('activo', true)
            ->where(function (Builder $q) use ($ciclo): void {
                $q->whereNull('ciclo_desde_id')
                    ->orWhereHas('cicloDesde', fn (Builder $desde) => $desde
                        ->where('inicio_anio', '<=', $ciclo->inicio_anio));
            })
            ->where(function (Builder $q) use ($ciclo): void {
                $q->whereNull('ciclo_hasta_id')
                    ->orWhereHas('cicloHasta', fn (Builder $hasta) => $hasta
                        ->where('inicio_anio', '>=', $ciclo->inicio_anio));
            });
    }

    public function nombreCompleto(): string
    {
        $origen = $this->director ?: $this->persona;

        if (! $origen) {
            return 'SIN CONFIGURAR';
        }

        return trim(implode(' ', array_filter([
            $origen->titulo ?? null,
            $origen->nombre ?? null,
            $origen->apellido_paterno ?? null,
            $origen->apellido_materno ?? null,
        ])));
    }
}
