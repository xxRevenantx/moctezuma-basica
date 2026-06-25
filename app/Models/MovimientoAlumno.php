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
        'trayectoria_academica_id',
        'ciclo_escolar_id',
        'ciclo_id',
        'trayectoria_origen_id',
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
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id')->withTrashed();
    }

    public function trayectoriaAcademica()
    {
        return $this->belongsTo(TrayectoriaAcademica::class, 'trayectoria_academica_id');
    }

    public function trayectoriaOrigen()
    {
        return $this->belongsTo(TrayectoriaAcademica::class, 'trayectoria_origen_id');
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
