<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InscripcionCicloAsignacion extends Model
{
    protected $table = 'inscripcion_ciclo_asignaciones';

    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'es_actual' => 'boolean',
        'snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (InscripcionCicloAsignacion $asignacion): void {
            if (! $asignacion->es_actual || ! $asignacion->inscripcion_ciclo_id) {
                return;
            }

            $historial = InscripcionCiclo::query()->find($asignacion->inscripcion_ciclo_id);
            if ($historial?->estaCerrado()) {
                $asignacion->es_actual = false;
                $asignacion->fecha_fin ??= $historial->fecha_salida;

                return;
            }

            static::query()
                ->where('inscripcion_ciclo_id', $asignacion->inscripcion_ciclo_id)
                ->when($asignacion->exists, fn (Builder $query) => $query->where('id', '!=', $asignacion->getKey()))
                ->where('es_actual', true)
                ->update([
                    'es_actual' => false,
                    'fecha_fin' => $asignacion->fecha_inicio,
                    'updated_at' => now(),
                ]);

            $asignacion->fecha_fin = null;
        });
    }

    public function scopeActuales(Builder $query): Builder
    {
        return $query->where('es_actual', true);
    }

    public function inscripcionCiclo()
    {
        return $this->belongsTo(InscripcionCiclo::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
