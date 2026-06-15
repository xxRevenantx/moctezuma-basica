<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LugarPreescolar extends Model
{
    protected $table = 'lugares_preescolar';

    protected $fillable = [
        'inscripcion_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'generacion_id',
        'ciclo_escolar_id',
        'tipo_reconocimiento',
        'periodo',
        'lugar',
        'texto_lugar',
        'motivo',
        'asignado_por',
        'fecha_asignacion',
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'periodo' => 'integer',
        'lugar' => 'integer',
    ];

    public function alumno()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
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

    public function cicloEscolar()
    {
        return $this->belongsTo(cicloEscolar::class, 'ciclo_escolar_id');
    }
}
