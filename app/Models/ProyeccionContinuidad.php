<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProyeccionContinuidad extends Model
{
    protected $table = 'proyecciones_continuidad';

    protected $guarded = [];

    protected $casts = [
        'semestre_destino_clave' => 'integer',
        'fecha_proyeccion' => 'date',
        'confirmada_at' => 'datetime',
        'cancelada_at' => 'datetime',
        'revertida_at' => 'datetime',
        'fecha_reversion' => 'date',
        'snapshot_origen' => 'array',
        'snapshot_confirmacion' => 'array',
        'snapshot_cancelacion' => 'array',
        'snapshot_reversion' => 'array',
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

    public function usuarioRevirtio()
    {
        return $this->belongsTo(User::class, 'revertida_por');
    }

    public function getEtiquetaEstadoAttribute(): string
    {
        return match ($this->estado) {
            'confirmada' => 'Proyección confirmada',
            'cancelada' => 'No continuará',
            'revertida' => 'Retirado del ciclo destino',
            default => 'Pendiente de confirmar',
        };
    }

    public function getEtiquetaTipoAttribute(): string
    {
        return match ($this->tipo_proyeccion) {
            'siguiente_grado' => 'Siguiente grado o semestre',
            'repeticion' => 'Repetición de grado o semestre',
            'siguiente_nivel' => 'Siguiente nivel educativo',
            default => 'Continuidad académica',
        };
    }

    public function getEtiquetaResultadoOrigenAttribute(): string
    {
        return match ($this->resultado_origen) {
            'promovido_grado' => 'Grado o semestre promovido',
            'no_promovido' => 'No promovido',
            'egresado' => 'Egresado del nivel',
            'promovido_nivel' => 'Promovido de nivel',
            'promovido' => 'Promovido',
            default => ucfirst(str_replace('_', ' ', (string) $this->resultado_origen)),
        };
    }
}
