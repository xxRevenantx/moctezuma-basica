<?php

namespace App\Models;

use App\Observers\AsignacionMateriaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AsignacionMateriaObserver::class)]
class AsignacionMateria extends Model
{
    /** @use HasFactory<\Database\Factories\AsignacionMateriaFactory> */
    use HasFactory;

    protected $fillable = [
        'materia_id',
        'grupo_id',
        'profesor_id',
        'orden',
    ];

    // RELACIONES
    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function profesor()
    {
        return $this->belongsTo(Persona::class, 'profesor_id');
    }
}
