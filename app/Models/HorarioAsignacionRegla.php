<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioAsignacionRegla extends Model
{
    protected $fillable = [
        'asignacion_materia_id', 'sesiones_semanales', 'max_sesiones_dia',
        'permitir_bloques_consecutivos', 'max_bloques_consecutivos',
        'dias_minimos', 'preferencia_horaria', 'permitir_multigrado',
        'bloqueada', 'actualizado_por',
    ];

    protected $casts = [
        'sesiones_semanales' => 'integer',
        'max_sesiones_dia' => 'integer',
        'permitir_bloques_consecutivos' => 'boolean',
        'max_bloques_consecutivos' => 'integer',
        'dias_minimos' => 'integer',
        'permitir_multigrado' => 'boolean',
        'bloqueada' => 'boolean',
    ];

    public function asignacionMateria() { return $this->belongsTo(AsignacionMateria::class); }
    public function usuarioActualizacion() { return $this->belongsTo(User::class, 'actualizado_por'); }
}
