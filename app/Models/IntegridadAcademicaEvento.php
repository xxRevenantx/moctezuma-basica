<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegridadAcademicaEvento extends Model
{
    public $timestamps = false;
    protected $table = 'integridad_academica_eventos';
    protected $guarded = [];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function caso()
    {
        return $this->belongsTo(IntegridadAcademicaCaso::class, 'caso_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
