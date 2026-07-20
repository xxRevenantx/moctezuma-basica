<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CicloEscolarNivel extends Model
{
    protected $table = 'ciclo_escolar_niveles';

    protected $fillable = [
        'ciclo_escolar_id',
        'nivel_id',
        'estado',
        'preparado_at',
        'preparado_por',
        'diagnostico',
        'observaciones',
    ];

    protected $casts = [
        'preparado_at' => 'datetime',
        'diagnostico' => 'array',
    ];

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function usuarioQuePreparo()
    {
        return $this->belongsTo(User::class, 'preparado_por');
    }
}
