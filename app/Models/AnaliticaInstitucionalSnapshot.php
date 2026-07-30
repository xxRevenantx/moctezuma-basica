<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnaliticaInstitucionalSnapshot extends Model
{
    protected $table = 'analitica_institucional_snapshots';

    protected $guarded = [];

    protected $casts = [
        'filtros' => 'array',
        'datos' => 'array',
        'generado_at' => 'datetime',
    ];

    public function cicloEscolar() { return $this->belongsTo(CicloEscolar::class); }
    public function nivel() { return $this->belongsTo(Nivel::class); }
    public function generacion() { return $this->belongsTo(Generacion::class); }
    public function grado() { return $this->belongsTo(Grado::class); }
    public function grupo() { return $this->belongsTo(Grupo::class); }
    public function generador() { return $this->belongsTo(User::class, 'generado_por'); }
}
