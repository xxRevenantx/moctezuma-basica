<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Director extends Model
{
    /** @use HasFactory<\Database\Factories\DirectorFactory> */
    use HasFactory;

    protected $table = 'directores';
    protected $fillable = [
        'titulo',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'curp',
        'rfc',
        'cargo',
        'identificador',
        'zona_escolar',
        'sector',
        'telefono',
        'correo',
        'genero',
        'status',
    ];

    public function nivelesDirector()
    {
        return $this->hasMany(Nivel::class, 'director_id');
    }

    public function nivelesSupervisor()
    {
        return $this->hasMany(Nivel::class, 'supervisor_id');
    }

public function niveles()
{
    return $this->belongsToMany(\App\Models\Nivel::class, 'director_nivel', 'director_id', 'nivel_id')
        ->withTimestamps();
}


}
