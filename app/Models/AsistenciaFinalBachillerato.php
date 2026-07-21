<?php

namespace App\Models;

use App\Models\Concerns\LinksInscripcionCiclo;

use Illuminate\Database\Eloquent\Model;

class AsistenciaFinalBachillerato extends Model
{
    use LinksInscripcionCiclo;

    protected $table = 'asistencias_finales_bachillerato';

    protected $fillable = [
        'inscripcion_id',
        'inscripcion_ciclo_id',
        'asignacion_materia_id',
        'ciclo_escolar_id',
        'porcentaje',
        'capturado_por',
        'capturado_at',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'capturado_at' => 'datetime',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class)->withTrashed();
    }

    public function asignacionMateria()
    {
        return $this->belongsTo(AsignacionMateria::class);
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class);
    }

    public function capturador()
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }
}
