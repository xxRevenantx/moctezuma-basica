<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cicloEscolar extends Model
{
    /** @use HasFactory<\Database\Factories\CicloEscolarFactory> */
    protected $table = 'ciclo_escolares';
    use HasFactory;

    protected $fillable = [
        'inicio_anio',
        'fin_anio',
    ];


    // Relación con Periodos_basico
    public function periodosBasicos()
    {
        return $this->hasMany(Periodos_basico::class, 'ciclo_escolar_id');
    }
    public function periodos()
    {
        return $this->hasMany(Periodo::class, 'ciclo_escolar_id');
    }

    public function periodosBachillerato()
    {
        return $this->hasMany(PeriodosBachillerato::class, 'ciclo_escolar_id');
    }

    public function docenteGrupos()
{
    return $this->hasMany(\App\Models\DocenteGrupo::class, 'ciclo_escolar_id');
}

// Obtener grupos del ciclo (si te sirve):
public function grupos()
{
    return $this->belongsToMany(\App\Models\Grupo::class, 'docente_grupo', 'ciclo_escolar_id', 'grupo_id')
        ->withPivot(['persona_id', 'es_tutor'])
        ->withTimestamps();
}

// Obtener docentes del ciclo:
public function docentes()
{
    return $this->belongsToMany(\App\Models\Persona::class, 'docente_grupo', 'ciclo_escolar_id', 'persona_id')
        ->withPivot(['grupo_id', 'es_tutor'])
        ->withTimestamps();
}

}
