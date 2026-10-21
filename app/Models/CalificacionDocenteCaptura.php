<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalificacionDocenteCaptura extends Model
{
    protected $table = 'calificaciones_docente_capturas';

    protected $fillable = ['asignacion_materia_id', 'periodo_id', 'estado', 'valores', 'actualizado_por', 'finalizado_at'];

    protected $casts = ['valores' => 'array', 'finalizado_at' => 'datetime'];
}
