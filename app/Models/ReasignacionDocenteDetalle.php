<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReasignacionDocenteDetalle extends Model
{
    public const RESULTADO_APLICADA = 'aplicada';
    public const RESULTADO_REVERTIDA = 'revertida';
    public const RESULTADO_OMITIDA = 'omitida';

    protected $table = 'reasignacion_docente_detalles';

    protected $fillable = [
        'reasignacion_docente_lote_id',
        'asignacion_materia_id',
        'profesor_anterior_id',
        'profesor_nuevo_id',
        'grupo_id',
        'materia_id',
        'estado_asignacion',
        'resultado',
        'motivo_omision',
        'contexto_snapshot',
        'horarios_snapshot',
        'versiones_snapshot',
        'aplicado_at',
        'revertido_at',
    ];

    protected $casts = [
        'contexto_snapshot' => 'array',
        'horarios_snapshot' => 'array',
        'versiones_snapshot' => 'array',
        'aplicado_at' => 'datetime',
        'revertido_at' => 'datetime',
    ];

    public function lote()
    {
        return $this->belongsTo(ReasignacionDocenteLote::class, 'reasignacion_docente_lote_id');
    }

    public function asignacionMateria()
    {
        return $this->belongsTo(AsignacionMateria::class, 'asignacion_materia_id');
    }

    public function profesorAnterior()
    {
        return $this->belongsTo(Persona::class, 'profesor_anterior_id');
    }

    public function profesorNuevo()
    {
        return $this->belongsTo(Persona::class, 'profesor_nuevo_id');
    }
}
