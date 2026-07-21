<?php

namespace App\Models;

use App\Models\Concerns\LinksInscripcionCiclo;

use Illuminate\Database\Eloquent\Model;

class DecisionPromocionOficial extends Model
{
    use LinksInscripcionCiclo;

    protected $table = 'decisiones_promocion_oficial';

    protected $fillable = [
        'inscripcion_id',
        'inscripcion_ciclo_id',
        'ciclo_escolar_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'generacion_id',
        'promedio_final',
        'promocion_sugerida',
        'promocion_confirmada',
        'motivo',
        'confirmada_por',
        'confirmada_at',
    ];

    protected $casts = [
        'promedio_final' => 'decimal:2',
        'promocion_sugerida' => 'boolean',
        'promocion_confirmada' => 'boolean',
        'confirmada_at' => 'datetime',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id')->withTrashed();
    }
}
