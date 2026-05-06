<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraCalificacion extends Model
{
    protected $table = 'bitacora_calificaciones';

    protected $fillable = [
        'nivel_id',
        'grado_id',
        'grupo_id',
        'semestre_id',
        'generacion_id',
        'periodo_id',
        'inscripcion_id',
        'asignacion_materia_id',
        'user_id',
        'accion',
        'calificacion_anterior',
        'calificacion_nueva',
        'valor_anterior_numerico',
        'valor_nuevo_numerico',
        'tipo_valor',
        'observacion',
        'motivo',
        'ip',
    ];

    protected $casts = [
        'valor_anterior_numerico' => 'decimal:2',
        'valor_nuevo_numerico' => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function asignacionMateria()
    {
        return $this->belongsTo(AsignacionMateria::class, 'asignacion_materia_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class, 'generacion_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodos::class, 'periodo_id');
    }
}
