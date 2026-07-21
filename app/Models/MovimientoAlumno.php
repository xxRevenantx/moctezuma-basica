<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoAlumno extends Model
{
    use HasFactory;

    protected $table = 'movimientos_alumnos';

    protected $fillable = [
        'inscripcion_id',
        'inscripcion_ciclo_id',
        'ciclo_escolar_id',
        'ciclo_id',
        'nivel_anterior_id',
        'nivel_nuevo_id',
        'resultado_continuidad',
        'usuario_acceso_activo',
        'documento_alumno_id',
        'tipo',
        'fecha',
        'motivo',
        'observaciones',
        'estado_anterior',
        'estado_nuevo',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'estado_anterior' => 'array',
        'estado_nuevo' => 'array',
        'usuario_acceso_activo' => 'boolean',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id')->withTrashed();
    }

    public function inscripcionCiclo()
    {
        return $this->belongsTo(InscripcionCiclo::class, 'inscripcion_ciclo_id');
    }

    public function nivelAnterior()
    {
        return $this->belongsTo(Nivel::class, 'nivel_anterior_id');
    }

    public function nivelNuevo()
    {
        return $this->belongsTo(Nivel::class, 'nivel_nuevo_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
    }

    public function documentoAlumno()
    {
        return $this->belongsTo(DocumentoAlumno::class, 'documento_alumno_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
