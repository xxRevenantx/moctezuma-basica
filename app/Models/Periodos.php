<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periodos extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodosFactory> */
    use HasFactory;

    protected $fillable = [
        "nivel_id",
        "generacion_id",
        "semestre_id",
        "ciclo_escolar_id",
        "mes_bachillerato_id",
        "fecha_inicio",
        "fecha_fin",
    ];

    // RELACIONES CON GENERACIONES
    public function generacion()
    {
        return $this->belongsTo(Generacion::class, 'generacion_id');
    }

    // RELACIONES CON SEMESTRES
    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }

    // RELACIONES CON CICLOS ESCOLARES
    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    // RELACIONES CON MESES BACHILLERATO
    public function mesesBachillerato()
    {
        return $this->belongsTo(MesesBachillerato::class, 'mes_bachillerato_id');
    }
}
