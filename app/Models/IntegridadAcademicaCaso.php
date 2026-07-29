<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IntegridadAcademicaCaso extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_REVISION = 'en_revision';
    public const ESTADO_RESUELTO = 'resuelto';
    public const ESTADO_IGNORADO = 'ignorado';

    protected $table = 'integridad_academica_casos';
    protected $guarded = [];

    protected $casts = [
        'evidencia' => 'array',
        'correccion_sugerida' => 'array',
        'metadata' => 'array',
        'primera_deteccion_at' => 'datetime',
        'ultima_deteccion_at' => 'datetime',
        'revision_iniciada_at' => 'datetime',
        'resuelto_at' => 'datetime',
        'ignorado_at' => 'datetime',
    ];

    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_REVISION]);
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class)->withTrashed();
    }

    public function historial()
    {
        return $this->belongsTo(InscripcionCiclo::class, 'inscripcion_ciclo_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function asignado()
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function ultimoAnalisis()
    {
        return $this->belongsTo(IntegridadAcademicaAnalisis::class, 'ultimo_analisis_id');
    }

    public function eventos()
    {
        return $this->hasMany(IntegridadAcademicaEvento::class, 'caso_id')->latest('created_at');
    }

    public function correcciones()
    {
        return $this->hasMany(IntegridadAcademicaCorreccion::class, 'caso_id')->latest();
    }

    public function getNombreAlumnoAttribute(): string
    {
        if (! $this->inscripcion) {
            return 'Caso general del sistema';
        }

        return trim(implode(' ', array_filter([
            $this->inscripcion->nombre,
            $this->inscripcion->apellido_paterno,
            $this->inscripcion->apellido_materno,
        ]))) ?: 'Alumno sin nombre';
    }

    public function getEtiquetaSeveridadAttribute(): string
    {
        return match ($this->severidad) {
            'critico' => 'Crítico',
            'advertencia' => 'Advertencia',
            default => 'Informativo',
        };
    }

    public function getEtiquetaEstadoAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_REVISION => 'En revisión',
            self::ESTADO_RESUELTO => 'Resuelto',
            self::ESTADO_IGNORADO => 'Ignorado justificadamente',
            default => 'Pendiente',
        };
    }

    public function getTieneCorreccionSugeridaAttribute(): bool
    {
        return filled(data_get($this->correccion_sugerida, 'clave'));
    }
}
