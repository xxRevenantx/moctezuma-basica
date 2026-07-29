<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SeguimientoAcademicoCaso extends Model
{
    protected $table = 'seguimiento_academico_casos';

    protected $guarded = [];

    protected $casts = [
        'puntaje_inicial' => 'integer',
        'puntaje_actual' => 'integer',
        'proxima_revision_at' => 'date',
        'apertura_automatica' => 'boolean',
        'abierto_at' => 'datetime',
        'cerrado_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereIn('estado', ['abierto', 'en_seguimiento', 'pausado']);
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class)->withTrashed();
    }

    public function inscripcionCiclo()
    {
        return $this->belongsTo(InscripcionCiclo::class);
    }

    public function evaluacion()
    {
        return $this->belongsTo(RiesgoAcademicoEvaluacion::class, 'riesgo_evaluacion_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function planes()
    {
        return $this->hasMany(SeguimientoAcademicoPlan::class, 'seguimiento_caso_id')->latest('id');
    }

    public function acciones()
    {
        return $this->hasMany(SeguimientoAcademicoAccion::class, 'seguimiento_caso_id')->orderBy('fecha_limite')->orderBy('id');
    }

    public function eventos()
    {
        return $this->hasMany(SeguimientoAcademicoEvento::class, 'seguimiento_caso_id')->latest('ocurrido_at')->latest('id');
    }

    public function alertas()
    {
        return $this->hasMany(AlertaAcademica::class, 'seguimiento_caso_id')->latest('generada_at');
    }
}
