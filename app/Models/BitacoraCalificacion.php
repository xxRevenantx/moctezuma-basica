<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BitacoraCalificacion extends Model
{
    use HasFactory;

    protected $table = 'bitacora_calificaciones';

    protected $fillable = [
        'user_id',
        'inscripcion_id',
        'asignacion_materia_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'generacion_id',
        'semestre_id',
        'periodo_id',
        'ciclo_escolar_id',
        'calificacion_anterior',
        'calificacion_nueva',
        'accion',
        'comentario',
    ];

    // Relaciones
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

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class);
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    public function periodo()
    {
        return $this->belongsTo(Periodos::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function asignacionMateria()
    {
        return $this->belongsTo(AsignacionMateria::class, 'asignacion_materia_id');
    }
}
