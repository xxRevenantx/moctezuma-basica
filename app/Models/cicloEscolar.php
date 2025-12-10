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
}
