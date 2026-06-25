<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrayectoriaAcademica extends Model
{
    use HasFactory;

    protected $table = 'trayectorias_academicas';

    protected $fillable = [
        'inscripcion_id',
        'ciclo_escolar_id',
        'ciclo_id',
        'nivel_id',
        'grado_id',
        'generacion_id',
        'grupo_id',
        'semestre_id',
        'activo',
        'fecha_baja',
        'motivo_baja',
        'observaciones_baja',
        'fecha_inscripcion',
        'promovido',
        'fecha_promocion',
        'trayectoria_origen_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_baja' => 'datetime',
        'fecha_inscripcion' => 'datetime',
        'promovido' => 'boolean',
        'fecha_promocion' => 'datetime',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class, 'generacion_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }
    public function trayectoriaOrigen()
    {
        return $this->belongsTo(TrayectoriaAcademica::class, 'trayectoria_origen_id');
    }

    public function trayectoriasDerivadas()
    {
        return $this->hasMany(TrayectoriaAcademica::class, 'trayectoria_origen_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoAlumno::class, 'trayectoria_academica_id');
    }
}
