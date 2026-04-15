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
    ];

    public function asignacionMateria()
    {
        return $this->belongsTo(AsignacionMateria::class, 'asignacion_materia_id');
    }

    public function hora()
    {
        return $this->belongsTo(Hora::class);
    }

    public function dia()
    {
        return $this->belongsTo(Dia::class);
    }
}
