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
        'documento_alumno_id',
        'tipo',
        'fecha',
        'motivo',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function trayectoriaAcademica()
    {
        return $this->belongsTo(TrayectoriaAcademica::class, 'trayectoria_academica_id');
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
