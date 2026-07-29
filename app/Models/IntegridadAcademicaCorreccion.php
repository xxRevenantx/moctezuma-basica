<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegridadAcademicaCorreccion extends Model
{
    protected $table = 'integridad_academica_correcciones';
    protected $guarded = [];

    protected $casts = [
        'parametros' => 'array',
        'respaldo_anterior' => 'array',
        'resultado_aplicado' => 'array',
        'aplicada_at' => 'datetime',
        'revertida_at' => 'datetime',
    ];

    public function caso()
    {
        return $this->belongsTo(IntegridadAcademicaCaso::class, 'caso_id');
    }

    public function usuarioAplico()
    {
        return $this->belongsTo(User::class, 'aplicada_por');
    }

    public function usuarioRevirtio()
    {
        return $this->belongsTo(User::class, 'revertida_por');
    }

    public function getPuedeRevertirseAttribute(): bool
    {
        return $this->estado === 'aplicada' && ! $this->revertida_at;
    }
}
