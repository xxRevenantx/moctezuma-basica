<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periodos_basico extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodosBasicoFactory> */
    use HasFactory;

    protected $fillable = [
        'ciclo_escolar_id',
        'periodo_id',
        'parcial_inicio',
        'parcial_fin',
    ];

    // Relación con CicloEscolar
    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }
    public function periodos()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }
}
