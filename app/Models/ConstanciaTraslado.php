<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaTraslado extends Model
{
    protected $table = 'constancias_traslado';

    protected $fillable = [
        'inscripcion_id',
        'trayectoria_academica_id',
        'ciclo_escolar_id',
        'folio',
        'fecha_emision',
        'modalidad',
        'periodos_incluidos',
        'observaciones',
        'ruta_pdf',
        'documento_alumno_id',
        'emitida_por',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'periodos_incluidos' => 'array',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id')->withTrashed();
    }

    public function trayectoriaAcademica()
    {
        return $this->belongsTo(TrayectoriaAcademica::class, 'trayectoria_academica_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function documentoAlumno()
    {
        return $this->belongsTo(DocumentoAlumno::class, 'documento_alumno_id');
    }

    public function emisor()
    {
        return $this->belongsTo(User::class, 'emitida_por');
    }
}
