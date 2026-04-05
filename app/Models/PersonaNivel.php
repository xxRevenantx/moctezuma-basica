<?php

namespace App\Models;

use App\Observers\PersonaNivelObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(PersonaNivelObserver::class)]
class PersonaNivel extends Model
{
    use HasFactory;

    protected $table = 'persona_nivel';

    protected $fillable = [
        'persona_id',
        'nivel_id',
        'ingreso_seg',
        'ingreso_sep',
        'ingreso_ct',
        'orden',
    ];

    // =========================
    // Relaciones base
    // =========================

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function detalles()
    {
        return $this->hasMany(PersonaNivelDetalle::class, 'persona_nivel_id');
    }

    // =========================
    // Relaciones de apoyo
    // =========================
    // Estas relaciones permiten acceder al grado y grupo principal
    // desde la cabecera. Sirven para consultas rápidas y para evitar
    // el error de método indefinido en otros componentes.

    public function grado()
    {
        return $this->hasOneThrough(
            Grado::class,
            PersonaNivelDetalle::class,
            'persona_nivel_id', // FK en persona_nivel_detalles
            'id',               // PK en grados
            'id',               // PK en persona_nivel
            'grado_id'          // FK en persona_nivel_detalles hacia grados
        );
    }

    public function grupo()
    {
        return $this->hasOneThrough(
            Grupo::class,
            PersonaNivelDetalle::class,
            'persona_nivel_id', // FK en persona_nivel_detalles
            'id',               // PK en grupos
            'id',               // PK en persona_nivel
            'grupo_id'          // FK en persona_nivel_detalles hacia grupos
        );
    }
}
