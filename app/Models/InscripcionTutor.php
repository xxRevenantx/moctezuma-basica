<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

class InscripcionTutor extends Pivot
{
    protected $table = 'inscripcion_tutor';

    public $incrementing = true;

    protected $fillable = [
        'inscripcion_id',
        'tutor_id',
        'parentesco',
        'es_principal',
        'orden_contacto',
        'es_tutor_legal',
        'estado_tutela',
        'vive_con_alumno',
        'recibe_avisos',
        'recibe_calificaciones',
        'contacto_emergencia',
        'autorizado_recoger',
        'responsable_economico',
        'activo',
        'fecha_inicio',
        'fecha_fin',
        'motivo_fin',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'es_tutor_legal' => 'boolean',
        'vive_con_alumno' => 'boolean',
        'recibe_avisos' => 'boolean',
        'recibe_calificaciones' => 'boolean',
        'contacto_emergencia' => 'boolean',
        'autorizado_recoger' => 'boolean',
        'responsable_economico' => 'boolean',
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->whereNull('fecha_fin');
    }

    public function scopePrincipales(Builder $query): Builder
    {
        return $query->activas()->where('es_principal', true);
    }

    public function scopeContactosEmergencia(Builder $query): Builder
    {
        return $query->activas()->where('contacto_emergencia', true);
    }

    public function scopeAutorizadosRecoger(Builder $query): Builder
    {
        return $query->activas()->where('autorizado_recoger', true);
    }

    public function scopeTutoresLegales(Builder $query): Builder
    {
        return $query->activas()->where('es_tutor_legal', true);
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
