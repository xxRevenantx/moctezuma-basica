<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioVersionEvento extends Model
{
    protected $fillable = [
        'horario_version_id', 'tipo', 'titulo', 'descripcion', 'metadata',
        'usuario_id', 'ocurrido_at',
    ];

    protected $casts = ['metadata' => 'array', 'ocurrido_at' => 'datetime'];

    public function version() { return $this->belongsTo(HorarioVersion::class, 'horario_version_id'); }
    public function usuario() { return $this->belongsTo(User::class); }
}
