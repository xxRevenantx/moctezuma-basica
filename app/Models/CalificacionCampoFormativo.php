<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalificacionCampoFormativo extends Model
{
    protected $table = 'calificaciones_campos_formativos';

    protected $fillable = [
        'inscripcion_id',
        'trayectoria_academica_id',
        'campo_formativo_id',
        'periodo_id',
        'ciclo_escolar_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'generacion_id',
        'calificacion_sugerida',
        'calificacion_oficial',
        'confirmada',
        'es_reconstruida',
        'observaciones',
        'confirmada_por',
        'confirmada_at',
    ];

    protected $casts = [
        'calificacion_sugerida' => 'decimal:2',
        'calificacion_oficial' => 'integer',
        'confirmada' => 'boolean',
        'es_reconstruida' => 'boolean',
        'confirmada_at' => 'datetime',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id')->withTrashed();
    }

    public function trayectoriaAcademica()
    {
        return $this->belongsTo(TrayectoriaAcademica::class, 'trayectoria_academica_id');
    }

    public function campoFormativo()
    {
        return $this->belongsTo(CampoFormativo::class, 'campo_formativo_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodos::class, 'periodo_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function confirmador()
    {
        return $this->belongsTo(User::class, 'confirmada_por');
    }
}
