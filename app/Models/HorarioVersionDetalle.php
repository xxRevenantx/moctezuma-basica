<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioVersionDetalle extends Model
{
    protected $fillable = [
        'horario_version_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id',
        'grupo_id', 'hora_id', 'dia_id', 'asignacion_materia_id', 'taller_sesion_id',
        'profesor_id', 'sesion_compartida', 'clave_sesion_compartida',
        'motivo_sesion_compartida', 'traslape_excepcional',
        'motivo_traslape_excepcional', 'motivo_autorizacion_disponibilidad', 'coensenanza', 'bloqueado', 'origen',
    ];

    protected $casts = [
        'sesion_compartida' => 'boolean',
        'traslape_excepcional' => 'boolean',
        'coensenanza' => 'boolean',
        'bloqueado' => 'boolean',
    ];

    public function version() { return $this->belongsTo(HorarioVersion::class, 'horario_version_id'); }
    public function nivel() { return $this->belongsTo(Nivel::class); }
    public function grado() { return $this->belongsTo(Grado::class); }
    public function generacion() { return $this->belongsTo(Generacion::class); }
    public function semestre() { return $this->belongsTo(Semestre::class); }
    public function grupo() { return $this->belongsTo(Grupo::class); }
    public function hora() { return $this->belongsTo(Hora::class); }
    public function dia() { return $this->belongsTo(Dia::class); }
    public function asignacionMateria() { return $this->belongsTo(AsignacionMateria::class); }
    public function tallerSesion() { return $this->belongsTo(TallerSesion::class); }
    public function profesor() { return $this->belongsTo(Persona::class, 'profesor_id'); }

    public function getNombreActividadAttribute(): string
    {
        return $this->tallerSesion?->taller?->nombre
            ?? $this->asignacionMateria?->materia?->materia
            ?? 'Actividad sin definir';
    }
}
