<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatriculaAlumno extends Model
{
    use HasFactory;

    protected $table = 'matriculas_alumnos';

    protected $fillable = [
        'inscripcion_id',
        'nivel_id',
        'matricula',
        'fecha_asignacion',
        'fecha_fin',
        'vigente',
        'origen',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_asignacion' => 'date',
        'fecha_fin' => 'date',
        'vigente' => 'boolean',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id')->withTrashed();
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
