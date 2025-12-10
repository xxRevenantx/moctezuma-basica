<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodosBachillerato extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodosBachilleratoFactory> */
    use HasFactory;

    protected $fillable = [
        "generacion_id",
        "semestre_id",
        "ciclo_escolar_id",
        "mes_id",
        "fecha_inicio",
        "fecha_fin",
    ];

    // RELACIONES CON GENERACIONES
    public function generacion(){
        return $this->belongsTo(Generacion::class, 'generacion_id');
    }

    // RELACIONES CON SEMESTRES
    public function semestre(){
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }

    // RELACIONES CON CICLOS ESCOLARES
    public function cicloEscolar(){
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    // RELACIONES CON MESES BACHILLERATO
    public function mesesBachillerato(){
        return $this->belongsTo(MesesBachillerato::class, 'mes_id');
    }
}
