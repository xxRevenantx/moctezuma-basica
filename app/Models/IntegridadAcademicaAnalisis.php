<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegridadAcademicaAnalisis extends Model
{
    protected $table = 'integridad_academica_analisis';
    protected $guarded = [];

    protected $casts = [
        'filtros' => 'array',
        'resumen' => 'array',
        'iniciado_at' => 'datetime',
        'finalizado_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'ejecutado_por');
    }

    public function casos()
    {
        return $this->hasMany(IntegridadAcademicaCaso::class, 'ultimo_analisis_id');
    }
}
