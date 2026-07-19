<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    use HasFactory;

    protected $table = 'horarios';

    protected $fillable = [
        'nivel_id',
        'grado_id',
        'generacion_id',
        'semestre_id',
        'grupo_id',
        'hora_id',
        'dia_id',
        'asignacion_materia_id',
        'taller_sesion_id',
        'ciclo_escolar_id',
    ];

    public function asignacionMateria()
    {
        return $this->belongsTo(AsignacionMateria::class, 'asignacion_materia_id');
    }

    public function tallerSesion()
    {
        return $this->belongsTo(TallerSesion::class, 'taller_sesion_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function esTallerConjunto(): bool
    {
        return filled($this->taller_sesion_id);
    }

    public function nombreActividad(): string
    {
        if ($this->esTallerConjunto()) {
            return $this->tallerSesion?->taller?->nombre ?? 'Taller conjunto';
        }

        return $this->asignacionMateria?->materia?->materia ?? 'Materia no definida';
    }

    public function profesorActividad(): ?Persona
    {
        if ($this->esTallerConjunto()) {
            return $this->tallerSesion?->profesor;
        }

        return $this->asignacionMateria?->profesor;
    }

    public function hora()
    {
        return $this->belongsTo(Hora::class);
    }

    public function dia()
    {
        return $this->belongsTo(Dia::class);
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class, 'generacion_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }
}
