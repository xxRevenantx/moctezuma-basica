<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RiesgoAcademicoEvaluacion extends Model
{
    protected $table = 'riesgo_academico_evaluaciones';

    protected $guarded = [];

    protected $casts = [
        'puntaje' => 'integer',
        'factores' => 'array',
        'metricas' => 'array',
        'reglas_aplicadas' => 'array',
        'es_actual' => 'boolean',
        'evaluado_at' => 'datetime',
    ];

    public function scopeActuales(Builder $query): Builder
    {
        return $query->where('es_actual', true);
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class)->withTrashed();
    }

    public function inscripcionCiclo()
    {
        return $this->belongsTo(InscripcionCiclo::class);
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    public function evaluador()
    {
        return $this->belongsTo(User::class, 'evaluado_por');
    }

    public function casos()
    {
        return $this->hasMany(SeguimientoAcademicoCaso::class, 'riesgo_evaluacion_id');
    }

    public function getEtiquetaRiesgoAttribute(): string
    {
        return match ($this->nivel_riesgo) {
            'moderado' => 'Moderado',
            'alto' => 'Alto',
            'critico' => 'Crítico',
            default => 'Bajo',
        };
    }
}
