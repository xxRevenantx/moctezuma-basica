<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nivel extends Model
{
    protected $table = "niveles";
    /** @use HasFactory<\Database\Factories\NivelFactory> */
    use HasFactory;
    protected $fillable = [
        'logo',
        'nombre',
        'slug',
        'cct',
        'color',
        'director_id',
        'supervisor_id',
    ];

    public function director()
    {
        return $this->belongsTo(Director::class, 'director_id');
    }
    public function supervisor()
    {
        return $this->belongsTo(Director::class, 'supervisor_id');
    }

    // RELACION CON GRADOS
    public function grados()
    {
        return $this->hasMany(Grado::class);
    }

    // RELACION CON GENERACIONES
    public function generaciones()
    {
        return $this->hasMany(Generacion::class);
    }

    // RELACION CON GRUPOS
    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }

    public function personaNiveles()
{
    return $this->hasMany(\App\Models\PersonaNivel::class);
}

 // INSCRIPCIONES
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function directivos()
{
    return $this->belongsToMany(\App\Models\Director::class, 'director_nivel', 'nivel_id', 'director_id')
        ->withTimestamps();
}


}
