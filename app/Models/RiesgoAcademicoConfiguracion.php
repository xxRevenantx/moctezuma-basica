<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiesgoAcademicoConfiguracion extends Model
{
    protected $table = 'riesgo_academico_configuraciones';

    protected $guarded = [];

    protected $casts = [
        'valor' => 'array',
    ];

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
