<?php

namespace App\Models;

use App\Observers\AsignacionMateriaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AsignacionMateriaObserver::class)]
class AsignacionMateria extends Model
{
    use HasFactory;

    protected $fillable = [
        'materia_id',
        'grupo_id',
        'profesor_id',
        'orden',
    ];

    protected $casts = [
        'materia_id' => 'integer',
        'grupo_id' => 'integer',
        'profesor_id' => 'integer',
        'orden' => 'integer',
    ];

    // RELACIÓN CON EL CATÁLOGO DE MATERIAS
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }

    // RELACIÓN CON GRUPOS
    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    // RELACIÓN CON PROFESORES
    public function profesor()
    {
        return $this->belongsTo(Persona::class, 'profesor_id');
    }
}
