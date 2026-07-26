<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProyeccionContinuidad extends Model
{
    protected $table = 'proyecciones_continuidad';

    protected $guarded = [];

    protected $casts = [
        'fecha_proyeccion' => 'date',
        'confirmada_at' => 'datetime',
        'cancelada_at' => 'datetime',
        'snapshot_origen' => 'array',
        'snapshot_confirmacion' => 'array',
        'snapshot_cancelacion' => 'array',
    ];

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', 'pendiente');
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class)->withTrashed();
    }

    public function inscripcionCicloOrigen()
    {
        return $this->belongsTo(InscripcionCiclo::class, 'inscripcion_ciclo_origen_id');
    }

    public function inscripcionCicloDestino()
    {
        return $this->belongsTo(InscripcionCiclo::class, 'inscripcion_ciclo_destino_id');
    }

    public function procesoCierre()
    {
        return $this->belongsTo(ProcesoCierreCiclo::class, 'proceso_cierre_ciclo_id');
    }

    public function detalleCierre()
    {
        return $this->belongsTo(ProcesoCierreCicloDetalle::class, 'proceso_cierre_ciclo_detalle_id');
    }

    public function cicloDestino()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_destino_id');
    }

    public function nivelDestino()
    {
        return $this->belongsTo(Nivel::class, 'nivel_destino_id');
    }

    public function generacionDestino()
    {
        return $this->belongsTo(Generacion::class, 'generacion_destino_id');
    }

    public function gradoDestino()
    {
        return $this->belongsTo(Grado::class, 'grado_destino_id');
    }

    public function semestreDestino()
    {
        return $this->belongsTo(Semestre::class, 'semestre_destino_id');
    }

    public function grupoDestino()
    {
        return $this->belongsTo(Grupo::class, 'grupo_destino_id');
    }

    public function usuarioProyecto()
    {
        return $this->belongsTo(User::class, 'proyectada_por');
    }

    public function usuarioConfirmo()
    {
        return $this->belongsTo(User::class, 'confirmada_por');
    }

    public function usuarioCancelo()
    {
        return $this->belongsTo(User::class, 'cancelada_por');
    }

    public function getEtiquetaEstadoAttribute(): string
    {
        return match ($this->estado) {
            'confirmada' => 'Continuidad confirmada',
            'cancelada' => 'No continuará',
            default => 'Pendiente de confirmar',
        };
    }
}
