<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semestre extends Model
{
    /** @use HasFactory<\Database\Factories\SemestreFactory> */
    use HasFactory;
    protected $fillable = [
        'grado_id',
        'mes_id',
        'numero',
        'orden_global',

    ];

    // RELACION CON GRADOS
    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    // RELACION CON MESES DE BACHILLERATO
    public function mesesBachillerato()
    {
        return $this->belongsTo(MesesBachillerato::class, 'mes_id');
    }

    // RELACION CON GRUPOS
    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }
    // RELACION CON PERIODOS DE BACHILLERATO
    public function periodosBachillerato()
    {
        return $this->hasMany(PeriodosBachillerato::class);
    }
}
