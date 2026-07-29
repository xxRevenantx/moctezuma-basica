<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HorarioVersion extends Model
{
    protected $table = 'horario_versiones';

    public const ESTADOS = ['propuesta', 'borrador', 'en_revision', 'programada', 'publicada', 'sustituida', 'archivada'];

    protected $fillable = [
        'uuid', 'ciclo_escolar_id', 'nivel_id', 'generacion_id', 'version_origen_id',
        'numero', 'nombre', 'estado', 'objetivo', 'puntaje', 'metricas',
        'conflictos', 'hash_integridad', 'motivo', 'observaciones',
        'vigencia_desde', 'publicar_at', 'publicado_at', 'creado_por',
        'revisado_por', 'publicado_por',
    ];

    protected $casts = [
        'numero' => 'integer',
        'puntaje' => 'decimal:2',
        'metricas' => 'array',
        'conflictos' => 'array',
        'vigencia_desde' => 'datetime',
        'publicar_at' => 'datetime',
        'publicado_at' => 'datetime',
    ];

    public function cicloEscolar() { return $this->belongsTo(CicloEscolar::class); }
    public function nivel() { return $this->belongsTo(Nivel::class); }
    public function generacion() { return $this->belongsTo(Generacion::class); }
    public function versionOrigen() { return $this->belongsTo(self::class, 'version_origen_id'); }
    public function derivadas() { return $this->hasMany(self::class, 'version_origen_id'); }
    public function detalles() { return $this->hasMany(HorarioVersionDetalle::class); }
    public function eventos() { return $this->hasMany(HorarioVersionEvento::class)->orderByDesc('ocurrido_at')->orderByDesc('id'); }
    public function creador() { return $this->belongsTo(User::class, 'creado_por'); }
    public function revisor() { return $this->belongsTo(User::class, 'revisado_por'); }
    public function publicador() { return $this->belongsTo(User::class, 'publicado_por'); }

    public function scopeDelContexto(Builder $query, int $cicloId, int $nivelId): Builder
    {
        return $query->where('ciclo_escolar_id', $cicloId)->where('nivel_id', $nivelId);
    }

    public function scopePublicadas(Builder $query): Builder { return $query->where('estado', 'publicada'); }

    public function getEtiquetaEstadoAttribute(): string
    {
        return match ($this->estado) {
            'en_revision' => 'En revisión',
            'programada' => 'Programada',
            'publicada' => 'Publicada',
            'sustituida' => 'Sustituida',
            'archivada' => 'Archivada',
            'propuesta' => 'Propuesta',
            default => 'Borrador',
        };
    }
}
